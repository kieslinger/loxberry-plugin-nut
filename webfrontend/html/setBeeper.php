<?php
require_once "loxberry_log.php";

// check inputs
switch($_GET['mode'] ) {	
	case "0":
	case "none":
		$lock = 0;
		$lock_str = "none";
		break;
	case "1":
	case "in":
		$lock = 1;
		$lock_str = "lock in";
		break;		
	case "2":
	case "out":
		$lock = 2;
		$lock_str = "lock out";
		break;
	case "3":
	case "both":
		$lock = 3;
		$lock_str = "lock both";
		break;
	default:
		die("Usage: ".$_SERVER['PHP_SELF']."?mode=[0|1|2|3] or [none|in|out|both|]<br>");
}

$params = [
    "name" => "Daemon",
    "filename" => "$lbplogdir/sureflap.log",
    "append" => 1
];
$log = LBLog::newLog ($params);

LOGSTART("SureFlap HTTP setLockMode.php started");
LOGDEB("LockMode: ".$lock_str);

// get new data - no output
$background = true;
include 'getData.php';

if($device_lock_id == $lock) {
	print "Lockmode on \"$flapname\" is \"$lock_str\". No change necessary.<br>";
	LOGINF("Lockmode on \"$flapname\" is \"$lock_str\". No change necessary.");
} else {
	LOGDEB("Starting request...");
	$json = json_encode(array("locking" => "$lock"));
	$ch = curl_init($endpoint."/api/device/$flap/control");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Content-Length: ".strlen($json),"Authorization: Bearer $token"));
	$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");
	LOGDEB("Request received with code: ".curl_getinfo($ch, CURLINFO_HTTP_CODE));

	if($result['data']['locking'] == $lock) {
		print "Successfully set lockmode for \"$flapname\" to \"$lock_str\"<br><br>";
		LOGINF("Successfully set lockmode for \"$flapname\" to \"$lock_str\"");
	} else {
		print "Lockmode change failed!<br>";
		LOGERR("Lockmode change failed!");
	}

	if($config_http_send == 1) {
		// Build data to responce
		$devices = array(array("id" => $flap, "name" => $flapname, "product_id" => $flaptype, "control" => $result['data']));
		include 'getDevices.php';
		// Responce to virutal input
		LOGDEB("Starting Response to miniserver...");
		include_once 'sendResponces.php';
	}	
}

LOGEND("SureFlap HTTP setLockMode.php stopped");
?>
