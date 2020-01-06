<?php

/*
************************
/var/www/html/data/report/index.php
************************

PHP script for reading data from GW-1000 Ecowitt weather stations gateway using the Ecowitt protocol
from a work of Christian C. Gruber 2017 on Fine Offset
Raffaello Di Martino http://www.kwos.it 2019

Features:

* Receivers data from webserver as $_POST array
* Forwards data to external meteotemplate server (if $forward_data = 1)
* Converts data to other units (°C, km/h, KTS, mm) if $convert_data = 1
* Stores data in json format to text file (if $json_data_log = 1)
* Stores data in txt format to text file (if $txt_data_log = 1)
* Stores data to dummy device in FHEM server (if $fhem_data_log = 1)
* Prepare export file for Meteonetwork (if $txt_mnw = 1)
* If $device = "auto", device name is extracted from weather station data stream 'PASSKEY' - supports multiple WS


Usage:

* Install a webserver with php capabilities
* create a directory named /data/report (es. /var/www/html/data/report )
* in this directory put the index.php file
* Configure the index.php
* Set your GW1000 Ecowitt to upload to this script (e.g. http://192.168.1.4/data/report/index.php)

*/

# Copyright (C) Raffaello Di Martino
#
# The following terms apply to all files associated
# with the software unless explicitly disclaimed in individual files.
#
# The authors hereby grant permission to use, copy, modify, distribute,
# and license this software and its documentation for any purpose, provided
# that existing copyright notices are retained in all copies and that this
# notice is included verbatim in any distributions. No written agreement,
# license, or royalty fee is required for any of the authorized uses.
# Modifications to this software may be copyrighted by their authors
# and need not follow the licensing terms described here, provided that
# the new terms are clearly indicated on the first page of each file where
# they apply.
#
# IN NO EVENT SHALL THE AUTHORS OR DISTRIBUTORS BE LIABLE TO ANY PARTY
# FOR DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES
# ARISING OUT OF THE USE OF THIS SOFTWARE, ITS DOCUMENTATION, OR ANY
# DERIVATIVES THEREOF, EVEN IF THE AUTHORS HAVE BEEN ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
#
# THE AUTHORS AND DISTRIBUTORS SPECIFICALLY DISCLAIM ANY WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.  THIS SOFTWARE
# IS PROVIDED ON AN "AS IS" BASIS, AND THE AUTHORS AND DISTRIBUTORS HAVE
# NO OBLIGATION TO PROVIDE MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR
# MODIFICATIONS.

# *** SETTINGS START ***

# Debug
error_reporting(E_ALL);
ini_set('display_errors', 'on');

# Settings: General
$device = "auto";  	# Use 'auto' for automatic name from PASSKEY else uses the name 
$json_data_log = 1; 	# Activate the export to JSON. Set always to 1 
$txt_data_log = 0; 	# Activate the export to .csv
$fhem_data_log = 0; 	# Activate the forward to FHEM server
$forward_data = 1; 	# Activate the forward to Meteotemplate web site
$txt_mnw = 0; 		# Activate the FTP for the Meteonetwork string
$txt_weewx = 1;         # Activate the export to .txt for weewx driver

# Settings: FHEM
$FHEM_server = "127.0.0.1";
$FHEM_port = "7072";

# Settings: json and txt data log dir
$json_data_logdir = "/var/log/ecowitt";
$txt_data_logdir = "/var/log/ecowitt";

# Settings: Forward to meteotemplate server
$forward_server = "www.kwos.org/poggiocorese_ecowitt/api.php";
$forward_server_password = "*******";

# Setting for Meteonetwork export file
$station_mnw = "mcr063";  		# This is the name of the station received by Meteonetwork registration
$txt_dir_mnw = "/var/log/ecowitt";  	# Directory where will be store the temp file (e.g. mcr063.txt )
$ftp_mnw = "ftp.kwos.org"; 		# FTP Server where will be stored the file for Meteonetwork (e.g. mcr063.txt )
$ftp_user_mnw = "******"; 		# User of the FTP server
$ftp_pass_mnw = "******"; 		# Password of the FTP server 
$ftp_dir_mnw = "/www.kwos.org/marinadimontemarciano/"; 		# Rememeber the "/" at the end, this is a directory

# Setting for weewx driver
$txt_dir_weewx = "/var/log/ecowitt";


# *** SETTINGS END ***

# Convert HTTP POST variables to json
$weather_data = $_POST;
$weather_data_forward = $_GET;
$date_txt = date('Y-m-d');

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
    @$weather_data['temp3c'] = round( ( $weather_data['temp3f'] - 32 ) * 5 / 9, 2 );
    @$weather_data['tempinc'] = round( ( $weather_data['tempinf'] - 32 ) * 5 / 9, 2 );
    @$weather_data['dewptc'] = round( ( $weather_data['dewptf'] - 32 ) * 5 / 9, 2 );
    
    # Speeds
    @$weather_data['windgustkmh'] = round( $weather_data['windgustmph'] * $f_mph_kmh, 2 );
    @$weather_data['windspeedkmh'] = round( $weather_data['windspeedmph'] * $f_mph_kmh, 2 );
    
    # Calculated
    @$weather_data['windchillc'] = round((13.12 + 0.6215 * @$weather_data['tempc'] - 11.37 * pow(@$weather_data['windspeedkmh'],0.16) + 0.3965 * @$weather_data['tempc'] * pow(@$weather_data['windspeedkmh'],0.16)), 1);
    @$weather_data['windchillf'] = round( ( $weather_data['windchillc'] * 9 / 5 ) + 32, 2 );
    @$weather_data['dewptc'] = round(((pow(($weather_data['humidity']/100), 0.125))*(112+0.9*@$weather_data['tempc'])+(0.1*@$weather_data['tempc'])-112),1);
    @$weather_data['dewptf'] = round( ( $weather_data['dewptc'] * 9 / 5 ) + 32, 2 );


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

    # Batteries
    if ( $weather_data['wh80batt'] < 2.5 )
    { 	
	@$weather_data['windBatteryStatus'] = 1.0 ;
    } 
    if ( $weather_data['wh80batt'] > 2.5 )
    { 	
	@$weather_data['windBatteryStatus'] = 0.0 ;
    } 
    @$weather_data['consBatteryVoltage'] = $weather_data['wh80batt'] ;

    if ( $weather_data['wh40batt'] < 1.0 )
    {
        @$weather_data['rainBatteryStatus'] = 1.0 ;
    }
    if ( $weather_data['wh40batt'] > 1.0 )
    {
        @$weather_data['rainBatteryStatus'] = 0.0 ;
    }
    @$weather_data['supplyVoltage'] = $weather_data['wh40batt'] ;

    if ( $weather_data['soilbatt1'] < 1.0 )
    {
        @$weather_data['txBatteryStatus'] = 1.0 ;
    }
    if ( $weather_data['soilbatt1'] > 1.0 )
    {
        @$weather_data['txBatteryStatus'] = 0.0 ;
    }
    @$weather_data['heatingVoltage'] = $weather_data['soilbatt1'] ;

    @$weather_data['outTempBatteryStatus'] = $weather_data['batt1'] ;



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
    @$weather_data_forward['T3'] = $weather_data['temp2c'] ;
    @$weather_data_forward['H3'] = $weather_data['humidity2'] ;
    @$weather_data_forward['SM1'] = $weather_data['soilmoisture1'] ;
    @$weather_data_forward['PP1'] = $weather_data['pm25_ch1'] ;


    #@$weather_data['forward_url'] = "http://" . $forward_server . $_SERVER[REQUEST_URI];
    @$weather_data_forward['forward_url'] = "http://" . $forward_server ;
    @$weather_data_forward['forward'] = file_get_contents($weather_data_forward['forward_url'] . "?" . "U=" . @$weather_data_forward['U'] . "&PASS=" . @$weather_data_forward['PASS'] . "&T=" . @$weather_data_forward['T'] . "&H=" . @$weather_data_forward['H'] ."&P=" . @$weather_data_forward['P'] . "&W=" . @$weather_data_forward['W'] . "&G=" . @$weather_data_forward['G'] . "&B=" . @$weather_data_forward['B'] . "&R=" . @$weather_data_forward['R'] . "&RR=" . @$weather_data_forward['RR'] . "&S=" . @$weather_data_forward['S'] . "&UV=" . @$weather_data_forward['UV'] . "&TIN=" . @$weather_data_forward['TIN'] . "&HIN=" . @$weather_data_forward['HIN'] . "&T1=" . @$weather_data_forward['T1'] . "&H1=" . @$weather_data_forward['H1'] . "&T2=" . @$weather_data_forward['T2'] . "&H2=" . @$weather_data_forward['H2'] . "&T3=" . @$weather_data_forward['T3'] . "&H3=" . @$weather_data_forward['H3'] . "&SM1=" . @$weather_data_forward['SM1'] . "&PP1=" . @$weather_data_forward['PP1'] );
}

# Pack data into json format
$weather_data_json = json_encode($weather_data);

# Converts array into string
#$weather_data_txt = implode(";",$weather_data);

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

# Write data to FHEM
if ( $fhem_data_log == 1 ) 
{
    # Add settings, json and url string to array
    $weather_data['json'] = $weather_data_json;
    $weather_data['url'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $weather_data['settings_device'] = $device;
    $weather_data['settings_convert_data'] = $convert_data;
    $weather_data['settings_json_data_log'] = $json_data_log;
    $weather_data['settings_json_data_logdir'] = $json_data_logdir;
    $weather_data['settings_json_data_logfile'] = $json_data_logfile;
    $weather_data['settings_fhem_data_log'] = $fhem_data_log;
    $weather_data['settings_forward_data'] = $forward_data;
    $weather_data['settings_forward_server'] = $forward_server;
    $weather_data['settings_FHEM_server'] = $FHEM_server;
    $weather_data['settings_FHEM_port'] = $FHEM_port;

    $FHEM_device = $device;
    
    $conn=fsockopen($FHEM_server,$FHEM_port);
    if($conn){
    
        # Create FHEM device if not exists
        $FHEM_command = fputs($conn,"define $FHEM_device dummy".chr(10).chr(13));
    
        # Write each value into seperate reading
        foreach ($weather_data as $reading => $value) {
            $FHEM_command = fputs($conn,"setreading $FHEM_device $reading $value".chr(10).chr(13));
        }
    
        # Exit from FHEM interface
        $FHEM_command = fputs($conn,"exit".chr(10).chr(13));
        fclose($conn);
    }
}

# Specifiche del file inviato dalla stazione al server Mnw formato file: file di testo con campi separati da ";" 
# nome file: nomestazione.txt (con nome stazione si intende il codice assegnato da mnw) separatore decimali: "."
# separatore migliaia: nessuno
# campi presenti nel file:
# 1.    Station - codice Stazione
# 2.    Date - Data rilevamento (gg/mm/aa) . 
# 3.    Time - Ora rilevamento (hh:mm)
# 4.    TempOut - Temperatura esterna (°C)
# 5.    Pres - Pressione (Hpa)
# 6.    HumOut Umidita' relativa esterna (%)
# 7.    Wind - Velocità del vento (km/h)
# 8.    Dir - Direzione del vento (°)
# 9.    Gust - Massima raffica (km/h)
# 10.   RainRate - Rain rate (mm/h)
# 11.   Rain - Pioggia giornaliera (mm)
# 12.   DewPoint - DewPoint (°C)
# 13.   Software - Nome del software ( un nome generico )
# 14.   Versione - Versione del software 
# 15.   TempIn - Temperatura interna (°C)
# 16.   HumIn - Umidità interna (%)
# 17.   UVI - Radiazione solare (UVI)
# 18.   Radiazione Solare W/m2
# dato non presente indicare “-99999”. Ultima riga in basso l’ultimo dato . Se non si puo’ mandare tutto l’archivio della giornata basta mandare solo l’ultimo dato , una riga sola .  Di norma il file contiene i dati degli ultimi 2 giorni . 
#
# vnt214;18/08/15;00:05;20.4;1010.2;90;0.0;180;3.2;0.0;0.0;18.7;WL02;0.1;-99999;-99999;-99999;-99999; 

if ( $txt_mnw == 1 )
{

    @$weather_data_mnw['Station'] = $station_mnw;
    @$weather_data_mnw['Date'] = gmdate("d-m-y");
    @$weather_data_mnw['Time'] = gmdate("H:i");
    @$weather_data_mnw['TempOut'] = $weather_data['tempc'] ;
    @$weather_data_mnw['Pres'] = $weather_data['baromrelhpa'] ;
    @$weather_data_mnw['HumOut'] = $weather_data['humidity'] ;
    @$weather_data_mnw['Wind'] = $weather_data['windspeedkmh'] ;
    @$weather_data_mnw['Dir'] = $weather_data['winddir'] ;
    @$weather_data_mnw['Gust'] = $weather_data['windgustkmh'] ;
    @$weather_data_mnw['RainRate'] = $weather_data['rainratemm'] ;
    @$weather_data_mnw['Rain'] = $weather_data['dailyrainmm'] ;
    @$weather_data_mnw['DewPoint'] = "-99999" ;
    @$weather_data_mnw['Software'] = "EcowittGate" ;
    @$weather_data_mnw['Versione'] = "XX" ;
    @$weather_data_mnw['TempIn'] = $weather_data['tempinc'] ;
    @$weather_data_mnw['HumIn'] = $weather_data['humidityin'] ;
    @$weather_data_mnw['SolarRad'] = $weather_data['solarradiation'] ;
    @$weather_data_mnw['UVI'] = $weather_data['uv'] ;

    $stringa = @$weather_data_mnw['Station'] . ";" . @$weather_data_mnw['Date'] . ";" . @$weather_data_mnw['Time'] . ";" . @$weather_data_mnw['TempOut'] . ";" . @$weather_data_mnw['Pres'] . ";" . @$weather_data_mnw['HumOut'] . ";" . @$weather_data_mnw['Wind'] . ";" . @$weather_data_mnw['Dir'] . ";" . @$weather_data_mnw['Gust'] . ";" . @$weather_data_mnw['RainRate'] . ";" . @$weather_data_mnw['Rain'] . ";" . @$weather_data_mnw['Rain'] . ";" . @$weather_data_mnw['DewPoint'] . ";" . @$weather_data_mnw['Software'] . ";" . @$weather_data_mnw['Versione'] . ";" . @$weather_data_mnw['TempIn'] . ";" . @$weather_data_mnw['HumIn'] . ";" . @$weather_data_mnw['SolarRad'] . ";" . @$weather_data_mnw['UVI'] . "\n";

    $txt_mnw_logfile = $txt_dir_mnw . "/" . $station_mnw . ".txt";
    $file = fopen($txt_mnw_logfile, 'w');
    fwrite($file, $stringa);
    fclose($file);

	// set up basic connection
	$conn_id = ftp_connect($ftp_mnw);

	// login with username and password
	$login_result = ftp_login($conn_id, $ftp_user_mnw, $ftp_pass_mnw);
	$remote_file = $ftp_dir_mnw . $station_mnw . ".txt";

	// turn passive mode on
	ftp_pasv($conn_id, true);

	// upload a file
	if (ftp_put($conn_id, $remote_file, $txt_mnw_logfile, FTP_ASCII)) {
 		print "successfully uploaded $txt_mnw_logfile\n";
	} else {
 		print "There was a problem while uploading $txt_mnw_logfile\n";
	}

	// close the connection
	ftp_close($conn_id);

    	#print($stringa);

}

if ( $txt_weewx == 1 )
{
    @$weather_data_weewx['outTemp'] = $weather_data['tempf'] ;
    @$weather_data_weewx['barometer'] = $weather_data['baromrelin'] ;
    @$weather_data_weewx['pressure'] = $weather_data['baromrelin'] ;
    @$weather_data_weewx['outHumidity'] = $weather_data['humidity'] ;
    @$weather_data_weewx['windSpeed'] = $weather_data['windspeedmph'] ;
    @$weather_data_weewx['windDir'] = $weather_data['winddir'] ;
    @$weather_data_weewx['windGust'] = $weather_data['windgustmph'] ;
    @$weather_data_weewx['rainRate'] = $weather_data['rainratein'] ;
    @$weather_data_weewx['rain_total'] = $weather_data['totalrainin'] ;
    @$weather_data_weewx['inTemp'] = $weather_data['tempinf'] ;
    @$weather_data_weewx['inHumidity'] = $weather_data['humidityin'] ;
    @$weather_data_weewx['radiation'] = $weather_data['solarradiation'] ;
    @$weather_data_weewx['UV'] = $weather_data['uv'] ;
    @$weather_data_weewx['windchill'] = $weather_data['windchillf'] ;
    @$weather_data_weewx['dewpoint'] = $weather_data['dewptf'] ;
    @$weather_data_weewx['extraTemp1'] = $weather_data['temp1f'] ;
    @$weather_data_weewx['extraHumid1'] = $weather_data['humidity1'] ;
    @$weather_data_weewx['extraTemp2'] = $weather_data['temp2f'] ;
    @$weather_data_weewx['extraHumid2'] = $weather_data['humidity2'] ;
    @$weather_data_weewx['extraTemp3'] = $weather_data['temp3f'] ;
    @$weather_data_weewx['extraHumid3'] = $weather_data['humidity3'] ;
    @$weather_data_weewx['soilTemp1'] = $weather_data['soilmoisture1'] ;
    @$weather_data_weewx['windBatteryStatus'] = $weather_data['windBatteryStatus'] ;
    @$weather_data_weewx['rainBatteryStatus'] = $weather_data['rainBatteryStatus'] ;
    @$weather_data_weewx['outTempBatteryStatus'] = $weather_data['batt1'] ;
    @$weather_data_weewx['consBatteryVoltage'] = $weather_data['consBatteryVoltage'] ;
    @$weather_data_weewx['supplyVoltage'] = $weather_data['supplyVoltage'] ;
    @$weather_data_weewx['heatingVoltage'] = $weather_data['heatingVoltage'] ;
    @$weather_data_weewx['txBatteryStatus'] = $weather_data['txBatteryStatus'] ;

    $stringa = "outTemp=" . @$weather_data_weewx['outTemp'] . "\nbarometer=" . @$weather_data_weewx['barometer'] . "\npressure=" . @$weather_data_weewx['pressure'] . "\noutHumidity=" . @$weather_data_weewx['outHumidity'] . "\nwindSpeed=" . @$weather_data_weewx['windSpeed'] . "\nwindDir=" . @$weather_data_weewx['windDir'] . "\nwindGust=" . @$weather_data_weewx['windGust'] . "\nrainRate=" . @$weather_data_weewx['rainRate'] . "\nrain_total=" . @$weather_data_weewx['rain_total'] . "\ninTemp=" . @$weather_data_weewx['inTemp'] . "\ninHumidity=" . @$weather_data_weewx['inHumidity'] . "\nradiation=" . @$weather_data_weewx['radiation'] . "\nUV=" . @$weather_data_weewx['UV'] . "\nwindchill=" . @$weather_data_weewx['windchill'] . "\ndewpoint=" . @$weather_data_weewx['dewpoint'] . "\nextraTemp1=" . @$weather_data_weewx['extraTemp1'] . "\nextraHumid1=" . @$weather_data_weewx['extraHumid1'] . "\nextraTemp2=" . @$weather_data_weewx['extraTemp2'] . "\nextraHumid2=" . @$weather_data_weewx['extraHumid2'] . "\nextraTemp3=" . @$weather_data_weewx['extraTemp3'] . "\nextraHumid3=" . @$weather_data_weewx['extraHumid3'] . "\nsoilTemp1=" . @$weather_data_weewx['soilTemp1'] . "\nwindBatteryStatus=" . @$weather_data_weewx['windBatteryStatus'] . "\nrainBatteryStatus=" . @$weather_data_weewx['rainBatteryStatus'] . "\noutTempBatteryStatus=" . @$weather_data_weewx['outTempBatteryStatus'] . "\nconsBatteryVoltage=" . @$weather_data_weewx['consBatteryVoltage'] . "\nsupplyVoltage=" . @$weather_data_weewx['supplyVoltage'] . "\ntxBatteryStatus=" . @$weather_data_weewx['txBatteryStatus'] . "\nheatingVoltage=" . @$weather_data_weewx['heatingVoltage'] . "\n";


    $txt_weewx_logfile = $txt_dir_weewx . "/weewx.txt";
    $file = fopen($txt_weewx_logfile, 'w');
    fwrite($file, $stringa);
    fclose($file);

}


print("success\n");

?>
