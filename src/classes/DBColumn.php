<?php

class DBColumn {

	private $integer_types = array(
		'tinyint'    => 255, 
		'smallint'   => 65535, 
		'mediumint'  => 16777215,
		'int'        => 4294967295,
		'bigint'     => 18446744073709551615,
	);

	private $text_types = array(
		'char'       => 255,
		'varchar'    => 65535,
		'tinytext'   => 255,
		'text'       => 65535,
		'mediumtext' => 16777215,
		'longtext'   => 4294967295,
	);

	private $fk_checked = array(
		'type', 'signed', 'length',// 'nullable'
	);

	private $tbl;
	private $field;
	private $data = false;
	private $fks = array();
	public $info;

	function __construct(DBTable $table, $field, $columndata){
		$this->tbl  = $table;
		$this->field = $field;
		$this->data = $columndata;
		$this->analyze();
	}

	function table() {
		return $this->tbl;
	}

	function name() {
		return $this->field;
	}

	function add_fk(DBColumn $fk) {
		$this->fks[ $fk->table()->name() ] = $fk;
	}

	function fks_type_mismatch() {
		$mismatches = array();
		foreach($this->fks as $fk) {
			foreach($this->fk_checked as $check) {
				if( $this->info[$check] != $fk->info[$check] ) {
					$mismatches[ $fk->table()->name() ]['mismatched'][ $check ] = $fk->info[$check];
					$mismatches[ $fk->table()->name() ]['column'] = $fk;
				}
			}
		}
		return $mismatches;
	}

	function data() {
		if( !$this->data ) {
			$this->data = db::fetch('describe `'. $this->tbl->name() .'` `'. $this->name() .'`', DB::ROW);
		}
		return $this->data;
	}

	function analyze() {
		$column = $this->data();
		$signed = strpos($column['Type'], 'unsigned') === false;
		preg_match('/^[a-z]+/sim', $column['Type'], $regs);
		$type = $regs[0];

		preg_match('/\((\d+)\)/', $column['Type'], $regs);
		$length = (int)$regs[1];

		//echo $type . PHP_EOL;

		$this->info = array(
			'name'         => $column['Field'],
			'signed'       => $signed,
			'type'         => $type,
			'nullable'     => $column['Null'] == 'YES',
			'integer_type' => isset($this->integer_types[$type]),
			'text_type'    => isset($this->text_types[$type]),
			'auto_incr'    => $column['Extra'] == 'auto_increment',
			'length'       => $length,
		);
	}

}