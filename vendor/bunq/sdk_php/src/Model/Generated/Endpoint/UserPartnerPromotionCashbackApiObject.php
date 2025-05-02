<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;

/**
 * Endpoint to specify that a user signed up using a promotion code.
 *
 * @generated
 */
class UserPartnerPromotionCashbackApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_PROMOTION_CODE = 'promotion_code';

    /**
     * The status of the user partner promotion.
     *
     * @var string
     */
    protected $status;

    /**
     * The number of transactions that are still eligible for this promotion.
     *
     * @var int
     */
    protected $numberOfTransactionRemaining;

    /**
     * The promotion that the user signed up with.
     *
     * @var PartnerPromotionCashbackApiObject
     */
    protected $partnerPromotion;

    /**
     * The promotion code used in signup to indicate the partner that referred the user.
     *
     * @var string
     */
    protected $promotionCodeFieldForRequest;

    /**
     * @param string $promotionCode The promotion code used in signup to indicate the partner that referred the user.
     */
    public function __construct(string  $promotionCode)
    {
        $this->promotionCodeFieldForRequest = $promotionCode;
    }

    /**
     * The status of the user partner promotion.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * The number of transactions that are still eligible for this promotion.
     *
     * @return int
     */
    public function getNumberOfTransactionRemaining()
    {
        return $this->numberOfTransactionRemaining;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $numberOfTransactionRemaining
     */
    public function setNumberOfTransactionRemaining($numberOfTransactionRemaining)
    {
        $this->numberOfTransactionRemaining = $numberOfTransactionRemaining;
    }

    /**
     * The promotion that the user signed up with.
     *
     * @return PartnerPromotionCashbackApiObject
     */
    public function getPartnerPromotion()
    {
        return $this->partnerPromotion;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PartnerPromotionCashbackApiObject $partnerPromotion
     */
    public function setPartnerPromotion($partnerPromotion)
    {
        $this->partnerPromotion = $partnerPromotion;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->numberOfTransactionRemaining)) {
            return false;
        }

        if (!is_null($this->partnerPromotion)) {
            return false;
        }

        return true;
    }
}
