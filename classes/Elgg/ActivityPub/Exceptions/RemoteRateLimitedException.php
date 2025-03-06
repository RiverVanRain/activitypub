<?php
namespace Elgg\ActivityPub\Exceptions;

use Elgg\Exceptions\HttpException;

class RemoteRateLimitedException extends HttpException {
	/**
	 * {@inheritdoc}
	 */
	public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		if (!$message) {
			$message = elgg_echo('The remote server returned a rate limit reached response');
		}
		
		if (!$code) {
			$code = 429;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
