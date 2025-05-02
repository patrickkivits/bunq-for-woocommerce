<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class CoOwnerObject extends BunqModel
{
    /**
     * The Alias of the co-owner.
     *
     * @var LabelUserObject
     */
    protected $alias;

    /**
     * Can be: ACCEPTED, REJECTED, PENDING or REVOKED
     *
     * @var string
     */
    protected $status;

    /**
     * The users the account will be joint with.
     *
     * @var PointerObject
     */
    protected $aliasFieldForRequest;

    /**
     * @param PointerObject $alias The users the account will be joint with.
     */
    public function __construct(PointerObject  $alias)
    {
        $this->aliasFieldForRequest = $alias;
    }

    /**
     * The Alias of the co-owner.
     *
     * @return LabelUserObject
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Can be: ACCEPTED, REJECTED, PENDING or REVOKED
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        return true;
    }
}
