<?php

namespace ADT\Ratchet\DI;

use Nette\DI\CompilerExtension;

class RatchetExtension extends CompilerExtension {

	/**
	 * @var array
	 */
	protected static $defaults = array(
		'httpHost' => '0.0.0.0',
		'port' => 8080,
		'sessionProvider' => array(
			'handler' => 'memcached',
			'httpHost' => 'localhost',
			'port' => 11211,
		),
	);
	
	protected static $routeDefaults = array(
		'httpHost' => '',
		'instantionResolver' => '@ADT\Ratchet\Controllers\NullResolver',
		'wrapped' => array(),
	);
	
	protected $config;


	/**
	 * Load configuration
	 */
	public function loadConfiguration()
	{
		$this->config = $this->getConfig(self::$defaults);

		$builder = $this->getContainerBuilder();
		
		$loop = $builder->addDefinition($this->prefix('loop'))
			->setClass('React\EventLoop\LoopInterface')
			->setFactory('React\EventLoop\Factory::create');

		$builder->addDefinition($this->prefix('server'))
			->setClass('ADT\Ratchet\Server', array($loop, $this->config));

		$builder->addDefinition($this->prefix('nullResolver'))
			->setClass('ADT\Ratchet\Controllers\NullResolver');


	}
	
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		
		$server = $builder->getDefinition($this->prefix('server'));
		
		if (isset($this->config['routes'])) {
			foreach ($this->config['routes'] as $path => $route) {
				
				if (! is_array($route)) {
					$route = array(
						'controller' => $route,
					);
				}
				
				$route['path'] = $path;
				$route = \Nette\DI\Config\Helpers::merge($route, self::$routeDefaults);
				
				$server->addSetup('route', array(
					$route['path'],
					$route['controller'],
					$route['instantionResolver'],
					$route['httpHost'],
					$route['wrapped'],
				));
			}
		}
		
	}

}