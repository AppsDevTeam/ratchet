<?php

namespace ADT\Ratchet\Components;

use Ratchet\ConnectionInterface;
use Guzzle\Http\Url;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;

class Router extends \Ratchet\Http\Router {

	protected $controllers;
	
	protected $config;
	
	public function __construct(UrlMatcherInterface $matcher, $config) {
		parent::__construct($matcher);
		
		$this->config = $config;
	}
	
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
		
		// route
		
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
		p('found');

		// get controller instantion or create new one
		
		if ($route['_instantionResolver'] === NULL) {
			$instantionId = '_';
		} else {
			$instantionId = $route['_instantionResolver']->getInstantionIdentifier($request, $conn);
		}
		if (! isset($this->controllers[$instantionId])) {
			if ($route['_controller'] instanceof \Ratchet\ComponentInterface) {
				$this->controllers[$instantionId] = $route['_controller'];
			} else {
				// Vytvoř nový controller pomocí továrny
				$this->controllers[$instantionId] = $route['_controller']->create();
			}
		}
		$controller = $this->controllers[$instantionId];
		
		// decoration
		
		if ($controller instanceof WampServerInterface) {
			$controller = new WampServer($controller);
		}
		
		if (in_array('sessionProvider', $route['_wrapped'])) {
			$controller = $this->createSessionProvider($controller, $this->config['sessionProvider']);
		}
		
		if ($controller instanceof \Ratchet\WebSocket\WsServerInterface) {
			$controller = new WsServer($controller);
		}

		if (!($controller instanceof HttpServerInterface)) {
			throw new \UnexpectedValueException('All routes must implement Ratchet\Http\HttpServerInterface');
		}
		
		// parameters
		
		$parameters = array();
		foreach($route as $key => $value) {
			if ((is_string($key)) && ('_' !== substr($key, 0, 1))) {
				$parameters[$key] = $value;
			}
		}
		$url = Url::factory($request->getPath());
		$url->setQuery($parameters);
		$request->setUrl($url);
		
		$conn->controller = $controller;
		$conn->controller->onOpen($conn, $request);
	}
	
	protected static function createSessionProvider($controller, $config) {
		
		switch ($config['handler']) {
			case 'memcached':

				$memcache = new \Memcached;
				$memcache->addServer($config['httpHost'], $config['port']);
				return new \Ratchet\Session\SessionProvider(
					$controller,
					new Handler\MemcachedSessionHandler(
						$memcache,
						array(
							'prefix' => ini_get('memcached.sess_prefix'),
						)
					)
				);
				break;

			default:
				throw new \ADT\Ratchet\Exception("TODO: Session handler '$config[handler]' nebyl implementován");
				break;
		}
		
	}

}