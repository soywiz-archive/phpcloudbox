<?php

class Sha1File {
	public $sha1;
	
	public function __construct($sha1) {
		//$sha1 = strtolower(trim($sha1));
		if (!preg_match('@^[0-9a-f]{40}$@', $sha1)) throw(new Exception("Invalid sha1"));
		$this->sha1 = basename($sha1);
	}
	
	public function serveFile($name) {
		header_remove('X-Powered-By');
		
		// Cache:
		header_remove('pragma');
		header_remove('expires');
		header(sprintf('Cache-Control: public'));
		
		header(sprintf('Content-Type: %s', $this->getContentType()));
		//header(sprintf('Content-Disposition: attachment; filename="%s"', basename($name)));
		header(sprintf('Content-Disposition: filename="%s"', basename($name)));
		
		switch ($this->getXSendFileType()) {
			case 'mod_xsendfile':
				header(sprintf('X-SendFile: %s', $this->local_file_path));
				exit;
			case 'nginx_xaccel':
				header(sprintf('X-Accel-Redirect: %s', $this->local_file_path));
				exit;
			default:
				readfile($this->getLocalPath());
				exit;
		}
	}
	
	public function getContentType() {
		if (function_exists('finfo_file')) {
			$contentType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->getLocalPath());
		} else {
			$contentType = false;
		}
		if (empty($contentType)) $contentType = 'application/octet-stream';
		return $contentType;
	}
	
	protected function getXSendFileType() {
		//return false;
	
		// Apache
		if (function_exists('apache_get_modules')) {
			$apache_get_modules = 'apache_get_modules';
			if (in_array('mod_xsendfile', $apache_get_modules())) return 'mod_xsendfile';
			return false;
		}
	
		// ngin-x
		if (preg_match('@^nginx@Umsi', @$_SERVER['SERVER_SOFTWARE'])) {
			return 'nginx_xaccel';
		}
	
		// Other
		return false;
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