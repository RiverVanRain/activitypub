<?php

namespace Elgg\ActivityPub\Factories;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Entity\FederatedObject;
use Elgg\ActivityPub\Enums\FederatedObjectSourcesEnum;
use Elgg\ActivityPub\Exceptions\NotImplementedException;
use Elgg\ActivityPub\Exceptions\RemoteGoneException;
use Elgg\ActivityPub\Exceptions\RemoteRateLimitedException;
use Elgg\ActivityPub\Helpers\ContentParserBuild;
use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Elgg\ActivityPub\Manager;
use Elgg\ActivityPub\Types\Core\ObjectType;
use Elgg\ActivityPub\Types\Core\SourceType;
use Elgg\ActivityPub\Types\Link\MentionType;
use Elgg\ActivityPub\Types\Object\ArticleType;
use Elgg\ActivityPub\Types\Object\AudioType;
use Elgg\ActivityPub\Types\Object\DocumentType;
use Elgg\ActivityPub\Types\Object\EventType;
use Elgg\ActivityPub\Types\Object\ImageType;
use Elgg\ActivityPub\Types\Object\NoteType;
use Elgg\ActivityPub\Types\Object\PageType;
use Elgg\ActivityPub\Types\Object\VideoType;
use Elgg\Database\Clauses\OrderByClause;
use Elgg\Exceptions\HttpException;
use Elgg\Exceptions\Http\EntityNotFoundException;
use Elgg\Exceptions\Http\PageNotFoundException;
use Elgg\Traits\Di\ServiceFacade;

class ObjectFactory
{
    use ServiceFacade;

    protected $manager;

    public function __construct(
        Manager $manager,
    ) {
        $this->manager = $manager;
    }

     /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubObjectFactory';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    public function fromUri(string $uri): ObjectType
    {
        if ($this->manager->isLocalUri($uri)) {
            $entity = $this->manager->getEntityFromUri($uri);
            if (!$entity instanceof \ElggObject) {
                throw new PageNotFoundException();
            }
            return $this->fromEntity($entity);
        }

        try {
            $response = elgg()->activityPubClient->request('GET', $uri);
            $json = json_decode($response->getBody()->getContents(), true);

            if (!is_array($json)) {
                throw new EntityNotFoundException(elgg_echo('BadResponseFromServer'));
            }
        } catch (ConnectException $e) {
            throw new EntityNotFoundException("Could not connect to $uri");
        } catch (ClientException | ServerException $e) {
            $code = $e->getCode();

            switch ($code) {
                case 404:
                    throw new PageNotFoundException("Could not find remote content: $uri");
                    break;
                case 403:
                    throw new ForbiddenException();
                    break;
                case 410:
                    throw new RemoteGoneException();
                    break;
                case 429:
                    throw new RemoteRateLimitedException();
                    break;
                default:
                    throw new HttpException("Unable to fetch $uri. " . $e->getMessage(), $code);
            }
        }

        return $this->fromJson($json);
    }

    /**
     * @param ElggEntity $entity
     * @return ObjectType
     * @throws PageNotFoundException
     * @throws NotImplementedException
     * @throws EntityNotFoundException
     * @throws \Elgg\Exceptions\HttpException
     */
    public function fromEntity(\ElggObject|FederatedObject $entity): ObjectType
    {
        if (!$entity) {
            throw new NotImplementedException();
        }

        $owner = $entity->getOwnerEntity();
        if (!$owner instanceof \ElggEntity) {
            throw new NotImplementedException();
        }

        $actorUri = elgg_generate_url("view:activitypub:{$owner->getType()}", [
            'guid' => (int) $owner->guid,
        ]);

        /**
         * If this is a remote entity, then we need to get the remote uri
         */
        if ($entity instanceof FederatedObject && (string) $entity->source === FederatedObjectSourcesEnum::ACTIVITY_PUB) {
            if ($uri = $this->manager->getUriFromEntity($entity)) {
                if (!$this->manager->isLocalUri($uri)) {
                    return $this->fromUri($uri);
                }
            } else {
                throw new PageNotFoundException(elgg_echo('FoundActivityPubEntityButCouldNotResolveRemoteUri'));
            }
        }

        //activity
        $build = [];

        $activity_reference = (int) $entity->activity_reference;
        $activity = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity_reference) {
            return get_entity($activity_reference);
        });

        if ($activity instanceof ActivityPubActivity) {
            $build = $activity->buildActivity();
        }

        // default options
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

        $json = [
            'id' => (string) elgg()->activityPubUtility->getActivityPubID($entity),
            'type' => (string) elgg()->activityPubUtility->getActivityPubObject($entity),
            'name' => (string) $entity->getDisplayName(),
            'content' => $content,
            'attributedTo' => $actorUri,
            'published' => date('c', (int) $entity->time_created),
            'url' => ($entity instanceof \ElggComment) ? elgg_generate_url('view:object:comment', [
                'guid' => (int) $entity->guid
            ]) : (string) $entity->getURL(),
            'to' => (!empty($build['to'])) ? $build['to'] : [],
            'cc' => (!empty($build['cc'])) ? $build['cc'] : [],
            'sensitive' => false,
        ];

        //updated
        $activity_reference = get_entity((int) $entity->activity_reference);
        if ($activity_reference instanceof ActivityPubActivity && !empty((string) $activity_reference->updated)) {
            $json['updated'] = (string) $activity_reference->updated;
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
                    'name' => $file->getDisplayName(),
                    'url' => $file->getDownloadURL(false),
                    'mediaType' => $mimetype,
                ];

                $attachments[] = $attachment;
            }
        }

        $json['attachment'] = $attachments;

        $container = $entity->getContainerEntity();

        //mention
        if (!empty($build['object']['tag'])) {
            $json['tag'] = $build['object']['tag'];
        }

        //audience
        if (!empty($build['object']['audience'])) {
            $json['audience'] = $build['object']['audience'];
        }

        //target
        if (!empty($build['object']['target'])) {
            $json['target'] = $build['object']['target'];
        }

        //reply
        if (!empty($build['object']['inReplyTo'])) {
            $json['inReplyTo'] = $build['object']['inReplyTo'];
        }

        //cover
        if (!empty($build['object']['image'])) {
            $json['image'] = $build['object']['image'];
        }

        //summary
        if (!empty($build['object']['summary'])) {
            $summary = $build['object']['summary'];

            $json['summary'] = trim($summary);

            $json['source'] = [
                'content' => trim($summary),
                'mediaType' => 'text/markdown',
            ];

            $json['_misskey_summary'] = trim($summary);
        }

        // If this is a 'reply', then cc in the owner of who we are replying to
        if ($json['inReplyTo'] ?? null) {
            $replyObject = $this->fromUri($json['inReplyTo']);
            $json['cc'][] = $replyObject->attributedTo;
        }

        return $this->fromJson($json);
    }

    public function fromJson(array $json): ObjectType
    {
        if (isset(ActorFactory::ACTOR_TYPES[$json['type']])) {
            return elgg()->activityPubActorFactory->fromJson($json);
        }

        $object = match ($json['type']) {
            'Article' => new ArticleType(),
            'Event' => new EventType(),
            'Note' => new NoteType(),
            'Page' => new PageType(),
            'Audio' => new AudioType(),
            'Document' => new DocumentType(),
            'Image' => new ImageType(),
            'Video' => new VideoType(),
            default => throw new NotImplementedException(),
        };

        // Must
        if (!isset($json['id'])) {
            throw new \Exception('Required fields are missing');
        }

        $object->id = $json['id'];

        // May
        if (isset($json['name'])) {
            $object->name = $json['name'];
        }

        if (isset($json['content'])) {
            $object->content = $json['content'];
        }

        if (isset($json['summary'])) {
            $object->summary = $json['summary'];
        }

        if (isset($json['attributedTo'])) {
            $object->attributedTo = $json['attributedTo'];
        }

        if (isset($json['published'])) {
            $object->published = $json['published'];
        }

        if (isset($json['updated'])) {
            $object->updated = $json['updated'];
        }

        if (isset($json['url'])) {
            $object->url = $json['url'];
        }

        if (isset($json['to'])) {
            $object->to = $json['to'];
        }

        if (isset($json['cc'])) {
            $object->cc = $json['cc'];
        }

        if (isset($json['sensitive'])) {
            $object->sensitive = $json['sensitive'];
        }

        if (isset($json['attachment'])) {
            $object->attachment = [];

            foreach ($json['attachment'] as $attachment) {
                $object->attachment[] = $attachment;
            }
        }

        //tag
        if (isset($json['tag'])) {
            $object->tag = $json['tag'];
        }

        if (isset($json['audience'])) {
            $object->audience = $json['audience'];
        }

        if (isset($json['target'])) {
            $object->target = $json['target'];
        }

        if (isset($json['inReplyTo'])) {
            $object->inReplyTo = JsonLdHelper::getValueOrId($json['inReplyTo']);
        }

        if (isset($json['source']) && is_array($json['source'])) {
            $object->source = new SourceType();
            $object->source->content = $json['source']['content'];
            $object->source->mediaType = $json['source']['mediaType'];
        }

        if (isset($json['_misskey_summary'])) {
            $object->_misskey_summary = $json['_misskey_summary'];
        }

        if (isset($json['icon']) && is_array($json['icon'])) {
            $icon = new ImageType();
            if (isset($json['icon']['mediaType'])) {
                $icon->mediaType = $json['icon']['mediaType'];
            }
            $icon->url = $json['icon']['url'] ?? '';
            $icon->name = $json['icon']['name'] ?? '';
            $object->icon = $icon;
        }

        if (isset($json['image']) && is_array($json['image'])) {
            $image = new ImageType();
            if (isset($json['image']['mediaType'])) {
                $image->mediaType = $json['image']['mediaType'];
            }
            $image->url = $json['image']['url'] ?? '';
            $image->name = $json['image']['name'] ?? '';
            $object->image = $image;
        }

        if (isset($json['mediaType'])) {
            $object->mediaType = $json['mediaType'];
        }

        if (isset($json['width'])) {
            $object->width = $json['width'];
        }

        if (isset($json['height'])) {
            $object->height = $json['height'];
        }

        if (isset($json['quoteUri'])) {
            $object->quoteUri = $json['quoteUri'];
        }

        return $object;
    }

    private function buildTag(string $content): array
    {
        $tag = [];

        // Is this a mention?
        if ($mentions = ContentParserBuilder::getMentions($content)) {
            foreach ($mentions as $mention) {
                $uri = $this->manager->getUriFromUsername($mention);

                if (!$uri) {
                    continue;
                }

                // Add all users to tge tags list
                $tag[] = [
                    'type' => 'Mention',
                    'href' => $uri,
                    'name' => $mention,
                ];
            }
        }

        return $tag;
    }
}
