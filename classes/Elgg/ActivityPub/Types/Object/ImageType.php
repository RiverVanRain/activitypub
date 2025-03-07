<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;

class ImageType extends DocumentType
{
    #[ExportProperty]
    protected string $type = 'Image';
}
