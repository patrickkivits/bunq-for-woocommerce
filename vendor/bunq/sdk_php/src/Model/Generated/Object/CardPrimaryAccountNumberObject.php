<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class CardPrimaryAccountNumberObject extends BunqModel
{
    /**
     * The ID for this Virtual PAN.
     *
     * @var int
     */
    protected $id;

    /**
     * The UUID for this Virtual PAN.
     *
     * @var string
     */
    protected $uuid;

    /**
     * The description for this PAN.
     *
     * @var string
     */
    protected $description;

    /**
     * The status for this PAN, only for Online Cards.
     *
     * @var string
     */
    protected $status;

    /**
     * The ID of the monetary account to assign to this PAN, only for Online Cards.
     *
     * @var int
     */
    protected $monetaryAccountId;

    /**
     * The last four digits of the PAN.
     *
     * @var string
     */
    protected $fourDigit;

    /**
     * The type of the PAN.
     *
     * @var string
     */
    protected $type;

    /**
     * The ID for this PAN.
     *
     * @var int
     */
    protected $idFieldForRequest;

    /**
     * The description for this PAN.
     *
     * @var string|null
     */
    protected $descriptionFieldForRequest;

    /**
     * The status for this PAN, only for Online Cards.
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * The ID of the monetary account to assign to this PAN, only for Online Cards.
     *
     * @var int|null
     */
    protected $monetaryAccountIdFieldForRequest;

    /**
     * @param int $id The ID for this PAN.
     * @param string|null $description The description for this PAN.
     * @param string|null $status The status for this PAN, only for Online Cards.
     * @param int|null $monetaryAccountId The ID of the monetary account to assign to this PAN, only for Online Cards.
     */
    public function __construct(int  $id, string  $description = null, string  $status = null, int  $monetaryAccountId = null)
    {
        $this->idFieldForRequest = $id;
        $this->descriptionFieldForRequest = $description;
        $this->statusFieldForRequest = $status;
        $this->monetaryAccountIdFieldForRequest = $monetaryAccountId;
    }

    /**
     * The ID for this Virtual PAN.
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
     * The UUID for this Virtual PAN.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * The description for this PAN.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * The status for this PAN, only for Online Cards.
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
     * The ID of the monetary account to assign to this PAN, only for Online Cards.
     *
     * @return int
     */
    public function getMonetaryAccountId()
    {
        return $this->monetaryAccountId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $monetaryAccountId
     */
    public function setMonetaryAccountId($monetaryAccountId)
    {
        $this->monetaryAccountId = $monetaryAccountId;
    }

    /**
     * The last four digits of the PAN.
     *
     * @return string
     */
    public function getFourDigit()
    {
        return $this->fourDigit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $fourDigit
     */
    public function setFourDigit($fourDigit)
    {
        $this->fourDigit = $fourDigit;
    }

    /**
     * The type of the PAN.
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->uuid)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->monetaryAccountId)) {
            return false;
        }

        if (!is_null($this->fourDigit)) {
            return false;
        }

        if (!is_null($this->type)) {
            return false;
        }

        return true;
    }
}
