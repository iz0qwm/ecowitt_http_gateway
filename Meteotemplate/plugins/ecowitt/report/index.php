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
	#   v1.2 - Sep 30, 2019
	#	    - Added support for soilmoisture and pm25 sensors
	#   v1.3 - Oct 20, 2019
	#	    - Solved error for second WH31
	#   v1.4 - Jan 06, 2020
	#	    - baromabsin is changed with baromrelin
	#   v1.5 - Jan 15, 2020
	#	    - first version of temperature correction method based on Energy balance 
	#       - http://www.kwos.it/joomla/weather-monitoring/articoli/139-ecowitt-ws80-correzione-della-temperatura-rilevata
	#   v1.6 - Jan 26, 2020
	#	    - modification to the formula of temperature correction method based on Energy balance 
	#	v2.0 - May 11, 2020
	#	    - Battery status for sensors
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
	@$weather_data['windspeedms'] = round( $weather_data['windspeedmph'] * $f_mph_ms, 2 );
    
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
	
	if ($ws80_temperature_correction)
	{
		# reading old data from database
		#
		# one value ago solar radiation
		$query_S_1 = "SELECT S FROM alldata ORDER BY DateTime DESC LIMIT 1";
		$result_S_1 = mysqli_query($con, $query_S_1);
		# two values ago solar radiation
		$query_S_2 = "SELECT S FROM alldata ORDER BY dateTime DESC LIMIT 1 OFFSET 1";
		$result_S_2 = mysqli_query($con, $query_S_2);
		# one value ago temperature
		$query_T_1 = "SELECT T FROM alldata ORDER BY DateTime DESC LIMIT 1";
		$result_T_1 = mysqli_query($con, $query_T_1);
		#
		# Fixed constants
		#
		$dm = 0.0009;
		$K = 0.0984;
		$cp = 29.3;
		$aslow = 0.300;
		$asnormal = 0.325;
		$ashigh = 0.335;
		$asultrahigh = 0.345;
		# Wind cannot be 0
		if ( $weather_data['windspeedms'] < 0.2 )
		{
			$u = 0.2 ;
		} else {
			$u = $weather_data['windspeedms'];
		}
		#
		# as parameter estimation
		#
		if ( $weather_data['solarradiation'] < 1 )
		{
			$S = 0.1;
		} elseif ( $weather_data['solarradiation'] > 352.3 ) {
			$S = 352.3;
		} else {
			$S = $weather_data['solarradiation'];
		}	
		
		if ( $S < $result_S_1 ) {
			if ( $result_S_1 < $result_S_2 ) {
				$as = $ashigh;
			} else {
				$as = $aslow;
			}	
		} else {
			if ( $S < $result_S_2 ) {
				$as = $aslow;
			} else {
				$as = $asnormal;
			}
		}
		
		if ( ( $as == $ashigh ) || ( $as == $low ) ) {
			$diff_temp = $weather_data['tempc'] - $result_T_1;
			if ( $diff_temp > 0.3 ) {
				$as = $asultrahigh;
			}
		}

		if ( round($weather_data['solarradiation'], 2) < 145 ) {
			$as = $asultrahigh*2.5; 
		}		
		
		
		# Formula sections
		$sez1 = $as * $S;
		$sez2 = $u / $dm;
		$sez3 = sqrt($sez2);
		$sez4 = $cp * $K * $sez3;
		$sez5 = $sez1 / $sez4;
		$temp_corr = $weather_data['tempc'] - $sez5;
		
		# Original value of temperature from WS80
		@$weather_data['tempc_orig'] = $weather_data['tempc'];
		
		# Corrected value of temperature from WS80
		$diff_corr_temp = round($result_T_1, 2) - round($temp_corr, 2);
		if ( $diff_corr_temp > 0.7 ) {
			$temp_corr = $temp_corr+0.5;	
		}
		if ( $diff_corr_temp < -0.7 ) {
			$temp_corr = $temp_corr-0.5;	
		}
	
		# Corrected calue of temperature from WS80
		@$weather_data['tempc'] = round( $temp_corr, 2 );
		
		
	}	
	
	# Forward data to meteotemplate server
	if ( $forward_data == 1 ) 
	{
		
		
		@$weather_data_forward['U'] = strtotime( $weather_data['dateutc'] );
		@$weather_data_forward['PASS'] = $forward_server_password ;
		@$weather_data_forward['T'] = $weather_data['tempc'] ;
		@$weather_data_forward['H'] = $weather_data['humidity'] ;
		@$weather_data_forward['P'] = $weather_data['baromrelhpa'] ;
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
		@$weather_data_forward['T2'] = $weather_data['temp2c'] ;
		@$weather_data_forward['H2'] = $weather_data['humidity2'] ;
		@$weather_data_forward['SM1'] = $weather_data['soilmoisture1'] ;
		@$weather_data_forward['SM2'] = $weather_data['soilmoisture2'] ;
		@$weather_data_forward['PP1'] = $weather_data['pm25_ch1'] ;	
		@$weather_data_forward['L'] = $weather_data['lightning_num'] ;
		@$weather_data_forward['LD'] = $weather_data['lightning'] ;
		@$weather_data_forward['LT'] = $weather_data['lightning_time'] ;
		@$weather_data_forward['WBAT'] = $weather_data['wh80batt'] ;
		@$weather_data_forward['RBAT'] = $weather_data['wh40batt'] ;
		@$weather_data_forward['LBAT'] = $weather_data['wh57batt'] ;
		@$weather_data_forward['SM1BAT'] = $weather_data['soilbatt1'] ;
		@$weather_data_forward['SM2BAT'] = $weather_data['soilbatt2'] ;
		@$weather_data_forward['PP1BAT'] = $weather_data['pm25batt1'] ;
		if ( $weather_data['batt1'] == 0 )
		{
			@$weather_data_forward['T1BAT'] = OK ;
		}
		if ( $weather_data['batt1'] == 1 )
		{
			@$weather_data_forward['T1BAT'] = LOW ;
		}
		if ( $weather_data['batt2'] == 0 )
		{
			@$weather_data_forward['T2BAT'] = OK ;
		}
		if ( $weather_data['batt2'] == 1 )
		{
			@$weather_data_forward['T2BAT'] = LOW ;
		}
		
		#@$weather_data['forward_url'] = "http://" . $forward_server . $_SERVER[REQUEST_URI];
		@$weather_data_forward['forward_url'] = "http://" . $forward_server ;
		@$weather_data_forward['forward'] = file_get_contents($weather_data_forward['forward_url'] . "?" . "U=" . @$weather_data_forward['U'] . "&PASS=" . @$weather_data_forward['PASS'] . "&T=" . @$weather_data_forward['T'] . "&H=" . @$weather_data_forward['H'] ."&P=" . @$weather_data_forward['P'] . "&W=" . @$weather_data_forward['W'] . "&G=" . @$weather_data_forward['G'] . "&B=" . @$weather_data_forward['B'] . "&R=" . @$weather_data_forward['R'] . "&RR=" . @$weather_data_forward['RR'] . "&S=" . @$weather_data_forward['S'] . "&UV=" . @$weather_data_forward['UV'] . "&TIN=" . @$weather_data_forward['TIN'] . "&HIN=" . @$weather_data_forward['HIN'] . "&T1=" . @$weather_data_forward['T1'] . "&H1=" . @$weather_data_forward['H1'] . "&T2=" . @$weather_data_forward['T2'] . "&H2=" . @$weather_data_forward['H2'] .  "&SM1=" . @$weather_data_forward['SM1'] . "&SM2=" . @$weather_data_forward['SM2'] . "&L=" . @$weather_data_forward['L'] . "&LD=" . @$weather_data_forward['LD'] . "&LT=" . @$weather_data_forward['LT'] . "&PP1=" . @$weather_data_forward['PP1'] . "&WBAT=" . @$weather_data_forward['WBAT'] . "&RBAT=" . @$weather_data_forward['RBAT'] . "&LBAT=" . @$weather_data_forward['LBAT'] . "&PP1BAT=" . @$weather_data_forward['PP1BAT'] . "&SM1BAT=" . @$weather_data_forward['SM1BAT'] . "&SM2BAT=" . @$weather_data_forward['SM2BAT'] . "&T1BAT=" . @$weather_data_forward['T1BAT'] . "&T2BAT=" . @$weather_data_forward['T2BAT']);
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
	if ($txt_data_log)
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
