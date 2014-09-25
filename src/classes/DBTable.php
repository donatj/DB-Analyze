<?php

class DBTable {

	private $columns = array();
	private $name;
	private $info;
	//private $fks;

	function __construct($tablename){
		$this->name = $tablename;
		$this->analyze();
	}

	function analyze() {
		$this->columns = array();
		$columns = conn::fetch('show columns from `'. $this->name .'`');
	
		foreach($columns as $column) {
			$this->columns[ $column['Field'] ] = new DBColumn($this, $column['Field'], $column);
		}

		//$this->info = conn::fetch("SHOW TABLE STATUS like '". $this->name() ."'");
	}

	function find_possible_fks(DBTable $table) {
		foreach($this->columns as $name => $column) {
			//$this->fks[ $name ] = array();
			foreach($table->columns as $tname => $tcolumns) {
				if($name == $tname) {
					$column->add_fk( $tcolumns );
				}
			}
		}
	}

	function fks_type_mismatch() {
		$mismatches = array();
		foreach($this->columns as $name => $col) {
			if($data = $col->fks_type_mismatch()) {
				$mismatches[$name]['mismatches'] = $data;
				$mismatches[$name]['column'] = $col;
			}
		}
		
		return $mismatches;
	}

	function columns_by_attribute($attribute, $value = true) {
		$results = array();
		foreach($this->columns as $field => $column) {
			if( $column->info[$attribute] == $value ) {
				$results[$field] = $column;
			}
		}

		return $results;
	}

	function name(){
		return $this->name;
	}

	public function columns() {
		return $columns;
	}

}