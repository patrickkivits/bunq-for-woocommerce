<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;

/**
 * Endpoint to retrieve point mutation.
 *
 * @generated
 */
class PointMutationApiObject extends BunqModel
{
    /**
     * The number of points earned.
     *
     * @var int
     */
    protected $numberOfPoint;

    /**
     * The number of points earned.
     *
     * @return int
     */
    public function getNumberOfPoint()
    {
        return $this->numberOfPoint;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $numberOfPoint
     */
    public function setNumberOfPoint($numberOfPoint)
    {
        $this->numberOfPoint = $numberOfPoint;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->numberOfPoint)) {
            return false;
        }

        return true;
    }
}
