<?php
namespace Elgg\ActivityPub\Exceptions;

use Elgg\Exceptions\HttpException;

class NotImplementedException extends HttpException {
	/**
	 * {@inheritdoc}
	 */
	public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		if (!$message) {
			$message = elgg_echo('The server is unable to process your request');
		}
		
		if (!$code) {
			$code = 501;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
