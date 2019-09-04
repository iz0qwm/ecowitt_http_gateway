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
	
	// check if settings already exists and if so, load it, otherwise set parameters to default values
	if(file_exists("settings.php")){
		include("settings.php");
	}

	if(!isset($forward_server)){
		$forward_server = "www.meteotemplate.com/template/api.php";
	}
	if(!isset($forward_server_password)){
		$forward_server_password = "meteotemplate admin password";
	}
	if(!isset($txt_data_log)){
		$txt_data_log = "0";
	}
	
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $pageName?></title>
		<?php metaHeader()?>
		<style type="text/css">
		<!--
		input.largerCheckbox
		{
			width: 20px;
			height: 20px;
		}
		//-->	
		</style>
	</head>
	<body>
		<div id="main_top">
			<?php bodyHeader()?>
			<?php include($baseURL."menu.php");?>
		</div>
		<div id="main" style="text-align:center">
			<h1>Ecowitt - Setup</h1>
			<form method="POST" action="saveSettings.php" target="_blank">
				<table style="width:98%;margin:0 auto">
					<tr>
						<td style="text-align:left;width:300px">
							Meteotemplate server
						</td>
						<td style="text-align:left">
							<input name="forward_server" size="70" class="button2" value="<?php echo $forward_server?>">
						</td>
					</tr>
					<tr>
						<td style="text-align:left;width:300px">
							Meteotemplate admin password
						</td>
						<td style="text-align:left">
							<input name="forward_server_password" size="70" class="button2" value="<?php echo $forward_server_password?>">
						</td>
					</tr>
					<tr>
						<td style="text-align:left;width:300px">
							Record data in CSV format
						</td>
						<td style="text-align:left">
							<input type="checkbox" class="largerCheckbox" name="txt_data_log" class="button2" value="1">
							<!-- <input name="txt_data_log" class="button2" value="<?php echo $txt_data_log?>"> -->
						</td>
					</tr>
					<tr>
						<td style="text-align:left;width:300px" colspan="2">
							<br><br>
							Set your Ecowitt GW1000 to send data to: <b><?php echo $_SERVER[HTTP_HOST]?></b> <br>
							path: <b><?php echo str_replace('setup.php', 'report/', $_SERVER[REQUEST_URI])?></b>
							<br><br>
						</td>
					</tr>
					<tr>
						<td style="text-align:left;width:300px" colspan="2">
							Your Meteotemplate API file will be: <b>http://<?php echo $forward_server?></b>
							<br><br>
						</td>
					</tr>
					<tr>
						<td style="text-align:left;width:300px" colspan="2">
							CSV data will be saved to: <b><?php echo $baseURL?>plugins/ecowitt/report/</b>
							<br><br>
						</td>
					</tr>					
				</table>
				<div style="width:50%;text-align:center;margin:0 auto">
					<input type="submit" value="Save" class="button2">
				</div>
			</form>
		</div>
		<?php include($baseURL."footer.php");?>
	</body>
</html>