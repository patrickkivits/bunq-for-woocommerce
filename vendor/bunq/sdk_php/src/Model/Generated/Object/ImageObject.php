<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class ImageObject extends BunqModel
{
    /**
     * The public UUID of the public attachment containing the image.
     *
     * @var string
     */
    protected $attachmentPublicUuid;

    /**
     * The content-type as a MIME filetype.
     *
     * @var string
     */
    protected $contentType;

    /**
     * The image height in pixels.
     *
     * @var int
     */
    protected $height;

    /**
     * The image width in pixels.
     *
     * @var int
     */
    protected $width;

    /**
     * The public UUID of the public attachment containing the image.
     *
     * @return string
     */
    public function getAttachmentPublicUuid()
    {
        return $this->attachmentPublicUuid;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $attachmentPublicUuid
     */
    public function setAttachmentPublicUuid($attachmentPublicUuid)
    {
        $this->attachmentPublicUuid = $attachmentPublicUuid;
    }

    /**
     * The content-type as a MIME filetype.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * The image height in pixels.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * The image width in pixels.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->attachmentPublicUuid)) {
            return false;
        }

        if (!is_null($this->contentType)) {
            return false;
        }

        if (!is_null($this->height)) {
            return false;
        }

        if (!is_null($this->width)) {
            return false;
        }

        return true;
    }
}
