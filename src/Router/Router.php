<?php

namespace ADT\Ratchet;

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
			$route = $this->_matcher->match($request->getPath());
		} catch (MethodNotAllowedException $nae) {
			return $this->close($conn, 403);
		} catch (ResourceNotFoundException $nfe) {
			return $this->close($conn, 404);
		}

		$identifier = $route['_identifier'];	// TODO: toto nebdue proměnná, ale callback, který dostane parametry routy
		if (! isset($this->controllers[$identifier])) {
			// Vytvoř nový controller pomocí továrny
			$this->controllers[$identifier] = $route['_controller']->create();
		}

		if (!($this->controllers[$identifier] instanceof HttpServerInterface)) {
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

		$conn->controller = $this->controllers[$identifier];
		$conn->controller->onOpen($conn, $request);
	}

}