<?php

namespace ADT\Ratchet\Components;

use Ratchet\ConnectionInterface;
use Guzzle\Http\Url;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;

class Router extends \Ratchet\Http\Router {

	protected $controllers;
	
	/**
	 * {@inheritdoc}
	 * @throws \UnexpectedValueException If a controller is not \Ratchet\Http\HttpServerInterface
	 */
	public function onOpen(ConnectionInterface $conn, RequestInterface $request = null) {
		if (null === $request) {
			throw new \UnexpectedValueException('$request can not be null');
		}

		$context = $this->_matcher->getContext();
		$context->setMethod($request->getMethod());
		$context->setHost($request->getHost());

		try {
			p('-- ROUTER');
			p($request->getPath());
			$route = $this->_matcher->match($request->getPath());
		} catch (MethodNotAllowedException $nae) {
			p('nae');
			return $this->close($conn, 403);
		} catch (ResourceNotFoundException $nfe) {
			p('nfe');
			return $this->close($conn, 404);
		}

		$instantionResolver = $route['_instantionResolver'];	// TODO: toto nebdue proměnná, ale callback, který dostane parametry routy
		$instantionResolver->getInstantionIdentifier($conn, $request);
		if (! isset($this->controllers[$instantionResolver])) {
			// Vytvoř nový controller pomocí továrny
			$this->controllers[$instantionResolver] = $route['_controller']->create();
		}
		$controller = $this->controllers[$instantionResolver];
		
		if ($controller instanceof HttpServerInterface || $controller instanceof WsServer) {
				$decorated = $controller;
		} elseif ($controller instanceof WampServerInterface) {
				$decorated = new WsServer(new WampServer($controller));
		} elseif ($controller instanceof MessageComponentInterface) {
				$decorated = new WsServer($controller);
		} else {
				$decorated = $controller;
		}

		if (!($decorated instanceof HttpServerInterface)) {
			throw new \UnexpectedValueException('All routes must implement Ratchet\Http\HttpServerInterface');
		}
		
		$parameters = array();
		foreach($route as $key => $value) {
			if ((is_string($key)) && ('_' !== substr($key, 0, 1))) {
				$parameters[$key] = $value;
			}
		}
		$url = Url::factory($request->getPath());
		$url->setQuery($parameters);
		$request->setUrl($url);

		$conn->controller = $decorated;
		$conn->controller->onOpen($conn, $request);
	}

}