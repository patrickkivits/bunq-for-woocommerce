<?php

function bunq_enviroment($testmode) {
    return $testmode ? \bunq\Util\BunqEnumApiEnvironmentType::SANDBOX() : \bunq\Util\BunqEnumApiEnvironmentType::PRODUCTION();
}

function bunq_create_api_context($apiKey, $testmode) {

    sleep(4); // Prevent rate-limit error with bunq API

    $apiContext = \bunq\Context\ApiContext::create(
        bunq_enviroment($testmode),
        $apiKey,
        'bunq for WooCommerce'
    );

    bunq_load_api_context($apiContext->toJson());

    return $apiContext;
}

function bunq_load_api_context($apiContext) {

    if(!$apiContext)
    {
        return false;
    }

    \bunq\Context\BunqContext::loadApiContext(\bunq\Context\ApiContext::fromJson($apiContext));
}

function bunq_create_payment_request($amount, $currency, $description, $returnUrl, $monetary_account_bank_id = null)
{
    $amount = new \bunq\Model\Generated\Object\Amount($amount, $currency);
    $bunqMeTabEntry = new \bunq\Model\Generated\Endpoint\BunqMeTabEntry($amount, $description, $returnUrl);
    $createBunqMeTab = \bunq\Model\Generated\Endpoint\BunqMeTab::create($bunqMeTabEntry, $monetary_account_bank_id)->getValue();
    $bunqMeRequest = \bunq\Model\Generated\Endpoint\BunqMeTab::get($createBunqMeTab, $monetary_account_bank_id)->getValue();

    return [
        'url' => $bunqMeRequest->getBunqmeTabShareUrl(),
        'id' => $bunqMeRequest->getId()
    ];
}

function bunq_get_bank_accounts($api_context)
{
    $bank_accounts = [];

    if($api_context){
        sleep(4);
        foreach (\bunq\Model\Generated\Endpoint\MonetaryAccountBank::listing()->getValue() as $monetaryAccountBank) {
            foreach($monetaryAccountBank->getAlias() as $alias) {
                if($monetaryAccountBank->getUserId() === \bunq\Context\BunqContext::getUserContext()->getUserId() && $alias->getType() === 'IBAN'){
                    $bank_accounts[$monetaryAccountBank->getId()] = $alias->getValue().' - '.$monetaryAccountBank->getDescription();
                }
            }
        }
    }
    else
    {
        $bank_accounts = [
            '' => 'Please enter API key first'
        ];
    }

    return $bank_accounts;
}

function bunq_get_payment_request($payment_id, $monetary_account_bank_id = null)
{
    $payment = \bunq\Model\Generated\Endpoint\BunqMeTab::get($payment_id, $monetary_account_bank_id);

    return $payment->getValue();
}

function bunq_get_notification_filters($monetary_account_bank_id = null)
{
    return \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::listing($monetary_account_bank_id)->getValue();
}

function bunq_create_notification_filters($monetary_account_bank_id = null)
{
    $notification_filters = [
        new \bunq\Model\Generated\Object\NotificationFilterUrl('BUNQME_TAB', WC()->api_request_url('wc_bunq_gateway'))
    ];

    \bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal::createWithListResponse($monetary_account_bank_id, $notification_filters);

    sleep(4); // Prevent rate-limit error with bunq API
}