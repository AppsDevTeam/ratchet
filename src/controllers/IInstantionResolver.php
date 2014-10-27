<?php

namespace ADT\Ratchet\Controllers;

interface IInstantionResolver {
	
	public function getInstantionIdentifier(ConnectionInterface $conn, RequestInterface $request);
	
}
