<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

return [
    'activityPubSignature' => \DI\create(\Elgg\ActivityPub\Services\ActivityPubSignature::class),
    'activityPubClient' => \DI\create(\Elgg\ActivityPub\Services\ActivityPubClient::class)
        ->constructor(\DI\get(\GuzzleHttp\Client::class)),
    'activityPubProcessClient' => \DI\create(\Elgg\ActivityPub\Services\ActivityPubProcessClient::class),
    'activityPubUtility' => \DI\create(\Elgg\ActivityPub\Services\ActivityPubUtility::class),
    'activityPubProcessCollection' => \DI\create(\Elgg\ActivityPub\Services\ProcessCollectionService::class),
    'activityPubReader' => \DI\create(\Elgg\ActivityPub\Services\Reader::class),
    'activityPubMediaCache' => \DI\create(\Elgg\ActivityPub\Services\MediaCache::class),

    'webfingerService' => \DI\create(\Elgg\ActivityPub\WebFinger\WebfingerService::class),

    'activityPubManager' => \DI\autowire(\Elgg\ActivityPub\Manager::class)
        ->constructor(
            \DI\get('webfingerService')
        ),

    'activityPubActivityFactory' => \DI\create(\Elgg\ActivityPub\Factories\ActivityFactory::class)
        ->constructor(
            \DI\get('activityPubManager')
        ),
    'activityPubActorFactory' => \DI\create(\Elgg\ActivityPub\Factories\ActorFactory::class)
        ->constructor(
            \DI\get('activityPubManager')
        ),
    'activityPubLikeFactory' => \DI\create(\Elgg\ActivityPub\Factories\LikeFactory::class)
        ->constructor(
            \DI\get('activityPubManager')
        ),
    'activityPubObjectFactory' => \DI\create(\Elgg\ActivityPub\Factories\ObjectFactory::class)
        ->constructor(
            \DI\get('activityPubManager')
        ),
    'activityPubOutboxFactory' => \DI\create(\Elgg\ActivityPub\Factories\OutboxFactory::class)
        ->constructor(
            \DI\get('activityPubManager')
        ),
];
