<?php
namespace Elgg\ActivityPub\Exceptions;

use Elgg\Exceptions\Http\EntityNotFoundException;

class MissingEntityException extends EntityNotFoundException {

	/**
	 * {@inheritdoc}
	 */
	public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		if (!$message) {
			$message = elgg_echo('The entity could not be found on this app.');
		}
		
		if (!$code) {
			$code = 410;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
