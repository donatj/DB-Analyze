#!/usr/bin/env php
<?php

require('init.php');

$shortopts  = "";
$shortopts .= "f:";  // Required value
$shortopts .= "v::"; // Optional value
$shortopts .= "abc"; // These options do not accept values

$longopts  = array(
	"required:",     // Required value
	"optional::",    // Optional value
	"option",        // No value
	"opt",           // No value
);
$options = getopt($shortopts, $longopts);
var_dump($options);

$ignore_pks = true;

$tables = db::fetch('show tables', db::FLAT);

$table_data = array();

$integer_types = array('tinyint' => 255, 'smallint' => 65535, 'mediumint' => 16777215, 'int' => 4294967295, 'bigint' => 18446744073709551615);
$text_types    = array('char' => 255, 'varchar' => 65535, 'tinytext' => 255, 'text' => 65535, 'mediumtext' => 16777215, 'longtext' => 4294967295);

foreach($tables as $table) {

	if($table[0] == '_') continue;
	
	//$table_data[ $table ] = array();
	$columns = db::fetch('show columns from `'. $table .'`');
	
	foreach($columns as $column) {

		if($column['Key'] == 'PRI' && $ignore_pks) {
			//continue;
		}

		$signed = strpos($column['Type'], 'unsigned') === false;
		preg_match('/^[a-z]+/sim', $column['Type'], $regs);
		$type = $regs[0];

		preg_match('/\((\d+)\)/', $column['Type'], $regs);
		$length = (int)$regs[1];

		//echo $type . PHP_EOL;

		$table_data[ $table ][ $column['Field'] ] = array(
			'name'         => $column['Field'],
			'signed'       => $signed,
			'type'         => $type,
			'nullable'     => $column['Null'] == 'YES',
			'integer_type' => isset($integer_types[$type]),
			'text_type'    => isset($text_types[$type]),
			'auto_incr'    => $column['Extra'] == 'auto_increment',
			'length'       => $length,
		);

	}	

}

drop($table_data);

$fk_diff = array();
foreach( $table_data as $table_name => $table ) {
	foreach($table as $column => $col) {
		$fk_diff[ $column ][ $col['type'] ][ $col['length'] ][] = $table_name;
	}
}

echo '# Database Analysis' . PHP_EOL;

$table_status = db::fetch('SHOW TABLE STATUS', db::KEYROW);

echo '## Structural/Data Optimizations / Problems' . PHP_EOL;

foreach( $table_data as $table_name => $table ) {

	echo '### ' . $table_name . ' ('.(rtrim(number_format($table_status[$table_name]['Data_length'] / 1048576, 3),'0.') ?: 0).' megabytes)' . PHP_EOL;

	$ints = array_filter($table, function($row){ return $row['integer_type']; });

	if($ints) {
		$maxss = array();
		$minss = array();
		foreach($ints as $int_k => $int) {
			$maxss[] = 'max(`'.db::input($int_k).'`) AS `' . db::input($int_k) . '`';
			$minss[] = 'min(`'.db::input($int_k).'`) AS `' . db::input($int_k) . '`';
		}

		$max = db::fetch('SELECT ' . implode(', ', $maxss) . ' FROM ' . $table_name, db::ROW);
		$min = db::fetch('SELECT ' . implode(', ', $minss) . ' FROM ' . $table_name, db::ROW);
		
		foreach($ints as $int_k => $int) {
			$table[ $int_k ]['max_value'] = $max[ $int_k ];
			$table[ $int_k ]['min_value'] = $min[ $int_k ];
		}

	}

	foreach($table as $column => $col) {

		$data = '';

		if($col['integer_type']) {
			if($col['min_value'] >= 0 && $col['signed']) {
				$data .= '- Signed but no negative values' . ( $col['auto_incr'] ? ' and auto-increment' : '' ) . PHP_EOL;
			}

			foreach($integer_types as $int_type => $int_max ) {
				$last_type = $int_type;
				if( $int_max > $col['max_value'] ) break;
			}

			if($last_type != $col['type']) {
				$current_percent = (int)(($col['max_value'] / $integer_types[ $col['type'] ]) * 100);
				$optimal_percent = (int)(($col['max_value'] / $integer_types[ $last_type ]) * 100);

				if( $col['max_value'] == $integer_types[ $col['type'] ] ) {
					$data .= '- **WARNING!** Max value of: *' . $col['max_value'] . '* at max value of current type *' . $col['type']  . '*' .   PHP_EOL;
				}else{
					$data .= '- Max ' . number_format($col['max_value'], 0) . ' fits within ' . $last_type . '(max: '. number_format($integer_types[ $last_type ], 0) .') but defined as '. $col['type'] .'(max: '. number_format($integer_types[ $col['type'] ], 0) .')' . PHP_EOL;
					$data .= '	- Using ' . $current_percent . '%, would be using: '.$optimal_percent.'%' . PHP_EOL;
				}
			}

			if( $col['auto_incr'] && $current_percent > 30 ) {
				$data .= '- **WARNING!** auto-increment column at ' . $current_percent .'% capacity' . PHP_EOL;
			}

			if(count($fk_diff[ $column ]) > 1 || count(reset($fk_diff[ $column ])) > 1) {
				$data .= '- Potential foreign key type mismatch:' . PHP_EOL;
				foreach($fk_diff[ $column ] as $xtype => $type_diff) {
					foreach( $type_diff as $xlength => $diff_tables ) {
						if( $xtype == $col['type'] && $xlength == $col['length'] ) continue;
						$data .= '	- **' .$xtype . ($xlength ? '('.$xlength.')' : '') . '** on '.count($diff_tables).' table(s): ' . PHP_EOL;
						$data .= '		- ' . implode(', ', $diff_tables) . PHP_EOL;
					}
				}
				$data .= '	- Selected type and length on ' . count( $fk_diff[ $column ][ $col['type'] ][ $col['length'] ] ) . ' table(s)' . PHP_EOL;
			}

		}

		if($data) {
			echo '#### ' . $column . ' ' . $col['type'] . ($col['length'] ? '('.$col['length'].')' : '') . PHP_EOL;
			echo $data;
		}

		echo PHP_EOL;

	}

	echo PHP_EOL . '------------------------------' . PHP_EOL;

	echo PHP_EOL . PHP_EOL;
	if( $j++ > 20) {
		//break;
	}

}