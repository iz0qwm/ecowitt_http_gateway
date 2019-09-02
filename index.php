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
* If $device = "auto", device name is extracted from weather station data stream 'PASSKEY' - supports multiple WS


Usage:

* Set your WS to upload to this script (e.g. http://192.168.1.1/)

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
$device = "auto";  # Use 'auto' for automatic name from PASSKEY else uses the name 
$json_data_log = 1;
$txt_data_log = 1;
$fhem_data_log = 0;
$forward_data = 1;

# Settings: FHEM
$FHEM_server = "127.0.0.1";
$FHEM_port = "7072";

# Settings: json and txt data log dir
$json_data_logdir = "/var/log/ecowitt";
$txt_data_logdir = "/var/log/ecowitt";

# Settings: Forward to meteotemplate server
#$forward_server = "www.kwos.org/marinadimontemarciano/api.php";
$forward_server = "192.168.2.205/marinadimontemarciano/api.php";
$forward_server_password = "******";

# *** SETTINGS END ***

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
$txt_data_logfile = $txt_data_logdir . "/" . $device . ".csv";
if ( $txt_data_log == 1 )
{
    $file = fopen($txt_data_logfile, 'a');
    #fputcsv($file, array('id','name','description'));
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

print("success\n");

?>
