<?php
LOGDEB("Response parameter: viname=\"".$_GET['viname']."\" viseparator=\"".$_GET['viseparator']."\" viparam=\"".$_GET['viparam']."\"");
$contents = explode("<br>", ob_get_contents());

// Check parameter
if(empty($_GET['viseparator'])) {
	LOGDEB("Response: \"viseparator\" empty. Set to \"-\"");
	$_GET['viseparator'] = "-";
}

if( $_GET['viparam']) {
	// only send to certain parameters
	$viparams = explode(";", $_GET['viparam']);
}

if(empty($_GET['viname'])) {
	print "Parameter \"viname\" not set!<br>";
	LOGWARN("Parameter \"viname\" not set!");
} else {
	$values = explode("@", $content);		

	foreach($contents AS $content) {				
		$values = explode("@", $content);
		if(empty($values[0])) {			
			continue;
		}		
		
		if(in_array($values[0].$values[1], $viparams) OR empty($_GET['viparam'])) {
			// replace space with special-space
			$values[2] = str_replace(" "," ",$values[2]);
			// set vi_endpoint
			$vi_endpoint = $_GET['viname'].$_GET['viseparator'].$values[0].$_GET['viseparator'].$values[1];

			//print "Try to send to: ".$response_endpoint.$vi_endpoint."/".$values[2]."<br>";
			LOGDEB("Try to send to: ".$vi_endpoint."/".$values[2]);
			$response = mshttp_send_mem($msno, $vi_endpoint, $values[2]);
			$ch = curl_init($response_endpoint.$vi_endpoint."/".$values[2]);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			$result = json_decode(curl_exec($ch),true);

			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == "200") {
				print "Send value \"$values[2]\" to \"$vi_endpoint\" successful!<br>";
				LOGINF("Send value \"$values[2]\" to \"$vi_endpoint\" successful!");
			}
		} 
	}
} 
?>
