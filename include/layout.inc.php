<?
/* layout.php: building blocks for constructing layout */

function htmlHeader($currentVersion) {
echo <<<HTML
	<?xml version="1.0" encoding="UTF-8"?>
HTML;
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>phpWebFTP <?=$currentVersion;?> By Edwin van Wijk</TITLE>
		<link rel="stylesheet" href="style/cm.css" title="contemporary" type="text/css"></link>
		<script type="text/javascript" src="include/script.js"></script>
	</head>
	<body>
<?
}

function htmlFooter() {
?>
	</body>
</html>
<?
}

function titlebar($lblVersion, $currentVersion) {
?>
	<div id="titlebar">
		phpWebFTP <?=$lblVersion?> <?=$currentVersion?>
	</div>
<?
}

function toolbarFilelist() {
?>
	<div id="toolbar">
	</div>
<?
}

function toolbarEditor() {
?>
	<div id="toolbar">
	</div>
<?
}

function toolbarViewer() {
?>
	<div id="toolbar">
	</div>
<?
}

function toolbarLogin() {
?>
	<div id="toolbar">
	</div>
<?
}

function actionpane() {
?>
<?
}

function windowFilelist() {
?>
<?
}

function windowTxtEditor() {
?>
<?
}

function windowHtmlEditor() {
?>
<?
}

function windowLogin($ar) {
	htmlHeader($ar["currentVersion"]);

	titlebar($ar["strings"]["version"], $ar["currentVersion"]);
	toolbarLogin();
?>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
		<div id="login">
		<table>
		<tr>
			<td colspan=3><b><?=$ar["strings"]["login"];?></b></td>
		</tr>
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td colspan="3"><?=$ar["strings"]["connectToFTPServer"];?></td>
		</tr>
		<tr>
			<td valign="top"><?=$ar["strings"]["server"];?></td>
			<td valign="top">
				<?php
					if($ar["defaultServer"] == "") {
					?>
						<input type=text name=server size=15>
						<?
					} else {
						$inputType=($ar["editDefaultServer"]==true)?"text":"hidden";
						?>
						<input type="<?=$inputType?>" name="server" value="<?=$defaultServer?>">
						<?
						if($ar["editDefaultServer"]==false) {
						?>
							<b><?=$ar["defaultServer"]?></b>
						<?
						}
					}
				?>
			</td>
			<td valign="top">
				<table cellspacing="0" border="0" cellpadding="0">
					<tr>
						<td><?=$ar["strings"]["port"];?></td>
						<td><input type="text" name="port" size="3" value="21"></td>
					</tr>
					<tr>
						<td><?=$ar["strings"]["passive"];?></td>
						<td><input type="checkbox" name="goPassive"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td><?=$ar["strings"]["user"];?></td>
			<td>
				<input type="text" name="user" size="18">
			</td>
			<td></td>
		</tr>
		<tr>
			<td><?=$ar["strings"]["password"];?></td>
			<td><input type="password" name="password" size="18"></td>
			<td><input type="submit" value="log on"></td>
		</tr>

		<?php
			if($ar["defaultLanguage"] == "") {
		?>
		<tr>
			<td><?=$ar["strings"]["language"];?></td>
			<td colspan="2">
				<select name="language">
				<?php
					if ($handle = opendir('include/language/')) {
						//Read file in directory and store them in an Array
						while (false !== ($file = readdir($handle))) {
							$fileArray[$file] = $file;
						}
						//Sort the array
						ksort($fileArray);

						foreach($fileArray as $file) {
							if ($file != "." && $file != ".." ) {
								$file=str_replace(".lang.php","",$file);
								$counter=0;
								foreach($languages as $thislang)
								{
									if($thislang==$file)
									{
										$counter++;
									}
								}
								if($counter>0) {
									$langName=strtoupper(substr($file,0,1)) . substr($file,1);
				?>
									<option value="<?=$file;?>" <?=($language==$file)?'selected="selected"':"";?>><?=$langName;?></option>
				<?php
								}

							}
						}
						closedir($handle);
					}
						?>
				</select>
		</tr>
		<?php
			} // End default server
		?>
		<tr>
			<td colspan="2"></td>
		</tr>
	</table>
	</div>
	<table width="328">
	<tr>
			<td colspan="2" valign="top" class="leftmenuitem">
				<div style="font-size:7pt;">
				<?=$ar["strings"]["disclaimer"]?>
				<br/><br/>
				phpWebFTP <?=$ar["strings"]["version"]?> <?=$ar["currentVersion"];?><br/>
				&copy; 2002-2004, Edwin van Wijk,<br/>
				<a href="http://www.v-wijk.net" style='font-size:7pt;'>www.v-wijk.net</a>
				</div>
				<p>
			</td>
		</tr>
		<tr>
		<td align=left></td>
		<td align=right></td>
		</tr>
	</table>
	</form>
<?
	htmlFooter();
}
?>
