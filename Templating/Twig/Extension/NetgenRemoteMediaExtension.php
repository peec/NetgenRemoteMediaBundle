<?php

namespace Netgen\Bundle\RemoteMediaBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Variation;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\Helper;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProvider;
use Twig_Extension;
use Twig_SimpleFunction;

class NetgenRemoteMediaExtension extends Twig_Extension
{
    /**
     * @var \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProviderInterface
     */
    protected $provider;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * @var \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\Helper
     */
    protected $helper;

    /**
     * NetgenRemoteMediaExtension constructor.
     *
     * @param \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProvider $provider
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\Helper
     */
    public function __construct(
        RemoteMediaProvider $provider,
        TranslationHelper $translationHelper,
        ContentTypeService $contentTypeService,
        Helper $helper
    ) {
        $this->provider = $provider;
        $this->translationHelper = $translationHelper;
        $this->contentTypeService = $contentTypeService;
        $this->helper = $helper;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'netgen_remote_media';
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ng_field_settings',
                array($this, 'getFieldSettings')
            ),
            new Twig_SimpleFunction(
                'netgen_remote_thumbnail',
                array($this, 'getVideoThumbnail')
            ),
            new Twig_SimpleFunction(
                'netgen_remote_video',
                array($this, 'getRemoteVideoTag')
            ),
            new Twig_SimpleFunction(
                'netgen_remote_media_fits',
                array($this, 'mediaFits')
            ),
            new Twig_SimpleFunction(
                'netgen_remote_resource',
                array($this, 'getResourceDownloadLink')
            ),
            new Twig_SimpleFunction(
                'netgen_remote_variation',
                array($this, 'getRemoteImageVariation')
            ),
        );
    }

    /**
     * Returns thumbnail url
     *
     * @param \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value $value
     *
     * @return string
     */
    public function getVideoThumbnail(Value $value)
    {
        return $this->provider->getVideoThumbnail($value->resourceId);
    }

    /**
     * Generates html5 video tag for the video with provided id
     *
     * @param Value $value
     * @param string $format
     * @param array $availableFormats
     *
     * @return mixed
     */
    public function getRemoteVideoTag(Value $value, $format = '', $availableFormats = array())
    {
        return $this->provider->generateVideoTag($value->resourceId, $format, $availableFormats);
    }

    /**
     * Calculates whether the remote image fits into all variations
     *
     * @param Value $value
     * @param $variations
     *
     * @return bool
     */
    public function mediaFits(Value $value, $variations)
    {
        $valueWidth = $value->metaData['width'];
        $valueHeight = $value->metaData['height'];

        foreach ($variations as $variationName => $variationSize) {
            $variationSizeArray = explode('x', $variationSize);

            if ($valueWidth < $variationSizeArray[0] || $valueHeight < $variationSizeArray[1]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the link to the remote resource
     *
     * @param Value $value
     *
     * @return string
     */
    public function getResourceDownloadLink(Value $value)
    {
        return $this->provider->generateDownloadLink($value);
    }

    /**
     * Returns variation by specified format
     *
     * @param Content    $content
     * @param string        $fieldIdentifier
     * @param string        $format
     * @param bool          $secure
     *
     * @return \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Variation
     */
    public function getRemoteImageVariation(Content $content, $fieldIdentifier, $format, $secure = true)
    {
        $field = $this->translationHelper->getTranslatedField($content, $fieldIdentifier);
        $contentTypeIdentifier = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId)->identifier;

        return $this->provider->buildVariation($field->value, $contentTypeIdentifier, $format, $secure);
    }
}
