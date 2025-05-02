<?php
namespace bunq\Model\Generated\Object;

use bunq\Exception\BunqException;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\BankSwitchServiceNetherlandsIncomingPaymentApiObject;
use bunq\Model\Generated\Endpoint\BunqMeFundraiserResultApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabResultResponseApiObject;
use bunq\Model\Generated\Endpoint\CardApiObject;
use bunq\Model\Generated\Endpoint\CardDebitApiObject;
use bunq\Model\Generated\Endpoint\DraftPaymentApiObject;
use bunq\Model\Generated\Endpoint\FeatureAnnouncementApiObject;
use bunq\Model\Generated\Endpoint\IdealMerchantTransactionApiObject;
use bunq\Model\Generated\Endpoint\InvoiceApiObject;
use bunq\Model\Generated\Endpoint\MasterCardActionApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Endpoint\PaymentBatchApiObject;
use bunq\Model\Generated\Endpoint\RequestInquiryApiObject;
use bunq\Model\Generated\Endpoint\RequestInquiryBatchApiObject;
use bunq\Model\Generated\Endpoint\RequestResponseApiObject;
use bunq\Model\Generated\Endpoint\ScheduleInstanceApiObject;
use bunq\Model\Generated\Endpoint\SchedulePaymentApiObject;
use bunq\Model\Generated\Endpoint\SchedulePaymentBatchApiObject;
use bunq\Model\Generated\Endpoint\ShareInviteMonetaryAccountInquiryApiObject;
use bunq\Model\Generated\Endpoint\ShareInviteMonetaryAccountResponseApiObject;
use bunq\Model\Generated\Endpoint\SofortMerchantTransactionApiObject;
use bunq\Model\Generated\Endpoint\TransferwiseTransferApiObject;

/**
 * @generated
 */
class EventObjectObject extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * @var BunqMeTabApiObject
     */
    protected $bunqMeTab;

    /**
     * @var BunqMeTabResultResponseApiObject
     */
    protected $bunqMeTabResultResponse;

    /**
     * @var BunqMeFundraiserResultApiObject
     */
    protected $bunqMeFundraiserResult;

    /**
     * @var CardApiObject
     */
    protected $card;

    /**
     * @var CardDebitApiObject
     */
    protected $cardDebit;

    /**
     * @var DraftPaymentApiObject
     */
    protected $draftPayment;

    /**
     * @var FeatureAnnouncementApiObject
     */
    protected $featureAnnouncement;

    /**
     * @var IdealMerchantTransactionApiObject
     */
    protected $idealMerchantTransaction;

    /**
     * @var InvoiceApiObject
     */
    protected $invoice;

    /**
     * @var SchedulePaymentApiObject
     */
    protected $scheduledPayment;

    /**
     * @var SchedulePaymentBatchApiObject
     */
    protected $scheduledPaymentBatch;

    /**
     * @var ScheduleInstanceApiObject
     */
    protected $scheduledInstance;

    /**
     * @var MasterCardActionApiObject
     */
    protected $masterCardAction;

    /**
     * @var BankSwitchServiceNetherlandsIncomingPaymentApiObject
     */
    protected $bankSwitchServiceNetherlandsIncomingPayment;

    /**
     * @var PaymentApiObject
     */
    protected $payment;

    /**
     * @var PaymentBatchApiObject
     */
    protected $paymentBatch;

    /**
     * @var RequestInquiryBatchApiObject
     */
    protected $requestInquiryBatch;

    /**
     * @var RequestInquiryApiObject
     */
    protected $requestInquiry;

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
     * @var SofortMerchantTransactionApiObject
     */
    protected $sofortMerchantTransaction;

    /**
     * @var TransferwiseTransferApiObject
     */
    protected $transferwisePayment;

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
     * @return CardApiObject
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CardApiObject $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * @return CardDebitApiObject
     */
    public function getCardDebit()
    {
        return $this->cardDebit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CardDebitApiObject $cardDebit
     */
    public function setCardDebit($cardDebit)
    {
        $this->cardDebit = $cardDebit;
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
     * @return FeatureAnnouncementApiObject
     */
    public function getFeatureAnnouncement()
    {
        return $this->featureAnnouncement;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param FeatureAnnouncementApiObject $featureAnnouncement
     */
    public function setFeatureAnnouncement($featureAnnouncement)
    {
        $this->featureAnnouncement = $featureAnnouncement;
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
     * @return SchedulePaymentBatchApiObject
     */
    public function getScheduledPaymentBatch()
    {
        return $this->scheduledPaymentBatch;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param SchedulePaymentBatchApiObject $scheduledPaymentBatch
     */
    public function setScheduledPaymentBatch($scheduledPaymentBatch)
    {
        $this->scheduledPaymentBatch = $scheduledPaymentBatch;
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
     * @return BankSwitchServiceNetherlandsIncomingPaymentApiObject
     */
    public function getBankSwitchServiceNetherlandsIncomingPayment()
    {
        return $this->bankSwitchServiceNetherlandsIncomingPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BankSwitchServiceNetherlandsIncomingPaymentApiObject $bankSwitchServiceNetherlandsIncomingPayment
     */
    public function setBankSwitchServiceNetherlandsIncomingPayment($bankSwitchServiceNetherlandsIncomingPayment)
    {
        $this->bankSwitchServiceNetherlandsIncomingPayment = $bankSwitchServiceNetherlandsIncomingPayment;
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
     * @return SofortMerchantTransactionApiObject
     */
    public function getSofortMerchantTransaction()
    {
        return $this->sofortMerchantTransaction;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param SofortMerchantTransactionApiObject $sofortMerchantTransaction
     */
    public function setSofortMerchantTransaction($sofortMerchantTransaction)
    {
        $this->sofortMerchantTransaction = $sofortMerchantTransaction;
    }

    /**
     * @return TransferwiseTransferApiObject
     */
    public function getTransferwisePayment()
    {
        return $this->transferwisePayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param TransferwiseTransferApiObject $transferwisePayment
     */
    public function setTransferwisePayment($transferwisePayment)
    {
        $this->transferwisePayment = $transferwisePayment;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->bunqMeTab)) {
            return $this->bunqMeTab;
        }

        if (!is_null($this->bunqMeTabResultResponse)) {
            return $this->bunqMeTabResultResponse;
        }

        if (!is_null($this->bunqMeFundraiserResult)) {
            return $this->bunqMeFundraiserResult;
        }

        if (!is_null($this->card)) {
            return $this->card;
        }

        if (!is_null($this->cardDebit)) {
            return $this->cardDebit;
        }

        if (!is_null($this->draftPayment)) {
            return $this->draftPayment;
        }

        if (!is_null($this->featureAnnouncement)) {
            return $this->featureAnnouncement;
        }

        if (!is_null($this->idealMerchantTransaction)) {
            return $this->idealMerchantTransaction;
        }

        if (!is_null($this->invoice)) {
            return $this->invoice;
        }

        if (!is_null($this->scheduledPayment)) {
            return $this->scheduledPayment;
        }

        if (!is_null($this->scheduledPaymentBatch)) {
            return $this->scheduledPaymentBatch;
        }

        if (!is_null($this->scheduledInstance)) {
            return $this->scheduledInstance;
        }

        if (!is_null($this->masterCardAction)) {
            return $this->masterCardAction;
        }

        if (!is_null($this->bankSwitchServiceNetherlandsIncomingPayment)) {
            return $this->bankSwitchServiceNetherlandsIncomingPayment;
        }

        if (!is_null($this->payment)) {
            return $this->payment;
        }

        if (!is_null($this->paymentBatch)) {
            return $this->paymentBatch;
        }

        if (!is_null($this->requestInquiryBatch)) {
            return $this->requestInquiryBatch;
        }

        if (!is_null($this->requestInquiry)) {
            return $this->requestInquiry;
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

        if (!is_null($this->sofortMerchantTransaction)) {
            return $this->sofortMerchantTransaction;
        }

        if (!is_null($this->transferwisePayment)) {
            return $this->transferwisePayment;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->bunqMeTab)) {
            return false;
        }

        if (!is_null($this->bunqMeTabResultResponse)) {
            return false;
        }

        if (!is_null($this->bunqMeFundraiserResult)) {
            return false;
        }

        if (!is_null($this->card)) {
            return false;
        }

        if (!is_null($this->cardDebit)) {
            return false;
        }

        if (!is_null($this->draftPayment)) {
            return false;
        }

        if (!is_null($this->featureAnnouncement)) {
            return false;
        }

        if (!is_null($this->idealMerchantTransaction)) {
            return false;
        }

        if (!is_null($this->invoice)) {
            return false;
        }

        if (!is_null($this->scheduledPayment)) {
            return false;
        }

        if (!is_null($this->scheduledPaymentBatch)) {
            return false;
        }

        if (!is_null($this->scheduledInstance)) {
            return false;
        }

        if (!is_null($this->masterCardAction)) {
            return false;
        }

        if (!is_null($this->bankSwitchServiceNetherlandsIncomingPayment)) {
            return false;
        }

        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->paymentBatch)) {
            return false;
        }

        if (!is_null($this->requestInquiryBatch)) {
            return false;
        }

        if (!is_null($this->requestInquiry)) {
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

        if (!is_null($this->sofortMerchantTransaction)) {
            return false;
        }

        if (!is_null($this->transferwisePayment)) {
            return false;
        }

        return true;
    }
}
