<?php
	
	############################################################################
	# 	Meteotemplate
	# 	http://www.meteotemplate.com
	# 	Free website template for weather enthusiasts
	# 	Author: Jachym
	#           Brno, Czech Republic
	# 	First release: 2015
	#
	############################################################################
	#
	#	Database Update
	#
	############################################################################
	
	
	include("../config.php");
	include($baseURL."css/design.php");
	include($baseURL."header.php");
	
	session_start();
	if($_SESSION['user']!="admin"){
		echo "Unauthorized access.";
		die();
	}

    if(file_exists("../meteotemplateLive.txt")){
        $apiData = file_get_contents("../meteotemplateLive.txt");
        $apiData = json_decode($apiData,true);
    }

    if(!file_exists("apiSettings.txt")){
        $apiSetup['TIN'] = 0;
        $apiSetup['HIN'] = 0;
        $apiSetup['UV'] = 0;
        $apiSetup['T1'] = 0;
        $apiSetup['T2'] = 0;
        $apiSetup['T3'] = 0;
        $apiSetup['T4'] = 0;
        $apiSetup['H1'] = 0;
        $apiSetup['H2'] = 0;
        $apiSetup['H3'] = 0;
        $apiSetup['H4'] = 0;
        $apiSetup['TS1'] = 0;
        $apiSetup['TS2'] = 0;
        $apiSetup['TS3'] = 0;
        $apiSetup['TS4'] = 0;
        $apiSetup['SM1'] = 0;
        $apiSetup['SM2'] = 0;
        $apiSetup['SM3'] = 0;
        $apiSetup['SM4'] = 0;
        $apiSetup['LW1'] = 0;
        $apiSetup['LW2'] = 0;
        $apiSetup['LW3'] = 0;
        $apiSetup['LW4'] = 0;
        $apiSetup['LT1'] = 0;
        $apiSetup['LT2'] = 0;
        $apiSetup['LT3'] = 0;
        $apiSetup['LT4'] = 0;
        $apiSetup['L'] = 0;
		$apiSetup['LD'] = 0;
		$apiSetup['LT'] = 0;
        $apiSetup['NL'] = 0;
        $apiSetup['SS'] = 0;
        $apiSetup['SN'] = 0;
        $apiSetup['SD'] = 0;
        $apiSetup['CO2_1'] = 0;
        $apiSetup['CO2_2'] = 0;
        $apiSetup['CO2_3'] = 0;
        $apiSetup['CO2_4'] = 0;
        $apiSetup['NO2_1'] = 0;
        $apiSetup['NO2_2'] = 0;
        $apiSetup['NO2_3'] = 0;
        $apiSetup['NO2_4'] = 0;
        $apiSetup['SO2_1'] = 0;
        $apiSetup['SO2_2'] = 0;
        $apiSetup['SO2_3'] = 0;
        $apiSetup['SO2_4'] = 0;
        $apiSetup['O3_1'] = 0;
        $apiSetup['O3_2'] = 0;
        $apiSetup['O3_3'] = 0;
        $apiSetup['O3_4'] = 0;
        $apiSetup['CO_1'] = 0;
        $apiSetup['CO_2'] = 0;
        $apiSetup['CO_3'] = 0;
        $apiSetup['CO_4'] = 0;
        $apiSetup['PP1'] = 0;
        $apiSetup['PP2'] = 0;
        $apiSetup['PP3'] = 0;
        $apiSetup['PP4'] = 0;
    }
    else{
        $apiSetup = json_decode(file_get_contents("apiSettings.txt"),true);
    }
	
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $pageName?></title>
		<?php metaHeader()?>
		<style>
			.sectionDiv{
				display: none;
			}
			.firstCell{
				text-align:left;
				vertical-align:top;
				font-weight:bold;
				font-variant:small-caps;
				font-size:1.1em;
			}
			.secondCell{
				text-align:left;
				vertical-align:top;
			}
			.thirdCell{
				text-align:left;
				font-size:0.9em;
			}
			.dateTimeDiv{
				display:none;
				padding-top:10px;
				padding-bottom:10px;
			}
		</style>
	</head>
	<body>
		<div id="main_top">
			<?php bodyHeader();?>
			<?php include($baseURL."menu.php");?>
		</div>
		<div id="main">
			<div class="textDiv" style="width:90%;position:relative">
				<h1>Extra Table Setup</h1>
				<p>Depending on which sensors you have and which software/update type you use, there might be more parameters available in the API file than what is included in the main alldata table.</p>
                <p>This page allows you to set up an extra table in the database which will log the additional parameters. It is therefore necessary to first set up the normal database updates. This will create the API file with the current conditions with all the available parameters sent by whichever update type you use (Weather Display, Meteobridge, WeeWx, WeatherCat, NetAtmo, BloomSky, WeatherLink etc.).<p>
                <p>Below is list of all the variables found in this file (these are available for you right now) and their current values. If in the future additional data is available, you should see it here and you can add new columns to the table. Only enable those which have sensible values. Also note! The values are shown in the default API units (degrees C, mm, hPa etc.), this is how they will be saved in the extra table, however, on the page you will see the numbers in whatever "display units" you or the user specifies.</p>
				<br><br>
                <h2>API File</h2>
                <?php 
                    if(!isset($apiData)){
                ?>
                        <h3>API FILE NOT FOUND, MAKE SURE YOU FIRST SET UP THE DATABASE UPDATES FOR THE MAIN TEMPLATE TABLE.</h3>
                <?php
                    }
                    else{
                ?>
                        <table style="width:98%;margin:0 auto;table-layout:fixed" class="table">
                            <tr>
                                <th style="text-align:left">
                                    Parameter
                                </th>
                                <th style="text-align:center">
                                    Current value
                                </th>
                                <th>

                                </th>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Air temperature
                                </td>
                                <?php 
                                    if(array_key_exists("T",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['T']?>&deg;C
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Air humidity
                                </td>
                                <?php 
                                    if(array_key_exists("H",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['H']?>%
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Sea-level adjusted pressure
                                </td>
                                <?php 
                                    if(array_key_exists("P",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['P']?> hPa
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Wind speed
                                </td>
                                <?php 
                                    if(array_key_exists("W",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['W']?> km/h
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Wind gust
                                </td>
                                <?php 
                                    if(array_key_exists("G",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['G']?> km/h
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Wind direction
                                </td>
                                <?php 
                                    if(array_key_exists("B",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['B']?>&deg;
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Daily rain
                                </td>
                                <?php 
                                    if(array_key_exists("R",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['R']?> mm
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Rain rate
                                </td>
                                <?php 
                                    if(array_key_exists("RR",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['RR']?> mm/h
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Solar radiation
                                </td>
                                <?php 
                                    if(array_key_exists("S",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['S']?> W<sup>2</sup>
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Dew point
                                </td>
                                <?php 
                                    if(array_key_exists("D",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['D']?>&deg;C
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Apparent temperature
                                </td>
                                <?php 
                                    if(array_key_exists("A",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['A']?>&deg;C
                                        </td>
                                        <td>
                                            included in main template table
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Indoor temperature
                                </td>
                                <?php 
                                    if(array_key_exists("TIN",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['TIN']?>&deg;C
                                        </td>
                                        <td>
                                            <select class="button2" id="apiTIN">
                                                <option value="0" <?php if($apiSetup['TIN']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['TIN']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiTIN" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Indoor humidity
                                </td>
                                <?php 
                                    if(array_key_exists("HIN",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['HIN']?>%
                                        </td>
                                        <td>
                                            <select class="button2" id="apiHIN">
                                                <option value="0" <?php if($apiSetup['HIN']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['HIN']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiHIN" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    UV
                                </td>
                                <?php 
                                    if(array_key_exists("UV",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['UV']?>
                                        </td>
                                        <td>
                                            <select class="button2" id="apiUV">
                                                <option value="0" <?php if($apiSetup['UV']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['UV']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiUV" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Extra temperature sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("T".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['T'.$i]?>&deg;C
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiT<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['T'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['T'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiT<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Extra humidity sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("H".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['H'.$i]?>%
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiH<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['H'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['H'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiH<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Soil temperature <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("TS".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['TS'.$i]?>&deg;C
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiTS<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['TS'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['TS'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiTS<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Soil moisture <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("SM".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['SM'.$i]?>
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiSM<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['SM'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['SM'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiSM<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Leaf wetness <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("LW".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['LW'.$i]?>
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiLW<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['LW'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['LW'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiLW<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Leaf temperature <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("LT".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['LT'.$i]?>&deg;C
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiLT<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['LT'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['LT'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiLT<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <tr>
                                <td style="text-align:left">
                                    Snowfall
                                </td>
                                <?php 
                                    if(array_key_exists("SN",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['SN']?> mm
                                        </td>
                                        <td>
                                            <select class="button2" id="apiSN">
                                                <option value="0" <?php if($apiSetup['SN']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['SN']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiSN" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Snow depth
                                </td>
                                <?php 
                                    if(array_key_exists("SD",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['SD']?> mm
                                        </td>
                                        <td>
                                            <select class="button2" id="apiSD">
                                                <option value="0" <?php if($apiSetup['SD']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['SD']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiSD" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Noise level
                                </td>
                                <?php 
                                    if(array_key_exists("NL",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['NL']?> dB
                                        </td>
                                        <td>
                                            <select class="button2" id="apiNL">
                                                <option value="0" <?php if($apiSetup['NL']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['NL']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiNL" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Ligthning
                                </td>
                                <?php 
                                    if(array_key_exists("L",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['L']?>
                                        </td>
                                        <td>
                                            <select class="button2" id="apiL">
                                                <option value="0" <?php if($apiSetup['L']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['L']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiL" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td style="text-align:left">
                                    Ligthning distance
                                </td>
                                <?php 
                                    if(array_key_exists("LD",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['LD']?>
                                        </td>
                                        <td>
                                            <select class="button2" id="apiLD">
                                                <option value="0" <?php if($apiSetup['LD']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['LD']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiLD" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>	
                            <tr>
                                <td style="text-align:left">
                                    Ligthning time
                                </td>
                                <?php 
                                    if(array_key_exists("LT",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['LT']?>
                                        </td>
                                        <td>
                                            <select class="button2" id="apiLT">
                                                <option value="0" <?php if($apiSetup['LT']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['LT']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiLT" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>							
                            <tr>
                                <td style="text-align:left">
                                    Sunshine
                                </td>
                                <?php 
                                    if(array_key_exists("SS",$apiData)){
                                ?>
                                        <td style="text-align:center">
                                            <?php echo $apiData['SS']?> hours
                                        </td>
                                        <td>
                                            <select class="button2" id="apiSS">
                                                <option value="0" <?php if($apiSetup['SS']==0){ echo "selected";}?>>
                                                    Do not save in database 
                                                </option>
                                                <option value="1" <?php if($apiSetup['SS']==1){ echo "selected";}?>>
                                                    Save in database 
                                                </option>
                                            </select>
                                        </td>
                                <?php 
                                    }
                                    else{
                                ?>
                                        <td colspan="2" style="text-align:center">
                                            Not available
                                            <input type="hidden" id="apiSS" value="0">
                                        </td>
                                <?php
                                    }
                                ?>
                            </tr>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            CO<sub>2</sub> sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("CO2_".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['CO2_'.$i]?> ppm
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiCO2_<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['CO2_'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['CO2_'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiCO2_<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            NO<sub>2</sub> sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("NO2_".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['NO2_'.$i]?> ppm
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiNO2_<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['NO2_'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['NO2_'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiNO2_<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            CO sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("CO_".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['CO_'.$i]?> ppm
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiCO_<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['CO_'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['CO_'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiCO_<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            SO<sub>2</sub> sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("SO2_".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['SO2_'.$i]?> ppb
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiSO2_<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['SO2_'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['SO2_'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiSO2_<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Ozone sensor <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("O3_".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['O3_'.$i]?> ppb
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiO3_<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['O3_'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['O3_'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiO3_<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                            <?php
                                for($i=1;$i<=4;$i++){
                            ?>
                                    <tr>
                                        <td style="text-align:left">
                                            Particulate pollution <?php echo $i?>
                                        </td>
                                        <?php 
                                            if(array_key_exists("PP".$i,$apiData)){
                                        ?>
                                                <td style="text-align:center">
                                                    <?php echo $apiData['PP'.$i]?> ug/m<sup>3</sup>
                                                </td>
                                                <td>
                                                    <select class="button2" id="apiPP<?php echo $i?>">
                                                        <option value="0" <?php if($apiSetup['PP'.$i]==0){ echo "selected";}?>>
                                                            Do not save in database 
                                                        </option>
                                                        <option value="1" <?php if($apiSetup['PP'.$i]==1){ echo "selected";}?>>
                                                            Save in database 
                                                        </option>
                                                    </select>
                                                </td>
                                        <?php 
                                            }
                                            else{
                                        ?>
                                                <td colspan="2" style="text-align:center">
                                                    Not available
                                                    <input type="hidden" id="apiPP<?php echo $i?>" value="0">
                                                </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                            <?php 
                                }
                            ?>
                        </table>
                <?php
                    }
                ?>
                <br>
                <div style="width:98%;margin:0 auto;text-align:center">
					<input type="button" id="saveAPI" class="button2" style="font-size:1.2em;font-variant:small-caps;font-weight:bold;padding:5px" value="Save" onclick="saveAPI()">
				</div>
                <br><br>
			</div>
		</div>
		<?php include($baseURL."footer.php");?>
		<script>
			function saveAPI(){
                apiTIN = $("#apiTIN").val();
                apiHIN = $("#apiHIN").val();
                apiUV = $("#apiUV").val();
                apiSN = $("#apiSN").val();
                apiSD = $("#apiSD").val();
                apiL = $("#apiL").val();
				apiLD = $("#apiLD").val();
				apiLT = $("#apiLT").val();
                apiNL = $("#apiNL").val();
                apiSS = $("#apiSS").val();
                <?php
                    for($i=1;$i<=4;$i++){
                ?>
                        apiT<?php echo $i?> = $("#apiT<?php echo $i?>").val();
                        apiH<?php echo $i?> = $("#apiH<?php echo $i?>").val();
                        apiTS<?php echo $i?> = $("#apiTS<?php echo $i?>").val();
                        apiLW<?php echo $i?> = $("#apiLW<?php echo $i?>").val();
                        apiLT<?php echo $i?> = $("#apiLT<?php echo $i?>").val();
                        apiSM<?php echo $i?> = $("#apiSM<?php echo $i?>").val();
                        apiCO2_<?php echo $i?> = $("#apiCO2_<?php echo $i?>").val();
                        apiNO2_<?php echo $i?> = $("#apiNO2_<?php echo $i?>").val();
                        apiSO2_<?php echo $i?> = $("#apiSO2_<?php echo $i?>").val();
                        apiCO_<?php echo $i?> = $("#apiCO_<?php echo $i?>").val();
                        apiPP<?php echo $i?> = $("#apiPP<?php echo $i?>").val();
                        apiO3_<?php echo $i?> = $("#apiO3_<?php echo $i?>").val();
                <?php 
                    }
                ?>
                saveURL = "saveAPISettings.php?";
                saveURL += "TIN=" + apiTIN;
                saveURL += "&HIN=" + apiHIN;
                saveURL += "&UV=" + apiUV;
                saveURL += "&SN=" + apiSN;
                saveURL += "&SD=" + apiSD;
                saveURL += "&L=" + apiL;
				saveURL += "&LD=" + apiLD;
				saveURL += "&LT=" + apiLT;
                saveURL += "&NL=" + apiNL;
                saveURL += "&SS=" + apiSS;
                <?php
                    for($i=1;$i<=4;$i++){
                ?>
                        saveURL += "&T<?php echo $i?>=" + apiT<?php echo $i?>;
                        saveURL += "&H<?php echo $i?>=" + apiH<?php echo $i?>;
                        saveURL += "&TS<?php echo $i?>=" + apiTS<?php echo $i?>;
                        saveURL += "&LW<?php echo $i?>=" + apiLW<?php echo $i?>;
                        saveURL += "&LT<?php echo $i?>=" + apiLT<?php echo $i?>;
                        saveURL += "&SM<?php echo $i?>=" + apiSM<?php echo $i?>;
                        saveURL += "&CO2_<?php echo $i?>=" + apiCO2_<?php echo $i?>;
                        saveURL += "&NO2_<?php echo $i?>=" + apiNO2_<?php echo $i?>;
                        saveURL += "&SO2_<?php echo $i?>=" + apiSO2_<?php echo $i?>;
                        saveURL += "&CO_<?php echo $i?>=" + apiCO_<?php echo $i?>;
                        saveURL += "&PP<?php echo $i?>=" + apiPP<?php echo $i?>;
                        saveURL += "&O3_<?php echo $i?>=" + apiO3_<?php echo $i?>;
                <?php
                    }
                ?>
                window.open(saveURL);
            }
		</script>
	</body>
</html>
		