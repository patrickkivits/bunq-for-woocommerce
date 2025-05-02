<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class ShareDetailObject extends BunqModel
{
    /**
     * Some of the fields in this model have custom names in the bunq API and therefore must be overridden.
     */
    protected static $fieldNameOverrideMap = [
        'payment_field_for_request' => 'ShareDetailPayment',
        'read_only_field_for_request' => 'ShareDetailReadOnly',
        'draft_payment_field_for_request' => 'ShareDetailDraftPayment',
    ];

    /**
     * The share details for a payment share. In the response 'payment' is replaced by 'ShareDetailPayment'.
     *
     * @var ShareDetailPaymentObject
     */
    protected $payment;

    /**
     * The share details for viewing a share. In the response 'read_only' is replaced by 'ShareDetailReadOnly'.
     *
     * @var ShareDetailReadOnlyObject
     */
    protected $readOnly;

    /**
     * The share details for a draft payment share. In the response 'draft_payment' is replaced by 'ShareDetailDraftPayment'.
     *
     * @var ShareDetailDraftPaymentObject
     */
    protected $draftPayment;

    /**
     * The share details for a payment share. Remember to replace 'payment' with 'ShareDetailPayment' before sending a request.
     *
     * @var ShareDetailPaymentObject|null
     */
    protected $paymentFieldForRequest;

    /**
     * The share details for viewing a share. Remember to replace 'read_only' with 'ShareDetailReadOnly' before sending a
     * request.
     *
     * @var ShareDetailReadOnlyObject|null
     */
    protected $readOnlyFieldForRequest;

    /**
     * The share details for a draft payment share. Remember to replace 'draft_payment' with 'ShareDetailDraftPayment' before
     * sending a request.
     *
     * @var ShareDetailDraftPaymentObject|null
     */
    protected $draftPaymentFieldForRequest;

    /**
     * @param ShareDetailPaymentObject|null $payment The share details for a payment share. Remember to replace 'payment' with
     * 'ShareDetailPayment' before sending a request.
     * @param ShareDetailReadOnlyObject|null $readOnly The share details for viewing a share. Remember to replace 'read_only'
     * with 'ShareDetailReadOnly' before sending a request.
     * @param ShareDetailDraftPaymentObject|null $draftPayment The share details for a draft payment share. Remember to replace
     * 'draft_payment' with 'ShareDetailDraftPayment' before sending a request.
     */
    public function __construct(ShareDetailPaymentObject  $payment = null, ShareDetailReadOnlyObject  $readOnly = null, ShareDetailDraftPaymentObject  $draftPayment = null)
    {
        $this->paymentFieldForRequest = $payment;
        $this->readOnlyFieldForRequest = $readOnly;
        $this->draftPaymentFieldForRequest = $draftPayment;
    }

    /**
     * The share details for a payment share. In the response 'payment' is replaced by 'ShareDetailPayment'.
     *
     * @return ShareDetailPaymentObject
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareDetailPaymentObject $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * The share details for viewing a share. In the response 'read_only' is replaced by 'ShareDetailReadOnly'.
     *
     * @return ShareDetailReadOnlyObject
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareDetailReadOnlyObject $readOnly
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
    }

    /**
     * The share details for a draft payment share. In the response 'draft_payment' is replaced by 'ShareDetailDraftPayment'.
     *
     * @return ShareDetailDraftPaymentObject
     */
    public function getDraftPayment()
    {
        return $this->draftPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareDetailDraftPaymentObject $draftPayment
     */
    public function setDraftPayment($draftPayment)
    {
        $this->draftPayment = $draftPayment;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->readOnly)) {
            return false;
        }

        if (!is_null($this->draftPayment)) {
            return false;
        }

        return true;
    }
}
