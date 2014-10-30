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

		$instantionId = $this->getInstantionId($route, $request, $conn);
		$conn->controller = $this->getController($route, $instantionId);
		
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
		
		$conn->controller->onOpen($conn, $request);
	}
	
	protected function getInstantionId($route, $request, $conn) {
		return $route['_instantionResolver']->getInstantionIdentifier($request, $conn);
	}
	
	/**
	 * Get controller instantion or create new one and decorate it.
	 * @param array $route
	 * @param string|int $instantionId
	 * @return HttpServerInterface
	 * @throws \UnexpectedValueException
	 */
	protected function getController($route, $instantionId) {
		
		$controller = $route['_controller'];
		$routeName = $route['_name'];
		
		if (! isset($this->controllers[$routeName][$instantionId])) {
			if (! ($controller instanceof \Ratchet\ComponentInterface)) {
				// Vytvoř nový controller pomocí továrny
				$controller = $controller->create();
			}

			// decoration
			
			$decorated = $controller;

			if ($decorated instanceof WampServerInterface) {
				$decorated = new WampServer($decorated);
			}

			if (in_array('sessionProvider', $route['_wrapped'])) {
				$decorated = $this->createSessionProvider($decorated, $this->config['sessionProvider']);
			}

			if ($decorated instanceof \Ratchet\WebSocket\WsServerInterface) {
				$decorated = new WsServer($decorated);
			}

			if (!($decorated instanceof HttpServerInterface)) {
				throw new \UnexpectedValueException('All routes must implement Ratchet\Http\HttpServerInterface');
			}
		
			$this->controllers[$routeName][$instantionId] = $decorated;
		}
		
		return $this->controllers[$routeName][$instantionId];
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