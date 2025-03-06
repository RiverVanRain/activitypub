<?php
namespace Elgg\ActivityPub\Exceptions;

use Elgg\Exceptions\HttpException;

class RemoteConnectException extends HttpException {

	/**
	 * {@inheritdoc}
	 */
	public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		if (!$message) {
			$message = elgg_echo('Unable to connect to the remote');
		}
		
		if (!$code) {
			$code = ELGG_HTTP_BAD_REQUEST;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
