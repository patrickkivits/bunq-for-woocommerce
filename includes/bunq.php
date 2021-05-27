<?php

require_once (__DIR__.'/RequestThrottler.php');
$requestThrottler = new RequestThrottler(new RequestThrottlerConfiguration());

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
            $apiContext->ensureSessionActive();
	        global $requestThrottler;
	        $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountBank::ENDPOINT_URL_LISTING, 'GET');
            \bunq\Context\BunqContext::loadApiContext($apiContext);

            return $apiContext->toJson();
        }
        catch (Exception $exception){
            if(defined( 'WP_DEBUG' ) && WP_DEBUG) {
                error_log($exception->getMessage());
            }
        }
    }

    return false;
}

function bunq_create_payment_request($amount, $currency, $description, $returnUrl, $monetary_account_bank_id = null)
{
    $amount = new \bunq\Model\Generated\Object\Amount($amount, $currency);
	$bunqMeTabEntry = new \bunq\Model\Generated\Endpoint\BunqMeTabEntry($amount, $description, $returnUrl);

	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTab::ENDPOINT_URL_CREATE, 'POST');
    $createBunqMeTab = \bunq\Model\Generated\Endpoint\BunqMeTab::create($bunqMeTabEntry, $monetary_account_bank_id)->getValue();

    $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTab::ENDPOINT_URL_READ, 'GET');
    $bunqMeRequest = \bunq\Model\Generated\Endpoint\BunqMeTab::get($createBunqMeTab, $monetary_account_bank_id)->getValue();

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
            $transient = 'wc_bunq_gateway.bunq_get_bank_accounts';
            if ( false === ($bank_accounts = get_transient($transient))) {
                $bank_accounts = ['' => 'Select a bank account'];
	            global $requestThrottler;
	            $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountBank::ENDPOINT_URL_LISTING, 'GET');
                foreach (\bunq\Model\Generated\Endpoint\MonetaryAccountBank::listing()->getValue() as $monetaryAccountBank) {
                    foreach($monetaryAccountBank->getAlias() as $alias) {
                        if($alias->getType() === 'IBAN'){
                            $bank_accounts[$monetaryAccountBank->getId()] = $alias->getValue().' - '.$monetaryAccountBank->getDescription();
                        }
                    }
                }
                $requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\MonetaryAccountJoint::ENDPOINT_URL_LISTING, 'GET');
                foreach (\bunq\Model\Generated\Endpoint\MonetaryAccountJoint::listing()->getValue() as $monetaryAccountJoint) {
                    foreach($monetaryAccountJoint->getAlias() as $alias) {
                        if($alias->getType() === 'IBAN'){
                            $bank_accounts[$monetaryAccountJoint->getId()] = $alias->getValue().' - '.$monetaryAccountJoint->getDescription().' (Joint)';
                        }
                    }
                }
                set_transient($transient, $bank_accounts);
            }
        }
        catch (Exception $exception) {
            if(defined( 'WP_DEBUG' ) && WP_DEBUG) {
                error_log($exception->getMessage());
            }
            $bank_accounts = ['' => 'Error: '.$exception->getMessage()];
        }
    }

    return $bank_accounts;
}

function bunq_get_payment_request($payment_id, $monetary_account_bank_id = null)
{
	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\BunqMeTab::ENDPOINT_URL_READ, 'GET');
    $payment = \bunq\Model\Generated\Endpoint\BunqMeTab::get($payment_id, $monetary_account_bank_id);

    return $payment->getValue();
}

function bunq_get_notification_filters($monetary_account_bank_id = null)
{
	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Generated\Endpoint\NotificationFilterUrlMonetaryAccount::ENDPOINT_URL_LISTING, 'GET');
    return \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::listing($monetary_account_bank_id)->getValue();
}

function bunq_create_notification_filters($monetary_account_bank_id = null)
{
    $notification_filters = [
        new \bunq\Model\Generated\Object\NotificationFilterUrl('BUNQME_TAB', WC()->api_request_url('wc_bunq_gateway'))
    ];

	global $requestThrottler;
	$requestThrottler->ensureApiLimitsAreRespected(\bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::ENDPOINT_URL_CREATE, 'POST');
    \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::createWithListResponse($monetary_account_bank_id, $notification_filters);
}