<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Manage a users automatic payment auto allocated settings.
 *
 * @generated
 */
class PaymentAutoAllocateApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/payment-auto-allocate';
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/payment-auto-allocate/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/payment-auto-allocate';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/payment-auto-allocate/%s';
    const ENDPOINT_URL_DELETE = 'user/%s/monetary-account/%s/payment-auto-allocate/%s';

    /**
     * Field constants.
     */
    const FIELD_PAYMENT_ID = 'payment_id';
    const FIELD_TYPE = 'type';
    const FIELD_DEFINITION = 'definition';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'PaymentAutoAllocate';

    /**
     * The id of the PaymentAutoAllocate.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp when the PaymentAutoAllocate was created.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp when the PaymentAutoAllocate was last updated.
     *
     * @var string
     */
    protected $updated;

    /**
     * The type.
     *
     * @var string
     */
    protected $type;

    /**
     * The status.
     *
     * @var string
     */
    protected $status;

    /**
     * The amount on which this payment auto allocate will be triggered.
     *
     * @var AmountObject
     */
    protected $triggerAmount;

    /**
     * DEPRECATED: superseded by `payment_original` and `payment_latest`
     *
     * @var PaymentApiObject
     */
    protected $payment;

    /**
     * The payment that was used to define the triggers for this payment auto allocate.
     *
     * @var PaymentApiObject
     */
    protected $paymentOriginal;

    /**
     * The latest payment allocated using this payment auto allocate.
     *
     * @var PaymentApiObject
     */
    protected $paymentLatest;

    /**
     * The payment that should be used to define the triggers for the payment auto allocate.
     *
     * @var int
     */
    protected $paymentIdFieldForRequest;

    /**
     * Whether a payment should be sorted ONCE or RECURRING.
     *
     * @var string
     */
    protected $typeFieldForRequest;

    /**
     * The definition of how the money should be allocated.
     *
     * @var PaymentAutoAllocateDefinitionApiObject[]
     */
    protected $definitionFieldForRequest;

    /**
     * @param int $paymentId The payment that should be used to define the triggers for the payment auto allocate.
     * @param string $type Whether a payment should be sorted ONCE or RECURRING.
     * @param PaymentAutoAllocateDefinitionApiObject[] $definition The definition of how the money should be allocated.
     */
    public function __construct(int  $paymentId, string  $type, array  $definition)
    {
        $this->paymentIdFieldForRequest = $paymentId;
        $this->typeFieldForRequest = $type;
        $this->definitionFieldForRequest = $definition;
    }

    /**
     * @param int $paymentId The payment that should be used to define the triggers for the payment auto allocate.
     * @param string $type Whether a payment should be sorted ONCE or RECURRING.
     * @param PaymentAutoAllocateDefinitionApiObject[] $definition The definition of how the money should be allocated.
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(int  $paymentId, string  $type, array  $definition, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [self::FIELD_PAYMENT_ID => $paymentId,
self::FIELD_TYPE => $type,
self::FIELD_DEFINITION => $definition],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $paymentAutoAllocateId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponsePaymentAutoAllocate
     */
    public static function get(int $paymentAutoAllocateId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponsePaymentAutoAllocate
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $paymentAutoAllocateId]
            ),
            [],
            $customHeaders
        );

        return BunqResponsePaymentAutoAllocate::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponsePaymentAutoAllocateApiObjectList
     */
    public static function listing(int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponsePaymentAutoAllocateApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            $params,
            $customHeaders
        );

        return BunqResponsePaymentAutoAllocateApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param int $paymentAutoAllocateId
     * @param int|null $monetaryAccountId
     * @param PaymentAutoAllocateDefinitionApiObject[]|null $definition The definition of how the money should be allocated.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $paymentAutoAllocateId, int $monetaryAccountId = null, array  $definition = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $paymentAutoAllocateId]
            ),
            [self::FIELD_DEFINITION => $definition],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param string[] $customHeaders
     * @param int $paymentAutoAllocateId
     *
     * @return BunqResponseNull
     */
    public static function delete(int $paymentAutoAllocateId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNull
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->delete(
            vsprintf(
                self::ENDPOINT_URL_DELETE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $paymentAutoAllocateId]
            ),
            $customHeaders
        );

        return BunqResponseNull::castFromBunqResponse(
            new BunqResponse(null, $responseRaw->getHeaders())
        );
    }

    /**
     * The id of the PaymentAutoAllocate.
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
     * The timestamp when the PaymentAutoAllocate was created.
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
     * The timestamp when the PaymentAutoAllocate was last updated.
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
     * The type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * The status.
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
     * The amount on which this payment auto allocate will be triggered.
     *
     * @return AmountObject
     */
    public function getTriggerAmount()
    {
        return $this->triggerAmount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $triggerAmount
     */
    public function setTriggerAmount($triggerAmount)
    {
        $this->triggerAmount = $triggerAmount;
    }

    /**
     * DEPRECATED: superseded by `payment_original` and `payment_latest`
     *
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
     * The payment that was used to define the triggers for this payment auto allocate.
     *
     * @return PaymentApiObject
     */
    public function getPaymentOriginal()
    {
        return $this->paymentOriginal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentApiObject $paymentOriginal
     */
    public function setPaymentOriginal($paymentOriginal)
    {
        $this->paymentOriginal = $paymentOriginal;
    }

    /**
     * The latest payment allocated using this payment auto allocate.
     *
     * @return PaymentApiObject
     */
    public function getPaymentLatest()
    {
        return $this->paymentLatest;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentApiObject $paymentLatest
     */
    public function setPaymentLatest($paymentLatest)
    {
        $this->paymentLatest = $paymentLatest;
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

        if (!is_null($this->type)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->triggerAmount)) {
            return false;
        }

        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->paymentOriginal)) {
            return false;
        }

        if (!is_null($this->paymentLatest)) {
            return false;
        }

        return true;
    }
}
