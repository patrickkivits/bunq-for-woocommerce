<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AddressObject;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\InvoiceItemGroupObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;

/**
 * Used to list bunq invoices by user.
 *
 * @generated
 */
class InvoiceByUserApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_LISTING = 'user/%s/invoice';
    const ENDPOINT_URL_READ = 'user/%s/invoice/%s';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'Invoice';

    /**
     * The id of the invoice object.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the invoice object's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the invoice object's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The invoice date.
     *
     * @var string
     */
    protected $invoiceDate;

    /**
     * The invoice number.
     *
     * @var string
     */
    protected $invoiceNumber;

    /**
     * The invoice status.
     *
     * @var string
     */
    protected $status;

    /**
     * The invoice item groups.
     *
     * @var InvoiceItemGroupObject[]
     */
    protected $group;

    /**
     * The total discounted item price including VAT.
     *
     * @var AmountObject
     */
    protected $totalVatInclusive;

    /**
     * The total discounted item price excluding VAT.
     *
     * @var AmountObject
     */
    protected $totalVatExclusive;

    /**
     * The VAT on the total discounted item price.
     *
     * @var AmountObject
     */
    protected $totalVat;

    /**
     * The label that's displayed to the counterparty with the invoice. Includes user.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The customer's address.
     *
     * @var AddressObject
     */
    protected $address;

    /**
     * The label of the counterparty of the invoice. Includes user.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterpartyAlias;

    /**
     * The company's address.
     *
     * @var AddressObject
     */
    protected $counterpartyAddress;

    /**
     * The company's chamber of commerce number.
     *
     * @var string
     */
    protected $chamberOfCommerceNumber;

    /**
     * The company's chamber of commerce number.
     *
     * @var string
     */
    protected $vatNumber;

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseInvoiceByUserApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseInvoiceByUserApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId()]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseInvoiceByUserApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param int $invoiceByUserId
     * @param string[] $customHeaders
     *
     * @return BunqResponseInvoiceByUser
     */
    public static function get(int $invoiceByUserId, array $customHeaders = []): BunqResponseInvoiceByUser
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $invoiceByUserId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseInvoiceByUser::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the invoice object.
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
     * The timestamp of the invoice object's creation.
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
     * The timestamp of the invoice object's last update.
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
     * The invoice date.
     *
     * @return string
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $invoiceDate
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;
    }

    /**
     * The invoice number.
     *
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * The invoice status.
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
     * The invoice item groups.
     *
     * @return InvoiceItemGroupObject[]
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param InvoiceItemGroupObject[] $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * The total discounted item price including VAT.
     *
     * @return AmountObject
     */
    public function getTotalVatInclusive()
    {
        return $this->totalVatInclusive;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $totalVatInclusive
     */
    public function setTotalVatInclusive($totalVatInclusive)
    {
        $this->totalVatInclusive = $totalVatInclusive;
    }

    /**
     * The total discounted item price excluding VAT.
     *
     * @return AmountObject
     */
    public function getTotalVatExclusive()
    {
        return $this->totalVatExclusive;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $totalVatExclusive
     */
    public function setTotalVatExclusive($totalVatExclusive)
    {
        $this->totalVatExclusive = $totalVatExclusive;
    }

    /**
     * The VAT on the total discounted item price.
     *
     * @return AmountObject
     */
    public function getTotalVat()
    {
        return $this->totalVat;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $totalVat
     */
    public function setTotalVat($totalVat)
    {
        $this->totalVat = $totalVat;
    }

    /**
     * The label that's displayed to the counterparty with the invoice. Includes user.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * The customer's address.
     *
     * @return AddressObject
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * The label of the counterparty of the invoice. Includes user.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getCounterpartyAlias()
    {
        return $this->counterpartyAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $counterpartyAlias
     */
    public function setCounterpartyAlias($counterpartyAlias)
    {
        $this->counterpartyAlias = $counterpartyAlias;
    }

    /**
     * The company's address.
     *
     * @return AddressObject
     */
    public function getCounterpartyAddress()
    {
        return $this->counterpartyAddress;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $counterpartyAddress
     */
    public function setCounterpartyAddress($counterpartyAddress)
    {
        $this->counterpartyAddress = $counterpartyAddress;
    }

    /**
     * The company's chamber of commerce number.
     *
     * @return string
     */
    public function getChamberOfCommerceNumber()
    {
        return $this->chamberOfCommerceNumber;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $chamberOfCommerceNumber
     */
    public function setChamberOfCommerceNumber($chamberOfCommerceNumber)
    {
        $this->chamberOfCommerceNumber = $chamberOfCommerceNumber;
    }

    /**
     * The company's chamber of commerce number.
     *
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
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

        if (!is_null($this->invoiceDate)) {
            return false;
        }

        if (!is_null($this->invoiceNumber)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->group)) {
            return false;
        }

        if (!is_null($this->totalVatInclusive)) {
            return false;
        }

        if (!is_null($this->totalVatExclusive)) {
            return false;
        }

        if (!is_null($this->totalVat)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->address)) {
            return false;
        }

        if (!is_null($this->counterpartyAlias)) {
            return false;
        }

        if (!is_null($this->counterpartyAddress)) {
            return false;
        }

        if (!is_null($this->chamberOfCommerceNumber)) {
            return false;
        }

        if (!is_null($this->vatNumber)) {
            return false;
        }

        return true;
    }
}
