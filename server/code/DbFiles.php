<?php

class DbFiles {
	/**
	 * 
	 * @var Db
	 */
	protected $db;
	
	/**
	 * 
	 * @var DbTable
	 */
	protected $files;
	
	public function __construct() {
		$this->db = new Db('files');
		$this->files = $this->db->get('files');
	}
	
	public function setup() {
		$this->files->defineColumns(array('sha1'));
		$this->files->ensureIndex(array('sha1' => +1), true);
	}
	
	public function addFile($sha1) {
		$this->files->insert(array('sha1' => $sha1));
	}
}