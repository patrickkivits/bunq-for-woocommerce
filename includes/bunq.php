<?php

/**
 * Run a bunq API call and retry it when bunq answers "too many requests" (3 GET, 5 POST or 2 PUT per 3 seconds).
 *
 * @param callable $callback
 * @param int $attempts
 * @return mixed
 * @throws Throwable
 */
function bunq_retry(callable $callback, $attempts = 3)
{
    for ($attempt = 1; ; $attempt++) {
        try {
            return $callback();
        } catch (\bunq\Exception\TooManyRequestsException $exception) {
            if ($attempt >= $attempts) {
                throw $exception;
            }

            bunq_helper_log('bunq rate limit hit, retrying in 3 seconds (attempt '.$attempt.' of '.$attempts.')', 'warning');
            sleep(3);
        }
    }
}

function bunq_environment($testmode) {
    return $testmode ? \bunq\Util\BunqEnumApiEnvironmentType::SANDBOX() : \bunq\Util\BunqEnumApiEnvironmentType::PRODUCTION();
}

function bunq_create_api_context($apiKey, $testmode) {

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

    $createBunqMeTab = bunq_retry(function() use ($bunqMeTabEntry, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::create($bunqMeTabEntry, $monetary_account_bank_id)->getValue();
    });

    $bunqMeRequest = bunq_retry(function() use ($createBunqMeTab, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::get($createBunqMeTab, $monetary_account_bank_id)->getValue();
    });

    return [
        'url' => $bunqMeRequest->getBunqmeTabShareUrl(),
        'id' => $bunqMeRequest->getId()
    ];
}

function bunq_get_bank_accounts($api_context)
{
    $bank_accounts = ['' => __('API key not valid or not setup yet', 'bunq-for-woocommerce')];

    if($api_context){
        try {
            $bank_accounts = ['' => __('Select a bank account', 'bunq-for-woocommerce')];
            $monetary_accounts_bank = bunq_retry(function() {
                return \bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject::listing()->getValue();
            });
            foreach ($monetary_accounts_bank as $monetaryAccountBank) {
                foreach($monetaryAccountBank->getAlias() as $alias) {
                    if($alias->getType() === 'IBAN'){
                        $bank_accounts[$monetaryAccountBank->getId()] = $alias->getValue().' - '.$monetaryAccountBank->getDescription();
                    }
                }
            }
            $monetary_accounts_joint = bunq_retry(function() {
                return \bunq\Model\Generated\Endpoint\MonetaryAccountJointApiObject::listing()->getValue();
            });
            foreach ($monetary_accounts_joint as $monetaryAccountJoint) {
                foreach($monetaryAccountJoint->getAlias() as $alias) {
                    if($alias->getType() === 'IBAN'){
                        $bank_accounts[$monetaryAccountJoint->getId()] = $alias->getValue().' - '.$monetaryAccountJoint->getDescription().' '.__('(Joint)', 'bunq-for-woocommerce');
                    }
                }
            }
        }
        catch (Throwable $exception) {
            bunq_helper_log($exception);
            $bank_accounts = ['' => sprintf(
                /* translators: %s: error message from bunq */
                __('Error: %s', 'bunq-for-woocommerce'),
                bunq_helper_format_error($exception)
            )];
        }
    }

    return $bank_accounts;
}

function bunq_get_payment_request($payment_request_id, $monetary_account_bank_id = null)
{

    return bunq_retry(function() use ($payment_request_id, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::get($payment_request_id, $monetary_account_bank_id)->getValue();
    });
}

/**
 * Cancel a bunq.me payment request so it can no longer be paid.
 */
function bunq_cancel_payment_request($payment_request_id, $monetary_account_bank_id = null)
{

    bunq_retry(function() use ($payment_request_id, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\BunqMeTabApiObject::update($payment_request_id, $monetary_account_bank_id, 'CANCELLED');
    });
}

/**
 * @return \bunq\Model\Generated\Endpoint\PaymentApiObject The payment as received on the bunq account.
 */
function bunq_get_payment($payment_id, $monetary_account_bank_id = null)
{

    return bunq_retry(function() use ($payment_id, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\PaymentApiObject::get($payment_id, $monetary_account_bank_id)->getValue();
    });
}

/**
 * Send money back to an IBAN (a refund of a received payment).
 *
 * @param float $amount
 * @param string $currency
 * @param string $iban
 * @param string $name Account holder name, required by bunq for IBAN pointers.
 * @param string $description Shown to the recipient; bunq allows 140 characters.
 * @param int|null $monetary_account_bank_id
 * @return int The id of the outgoing payment.
 */
function bunq_create_refund($amount, $currency, $iban, $name, $description, $monetary_account_bank_id = null)
{
    $amount = new \bunq\Model\Generated\Object\AmountObject(number_format((float) $amount, 2, '.', ''), $currency);
    $counterparty = new \bunq\Model\Generated\Object\PointerObject('IBAN', $iban, $name);
    $description = mb_substr($description, 0, 140);


    return bunq_retry(function() use ($amount, $counterparty, $description, $monetary_account_bank_id) {
        return \bunq\Model\Generated\Endpoint\PaymentApiObject::create($amount, $counterparty, $description, $monetary_account_bank_id)->getValue();
    });
}

function bunq_get_notification_filters($monetary_account_bank_id = null)
{
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

    \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::createWithListResponse($monetary_account_bank_id, $notification_filters);
}