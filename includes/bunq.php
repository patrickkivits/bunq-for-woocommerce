<?php

require_once (__DIR__.'/RequestThrottler.php');

if (empty($GLOBALS['requestThrottler'])) {
    $GLOBALS['requestThrottler'] = new RequestThrottler(new RequestThrottlerConfiguration());
}

function bunq_environment($testmode) {
    return $testmode ? \bunq\Util\BunqEnumApiEnvironmentType::SANDBOX() : \bunq\Util\BunqEnumApiEnvironmentType::PRODUCTION();
}

function bunq_create_api_context($apiKey, $testmode) {
	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Core\DeviceServerInternal::ENDPOINT_URL_CREATE, 'POST');
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Core\SessionServer::ENDPOINT_URL_POST, 'POST');

    $apiContext = \bunq\Context\ApiContext::create(
        bunq_environment($testmode),
        $apiKey,
        'bunq for WooCommerce'
    );

    bunq_load_api_context_from_json($apiContext->toJson());

    return $apiContext;
}

function bunq_load_api_context_from_json($json) {

    if($json)
    {
        try {
            $apiContext = \bunq\Context\ApiContext::fromJson($json);

            try {
                $currentContext = \bunq\Context\BunqContext::getApiContext();
                if($currentContext->getApiKey() === $apiContext->getApiKey() && $currentContext->isSessionActive()) {
                    // Re-use current context, return early
                    return $json;
                }
            } catch (Throwable $exception) {
                // No context loaded yet in this request, this is expected.
            }

            // the current context expired, ensure active session and load api context with new session
            $apiContext->ensureSessionActive();

	        global $requestThrottler;
	        $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject::ENDPOINT_URL_LISTING, 'GET');
            \bunq\Context\BunqContext::loadApiContext($apiContext);

            return $apiContext->toJson();
        }
        catch (Throwable $exception){
            bunq_helper_log($exception);
        }
    }

    return false;
}

function bunq_create_payment_request($amount, $currency, $description, $returnUrl, $monetary_account_bank_id = null)
{
    $amount = new \bunq\Model\Generated\Object\AmountObject($amount, $currency);
	$bunqMeTabEntry = new \bunq\Model\Generated\Endpoint\BunqMeTabEntryApiObject($amount, $description, $returnUrl);

	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTabApiObject::ENDPOINT_URL_CREATE, 'POST');
    $createBunqMeTab = \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::create($bunqMeTabEntry, $monetary_account_bank_id)->getValue();

    $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTabApiObject::ENDPOINT_URL_READ, 'GET');
    $bunqMeRequest = \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::get($createBunqMeTab, $monetary_account_bank_id)->getValue();

    return [
        'url' => $bunqMeRequest->getBunqmeTabShareUrl(),
        'id' => $bunqMeRequest->getId()
    ];
}

function bunq_get_bank_accounts($api_context)
{
    $bank_accounts = ['' => 'API key not valid or not setup yet'];

    if($api_context){
        try {
            $bank_accounts = ['' => 'Select a bank account'];
            global $requestThrottler;
            $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject::ENDPOINT_URL_LISTING, 'GET');
            foreach (\bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject::listing()->getValue() as $monetaryAccountBank) {
                foreach($monetaryAccountBank->getAlias() as $alias) {
                    if($alias->getType() === 'IBAN'){
                        $bank_accounts[$monetaryAccountBank->getId()] = $alias->getValue().' - '.$monetaryAccountBank->getDescription();
                    }
                }
            }
            $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountJointApiObject::ENDPOINT_URL_LISTING, 'GET');
            foreach (\bunq\Model\Generated\Endpoint\MonetaryAccountJointApiObject::listing()->getValue() as $monetaryAccountJoint) {
                foreach($monetaryAccountJoint->getAlias() as $alias) {
                    if($alias->getType() === 'IBAN'){
                        $bank_accounts[$monetaryAccountJoint->getId()] = $alias->getValue().' - '.$monetaryAccountJoint->getDescription().' (Joint)';
                    }
                }
            }
        }
        catch (Throwable $exception) {
            bunq_helper_log($exception);
            $bank_accounts = ['' => 'Error: '.bunq_helper_format_error($exception)];
        }
    }

    return $bank_accounts;
}

function bunq_get_payment_request($payment_id, $monetary_account_bank_id = null)
{
	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTabApiObject::ENDPOINT_URL_READ, 'GET');
    $payment = \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::get($payment_id, $monetary_account_bank_id);

    return $payment->getValue();
}

function bunq_get_notification_filters($monetary_account_bank_id = null)
{
	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\NotificationFilterUrlMonetaryAccountApiObject::ENDPOINT_URL_LISTING, 'GET');
    return \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::listing($monetary_account_bank_id)->getValue();
}

function bunq_create_notification_filters($monetary_account_bank_id = null)
{
    $callback_url = WC()->api_request_url('wc_bunq_gateway');

    // bunq replaces the complete list of URL notification filters of the monetary account with the posted list,
    // so keep the filters that are already there (other integrations) and add ours to them.
    $notification_filters = [];

    try {
        foreach (bunq_get_notification_filters($monetary_account_bank_id) as $existing_filter_list) {
            foreach ((array) $existing_filter_list->getNotificationFilters() as $existing_filter) {
                if ($existing_filter->getCategory() === 'BUNQME_TAB' && $existing_filter->getNotificationTarget() === $callback_url) {
                    continue; // Ours, added below.
                }

                // Listed objects also carry response fields (id, created, ...) that must not be posted back.
                $notification_filters[] = new \bunq\Model\Generated\Object\NotificationFilterUrlObject(
                    $existing_filter->getCategory(),
                    $existing_filter->getNotificationTarget(),
                    $existing_filter->getAllUserId(),
                    $existing_filter->getAllMonetaryAccountId(),
                    $existing_filter->getAllVerificationType()
                );
            }
        }
    } catch (Throwable $exception) {
        // Registering our callback matters more than preserving the others; log what happened.
        bunq_helper_log('Could not read the existing bunq notification filters, registering only the WooCommerce callback: '.bunq_helper_format_error($exception), 'warning');
        $notification_filters = [];
    }

    $notification_filters[] = new \bunq\Model\Generated\Object\NotificationFilterUrlObject('BUNQME_TAB', $callback_url);

	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::ENDPOINT_URL_CREATE, 'POST');
    \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::createWithListResponse($monetary_account_bank_id, $notification_filters);
}