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
	
	public function uploadAllNonUploadedFiles($folder) {
		$local = $this->getAllFolderFilesRecursively($folder);
		$online = $this->getAllOnlineFiles();
		$pendingList = array_diff($local, $online);
		foreach ($pendingList as $pending) {
			if (is_dir("{$folder}/{$pending}")) {
				
			} else {
				$this->uploadFile($pending, $folder, $pending);
			}
		}
	}
	
	protected function getAllFolderFilesRecursively($folder) {
		$paths = array();
		foreach (
			new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::CHILD_FIRST)
			as $path
		) {
			/* @var $path SplFileInfo */
			$paths[] = trim(str_replace('\\', '/', substr($path->getPathname(), strlen($folder))), '/');
		}
		return $paths;
	}
	
	protected function getAllOnlineFiles() {
		$paths = array();
		foreach ($this->getAllFiles() as $file) $paths[] = $file->path;
		return $paths;
	}
	
	public function downloadAllFilesTo($folder) {
		foreach ($this->getAllFiles() as $file) {
			echo "{$file->path}...";
			$this->downloadFile($file, $folder);
			echo "Ok\n";
		}
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
	
	static protected function combinePath($path1, $path2) {
		$path1 = static::normalizePath($path1) . '/';
		$path2 = static::normalizePath($path1 . '/' . $path2);
		
		if (strpos($path2, $path1) !== 0) throw(new Exception("Invalid path"));
		
		return $path2;
	}

	public function uploadFile($remoteFile, $localPath, $localFile = NULL) {
		if ($localFile === NULL) $localFile = $remoteFile; 
		
		$realLocalFile = static::combinePath($localPath, $localFile);
		
		$sha1 = sha1_file($realLocalFile);
		
		if (!$this->requestJson('file.has', array('sha1' => $sha1))) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_URL, $this->getUrl('file.upload'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'file' => "@{$realLocalFile}",
			));
			$response = $this->parseJson(curl_exec($ch));
			curl_close($ch);
		}
		
		$info = $this->requestJson('tree.add', array(
			'path' => $remoteFile,
			'sha1' => $sha1,
			'ctime' => filectime($realLocalFile),
			'mtime' => filemtime($realLocalFile),
			'perms' => '0777',
		));
	}
	
	public function downloadFile($remoteFileInfo, $localPath, $localFile = NULL) {
		$remoteFile = $remoteFileInfo->path;
		if ($localFile === NULL) $localFile = $remoteFile; 
		
		$realLocalFile = static::combinePath($localPath, $localFile);
		$realLocalPath = dirname($realLocalFile);
		
		if (!is_dir($realLocalPath)) {
			mkdir($realLocalPath, 0777, true);
		}
		
		if (!is_file($realLocalFile)) {
			copy($this->getUrl('tree.file.get', array('path' => $remoteFile)), $realLocalFile, $this->getContext());
			touch($realLocalFile, $remoteFileInfo->mtime);
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
	
	private function parseJson($jsonData) {
		$info = json_decode($jsonData);
		if ($info->result != 'ok') throw(new Exception("Error {$info->result} : {$info->data}"));
		return $info->data;
	}
	
	public function requestJson($action, $params = array()) {
		return $this->parseJson($this->request($action, $params));
	}
}
