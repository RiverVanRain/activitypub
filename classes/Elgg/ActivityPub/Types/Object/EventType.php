<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ObjectType;

class EventType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Event';

    public function __construct()
    {
        $this->contexts[] = 'https://schema.org/';
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
            'mz' => 'https://joinmobilizon.org/ns#',
            'status' => 'http://www.w3.org/2002/12/cal/ical#status',
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
            'PostalAddress' => 'sc:PostalAddress',
            'address' => [
                '@id' => 'sc:address',
                '@type' => 'sc:PostalAddress'
            ],
            'addressCountry' => 'sc:addressCountry',
            'addressLocality' => 'sc:addressLocality',
            'addressRegion' => 'sc:addressRegion',
            'postalCode' => 'sc:postalCode',
            'streetAddress' => 'sc:streetAddress',
            'isOnline' => 'mz:isOnline',
            'timezone' => 'mz:timezone',
            'participantCount' => 'mz:participantCount',
            'anonymousParticipationEnabled' => 'mz:anonymousParticipationEnabled',
            'joinMode' => [
                '@id' => 'mz:joinMode',
                '@type' => 'mz:joinModeType',
            ],
            'externalParticipationUrl' => [
                '@id' => 'mz:externalParticipationUrl',
                '@type' => 'schema:URL',
            ],
            'repliesModerationOption' => [
                '@id' => 'mz:repliesModerationOption',
                '@type' => '@vocab',
            ],
            'contacts' => [
                '@id' => 'mz:contacts',
                '@type' => '@id',
            ],
        ];
    }

    /**
     * The events contacts.
     *
     * @context {
     *   '@id'   => 'mz:contacts',
     *   '@type' => '@id',
     * }
     *
     * @param array Array of contacts (ActivityPub actor IDs).
     */
    #[ExportProperty]
    public array $contacts;

    /**
     * Extension invented by PeerTube whether comments/replies are <enabled>
     * Mobilizon also implemented this as a fallback to their own
     * repliesModerationOption.
     *
     * @see https://docs.joinpeertube.org/api/activitypub#video
     * @see https://docs.mobilizon.org/5.%20Interoperability/1.activity_pub/#extensions
     * @param bool|null
     */
    #[ExportProperty]
    public bool|null $commentsEnabled;

    /**
     * Moderation option for replies.
     *
     * @context https://joinmobilizon.org/ns#repliesModerationOption
     * @see https://docs.mobilizon.org/5.%20Interoperability/1.activity_pub/#repliesmoderation
     * @param string
     */
    #[ExportProperty]
    public string $repliesModerationOption;

    /**
     * Timezone of the event.
     *
     * @context https://joinmobilizon.org/ns#timezone
     * @param string
     */
    #[ExportProperty]
    public string $timezone;

    /**
     * Whether anonymous participation is enabled.
     *
     * @context https://joinmobilizon.org/ns#anonymousParticipationEnabled
     * @see https://docs.mobilizon.org/5.%20Interoperability/1.activity_pub/#anonymousparticipationenabled
     * @param bool
     */
    #[ExportProperty]
    public bool $anonymousParticipationEnabled;

    /**
     * The event's category.
     *
     * @context https://schema.org/category
     * @param string
     */
    #[ExportProperty]
    public string $category;

    /**
     * Language of the event.
     *
     * @context https://schema.org/inLanguage
     * @param string
     */
    #[ExportProperty]
    public string $inLanguage;

    /**
     * Whether the event is online.
     *
     * @context https://joinmobilizon.org/ns#isOnline
     * @param bool
     */
    #[ExportProperty]
    public bool $isOnline;

    /**
     * The event's status.
     *
     * @context https://www.w3.org/2002/12/cal/ical#status
     * @param string
     */
    #[ExportProperty]
    public string $status;

    /**
     * The external participation URL.
     *
     * @context https://joinmobilizon.org/ns#externalParticipationUrl
     * @param string
     */
    #[ExportProperty]
    public string $externalParticipationUrl;

    /**
     * Indicator of how new members may be able to join.
     *
     * @context https://joinmobilizon.org/ns#joinMode
     * @see https://docs.mobilizon.org/5.%20Interoperability/1.activity_pub/#joinmode
     * @param string
     */
    #[ExportProperty]
    public string $joinMode;

    /**
     * The participant count of the event.
     *
     * @context https://joinmobilizon.org/ns#participantCount
     * @param int
     */
    #[ExportProperty]
    public int $participantCount;

    /**
     * How many places there can be for an event.
     *
     * @context https://schema.org/maximumAttendeeCapacity
     * @see https://docs.mobilizon.org/5.%20Interoperability/1.activity_pub/#maximumattendeecapacity
     * @param int
     */
    #[ExportProperty]
    public int $maximumAttendeeCapacity;

    /**
     * The number of attendee places for an event that remain unallocated.
     *
     * @context https://schema.org/remainingAttendeeCapacity
     * @see https://docs.joinmobilizon.org/contribute/activity_pub/#remainignattendeecapacity
     * @param int
     */
    #[ExportProperty]
    public int $remainingAttendeeCapacity;
}
