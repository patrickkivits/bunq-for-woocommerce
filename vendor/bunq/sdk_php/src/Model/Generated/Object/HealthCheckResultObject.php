<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class HealthCheckResultObject extends BunqModel
{
    /**
     * The result status of the health check.
     *
     * @var string
     */
    protected $status;

    /**
     * The entries on which the current status is based.
     *
     * @var HealthCheckResultEntryObject[]
     */
    protected $allEntry;

    /**
     * The result status of the health check.
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
     * The entries on which the current status is based.
     *
     * @return HealthCheckResultEntryObject[]
     */
    public function getAllEntry()
    {
        return $this->allEntry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param HealthCheckResultEntryObject[] $allEntry
     */
    public function setAllEntry($allEntry)
    {
        $this->allEntry = $allEntry;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->allEntry)) {
            return false;
        }

        return true;
    }
}
