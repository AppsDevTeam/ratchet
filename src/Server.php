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
	 * @var string
	 */
	protected $httpHost;


	/**
	 * @var int
	 */
	protected $port;


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
	public function __construct(/*ControllerApplication $application, */LoopInterface $loop, $httpHost, $port)
	{
		//$this->application = $application;
		$this->httpHost = $httpHost;
		$this->port = $port;
		$this->loop = $loop;
		$this->routes  = new RouteCollection;
	}
	
	public function route($path, $controller/*, array $allowedOrigins = array()*/, $httpHost = null, $identifier = NULL) {
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
					$httpHost = $this->httpHost;
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
			
			p('-- SERVER');
			p($path);
			p(get_class($controller));
			p($identifier);
			
			$this->routes->add('rr-' . ++$this->_routeCounter, new Route($path, array('_controller' => $controller, '_identifier' => $identifier), array('Origin' => $this->httpHost), array()/*, $httpHost*/));

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
		
		$router = new Router(new UrlMatcher($this->routes, new RequestContext));
		$httpServer = new \Ratchet\Http\HttpServer($router);
		
		$socket = new \React\Socket\Server($this->loop);
		$socket->listen($this->port, $this->httpHost);
		
		$server = new \Ratchet\Server\IoServer($httpServer, $socket, $this->loop);
		$server->run();
	}


}