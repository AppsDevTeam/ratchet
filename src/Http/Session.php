<?php

namespace ADT\Ratchet\Http;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

class Session extends \Nette\Http\Session {
	
	protected $sessionBag;
	
	public function __construct(SessionBagInterface $sessionBag) {
		$this->sessionBag = $sessionBag;
	}
	
	public function start() {
	}
	
	public function exists() {
		// TODO
		return TRUE;
	}
	
	public function getSection($section, $class = 'ADT\Ratchet\Http\SessionSection') {
		return new $class($this, $section);
	}
	
	public function getSessionBag() {
		return $this->sessionBag;
	}
	
}

