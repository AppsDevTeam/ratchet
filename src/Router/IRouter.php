<?php

namespace ADT\Ratchet\Router;

use ADT\Ratchet\Request;

/**
 *
 * Interface for routers.
 *
 * @copyright Copyright (c) 2013 Ledvinka Vít
 * @author Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
interface IRouter {


	/**
	 * Convert incoming message to the request, if not match return NULL
	 * @param string $message
	 * @return Request|NULL
	 */
	public function match($message);

}