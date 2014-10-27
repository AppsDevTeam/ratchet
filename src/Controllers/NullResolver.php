<?php

namespace ADT\Ratchet\Controllers;

use \Ratchet\ConnectionInterface;
use \Guzzle\Http\Message\RequestInterface;

class NullResolver implements IInstantionResolver {
	
	public function getInstantionIdentifier(RequestInterface $request = NULL, ConnectionInterface $conn = NULL) {
		return '_';
	}
	
}
