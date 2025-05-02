<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\PointerObject;

/**
 * Manage permissions for Adyen card transactions / Tap to Pay for a company employee.
 *
 * @generated
 */
class CompanyEmployeeSettingAdyenCardTransactionApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/company-employee-setting-adyen-card-transaction/%s';

    /**
     * Field constants.
     */
    const FIELD_POINTER_COUNTER_USER = 'pointer_counter_user';
    const FIELD_STATUS = 'status';
    const FIELD_MONETARY_ACCOUNT_PAYOUT_ID = 'monetary_account_payout_id';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'CompanyEmployeeSettingAdyenCardTransaction';

    /**
     * Whether the user is allowed to use Tap to Pay.
     *
     * @var string
     */
    protected $status;

    /**
     * The ID of the monetary account where Tap to Pay transactions should be paid out to.
     *
     * @var int
     */
    protected $monetaryAccountPayoutId;

    /**
     * The pointer to the employee for which you want to create a card.
     *
     * @var PointerObject
     */
    protected $pointerCounterUserFieldForRequest;

    /**
     * Whether the user is allowed to use Tap to Pay.
     *
     * @var string
     */
    protected $statusFieldForRequest;

    /**
     * The ID of the monetary account where Tap to Pay transactions should be paid out to.
     *
     * @var int|null
     */
    protected $monetaryAccountPayoutIdFieldForRequest;

    /**
     * @param PointerObject $pointerCounterUser The pointer to the employee for which you want to create a card.
     * @param string $status Whether the user is allowed to use Tap to Pay.
     * @param int|null $monetaryAccountPayoutId The ID of the monetary account where Tap to Pay transactions should be paid out
     * to.
     */
    public function __construct(PointerObject  $pointerCounterUser, string  $status, int  $monetaryAccountPayoutId = null)
    {
        $this->pointerCounterUserFieldForRequest = $pointerCounterUser;
        $this->statusFieldForRequest = $status;
        $this->monetaryAccountPayoutIdFieldForRequest = $monetaryAccountPayoutId;
    }

    /**
     * @param int $companyEmployeeSettingAdyenCardTransactionId
     * @param string[] $customHeaders
     *
     * @return BunqResponseCompanyEmployeeSettingAdyenCardTransaction
     */
    public static function get(int $companyEmployeeSettingAdyenCardTransactionId, array $customHeaders = []): BunqResponseCompanyEmployeeSettingAdyenCardTransaction
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $companyEmployeeSettingAdyenCardTransactionId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseCompanyEmployeeSettingAdyenCardTransaction::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * Whether the user is allowed to use Tap to Pay.
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
     * The ID of the monetary account where Tap to Pay transactions should be paid out to.
     *
     * @return int
     */
    public function getMonetaryAccountPayoutId()
    {
        return $this->monetaryAccountPayoutId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $monetaryAccountPayoutId
     */
    public function setMonetaryAccountPayoutId($monetaryAccountPayoutId)
    {
        $this->monetaryAccountPayoutId = $monetaryAccountPayoutId;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->monetaryAccountPayoutId)) {
            return false;
        }

        return true;
    }
}
