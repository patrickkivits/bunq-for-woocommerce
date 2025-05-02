<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * Used to confirm availability of funds on an account.
 *
 * @generated
 */
class ConfirmationOfFundsApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/confirmation-of-funds';

    /**
     * Field constants.
     */
    const FIELD_POINTER_IBAN = 'pointer_iban';
    const FIELD_AMOUNT = 'amount';

    /**
     * Object type.
     */
    const OBJECT_TYPE_POST = 'ConfirmationOfFunds';

    /**
     * Whether the account has sufficient funds.
     *
     * @var bool
     */
    protected $hasSufficientFunds;

    /**
     * The pointer (IBAN) of the account we're querying.
     *
     * @var PointerObject
     */
    protected $pointerIbanFieldForRequest;

    /**
     * The amount we want to check for.
     *
     * @var AmountObject
     */
    protected $amountFieldForRequest;

    /**
     * @param PointerObject $pointerIban The pointer (IBAN) of the account we're querying.
     * @param AmountObject $amount The amount we want to check for.
     */
    public function __construct(PointerObject  $pointerIban, AmountObject  $amount)
    {
        $this->pointerIbanFieldForRequest = $pointerIban;
        $this->amountFieldForRequest = $amount;
    }

    /**
     * @param PointerObject $pointerIban The pointer (IBAN) of the account we're querying.
     * @param AmountObject $amount The amount we want to check for.
     * @param string[] $customHeaders
     *
     * @return BunqResponseConfirmationOfFunds
     */
    public static function create(PointerObject  $pointerIban, AmountObject  $amount, array $customHeaders = []): BunqResponseConfirmationOfFunds
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_POINTER_IBAN => $pointerIban,
self::FIELD_AMOUNT => $amount],
            $customHeaders
        );

        return BunqResponseConfirmationOfFunds::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_POST)
        );
    }

    /**
     * Whether the account has sufficient funds.
     *
     * @return bool
     */
    public function getHasSufficientFunds()
    {
        return $this->hasSufficientFunds;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param bool $hasSufficientFunds
     */
    public function setHasSufficientFunds($hasSufficientFunds)
    {
        $this->hasSufficientFunds = $hasSufficientFunds;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->hasSufficientFunds)) {
            return false;
        }

        return true;
    }
}
