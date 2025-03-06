<?php
namespace Elgg\ActivityPub\Types\Actor;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\AbstractType;

class PublicKeyType extends AbstractType {
    public function __construct(
        #[ExportProperty]
        public string $id,
        #[ExportProperty]
        public string $owner,
        #[ExportProperty]
        public string $publicKeyPem,
    ) {

    }
}
