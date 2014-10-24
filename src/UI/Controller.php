<?php

namespace ADT\Ratchet\UI;

use ADT\Ratchet\ConnectionStorage;
use ADT\Ratchet\Response\IResponse;

/**
 *
 * @copyright Copyright (c) 2013 Ledvinka Vít
 * @author Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
abstract class Controller extends \Nette\Object {




	/**
	 * @param ConnectionStorage $connection
	 */
	final public function __construct()
	{
	}


	/**
	 * @return ConnectionStorage
	 */
	public function getConnectionStorage()
	{
	}


	/**
	 * This send response
	 * @param IResponse $response
	 */
	public function send(IResponse $response)
	{
		// TODO: nějak pořešit cílovou skupinu
	}


	/**
	 * Startup, is call before call handle
	 */
	public function startup()
	{
	}


	/**
	 * Shutdown, is call after call handle
	 */
	public function shutdown()
	{
	}


}