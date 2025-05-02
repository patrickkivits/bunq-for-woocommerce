<?php
namespace bunq\Model\Generated\Object;

use bunq\Exception\BunqException;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\BunqMeFundraiserResultApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabResultInquiryApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabResultResponseApiObject;
use bunq\Model\Generated\Endpoint\ChatMessageApiObject;
use bunq\Model\Generated\Endpoint\DraftPaymentApiObject;
use bunq\Model\Generated\Endpoint\IdealMerchantTransactionApiObject;
use bunq\Model\Generated\Endpoint\InvoiceApiObject;
use bunq\Model\Generated\Endpoint\MasterCardActionApiObject;
use bunq\Model\Generated\Endpoint\MonetaryAccountApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Endpoint\PaymentBatchApiObject;
use bunq\Model\Generated\Endpoint\RequestInquiryApiObject;
use bunq\Model\Generated\Endpoint\RequestInquiryBatchApiObject;
use bunq\Model\Generated\Endpoint\RequestResponseApiObject;
use bunq\Model\Generated\Endpoint\ScheduleInstanceApiObject;
use bunq\Model\Generated\Endpoint\SchedulePaymentApiObject;
use bunq\Model\Generated\Endpoint\ShareInviteMonetaryAccountInquiryApiObject;
use bunq\Model\Generated\Endpoint\ShareInviteMonetaryAccountResponseApiObject;
use bunq\Model\Generated\Endpoint\UserApiObject;

/**
 * @generated
 */
class NotificationAnchorObjectObject extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * @var BunqMeFundraiserResultApiObject
     */
    protected $bunqMeFundraiserResult;

    /**
     * @var BunqMeTabApiObject
     */
    protected $bunqMeTab;

    /**
     * @var BunqMeTabResultInquiryApiObject
     */
    protected $bunqMeTabResultInquiry;

    /**
     * @var BunqMeTabResultResponseApiObject
     */
    protected $bunqMeTabResultResponse;

    /**
     * @var ChatMessageApiObject
     */
    protected $chatMessage;

    /**
     * @var DraftPaymentApiObject
     */
    protected $draftPayment;

    /**
     * @var IdealMerchantTransactionApiObject
     */
    protected $idealMerchantTransaction;

    /**
     * @var InvoiceApiObject
     */
    protected $invoice;

    /**
     * @var MasterCardActionApiObject
     */
    protected $masterCardAction;

    /**
     * @var MonetaryAccountApiObject
     */
    protected $monetaryAccount;

    /**
     * @var PaymentApiObject
     */
    protected $payment;

    /**
     * @var PaymentBatchApiObject
     */
    protected $paymentBatch;

    /**
     * @var RequestInquiryApiObject
     */
    protected $requestInquiry;

    /**
     * @var RequestInquiryBatchApiObject
     */
    protected $requestInquiryBatch;

    /**
     * @var RequestResponseApiObject
     */
    protected $requestResponse;

    /**
     * @var ShareInviteMonetaryAccountInquiryApiObject
     */
    protected $shareInviteBankInquiry;

    /**
     * @var ShareInviteMonetaryAccountResponseApiObject
     */
    protected $shareInviteBankResponse;

    /**
     * @var SchedulePaymentApiObject
     */
    protected $scheduledPayment;

    /**
     * @var ScheduleInstanceApiObject
     */
    protected $scheduledInstance;

    /**
     * @var UserApiObject
     */
    protected $user;

    /**
     * @return BunqMeFundraiserResultApiObject
     */
    public function getBunqMeFundraiserResult()
    {
        return $this->bunqMeFundraiserResult;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqMeFundraiserResultApiObject $bunqMeFundraiserResult
     */
    public function setBunqMeFundraiserResult($bunqMeFundraiserResult)
    {
        $this->bunqMeFundraiserResult = $bunqMeFundraiserResult;
    }

    /**
     * @return BunqMeTabApiObject
     */
    public function getBunqMeTab()
    {
        return $this->bunqMeTab;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqMeTabApiObject $bunqMeTab
     */
    public function setBunqMeTab($bunqMeTab)
    {
        $this->bunqMeTab = $bunqMeTab;
    }

    /**
     * @return BunqMeTabResultInquiryApiObject
     */
    public function getBunqMeTabResultInquiry()
    {
        return $this->bunqMeTabResultInquiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqMeTabResultInquiryApiObject $bunqMeTabResultInquiry
     */
    public function setBunqMeTabResultInquiry($bunqMeTabResultInquiry)
    {
        $this->bunqMeTabResultInquiry = $bunqMeTabResultInquiry;
    }

    /**
     * @return BunqMeTabResultResponseApiObject
     */
    public function getBunqMeTabResultResponse()
    {
        return $this->bunqMeTabResultResponse;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqMeTabResultResponseApiObject $bunqMeTabResultResponse
     */
    public function setBunqMeTabResultResponse($bunqMeTabResultResponse)
    {
        $this->bunqMeTabResultResponse = $bunqMeTabResultResponse;
    }

    /**
     * @return ChatMessageApiObject
     */
    public function getChatMessage()
    {
        return $this->chatMessage;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ChatMessageApiObject $chatMessage
     */
    public function setChatMessage($chatMessage)
    {
        $this->chatMessage = $chatMessage;
    }

    /**
     * @return DraftPaymentApiObject
     */
    public function getDraftPayment()
    {
        return $this->draftPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentApiObject $draftPayment
     */
    public function setDraftPayment($draftPayment)
    {
        $this->draftPayment = $draftPayment;
    }

    /**
     * @return IdealMerchantTransactionApiObject
     */
    public function getIdealMerchantTransaction()
    {
        return $this->idealMerchantTransaction;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param IdealMerchantTransactionApiObject $idealMerchantTransaction
     */
    public function setIdealMerchantTransaction($idealMerchantTransaction)
    {
        $this->idealMerchantTransaction = $idealMerchantTransaction;
    }

    /**
     * @return InvoiceApiObject
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param InvoiceApiObject $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return MasterCardActionApiObject
     */
    public function getMasterCardAction()
    {
        return $this->masterCardAction;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MasterCardActionApiObject $masterCardAction
     */
    public function setMasterCardAction($masterCardAction)
    {
        $this->masterCardAction = $masterCardAction;
    }

    /**
     * @return MonetaryAccountApiObject
     */
    public function getMonetaryAccount()
    {
        return $this->monetaryAccount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountApiObject $monetaryAccount
     */
    public function setMonetaryAccount($monetaryAccount)
    {
        $this->monetaryAccount = $monetaryAccount;
    }

    /**
     * @return PaymentApiObject
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentApiObject $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return PaymentBatchApiObject
     */
    public function getPaymentBatch()
    {
        return $this->paymentBatch;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentBatchApiObject $paymentBatch
     */
    public function setPaymentBatch($paymentBatch)
    {
        $this->paymentBatch = $paymentBatch;
    }

    /**
     * @return RequestInquiryApiObject
     */
    public function getRequestInquiry()
    {
        return $this->requestInquiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestInquiryApiObject $requestInquiry
     */
    public function setRequestInquiry($requestInquiry)
    {
        $this->requestInquiry = $requestInquiry;
    }

    /**
     * @return RequestInquiryBatchApiObject
     */
    public function getRequestInquiryBatch()
    {
        return $this->requestInquiryBatch;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestInquiryBatchApiObject $requestInquiryBatch
     */
    public function setRequestInquiryBatch($requestInquiryBatch)
    {
        $this->requestInquiryBatch = $requestInquiryBatch;
    }

    /**
     * @return RequestResponseApiObject
     */
    public function getRequestResponse()
    {
        return $this->requestResponse;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestResponseApiObject $requestResponse
     */
    public function setRequestResponse($requestResponse)
    {
        $this->requestResponse = $requestResponse;
    }

    /**
     * @return ShareInviteMonetaryAccountInquiryApiObject
     */
    public function getShareInviteBankInquiry()
    {
        return $this->shareInviteBankInquiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareInviteMonetaryAccountInquiryApiObject $shareInviteBankInquiry
     */
    public function setShareInviteBankInquiry($shareInviteBankInquiry)
    {
        $this->shareInviteBankInquiry = $shareInviteBankInquiry;
    }

    /**
     * @return ShareInviteMonetaryAccountResponseApiObject
     */
    public function getShareInviteBankResponse()
    {
        return $this->shareInviteBankResponse;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareInviteMonetaryAccountResponseApiObject $shareInviteBankResponse
     */
    public function setShareInviteBankResponse($shareInviteBankResponse)
    {
        $this->shareInviteBankResponse = $shareInviteBankResponse;
    }

    /**
     * @return SchedulePaymentApiObject
     */
    public function getScheduledPayment()
    {
        return $this->scheduledPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param SchedulePaymentApiObject $scheduledPayment
     */
    public function setScheduledPayment($scheduledPayment)
    {
        $this->scheduledPayment = $scheduledPayment;
    }

    /**
     * @return ScheduleInstanceApiObject
     */
    public function getScheduledInstance()
    {
        return $this->scheduledInstance;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ScheduleInstanceApiObject $scheduledInstance
     */
    public function setScheduledInstance($scheduledInstance)
    {
        $this->scheduledInstance = $scheduledInstance;
    }

    /**
     * @return UserApiObject
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserApiObject $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->bunqMeFundraiserResult)) {
            return $this->bunqMeFundraiserResult;
        }

        if (!is_null($this->bunqMeTab)) {
            return $this->bunqMeTab;
        }

        if (!is_null($this->bunqMeTabResultInquiry)) {
            return $this->bunqMeTabResultInquiry;
        }

        if (!is_null($this->bunqMeTabResultResponse)) {
            return $this->bunqMeTabResultResponse;
        }

        if (!is_null($this->chatMessage)) {
            return $this->chatMessage;
        }

        if (!is_null($this->draftPayment)) {
            return $this->draftPayment;
        }

        if (!is_null($this->idealMerchantTransaction)) {
            return $this->idealMerchantTransaction;
        }

        if (!is_null($this->invoice)) {
            return $this->invoice;
        }

        if (!is_null($this->masterCardAction)) {
            return $this->masterCardAction;
        }

        if (!is_null($this->monetaryAccount)) {
            return $this->monetaryAccount;
        }

        if (!is_null($this->payment)) {
            return $this->payment;
        }

        if (!is_null($this->paymentBatch)) {
            return $this->paymentBatch;
        }

        if (!is_null($this->requestInquiry)) {
            return $this->requestInquiry;
        }

        if (!is_null($this->requestInquiryBatch)) {
            return $this->requestInquiryBatch;
        }

        if (!is_null($this->requestResponse)) {
            return $this->requestResponse;
        }

        if (!is_null($this->shareInviteBankInquiry)) {
            return $this->shareInviteBankInquiry;
        }

        if (!is_null($this->shareInviteBankResponse)) {
            return $this->shareInviteBankResponse;
        }

        if (!is_null($this->scheduledPayment)) {
            return $this->scheduledPayment;
        }

        if (!is_null($this->scheduledInstance)) {
            return $this->scheduledInstance;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->bunqMeFundraiserResult)) {
            return false;
        }

        if (!is_null($this->bunqMeTab)) {
            return false;
        }

        if (!is_null($this->bunqMeTabResultInquiry)) {
            return false;
        }

        if (!is_null($this->bunqMeTabResultResponse)) {
            return false;
        }

        if (!is_null($this->chatMessage)) {
            return false;
        }

        if (!is_null($this->draftPayment)) {
            return false;
        }

        if (!is_null($this->idealMerchantTransaction)) {
            return false;
        }

        if (!is_null($this->invoice)) {
            return false;
        }

        if (!is_null($this->masterCardAction)) {
            return false;
        }

        if (!is_null($this->monetaryAccount)) {
            return false;
        }

        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->paymentBatch)) {
            return false;
        }

        if (!is_null($this->requestInquiry)) {
            return false;
        }

        if (!is_null($this->requestInquiryBatch)) {
            return false;
        }

        if (!is_null($this->requestResponse)) {
            return false;
        }

        if (!is_null($this->shareInviteBankInquiry)) {
            return false;
        }

        if (!is_null($this->shareInviteBankResponse)) {
            return false;
        }

        if (!is_null($this->scheduledPayment)) {
            return false;
        }

        if (!is_null($this->scheduledInstance)) {
            return false;
        }

        if (!is_null($this->user)) {
            return false;
        }

        return true;
    }
}
