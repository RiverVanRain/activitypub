<?php

namespace Elgg\ActivityPub\Types\Actor;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Types\AbstractType;
use Elgg\ActivityPub\Types\Core\SourceType;
use Elgg\ActivityPub\Types\Object\ImageType;

/**
 * https://www.w3.org/TR/activitypub/#actor-objects
 */
abstract class AbstractActorType extends AbstractType
{
    #[ExportProperty]
    protected string $type;

    #[ExportProperty]
    public string $id;

    #[ExportProperty]
    public string $name;

    #[ExportProperty]
    public string $preferredUsername;

    #[ExportProperty]
    public string $url;

    #[ExportProperty]
    public string $inbox;

    #[ExportProperty]
    public string $outbox;

    #[ExportProperty]
    public string $following;

    #[ExportProperty]
    public string $followers;

    #[ExportProperty]
    public string $liked;

    #[ExportProperty]
    public bool $manuallyApprovesFollowers = false;

    #[ExportProperty]
    public bool $discoverable = false;

    #[ExportProperty]
    public bool $indexable = true;

    #[ExportProperty]
    public string $published;

    #[ExportProperty]
    public string $updated;

    #[ExportProperty]
    public string $webfinger;

    #[ExportProperty]
    public array $attributionDomains;

    #[ExportProperty]
    public PublicKeyType $publicKey;

    #[ExportProperty]
    public array $endpoints;

    #[ExportProperty]
    public bool $suspended = false;

    #[ExportProperty]
    public string $content;

    #[ExportProperty]
    public string $summary;

    #[ExportProperty]
    public SourceType $source;

    #[ExportProperty]
    public string $_misskey_summary;

    #[ExportProperty]
    public ImageType $icon;

    #[ExportProperty]
    public ImageType $image;

    #[ExportProperty]
    public array $attachment;

    public function __construct()
    {
        $this->contexts[] = ActivityPubActivity::SECURITY_URL;
        $this->contexts[] = 'https://www.w3.org/ns/did/v1';
        $this->contexts[] = 'https://purl.archive.org/socialweb/webfinger';
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
            'directMessage' => 'litepub:directMessage',
            '_misskey_content' => 'misskey:_misskey_content',
            '_misskey_summary' => 'misskey:_misskey_summary',
        ];
    }
}
