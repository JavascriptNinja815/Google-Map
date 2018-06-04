<?php

namespace PM\DB;

class DBIterator implements \Iterator {
	private $db;
	private $query;
	private $where_clause = [];

	private $position = 0;
	//private $rowcount;
	private $results;
	private $next_result;

	public function __construct($query) {
		$this->_setQuery($query);
	}
	
	protected function getDB() {
		if(empty($this->db)) {
			$this->db = \PM\DB\SQL::connection();
		}
		return $this->db;
	}

	public function rewind() {
		$this->_executeQuery(); // Executes query, storing results in $this->results

		//$this->rowcount = $this->results->rowCount();
		$this->position = 0;
	}

	public function current() {
		$data = $this->next_result;
		$data_deduped = [];
		foreach($data as $key => $value) {
			$key = (string)$key;
			if(ctype_digit($key)) {
				continue;
			}
			$data_deduped[$key] = $value;
		}
		return $data_deduped;
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		$this->position++;
	}

	public function valid() {
		$this->next_result = $this->results->fetch();
		return !empty($this->next_result);
	}

	protected function _addWhereClause($where) {
		if(!in_array($where, $this->where_clause)) {
			$this->where_clause[] = $where;
			return True;
		}
		return False;
	}

	protected function _setWhereClause($where) {
		$this->where_clause = $where;
	}

	protected function _setQuery($query) {
		$this->query = $query;
	}

	private function _executeQuery() {
		$where_clause = implode(' AND ', $this->where_clause);
		if(!empty($where_clause)) {
			$where_clause = ' WHERE ' . $where_clause;
		}
		$query = str_replace('{WHERECLAUSE}', $where_clause, $this->query);
		$this->results = $this->getDB()->query($query);
	}
}
