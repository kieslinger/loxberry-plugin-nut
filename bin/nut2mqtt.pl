#!/usr/bin/perl
use lib "/opt/loxberry/libs/perllib";

#install service:
# ln -s /opt/loxberry/bin/plugins/nut/nut2mqtt.service /etc/systemd/system/nut2mqtt.service
# systemctl daemon-reload
# systemctl enable nut2mqtt
# systemctl start nut2mqtt

use strict;
use warnings;
use LoxBerry::System;
use LoxBerry::IO;
use Time::Piece;
use JSON;

# list of params in light mode
my $light_params = 'charge$|battery/mfr/date$|runtime$|voltage$|^system|status$|ups/model$|load$';

# publish in json format
my $use_json = 0;
if ($ARGV[1] =~ /json/) {
	$use_json = 1;
}

my %json_array = ( );
my $sub_topic_last = '';

# constants
my $base_topic = "nut/";
my $status = "bridge/status";
my $interval = 10;

# connect to MQTT-Gateway
print "connecting to mqtt gateway\n";
my $mqtt = mqtt_connect();

# set last will
print "sending last will to mqtt gateway\n";
$mqtt->last_will($base_topic.$status, "Disconnected", 1);

# begin endless loop
while (1) {
	# set connected state
	print "retain: ".$base_topic.$status.":Connected\n";
	$mqtt->retain( $base_topic.$status, "Connected" );	
	
	for (my $index = 0; $index < 2; $index++) {
		my $topic = $base_topic;
		my $result = "";
		
		# get data from UPS
		if ($index == 0) {
			$topic .= "DG/";
			$result = `upsc ups\@localhost 2>&1`;
		} else {
			$topic .= "EG/";
			$result = `upsc ups\@10.1.10.15 2>&1`;
		}

		# check error informations
		my $help = "initial";
		if( $result =~ /Error:/ ) {
			print "Error while getting data!\n";
			
			# try show usefull informations
			if ($result =~ /Data stale/) {
				$help = 'Please check USB connection';
			} elsif($result =~ /Driver not connected/) {
				$help = 'SSH into LoxBerry and try "upsdrvctl start"';
			} elsif($result =~ /Connection refused/) {
				$help = 'SSH into LoxBerry and try "service nut-server stop/start"';
			}
		}

		# print system data
		$result .= "system.datetime:".localtime->ymd." ".localtime->hms("-")."\n";
		$result .= "system.help:".$help."\n";

		# convert data
		foreach my $line (split /\n/, $result) {
			my $sub_topic;
			
			# convert error to status
			if ( $line =~ /Error:/) {
				$line = "ups.status:".substr($line, 6);
			}
			
			# convert data
			my ($param, $value) = split /:/, $line;
			
			# replace all dots with splash
			$param =~ s/\./\//g;
			# trim values
			$value = trim($value);
			
			# noting to transfer
			if (!$param || $param eq '' || !$value || $value eq '') { 
				next; 
			}

			# check only light fields?
			if (($ARGV[0] =~ /light/) and ($param !~ m/$light_params/)) {
				next;
			}
			
			# use json format?
			if ($use_json == 1) {
				# set sub topic
				$sub_topic = substr($param, 0, index($param,'/'));
				# new sub_topic? then publish to mqtt
				if ((%json_array) and ($sub_topic ne $sub_topic_last)) {
					print "publish: ".$topic.$sub_topic_last."\n";
					$mqtt->publish( $topic.$sub_topic_last, encode_json \%json_array );
					%json_array = ();
				}
				$param = substr($param, index($param,'/')+1);
				$sub_topic_last = $sub_topic;
			}
			
			# transfer dates in epoch and loxepoch
			if ( $param =~ /date/) {
				# convert string to date
				$value =~ s/\//\-/g;
				my $date = Time::Piece->strptime($value, "%Y-%m-%d %H-%M-%S");
				
				# publish loxdate to MQTT
				$value = epoch2lox($date->epoch);
				if ($use_json == 1) {
					$json_array{ $param."/lox" } = $value;
				} else {
					print "publish: ".$param."/lox value: ".$value."\n";
					$mqtt->publish( $topic.$param."/lox", $value );
				}
				
				# publish date to MQTT
				if ($date->hms =~ /00:00:00/) {
					# publish only date
					$value = $date->dmy(".");
				} else {
					# publish date and time
					$value = $date->dmy(".")." ".$date->hms;
				}
			}
			
			# publish to MQTT
			if ($use_json == 1) {
				$json_array{ $param } = $value;
			} else {
				print "publish: ".$param." value: ".$value."\n";
				$mqtt->publish( $topic.$param, $value );
			}
		}

		if (($use_json == 1) and (%json_array)) {
			print "publish: ".$topic.$sub_topic_last."\n";
			$mqtt->publish( $topic.$sub_topic_last, encode_json \%json_array );
			%json_array = ();
		}
	}
	
	# sleep a bit
	sleep($interval);
}

# disconnect from MQTT-Gateway
print "retain: ".$base_topic.$status.":Disonnected\n";
$mqtt->retain( $base_topic.$status, "Disonnected" );
$mqtt->disconnect();
