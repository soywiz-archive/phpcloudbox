<?php

class Db {
	/**
	 * 
	 * @var PDO
	 */
	public $pdo;
	
	public function __construct($name) {
		$this->pdo = new PDO('sqlite:' . Folders::$db . '/' . basename($name) . '.sqlite');
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	public function get($name) {
		return new DbTable($this, $name);
	}
}

class DbTable {
	/**
	 * 
	 * @var Db
	 */
	public $db;
	
	/**
	 * 
	 * @var String
	 */
	public $tableName;
	
	/**
	 * 
	 * @param Db $db
	 * @param String $tableName
	 */
	public function __construct(Db $db, $tableName) {
		$this->db = $db;
		$this->tableName = $tableName;
	}
	
	public function defineColumns($columns) {
		$pdo = $this->db->pdo;
		
		$pdo->exec(sprintf('CREATE TABLE IF NOT EXISTS %s (%s);', $this->tableName, implode(', ', $columns)));
	}
	
	public function ensureIndex($columns, $unique) {		
		$index_name = implode('_', array_keys($columns));
		$columnsDef = array();
		//$columns[]
		foreach ($columns as $column => $dir) {
			$columnsDef[] = $column . ' ' . (($dir > 0) ? 'ASC' : 'DESC');
		}
		
		$indexType = $unique ? 'UNIQUE INDEX' : 'INDEX';
		if ($unique) {
			$index_name = 'unique_' . $index_name;
		}
		
		$sql = sprintf('CREATE %s IF NOT EXISTS %s ON %s (%s);', $indexType, $index_name, $this->tableName, implode(', ', $columnsDef));
		//printf("%s\n", $sql);
		return $this->execute($sql);
	}
	
	public function execute($sql, $values2 = array()) {
		$pdo = $this->db->pdo;
		
		$statement = $pdo->prepare($sql);
		$statement->execute($values2);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insert($object) {
		$names = array();
		$valuesQuestion = array();
		$values = array();
		
		foreach ($object as $key => $value) {
			$names[] = '"' . $key . '"';
			$valuesQuestion[] = '?';
			$values[] = $value;
			//$values[] = $pdo->quote($value, PDO::PARAM_STR);
		}
		
		$sql = sprintf(
			'INSERT OR IGNORE INTO %s (%s) VALUES (%s);',
			$this->tableName,
			implode(', ', $names),
			implode(', ', $valuesQuestion)
		);
		
		return $this->execute($sql, $values);
	}
	
	protected function getWhereLimit($query, &$values, $limit = NULL) {
		$conds = array();
		$values = array();
		foreach ($query as $key => $value) {
			if (is_array($value)) {
				$operators = array(
					'$lt' => '<',
					'$gt' => '>',
					'$le' => '<=',
					'$ge' => '>=',
				);
				foreach ($operators as $operatorKey => $operator) {
					$operatorValue = &$value[$operatorKey]; 
					if (isset($operatorValue)) {
						$conds[] = $key . $operator . '?';
						$values[] = $operatorValue;
					}
				}
			} else {
				$conds[] = $key . '=?';
				$values[] = $value;
			}
		}
		if (count($values) == 0) $conds[] = '1=1';
		$sql = sprintf('WHERE %s', implode(' AND ', $conds));
		if ($limit !== NULL) $sql .= ' LIMIT ' . (int)$limit;
		return $sql;
	}

	public function find($query, $limit = NULL) {
		$values = array();
		$sql = sprintf('SELECT rowid, * FROM %s %s;', $this->tableName, $this->getWhereLimit($query, $values, $limit));
		//echo $sql;
		return $this->execute($sql, $values);
	}
	
	public function remove($query, $limit = NULL) {
		$values = array();
		$sql = sprintf('DELETE FROM %s %s;', $this->tableName, $this->getWhereLimit($query, $values, $limit));
		return $this->execute($sql, $values);
	}
	
	public function removeOne($query) {
		$this->remove($query, 1);
	}
	
	public function findOne($query) {
		foreach ($this->find($query, 1) as $row) return $row;
		return NULL;
	}
}