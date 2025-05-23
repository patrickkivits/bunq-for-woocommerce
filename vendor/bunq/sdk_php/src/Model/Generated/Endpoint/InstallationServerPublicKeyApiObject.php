<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;

/**
 * Using /installation/_/server-public-key you can request the ServerPublicKey again. This is done by referring to the id
 * of the Installation.
 *
 * @generated
 */
class InstallationServerPublicKeyApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_LISTING = 'installation/%s/server-public-key';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'ServerPublicKey';

    /**
     * The server's public key for this Installation.
     *
     * @var string
     */
    protected $serverPublicKey;

    /**
     * Show the ServerPublicKey for this Installation.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int $installationId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseInstallationServerPublicKeyApiObjectList
     */
    public static function listing(int $installationId, array $params = [], array $customHeaders = []): BunqResponseInstallationServerPublicKeyApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [$installationId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseInstallationServerPublicKeyApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The server's public key for this Installation.
     *
     * @return string
     */
    public function getServerPublicKey()
    {
        return $this->serverPublicKey;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $serverPublicKey
     */
    public function setServerPublicKey($serverPublicKey)
    {
        $this->serverPublicKey = $serverPublicKey;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->serverPublicKey)) {
            return false;
        }

        return true;
    }
}
