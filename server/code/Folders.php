<?php

class Folders {
	static public $root;
	static public $files;
	static public $db;
}

Folders::$root = dirname(__DIR__);
Folders::$files = Folders::$root . '/files';
Folders::$db = Folders::$root . '/db';
