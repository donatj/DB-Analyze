<?php

class DBBase {

	private $tables;

	function __construct() {
		$tables = db::fetch('show tables', db::FLAT);
		foreach($tables as $table) {
			if($table[0] == '_') continue;
			$this->tables[$table] = new DBTable($table);
		}

		$this->find_possible_fks();
	}

	function find_possible_fks(){
		foreach( $this->tables as $table ) {
			foreach( $this->tables as $table2 ) {
				if( $table->name() != $table2->name() ) {
					$table->find_possible_fks($table2);
				}
			}
		}

	}

	function find_fk_type_mismatches() {
		$sum = array();
		foreach( $this->tables as $table ) {

			$mismatches = $table->fks_type_mismatch();
			foreach($mismatches as $instance) {
				foreach($instance['mismatches'] as $mismatched_tbl) {
					$sum[ $instance['column']->name() ][ $mismatched_tbl['column']->table()->name() ] = $mismatched_tbl['mismatched'];
				}
			}
		}

		return $sum;
	}


}