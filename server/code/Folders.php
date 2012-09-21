<?php

class Folders {
	static public $root;
	static public $files;
	static public $db;
}

Folders::$root = __DIR__ . '/..';
Folders::$files = __DIR__ . '/../files';
Folders::$db = __DIR__ . '/../db';
