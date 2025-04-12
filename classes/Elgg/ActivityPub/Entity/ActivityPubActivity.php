<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Entity;

use ElggComment;
use Elgg\ActivityPub\Entity\FederatedObject;
use Elgg\ActivityPub\Entity\FederatedUser;
use Elgg\ActivityPub\Enums\FederatedEntitySourcesEnum;
use Elgg\Database\Clauses\OrderByClause;
use Elgg\Friends\Notifications;
use Elgg\Values;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * ActivityPub activity entity class.
 */
class ActivityPubActivity extends \ElggObject
{
    const SUBTYPE = 'activitypub_activity';

    // Collections constants.
    const FOLLOWERS = 'followers';
    const FOLLOWING = 'following';
    const INBOX = 'inbox';
    const OUTBOX = 'outbox';
    const LIKED = 'liked';

    // Activity URL constants.
    const CONTEXT_URL = 'https://www.w3.org/ns/activitystreams';
    const SECURITY_URL = 'https://w3id.org/security/v1';
    const PUBLIC_URL = 'https://www.w3.org/ns/activitystreams#Public';

    // Regexp constants.
    const USERNAME_REGEXP = '(?:([A-Za-z0-9\._-]+)@((?:[A-Za-z0-9_-]+\.)+[A-Za-z]+))';
    const HASHTAGS_REGEXP = '(?:(?<=\s)|(?<=<p>)|(?<=<br>)|^)#([A-Za-z0-9_]+)(?:(?=\s|[[:punct:]]|$))';

    /**
     * {@inheritdoc}
     */
    protected function initializeAttributes()
    {
        parent::initializeAttributes();
        $this->attributes['subtype'] = self::SUBTYPE;
    }

    public function getDisplayName(): string
    {
        return (string) elgg_echo('activitypub:activitypub_activity', [$this->guid]);
    }

    /**
     * Returns whether the activity is processed or not.
     *
     * @return boolean
     */
    public function isProcessed(): bool
    {
        return (bool) $this->processed;
    }

    /**
     * Returns whether the activity is post processed or not (see postSave()).
     *
     * @return boolean
     */
    public function postProcessed(): bool
    {
        return (bool) $this->post_processed;
    }

    /**
     * Returns whether the activity is queued or not.
     *
     * @return boolean
     */
    public function isQueued(): bool
    {
        return (bool) $this->queued;
    }

    /**
     * Returns the collection.
     *
     * @return string
     */
    public function getCollection()
    {
        return (string) $this->collection;
    }

    /**
     * Returns the external ID.
     *
     * @return string
     */
    public function getExternalId()
    {
        return (string) $this->external_id;
    }

    /**
     * Returns the Activity type.
     *
     * @return string
     */
    public function getActivityType()
    {
        return (string) $this->activity_type;
    }

    /**
     * Returns the object.
     *
     * @return string
     */
    public function getActivityObject()
    {
        return (string) $this->activity_object;
    }

    /**
     * Returns the reply.
     *
     * @return string
     */
    public function getReply()
    {
        return (string) $this->reply;
    }

    /**
     * Returns the content.
     *
     * @return string
     */
    public function getContent()
    {
        return (string) $this->content;
    }

    /**
     * Returns the Activity actor.
     *
     * @return string
     */
    public function getActor()
    {
        return (string) $this->actor;
    }

    /**
     * Returns the target Entity subtype.
     *
     * @return string
     */
    public function getTargetEntitySubtype()
    {
        return (string) $this->entity_subtype;
    }

    /**
     * Return the target entity guid.
     *
     * @return integer
     */
    public function getTargetEntityGuid(): int
    {
        return (int) $this->entity_guid;
    }

    /**
     * Get the payload.
     *
     * @return string
     */
    public function getPayload()
    {
        return (string) $this->payload;
    }

    /**
     * Get the context.
     *
     * @return string
     */
    public function getContext()
    {
        return (string) $this->context;
    }

    /**
     * Get the raw to value.
     *
     * @return string
     */
    public function getToRaw()
    {
        return (string) $this->to;
    }

    /**
     * Get array of URL's for the to property.
     *
     * @return array
     */
    public function getTo(): array
    {
        $to = [];
        foreach (explode("\n", $this->getToRaw() ?: '') as $t) {
            $t = trim($t);
            if (!empty($t)) {
                $to[] = $t;
            }
        }
        return $to;
    }

    /**
     * Set to audience.
     *
     * @param $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        return $this->setMetadata('to', implode("\n", $to));
    }

    /**
     * Returns whether the item was read or not.
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return (bool) $this->is_read;
    }

    /**
     * Returns whether the item is muted or not.
     *
     * @return bool
     */
    public function isMuted(): bool
    {
        return (bool) $this->mute;
    }

    /**
     * Returns whether the item is public or not.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->access_id === ACCESS_PUBLIC;
    }

    /**
     * Returns whether the item is for followers only or not.
     *
     * @return bool
     */
    public function isFollowers(): bool
    {
        return $this->access_id === ACCESS_FRIENDS;
    }

    /**
     * Returns whether the item is unlisted or not.
     *
     * @return bool
     */
    public function isUnlisted(): bool
    {
        return $this->access_id === ACCESS_LOGGED_IN;
    }

    /**
     * Returns whether the item is private or not.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return (!in_array($this->access_id, [ACCESS_PUBLIC, ACCESS_FRIENDS, ACCESS_LOGGED_IN]));
    }

    /**
     * Whether this activity can be sent to a shared inbox.
     *
     * @return bool
     */
    public function canUseSharedInbox(): bool
    {
        if (!in_array($this->getActivityType(), ['Create', 'Update'])) {
            return false;
        }

        return $this->isPublic() || $this->isFollowers();
    }

    /**
     * Set the activity visibility to private.
     *
     * @param array $urls URLs of recipients
     * @param array $params Get params (actor, status, content, reply)
     *
     * @return array Compound array of each delivery user/delivery method's success or failure.
     */
    public function setMentioned(array $urls = [], array $params = []): array
    {
        if (empty($urls)) {
            return [];
        }

        $subject = elgg_echo('activitypub:private:subject');

        $reply = '';

        if (!empty($params['reply'])) {
            $reply_uri = $params['reply'];
            $reply_entity = elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($reply_uri) {
                return elgg()->activityPubManager->getEntityFromUri($reply_uri);
            });
            if ($reply_entity instanceof \ElggEntity) {
                $reply = elgg_echo('activitypub:reply:on:this', [$reply_entity->getURL()]);
            }
        }

        $body = elgg_echo('activitypub:private:body', [
            elgg_view('output/url', [
                'href' => $params['actor'],
                'text' => elgg_echo('activitypub:activities:actor'),
            ]),
            $params['content'],
            $reply,
            elgg_view('output/url', [
                'href' => $params['status'],
                'text' => $params['status'],
            ])
        ]);

        $result = [];

        foreach ($urls as $uri) {
            $entity = elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($uri) {
                return elgg()->activityPubManager->getEntityFromLocalUri($uri);
            });

            if ($entity instanceof \ElggEntity) {
                if ($entity instanceof \ElggUser && (string) $entity->subtype !== 'federated') {
                    $result[] = notify_user((int) $entity->guid, (int) elgg_get_site_entity()->guid, $subject, $body, []);
                } elseif (
                    elgg_is_active_plugin('groups')
                    && (bool) elgg_get_plugin_setting('enable_group', 'activitypub')
                    && (string) $entity->subtype !== 'federated'
                    && (bool) $entity->enable_activitypub
                    && (bool) $entity->activitypub_actor
                ) {
                    // Notify Group owner
                    $result[] = notify_user((int) $entity->owner_guid, (int) elgg_get_site_entity()->guid, $subject, $body, []);
                }
            }
        }

        return $result;
    }

    /**
     * Mute an activity.
     *
     * @return $this
     */
    public function mute()
    {
        return $this->setMetadata('mute', 1);
    }

    /**
     * Unmute an activity.
     *
     * @return $this
     */
    public function unMute()
    {
        return $this->setMetadata('mute', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function buildActivity()
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $object = [
            'id' => (string) $this->getActivityObject(),
        ];

        $to = $cc = $mention = $target = $audience = [];

        $return = [
            'type' => (string) $this->getActivityType(),
            'id' => (string) $this->getURL(),
            'actor' => (string) $this->getActor(),
            'published' => date('c', (int) $this->time_created),
            'to' => $to,
            'cc' => $cc,
            'object' => $object
        ];

        $data = elgg()->activityPubUtility->buildAudience($this);
        $mention = isset($data['mention']) ? $data['mention'] : [];

        // local entity
        $entity_subtype = (string) $this->entity_subtype;
        $entity_guid = (int) $this->entity_guid;

        if (isset($entity_guid) && isset($entity_subtype) && (int) $entity_guid > 0) {
            $entity = get_entity($entity_guid);
            if ($entity instanceof \ElggEntity) {
                $summary = null;

                if (mb_strlen((string) $entity->description, 'UTF-8') > 500) {
                    $summary = isset($entity->briefdescription) ? (string) $entity->briefdescription : elgg_get_excerpt((string) $entity->description, 500);
                }

                //attachments
                $attachments = [];

                $files = elgg_get_entities([
                    'relationship' => 'attached',
                    'relationship_guid' => (int) $entity->guid,
                    'inverse_relationship' => ($entity instanceof \wZm\River\Entity\River) ? true : false, // WIP - remove after updating 'river' plugin
                    'limit' => 0,
                    'order_by' => [new OrderByClause('time_created', 'ASC')],
                ]);

                if (!empty($files)) {
                    foreach ($files as $file) {
                        $mimetype = $file->getMimeType();
                        $basetype = substr($mimetype, 0, strpos($mimetype, '/'));

                        $attachment = [
                            'type' => match ($basetype) {
                                'image' => 'Image',
                                'video' => 'Video',
                                'audio' => 'Audio',
                                default => 'Document'
                            },
                            'name' => (string) $file->getDisplayName(),
                            'url' => (string) $file->getDownloadURL(false),
                            'mediaType' => $mimetype,
                        ];

                        $attachments[] = $attachment;
                    }
                }

                $content = '';

                if (!empty((string) $entity->description)) {
                    $options = [
                        'parse_emails' => false,
                        'parse_hashtags' => false,
                        'parse_urls' => true,
                        'parse_usernames' => false,
                        'parse_groups' => false,
                        'parse_mentions' => false,
                        'oembed' => false,
                        'sanitize' => true,
                        'autop' => true,
                    ];

                    $content = _elgg_services()->html_formatter->formatBlock((string) $entity->description, $options);
                }

                $id = (string) elgg()->activityPubUtility->getActivityPubID($entity);

                if (in_array($this->getActivityType(), ['Like', 'Dislike', 'Announce'])) {
                    $id = (string) $this->getActivityObject();
                }

                $object = [
                    'type' => elgg()->activityPubUtility->getActivityPubObject($entity),
                    'id' => $id,
                    'name' => (!$entity instanceof \wZm\Inbox\Message && !$entity instanceof \ElggMessage) ? (string) $entity->getDisplayName() : null,
                    'content' => $content,
                    'summary' => $summary,
                    'published' => date('c', (int) $entity->time_created),
                    'attributedTo' => $this->getActor(),
                    'url' => (string) $entity->getURL(),
                    'sensitive' => false,
                    'mediaType' => 'text/html',
                ];

                $object['to'] = $object['cc'] = $object['target'] = $object['audience'] = [];

                if (!empty((string) $this->updated)) {
                    $object['updated'] = (string) $this->updated;
                }

                if (!empty($attachments)) {
                    $object['attachment'] = $attachments;
                }

                //audience
                $owner = $entity->getOwnerEntity();
                $container = $entity->getContainerEntity();

                if ($container instanceof \ElggGroup) {
                    $object['audience'][] = elgg_generate_url('view:activitypub:group', [
                        'guid' => (int) $container->getGUID()
                    ]);

                    $object['to'][] = elgg_generate_url('view:activitypub:group', [
                        'guid' => (int) $container->getGUID()
                    ]);
                } elseif ($container->getContainerEntity() instanceof \ElggGroup) {
                    $object['audience'][] = elgg_generate_url('view:activitypub:group', [
                        'guid' => (int) $container->getContainerEntity()->getGUID()
                    ]);

                    $object['to'][] = elgg_generate_url('view:activitypub:group', [
                        'guid' => (int) $container->getContainerEntity()->getGUID()
                    ]);
                }

                //reply
                if ($entity instanceof \ElggComment || $entity instanceof \ElggWire || $entity instanceof \wZm\River\Entity\River) {
                    $reply_to = [];

                    if ($entity instanceof \ElggComment) {
                        $container_url = (string) $container->getURL();

                        if ($container instanceof \ElggComment) {
                            $container_url = elgg_generate_url('view:object:comment', [
                                'guid' => (int) $container->getGUID()
                            ]);
                        }

                        $reply_to = $container_url;
                    }

                    if ($entity instanceof \ElggWire) {
                        $container = $entity->getParent();

                        if ($container instanceof \ElggWire) {
                            $container_url = elgg_generate_url('view:object:thewire', [
                                'guid' => (int) $container->guid
                            ]);

                            $reply_to = $container_url;
                        }
                    }

                    if ($entity instanceof \wZm\River\Entity\River) {
                        $container = $entity->getParent();

                        if ($container instanceof \wZm\River\Entity\River) {
                            $container_url = elgg_generate_url('view:object:river', [
                                'guid' => (int) $container->guid
                            ]);

                            $reply_to = $container_url;
                        }
                    }

                    if ($container && !empty((string) $container->external_id)) {
                        $reply_to = (string) $container->external_id;
                    }

                    if (!empty($reply_to)) {
                        $object['inReplyTo'] = $reply_to;

                        if ($container instanceof \ElggGroup) {
                            $object['cc'] = [
                                elgg_generate_url('view:activitypub:group', [
                                    'guid' => (int) $container->getGUID()
                                ]),
                                elgg_generate_url('view:activitypub:user', [
                                    'guid' => (int) $owner->getGUID()
                                ]),
                            ];

                            $object['target'][] = elgg_generate_url('view:activitypub:group', [
                                'guid' => (int) $container->getGUID()
                            ]);
                        }

                        if ($entity instanceof \ElggComment) {
                            // nested comments
                            $original_container = !elgg_is_active_plugin('theme') ? $entity->getThreadEntity()->getContainerEntity()->getContainerEntity() : $entity->getOriginalContainer()->getContainerEntity();

                            if ($original_container instanceof \ElggGroup || $container->getContainerEntity() instanceof \ElggGroup) {
                                $group = ($original_container instanceof \ElggGroup) ? $original_container : $container->getContainerEntity();

                                $object['cc'] = [
                                    elgg_generate_url('view:activitypub:group', [
                                        'guid' => (int) $group->getGUID()
                                    ]),
                                    elgg_generate_url('view:activitypub:user', [
                                        'guid' => (int) $owner->getGUID()
                                    ]),
                                ];

                                $object['target'][] = elgg_generate_url('view:activitypub:group', [
                                    'guid' => (int) $group->getGUID()
                                ]);

                                $object['audience'][] = elgg_generate_url('view:activitypub:group', [
                                    'guid' => (int) $group->getGUID()
                                ]);
                            }
                        }
                    }
                }

                //tag
                $link = [];
                if (!$entity instanceof \ElggComment && !$entity instanceof \wZm\Inbox\Message && !$entity instanceof \ElggMessage) {
                    $link = [
                        'href' => (string) $entity->getURL(),
                        'name' => (string) $entity->getDisplayName() ?? null,
                    ];
                }

                //cover
                if ($image_url = elgg()->activityPubUtility->getActivityPubActorImage($entity, 'cover', 'large')) {
                    $image = [
                        'type' => 'Image',
                        'name' => (string) $entity->getDisplayName() ?? null,
                        'url' => $image_url,
                    ];
                    $object['image'] = (object) $image;
                }

                if (!empty($data['to'])) {
                    $object['to'] = array_unique(array_merge($object['to'], $data['to']));
                }
                if (!empty($data['cc'])) {
                    $object['cc'] = array_unique(array_merge($object['cc'], $data['cc']));
                }
                if (!empty($data['target'])) {
                    $object['target'] = array_unique(array_merge($object['target'], $data['target']));
                }

                $to = array_unique($object['to']);
                $cc = array_unique($object['cc']);
                $target = array_unique($object['target']);
                $audience = isset($object['target']) ? $object['audience'] : [];

                //Event
                if ($entity instanceof \Event) {
                    $object['summary'] = $entity->getExcerpt();
                    $object['startTime'] = $entity->getStartDate();
                    $object['endTime'] = $entity->getEndDate();

                    //location
                    // WIP - change for ElggTheme
                    if (isset($entity->location) || isset($entity->{'geo:lat'}) || isset($entity->{'geo:long'})) {
                        $location = [
                            'type' => 'Place',
                            'name' => (string) $entity->venue ?? null,
                            'address' => (string) $entity->location ?? null,
                            'latitude' => (string) $entity->getLatitude() ?? null,
                            'longitude' => (string) $entity->getLongitude() ?? null,
                        ];
                        $object['location'] = $location;
                    }

                    //attachments
                    $attachments = [];

                    $files = $entity->getFiles();

                    if (!empty($files)) {
                        $elggfile = new \ElggFile();
                        $elggfile->owner_guid = (int) $entity->guid;

                        foreach ($files as $file) {
                            $elggfile->setFilename($file->file);

                            if (!$elggfile->exists()) {
                                // check old storage location
                                $elggfile->setFilename("files/{$file->file}");
                            }

                            $mimetype = $file->mime;
                            $basetype = substr($mimetype, 0, strpos($mimetype, '/'));

                            $attachment = [
                                'type' => match ($basetype) {
                                    'image' => 'Image',
                                    'video' => 'Video',
                                    'audio' => 'Audio',
                                    default => 'Document'
                                },
                                'name' => (string) $file->title,
                                'url' => (string) $elggfile->getDownloadURL(false),
                                'mediaType' => $mimetype,
                            ];

                            $attachments[] = $attachment;
                        }
                    }

                    if (!empty($attachments)) {
                        $object['attachment'] = $attachments;
                    }

                    $contacts = [];

                    if (isset($entity->contact_guids)) {
                        $contact_guids = $entity->contact_guids;

                        if (!is_array($contact_guids)) {
                            $contact_guids = [$contact_guids];
                        }

                        foreach ($contact_guids as $guid) {
                            $user = get_entity((int) $guid);
                            if (!$user instanceof \ElggUser || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
                                continue;
                            }
                            $contacts[] = [
                                'id' => elgg()->activityPubUtility->getActivityPubID($user),
                            ];
                        }

                        if (!empty($contacts)) {
                            $object['contacts'] = $contacts;
                        }
                    }

                    $object['commentsEnabled'] = (bool) $entity->comments_on;
                    $object['repliesModerationOption'] = (bool) $entity->comments_on ? 'allow_all' : 'closed';
                    $object['timezone'] = elgg_is_active_plugin('theme') ? elgg_get_plugin_setting('timezone', 'theme', 'UTC') : date_default_timezone_get();
                    $object['anonymousParticipationEnabled'] = (bool) $entity->register_nologin;
                    $object['category'] = (string) $entity->event_type ?: 'MEETING';
                    $object['isOnline'] = false;
                    $object['status'] = 'CONFIRMED';
                    $object['externalParticipationUrl'] = (string) $entity->getURL();
                    $object['joinMode'] = 'external';

                    $attendee_count = (int) $entity->countAttendees();
                    $object['participantCount'] = $attendee_count;

                    $object['maximumAttendeeCapacity'] = isset($entity->max_attendees) ? (int) $entity->max_attendees : null;
                    $object['remainingAttendeeCapacity'] = isset($entity->max_attendees) ? ((int) $entity->max_attendees - $attendee_count) : null;
                }
            }
        }

        // Follow / Join type.
        if (in_array($this->getActivityType(), ['Follow', 'Join'])) {
            $return = [
                'id' => $this->getURL(),
                'type' => $this->getActivityType(),
                'actor' => $this->getActor(),
                'object' => $object['id'],
                'to' => [$object['id']],
                'cc' => [],
            ];
        }

        // Like / Dislike type.
        if (in_array($this->getActivityType(), ['Like', 'Dislike', 'Announce'])) {
            $return['object'] = $object['id'];

            if (!empty($data['to'])) {
                $return['to'] = $data['to'];
            }

            if (!empty($data['cc'])) {
                $return['cc'] = $data['cc'];
            }
        }

        // Accept type.
        if ($this->getActivityType() === 'Accept') {
            $type = 'Follow';

            if ($this->getActor() instanceof \ElggGroup) {
                $type = 'Join';
            }

            $return = [
                'id' => $this->getURL(),
                'type' => $this->getActivityType(),
                'actor' => $this->getActor(),
                'to' => [$object['id']],
                'object' => [
                    'type' => $type,
                    'id' => $this->getExternalId(),
                    'actor' => $object['id'],
                    'object' => $this->getActor(),
                ]
            ];
        }

        // Undo type.
        if ($this->getActivityType() === 'Undo') {
            $return = [
                'id' => $this->getURL(),
                'type' => $this->getActivityType(),
                'actor' => $this->getActor(),
                'to' => [$object['id']],
                'object' => [
                    'type' => 'Follow',
                    'id' => $this->getExternalId(),
                    'actor' => $this->getActor(),
                    'object' => $object['id'],
                ]
            ];
        }

        // Leave type.
        if ($this->getActivityType() === 'Leave') {
            $return = [
                'id' => $this->getURL(),
                'type' => $this->getActivityType(),
                'actor' => $this->getActor(),
                'to' => [$object['id']],
                'object' => [
                    'type' => 'Join',
                    'id' => $this->getExternalId(),
                    'actor' => $this->getActor(),
                    'object' => $object['id'],
                ]
            ];
        }

        // Delete type.
        if ($this->getActivityType() === 'Delete') {
            $actor = $this->getOwnerEntity();

            if (!$this->isPrivate()) {
                $to = array_merge([ActivityPubActivity::PUBLIC_URL], $this->getTo());
                $cc = [
                    elgg_generate_url('view:activitypub:user:followers', [
                        'guid' => (int) $actor->guid,
                    ]),
                ];
            } else {
                $to = $this->getTo();
                $cc = [];
            }

            $return = [
                'id' => $this->getURL(),
                'type' => $this->getActivityType(),
                'actor' => $this->getActor(),
                'object' => $object['id'],
                'to' => $to,
                'cc' => $cc,
            ];
        }

        // Create.
        if ($this->getActivityType() === 'Create') {
            if (!empty((string) $this->updated)) {
                $return['updated'] = (string) $this->updated;
            }

            $return['to'] = $to;
            $return['cc'] = $cc;

            if (!empty($audience)) {
                $return['audience'] = $audience;
            }

            $return['object'] = $object;

            $return['object']['tag'] = [];

            if (!empty($mention)) {
                foreach ($mention as $m) {
                    $tag = new \Elgg\ActivityPub\Types\Link\MentionType();
                    $tag->href = $m['href'];
                    $tag->name = $m['name'];
                    $return['object']['tag'][] = $tag;
                }
            }

            if (!empty($link)) {
                $tag = new \Elgg\ActivityPub\Types\Core\LinkType();
                $tag->href = $link['href'];
                $tag->name = $link['name'];
                $return['object']['tag'][] = $tag;
            }

            if (!empty($target)) {
                $return['target'] = $target;
            }
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function postOutboxProcess()
    {
        if (isset($this->post_processed) && (bool) $this->postProcessed()) {
            return;
        }

        $type = $this->getActivityType();

        // Outcoming accept request.
        if ($type === 'Accept' && $this->getCollection() === ActivityPubActivity::OUTBOX && (bool) $this->isProcessed()) {
            $actor = $this->getOwnerEntity();

            if (!$actor instanceof \ElggUser && !$actor instanceof \ElggGroup) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('NoActorFound'));
                }
                return false;
            }

            $uri = $this->getActivityObject();

            if (!empty($uri)) {
                try {
                    $follower = $this->setFederatedUser($uri);

                    if ($follower instanceof FederatedUser && $actor instanceof \ElggUser) {
                        $this->addRemoteFriend($follower, $actor);
                    } elseif ($follower instanceof FederatedUser && $actor instanceof \ElggGroup) {
                        $this->joinRemoteMember($follower, $actor);
                    }
                } catch (\Exception $e) {
                    if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                        $this->log("Error creating FederatedUser for activity {$this->guid}: {$e->getMessage()}");
                    }
                    return false;
                }
            }

            $this->setMetadata('post_processed', 1);
            $this->save();
        }

        // Outcoming undo request.
        if ($type === 'Undo' && $this->getCollection() === ActivityPubActivity::OUTBOX && (bool) $this->isProcessed()) {
            $actor = $this->getOwnerEntity();

            if (!$actor instanceof \ElggUser) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('NoActorFound'));
                }
                return false;
            }

            $options = [
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
            ];

            $follows = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                return elgg_get_entities(array_merge($options, [
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'activity_object',
                            'value' => (string) $this->getActivityObject(),
                        ],
                        [
                            'name' => 'actor',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => 'Follow',
                        ],
                    ],
                ]));
            });

            if (!empty($follows)) {
                foreach ($follows as $f) {
                    $f->delete();
                }
            }

            $accepts = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                return elgg_get_entities(array_merge($options, [
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'actor',
                            'value' => (string) $this->getActivityObject(),
                        ],
                        [
                            'name' => 'activity_object',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => 'Accept',
                        ],
                    ],
                ]));
            });

            if (!empty($accepts)) {
                foreach ($accepts as $a) {
                    $a->delete();
                }
            }

            $this->setMetadata('post_processed', 1);
            $this->save();
        }

        // WIP - make it only for reshare/repost
        // Outcoming announce request.
        /*
        if (in_array($type, ['Create']) && $this->getCollection() === ActivityPubActivity::OUTBOX && (bool) $this->isProcessed()) {
            $owner = $this->getOwnerEntity();
            if (!$owner instanceof \ElggUser && !$owner instanceof \ElggGroup) {
                return false;
            }

            $actor = (string) elgg()->activityPubUtility->getActivityPubID($owner);

            $activity = new ActivityPubActivity();
            $activity->owner_guid = $this->owner_guid;
            $activity->access_id = ACCESS_PUBLIC;
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('activity_type', 'Announce');
            $activity->setMetadata('actor', $actor);
            $activity->setMetadata('activity_object', $this->getURL());
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            if ($activity->canBeQueued()) {
                $activity->setMetadata('queued', 1);
            }

            if (!$activity->save()) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:outbox:post_proccess:announce:error', [$this->guid]));
                }
                return false;
            }

            $this->setMetadata('post_processed', 1);
            $this->save();
        }
            */
    }

    /**
     * {@inheritdoc}
     */
    public function preInboxSave(\ElggUser|\ElggGroup|string $entity = null): bool
    {
        $type = $this->getActivityType();

        // Check update.
        if ($entity instanceof \ElggEntity && $type === 'Update') {
            $this->updateExistingActivity($entity);
            return false;
        }

        // Delete request. If actor and object are not the same, try to find an activity and entity with the guid.
        if ($type === 'Delete') {
            if ($this->getActor() !== $this->getActivityObject()) {
                $entities = elgg_call(ELGG_IGNORE_ACCESS, function () {
                    return elgg_get_entities([
                        'type' => 'object',
                        'subtype' => [FederatedObject::SUBTYPE, 'comment'],
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'external_id',
                                'value' => (string) $this->getActivityObject(),
                            ],
                        ],
                        'limit' => 1,
                    ]);
                });

                if (!empty($entities)) {
                    foreach ($entities as $e) {
                        $e->delete();
                    }
                }

                $activities = elgg_call(ELGG_IGNORE_ACCESS, function () {
                    return elgg_get_entities([
                        'type' => 'object',
                        'subtype' => ActivityPubActivity::SUBTYPE,
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'activity_object',
                                'value' => (string) $this->getActivityObject(),
                            ],
                            [
                                'name' => 'activity_type',
                                'value' => 'Delete',
                                'operand' => '!=',
                            ],
                        ],
                        'limit' => 1,
                    ]);
                });

                if (!empty($activities)) {
                    foreach ($activities as $a) {
                        $a->delete();
                    }
                }
            }

            $this->delete();

            return false;
        }

        // Check if the actor and object already exists. If so, do not save the activity.
        if (in_array($type, ['Accept', 'Follow', 'Join', 'Undo', 'Leave'])) {
            $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($type) {
                return elgg_get_entities([
                    'type' => 'object',
                    'subtype' => ActivityPubActivity::SUBTYPE,
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'collection',
                            'value' => ActivityPubActivity::INBOX,
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => (string) $type,
                        ],
                        [
                            'name' => 'actor',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'activity_object',
                            'value' => (string) $this->getActivityObject(),
                        ],
                    ],
                ]);
            });

            if (!empty($activities)) {
                return false;
            }
        }

        // Check if we have an existing activity. Properties to match on: type, actor and external id.
        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($type) {
            return elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::INBOX,
                    ],
                    [
                        'name' => 'activity_type',
                        'value' => (string) $type,
                    ],
                    [
                        'name' => 'actor',
                        'value' => (string) $this->getActor(),
                    ],
                    [
                        'name' => 'external_id',
                        'value' => (string) $this->getExternalId(),
                    ],
                ],
            ]);
        });

        if (!empty($activities)) {
            return false;
        }

        // Types like Announce, Like, Dislike, Follow, Join will populate reference to local content, users or group
        if (in_array($type, ['Like', 'Dislike', 'Announce', 'Follow', 'Join'])) {
            $entity = get_entity(activitypub_get_guid((string) $this->getActivityObject()));

            if ($entity instanceof \ElggEntity) {
                $this->setMetadata('entity_guid', (int) $entity->guid);
                $this->setMetadata('entity_subtype', (string) $entity->getSubtype());
            }
        }

        // Add recipients
        $payload = json_decode($this->getPayload(), true);
        $recipients = $this->extractRecipients($payload);

        if ($type === 'Create' && empty($payload['cc'])) {
            $followers_url = $this->getActor() . '/followers';

            if (!in_array(ActivityPubActivity::PUBLIC_URL, $recipients, true)) {
                elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($recipients) {
                    $this->setMentioned($recipients, [
                        'actor' => (string) $this->getActor(),
                        'status' => (string) $this->getActivityObject(),
                        'content' => $this->getContent(),
                        'reply' => $this->getReply(),
                    ]);
                });
            } elseif (in_array($followers_url, $payload['to'])) {
                elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($recipients) {
                    $this->setMentioned($recipients, [
                        'actor' => (string) $this->getActor(),
                        'status' => (string) $this->getActivityObject(),
                        'content' => $this->getContent(),
                        'reply' => $this->getReply(),
                    ]);
                });
            }

            // we don't want to proccess it
            $this->setMetadata('processed', 1);
            $this->setMetadata('queued', 0);

            return true;
        }

        $this->setMetadata('processed', 0);

        if ($this->canBeQueued()) {
            $this->setMetadata('queued', 1);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function doInboxProcess(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $url = null;
        $entity = null;

        $entity_subtype = (string) $this->entity_subtype;
        $entity_guid = (int) $this->entity_guid;

        if (!empty($entity_guid) && !empty($entity_subtype) && $entity_guid > 0) {
            $entity = get_entity($entity_guid);
            if ($entity instanceof \ElggEntity) {
                $url = (string) $entity->getURL();
            }
        }

        if ($this->getReply()) {
            $url = $this->getReply();
        } elseif (!in_array($this->getActivityType(), ['Delete'])) {
            $url = $this->getActivityObject();
        }

        if (!empty($url)) {
            try {
                $date = gmdate('D, d M Y H:i:s T', time());

                $keyId = elgg_generate_url('view:activitypub:application');

                $digest = elgg()->activityPubSignature->createDigest($keyId);

                $parsed = parse_url($url);
                $host = $parsed['host'];
                $path = $parsed['path'];

                // Create signature.
                $signature = elgg()->activityPubSignature->createSignature((string) elgg_get_site_entity()->getDomain(), $host, $path, $digest, $date);

                $options = [
                    'headers' => [
                        'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                        'Content-Type' => 'application/activity+json; charset=utf-8',
                        'Host' => $host,
                        'Date' => $date,
                        'Digest' => $digest,
                        'Signature' => 'keyId="' . $keyId . '#main-key",headers="(request-target) host date digest",signature="' . base64_encode($signature) . '",algorithm="rsa-sha256"',
                    ],
                ];

                $response = elgg()->activityPubUtility->http_client()->get($url, $options);

                $body = $response->getBody()->getContents();

                $context = json_decode($body, true);

                if (isset($context['id']) && $context['id'] === $url) {
                    $this->setMetadata('context', $body);
                    return true;
                }
            } catch (\Exception $e) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log("Error fetching context for activity {$this->guid}: {$e->getMessage()}");
                }
                return false;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function postSaveProcess()
    {
        if (isset($this->post_processed) && (bool) $this->postProcessed()) {
            return;
        }

        $type = $this->getActivityType();

        // Incoming Create request.
        if ($type === 'Create' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            try {
                $payload = json_decode($this->getPayload(), true);

                elgg()->activityPubReader->read($payload);

                $this->setMetadata('post_processed', 1);
                $this->save();
            } catch (\Exception $e) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log('Error creating object: ' . $e->getMessage());
                }
                return false;
            }
        }

        // Incoming Follow request.
        if ($type === 'Follow' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $activity = new ActivityPubActivity();
            $activity->owner_guid = (int) $this->owner_guid;
            $activity->access_id = ACCESS_PUBLIC;
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('activity_type', 'Accept');
            $activity->setMetadata('external_id', (string) $this->getExternalId());
            $activity->setMetadata('actor', (string) $this->getActivityObject());
            $activity->setMetadata('activity_object', (string) $this->getActor());
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            if ($activity->canBeQueued()) {
                $activity->setMetadata('queued', 1);
            }

            if (!$activity->save()) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:inbox:post_save:accept:error', [(int) $this->guid]));
                }
                return false;
            }

            $this->setMetadata('post_processed', 1);
            $this->save();

            $owner = $activity->getOwnerEntity();

            $subject = elgg_echo('activitypub:follow:subject', [], $owner->getLanguage());
            $body = elgg_echo('activitypub:follow:body', [
                (string) elgg()->activityPubUtility->getActorName($this->getActor()),
                (string) elgg()->activityPubUtility->getActorDomain($this->getActor()),
                (string) $this->getActor(),
            ], $owner->getLanguage());

            notify_user((int) $owner->guid, (int) elgg_get_site_entity()->guid, $subject, $body);

            return true;
        }

        // Incoming Join request.
        if (elgg_is_active_plugin('groups') && (bool) elgg_get_plugin_setting('enable_group', 'activitypub') && $type === 'Join' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $activity = new ActivityPubActivity();
            $activity->owner_guid = (int) $this->owner_guid;
            $activity->access_id = ACCESS_PUBLIC;
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('activity_type', 'Accept');
            $activity->setMetadata('external_id', (string) $this->getExternalId());
            $activity->setMetadata('actor', (string) $this->getActivityObject());
            $activity->setMetadata('activity_object', (string) $this->getActor());
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            if ($activity->canBeQueued()) {
                $activity->setMetadata('queued', 1);
            }

            if (!$activity->save()) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:inbox:post_save:accept:error', [(int) $this->guid]));
                }
                return false;
            }

            $this->setMetadata('post_processed', 1);
            $this->save();

            // access bypass for getting invisible group
            $group = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity) {
                return get_entity((int) $activity->owner_guid);
            });

            // Get group owner here.
            $owner = $group->getOwnerEntity();

            // join or request
            $join = false;
            if ($group->isPublicMembership()) {
                // anyone can join public groups
                $join = true;
            }

            if ($join) {
                $subject = elgg_echo('activitypub:group:join:subject', [], $owner->getLanguage());
                $body = elgg_echo('activitypub:group:join:body', [
                    (string) elgg()->activityPubUtility->getActorName($this->getActor()),
                    (string) elgg()->activityPubUtility->getActorDomain($this->getActor()),
                    (string) $group->getDisplayName(),
                    (string) $this->getActor(),
                ], $owner->getLanguage());

                return notify_user((int) $owner->guid, (int) elgg_get_site_entity()->guid, $subject, $body);
            }

            $subject = elgg_echo('activitypub:group:request:subject', [], $owner->getLanguage());
            $body = elgg_echo('activitypub:group:request:body', [
                (string) elgg()->activityPubUtility->getActorName($this->getActor()),
                (string) elgg()->activityPubUtility->getActorDomain($this->getActor()),
                (string) $group->getDisplayName(),
                (string) $this->getActor(),
            ], $owner->getLanguage());

            return true;
        }

        // Incoming Move request. Remove any follow/accept requests in the inbox and outbox and create a new follow request in case you are following the original actor.
        // WIP - re-friendships and re-memberships
        if ($type === 'Move' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $options = [
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'owner_guid' => (int) $this->owner_guid,
            ];

            $follows = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                return elgg_get_entities(array_merge($options, [
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'activity_object',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'actor',
                            'value' => (string) elgg()->activityPubUtility->getActivityPubID($this->getOwnerEntity()),
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => ['Follow', 'Accept'],
                        ],
                    ],
                ]));
            });

            if (!empty($follows)) {
                foreach ($follows as $f) {
                    try {
                        if ($f->getActivityType() === 'Follow') {
                            // Outgoing follow request.
                            $activity = new ActivityPubActivity();
                            $activity->access_id = ACCESS_PUBLIC;
                            $activity->owner_guid = (int) $this->owner_guid;
                            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
                            $activity->setMetadata('activity_type', 'Follow');
                            $activity->setMetadata('actor', (string) elgg()->activityPubUtility->getActivityPubID($this->getOwnerEntity()));
                            $activity->setMetadata('activity_object', (string) $this->getActivityObject());
                            $activity->setMetadata('processed', 0);
                            $activity->setMetadata('status', 0);

                            if ($activity->canBeQueued()) {
                                $activity->setMetadata('queued', 1);
                            }

                            if (!$activity->save()) {
                                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                                    $this->log(elgg_echo('activitypub:inbox:post_save:move:error', [(int) $this->guid]));
                                }
                                return false;
                            }

                            $f->delete();
                        }
                    } catch (\Exception $ignored) {
                    }
                }
            }

            $followee = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                return elgg_get_entities(array_merge($options, [
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'activity_object',
                            'value' => (string) elgg()->activityPubUtility->getActivityPubID($this->getOwnerEntity()),
                        ],
                        [
                            'name' => 'actor',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => ['Follow', 'Accept'],
                        ],
                    ],
                ]));
            });

            if (!empty($followee)) {
                foreach ($followee as $f) {
                    try {
                        $f->delete();
                    } catch (\Exception $ignored) {
                    }
                }
            }

            $this->setMetadata('post_processed', 1);
            $this->save();
        }

        // Incoming Accept request.
        if ($type === 'Accept' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $activities = elgg_call(ELGG_IGNORE_ACCESS, function () {
                return elgg_get_entities([
                    'type' => 'object',
                    'subtype' => ActivityPubActivity::SUBTYPE,
                    'metadata_name_value_pairs' => [
                        [
                            'name' => 'activity_object',
                            'value' => (string) $this->getActor(),
                        ],
                        [
                            'name' => 'actor',
                            'value' => (string) $this->getActivityObject(),
                        ],
                        [
                            'name' => 'activity_type',
                            'value' => ['Follow', 'Join'],
                        ],
                        [
                            'name' => 'collection',
                            'value' => ActivityPubActivity::OUTBOX,
                        ]
                    ],
                    'limit' => 1,
                ]);
            });

            if (!empty($activities)) {
                foreach ($activities as $activity) {
                    $actor = $activity->getOwnerEntity();

                    if (!$actor instanceof \ElggUser && !$actor instanceof \ElggGroup) {
                        if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                            $this->log(elgg_echo('NoActorFound'));
                        }
                        return false;
                    }

                    $uri = $activity->getActivityObject();

                    if (!empty($uri)) {
                        try {
                            $follower = $this->setFederatedUser($uri);

                            if ($follower instanceof FederatedUser && $actor instanceof \ElggUser) {
                                $this->addRemoteFriend($actor, $follower);
                            } elseif ($follower instanceof FederatedUser && $actor instanceof \ElggGroup) {
                                $this->joinRemoteMember($follower, $actor);
                            }

                            $this->setMetadata('post_processed', 1);
                            $this->save();
                        } catch (\Exception $e) {
                            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                                $this->log("Error creating FederatedEntity for activity {$activity->guid}: {$e->getMessage()}");
                            }
                            return false;
                        }
                    }

                    return false;
                }

                return false;
            }
        }

        // Incoming Undo request.
        if ($type === 'Undo' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $build = $this->buildActivity();

            if (isset($build['object']) && isset($build['object']['type']) && $build['object']['type'] === 'Follow') {
                $options = [
                    'type' => 'object',
                    'subtype' => ActivityPubActivity::SUBTYPE,
                ];

                $follows = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                    return elgg_get_entities(array_merge($options, [
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'activity_object',
                                'value' => (string) $this->getActivityObject(),
                            ],
                            [
                                'name' => 'actor',
                                'value' => (string) $this->getActor(),
                            ],
                            [
                                'name' => 'activity_type',
                                'value' => 'Follow',
                            ],
                        ],
                    ]));
                });

                if (!empty($follows)) {
                    foreach ($follows as $f) {
                        $f->delete();
                    }
                }

                $accepts = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                    return elgg_get_entities(array_merge($options, [
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'actor',
                                'value' => (string) $this->getActivityObject(),
                            ],
                            [
                                'name' => 'activity_object',
                                'value' => (string) $this->getActor(),
                            ],
                            [
                                'name' => 'activity_type',
                                'value' => 'Accept',
                            ],
                        ],
                    ]));
                });

                if (!empty($accepts)) {
                    foreach ($accepts as $a) {
                        $a->delete();
                    }
                }

                $follower = elgg()->activityPubManager->getEntityFromUri((string) $this->getActor());
                $user = elgg()->activityPubManager->getEntityFromUri((string) $this->getActivityObject());

                if ($follower->isFriendsWith((int) $user->guid)) {
                    if (!$follower->removeFriend((int) $user->guid)) {
                        if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                            $this->log(elgg_echo('friends:remove:failure', [$user->getDisplayName()]));
                        }
                        return false;
                    }
                }

                if ($follower->isRemote($user)) {
                    if (!$follower->removeRelationship((int) $user->guid, 'remote_friend')) {
                        if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                            $this->log(elgg_echo('activitypub:user:remove:remote_friend:failure', [$user->getDisplayName()]));
                        }
                        return false;
                    }
                }

                $this->setMetadata('post_processed', 1);
                $this->save();
            }
        }

        // Incoming Leave request.
        if (elgg_is_active_plugin('groups') && (bool) elgg_get_plugin_setting('enable_group', 'activitypub') && $type === 'Leave' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $build = $this->buildActivity();

            if (isset($build['object']) && isset($build['object']['type']) && $build['object']['type'] === 'Join') {
                $options = [
                    'type' => 'object',
                    'subtype' => ActivityPubActivity::SUBTYPE,
                ];

                $joins = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                    return elgg_get_entities(array_merge($options, [
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'activity_object',
                                'value' => (string) $this->getActivityObject(),
                            ],
                            [
                                'name' => 'actor',
                                'value' => (string) $this->getActor(),
                            ],
                            [
                                'name' => 'activity_type',
                                'value' => 'Join',
                            ],
                        ],
                    ]));
                });

                if (!empty($joins)) {
                    foreach ($joins as $j) {
                        $j->delete();
                    }
                }

                $accepts = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
                    return elgg_get_entities(array_merge($options, [
                        'metadata_name_value_pairs' => [
                            [
                                'name' => 'actor',
                                'value' => (string) $this->getActivityObject(),
                            ],
                            [
                                'name' => 'activity_object',
                                'value' => (string) $this->getActor(),
                            ],
                            [
                                'name' => 'activity_type',
                                'value' => 'Accept',
                            ],
                        ],
                    ]));
                });

                if (!empty($accepts)) {
                    foreach ($accepts as $a) {
                        $a->delete();
                    }
                }

                $follower = elgg()->activityPubManager->getEntityFromUri($this->getActor());
                $group = elgg()->activityPubManager->getEntityFromUri($this->getActivityObject());

                if ((int) $group->owner_guid !== (int) $follower->guid) {
                    if (!$group->leave($follower)) {
                        if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                            $this->log(elgg_echo('groups:cantleave'));
                        }
                        return false;
                    }
                }

                if ($follower->isRemote($group)) {
                    if (!$follower->removeRelationship((int) $group->guid, 'remote_member')) {
                        if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                            $this->log(elgg_echo('activitypub:group:remove:remote_member:failure', [(string) $group->getDisplayName()]));
                        }
                        return false;
                    }
                }

                $this->setMetadata('post_processed', 1);
                $this->save();
            }
        }

        // Incoming Like request.
        if (elgg_is_active_plugin('likes') && $type === 'Like' && $this->getCollection() === ActivityPubActivity::INBOX && (bool) $this->isProcessed()) {
            $entity_guid = (int) $this->entity_guid;
            $entity = get_entity($entity_guid);

            try {
                $payload = json_decode($this->getPayload(), true);
                $object = $payload['object'];
                $uri = $payload['actor'];

                $actor = $this->setFederatedUser($uri);

                if (!$actor instanceof FederatedUser) {
                    if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                        $this->log('No found FederatedUser for activity: ' . (int) $this->guid);
                    }
                    return false;
                }

                if (!$entity->canAnnotate((int) $actor->guid, 'likes')) {
                    if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                        $this->log(elgg_echo('likes:failure') . ' : canNotAnnotate');
                    }
                    return false;
                }

                if (elgg_annotation_exists($entity_guid, 'likes', (int) $actor->guid)) {
                    if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                        $this->log(elgg_echo('likes:failure') . ' : annotationExists');
                    }
                    return false;
                }

                $annotation_id = $entity->annotate('likes', 'likes', ACCESS_PUBLIC, (int) $actor->guid);

                // tell user annotation didn't work if that is the case
                if (!$annotation_id) {
                    if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                        $this->log(elgg_echo('likes:failure') . ' : noAnnotation');
                    }
                    return false;
                }

                if ((int) $entity->owner_guid === (int) $actor->guid) {
                    return true;
                }

                $owner = $entity->getOwnerEntity();

                $annotation = elgg_get_annotation_from_id($annotation_id);

                $title_str = (string) $entity->getDisplayName();
                if (!$title_str) {
                    $title_str = elgg_get_excerpt((string) $entity->description, 80);
                }

                $site = elgg_get_site_entity();

                // summary for site_notifications
                $summary = elgg_echo('likes:notifications:subject', [
                    (string) $actor->getDisplayName(),
                    $title_str,
                ], $owner->getLanguage());

                // prevent long subjects in mail
                $title_str = elgg_get_excerpt($title_str, 80);
                $subject = elgg_echo('likes:notifications:subject', [
                    (string) $actor->getDisplayName(),
                    $title_str,
                ], $owner->getLanguage());

                $body = elgg_echo('likes:notifications:body', [
                    (string) $actor->getDisplayName(),
                    $title_str,
                    (string) $site->getDisplayName(),
                    (string) $entity->getURL(),
                    (string) $actor->getURL(),
                ], $owner->getLanguage());

                notify_user(
                    (int) $entity->owner_guid,
                    (int) $actor->guid,
                    $subject,
                    $body,
                    [
                        'action' => 'create',
                        'object' => $annotation,
                        'summary' => $summary,
                        'url' => (string) $entity->getURL(),
                    ]
                );

                $this->setMetadata('post_processed', 1);
                $this->save();
            } catch (\Exception $e) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log('Error creating like: ' . $e->getMessage());
                }
                return false;
            }
        }

        // WIP - Incoming Dislike request.
    }

    /**
     * Update existing activity.
     *
     */
    protected function updateExistingActivity(\ElggUser|\ElggGroup $entity)
    {
        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity) {
            return elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'owner_guid' => (int) $entity->guid,
                'limit' => 1,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'activity_type',
                        'value' => 'Create',
                    ],
                    [
                        'name' => 'actor',
                        'value' => $this->getActor(),
                    ],
                    [
                        'name' => 'activity_object',
                        'value' => $this->getActivityObject(),
                    ],
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::INBOX,
                    ],
                ],
            ]);
        });

        if (!empty($activities)) {
            foreach ($activities as $activity) {
                $activity->payload = (string) $this->getPayload();
                $activity->content = (string) $this->getContent();
                $activity->updated = date('c', time());
                $activity->save();

                // let others know about the updating
                elgg_trigger_event_results('update', 'activitypub', [
                    'entity' => $activity,
                ], true);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeQueued(): bool
    {
        $canBeQueued = false;

        if ($this->getCollection() === ActivityPubActivity::OUTBOX && !$this->isProcessed()) {
            $canBeQueued = true;
        } elseif ($this->getCollection() === ActivityPubActivity::INBOX && !$this->isProcessed() && ($this->getReply() || in_array($this->getActivityType(), ['Create', 'Like', 'Dislike', 'Announce', 'Follow', 'Join', 'Accept', 'Undo', 'Leave']))) {
            $canBeQueued = true;
        }

        return $canBeQueued;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUndone(): bool
    {
        $canBeUndone = false;

        if ($this->getCollection() === ActivityPubActivityInterface::OUTBOX && (in_array($this->getActivityType(), ['Follow', 'Join'])) && $this->isPublished()) {
            $canBeUndone = true;
        }

        return $canBeUndone;
    }

    /**
     * {@inheritdoc}
     */
    protected function setFederatedUser(string $uri = ''): ?FederatedUser
    {
        // Get existed entity from URI
        $follower = elgg()->activityPubManager->getEntityFromUri($uri);
        if ($follower instanceof FederatedUser) {
            return $follower;
        }

        $data = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($uri);

        if (!$data || empty($data)) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('EmptyData'));
            }
            return null;
        }

        if (!isset($data['@context']) || !isset($data['type'])) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('EmptyContextType'));
            }
            return null;
        }

        // Create a new remote user
        $follower = new FederatedUser();

        $username = (string) $data['preferredUsername'] ?? elgg()->activityPubUtility->getActorNameFromUrl($uri);

        // WIP - make checking username on blacklist, limit better
        if (strlen($username) < 4) {
            $username = $username . '_';
        }

        while (elgg_get_user_by_username($username)) {
            $username = $username . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, 3);
        }

        $follower->username = $username;

        $follower->name = (string) $data['name'] ?? elgg()->activityPubUtility->getActorNameFromUrl($uri);

        if (!$follower->save()) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('RemoteFriendNotSave'));
            }
            return false;
        }

        $follower->setNotificationSetting('site', false);
        $follower->setNotificationSetting('email', false);

        // Author icon
        $avatar = (array) $data['icon'] ?? [];

        if (!empty($avatar)) {
            if ($photo = elgg()->activityPubMediaCache->saveImageFromUrl((string) $avatar['url'], 'avatar')) {
                $follower->saveIconFromElggFile($photo, 'icon');
                $follower->setMetadata('thumbnail_url', elgg_get_inline_url($photo));
            }
        }

        // Cover icon
        $cover = (array) $data['image'] ?? [];

        if (!empty($cover)) {
            if ($photo = elgg()->activityPubMediaCache->saveImageFromUrl((string) $cover['url'], 'cover')) {
                $follower->saveIconFromElggFile($photo, 'cover');
                $follower->setMetadata('cover_url', elgg_get_inline_url($photo));
            }
        }

        $description = (string) $data['summary'] ?? '';
        $follower->setMetadata('description', $description);
        $follower->setProfileData('description', $description, ACCESS_PUBLIC);

        $domain = elgg()->activityPubUtility->getActorDomain($uri);
        $follower->setMetadata('source', FederatedEntitySourcesEnum::ACTIVITY_PUB);
        $follower->setProfileData('briefdescription', $domain, ACCESS_PUBLIC);

        $follower->setMetadata('canonical_url', (string) $data['id']);
        $follower->setMetadata('activity_type', 'Person');

        $follower->setMetadata('website', (string) $data['url']);
        $follower->setProfileData('website', (string) $data['url'], ACCESS_PUBLIC);

        if (!$follower->save()) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('RemoteFriendNotSave'));
            }
            return false;
        }

        return $follower;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRemoteFriend(\ElggUser $follower, \ElggUser $actor): bool
    {
        // Request friendship
        if ((bool) elgg_get_plugin_setting('friend_request', 'friends')) {
            $this->requestFriend($follower, $actor);
        } else {
            // Add friend
            if (!$follower->addFriend((int) $actor->guid, true)) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('friends:add:failure', [$actor->getDisplayName()]));
                }
                return false;
            }
        }

        if ($follower instanceof FederatedUser) {
            return $follower->addRelationship((int) $actor->guid, 'remote_friend');
        } elseif ($actor instanceof FederatedUser) {
            return $actor->addRelationship((int) $follower->guid, 'remote_friend');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinRemoteMember(\ElggUser $follower, \ElggGroup $actor): bool
    {
        if (!(bool) elgg()->activityPubUtility->isEnabledGroup($actor)) {
            return false;
        }

        // join or request
        $join = false;
        if ($actor->isPublicMembership()) {
            // anyone can join public groups
            $join = true;
        } else {
            if ($actor->hasRelationship((int) $follower->guid, 'invited')) {
                // user has invite to closed group
                $join = true;
            }
        }

        if ($join) {
            if (!$actor->join($follower, ['create_river_item' => true])) {
                if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                    $this->log(elgg_echo('groups:cantjoin'));
                }
                return false;
            }

            return $follower->addRelationship((int) $actor->guid, 'remote_member');
        }

        if ($follower->hasRelationship((int) $actor->guid, 'membership_request')) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('groups:joinrequest:exists'));
            }
            return false;
        }

        if (!$follower->addRelationship((int) $actor->guid, 'membership_request')) {
            if ((bool) elgg_get_plugin_setting('log_activity_error', 'activitypub')) {
                $this->log(elgg_echo('groups:joinrequestnotmade'));
            }
            return false;
        }

        // Notify group owner
        $owner = $actor->getOwnerEntity();
        $url = elgg_generate_url('requests:group:group', [
            'guid' => (int) $actor->guid,
        ]);

        $subject = elgg_echo('groups:request:subject', [
            (string) $follower->getDisplayName(),
            (string) $actor->getDisplayName(),
        ], $owner->getLanguage());

        $body = elgg_echo('groups:request:body', [
            (string) $follower->getDisplayName(),
            (string) $actor->getDisplayName(),
            (string) $follower->getURL(),
            $url,
        ], $owner->getLanguage());

        $params = [
            'action' => 'membership_request',
            'object' => $actor,
            'url' => $url,
        ];
        notify_user((int) $owner->guid, (int) $follower->guid, $subject, $body, $params);

        return true;
    }

    protected function requestFriend(\ElggUser $follower, \ElggUser $actor)
    {
        if ($actor->isFriendsWith((int) $follower->guid)) {
            // the friend is already friends with the user, so accept the other way around automatically
            $result = $this->addRemoteFriend($follower, $actor);

            if ($result) {
                Notifications::sendAddFriendNotification($actor, $follower);
            }

            return $result;
        }

        if ($actor->hasRelationship((int) $follower->guid, 'friendrequest')) {
            // friend requested to be friends with user, so accept request
            $actor->addFriend((int) $follower->guid, true);
            $result = $this->addRemoteFriend($follower, $actor);

            if ($result) {
                Notifications::sendAcceptedFriendRequestNotification($actor, $follower);
            }

            return $result;
        }

        if ($follower->addRelationship((int) $actor->guid, 'friendrequest')) {
            // friend request made, notify potential friend
            $subject = elgg_echo('friends:notification:request:subject', [(string) $follower->getDisplayName()], $actor->getLanguage());
            $message = elgg_echo('friends:notification:request:message', [
                (string) $follower->getDisplayName(),
                (string) elgg_get_site_entity()->getDisplayName(),
                elgg_generate_url('collection:relationship:friendrequest:pending', [
                    'username' => (string) $actor->username,
                ]),
            ], $actor->getLanguage());

            $params = [
                'action' => 'friendrequest',
                'object' => $follower,
                'friend' => $actor,
            ];
            notify_user((int) $actor->guid, (int) $follower->guid, $subject, $message, $params);

            return true;
        }
    }

    /**
     * Extract recipient URLs from Activity object.
     *
     * @param array $data The Activity object as array.
     *
     * @return array The list of user URLs.
     */
    public function extractRecipients(array $data = []): array
    {
        $recipient_items = [];

        foreach (['to', 'bto', 'cc', 'bcc', 'audience'] as $i) {
            if (array_key_exists($i, $data)) {
                if (is_array($data[$i])) {
                    $recipient = $data[$i];
                } else {
                    $recipient = [$data[$i]];
                }
                $recipient_items = array_merge($recipient_items, $recipient);
            }

            if (is_array($data['object']) && array_key_exists($i, $data['object'])) {
                if (is_array($data['object'][$i])) {
                    $recipient = $data['object'][$i];
                } else {
                    $recipient = [$data['object'][$i]];
                }
                $recipient_items = array_merge($recipient_items, $recipient);
            }
        }

        $recipients = [];

        // Flatten array.
        foreach ($recipient_items as $recipient) {
            if (is_array($recipient)) {
                // Check if recipient is an object.
                if (array_key_exists('id', $recipient)) {
                    $recipients[] = $recipient['id'];
                }
            } else {
                $recipients[] = $recipient;
            }
        }

        return array_unique($recipients);
    }

    /** Logger */
    public function log($message = '')
    {
        $log_file = elgg_get_data_path() . 'activitypub/logs/log_activity_error';

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }
}
