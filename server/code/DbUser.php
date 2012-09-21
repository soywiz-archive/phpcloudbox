<?php

class DbUser {
	/**
	 *
	 * @var Db
	 */
	protected $db;
	
	/**
	 *
	 * @var DbTable
	 */
	protected $tree;
	
	/**
	 *
	 * @var DbTable
	 */
	protected $log;
	
	public function __construct($user) {
		$this->db = new Db("user.{$user}");
		$this->tree = $this->db->get('tree');
		$this->log = $this->db->get('log');
	}
	
	public function setup() {
		// Tree
		$this->tree->defineColumns(array('path', 'sha1', 'ctime', 'mtime', 'perms'));
		$this->tree->ensureIndex(array('path' => +1), true);

		// Log
		$this->log->defineColumns(array('action', 'data'));
	}
	
	protected function addLog($action, $data) {
		$this->log->insert(array('action' => $action, 'data' => json_encode($data)));
	}

	public function getFile($path) {
		return $this->tree->findOne(array('path' => $path));
	}
	
	public function getAllFiles() {
		return $this->tree->find(array());
	}
	
	public function addFile($path, $sha1, $ctime, $mtime, $perms) {
		$data = array(
			'path' => $path,
			'sha1' => $sha1,
			'ctime' => $ctime,
			'mtime' => $mtime,
			'perms' => $perms,
		);
		$this->tree->insert($data);
		$this->addLog('add', $data);
	}
	
	public function getLogSince($rowid) {
		return $this->log->find(array('rowid' => array('$ge' => $rowid)));
	}
	
	public function deleteFile($path) {
		$this->tree->remove(array('path' => $path));
		$this->addLog('delete', array('path' => $path));
	}
}