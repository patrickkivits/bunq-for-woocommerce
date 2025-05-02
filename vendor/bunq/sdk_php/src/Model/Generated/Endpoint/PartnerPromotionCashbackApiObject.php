<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\AvatarObject;

/**
 * Read the publicly available information of a partner’s promotion.
 *
 * @generated
 */
class PartnerPromotionCashbackApiObject extends BunqModel
{
    /**
     * The public UUID of the cashback promotion.
     *
     * @var string
     */
    protected $publicUuid;

    /**
     * The status of the cashback promotion.
     *
     * @var string
     */
    protected $status;

    /**
     * The promotion code used in signup to indicate the partner that referred the user.
     *
     * @var string
     */
    protected $promotionCode;

    /**
     * The amount of cashback per transaction, will not be higher than the amount of the transaction.
     *
     * @var AmountObject
     */
    protected $amountCashbackPerTransactionMaximum;

    /**
     * The maximum number of transactions that can be made.
     *
     * @var int
     */
    protected $numberOfTransactionMaximum;

    /**
     * The minimum amount of a transaction.
     *
     * @var AmountObject
     */
    protected $amountTransactionMinimum;

    /**
     * The URL to the Together page with FAQs about this promotion.
     *
     * @var string
     */
    protected $urlTogether;

    /**
     * The deeplink to the cashback promotion.
     *
     * @var string
     */
    protected $deeplink;

    /**
     * The name of the partner.
     *
     * @var string
     */
    protected $partnerName;

    /**
     * The avatar of the partner.
     *
     * @var AvatarObject
     */
    protected $partnerAvatar;

    /**
     * The short title of the promotion.
     *
     * @var string[]
     */
    protected $promotionTitleShort;

    /**
     * The long title of the promotion.
     *
     * @var string[]
     */
    protected $promotionTitleLong;

    /**
     * The description of the promotion.
     *
     * @var string[]
     */
    protected $promotionDescription;

    /**
     * The public UUID of the cashback promotion.
     *
     * @return string
     */
    public function getPublicUuid()
    {
        return $this->publicUuid;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $publicUuid
     */
    public function setPublicUuid($publicUuid)
    {
        $this->publicUuid = $publicUuid;
    }

    /**
     * The status of the cashback promotion.
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
     * The promotion code used in signup to indicate the partner that referred the user.
     *
     * @return string
     */
    public function getPromotionCode()
    {
        return $this->promotionCode;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $promotionCode
     */
    public function setPromotionCode($promotionCode)
    {
        $this->promotionCode = $promotionCode;
    }

    /**
     * The amount of cashback per transaction, will not be higher than the amount of the transaction.
     *
     * @return AmountObject
     */
    public function getAmountCashbackPerTransactionMaximum()
    {
        return $this->amountCashbackPerTransactionMaximum;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountCashbackPerTransactionMaximum
     */
    public function setAmountCashbackPerTransactionMaximum($amountCashbackPerTransactionMaximum)
    {
        $this->amountCashbackPerTransactionMaximum = $amountCashbackPerTransactionMaximum;
    }

    /**
     * The maximum number of transactions that can be made.
     *
     * @return int
     */
    public function getNumberOfTransactionMaximum()
    {
        return $this->numberOfTransactionMaximum;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $numberOfTransactionMaximum
     */
    public function setNumberOfTransactionMaximum($numberOfTransactionMaximum)
    {
        $this->numberOfTransactionMaximum = $numberOfTransactionMaximum;
    }

    /**
     * The minimum amount of a transaction.
     *
     * @return AmountObject
     */
    public function getAmountTransactionMinimum()
    {
        return $this->amountTransactionMinimum;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountTransactionMinimum
     */
    public function setAmountTransactionMinimum($amountTransactionMinimum)
    {
        $this->amountTransactionMinimum = $amountTransactionMinimum;
    }

    /**
     * The URL to the Together page with FAQs about this promotion.
     *
     * @return string
     */
    public function getUrlTogether()
    {
        return $this->urlTogether;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $urlTogether
     */
    public function setUrlTogether($urlTogether)
    {
        $this->urlTogether = $urlTogether;
    }

    /**
     * The deeplink to the cashback promotion.
     *
     * @return string
     */
    public function getDeeplink()
    {
        return $this->deeplink;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $deeplink
     */
    public function setDeeplink($deeplink)
    {
        $this->deeplink = $deeplink;
    }

    /**
     * The name of the partner.
     *
     * @return string
     */
    public function getPartnerName()
    {
        return $this->partnerName;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $partnerName
     */
    public function setPartnerName($partnerName)
    {
        $this->partnerName = $partnerName;
    }

    /**
     * The avatar of the partner.
     *
     * @return AvatarObject
     */
    public function getPartnerAvatar()
    {
        return $this->partnerAvatar;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AvatarObject $partnerAvatar
     */
    public function setPartnerAvatar($partnerAvatar)
    {
        $this->partnerAvatar = $partnerAvatar;
    }

    /**
     * The short title of the promotion.
     *
     * @return string[]
     */
    public function getPromotionTitleShort()
    {
        return $this->promotionTitleShort;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string[] $promotionTitleShort
     */
    public function setPromotionTitleShort($promotionTitleShort)
    {
        $this->promotionTitleShort = $promotionTitleShort;
    }

    /**
     * The long title of the promotion.
     *
     * @return string[]
     */
    public function getPromotionTitleLong()
    {
        return $this->promotionTitleLong;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string[] $promotionTitleLong
     */
    public function setPromotionTitleLong($promotionTitleLong)
    {
        $this->promotionTitleLong = $promotionTitleLong;
    }

    /**
     * The description of the promotion.
     *
     * @return string[]
     */
    public function getPromotionDescription()
    {
        return $this->promotionDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string[] $promotionDescription
     */
    public function setPromotionDescription($promotionDescription)
    {
        $this->promotionDescription = $promotionDescription;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->publicUuid)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->promotionCode)) {
            return false;
        }

        if (!is_null($this->amountCashbackPerTransactionMaximum)) {
            return false;
        }

        if (!is_null($this->numberOfTransactionMaximum)) {
            return false;
        }

        if (!is_null($this->amountTransactionMinimum)) {
            return false;
        }

        if (!is_null($this->urlTogether)) {
            return false;
        }

        if (!is_null($this->deeplink)) {
            return false;
        }

        if (!is_null($this->partnerName)) {
            return false;
        }

        if (!is_null($this->partnerAvatar)) {
            return false;
        }

        if (!is_null($this->promotionTitleShort)) {
            return false;
        }

        if (!is_null($this->promotionTitleLong)) {
            return false;
        }

        if (!is_null($this->promotionDescription)) {
            return false;
        }

        return true;
    }
}
