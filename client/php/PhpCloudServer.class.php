<?php

class PhpCloudServer {
	protected $url, $username, $password;

	public function __construct($url, $username, $password) {
		$this->url = $url;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function getAllFiles() {
		return $this->requestJson('tree.get');
	}
	
	protected function getUrl($action, $params = array()) {
		return $this->url . '/?' . http_build_query(array('action' => $action) + $params);
	}
	
	static protected function normalizePath($path) {
		$path = str_replace('\\', '/', $path);
		$components = array();
		foreach (explode('/', $path) as $component) {
			switch ($component) {
				case '.': break;
				case '..': @array_pop($components); break;
				case '': if (count($components) == 0) $components[] = ''; break;
				default: $components[] = $component; break;
			}
		}
		return implode('/', $components);
	}
	
	public function downloadFile($remoteFile, $localPath, $localFile = NULL) {
		if ($localFile === NULL) $localFile = $remoteFile; 
		
		$localPath = static::normalizePath($localPath) . '/';
		$realLocalFile = static::normalizePath($localPath . '/' . $localFile);
		
		if (strpos($realLocalFile, $localPath) !== 0) throw(new Exception("Invalid path"));
		
		$realLocalPath = dirname($realLocalFile);
		
		if (!is_dir($realLocalPath)) {
			mkdir($realLocalPath, 0777, true);
		}
		
		if (!is_file($realLocalFile)) {
			copy($this->getUrl('tree.file.get', array('path' => $remoteFile)), $realLocalFile, $this->getContext());
		}
	}
	
	protected function getContext() {
		return stream_context_create(array(
			'http' => array(
				'header'  => "Authorization: Basic " . base64_encode("{$this->username}:{$this->password}")
			)
		));
	}
	
	public function request($action, $params = array()) {
		return file_get_contents($this->getUrl($action, $params), false, $this->getContext());
	}
	
	public function requestJson($action, $params = array()) {
		$info = json_decode($this->request($action, $params));
		if ($info->result != 'ok') throw(new Exception("Error {$info->result} : {$info->data}"));
		return $info->data;
	}
}
