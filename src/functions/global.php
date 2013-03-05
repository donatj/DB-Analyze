<?php

function drop() {
	call_user_func_array('see', func_get_args());
	die(127);
}

function see() {
	$data = $args = "";
	$eol = "\n\r";
	$arguments = func_get_args();
	if (is_array($arguments) && !empty($arguments)) {
		foreach ($arguments as $i => $argument) {
			$args .= ">>>>>>>>>>>>> Arg No. {$i} >>>>>>>>>>>>>" . $eol . $eol;
			$argument = is_null($argument) ? "(null)null" : $argument;
			$argument = $argument === false ? "(bool)false" : $argument;
			$argument = $argument === true ? "(bool)true" : $argument;
			$args .= print_r($argument, true) . $eol . $eol;
		} $args .= ">>>>>>>>>>>>>>> EOF >>>>>>>>>>>>>>>>>";
		$final = PHP_EOL . PHP_EOL . "<pre>{$eol}{$args}{$eol}</pre>" . PHP_EOL . PHP_EOL;
		echo $final;
	} return "";
}