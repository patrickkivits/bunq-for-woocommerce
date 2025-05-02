<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\MonetaryAccountProfileDrainObject;
use bunq\Model\Generated\Object\MonetaryAccountProfileFillObject;

/**
 * Used to update and read up monetary account profiles, to keep the balance between specific thresholds.
 *
 * @generated
 */
class MonetaryAccountProfileApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_PROFILE_FILL = 'profile_fill';
    const FIELD_PROFILE_DRAIN = 'profile_drain';

    /**
     * The profile settings for triggering the fill of a monetary account.
     *
     * @var MonetaryAccountProfileFillObject
     */
    protected $profileFill;

    /**
     * The profile settings for moving excesses to a savings account
     *
     * @var MonetaryAccountProfileDrainObject
     */
    protected $profileDrain;

    /**
     * The profile settings for triggering the fill of a monetary account.
     *
     * @var MonetaryAccountProfileFillObject|null
     */
    protected $profileFillFieldForRequest;

    /**
     * The profile settings for moving excesses to a savings account
     *
     * @var MonetaryAccountProfileDrainObject|null
     */
    protected $profileDrainFieldForRequest;

    /**
     * @param MonetaryAccountProfileFillObject|null $profileFill The profile settings for triggering the fill of a monetary
     * account.
     * @param MonetaryAccountProfileDrainObject|null $profileDrain The profile settings for moving excesses to a savings
     * account
     */
    public function __construct(MonetaryAccountProfileFillObject  $profileFill = null, MonetaryAccountProfileDrainObject  $profileDrain = null)
    {
        $this->profileFillFieldForRequest = $profileFill;
        $this->profileDrainFieldForRequest = $profileDrain;
    }

    /**
     * The profile settings for triggering the fill of a monetary account.
     *
     * @return MonetaryAccountProfileFillObject
     */
    public function getProfileFill()
    {
        return $this->profileFill;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountProfileFillObject $profileFill
     */
    public function setProfileFill($profileFill)
    {
        $this->profileFill = $profileFill;
    }

    /**
     * The profile settings for moving excesses to a savings account
     *
     * @return MonetaryAccountProfileDrainObject
     */
    public function getProfileDrain()
    {
        return $this->profileDrain;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountProfileDrainObject $profileDrain
     */
    public function setProfileDrain($profileDrain)
    {
        $this->profileDrain = $profileDrain;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->profileFill)) {
            return false;
        }

        if (!is_null($this->profileDrain)) {
            return false;
        }

        return true;
    }
}
