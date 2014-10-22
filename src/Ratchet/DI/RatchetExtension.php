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

	const CONTROL_TAG = 'ratchet.control';

	/**
	 * @var array
	 */
	private $defaults = array(
		"server" 	=> "0.0.0.0",
		"port"		=> 8080,
		"router"	=> array(
			"namespace"	=> "App",
			"control"	=> "Default",
			"handle"	=> "default"
		)
	);


	/**
	 * Load configuration
	 */
	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('router'))
			->setClass('ADT\Ratchet\Router\SimpleRouter', array($config["router"]["namespace"],
				$config["router"]["control"], $config["router"]["handle"]));

		$builder->addDefinition($this->prefix('connectionStorage'))
			->setClass('ADT\Ratchet\ConnectionStorage');

		$loop = $builder->addDefinition($this->prefix('loop'))
			->setClass('React\EventLoop\LoopInterface')
			->setFactory('React\EventLoop\Factory::create');

		$application = $builder->addDefinition($this->prefix('application'))
			->setClass('ADT\Ratchet\Application');

		$builder->addDefinition($this->prefix('server'))
			->setClass('ADT\Ratchet\Server', array($application, $loop, $config['server'], $config['port']));


	}
	
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		
		$application = $builder->getDefinition($this->prefix('application'));
		foreach ($builder->findByTag(self::CONTROL_TAG) as $controlId => $meta) {
			$application->addSetup('addControl', array('@' . $controlId));
		}
	}

}