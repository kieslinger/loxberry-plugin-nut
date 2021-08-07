<?php

$mem_logs = array();

function create_log() {
	require_once "loxberry_system.php";
	require_once "loxberry_log.php";

	global $mem_logs, $lbplogdir;
	
	$level = LBSystem::pluginloglevel();
	$lowest_level = 9;
	
	foreach($mem_logs AS $mem_log) {
		$lowest_level = min($mem_log['level'], $lowest_level);
	}

	if($lowest_level > $level ) {
		print "<br>No log output<br>-> current log level is $level and the lowest message that occurred is $lowest_level";
		return;
	}
	
	$params = [
		"name" => "Daemon",
		"filename" => "$lbplogdir/nut.log",
		"append" => 1
	];
	$log = LBLog::newLog ($params);

	foreach($mem_logs AS $mem_log) {
		switch ($mem_log['level']) {
			case 9:
				LOGSTART($mem_log['message']);
				break;
			case 8:
				LOGEND($mem_log['message']);				
				break;
			case 7:
				LOGDEB($mem_log['message']);
				break;
			case 6:
				LOGINF($mem_log['message']);
				break;
			case 5:
				LOGOK($mem_log['message']);
				break;
			case 4:
				LOGWARN($mem_log['message']);
				break;
			case 3:
				LOGERR($mem_log['message']);
				break;
			case 2:
				LOGCRIT($mem_log['message']);
				break;
			case 1:
				LOGALERT($mem_log['message']);
				break;
			case 0:
				LOGEMERGE($mem_log['message']);
				break;				
		}
	}
}	

function logpush($level, $message) {
	global $mem_logs;
	array_push($mem_logs, array("level" => $level, "message" => $message));
}

function mem_LOGSTART($message) {
	logpush(9, $message);
}

function mem_LOGEND($message) {
	logpush(8, $message);
	create_log();
}

function mem_LOGDEB($message) {
	logpush(7, $message);
}

function mem_LOGINF($message) {
	logpush(6, $message);
}

function mem_LOGOK($message) {
	logpush(5, $message);
}

function mem_LOGWARN($message) {
	logpush(4, $message);
}

function mem_LOGERR($message) {
	logpush(3, $message);
}

function mem_LOGCRIT($message) {
	logpush(2, $message);
}

function mem_LOGALERT($message) {
	logpush(1, $message);
}

function mem_LOGEMERGE($message) {
	logpush(0, $message);
}

?>
