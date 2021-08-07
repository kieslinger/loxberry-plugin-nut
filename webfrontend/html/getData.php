<?php
include_once "logging.php";

mem_LOGSTART("NUT HTTP getData.php started");

// load config
include_once 'config.php';

// get data from UPS
mem_LOGINF("Getting data from UPS");
@exec('upsc ups 2>&1',$results,$retval);

$help = "initial";
if( $retval != 0 ) {
	print "Error while getting data!<br><br>";
	mem_LOGERR("Error while getting data!");	
	
	// try show usefull informations
	if(in_array("Error: Data stale", $results)) {
		$help = "Please check USB connection";		
	} elseif(in_array("Error: Driver not connected", $results)) {
		$help = "SSH into LoxBerry and try \"upsdrvctl start\"";
	} elseif(in_array("Error: Connection failure: Connection refused", $results)) {
		$help = "SSH into LoxBerry and try \"service nut-server stop/start\"";
	}
}

// puffer output
ob_start();

// print request moment
print "System@DateTime@".date('d.m.Y H:i:s')."<br>";
print "System@DateTimeLox@".epoch2lox(time())."<br>";
print "System@Help@$help<br><br>";

// convert data
mem_LOGOK("Converting data...");
foreach($results AS $line) {
	// convert error to status
	if(strpos($line, "Error:") !== false) {
		$line = "ups.status:".substr($line, 6);
	}
	
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
	$param_str = "";
	foreach($params AS $param) {
		$param_str .= ucfirst( $param );
	}
	
	if( $is_date ) {
		$date = strtotime($values[1]);	
		print $name."@".$param_str."@".date('d.m.Y', $date)."<br>";
		print $name."@".$param_str."Lox"."@".epoch2lox($date)."<br>";
	} else {
		print $name."@".$param_str."@".trim($values[1])."<br>";
	}
	mem_LOGDEB($name."@".$param_str."@".trim($values[1]));

}
print "<br>";

// Responce to virutal input?
if($config_http_send == 1) {	
	mem_LOGDEB("Starting Response to miniserver...");
	include_once 'sendResponces.php';
} 

// print data
ob_end_flush();

mem_LOGEND("NUT HTTP getData.php stopped");	
exit(0);

?>
