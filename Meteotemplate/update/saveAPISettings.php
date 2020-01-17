<?php
	// check acces authorization
	session_start();
	if($_SESSION['user']!="admin"){
		echo "Unauthorized access.";
		die();
	}
	
	include("../config.php");
	
	//error_reporting(E_ALL);
	
	// load user settings
	foreach($_GET as $key=>$value){
		$parameters[trim($key)] = $value;
		if($value==1){
			$extraCols[] = trim($key);
		}
	}
	
	if(file_exists("apiSettings.txt")){
		unlink("apiSettings.txt");
	}
	file_put_contents("apiSettings.txt",json_encode($parameters));

	// check if table already exists
	if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'alldataExtra'")) > 0){
		// table already exists
        // echo "Table already exists.<br>";
	}
    else{
        $query = 
        "	
            CREATE  TABLE  alldataExtra (  
                DateTime datetime NOT  NULL  PRIMARY  KEY
            ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci;
		";
        //echo $query;
        mysqli_query($con, $query);
        //echo "Creating extra data table...<br>";

        // check if table was created successfully
        if(
            mysqli_num_rows(
                mysqli_query($con,
                    "
                        SHOW TABLES LIKE 'alldataExtra'
                    "
                )
            ) > 0
            or die ("Table was not created, please check your MySQL setup.")
        ){
            //echo "Table created!<br>";
        }
    }

    // echo "<br>Checking columns...<br>";

    // add columns
    for($i=0;$i<count($extraCols);$i++){
        $thisCol = $extraCols[$i];
        //echo "<br>Checking column: ".$thisCol."<br>";
        $query = "SHOW COLUMNS FROM `alldataExtra` LIKE '".$thisCol."'";
        $result = mysqli_query($con, $query);
        $exists = (mysqli_num_rows($result)) ? true : false;
        if(!$exists){
            //echo "This column does not exist, creating it.<br>";
            if($thisCol=="UV"){
                // UV
                $query = "ALTER TABLE  `alldataExtra` ADD  `UV` DECIMAL(3,1) NULL";
            }
            else if($thisCol=="TIN" || $thisCol=="T1" || $thisCol=="T2" || $thisCol=="T3" || $thisCol=="T4" || $thisCol=="TS1" || $thisCol=="TS2" || $thisCol=="TS3" || $thisCol=="TS4"){
                // extra temperature / indoor temperature / soil temperature [deg C]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(3,1) NULL";
            }
            else if($thisCol=="HIN" || $thisCol=="H1" || $thisCol=="H2" || $thisCol=="H3" || $thisCol=="H4"){
                // extra humidity / indoor humidity [%]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="LT1" || $thisCol=="LT2" || $thisCol=="LT3" || $thisCol=="LT4"){
                // leaf temperature
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="LW1" || $thisCol=="LW2" || $thisCol=="LW3" || $thisCol=="LW4"){
                // leaf wetness
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="SD"){
                // snow depth [mm]
                $query = "ALTER TABLE  `alldataExtra` ADD  `SD` DECIMAL(6,1) NULL";
            }
            else if($thisCol=="SN"){
                // snowfall [mm]
                $query = "ALTER TABLE  `alldataExtra` ADD  `SN` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="SM1" || $thisCol=="SM2" || $thisCol=="SM3" || $thisCol=="SM4"){
                // soil moisture [%]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="L"){
                // lightning count
                $query = "ALTER TABLE  `alldataExtra` ADD  `L` INT(4) NULL";
            }
            else if($thisCol=="LD"){
                // lightning count
                $query = "ALTER TABLE  `alldataExtra` ADD  `LD` INT(2) NULL";
            }	
            else if($thisCol=="LT"){
                // lightning count
                $query = "ALTER TABLE  `alldataExtra` ADD  `LT` INT(10) NULL";
            }			
            else if($thisCol=="NL"){
                // noise level [dB]
                $query = "ALTER TABLE  `alldataExtra` ADD  `NL` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="SS"){
                // sunshine [h]
                $query = "ALTER TABLE  `alldataExtra` ADD  `SS` DECIMAL(4,1) NULL";
            }
            else if($thisCol=="CO2_1" || $thisCol=="CO2_2" || $thisCol=="CO2_3" || $thisCol=="CO2_4"){
                // CO2 [ppm]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="NO2_1" || $thisCol=="NO2_2" || $thisCol=="NO2_3" || $thisCol=="NO2_4"){
                // NO2 [ppm]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="CO_1" || $thisCol=="CO_2" || $thisCol=="CO_3" || $thisCol=="CO_4"){
                // CO [ppm]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="SO2_1" || $thisCol=="SO2_2" || $thisCol=="SO2_3" || $thisCol=="SO2_4"){
                // SO2 [ppb]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="O3_1" || $thisCol=="O3_2" || $thisCol=="O3_3" || $thisCol=="O3_4"){
                // O3 [ppb]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            else if($thisCol=="PP1" || $thisCol=="PP2" || $thisCol=="PP3" || $thisCol=="PP4"){
                // particulate pollution [ug/m3]
                $query = "ALTER TABLE  `alldataExtra` ADD  `".$thisCol."` DECIMAL(5,1) NULL";
            }
            mysqli_query($con, $query);
        }
        else{
            //echo "This column already exists, skipping.<br>";
        }
    }

	// check file exists
	
	if(!file_exists("apiSettings.txt")){
		echo "<script>alert('API settings file could not be created! Check that permissions for the update folder are set correctly to write files in there!');close();</script>";
	}
	else{
		print "<script>alert('API settings file created/updated and alldataExtra table created/updated.');close();</script>";
	}
