<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Used to get quotes from Transferwise. These can be used to initiate payments.
 *
 * @generated
 */
class TransferwiseQuoteApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/transferwise-quote';
    const ENDPOINT_URL_READ = 'user/%s/transferwise-quote/%s';

    /**
     * Field constants.
     */
    const FIELD_CURRENCY_SOURCE = 'currency_source';
    const FIELD_CURRENCY_TARGET = 'currency_target';
    const FIELD_AMOUNT_SOURCE = 'amount_source';
    const FIELD_AMOUNT_TARGET = 'amount_target';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'TransferwiseQuote';

    /**
     * The id of the quote.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the quote's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the quote's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The expiration timestamp of the quote.
     *
     * @var string
     */
    protected $timeExpiry;

    /**
     * The quote id Transferwise needs.
     *
     * @var string
     */
    protected $quoteId;

    /**
     * The source amount.
     *
     * @var AmountObject
     */
    protected $amountSource;

    /**
     * The target amount.
     *
     * @var AmountObject
     */
    protected $amountTarget;

    /**
     * The fee amount.
     *
     * @var AmountObject
     */
    protected $amountFee;

    /**
     * The rate.
     *
     * @var string
     */
    protected $rate;

    /**
     * The estimated delivery time.
     *
     * @var string
     */
    protected $timeDeliveryEstimate;

    /**
     * The source currency.
     *
     * @var string
     */
    protected $currencySourceFieldForRequest;

    /**
     * The target currency.
     *
     * @var string
     */
    protected $currencyTargetFieldForRequest;

    /**
     * The source amount. Required if target amount is left empty.
     *
     * @var AmountObject|null
     */
    protected $amountSourceFieldForRequest;

    /**
     * The target amount. Required if source amount is left empty.
     *
     * @var AmountObject|null
     */
    protected $amountTargetFieldForRequest;

    /**
     * @param string $currencySource The source currency.
     * @param string $currencyTarget The target currency.
     * @param AmountObject|null $amountSource The source amount. Required if target amount is left empty.
     * @param AmountObject|null $amountTarget The target amount. Required if source amount is left empty.
     */
    public function __construct(string  $currencySource, string  $currencyTarget, AmountObject  $amountSource = null, AmountObject  $amountTarget = null)
    {
        $this->currencySourceFieldForRequest = $currencySource;
        $this->currencyTargetFieldForRequest = $currencyTarget;
        $this->amountSourceFieldForRequest = $amountSource;
        $this->amountTargetFieldForRequest = $amountTarget;
    }

    /**
     * @param string $currencySource The source currency.
     * @param string $currencyTarget The target currency.
     * @param AmountObject|null $amountSource The source amount. Required if target amount is left empty.
     * @param AmountObject|null $amountTarget The target amount. Required if source amount is left empty.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(string  $currencySource, string  $currencyTarget, AmountObject  $amountSource = null, AmountObject  $amountTarget = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_CURRENCY_SOURCE => $currencySource,
self::FIELD_CURRENCY_TARGET => $currencyTarget,
self::FIELD_AMOUNT_SOURCE => $amountSource,
self::FIELD_AMOUNT_TARGET => $amountTarget],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $transferwiseQuoteId
     * @param string[] $customHeaders
     *
     * @return BunqResponseTransferwiseQuote
     */
    public static function get(int $transferwiseQuoteId, array $customHeaders = []): BunqResponseTransferwiseQuote
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $transferwiseQuoteId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseTransferwiseQuote::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the quote.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * The timestamp of the quote's creation.
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * The timestamp of the quote's last update.
     *
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * The expiration timestamp of the quote.
     *
     * @return string
     */
    public function getTimeExpiry()
    {
        return $this->timeExpiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeExpiry
     */
    public function setTimeExpiry($timeExpiry)
    {
        $this->timeExpiry = $timeExpiry;
    }

    /**
     * The quote id Transferwise needs.
     *
     * @return string
     */
    public function getQuoteId()
    {
        return $this->quoteId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $quoteId
     */
    public function setQuoteId($quoteId)
    {
        $this->quoteId = $quoteId;
    }

    /**
     * The source amount.
     *
     * @return AmountObject
     */
    public function getAmountSource()
    {
        return $this->amountSource;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountSource
     */
    public function setAmountSource($amountSource)
    {
        $this->amountSource = $amountSource;
    }

    /**
     * The target amount.
     *
     * @return AmountObject
     */
    public function getAmountTarget()
    {
        return $this->amountTarget;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountTarget
     */
    public function setAmountTarget($amountTarget)
    {
        $this->amountTarget = $amountTarget;
    }

    /**
     * The fee amount.
     *
     * @return AmountObject
     */
    public function getAmountFee()
    {
        return $this->amountFee;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountFee
     */
    public function setAmountFee($amountFee)
    {
        $this->amountFee = $amountFee;
    }

    /**
     * The rate.
     *
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * The estimated delivery time.
     *
     * @return string
     */
    public function getTimeDeliveryEstimate()
    {
        return $this->timeDeliveryEstimate;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeDeliveryEstimate
     */
    public function setTimeDeliveryEstimate($timeDeliveryEstimate)
    {
        $this->timeDeliveryEstimate = $timeDeliveryEstimate;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->created)) {
            return false;
        }

        if (!is_null($this->updated)) {
            return false;
        }

        if (!is_null($this->timeExpiry)) {
            return false;
        }

        if (!is_null($this->quoteId)) {
            return false;
        }

        if (!is_null($this->amountSource)) {
            return false;
        }

        if (!is_null($this->amountTarget)) {
            return false;
        }

        if (!is_null($this->amountFee)) {
            return false;
        }

        if (!is_null($this->rate)) {
            return false;
        }

        if (!is_null($this->timeDeliveryEstimate)) {
            return false;
        }

        return true;
    }
}
