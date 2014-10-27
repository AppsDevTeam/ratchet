<?php

namespace ADT\Ratchet\Controllers;

use \Ratchet\ConnectionInterface;
use \Guzzle\Http\Message\RequestInterface;

interface IInstantionResolver {
	
	/**
	 * 
	 * @param RequestInterface $request
	 * @param ConnectionInterface $conn
	 * @return string|int
	 */
	public function getInstantionIdentifier(RequestInterface $request, ConnectionInterface $conn);
	
}
