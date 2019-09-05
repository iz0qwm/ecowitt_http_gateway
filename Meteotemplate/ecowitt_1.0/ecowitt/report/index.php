<?php

	############################################################################
	# 	
	#	Meteotemplate
	# 	http://www.meteotemplate.com
	# 	Free website template for weather enthusiasts
	# 	Author: Jachym
	#           Brno, Czech Republic
	# 	First release: 2015
	#
	############################################################################
	#
	#	Plugin for updates via Ecowitt HTTP protocol by Raffaello Di Martino 
	#
	############################################################################
	#	Version and change log
	#
	# 	v1.0 - Sep 03, 2019
	# 		- initial release	
	#	v1.1 - Sep 05, 2019
	#		- Added support to CSV files
	#
	############################################################################
	

	define('__ROOT__', dirname(dirname(__FILE__)));
	
    // load settings
	if(!file_exists("../settings.php")){
		die("The settings file does not exist! Please go to your control panel and set up the netAtmo plugin.");
	}
	include("../settings.php");;

    $base = "../../../";
    
    // load main info
    require($base."config.php");

    // load dependencies
    require($base."scripts/functions.php");
    
    // check acces authorization
	//$password = $_GET['pass'];
    
    // check if password is correct
    //if($password!=$updatePassword){
    //    if($password==$adminPassword){ // if admin password provided accept, but notify
    //        echo "Authorized via admin password";
    //    }
    //    else{
    //        die("Unauthorized");
    //    }
    //}
	
	########### GET DATA ############
	
	# Settings: General
	$device = "auto";  # Use 'auto' for automatic name from PASSKEY else uses the name 
	$forward_data = 1;
	$json_data_log = 1;
	
	$json_data_logdir = $baseURL . "/plugins/ecowitt/";
	
	$txt_data_logdir = $baseURL . "/plugins/ecowitt/";
	$date_txt = date('Y-m-d');
	
	# Convert HTTP POST variables to json
	$weather_data = $_POST;
	$weather_data_forward = $_GET;
	
	# Conversion factors
	$f_mph_kmh = 1.60934;
	$f_mph_kts = 0.868976;
	$f_mph_ms = 0.44704;
	$f_in_hpa = 33.86;
	$f_in_mm = 25.4;
	
	# Get weather station identifier if requested
	if ( $device == "auto" ) {
    		$device = "weather_" . $weather_data['PASSKEY'];
	} else {
			$device = "weather_" . $device ;
	} 
	
	# Convert data
    # Temps
    @$weather_data['windchillc'] = round( ( $weather_data['windchillf'] - 32 ) * 5 / 9, 2 );
    @$weather_data['tempc'] = round( ( $weather_data['tempf'] - 32 ) * 5 / 9, 2 );
    @$weather_data['temp1c'] = round( ( $weather_data['temp1f'] - 32 ) * 5 / 9, 2 );
    @$weather_data['temp2c'] = round( ( $weather_data['temp2f'] - 32 ) * 5 / 9, 2 );
    @$weather_data['tempinc'] = round( ( $weather_data['tempinf'] - 32 ) * 5 / 9, 2 );
    @$weather_data['dewptc'] = round( ( $weather_data['dewptf'] - 32 ) * 5 / 9, 2 );
    
    # Speeds
    @$weather_data['windgustkmh'] = round( $weather_data['windgustmph'] * $f_mph_kmh, 2 );
    @$weather_data['windspeedkmh'] = round( $weather_data['windspeedmph'] * $f_mph_kmh, 2 );
    
    # Distances
    @$weather_data['rainmm'] = round( $weather_data['rainin'] * $f_in_mm, 2 );
    @$weather_data['dailyrainmm'] = round( $weather_data['dailyrainin'] * $f_in_mm, 2 );
    @$weather_data['weeklyrainmm'] = round( $weather_data['weeklyrainin'] * $f_in_mm, 2 );
    @$weather_data['monthlyrainmm'] = round( $weather_data['monthlyrainin'] * $f_in_mm, 2 );
    @$weather_data['yearlyrainmm'] = round( $weather_data['yearlyrainin'] * $f_in_mm, 2 );
    @$weather_data['rainratemm'] = round( $weather_data['rainratein'] * $f_in_mm, 2 );
    
    # Baros
    @$weather_data['baromabshpa'] = round( $weather_data['baromabsin'] * $f_in_hpa, 2 );
    @$weather_data['baromrelhpa'] = round( $weather_data['baromrelin'] * $f_in_hpa, 2 );
    
    # Date and time
    $weather_data['dateutc'] = gmdate("Y-m-d\TH:i:s\Z");
	
	# Forward data to meteotemplate server
	if ( $forward_data == 1 ) 
	{
		@$weather_data_forward['U'] = strtotime( $weather_data['dateutc'] );
		@$weather_data_forward['PASS'] = $forward_server_password ;
		@$weather_data_forward['T'] = $weather_data['tempc'] ;
		@$weather_data_forward['H'] = $weather_data['humidity'] ;
		@$weather_data_forward['P'] = $weather_data['baromabshpa'] ;
		@$weather_data_forward['W'] = $weather_data['windspeedkmh'] ;
		@$weather_data_forward['G'] = $weather_data['windgustkmh'] ;
		@$weather_data_forward['B'] = $weather_data['winddir'] ;
		@$weather_data_forward['R'] = $weather_data['dailyrainmm'] ;
		@$weather_data_forward['RR'] = $weather_data['rainratemm'] ;
		@$weather_data_forward['S'] = $weather_data['solarradiation'] ;
		@$weather_data_forward['UV'] = $weather_data['uv'] ;
		@$weather_data_forward['TIN'] = $weather_data['tempinc'] ;
		@$weather_data_forward['HIN'] = $weather_data['humidityin'] ;
		@$weather_data_forward['T1'] = $weather_data['temp1c'] ;
		@$weather_data_forward['H1'] = $weather_data['humidity1'] ;
		#@$weather_data['forward_url'] = "http://" . $forward_server . $_SERVER[REQUEST_URI];
		@$weather_data_forward['forward_url'] = "http://" . $forward_server ;
		@$weather_data_forward['forward'] = file_get_contents($weather_data_forward['forward_url'] . "?" . "U=" . @$weather_data_forward['U'] . "&PASS=" . @$weather_data_forward['PASS'] . "&T=" . @$weather_data_forward['T'] . "&H=" . @$weather_data_forward['H'] ."&P=" . @$weather_data_forward['P'] . "&W=" . @$weather_data_forward['W'] . "&G=" . @$weather_data_forward['G'] . "&B=" . @$weather_data_forward['B'] . "&R=" . @$weather_data_forward['R'] . "&RR=" . @$weather_data_forward['RR'] . "&S=" . @$weather_data_forward['S'] . "&UV=" . @$weather_data_forward['UV'] . "&TIN=" . @$weather_data_forward['TIN'] . "&HIN=" . @$weather_data_forward['HIN'] . "&T1=" . @$weather_data_forward['T1'] . "&H1=" . @$weather_data_forward['H1'] );
	}
	
	# Pack data into json format
	$weather_data_json = json_encode($weather_data);

	# Write json stream to logfile
	$json_data_logfile = $json_data_logdir . "/" . $device . ".json";
	if ( $json_data_log == 1 ) 
	{
		$file = fopen($json_data_logfile, 'w');
		fwrite($file, $weather_data_json);
		fclose($file);
	}

	# Write stream to csvfile
	$txt_data_logfile = $txt_data_logdir . "/" . $device . "_" . $date_txt . ".csv";
	if ( $txt_data_log == 1 )
	{
		if (!file_exists($txt_data_logfile)) {
			$data = json_decode($weather_data_json);
			foreach($data as $key => $value) {
				$string .= $key . ',';
				}
			$string .= "\n";
			file_put_contents($txt_data_logfile, $string, FILE_APPEND);
		}
		

		$file = fopen($txt_data_logfile, 'a');
		fputcsv($file, $weather_data);
		fclose($file);
	}

	print("Success. Update done\n");
?>
