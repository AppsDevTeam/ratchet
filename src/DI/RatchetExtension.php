<?php

namespace ADT\Ratchet\DI;

use Nette\DI\CompilerExtension;

class RatchetExtension extends CompilerExtension {

	const CONTROLLER_TAG = 'ratchet.controller';

	/**
	 * @var array
	 */
	protected static $defaults = array(
		"httpHost" 	=> "0.0.0.0",
		"port"		=> 8080,
	);
	
	protected static $routeDefaults = array(
		"httpHost" => NULL,
		"instantionResolver" => '@ADT\Ratchet\Controllers\NullResolver',
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
			->setClass('ADT\Ratchet\Server', array(/*$application, */$loop, $this->config['httpHost'], $this->config['port']));

		$builder->addDefinition($this->prefix('nullResolver'))
			->setClass('ADT\Ratchet\Controllers\NullResolver');


	}
	
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		
		/*
		$application = $builder->getDefinition($this->prefix('application'));
		foreach ($builder->findByTag(self::CONTROLLER_TAG) as $controllerId => $meta) {
			$application->addSetup('addController', array('@' . $controllerId));
		}
		*/
		
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
				));
			}
		}
		
	}

}