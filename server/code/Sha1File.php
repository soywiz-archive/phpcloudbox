<?php

class Sha1File {
	public $sha1;
	
	public function __construct($sha1) {
		//$sha1 = strtolower(trim($sha1));
		if (!preg_match('@^[0-9a-f]{40}$@', $sha1)) throw(new Exception("Invalid sha1"));
		$this->sha1 = basename($sha1);
	}
	
	public function exists() {
		return file_exists($this->getLocalPath());
	}
	
	public function getLocalPath() {
		$sha1 = $this->sha1;
		$p1 = substr($sha1, 0, 2);
		$p2 = substr($sha1, 0, 4);
		return sprintf('%s/%s/%s/%s', Folders::$files, $p1, $p2, $sha1);
	}
	
	public function createPathIfNotExists() {
		$path = dirname($this->getLocalPath());
		if (!is_dir($path)) mkdir($path, 0777, true);
	}
	
	public function moveUploadedFile($uploadedFile) {
		$this->createPathIfNotExists();
		$outFile = $this->getLocalPath();
		if (!file_exists($outFile)) {
			move_uploaded_file($uploadedFile, $outFile);
		}
	}
}