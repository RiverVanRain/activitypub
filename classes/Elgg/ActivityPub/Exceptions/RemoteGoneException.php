<?php

namespace Elgg\ActivityPub\Exceptions;

use Elgg\Exceptions\HttpException;

class RemoteGoneException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        if (!$message) {
            $message = elgg_echo('The remote content is gone and can not be fetched');
        }

        if (!$code) {
            $code = 410;
        }

        parent::__construct($message, $code, $previous);
    }
}
