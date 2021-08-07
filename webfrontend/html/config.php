<?php
require_once "Config/Lite.php";
require_once "loxberry_system.php";

// save timezone
$server_timezone = date_default_timezone_get();

// load configfile
$config = new Config_Lite("$lbpconfigdir/pluginconfig.cfg");

// config data
$config_email_send = $config['MAIN']['EMAIL'];
$config_miniserver = $config['MAIN']['MINISERVER'];
$config_http_send = $config['MAIN']['HTTPSEND'];

// send http?
$found = false;
if($config_http_send == 1) {	
	$miniservers = LBSystem::get_miniservers();
	foreach ($miniservers as $index =>$miniserver) {		
		if($miniserver['Name'] == $config_miniserver) {
			$miniserver_no = $index;
			if($miniserver['PreferHttps'] == 1) {
				mem_LOGDEB("sending encrypted in https-Mode");
				$response_endpoint = "https://";
				$miniserver_port = $miniserver['PortHttps'];	
			} else {
				mem_LOGDEB("sending not encrypted in http-Mode");
				$response_endpoint = "http://";
				$miniserver_port = $miniserver['Port'];
			}		
			$response_endpoint = $response_endpoint.$miniserver['Credentials']."@".
								 $miniserver['IPAddress'].":".$miniserver_port."/dev/sps/io/";
			break;
		}			
	}
}

?>
