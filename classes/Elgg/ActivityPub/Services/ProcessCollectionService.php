<?php

namespace Elgg\ActivityPub\Services;

use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Elgg\ActivityPub\Factories\ActivityFactory;
use Elgg\ActivityPub\Types\Actor\AbstractActorType;
use Elgg\Traits\Di\ServiceFacade;

// WIP
class ProcessCollectionService {
	use ServiceFacade;

	protected array $json;
    protected AbstractActorType|string $actor;

    public function __construct(
        protected ActivityFactory        $activityFactory,
    ) {

    }

    public function withJson(array $json): ProcessCollectionService {
        $instance = clone $this;
        $instance->json = $json;
        return $instance;
    }

    public function withActor(AbstractActorType|string $actor): ProcessCollectionService {
        $instance = clone $this;
        $instance->actor = $actor;
        return $instance;
    }

    public function process(): void {
        if (!isset($this->json)) {
           throw new \Elgg\Exceptions\Http\BadRequestException(elgg_echo('activitypub:inbox:general:payload:empty', ['']));
        }

        if (!JsonLdHelper::isSupportedContext($this->json)) {
            return;
        }

        if ($this->isActorDifferent()) {
            return;
        }

        if ($this->isActorBanned()) {
            return;
        }

        if ($this->isActorLocal()) {
            return;
        }

        switch ($this->json['type']) {
            case 'Collection':
            case 'CollectionPage':
                $this->processItems($this->json['items']);
                break;
            case 'OrderedCollection':
            case 'OrderedCollectionPage':
                $this->processItems($this->json['orderedItems']);
                break;
            default:
                $this->processItems([$this->json]);
        }
    }

    private function processItems(array $items): void
    {
        $items = array_reverse($items);
        foreach ($items as $item) {
            $this->processItem($item);
        }
    }

    private function processItem(array $item): void {
        if (!is_array($item)) {
            elgg_log(elgg_echo('activitypub:process:collection:error'), \Psr\Log\LogLevel::ERROR);
            return;
        }
        $apActivity = $this->activityFactory->fromJson($item, $this->actor);

        $this->processActivityService
            ->withActivity($apActivity)
            ->process();
    }

    private function isActorDifferent(): bool {
        return isset($this->actor) && JsonLdHelper::getValueOrId($this->actor) !== JsonLdHelper::getValueOrId($this->actor);
    }

    private function isActorBanned(): bool {
        return (bool) elgg()->activityPubUtility->domainIsGlobalBlocked($this->actor);
    }

    private function isActorLocal(): bool {
        return (bool) elgg()->activityPubManager->isLocalUri($this->actor);
    }
	
	/**
	 * Returns registered service name
	 * @return string
	 */
	public static function name() {
		return 'activityPubProcessCollection';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}
}
