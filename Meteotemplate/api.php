<?php

    ############################################################################
    #
    #   Meteotemplate
    #   http://www.meteotemplate.com
    #   Free website template for weather enthusiasts
    #   Author: Jachym
    #           Brno, Czech Republic
    #   First release: 2015
    #
    ############################################################################
    #           
    #   v2.0    2017-04-20  Now also handle data of update programs
    #   v2.1    2017-04-24  Handling of fallen rain between 23:55 and 00:00:00
    #   v2.2    2017-07-02  Added Extra sensors; fixed bug rain at begin of day
    #   v3.0    2017-08-11  Added multipliers
    #   v3.1    2017-12-20  Discard outdated parameters; added mysql error logging
    #
    ############################################################################

    //error_reporting(E_ALL);
    $apiLog = array();
    $rawInput = array();
    $utc = time();
    $timeId = "Time";
    
    if(isset($apiUpdate)){
        ############################################################################
        // api included by CUMULUS, WU, CUSTOM, NETATMO, WLIP
        ############################################################################
            
        $rawUpdate['SW'] = $apiUpdate;
        $apiLog['info'][] = "Handling data from ".$apiUpdate;
        // load data
        foreach ($rawUpdate as $key => $value) {
            $rawInput[$key] = $value;
            if($key == 'U'){
                $apiLog['info'][] = "update 'U' = ".$rawInput['U']." (".date("Y-m-d H:i:s",$rawInput['U']).")";
            }
            else{
                $apiLog['info'][] = "update '".$key."' = ".$rawInput[$key];
            }
        }
    }
    else{
        ############################################################################
        // api section called by http
        ############################################################################
        $base = "";
        // load main info
        require($base."config.php");

        // load dependencies
        require($base."scripts/functions.php");
        
        // check acces authorization
        if(isset($_GET['PASS'])){
            $password = $_GET['PASS'];
        }
        else if(isset($_GET['PASSWORD'])){
            $password = $_GET['PASSWORD'];
        }
        else{
            die("No password provided");
        }
        // check if password is correct
        if($password!=$updatePassword){
            if($password==$adminPassword){ // if admin password provided accept, but notify
                echo "Authorized via admin password";
            }
            else{
                die("Unauthorized");
            }
        }
        $apiLog['info'][] = "Authorized access.";
        $apiLog['info'][] = "Current date/time: ".date("Y-m-d H:i:s");
        
        $apiLog['info'][] = "api called by http";
        
        // read raw input
        if(isset($_GET)){
            foreach($_GET as $urlParameter=>$value){
                if($urlParameter!="PASSWORD" && $urlParameter!="PASS"){
                    $rawInput[$urlParameter] = $value;
                    $apiLog['info'][] = "update ".$urlParameter.": ".$value;
                }
            }
        }
        else{
            // no data in api
            $apiLog['error'][] = "api: no data";
            generateAPILog();
            die("api: no data");
        }
        ############################################################################
        // end of api section
        ############################################################################
    }
    
    ############################################################################
    // common part for both:
    // apiUpdate section called by /update/update.php
    // api section called by http
    ############################################################################
    
    $apiLog['info'][] = "Begin of common part of api script";
    
    ############################################################################
    #                            HANDLING LIVE DATA   
    ############################################################################
    
    $apiLog['info'][] = "Start handling live data";
    
    // Preset time of live update
    foreach ($rawInput as $key => $value) {
        $rawInput[$key.$timeId] = $utc;
    }

    // multipliers
    if($multiplierT != 0 && isset($rawInput['T'])){
        $rawInput["T"] = $rawInput["T"] + $multiplierT;
    }
    if($multiplierP != 0 && isset($rawInput['P'])){
        $rawInput["P"] = $rawInput["P"] + $multiplierP;
    }
    if($multiplierW != 1 && isset($rawInput['W'])){
        $rawInput["W"] = $rawInput["W"] * $multiplierW;
    }
    if($multiplierR != 1 && isset($rawInput['R'])){
        $rawInput["R"] = $rawInput["R"] * $multiplierR;
    }
    if($multiplierW != 1 && isset($rawInput['G'])){
        $rawInput["G"] = $rawInput["G"] * $multiplierW;
    }
    if($multiplierR != 1 && isset($rawInput['RR'])){
        $rawInput["RR"] = $rawInput["RR"] * $multiplierR;
    }

    // Check if extra sensors should be loaded
    $apiLog['info'][] = "Checking if extra sensors should be logged";
    $extraSensors = array();
    if(file_exists($base."update/apiSettings.txt")){
        $extraAPIRaw = json_decode(file_get_contents($base."update/apiSettings.txt"),true);
        foreach($extraAPIRaw as $extraParam=>$extraValue){
            if($extraValue==1){
                $extraSensors[] = $extraParam;
            }
        }
    }
    else{
        $apiLog['info'][] = "Extra sensor settings file not found.";
    }

    $apiLog['info'][] = "Extra sensors data to save in db: " . implode(", ",$extraSensors);


    // read previous live data
    if(file_exists($base."meteotemplateLive.txt")){
        // get latest date from cache file
        $mtLive = file_get_contents($base."meteotemplateLive.txt");
        $liveInput = json_decode($mtLive,true);

        if(array_key_exists("R".$timeId,$liveInput)){ // check if rain is from yesterday, if so, delete it
            $dayRain = date("Ymd",$liveInput['R'.$timeId]);
            $dayNow = date("Ymd");
            if($dayRain!=$dayNow){
                unset($liveInput['R']);
                unset($liveInput['RTime']);
            }
        }
        
        foreach ($liveInput as $key => $value) {
            $pos = strpos($key,$timeId);
            if ($pos > 0) {
                // time parameter
                if($utc-$value > 600) {
                    // value more than 10 minutes ago; skip writing back
                    $apiLog['info'][] = "Variable too old, remove from meteotemplateLive.txt: ".$key.": ".date("Y-m-d H:i:s",$value);
                    $keyValue = substr($key,0,$pos);
                    // remove value and timestamp from array
                    unset($liveInput[$keyValue]);  // remove value
                    unset($liveInput[$key]);       // remove timestamp
                }  
            }
            else {
                // value parameter
                $liveInput[$key] = $value;
            }
        }
    }
    // convert UGP to P when needed
    if(isset($rawInput['UGP']) && !isset($rawInput['P'])){
        $apiLog['info'][] = "convert UGP: ".$rawInput['UGP'];
        // get station elevation from config
        // $stationElevation
        // $stationElevationUnits - m/ft 
        // define constants for SLP calculations

        // convert elevation to m if in ft
        if($stationElevationUnits=="ft"){
            $elevationM = $stationElevation * 0.3048;
        }
        else{
            $elevationM = $stationElevation;
        }
        $apiLog['info'][] = "elevationM: ".$elevationM;
        $apiLog['info'][] = "liveInput['T']: ".$liveInput['T'];
        if(isset($liveInput['T'])){ // temperature is available, use more accurate formula
            $temperatureK = $liveInput['T'] + 273.15; // temperature in K
            $constant['g'] = 9.80665; // Earth-surface gravitational constant [m/s2]
            $constant['M'] = 0.0289644; // molar mass of dry air [kg/mol]
            $constant['R'] = 8.31447; // universal gas constant
            $partA = ($constant['g'] * $constant['M'] * $elevationM) / ($constant['R'] * $temperatureK);
            $convertedP = $rawInput['UGP'] / exp(- $partA);
            $apiLog['info'][] = "more accurate convertedP: ".$convertedP;
        }
        else{ // temperature is N/A use less accurate formula
            $convertedP = $rawInput['UGP'] - ($elevationM/9.2);
            $apiLog['info'][] = "less accurate convertedP: ".$convertedP;
        }
        $rawInput['P'] = (string)(round($convertedP,3));
        $rawInput['P'.$timeId] = $utc;
        $apiLog['info'][] = "calculated P: ".$rawInput['P'];
        // multiplier for converted UGP to P
        if($multiplierP != 0 && isset($rawInput['P'])){
            $rawInput["P"] = $rawInput["P"] + $multiplierP;
        }
    }
    
    // add saved live data fields for not updated fields
    foreach ($liveInput as $key => $value) {
        if(!isset($rawInput[$key])){
            // if live data key not present use saved live data
            $rawInput[$key] = $value;
            // time fields ($rawInput[$key.$timeId]) will be copied too
            $apiLog['info'][] = "add live data: ".$key.": ".$value;
        }
    }
    
    // calculate dewpoint
    $rawInput['D'] = (string)dewpoint($rawInput['T'], $rawInput['H']);
    $rawInput['D'.$timeId] = $utc;
    $apiLog['info'][] = "calculated D: ".$rawInput['D'];
    
    // calculate apparent temp
    $rawInput['A'] = (string)apparent($rawInput['T'], $rawInput['H'], $rawInput['W'] / 3.6);
    $rawInput['A'.$timeId] = $utc;
    $apiLog['info'][] = "calculated A: ".$rawInput['A'];
    
    // save raw input
    $rawOutput = json_encode($rawInput,JSON_NUMERIC_CHECK);
    $apiLog['info'][] = "Save meteotemplateLive.txt: ".$rawOutput;
    file_put_contents($base."meteotemplateLive.txt",$rawOutput);

    // check we have write permissions for the cache folder and meteotemplateLive.txt
    if(!file_exists($base."meteotemplateLive.txt")){
        $apiLog['error'][] = "meteotemplateLive.txt was not created! Probably incorrect permissions for the template root folder.";
        echo "Live conditions file not created. Check permissions!";
    }

    ############################################################################
    #                               PARSING
    ############################################################################

    $apiLog['info'][] = "Start data parsing";
    $convertUgp = false;

    // save live data fields to raw data
    foreach ($rawInput as $key => $value) {
        $rawData[$key] = $value;
    }
    // ESSENTIAL
    // Date and time
    $apiLog['info'][] = "Parsing date; Server time: ".date("Y-m-d H:i:s");
    $valid['date'] = true;

   if(!isset($rawData['U'])){
       $valid['date'] = false;
       $apiLog['error'][] = "No value for date specified.";
   }
   else{
       // date must be passed as UNIX timestamp
       // checking: date must be numeric, must be after 1990 and must not be in the future
       $apiLog['info'][] = "Validating date: ".$rawData['U']." (".date("Y-m-d H:i:s",$rawData['U']).")";
       if(is_numeric($rawData['U'])){
           if($rawData['U']>631152000 && $rawData['U']<(time()+10*60)){ // 10 minutes tolerance
               $data['timestamp'] = ($rawData['U']); // also save the timestamp
               $data['date'] = date("Y-m-d H:i:s",$rawData['U']); // convert to MySQL accepted format
               $apiLog['info'][] = "Date is valid; difference with server time is ".($rawData['U'] - $utc)." s" ;
           }
           else{
               $valid['date'] = false;
               $apiLog['error'][] = "Date is invalid; difference with server time is ".($rawData['U'] - $utc)." s";
           }
       }
       else{
           $valid['date'] = false;
           $apiLog['error'][] = "Date is invalid (not numeric)";
       }
   }

    ############################################################################
    // Parameters
    ############################################################################


    ############################################################################
    // Outside Temperature
    $apiLog['info'][] = "Parsing temperature";
    $valid['T'] = true;
    if(!isset($rawData['T'])){
        $valid['T'] = false;
        $apiLog['error'][] = "No temperature data provided.";
    }
    else{
        $rawT = trim($rawData['T']);
        $apiLog['info'][] = "Temperature: ".$rawT." C";
        if($rawT===""){
            $valid['T'] = false;
            $apiLog['error'][] = "Temperature field blank.";
        }
        elseif(!is_numeric($rawT)){
            $valid['T'] = false;
            $apiLog['error'][] = "Temperature is not a number.";
        }
    }
    // apply conversion - API uses Celsius
    if($valid['T']){
        $apiLog['info'][] = "Database units: ".$dataTempUnits;
        if($dataTempUnits!="C"){
            $rawT = convertor($rawT, "C", $dataTempUnits);
            $apiLog['info'][] = "Temperature converted to: ".$rawT." ".$dataTempUnits;
        }
        // check limits
        $apiLog['info'][] = "Checking temperature is between limits specified in template Main settings.";
        $apiLog['info'][] = "Minimum temperature limit: ".$limitTempMin." ".$dataTempUnits;
        $apiLog['info'][] = "Maximum temperature limit: ".$limitTempMax." ".$dataTempUnits;
        if($rawT>=$limitTempMin && $rawT<=$limitTempMax){
            $apiLog['info'][] = "Temperature is OK and within the allowed limits";
            $data['T'] = $rawT;
        }
        else{
            $apiLog['error'][] = "Temperature is outside the allowed limits.";
            $valid['T'] = false;
        }
    }

    ############################################################################
    // Outside Max Temperature
    $apiLog['info'][] = "Parsing maximum temperature";
    $valid['Tmax'] = true;
    if(!isset($rawData['TMX'])){
        $valid['Tmax'] = false;
        $apiLog['error'][] = "No max temperature data provided.";
    }
    else{
        $rawTmax = trim($rawData['TMX']);
        $apiLog['info'][] = "Max Temperature: ".$rawTmax." C";
        if($rawTmax===""){
            $valid['Tmax'] = false;
            $apiLog['error'][] = "Max Temperature field blank.";
        }
        elseif(!is_numeric($rawTmax)){
            $valid['Tmax'] = false;
            $apiLog['error'][] = "Max Temperature is not a number.";
        }
    }
    // apply conversion - API uses Celsius
    if($valid['Tmax']){
        $apiLog['info'][] = "Database units: ".$dataTempUnits;
        if($dataTempUnits!="C"){
            $rawTmax = convertor($rawTmax, "C", $dataTempUnits);
            $apiLog['info'][] = "Temperature converted to: ".$rawTmax." ".$dataTempUnits;
        }
        // check limits
        $apiLog['info'][] = "Checking max temperature is between limits specified in template Main settings.";
        $apiLog['info'][] = "Minimum temperature limit: ".$limitTempMin." ".$dataTempUnits;
        $apiLog['info'][] = "Maximum temperature limit: ".$limitTempMax." ".$dataTempUnits;
        if($rawTmax>=$limitTempMin && $rawTmax<=$limitTempMax){
            $apiLog['info'][] = "Max Temperature is OK and within the allowed limits";
            $data['Tmax'] = $rawTmax;
        }
        else{
            $apiLog['error'][] = "Max Temperature is outside the allowed limits.";
            $valid['Tmax'] = false;
        }
    }

    ############################################################################
    // Outside Min Temperature
    $apiLog['info'][] = "Parsing minimum temperature";
    $valid['Tmin'] = true;
    if(!isset($rawData['TMN'])){
        $valid['Tmin'] = false;
        $apiLog['error'][] = "No min temperature data provided.";
    }
    else{
        $rawTmin = trim($rawData['TMN']);
        $apiLog['info'][] = "Min Temperature: ".$rawTmin." C";
        if($rawTmin===""){
            $valid['Tmin'] = false;
            $apiLog['error'][] = "Min Temperature field blank.";
        }
        elseif(!is_numeric($rawTmin)){
            $valid['Tmin'] = false;
            $apiLog['error'][] = "Min Temperature is not a number.";
        }
    }
    // apply conversion - API uses Celsius
    if($valid['Tmin']){
        $apiLog['info'][] = "Database units: ".$dataTempUnits;
        if($dataTempUnits!="C"){
            $rawTmin = convertor($rawTmin, "C", $dataTempUnits);
            $apiLog['info'][] = "Temperature converted to: ".$rawTmin." ".$dataTempUnits;
        }
        // check limits
        $apiLog['info'][] = "Checking min temperature is between limits specified in template Main settings.";
        $apiLog['info'][] = "Minimum temperature limit: ".$limitTempMin." ".$dataTempUnits;
        $apiLog['info'][] = "Maximum temperature limit: ".$limitTempMax." ".$dataTempUnits;
        if($rawTmin>=$limitTempMin && $rawTmin<=$limitTempMax){
            $apiLog['info'][] = "Min Temperature is OK and within the allowed limits";
            $data['Tmin'] = $rawTmin;
        }
        else{
            $apiLog['error'][] = "Min Temperature is outside the allowed limits.";
            $valid['Tmin'] = false;
        }
    }

    ############################################################################
    // Humidity
    $apiLog['info'][] = "Parsing humidity";
    $valid['H'] = true;
    if(!isset($rawData['H'])){
        $valid['H'] = false;
        $apiLog['error'][] = "No humidity data provided.";
    }
    else{
        $rawH = trim($rawData['H']);
        $apiLog['info'][] = "Humidity: ".$rawH." percent";
        if($rawH===""){
            $valid['H'] = false;
            $apiLog['error'][] = "Humidity field blank.";
        }
        elseif(!is_numeric($rawH)){
            $valid['H'] = false;
            $apiLog['error'][] = "Humidity is not a number.";
        }
    }

    if($valid['H']){
        // discard nonsense
        if($rawH<0 && $rawH>100){
            $valid['H'] = false;
            $apiLog['error'][] = "Humidity is outside sensible range (0-100%).";
        }
        else{
            // check limits from main settings
            $apiLog['info'][] = "Checking humidity is between limits specified in template Main settings.";
            $apiLog['info'][] = "Minimum humidity limit: ".$limitHumidityMin." %";
            $apiLog['info'][] = "Maximum humidity limit: ".$limitHumidityMax." %";
            if($rawH>=$limitHumidityMin && $rawH<=$limitHumidityMax){
                $apiLog['info'][] = "Humidity is OK and within the allowed limits";
                $data['H'] = $rawH;
            }
            else{
                $apiLog['error'][] = "Humidity is outside the allowed limits.";
                $valid['H'] = false;
            }
        }
    }

    ############################################################################
    // Wind Speed
    $apiLog['info'][] = "Parsing wind speed";
    $valid['W'] = true;
    if(!isset($rawData['W'])){
        $valid['W'] = false;
        $apiLog['error'][] = "No wind speed data provided.";
    }
    else{
        $rawW = trim($rawData['W']);
        $apiLog['info'][] = "Wind speed: ".$rawW." kmh";
        if($rawW===""){
            $valid['W'] = false;
            $apiLog['error'][] = "Wind speed field blank.";
        }
        elseif(!is_numeric($rawW)){
            $valid['W'] = false;
            $apiLog['info'][] = "Wind speed is not a number.";
        }
    }
    // apply conversion - API uses km/h
    if($valid['W']){
        $apiLog['info'][] = "Database wind speed units: ".$dataWindUnits;
        if($dataWindUnits!="kmh"){
            $rawW = convertor($rawW, "kmh", $dataWindUnits);
            $apiLog['info'][] = "Wind speed  converted to: ".$rawW." ".$dataWindUnits;
        }
        // discard nonsense
        if($rawW<0){
            $valid['W'] = false;
            $apiLog['error'][] = "Wind speed cannot be negative.";
        }
        else{
            // check limits
            $apiLog['info'][] = "Checking wind speed is between limits specified in template Main settings.";
            $apiLog['info'][] = "Minimum wind speed limit: ".$limitWindMin." ".$dataWindUnits;
            $apiLog['info'][] = "Maximum wind speed limit: ".$limitWindMax." ".$dataWindUnits;
            if($rawW>=$limitWindMin && $rawW<=$limitWindMax){
                $apiLog['info'][] = "Wind speed is OK and within the allowed limits";
                $data['W'] = $rawW;
            }
            else{
                $apiLog['error'][] = "Wind speed is outside the allowed limits.";
                $valid['W'] = false;
            }
        }
    }

    ############################################################################
    // Wind Gust
    $apiLog['info'][] = "Parsing wind gust";
    $valid['G'] = true;
    if(!isset($rawData['G'])){
        $valid['G'] = false;
        $apiLog['error'][] = "No wind gust data provided.";
    }
    else{
        $rawG = trim($rawData['G']);
        $apiLog['info'][] = "Wind gust: ".$rawG." kmh";
        if($rawG===""){
            $valid['G'] = false;
            $apiLog['error'][] = "Wind gust field blank.";
        }
        elseif(!is_numeric($rawG)){
            $valid['G'] = false;
            $apiLog['error'][] = "Wind gust is not a number.";
        }
    }
    // apply conversion - API uses km/h
    if($valid['G']){
        $apiLog['info'][] = "Database wind units: ".$dataWindUnits;
        if($dataWindUnits!="kmh"){
            $rawG = convertor($rawG, "kmh", $dataWindUnits);
            $apiLog['info'][] = "Wind gust  converted to: ".$rawG." ".$dataWindUnits;
        }
        // discard nonsense
        if($rawG<0){
            $valid['G'] = false;
            $apiLog['error'][] = "Wind gust cannot be negative.";
        }
        else{
            // check limits
            $apiLog['info'][] = "Checking wind gust is between limits specified in template Main settings.";
            $apiLog['info'][] = "Minimum wind gust limit: ".$limitWindMin." ".$dataWindUnits;
            $apiLog['info'][] = "Maximum wind gust limit: ".$limitWindMax." ".$dataWindUnits;
            if($rawG>=$limitWindMin && $rawG<=$limitWindMax){
                $apiLog['info'][] = "Wind gust is OK and within the allowed limits";
                $data['G'] = $rawG;
            }
            else{
                $apiLog['error'][] = "Wind gust is outside the allowed limits.";
                $valid['G'] = false;
            }
        }
    }

    ############################################################################
    // Wind direction (bearing)
    $apiLog['info'][] = "Parsing wind direction";
    $valid['B'] = true;
    if(!isset($rawData['B'])){
        $valid['B'] = false;
        $apiLog['error'][] = "No wind direction data provided.";
    }
    else{
        $rawB = trim($rawData['B']);
        $apiLog['info'][] = "Wind direction: ".$rawB." degrees";
        if($rawB===""){
            $valid['B'] = false;
            $apiLog['error'][] = "Wind direction field blank.";
        }
        elseif(!is_numeric($rawB)){
            $valid['B'] = false;
            $apiLog['error'][] = "Wind direction is not a number.";
        }
    }

    if($valid['B']){
        // discard nonsense
        if($rawB<0 && $rawB>360){
            $valid['B'] = false;
            $apiLog['error'][] = "Wind direction is outside sensible range (0-360 degrees).";
        }
        else{
            $apiLog['info'][] = "Wind direction is OK and within the allowed limits";
            $data['B'] = $rawB;
        }
    }

    ############################################################################
    // Precipitation
    $apiLog['info'][] = "Parsing daily cumulative precipitation";
    $valid['R'] = true;
    if(!isset($rawData['R'])){
        $valid['R'] = false;
        $apiLog['error'][] = "No precipitation data provided.";
    }
    else{
        $rawR = trim($rawData['R']);
        $apiLog['info'][] = "Precipitation: ".$rawR." mm";
        if($rawR===""){
            $valid['R'] = false;
            $apiLog['error'][] = "Precipitation field blank.";
        }
        elseif(!is_numeric($rawR)){
            $valid['R'] = false;
            $apiLog['error'][] = "Precipitation is not a number.";
        }
    }
    // apply conversion - API uses mm
    if($valid['R']){
        $apiLog['info'][] = "Database precipitation units: ".$dataRainUnits;
        if($dataRainUnits!="mm"){
            $rawR = convertor($rawR, "mm", $dataRainUnits);
            $apiLog['info'][] = "Precipitation  converted to: ".$rawR." ".$dataRainUnits;
        }
        // discard nonsense
        if($rawR<0){
            $valid['R'] = false;
            $apiLog['error'][] = "Precipitation cannot be negative.";
        }
        else{
            // check limits
            $apiLog['info'][] = "Checking precipitation is between limits specified in template Main settings.";
            $apiLog['info'][] = "Minimum precipitation limit: ".$limitRainMin." ".$dataRainUnits;
            $apiLog['info'][] = "Maximum precipitation limit: ".$limitRainMax." ".$dataRainUnits;
            if($rawR>=$limitRainMin && $rawR<=$limitRainMax){
                $apiLog['info'][] = "Precipitation is OK and within the allowed limits";
                $data['R'] = $rawR;
            }
            else{
                $apiLog['error'][] = "Precipitation is outside the allowed limits.";
                $valid['R'] = false;
            }
        }
    }

    ############################################################################
    // Rain rate
    $apiLog['info'][] = "Parsing rain rate";
    $valid['RR'] = true;
    if(!isset($rawData['RR'])){
        $valid['RR'] = false;
        $apiLog['error'][] = "No rain rate data provided.";
    }
    else{
        $rawRR = trim($rawData['RR']);
        $apiLog['info'][] = "Rain rate: ".$rawRR." mm/h";
        if($rawRR===""){
            $valid['RR'] = false;
            $apiLog['error'][] = "Rain rate field blank.";
        }
        elseif(!is_numeric($rawR)){
            $valid['RR'] = false;
            $apiLog['error'][] = "Rain rate is not a number.";
        }
    }
    // apply conversion - API uses mm/h
    if($valid['RR']){
        $apiLog['info'][] = "Database rain units: ".$dataRainUnits."/h";
        if($dataRainUnits!="mm"){
            $rawRR = convertor($rawRR, "mm", $dataRainUnits);
            $apiLog['info'][] = "Rain rate  converted to: ".$rawRR." ".$dataRainUnits."/h";
        }
        // discard nonsense
        if($rawRR<0){
            $valid['RR'] = false;
            $apiLog['error'][] = "Rain rate cannot be negative.";
        }
        else{
            // check limits
            $apiLog['info'][] = "Checking rain rate is between limits specified in template Main settings.";
            $apiLog['info'][] = "Minimum rain rate limit: ".$limitRainRateMin." ".$dataRainUnits."/h";
            $apiLog['info'][] = "Maximum rain rate limit: ".$limitRainRateMax." ".$dataRainUnits."/h";
            if($rawRR>=$limitRainRateMin && $rawRR<=$limitRainRateMax){
                $apiLog['info'][] = "Rain rate is OK and within the allowed limits";
                $data['RR'] = $rawRR;
            }
            else{
                $apiLog['error'][] = "Rain rate is outside the allowed limits.";
                $valid['RR'] = false;
            }
        }
    }

    ############################################################################
    // Solar radiation
    $valid['S'] = true;
    if($solarSensor){
        $apiLog['info'][] = "Parsing solar radiation";
        if(!isset($rawData['S'])){
            $valid['S'] = false;
            $apiLog['error'][] = "No solar radiation data provided.";
        }
        else{
            $rawS = trim($rawData['S']);
            $apiLog['info'][] = "Solar radiation: ".$rawS." w/m2";
            if($rawS===""){
                $valid['S'] = false;
                $apiLog['error'][] = "Solar radiation field blank.";
            }
            elseif(!is_numeric($rawS)){
                $valid['S'] = false;
                $apiLog['error'][] = "Solar radiation is not a number.";
            }
        }

        if($valid['S']){
            // discard nonsense
            if($rawS<0){
                $valid['S'] = false;
                $apiLog['error'][] = "Solar radiation cannot be negative.";
            }
            else{
                // check limits from main settings
                $apiLog['info'][] = "Checking solar radiation is between limits specified in template Main settings.";
                $apiLog['info'][] = "Minimum solar radiation limit: ".$limitSolarMin." W/m2";
                $apiLog['info'][] = "Maximum solar radiation limit: ".$limitSolarMax." W/m2";
                if($rawS>=$limitSolarMin && $rawS<=$limitSolarMax){
                    $apiLog['info'][] = "Solar radiation is OK and within the allowed limits";
                    $data['S'] = $rawS;
                }
                else{
                    $apiLog['error'][] = "Solar radiation is outside the allowed limits.";
                    $valid['S'] = false;
                }
            }
        }
    }
    else{
        $valid['S'] = false;
        $apiLog['info'][] = "Solar radiation sensor disabled in Main settings - skipping.";
    }

    ############################################################################
    // EXTRA SENSORS
    ############################################################################
    // Now parse extra sensors
    if(count($extraSensors)>0){
        $extraQueryParams = array();
        $extraQueryValues = array();
         $apiLog['info'][] = "Now parsing extra sensors.";
         foreach($extraSensors as $extraSensor){
             if($extraSensor == "TIN"){
                 $thisSensorName = "indoor temperature";
                 $thisSensorLimits = array(1,50);
                 $thisSensorUnits = "deg C";
                 $thisSensorDecimals = 1;
             }
             if($extraSensor == "HIN"){
                 $thisSensorName = "indoor humidity";
                 $thisSensorLimits = array(0.01,100);
                 $thisSensorUnits = "%";
                 $thisSensorDecimals = 1;
             }
             if($extraSensor == "SN"){
                 $thisSensorName = "daily snowfall";
                 $thisSensorLimits = array(0,5000);
                 $thisSensorUnits = "mm";
                 $thisSensorDecimals = 0;
             }
             if($extraSensor == "SD"){
                 $thisSensorName = "snow depth";
                 $thisSensorLimits = array(0,10000);
                 $thisSensorUnits = "mm";
                 $thisSensorDecimals = 0;
             }
             if($extraSensor == "NL"){
                 $thisSensorName = "noise level";
                 $thisSensorLimits = array(0,200);
                 $thisSensorUnits = "dB";
                 $thisSensorDecimals = 1;
             }
             if($extraSensor == "L"){
                 $thisSensorName = "lightning";
                 $thisSensorLimits = array(0,1000);
                 $thisSensorUnits = "";
                 $thisSensorDecimals = 0;
             }
             if($extraSensor == "LD"){
                 $thisSensorName = "lightning distance";
                 $thisSensorLimits = array(0,100);
                 $thisSensorUnits = "";
                 $thisSensorDecimals = 0;
             }
             if($extraSensor == "LT"){
                 $thisSensorName = "lightning time";
                 $thisSensorLimits = array(0,10000000000);
                 $thisSensorUnits = "";
                 $thisSensorDecimals = 0;
             }			 
             if($extraSensor == "SS"){
                 $thisSensorName = "sunshine";
                 $thisSensorLimits = array(0,24);
                 $thisSensorUnits = "h";
                 $thisSensorDecimals = 1;
             }
             if($extraSensor == "UV"){
                 $thisSensorName = "UV";
                 $thisSensorLimits = array(0,20);
                 $thisSensorUnits = "";
                 $thisSensorDecimals = 1;
             }
             //$for($g=1;$g<=4;$g++){
             for($g=1;$g<=4;$g++){
                if($extraSensor == "T".$g){
                    $thisSensorName = "extra temperature sensor ".$g;
                    $thisSensorLimits = array(-60,60);
                    $thisSensorUnits = "deg C";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "H".$g){
                    $thisSensorName = "extra humidity sensor ".$g;
                    $thisSensorLimits = array(0.01,100);
                    $thisSensorUnits = "%";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "TS".$g){
                    $thisSensorName = "soil temperature sensor ".$g;
                    $thisSensorLimits = array(-60,80);
                    $thisSensorUnits = "deg C";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "SM".$g){
                    $thisSensorName = "soil moisture sensor ".$g;
                    $thisSensorLimits = array(0.01,200);
                    $thisSensorUnits = "";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "LT".$g){
                    $thisSensorName = "leaf temperature sensor ".$g;
                    $thisSensorLimits = array(-60,80);
                    $thisSensorUnits = "deg C";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "LW".$g){
                    $thisSensorName = "leaf wetness sensor ".$g;
                    $thisSensorLimits = array(0,15);
                    $thisSensorUnits = "";
                    $thisSensorDecimals = 1;
                }
                if($extraSensor == "CO2_".$g){
                    $thisSensorName = "CO2 sensor ".$g;
                    $thisSensorLimits = array(300,600);
                    $thisSensorUnits = "ppm";
                    $thisSensorDecimals = 0;
                }
                if($extraSensor == "CO_".$g){
                    $thisSensorName = "CO sensor ".$g;
                    $thisSensorLimits = array(0.1,50);
                    $thisSensorUnits = "ppm";
                    $thisSensorDecimals = 0;
                }
                if($extraSensor == "NO2_".$g){
                    $thisSensorName = "NO2 sensor ".$g;
                    $thisSensorLimits = array(0,10);
                    $thisSensorUnits = "ppm";
                    $thisSensorDecimals = 0;
                }
                if($extraSensor == "SO2_".$g){
                    $thisSensorName = "SO2 sensor ".$g;
                    $thisSensorLimits = array(0,1000);
                    $thisSensorUnits = "ppb";
                    $thisSensorDecimals = 0;
                }
                if($extraSensor == "O3_".$g){
                    $thisSensorName = "O3 sensor ".$g;
                    $thisSensorLimits = array(0,100);
                    $thisSensorUnits = "ppb";
                    $thisSensorDecimals = 0;
                }
                if($extraSensor == "PP".$g){
                    $thisSensorName = "particulate pollution ".$g;
                    $thisSensorLimits = array(0,1000);
                    $thisSensorUnits = "ug/m3";
                    $thisSensorDecimals = 0;
                }
             }
             if(isset($rawData[$extraSensor])){
                $thisSensorVal = $rawData[$extraSensor];
                $apiLog['info'][] = "Sensor ".$thisSensorName." raw value: " . $thisSensorVal . " " . $thisSensorUnits;
                $apiLog['info'][] = "Limits for this sensor: " . $thisSensorLimits[0] ." to " . $thisSensorLimits[1];
                if($thisSensorVal >= $thisSensorLimits[0] && $thisSensorValue <= $thisSensorLimits[1] && is_numeric($thisSensorVal)){
                    $apiLog['info'][] = "Sensor data within acceptable limits.";
                    $extraQueryParams[] = $extraSensor;
                    $extraQueryValues[] = number_format($thisSensorVal, $thisSensorDecimals, ".", "");
                }
                else{
                    $apiLog['info'][] = "Sensor data outside acceptable limits or value invalid.";
                }
             }
             else{
                 $apiLog['info'][] = "Sensor: ".$thisSensorName." not found in API file, skipping...";
             }
         }
    }
    else{
         $apiLog['info'][] = "No extra sensors set to log to extra alldata table.";
    }

    ############################################################################
    // Create/load cache file
    ############################################################################

    // first check how old the cache is, if it is older than 30 minutes delete it to prevent averaging current values with very old values when station offline
    if(file_exists($base."cache/apiCache.txt")){
        // get latest date from cache file
        $cacheRaw = file_get_contents($base."cache/apiCache.txt");
        $cache = json_decode($cacheRaw,true);
        $latestCacheDate = $cache['timestamp'][count($cache['timestamp'])-1];
        if (time()-$latestCacheDate > 60 * 30) {
            unlink($base."cache/apiCache.txt");
            $apiLog['info'][] = "Cache file is over 30 minutes old, deleting it.";
        }
    }

    // cache file exists - i.e. it is valid, load it
    if(file_exists($base."cache/apiCache.txt")){
        $cacheRaw = file_get_contents($base."cache/apiCache.txt");
        $cache = json_decode($cacheRaw,true);
        $apiLog['info'][] = "Cached data loaded from cache/apiCache.txt.";
    }
    // cache file does not exist, create empty file
    else{
        $cache = array();
        $apiLog['info'][] = "No cache file found, create empty file.";
    }

    
    ############################################################################
    // Check for partial data
    ############################################################################
    
    // Check for partial data in cache when data is not present
    $validCachedT = false;
    if(!isset($rawData['T'])){
        // use cached data instead when present
        if(count($cache['T']) > 0) {
            $T = $cache['T'][count($cache['T'])-1];
            $validCachedT = true;
            $apiLog['info'][] = "Use cached Temperature for calculations: ".$T;
        }
    }
    else {
        if($valid['T']) {
            $T = $data['T'];
        }
    }

    $validCachedH = false;
    if(!isset($rawData['H'])){
        // use cached data instead when present
        if(count($cache['H']) > 0) {
            $H = $cache['H'][count($cache['H'])-1];
            $validCachedH = true;
            $apiLog['info'][] = "Use cached Humidity for calculations: ".$H;
        }
    }
    else {
        if($valid['H']) {
            $H = $data['H'];
        }
    }

    $validCachedW = false;
    if(!isset($rawData['W'])){
        // use cached data instead when present
        if(count($cache['W']) > 0) {
            $W = $cache['W'][count($cache['W'])-1];
            $validCachedW = true;
            $apiLog['info'][] = "Use cached Wind for calculations: ".$W;
        }
    }
    else {
        if($valid['W']) {
            $W = $data['W'];
        }
    }
    
    ############################################################################
    //  Unadjusted gauge pressure
    // convert UGP to P when needed
    if(isset($rawData['UGP']) && !isset($rawData['P'])){
        $convertUgp = true;
        $apiLog['info'][] = "convert UGP: ".$rawData['UGP'];
        // get station elevation from config
        // $stationElevation
        // $stationElevationUnits - m/ft 
        // define constants for SLP calculations

        // convert elevation to m if in ft
        if($stationElevationUnits=="ft"){
            $elevationM = $stationElevation * 0.3048;
        }
        else{
            $elevationM = $stationElevation;
        }
        $apiLog['info'][] = "elevationM: ".$elevationM;
        if($valid['T'] || $validCachedT){ // temperature is available, use more accurate formula
            $apiLog['info'][] = "T: ".$T;
            $temperatureK = $T + 273.15; // temperature in K
            $constant['g'] = 9.80665; // Earth-surface gravitational constant [m/s2]
            $constant['M'] = 0.0289644; // molar mass of dry air [kg/mol]
            $constant['R'] = 8.31447; // universal gas constant
            $partA = ($constant['g'] * $constant['M'] * $elevationM) / ($constant['R'] * $temperatureK);
            $convertedP = $rawData['UGP'] / exp(- $partA);
            $apiLog['info'][] = "more accurate convertedP: ".$convertedP;
            $rawData['P'] = (string)(round($convertedP,3));
        }
        else{ // temperature is N/A skip calculation
            $apiLog['info'][] = "No valid temp, skip less accurate calculation ";
        }
    }

    ############################################################################
    // Barometric pressure
    $apiLog['info'][] = "Parsing pressure";
    $valid['P'] = true;
    if(!isset($rawData['P'])){
        $valid['P'] = false;
        $apiLog['error'][] = "No pressure data provided.";
    }
    else{
        $rawP = trim($rawData['P']);
        $apiLog['info'][] = "Pressure: ".$rawP." hpa";
        if($rawP===""){
            $valid['P'] = false;
            $apiLog['error'][] = "Pressure field blank.";
        }
        elseif(!is_numeric($rawP)){
            $valid['P'] = false;
            $apiLog['error'][] = "Pressure is not a number.";
        }
    }
    // apply conversion - API uses hectopascals
    if($valid['P']){
        $apiLog['info'][] = "Database pressure units: ".$dataPressUnits;
        if($dataPressUnits!="hpa"){
            $rawP = convertor($rawP, "hpa", $dataPressUnits);
            $apiLog['info'][] = "Pressure  converted to: ".$rawP." ".$dataPressUnits;
        }
        // check limits
        $apiLog['info'][] = "Checking pressure is between limits specified in template Main settings.";
        $apiLog['info'][] = "Minimum pressure limit: ".$limitPressureMin." ".$dataPressUnits;
        $apiLog['info'][] = "Maximum pressure limit: ".$limitPressureMax." ".$dataPressUnits;
        if($rawP>=$limitPressureMin && $rawP<=$limitPressureMax){
            $apiLog['info'][] = "Pressure is OK and within the allowed limits";
            $data['P'] = $rawP;
            $apiLog['info'][] = "Pressure is valid.";
        }
        else{
            $apiLog['error'][] = "Pressure is outside the allowed limits.";
            $valid['P'] = false;
        }
    }

    ############################################################################
    // Calculations
    ############################################################################

    ############################################################################
    // Dew point
    // essential: valid temperature and valid humidity
    $valid['D'] = true;
    if(($valid['T'] || $validCachedT) && ($valid['H'] || $validCachedH)){
        // convert temperature to Celsius if necessary
        if($dataTempUnits=="F"){
            $temperatureC = convertor($T,"F","C");
        }
        else{
            $temperatureC = $T;
        }
        $rawDCelsius = dewpoint($temperatureC, $H);
        // convert back to Farenheit if necessary
        if($dataTempUnits=="F"){
            $rawD = convertor($rawDCelsius,"C","F");
        }
        else{
            $rawD = $rawDCelsius;
        }
        $apiLog['info'][] = "Calculated dew point: ".$rawD." ".$dataTempUnits;
        // just do some basic checking, but should be ok since T and H both valid
        if(is_numeric($rawD) && $rawD!=="" && $rawD!=null && $rawD>-100 && $rawD<200){
            $data['D'] = $rawD;
            $apiLog['info'][] = "Dew point ok.";
        }
        else{
            $apiLog['error'][] = "There is some problem with the calculated dew point value. Ignored.";
            $valid['D'] = false;
        }
    }
    else{
        $valid['D'] = false;
        $apiLog['info'][] = "Temperature, humidity or both are not valid and dew point calculation is therefore skipped.";
    }

    ############################################################################
    // Apparent Temperature
    // essential: valid temperature, valid humidity and valid wind speed
    $valid['A'] = true;

    if(($valid['T'] || $validCachedT) && ($valid['H'] || $validCachedH) && ($valid['W'] || $validCachedW)){
        // convert temperature to Celsius if necessary
        if($dataTempUnits=="F"){
            $temperatureC = convertor($T,"F","C");
        }
        else{
            $temperatureC = $T;
        }
        if($dataWindUnits!="ms"){
            $windMS = convertor($W,$dataWindUnits,"ms");
        }
        else{
            $windMS = $W;
        }
        $rawACelsius = apparent($temperatureC, $H, $windMS);
        // convert back to Farenheit if necessary
        if($dataTempUnits=="F")
        {
            $rawA = convertor($rawACelsius,"C","F");
        }
        else{
            $rawA = $rawACelsius;
        }
        $apiLog['info'][] = "Calculated apparent temperature: ".$rawA." ".$dataTempUnits;
        // just do some basic checking, but should be ok since T, H and W both valid
        if(is_numeric($rawA) && $rawA!=="" && $rawA!=null && $rawA>-100 && $rawA<200){
            $data['A'] = $rawA;
            $apiLog['info'][] = "Apparent temperature ok.";
        }
        else{
            $apiLog['error'][] = "There is some problem with the calculated apparent temperature value. Ignored.";
            $valid['A'] = false;
        }
    }
    else{
        $valid['A'] = false;
        $apiLog['info'][] = "Temperature, humidity, wind speed or their combination are not valid and apparent temperature calculation is therefore skipped.";
    }


    ############################################################################
    // Check db update / update db
    ############################################################################

    $dbInterval = 300;
    
    // check if cache file contain a record
    if(count($cache["timestamp"]) > 0) {
        // timeForUpdate calculated from timestamp of first cached data
        $checkTimestamp = $cache["timestamp"][0];
        $apiLog['info'][] = "End time for database update based on timestamp of first cache data: ".date("Y-m-d H:i:s",$checkTimestamp);
    }
    else {
        // timeForUpdate calculated from timestamp of current data
        $checkTimestamp = $data['timestamp'];
        $apiLog['info'][] = "End time for database update based on timestamp of current data: ".date("Y-m-d H:i:s",$checkTimestamp);
    }
    if($checkTimestamp % $dbInterval == 0) {
        $timeForUpdate = $checkTimestamp;  // $checkTimestamp is equal to end time of current archive period   
    }
    else {
        $timeForUpdate = floor($checkTimestamp / $dbInterval) * $dbInterval + $dbInterval;  // end time of current archive period  
    }
    $apiLog['info'][] = "Rounded end time for database update: ".date("Y-m-d H:i:s",$timeForUpdate);
    
    $updateTime = false;
    if($data['timestamp'] >= $timeForUpdate){
        $updateTime = true;
    }
    if($data['timestamp'] == $timeForUpdate){
        // addDataToCache - current record belongs to the current archive set
        ############################################################################
        // Add current data set to cache
        ############################################################################

        // only do something if we have a valid date
        if($valid['date']){
            // add current data set to either empty cache file or add to existing cache
            $cache['timestamp'][] = $data['timestamp'];
            $cache['date'][] = $data['date'];
            if($valid['T']){
                $cache['T'][] = $data['T'];
            }
            if($valid['Tmax']){
                $cache['Tmax'][] = $data['Tmax'];
            }
            if($valid['Tmin']){
                $cache['Tmin'][] = $data['Tmin'];
            }
            if($valid['H']){
                $cache['H'][] = $data['H'];
            }
            if($valid['P']){
                $cache['P'][] = $data['P'];
            }
            if($valid['W']){
                $cache['W'][] = $data['W'];
            }
            if($valid['G']){
                $cache['G'][] = $data['G'];
            }
            if($valid['B']){
                $cache['B'][] = $data['B'];
            }
            if($valid['R']){
                // rain is cumulative, yet we cache for newDay checks
                $cache['R'][] = $data['R']; 
            }
            if($valid['RR']){
                $cache['RR'][] = $data['RR'];
            }
            if($valid['S']){
                $cache['S'][] = $data['S'];
            }
            if($valid['D']){
                $cache['D'][] = $data['D'];
            }
            if($valid['A']){
                $cache['A'][] = $data['A'];
            }

            // extra parameters
            if(count($extraQueryParams)>0){
                for($j = 0; $j < count($extraQueryParams); $j++){
                    $cache[$extraQueryParams[$j]][] = $extraQueryValues[$j];
                }
            }
        }
    }

    // if time for update, do the update
    if($updateTime){
        $apiLog['info'][] = "Time to update the database, preparing query.";
        // create the column name array for db and values array and insert date
        $db['parameters'][] = "DateTime";
        $firstCacheDate = $cache['timestamp'][0]; 
        $lastCacheDate = $cache['timestamp'][count($cache['timestamp'])-1]; 
        $apiLog['info'][] = "Timestamp first cached record: ".date("Y-m-d H:i:s",$firstCacheDate);
        $apiLog['info'][] = "Timestamp last cached record: ".date("Y-m-d H:i:s",$lastCacheDate);
        $apiLog['info'][] = "Timestamp last received record: ".date("Y-m-d H:i:s",$data['timestamp']);
        $apiLog['info'][] = "Timestamp new database record: ".date("Y-m-d H:i:s",$timeForUpdate);

        // check if this update is at 00:00:00
        $dayUpdateMin1 = date("Ymd",$timeForUpdate-1);
        $dayUpdate = date("Ymd",$timeForUpdate);
        if($dayUpdateMin1!=$dayUpdate){
           // start of a new day at 00:00:00
           $newDay = true;
        }
        else {
           $newDay = false;
        }
        $db['fields'][] = "'".date("Y-m-d H:i:s",$timeForUpdate)."'";
        
        // average temperature
        if(isset($cache['T'])){
            $db['parameters'][] = "T";
            // take temperature average and convert to db units
            $rawValue = array_sum($cache['T'])/count($cache['T']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }

        // maximum temperature
        if(isset($cache['Tmax'])){
            $db['parameters'][] = "Tmax";
            // take maximum temperature and convert to db units
            $rawValue = max($cache['Tmax']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }
        else{ // if Tmax is not available see if T is and if so, use that
            if(isset($cache['T'])){
                $db['parameters'][] = "Tmax";
                // take maximum and convert to db units
                $rawValue = max($cache['T']);
                $db['fields'][] = number_format($rawValue,1,".","");
            }
        }

        // minimum temperature
        if(isset($cache['Tmin'])){
            $db['parameters'][] = "Tmin";
            // take minimum temperature and convert to db units
            $rawValue = min($cache['Tmin']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }
        else{ // if Tmin is not available see if T is and if so, use that
            if(isset($cache['T'])){
                $db['parameters'][] = "Tmin";
                // take minimum and convert to db units
                $rawValue = min($cache['T']);
                $db['fields'][] = number_format($rawValue,1,".","");
            }
        }

        // relative humidity
        if(isset($cache['H'])){
            $db['parameters'][] = "H";
            // take humidity average
            $rawValue = array_sum($cache['H'])/count($cache['H']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }

        // barometric pressure
        // determine necessary number of decimal places for pressure
        if($dataPressUnits=="inhg"){
            $decimalsP = 2;
        }
        else{
            $decimalsP = 1;
        }
        if(isset($cache['P'])){
            $db['parameters'][] = "P";
            // take pressure average
            $rawValue = array_sum($cache['P'])/count($cache['P']);
            $db['fields'][] = number_format($rawValue,$decimalsP,".","");
        }

        // wind speed
        if(isset($cache['W'])){
            $db['parameters'][] = "W";
            // take wind average
            $rawValue = array_sum($cache['W'])/count($cache['W']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }

        // wind gust
        if(isset($cache['G'])){
            $db['parameters'][] = "G";
            // take wind gust maximum
            $rawValue = max($cache['G']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }
        else{
           // no gust data cached, take max wind speed instead
           if(isset($cache['W'])){
               $db['parameters'][] = "G";
               // take wind total
               $rawValue = max($cache['W']);
               $db['fields'][] = number_format($rawValue,1,".","");
           }
       }

        // wind bearing
        if(isset($cache['B'])){
            $db['parameters'][] = "B";
            // take wind bearing average
            $rawValue = avgWindUpdate($cache['B']);
            $db['fields'][] = number_format($rawValue,0,".","");
        }

        // precipitation
        // determine necessary number of decimal places for precipitation
        if($dataRainUnits=="in"){
            $decimalsR = 2;
        }
        else{
            $decimalsR = 1;
        }
        if(isset($cache['R'])){
            $db['parameters'][] = "R";
            // take cumulative daily rain and round it to the desired number of decimals
            // use max value for check and update of last days registration
            $maxRain = number_format(max($cache['R']),$decimalsR,".","");
            // use last value for other periods
            $currentRain = number_format($cache['R'][count($cache['R'])-1],$decimalsR,".","");

            // check if newDay
            if($newDay){
                $apiLog['info'][] = "First database record in new day; check rain values";
                $previousRain = "";
                // read previous rain from database
                $queryRain = "SELECT DateTime, R FROM alldata ORDER BY DateTime DESC LIMIT 1";
                $thisQuery = mysqli_query($con,$queryRain);
                if(!$thisQuery){
                    $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ".$queryRain;
                }
                else{
                    while($row = mysqli_fetch_array($thisQuery)){
                        // the previous daily rain is already rounded to the desired number of decimals
                        $previousRain = $row['R'];
                        $apiLog['info'][] = "previous rain=".number_format($previousRain,$decimalsR,".","")." maxRain=".number_format($maxRain,$decimalsR,".","");
                        if($previousRain != "" && $maxRain > $previousRain){
                            // save rain during last (5-minute) period of the day
                            $lastRain = $maxRain - $previousRain;
                            $apiLog['info'][] = "Last rain of this day=".number_format($lastRain,$decimalsR,".","");
                            $previousDate = $row['DateTime'];
                            $newQuery = "UPDATE alldata SET R=".$maxRain." WHERE DateTime='".$previousDate."'";
                            $otherQuery = mysqli_query($con,$newQuery);
                            if(!$otherQuery){
                                $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ".$newQuery;
                            }
                            else{
                                $apiLog['info'][] = "The database is updated with the following query: ".$newQuery;
                            }
                        }
                        // reset total rain at start of new day
                        $currentRain = 0.0;
                        $apiLog['info'][] = "Reset rain at start of new day=".number_format($currentRain,$decimalsR,".","");
                    }
                }
            }
            else{
                $apiLog['info'][] = "No new day, currentRain=".number_format($currentRain,$decimalsR,".","");
            }
            $db['fields'][] = number_format($currentRain,$decimalsR,".","");
        }

        // rain rate
        if(isset($cache['RR'])){
            $db['parameters'][] = "RR";
            // take rain rate average or maximum based on template settings
            if($apiRRCalculation == "max"){
                $rawValue = max($cache['RR']);
            }
            else{
                $rawValue = array_sum($cache['RR'])/count($cache['RR']);
            }
            $db['fields'][] = number_format($rawValue,$decimalsR,".","");
        }

        // solar radiation
        if($solarSensor){
            if(isset($cache['S'])){
                $db['parameters'][] = "S";
                // take solar radiation average
                $rawValue = array_sum($cache['S'])/count($cache['S']);
                $db['fields'][] = number_format($rawValue,1,".","");
            }
        }

        // dew point
        if(isset($cache['D'])){
            $db['parameters'][] = "D";
            // take dew point average
            $rawValue = array_sum($cache['D'])/count($cache['D']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }

        // apparent temperature
        if(isset($cache['A'])){
            $db['parameters'][] = "A";
            // take apparent temperature average
            $rawValue = array_sum($cache['A'])/count($cache['A']);
            $db['fields'][] = number_format($rawValue,1,".","");
        }
        
        // calculations
        $finalExtraParams = array();
        $finalExtraValues = array();
        if(count($extraSensors)>0){
            foreach($extraSensors as $extraSensor){
                if(isset($cache[$extraSensor])){
                    $finalExtraParams[] = $extraSensor;
                    $thisExtraVal = array_sum($cache[$extraSensor])/count($cache[$extraSensor]);
                    $finalExtraValues[] = $thisExtraVal;
                }
            }
        }

        // UPDATE!

        // Check database connection
        if (!$con) {
            $apiLog['error'][] = "Unable to connect to MySQL";
        }
        
        $query = "
            INSERT INTO alldata
            (".implode(',',$db['parameters']).")
            values (".implode(',',$db['fields'])."
        )";   
    
        $thisQuery = mysqli_query($con,$query);
        if(!$thisQuery){
            $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ".$query;
        }
        else{
            $apiLog['info'][] = "The database is updated with the following query: ".$query;
        }
        $thisQuery = mysqli_query($con,"ALTER TABLE alldata ORDER BY DateTime");
        if(!$thisQuery){
            $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ALTER TABLE alldata ORDER BY DateTime";
        }

        // perform query to alldataExtra if some params are available
        if(count($finalExtraParams)>0){
            $finalExtraParams[] = "DateTime";
            $finalExtraValues[] = "'".date("Y-m-d H:i:s",$timeForUpdate)."'";
            $apiLog['info'][] = "Preparing extra sensor query...";
            $queryExtra = "
                INSERT INTO alldataExtra
                (".implode(',',$finalExtraParams).")
                values (".implode(',',$finalExtraValues).")
            ";

            $thisQuery = mysqli_query($con,$queryExtra);
            if(!$thisQuery){
                $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ".$query;
            }
            else{
                $apiLog['info'][] = "The extra database table is updated with the following query: ".$queryExtra;
            }
            // only do this at 0:00-0:10
            if(date("H")==0 && date("i") <= 10){
                $thisQuery = mysqli_query($con,"ALTER TABLE alldataExtra ORDER BY DateTime");
                if(!$thisQuery){
                    $apiLog['error'][] = "Meteotemplate MySQL Error: ".mysqli_error($con)." with query: ALTER TABLE alldataExtra ORDER BY DateTime";
                }
            }
        }

        // save latest results of cache and log
        file_put_contents($base."cache/latestApiCache.txt",json_encode($cache));

        if(!file_exists($base."cache/latestApiCache.txt")){
            $apiLog['error'][] = "cache/latestApiCache.txt was not created! Probably incorrect permissions on the cache folder.";
        }
        
        // delete cache
        unlink($base."cache/apiCache.txt");

        $apiLog['info'][] = "Cache file deleted.";
        
        if($data['timestamp'] > $timeForUpdate){
            // addDataToCache - current record belongs to the next archive set
            ############################################################################
            // Add current data set to cache
            ############################################################################
            $cache = array();
            // only do something if we have a valid date
            if($valid['date']){
                // add current data set to either empty cache file or add to existing cache
                $cache['timestamp'][] = $data['timestamp'];
                $cache['date'][] = $data['date'];
                if($valid['T']){
                    $cache['T'][] = $data['T'];
                }
                if($valid['Tmax']){
                    $cache['Tmax'][] = $data['Tmax'];
                }
                if($valid['Tmin']){
                    $cache['Tmin'][] = $data['Tmin'];
                }
                if($valid['H']){
                    $cache['H'][] = $data['H'];
                }
                if($valid['P']){
                    $cache['P'][] = $data['P'];
                }
                if($valid['W']){
                    $cache['W'][] = $data['W'];
                }
                if($valid['G']){
                    $cache['G'][] = $data['G'];
                }
                if($valid['B']){
                    $cache['B'][] = $data['B'];
                }
                if($valid['R']){
                    // rain is cumulative, yet we cache for newDay checks
                    $cache['R'][] = $data['R']; 
                }
                if($valid['RR']){
                    $cache['RR'][] = $data['RR'];
                }
                if($valid['S']){
                    $cache['S'][] = $data['S'];
                }
                if($valid['D']){
                    $cache['D'][] = $data['D'];
                }
                if($valid['A']){
                    $cache['A'][] = $data['A'];
                }

                // extra sensors
                if(count($extraQueryParams)>0){
                    for($j = 0; $j < count($extraQueryParams); $j++){
                        $cache[$extraQueryParams[$j]][] = $extraQueryValues[$j];
                    }
                }
            }
            $apiLog['info'][] = "Saving first new data to cache/apiCache.txt, timestamp=".date("Y-m-d H:i:s",$data['timestamp']);
            file_put_contents($base."cache/apiCache.txt",json_encode($cache));
        }
        // create API update log file
        $updateLog = "";
        foreach($apiLog['info'] as $info){
            $updateLog .= $info."\n\r";
        }
        $updateLog .= "\n\rERRORS:\n\r";
        if(isset($apiLog['error'])){
            foreach($apiLog['error'] as $error){
                $updateLog .= $error."\n\r";
            }
        }
        file_put_contents($base."cache/latestApiLog.txt",$updateLog);
    }
    // not yet time for update - just save updated cache file
    else{
        // addDataToCache - current record belongs to the next archive set
        ############################################################################
        // Add current data set to cache
        ############################################################################

        // only do something if we have a valid date
        if($valid['date']){
            // add current data set to either empty cache file or add to existing cache
            $cache['timestamp'][] = $data['timestamp'];
            $cache['date'][] = $data['date'];
            if($valid['T']){
                $cache['T'][] = $data['T'];
            }
            if($valid['Tmax']){
                $cache['Tmax'][] = $data['Tmax'];
            }
            if($valid['Tmin']){
                $cache['Tmin'][] = $data['Tmin'];
            }
            if($valid['H']){
                $cache['H'][] = $data['H'];
            }
            if($valid['P']){
                $cache['P'][] = $data['P'];
            }
            if($valid['W']){
                $cache['W'][] = $data['W'];
            }
            if($valid['G']){
                $cache['G'][] = $data['G'];
            }
            if($valid['B']){
                $cache['B'][] = $data['B'];
            }
            if($valid['R']){
                // rain is cumulative, yet we cache for newDay checks
                $cache['R'][] = $data['R']; 
            }
            if($valid['RR']){
                $cache['RR'][] = $data['RR'];
            }
            if($valid['S']){
                $cache['S'][] = $data['S'];
            }
            if($valid['D']){
                $cache['D'][] = $data['D'];
            }
            if($valid['A']){
                $cache['A'][] = $data['A'];
            }

            // extra sensors
            if(count($extraQueryParams)>0){
                for($j = 0; $j < count($extraQueryParams); $j++){
                    $cache[$extraQueryParams[$j]][] = $extraQueryValues[$j];
                }
            }
        }
        $apiLog['info'][] = "Not yet time to update the db, saving new data to cache/apiCache.txt";
        file_put_contents($base."cache/apiCache.txt",json_encode($cache)); 
    }

    // create API update log file
    $apiLog['info'][] = "Generating log file cache/apiLog.txt";
    $updateLog = "";
    foreach($apiLog['info'] as $info){
        $updateLog .= $info."\n\r";
    }
    $updateLog .= "\n\rERRORS:\n\r";
    if(isset($apiLog['error'])){
        foreach($apiLog['error'] as $error){
            $updateLog .= $error."\n\r";
        }
    }
    file_put_contents($base."cache/apiLog.txt",$updateLog);
    if($convertUgp){
        file_put_contents($base."cache/apiLogConvertUgp.txt",$updateLog);
    }
    
    // return success status to caller
    echo "Success";
    
    ############################################################################
    // Functions
    ############################################################################

    // Dew point calculation
    // accepts temperature [C] and humidity [%]
    // returns dew point [C]
    function dewpoint($dewT,$dewH){
        $calcD = round(((pow(($dewH/100), 0.125))*(112+0.9*$dewT)+(0.1*$dewT)-112),1);
        return $calcD;
    }

    // Apparent temperature calculation
    // accepts temperature [C], humidity [%], wind speed [m/s]
    // returns apparent temperature [C]
    function apparent($apparentT,$apparentH,$apparentW){
        $e = ($apparentH/100)*6.105*pow(2.71828, ((17.27*$apparentT)/(237.7+$apparentT)));
        $calcA = round(($apparentT + 0.33*$e-0.7*$apparentW-4),1);
        return $calcA;
    }

    // Average wind bearing
    // accepts wind directions as an array and data as number between 0 and 360
    function avgWindUpdate($directions) { // based on http://en.wikipedia.org/wiki/Yamartino_method
        $sinSum = 0;
        $cosSum = 0;
        foreach ($directions as $value) {
            $sinSum += sin(deg2rad($value));
            $cosSum += cos(deg2rad($value));
        }
        return ((rad2deg(atan2($sinSum, $cosSum)) + 360) % 360);
    }

    function generateAPILog(){
        global $apiLog;
        global $base;
        // create log and exit update script 
        $apiLog['info'][] = "Generating log file cache/apiLog.txt";
        $updateLog = "";
        foreach($apiLog['info'] as $info){
            $updateLog .= $info."\n\r";
        }
        $updateLog .= "\n\rERRORS:\n\r";
        if(isset($apiLog['error'])){
            foreach($apiLog['error'] as $error){
                $updateLog .= $error."\n\r";
            }
        }
        file_put_contents($base."cache/apiLog.txt",$updateLog);
    }