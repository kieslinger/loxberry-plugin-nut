<?php
require_once "loxberry_log.php";

$params = [
    "name" => "Daemon",
    "filename" => "$lbplogdir/nut.log",
    "append" => 1
];
$log = LBLog::newLog ($params);

LOGSTART("NUT HTTP getData.php started");

// load config
include_once 'config.php';

// get data from UPS
@exec('upsc ups 2>&1',$results,$retval);

if( $retval != 0 ) {
	print "Error while getting data!<br";
	LOGERR("Error while getting data!");
	LOGEND("NUT HTTP getData.php stopped");
	exit(0);
}

LOGINF("Getting data from UPS");

// print request moment
print "System@DateTime@".date('d.m.Y H:i:s')."<br>";
print "System@DateTimeLox@".epoch2lox(time())."<br><br>";

// puffer output
ob_start();

// convert data
LOGINF("Converting data...");
foreach($results AS $line) {
	$values = explode(":",$line);
	// noting to print
	if( empty($values[0]) ) {
		continue;
	}
	
	// no data-line
	if( empty($values[1]) ) {
		print $line."<br>";
		continue;
	}
	
	$values[0] = str_replace(" ","_",trim($values[0]));
	// replace first dot with @
	$pos = strpos($values[0], ".");		
	$name = ucfirst(substr($values[0], 0, $pos));
	$param_str = substr($values[0], $pos+1);
	$is_date = strpos($param_str, "date") !== false;

	// replace all other dots
	$params = explode(".",$param_str);
	unset($param_str);
	foreach($params AS $param) {
		$param_str = $param_str.ucfirst( $param );
	}
	
	if( $is_date ) {
		$date = strtotime($values[1]);	
		print $name."@".$param_str."@".date('d.m.Y', $date)."<br>";
		print $name."@".$param_str."Lox"."@".epoch2lox($date)."<br>";
	} else {
		print $name."@".$param_str."@".trim($values[1])."<br>";
	}
	LOGDEB($name."@".$param_str."@".trim($values[1]));

}
print "<br>";

// Responce to virutal input?
if($config_http_send == 1) {	
	LOGDEB("Starting Response to miniserver...");
	include_once 'sendResponces.php';
} 

// print data
ob_end_flush();

LOGEND("NUT HTTP getData.php stopped");	

?>
