<?php

	// check acces authorization
	session_start();
	if($_SESSION['user']!="admin"){
		echo "Unauthorized access.";
		die();
	}
	
	// load core files
	include("../../config.php");
	include($baseURL."css/design.php");
	include($baseURL."header.php");
	
	// load form parameters
	foreach($_POST as $variableName=>$variableValue){
		$variables[] = array($variableName,$variableValue);
	}

	
	// Create file
	$string = "<?php".PHP_EOL;
	
	$string .=" // Air traffic setup file".PHP_EOL;
	
	$string .= PHP_EOL;
	$string .= PHP_EOL;
	
	// add each parameter
	foreach($variables as $variable){
		// if it is a boolean or a number, do not use quotes
		if($variable[1]=="true" || $variable[1]=="false" || is_numeric($variable[1])){
			$string .= "\$".$variable[0]." = ".$variable[1].";".PHP_EOL;
		}
		// if it is a string, use quotes and make sure to replace '
		else{
			$variable[1] = str_replace("'","\'",$variable[1]);
			$string .= "\$".$variable[0]." = '".$variable[1]."';".PHP_EOL;
		}
	}
	
	$string .= "?>".PHP_EOL;
	
	// save settings file
	file_put_contents("settings.php",$string);

	// check file exists
	if(!file_exists("settings.php")){
		echo "<script>alert('Settings file could not be created! Check that permissions for the plugin folder are set correctly to write files in there!');close();</script>";
	}
	else{
		print "<script>alert('Settings file created/updated.');close();</script>";
	}
?>