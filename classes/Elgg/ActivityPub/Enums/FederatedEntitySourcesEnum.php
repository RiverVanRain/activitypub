<?php
namespace Elgg\ActivityPub\Enums;

enum FederatedEntitySourcesEnum: string {
    case LOCAL = 'local';
    case ACTIVITY_PUB = 'activitypub';
    case NOSTR = 'nostr';
}
