<?php

namespace ADT\Ratchet\DI;

use Nette\Config\CompilerExtension;

/**
 *
 * Install ratchet server and other components to container.
 *
 * @copyright Copyright (c) 2013 Ledvinka Vít
 * @author Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class RatchetExtension extends CompilerExtension {

	const CONTROLLER_TAG = 'ratchet.controller';

	/**
	 * @var array
	 */
	private $defaults = array(
		"httpHost" 	=> "0.0.0.0",
		"port"		=> 8080,
	);


	/**
	 * Load configuration
	 */
	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);

		$builder = $this->getContainerBuilder();
		
		$loop = $builder->addDefinition($this->prefix('loop'))
			->setClass('React\EventLoop\LoopInterface')
			->setFactory('React\EventLoop\Factory::create');

		$builder->addDefinition($this->prefix('server'))
			->setClass('ADT\Ratchet\Server', array(/*$application, */$loop, $config['httpHost'], $config['port']));


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
		
		// TODO: přesunout do neonu a brát z neonu
		$server->addSetup('route', array('/lock', '@\App\RatchetModule\Controllers\ILockControllerFactory', NULL, 'demo'));
	}

}