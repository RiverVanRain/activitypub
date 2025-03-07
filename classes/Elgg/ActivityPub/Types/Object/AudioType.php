<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;

class AudioType extends DocumentType
{
    #[ExportProperty]
    protected string $type = 'Audio';
}
