<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class TransferwiseRequirementFieldGroupValuesAllowed extends BunqModel
{
    /**
     * The key.
     *
     * @var string
     */
    protected $key;

    /**
     * The label.
     *
     * @var string
     */
    protected $name;

    /**
     * The key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * The label.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->key)) {
            return false;
        }

        if (!is_null($this->name)) {
            return false;
        }

        return true;
    }
}
