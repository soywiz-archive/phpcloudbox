<?php

class Core {
	static public function __autoload($class) {
		require(__DIR__ . '/' . basename($class) . '.php');
	}
}

spl_autoload_register(array('Core', '__autoload'));