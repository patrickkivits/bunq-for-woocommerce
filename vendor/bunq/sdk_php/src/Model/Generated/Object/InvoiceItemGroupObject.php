<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class InvoiceItemGroupObject extends BunqModel
{
    /**
     * The type of the invoice item group.
     *
     * @var string
     */
    protected $type;

    /**
     * The description of the type of the invoice item group.
     *
     * @var string
     */
    protected $typeDescription;

    /**
     * The translated description of the type of the invoice item group.
     *
     * @var string
     */
    protected $typeDescriptionTranslated;

    /**
     * The identifier of the invoice item group.
     *
     * @var string
     */
    protected $instanceDescription;

    /**
     * The unit item price excluding VAT.
     *
     * @var AmountObject
     */
    protected $productVatExclusive;

    /**
     * The unit item price including VAT.
     *
     * @var AmountObject
     */
    protected $productVatInclusive;

    /**
     * The invoice items in the group.
     *
     * @var InvoiceItemObject[]
     */
    protected $item;

    /**
     * The type of the invoice item group.
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
     * The description of the type of the invoice item group.
     *
     * @return string
     */
    public function getTypeDescription()
    {
        return $this->typeDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $typeDescription
     */
    public function setTypeDescription($typeDescription)
    {
        $this->typeDescription = $typeDescription;
    }

    /**
     * The translated description of the type of the invoice item group.
     *
     * @return string
     */
    public function getTypeDescriptionTranslated()
    {
        return $this->typeDescriptionTranslated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $typeDescriptionTranslated
     */
    public function setTypeDescriptionTranslated($typeDescriptionTranslated)
    {
        $this->typeDescriptionTranslated = $typeDescriptionTranslated;
    }

    /**
     * The identifier of the invoice item group.
     *
     * @return string
     */
    public function getInstanceDescription()
    {
        return $this->instanceDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $instanceDescription
     */
    public function setInstanceDescription($instanceDescription)
    {
        $this->instanceDescription = $instanceDescription;
    }

    /**
     * The unit item price excluding VAT.
     *
     * @return AmountObject
     */
    public function getProductVatExclusive()
    {
        return $this->productVatExclusive;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $productVatExclusive
     */
    public function setProductVatExclusive($productVatExclusive)
    {
        $this->productVatExclusive = $productVatExclusive;
    }

    /**
     * The unit item price including VAT.
     *
     * @return AmountObject
     */
    public function getProductVatInclusive()
    {
        return $this->productVatInclusive;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $productVatInclusive
     */
    public function setProductVatInclusive($productVatInclusive)
    {
        $this->productVatInclusive = $productVatInclusive;
    }

    /**
     * The invoice items in the group.
     *
     * @return InvoiceItemObject[]
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param InvoiceItemObject[] $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->type)) {
            return false;
        }

        if (!is_null($this->typeDescription)) {
            return false;
        }

        if (!is_null($this->typeDescriptionTranslated)) {
            return false;
        }

        if (!is_null($this->instanceDescription)) {
            return false;
        }

        if (!is_null($this->productVatExclusive)) {
            return false;
        }

        if (!is_null($this->productVatInclusive)) {
            return false;
        }

        if (!is_null($this->item)) {
            return false;
        }

        return true;
    }
}
