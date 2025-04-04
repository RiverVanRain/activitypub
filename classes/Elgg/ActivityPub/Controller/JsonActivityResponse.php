<?php

namespace Elgg\ActivityPub\Controller;

use Laminas\Diactoros\Response\InjectContentTypeTrait;
use Laminas\Diactoros\Response\JsonResponse;

class JsonActivityResponse extends JsonResponse
{
    use InjectContentTypeTrait;

    public function __construct(
        $data,
        $status = 200,
        array $headers = [],
        $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $headers = $this->injectContentType('application/activity+json', $headers);

        parent::__construct($data, $status, $headers, $encodingOptions);
    }
}
