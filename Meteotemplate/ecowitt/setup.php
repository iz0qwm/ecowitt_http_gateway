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

<?php
   // user has clicked a delete hyperlink
   if($_GET['action'] && $_GET['action'] == 'delete') {
       unlink($_GET['filename']);
       header("Location:setup.php");
       exit();
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
		.tg  {border-collapse:collapse;border-spacing:0;align:center}
		.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
		.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
		.tg .tg-0lax{text-align:left;vertical-align:top}
		.tg .tg-1lax{text-align:center;vertical-align:top}
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
							<?php
									if( $txt_data_log == "0" ){ 
							?>
										<input type="checkbox" class="largerCheckbox" name="txt_data_log" class="button2" value="1">
							<?php	}							
									if( $txt_data_log == "1" ){
							?>
										<input type="checkbox" class="largerCheckbox" name="txt_data_log" class="button2" value="0" checked>
							<?php		}
							?>							

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
					<tr>
						<td style="text-align:left;width:300px" colspan="2">
							<?php
							$dir = "./";
							$allFiles = scandir($dir);
							$files = preg_grep("/^(\.|\.\.|report|(.*)\.php|(.*)\.txt|(.*)\.csv)$/",$allFiles, PREG_GREP_INVERT);
							foreach($files as $file){
								echo "JSON file is: <b><a href='http://".$_SERVER[HTTP_HOST].str_replace('setup.php', $file, $_SERVER[REQUEST_URI])."' target=_blank>http://".$_SERVER[HTTP_HOST].str_replace('setup.php', $file, $_SERVER[REQUEST_URI])."</a></b>";
							}	
							?>
							
							
							<br><br>
						</td>
					</tr>					
				</table>
				<div style="width:50%;text-align:center;margin:0 auto">
					<input type="submit" value="Save" class="button2">
				</div>
			</form>
			<br><br>
			<center>
			<table class="tg">
			<tr>
				<td class='tg-1lax'>Log files (click on to download)</td>
				<td class='tg-1lax'>Command</td>
			</tr>	
			<?php
				$dir = "./";

				$allFiles = scandir($dir);
				$files = preg_grep("/^(\.|\.\.|report|(.*)\.php|(.*)\.txt|(.*)\.json)$/",$allFiles, PREG_GREP_INVERT);
				foreach($files as $file){
					echo "<tr>";
					echo "<td class='tg-0lax'><a href='download.php?file=".$file."'>".$file."</a></td>";
					echo "<td class='tg-0lax'><a href='setup.php?action=delete&filename=".$file."'>delete</a></td>";
					echo "</tr>";
				}

			?>
			</table>
			</center>
		</div>
		<?php include($baseURL."footer.php");?>
	</body>
</html>