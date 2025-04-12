<?php

namespace Elgg\ActivityPub\Types\Core;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\AbstractType;
use Elgg\ActivityPub\Types\Core\SourceType;
use Elgg\ActivityPub\Types\Object\ImageType;
use Elgg\ActivityPub\Types\Object\PlaceType;

class ObjectType extends AbstractType
{
    #[ExportProperty]
    protected string $type = 'Object';

    #[ExportProperty]
    public string $id;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     */
    #[ExportProperty]
    public string|null $name;

    /**
     * The name MAY be expressed using multiple language-tagged values.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     */
    #[ExportProperty]
    public array|null $nameMap;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
     */
    #[ExportProperty]
    public string|null $content;

    /**
     * The content MAY be expressed using multiple language-tagged values.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
     */
    #[ExportProperty]
    public array|null $contentMap;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary
     */
    #[ExportProperty]
    public string|null $summary;

    /**
     * The content MAY be expressed using multiple language-tagged values.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary
     */
    #[ExportProperty]
    public array|null $summaryMap;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attributedto
     */
    #[ExportProperty]
    public string|null $attributedTo;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-published
     */
    #[ExportProperty]
    public string|null $published;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-updated
     */
    #[ExportProperty]
    public string|null $updated;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-starttime
     */
    #[ExportProperty]
    public string|null $startTime;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-endtime
     */
    #[ExportProperty]
    public string|null $endTime;

    /**
     * @param string|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-url
     */
    #[ExportProperty]
    public string|array $url;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-to
     */
    #[ExportProperty]
    public string|array|null $to;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-bto
     */
    #[ExportProperty]
    public string|array|null $bto;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-cc
     */
    #[ExportProperty]
    public string|array|null $cc;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-bcc
     */
    #[ExportProperty]
    public string|array|null $bcc;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-sensitive
     */
    #[ExportProperty]
    public bool $sensitive;

    /**
     * @param ObjectType[]|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment
     */
    #[ExportProperty]
    public array $attachment;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag
     */
    #[ExportProperty]
    public array $tag;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-audience
     */
    #[ExportProperty]
    public array|string $audience;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-target
     */
    #[ExportProperty]
    public array|string $target;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-inreplyto
     */
    #[ExportProperty]
    public string|null $inReplyTo;

    /**
     * @see https://www.w3.org/TR/activitypub/#the-source-property
     */
    #[ExportProperty]
    public SourceType $source;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-replies
     */
    #[ExportProperty]
    public string|array|null $replies;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-likes
     */
    #[ExportProperty]
    public array $likes;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-shares
     */
    #[ExportProperty]
    public array $shares;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-icon
     */
    #[ExportProperty]
    public ImageType $icon;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-image
     */
    #[ExportProperty]
    public ImageType $image;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediaType
     */
    #[ExportProperty]
    public string|null $mediaType;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-width
     */
    #[ExportProperty]
    public int $width;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-height
     */
    #[ExportProperty]
    public int $height;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-duration
     */
    #[ExportProperty]
    public string|null $duration;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-quoteUri
     */
    #[ExportProperty]
    public string|null $quoteUri;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-preview
     */
    #[ExportProperty]
    public string|null $preview;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-location
     */
    #[ExportProperty]
    public PlaceType $location;

    /**
     * @see https://misskey-hub.net/en/docs/
     */
    #[ExportProperty]
    public string $_misskey_summary;

    /**
     * Sets the ID (must be a string)
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Returns the ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->contexts[] = [
            'schema' => 'https://schema.org#',
            'ostatus' => 'http://ostatus.org#',
            'toot' => 'https://joinmastodon.org/ns#',
            'lemmy' => 'https://join-lemmy.org/ns#',
            'misskey' => 'https://misskey-hub.net/ns#',
            'vcard' => 'http://www.w3.org/2006/vcard/ns#',
            'dfrn' => 'http://purl.org/macgirvin/dfrn/1.0/',
            'diaspora' => 'https://diasporafoundation.org/ns/',
            'litepub' => 'http://litepub.social/ns#',
            'pt' => 'https://joinpeertube.org/ns#',
            'sm' => 'http://smithereen.software/ns#',
            'mitra' => 'http://jsonld.mitra.social#',
            'sc' => 'https://schema.org/',
            'PropertyValue' => 'schema:PropertyValue',
            'value' => 'schema:value',
            'Hashtag' => 'as:Hashtag',
            'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            'capabilities' => 'litepub:capabilities',
            'attributionDomains' => [
                '@id' => 'toot:attributionDomains',
                '@type' => '@id',
            ],
            'moderators' => [
                '@id' => 'lemmy:moderators',
                '@type' => '@id',
            ],
            'commentsEnabled' => 'pt:commentsEnabled',
            'playlists' => [
                '@id' => 'pt:playlists',
                '@type' => '@id',
            ],
            'postingRestrictedToMods' => 'lemmy:postingRestrictedToMods',
            'discoverable' => 'toot:discoverable',
            'suspended' => 'toot:suspended',
            'indexable' => 'toot:indexable',
            'sensitive' => 'as:sensitive',
            'icons' => 'as:icon',
            'conversation' => 'ostatus:conversation',
            'atomUri' => 'ostatus:atomUri',
            'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
            'directMessage' => 'litepub:directMessage',
            '_misskey_content' => 'misskey:_misskey_content',
            '_misskey_summary' => 'misskey:_misskey_summary',
        ];
    }
}
