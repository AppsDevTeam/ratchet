<?php

namespace ADT\Ratchet;

use ADT\Ratchet\Components\Router;
use Nette\ComponentModel\Container;
use React\EventLoop\LoopInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/**
 * Ratchet server for Nette - run instead of Nette application.
 *
 * @copyright Copyright (c) 2013 Ledvinka Vít
 * @author Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class Server extends \Nette\Object {


	/**
	 * @var array
	 */
	protected $config;


	/**
	 * @var ControllerApplication
	 */
	protected $application;


	/**
	 * @var LoopInterface
	 */
	protected $loop;


	/**
	 * @var RouteCollection
	 */
	protected $routes;

	/**
	 * @var int
	 */
	protected $_routeCounter = 0;
	

	/**
	 * @param ControllerApplication $application
	 * @param LoopInterface $loop
	 * @param string $httpHost The address to receive sockets on (0.0.0.0 means receive connections from any)
	 * @param int $port The port to server sockets on
	 */
	public function __construct(LoopInterface $loop, $config)
	{
		//$this->application = $application;
		$this->config = $config;
		$this->loop = $loop;
		$this->routes  = new RouteCollection;
	}
	
	public function route($path, $controller/*, array $allowedOrigins = array()*/, Controllers\IInstantionResolver $instantionResolver, $httpHost = '', $wrapped = array()) {
			/*
			if ($controller instanceof HttpServerInterface || $controller instanceof WsServer) {
					$decorated = $controller;
			} elseif ($controller instanceof WampServerInterface) {
					$decorated = new WsServer(new WampServer($controller));
			} elseif ($controller instanceof MessageComponentInterface) {
					$decorated = new WsServer($controller);
			} else {
					$decorated = $controller;
			}
			*/
			
			if ($httpHost === null) {
					$httpHost = $this->config['httpHost'];
			}

			/*
			$allowedOrigins = array_values($allowedOrigins);
			if (0 === count($allowedOrigins)) {
					$allowedOrigins[] = $httpHost;
			}
			if ('*' !== $allowedOrigins[0]) {
					$decorated = new OriginCheck($decorated, $allowedOrigins);
			}
			*/
			
			p('-- route');
			p($path);
			
			$routeName = 'rr-' . ++$this->_routeCounter;
			
			$route = new Route(
				$path,
				array(
					'_controller' => $controller,
					'_instantionResolver' => $instantionResolver,
					'_wrapped' => $wrapped,
					'_name' => $routeName,
				),
				array(
					'Origin' => $this->config['httpHost']
				),
				array(
				),
				$httpHost
			);
			$this->routes->add($routeName, $route);

			return $controller;
	}


	/**
	 * Run IO server
	 */
	public function run()
	{
		/*
		$memcache = new \Memcached;
		$memcache->addServer('localhost', 11211);

		$sessionProvider = new Ratchet\Session\SessionProvider(
			$this->application, 
			new Handler\MemcachedSessionHandler(
				$memcache,
				array(
					'prefix' => ini_get('memcached.sess_prefix'),
				)
			)
		);
		$wsServer = new \Ratchet\WebSocket\WsServer($sessionProvider);
		*/
		
		$router = new Router(new UrlMatcher($this->routes, new RequestContext), $this->config);
		$httpServer = new \Ratchet\Http\HttpServer($router);
		
		$socket = new \React\Socket\Server($this->loop);
		$socket->listen($this->config['port'], $this->config['httpHost']);
		
		$server = new \Ratchet\Server\IoServer($httpServer, $socket, $this->loop);
		$server->run();
	}


}