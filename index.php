<?php
	/*
	Copyright (C) 2002-2004 Edwin van Wijk, www.v-wijk.net

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

	New in Version 3.3a
	- zip support for zipping and downloading an entire directory
	- moved all ftp functions into a ftp class
	*/

	session_start();

	include('config.inc.php'); //load configuration
	include("include/functions.inc.php");
	include("include/ftp.class.php");

	// Report simple running errors
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);
	error_reporting(E_ERROR | E_PARSE);
	//error_reporting(E_ALL);

	/* 
	 * check availability of tar, zip, bzip2 and gzip
	 */

	// zip
	exec("which unzip", $unzipLocation, $retVal);

	if (preg_match("/unzip$/", $unzipLocation[0])) {
		$uncompress["zip"] = $unzipLocation[0];
	}


	// tar
	exec("which tar", $tarLocation, $retVal);

	if (preg_match("/tar/", $tarLocation[0])) {
		$uncompress["tar"] = $tarLocation[0] . " xf";
	}

	// gz
	exec("which gunzip", $gunzipLocation, $retVal);

	if (preg_match("/gunzip$/", $gunzipLocation[0])) {

		// tar.gz
		if (preg_match("/tar/", $tarLocation[0])) {
			$uncompress["tar.gz"] = $tarLocation[0] . " xzf";
			$uncompress["tgz"] = $tarLocation[0] . " xzf";
		}

		$uncompress["gz"] = $gunzipLocation[0];
	}

	// bz2
	exec("which bunzip2", $bunzip2Location, $retVal);

	if (preg_match("/bunzip2$/", $bunzip2Location[0])) {

		// tar.bz2
		if (preg_match("/tar$/", $tarLocation[0])) {
			$uncompress["tar.bz2"] = $tarLocation[0] . " xjf";
		}

		$uncompress["bz2"] = $bunzip2Location[0];
	}


	//Procedure for emptying the tmp directory
	if($clearTemp==true)
	{
        deleteRecursive($downloadDir, false);
	}

	// Get the POST and SESSION variables (if register_globals=off (PHP4.2.1+))
	/*
	$goPassive=(isset($_POST['goPassive']))?$_POST['goPassive']:$_GET['goPassive'];
	*/
    if (isset($_POST['goPassive'])) {
	    $goPassive=$_POST['goPassive'];
    }

	if (isset($_POST['mode'])) {
		$ftpMode = $_POST['mode'];
    }

	if (isset($_POST['actionType'])) {
	    $actionType=$_POST['actionType'];
    }

	if (isset($_POST['currentDir'])) {
	    $currentDir=stripSlashes($_POST['currentDir']);
    } else {
		$currentDir="";
	}
    
    if (isset($_POST['file'])) {
	    $file=$_POST['file'];
	    $file=StripSlashes($file);
	}

    if (isset($_POST['file2'])) {
	    $file2=$_POST['file2'];
	    $file2=StripSlashes($file2);
    }

    if (isset($_POST['permissions'])) {
	    $permissions=$_POST['permissions'];
    }

    if (isset($_POST['directory'])) {
	    $directory=$_POST['directory'];
    }

    if (isset($_POST['fileContent'])) {
	    $fileContent=$_POST['fileContent'];
    }

	if (
		($disableLoginScreen == false) &&
		isset($_POST['user'])
	) 
	{
		// we dont care if we are already logged or not in case user provides
		// login information. That allows relogging in without explicitly
		// loging out, eg with the "back" button.
		if ($editDefaultServer)
			$_SESSION['server']=$_POST['server'];
		else
			$_SESSION['server']=$defaultServer;

		if (isset($_POST['user'])) {
			$_SESSION['user']=$_POST['user'];
		}

		if (isset($_POST['password'])) {
			$_SESSION['password']=$_POST['password'];
		}

		if (isset($_POST['language'])) {
			$_SESSION['language']=$_POST['language'];
		}

		if (isset($_POST['port'])) {
			$_SESSION['port']=$_POST['port'];
		}

		if (isset($_POST['passive'])) {
			$_SESSION['passive']=$_POST['passive'];
		}
	}

	if (isset($actionType) and $actionType=="logoff")
	{
		unset($_SESSION['server']);
		unset($_SESSION['user']);
		unset($_SESSION['password']);
		unset($_SESSION['port']);
		unset($_SESSION['passive']);
		session_destroy();

		if ($disableLoginScreen) {
?>
			<script type="text/javascript">
				window.close();
			</script>
<?
		}
	}

    if (isset($_SESSION['server'])) {
	    $server=$_SESSION['server'];
	}

    if (isset($_SESSION['user'])) {
	    $user=$_SESSION['user'];
    }

    if (isset($_SESSION['password'])) {
	    $password=$_SESSION['password'];
    }

    if (isset($_SESSION['language'])) {
	    $language=$_SESSION['language'];
    }

    if (isset($_SESSION['port'])) {
	    $port=$_SESSION['port'];
    }

    if (isset($_SESSION['passive'])) {
	    $passive=$_SESSION['passive'];
    } else {
		$passive = false;
	}

	// If language is not yet set, check the default language or try to get the language from your browser.

    $validLanguage = false;
	if(!isset($language) or $language==""){
		if ($defaultLanguage !="") {
			$language = $defaultLanguage ;
			if(file_exists("include/language/" . $languages[$language] . ".lang.php")) {
                $validLanguage = true;
            }
		} else {
			$browser_lang = getenv("http_accept_language");
			$tmplang = $languages[$browser_lang];
			if(file_exists("include/language/" . $tmplang . ".lang.php")) {
				$language = $tmplang;
			} else {
				$language = "english";
			}
			$validLanguage=true;
		}
	} else {
		//Check if the language is a valid language
		foreach($languages as $langid=>$thisLanguage) {
			if($langid==$language) {
				$validLanguage=true;
			}
		}
	}

	//Include Language file
	if($validLanguage) {
		include("include/language/" . $languages[$language] . ".lang.php");   // Selected language
	} else {
		die("Invalid language entered. Exiting script");
	}

	if (isset($server) && $server!="")
	{
		$ftp = new ftp($server, $port, $user, $password, $passive);

		if (isset($_SESSION["ftpmode"])) {
			$ftp->setMode($_SESSION["ftpmode"]);
		}
		$ftp->setCurrentDir($currentDir);

		// set some default values as defined in config.inc.php
		$ftp->setResumeDownload($resumeDownload);
		$ftp->setDownloadDir($downloadDir);

		if ($ftp->loggedOn)
		{
			$msg = $ftp->getCurrentDirectoryShort();
			// what to do now ???
			if(isset($actionType)) 
			{
				switch ($actionType) 
				{
			 		case "changemode":
						$_SESSION["ftpmode"] = $ftpMode;
						$ftp->setMode($_SESSION["ftpmode"]);
						break;
					case "chmod":	// Change permissions
						if($ftp->chmod($permissions, $file)){
							$msg = $lblFilePermissionChanged;
						} else {
							$msg = $lblCouldNotChangePermissions;
						}
						break;
					case "cd":			// Change directory
						$ftp->cd($file);
						$msg = /*$lblndexOf .*/ $ftp->getCurrentDirectoryShort();
						break;
					case "get":			// Download file
						$ftp->download($file) or DIE($lblErrorDownloadingFile);
						break;
					case "put":			// Upload file

						$fileObject = $_FILES['file'];

						if($fileObject['size'] <= $maxFileSize) {
							if (isset($_POST["putunzip"])) {
								$file = $fileObject["name"];
								$tmpfile = $fileObject["tmp_name"];
								copy($fileObject["tmp_name"], $ftp->downloadDir . "/" . $file);

								// 1. check if file is unzippable
								// 2. unzip file
								// 3. clean up
								set_time_limit(30); //for big archives
								$dir = $ftp->downloadDir . $ftp->userDir . "/";  

								// 2. mkdir
								mkdir($dir);
								chdir($dir);

								// 3. unzip
								$cmd = false;

								foreach ($uncompress as $key=>$value) {
									if (!$cmd and preg_match("/\.$key$/", $file)) {
										$dir = preg_replace("/\.$key$/", "", $file);
										$cmd = $value;
									}
								}

								// 4. recursive upload

								if ($cmd) {
									mkdir($dir);
									chdir($dir);
									`$cmd ../../$file`;
									chdir("..");
									$ftp->putRecursive($dir);
								} else {
									$msg = "bestandstype wordt niet ondersteund.";
									$ftp->upload($fileObject);
								}
							} else {
								if(!$ftp->upload($fileObject)) {
									$msg = $lblFileCouldNotBeUploaded;
								}
							}
						} else {
							$msg = "<B>" . $lblFileSizeTooBig . "</B> (max. " . $maxFileSize . " bytes)<P>";
						}
						break;
					case "deldir";		// Delete directory
						$ftp->deleteRecursive($file);
						break;
					case "delfile";		// Delete file
						$ftp->deleteFile($file);
						break;
					case "rename";		// Rename file
						if($ftp->rename($file, $file2))	{
							$msg = $file . " " . $lblRenamedTo . " " . $file2;
						} else {
							$msg = $lblCouldNotRename . " " . $file . " " . $lblTo . " " . $file2;
						}
						break;
					case "createdir":  // Create a new directory
						if($ftp->makeDir($file)) {
							$msg = $file . " " . $lblCreated;
						} else {
							$msg = $lblCouldNotCreate . " " . $file;
						}
						break;
					case "edit":
						//First download the file to the server
						$ftp->get($file);

						//Now open the content of the file in an edit window
						// ToDo: separate file, html editor.
					?>
<?///BEGIN EDITOR///?>
<?
echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
XML;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>phpWebFTP <?=$currentVersion;?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" href="style/cm.css" title=contemporary type="text/css"/>
		<script type="text/javascript" src="include/script.js"></script>
<?
		if (
			preg_match("/\.htm$/", $file)
			||
			preg_match("/\.html$/", $file)
		) 
		{
?>
			<script type="text/javascript" src="include/fckeditor/fckeditor.js"></script>
			<script type="text/javascript">

			window.onload = function()
			{
				// Automatically calculates the editor base path based on the _samples directory.
				// This is usefull only for these samples. A real application should use something like this:
				// oFCKeditor.BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.
				var oFCKeditor = new FCKeditor( 'fileContent' ) ;
				oFCKeditor.BasePath = 'include/fckeditor/' ;
				oFCKeditor.Height	= 500 ;
				oFCKeditor.ReplaceTextarea() ;
			}

			</script>
<?
		}
?>
	</head>
	<body>
		<form method="post" name="editFileForm" action="<?=$_SERVER["PHP_SELF"];?>">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td valign="top">
						<table border="0" cellpadding="2" cellspacing="0" width="100%">
							<tr>
								<td class="titlebar" colspan="3">
									<b>phpWebFTP <?=$lblVersion;?> <?=$currentVersion;?></b>
								</td>
							</tr>
							<tr>
								<td class="menu">
<?
									$newMode=($ftp->mode==1)?0:1;

									if($ftp->loggedOn) 
									{
?>
										<table cellpadding="0" cellspacing="0">
											<tr>
												<td>
													<div class="toolbarButton" onclick="cancelEditFile();">
														<table border="0">
															<tr>
																<td>
																	<img src="img/back.gif" border="0" alt=""/>
																</td>
																<td>
																	&nbsp;<?=$lblBack?>
																</td>
															</tr>
														</table>
													</div>
												</td>
												<td>
													<div class="toolbarButton" onclick="document.editFileForm.submit();">
														<table border="0">
															<tr>
																<td>
																	<img src="img/save.gif" border="0" alt=""/>
																</td>
																<td>
																	<?=$lblSave?>
																</td>
															</tr>
														</table>
													</div>
												</td>
												<td align="right" width="100%">
													Edit <?=directoryPath($ftp->currentDir, $server);?> <?=$file;?>
												</td>
											</tr>
										</table>
<?
									} 
									else 
									{ 
?>
										<table cellpadding="0" cellspacing="0">
											<tr>
												<td valign="middle">&nbsp;<a class="menu" href="javascript:logOff()"><img src="img/logoff.gif" height="24" border="0" align="middle" alt=""/></a> </td>
												<td valign="middle">&nbsp;<a class="menu" href="javascript:logOff()"><?=$lblRetry;?></a> </td>
											</tr>
										</table>
<?
									} 
?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top">
<?
						$content = file_get_contents($ftp->downloadDir . $file);
						$content = htmlspecialchars($content, ENT_QUOTES);
?>
						<textarea name="fileContent" rows="30" cols="80" style="width: 100%; height: 500px;"><?=$content?></textarea>
						<input type="hidden" name="actionType" value="saveFile"/>
						<input type="hidden" name="currentDir" value="<?=$ftp->currentDir;?>"/>
						<input type="hidden" name="file" value="<?=$file;?>"/>
						<input type="hidden" name="mode" value="<?=$ftp->mode;?>"/>
					</td>
				</tr>
			</table>
		</form>
	</body>
</html>

<?///END EDITOR///?>
<?
						unlink($ftp->downloadDir . $file);
						exit;
						break;
					case "saveFile":
						//Write content of fileContent to tempFile
						$tempFile = "tmpFile.txt";
						$fp = fopen($ftp->downloadDir . $tempFile, "w+t");
						if ($bytes=!fwrite($fp, stripslashes($fileContent))) {
							$msg = $lblFileCouldNotBeUploaded;
						}
						fclose($fp);

						//Upload the file to the server
						if(!$ftp->put($ftp->currentDir . "/" . filePart(StripSlashes($file)),$ftp->downloadDir . $tempFile)) $msg = $lblFileCouldNotBeUploaded;

						//Delete temporary file
						unlink($ftp->downloadDir . $tempFile);
						break;

					case "getzip":
						set_time_limit(30); //for big archives
						$zipfile = $file . ".zip";

						// a directory for every user, just in case...
						$dir = $ftp->downloadDir . $ftp->userDir . "/";  

						header("Content-disposition: attachment; filename=\"$zipfile\"");
						header("Content-type: application/octetstream");
						header("Pragma: ");
						header("Cache-Control: cache");
						header("Expires: 0");

						$zipfile = $ftp->downloadDir . $zipfile;

						//Create temporary directory 
						mkdir($dir);

						//Get entire directory and store to temporary directory
						$ftp->getRecursive($ftp->currentDir, $file);

						//zip the directory
						$zip = new ss_zip('',6); 
						$zip->zipDirTree($dir, $dir);
						$zip->save($zipfile);

						//send zipfile to the browser
						$filearray = explode("/",$zipfile);
						$file = $filearray[sizeof($filearray)-1];

						$data = readfile($zipfile);
						$i=0;
						while ($data[$i] != "")
						{
							echo $data[$i];
							$i++;
						}

						//Delete zip file
						unlink($zipfile);

						//Delete downloaded files from user specific directory
						deleteRecursive($dir);
						exit;
						break;
					case "unzip": // BK20061114
						set_time_limit(30); //for big archives
						$dir = $ftp->downloadDir . $ftp->userDir . "/";  

						// 1. download
						$ftp->get($file);

						// 2. mkdir
						mkdir($dir);
						chdir($dir);

						// 3. unzip
						$cmd = false;

						foreach ($uncompress as $key=>$value) {
							if (!$cmd and preg_match("/\.$key$/", $file)) {
								$dir = preg_replace("/\.$key$/", "", $file);
								$cmd = $value;
							}
						}

						// 4. recursive upload
						if ($cmd) {
							mkdir($dir);
							chdir($dir);
							`$cmd ../../$file`;
							chdir("..");
							$ftp->putRecursive($dir);
						}
				}
			}
?>
<?///BEGIN FILE MANAGER///?>
<?
echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
XML;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>phpWebFTP <?=$currentVersion;?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" href="style/cm.css" title="Contemporary" type="text/css"/>
		<style type="text/css">
			body {
				background-color: white;
				background-image: url(img/menubg.gif);
				background-repeat: repeat-y;
			}
		</style>
		<script type="text/javascript">
			// variables for javascript
			var currentUser = "<?=$_SESSION["user"]?>";
			var dir = "<?=$ftp->currentDir;?>";
			var uncompress = new Array();
<?
			$uc = 0;
			foreach ($uncompress as $key=>$value) {
?>
				uncompress[<?=$uc++?>] = "<?=$key?>";
<?
			}
?>
			var ucNum = <?=$uc?>;

			/*
				strip public_html or www from dirname (for file view);
				for use within hosting management systems
			*/
			var site = "<?=$server?>";
			dir = dir.replace(/^\/www\/?/, "");
			dir = dir.replace(/^\/public_html\/?/, "");
		</script>
		<script type="text/javascript" src="include/script.js"></script>
	</head>
	<body>
		<table cellpadding="0" cellspacing="0" style="height:100%;width:100%;">
			<tr>
				<td>
					<table width="100%" border="0" cellspacing="0" cellpadding="2">
						<tr>
							<td class="titlebar">
								<b>phpWebFTP <?=$lblVersion;?> <?=$currentVersion;?></b>
							</td>
						</tr>
					</table>
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
<?
							$newMode=($ftp->mode==1)?0:1;
							if($ftp->loggedOn) 
							{
?>
								<td class="menu">
									<table border="0">
										<tr>
											<td>
												<div class="toolbarButton" onclick="submitForm('cd', '..');">
													<table border="0">
														<tr>
															<td>
																<img src="img/parent.gif" border="0" alt=""/>
															</td>
															<td>
																<?=$lblUp?>
															</td>
														</tr>
													</table>
												</div>
											</td>
											<td>
												<div class="toolbarButton" onclick="changeMode('<?=$newMode?>');">
													<table border="0">
														<tr>
															<td>
																<img src="img/mode.gif" border="0" alt=""/>
															</td>
															<td>
																<?=$lblChangeMode?>
															</td>
														</tr>
													</table>
												</div>
											</td>
											<td>
												<div class="toolbarButton" onclick="logOff();">
													<table border="0">
														<tr>
															<td>
																<img src="img/logoff.gif" border="0" alt=""/>
															</td>
															<td>
																<?=$lblLogOff?>
															</td>
														</tr>
													</table>
												</div>
											</td>
										</tr>
									</table>
								</td>

								<td colspan="6" class="menu" width="100%" valign="middle" align="right">
									<?=directoryPath($ftp->currentDir, $server);?>
								</td>
	<?
							} 
							else 
							{ 
	?>
								<td class="menu">
									<table border="0">
										<tr>
											<td>
												<div class="toolbarButton" onclick="logOff();">
													<table border="0">
														<tr>
															<td>
																<img src="img/parent.gif" border="0" alt=""/>
															</td>
															<td>
																<?=$lblRetry?>
															</td>
														</tr>
													</table>
												</div>
											</td>
										</tr>
									</table>
								</td>
<?
							}
?>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="height:100%;width:100%;">
					<div style="display:none;">
						<form name="actionform" method="post" action="<?=$_SERVER["PHP_SELF"];?>" enctype="multipart/form-data">
							<input type="hidden" name="actionType" value=""/>
							<input type="hidden" name="delaction" value=""/>
							<input type="hidden" name="currentDir" value="<?=$ftp->currentDir;?>"/>
							<input type="hidden" name="file" value=""/>
							<input type="hidden" name="file2" value=""/>
							<input type="hidden" name="extension" value=""/>
							<input type="hidden" name="permissions" value=""/>
							<input type="hidden" name="mode" value="<?=$ftp->mode;?>"/>
						</form>
					</div>
					<table style="height:100%;width:100%;" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td class="leftmenu" valign="top">
								<img src="img/1px.gif" width="212" height="1">
								<div align="center">
									<br/>
									<!-- File and folder -->
									<table border="0" cellspacing="0" cellpadding="0" class="item">
										<tr>
											<td valign="top" class="itemheadContainer">
												<div class="itemhead">
													<?=$lblFileTasks;?>
												</div>
											</td>
										</tr>
										<tr>
											<td valign="top" class="leftmenuitem">
												<table id="action_delete" border="0" cellspacing="0" cellpadding="0" style="display:none;">
													<!-- Delete File -->
													<tr>
														<td valign="middle"><img src="img/menu_delete.gif" alt=""/></td>
														<td valign="middle">
															<a href='javascript:deleteFile()' class=leftmenulink><?=$lblDeleteFile;?></A>
														</td>
													</tr>
												</table>
												<table id="action_zipdl" style="display:none;" border="0" cellspacing="0" cellpadding="0">
													<!-- zip&download directory -->
													<tr>
														<td valign="middle"><img src="img/zip.gif" alt=""/></td>
														<td valign="middle">
															<a href='javascript:zipFile()' class=leftmenulink><?="download";?></A>
														</td>
													</tr>
												</table>
												<table id="action_unzip" style="display:none;" border="0" cellspacing="0" cellpadding="0">
													<!-- unzip to <filename>/ -->
													<tr>
														<td valign="middle"><img src="img/zip.gif" alt=""/></td>
														<td valign="middle">
															<a href='javascript:unzipFile()' class="leftmenulink" id="unzipto"><?="unzip";?></A>
														</td>
													</tr>
												</table>
												<!-- View file on http server -->
												<table id="action_view" style="display:none;" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td valign=center><img src="img/menu_edit.gif" alt=""/></td>
														<td valign=center>
															<a href='javascript:viewFile()' class=leftmenulink>View<?//$lblEditFile;?></A>
														</td>
													</tr>
												</table>
												<table id="action_edit" style="display:none;" border="0" cellspacing="0" cellpadding="0">
													<!-- edit file 
													Only if $user is allowed to edit the file.
													-->
													<tr>
														<td valign="middle"><img src="img/menu_edit.gif" alt=""/></td>
														<td valign="middle">
															<a href='javascript:editFile()' class=leftmenulink><?=$lblEditFile;?></A>
														</td>
													</tr>
												</table>
												<table id="action_rename" style="display:none;" border="0" cellspacing="0" cellpadding="0">

													<!-- rename file 
													Only if $user is allowed to rename the file
													-->
													<tr>
														<td VALIGN=top><img src="img/menu_rename.gif" alt=""/></td>
														<td VALIGN=top>
															<a href='javascript:setNewFileName()' class=leftmenulink><?=$lblRename;?></A>
															<!-- Rename file -->
															<DIV ID='renameFileEntry' style='display:none;'>
																<form NAME=renameFile>
																<table border="0" cellpadding="0" cellspacing="0" class=lined align=center>
																	<tr>
																		<td class=tinyblue>
																		<B><?=$lblNewName;?></B><br/>
																		<input type="text" name="newName" value=""/></td>
																	</tr>
																	</table>
																<br/>
																<div align=center><input type=button onclick='renameItem();' value='<?=$lblRename;?>'/></div>
																</form>
																<br/>
															</div>
														</td>
													</tr>
												</table>
												<table id="action_permissions" style="display:none;" border="0" cellspacing="0" cellpadding="0">

													<!-- permissions 
													Only if $user is allowed to edit the permissions 
													-->
													<tr>
														<td valign="top"><img src="img/menu_settings.gif" alt=""/></td>
														<td valign="top">
															<a href="javascript:;" onclick="setPermissions()" class="leftmenulink"><?=$lblSetPermissions;?></a>
															<!-- Change permissions -->
															<div id="setPermissions" style="display:none;">
																<form name=permissions>
																<table border="0" cellpadding="0" cellspacing="0" class="lined" align="center">
																<tr>
																	<td class="tinyblue" align="center"><b><?=$lblOwner;?></b></td>
																	<td class="tiny" align="center"><b><?=$lblGroup;?></b></td>
																	<td class="tinywhite" align="center"><b><?=$lblPublic;?></b></td>
																</tr>
																<tr>
																	<td class="tinyblue"><input type="checkbox" name="iOr"/> <?=$lblRead;?></td>
																	<td class="tiny"><input type="checkbox" name="iGr"/> <?=$lblRead;?></td>
																	<td class="tinywhite"><input type="checkbox" name="iPr"/> <?=$lblRead;?></td>
																</tr>
																<tr>
																	<td class="tinyblue"><input type="checkbox" name="iOw"/> <?=$lblWrite;?></td>
																	<td class="tiny"><input type="checkbox" name="iGw"/> <?=$lblWrite;?></td>
																	<td class="tinywhite"><input type="checkbox" name="iPw"/> <?=$lblWrite;?></td>
																</tr>
																<tr>
																	<td class="tinyblue"><input type="checkbox" name="iOx"/> <?=$lblExecute;?></td>
																	<td class="tiny"><input type="checkbox" name="iGx"/> <?=$lblExecute;?></td>
																	<td class="tinywhite"><input type="checkbox" name="iPx"/> <?=$lblExecute;?></td>
																</tr>

																</table>
																<br/>
																<div align="center"><input type="button" onclick="changePermissions()" value="<?=$lblSetPermissions;?>"/></div>
																</form>
																<br/>
															</div>
														</td>
													</tr>
												</table>


												<!-- Standaard actions -->
												<table border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td VALIGN=top><img src="img/upload.gif" border="0" alt=""/></td>
														<td VALIGN=top>
															<a href="JavaScript:toggle('uploadform');" class=leftmenulink><?=$lblUploadFile;?></A>
																<form id="uploadform" style="display:none;" name="uploadform" enctype="multipart/form-data" method="post" action="<?=$_SERVER["PHP_SELF"];?>">
																	<input type="hidden" name="actionType" value="put"/>
																	<input type='hidden' name='currentDir' value='<?=$ftp->currentDir;?>'/>
																	<input type='hidden' name='mode' value='<?=$ftp->mode;?>'/>
																	<input type="file" name="file" size="8" style="width:100px; font-size:7pt;" onChange='/*document.uploadform.submit();*/'/><br/>
																	<input type="checkbox" name="putunzip"/><?=$lblCompressedFolder?>
																	<input type="submit" value="Ok" style='width=150px; font-size:7pt;'/>
																</form>
														</td>
													</tr>
													<tr>
														<td valign="top"><img src="img/createdir.gif" border="0" alt=""/></td>
														<td valign="top">
															<a href="JavaScript:toggle('createform');" class=leftmenulink><?=$lblCreateDirectory;?></A>
															<form id="createform" style='display:none;' method=post name='dirinput' action="<?=$_SERVER["PHP_SELF"];?>">
																<input type="text" name="directory" value="" style="width:100px; font-size:7pt;"/>
																<input type="button" value="Ok" onclick="javascript:createDirectory(dirinput.directory.value)" style="width:40px; font-size:7pt;"/>
															</form>
														</td>
													</tr>
													<tr>
														<td valign="top"><img src="img/gotodir.gif" border="0" alt=""/></td>
														<td valign="top">
															<a href="JavaScript:toggle('gotoform');" class="leftmenulink"><?=$lblGoToDirectory;?></a>
															<form id="gotoform" style="display:none;" name="cdDirect" method="post" action="<?=$_SERVER["PHP_SELF"];?>">
																<input type='hidden' name='actionType' value='cd'/>
																<input type='hidden' name='currentDir' value='<?=$ftp->currentDir;?>'/>
																<input type="text" name="file" value="" style="width:100px; font-size:7pt;"/>
																<input type="submit" value="Ok" style="width:40px; font-size:7pt;"/>
															</form>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<br/><br/>
									<!-- Details -->
									<table border="0" cellpadding="0" cellspacing="0" class="item">
										<tr>
											<td valign="top" class="itemheadContainer">
												<div class="itemhead">
													<?=$lblDetails;?>
												</div>
											</td>
										</tr>
										<tr>
											<td valign="top" class=leftmenuitem style='color:black' >
												<br/>
												<div style="width:170px;overflow:hidden;">
												<B><?=$msg;?></B>
												<P>
												<?=($ftp->loggedOn)?"$lblConnectedTo  $server:$port ($ftp->systype)":$lblNotConnected;?>
												<P>
												<?=$lblTransferMode;?> :<?=$ftp->mode==1?$lblBinaryMode:$lblASCIIMode;?>
												<br/><br/>
												</div>
											</td>
										</tr>

									</table>
								</div>
							</td>
							<td valign="top">
								<div id="filelist">
									<table width="100%" border="0" cellspacing="0" cellpadding="0" onclick='resetEntries()'>
										<tr>
											<td colspan="2" class="listhead"><?=$lblName;?></td>
											<td class="listhead" align="right"><?=$lblSize;?>&nbsp;</td>
											<td class="listhead"><?=$lblFileType;?>&nbsp;</td>
											<td class="listhead"><?=$lblDate;?></td>
											<td class="listhead"><?=$lblPermissions;?></td>
											<td class="listhead"><?=$lblOwner;?></td>
											<td class="listhead"><?=$lblGroup;?></td>
										</tr>
	<?
										$list = $ftp->ftpRawList();

										if (is_array($list))
										{
											// Directories
											$counter=0;

											foreach($list as $myDir)
											{
												if ($myDir["is_dir"]==1)
												{
													$fileAction = "cd";
													$fileName = $myDir["name"];
													$fileSize="";
													$delAction = "deldir";
													$fileType['description'] = 'File Folder';
													$fileType['imgfilename'] = 'folder.gif';
												}

												if ($myDir["is_link"]==1)
												{
													$fileAction = "cd";
													$fileName = $myDir["target"];
													$fileSize="";
													$delAction = "delfile";
													$fileType['description'] = 'Symbolic Link';
													$fileType['imgfilename'] = 'link.gif';
												}

												if ($myDir["is_link"]!=1 && $myDir["is_dir"]!=1)
												{
													$fileType = fileDescription($myDir["name"]);
													$fileAction = "get";
													$fileName = $myDir["name"];
													$image = "file.gif";
													if($myDir["size"]<1024) {
														$fileSize= $myDir["size"] . " bytes ";
															$fileSize=number_format($myDir["size"], 0, ',', '.') . " bytes";
													} else {
														if($myDir["size"]<1073741824) {
															$fileSize=number_format($myDir["size"]/1024, 0, ',', '.') . " KB";
														} else {
															$fileSize=number_format($myDir["size"]/1048576, 0, ',', '.') . " MB";
														}
													}

													
													$delAction = "delfile";
												}

												$escapedFileName = addslashes($fileName);
	?>

													<tr>
													<td class="filenamecol" width="20"><a href="javascript:selectEntry('<?=$fileAction?>','<?=$escapedFileName?>','filename<?=$counter?>','<?=addslashes($myDir["user"])?>','<?=$myDir["perms"]?>','<?=$delAction;?>')" ondblclick="submitForm('<?=$fileAction;?>','<?=$escapedFileName?>')"><img src="img/<?=$fileType['imgfilename'];?>" align="top" border="0" alt=""/></a></td>
													<td class="filenamecol"><a class="filename" id='filename<?=$counter;?>' href="javascript:selectEntry('<?=$fileAction?>','<?=$escapedFileName?>','filename<?=$counter?>','<?=$myDir["user"]?>','<?=$myDir["perms"];?>','<?=$delAction;?>')" ondblclick="submitForm('<?=$fileAction;?>','<?=$escapedFileName?>')"><?=$fileName;?></A></td>
													<td class="listcol" align="right"><?=$fileSize;?></td>
													<td class="listcol" align="left"><?=$fileType['description'];?></td>
													<td class="listcol"><?=$myDir["date"];?></td>
													<td class="listcol"><?=$myDir["perms"];?></td>
													<td class="listcol"><?=$myDir["user"];?></td>
													<td class="listcol"><?=$myDir["group"];?></td>
													</tr>
<?
												$counter++;
											}
										} else {
?>
											<tr>
												<td colspan=14><b><?=$lblDirectoryEmpty;?>...</b></td>
											</tr>
<?
										}
?>
									</table>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
<?///END FILE MANAGER///?>
<?
		}
		else
		{
			if(!isset($msg))
			{
				$msg = "$lblCouldNotConnectToServer  $server:$port $lblWithUser $user<p><a href='" . $_SERVER["PHP_SELF"] . "'>$lblTryAgain</A>";
				unset($_SESSION['server']);
				unset($_SESSION['user']);
				unset($_SESSION['password']);
				unset($_SESSION['port']);
				session_destroy();
			}
			print $msg;
		}
	}
	else // Still need to logon...
	{
		if ($disableLoginScreen == false) 
		{
?>
<?///BEGIN LOGIN SCREEN///?>
<?
echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
XML;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>phpWebFTP <?=$currentVersion;?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" href="style/cm.css" title="contemporary" type="text/css"/>
		<script type="text/javascript" src="include/script.js"></script>
	</head>
	<body>
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="titlebar">
					<B>phpWebFTP <?=$lblVersion;?> <?=$currentVersion;?></B>
				</td>
			</tr>
			<tr>
				<td class="menu">
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td valign="middle"><img src="img/1px.gif" height="24" border="0" align="middle" alt=""/></td>
							<td valign="middle">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<form name="login" action="<?=$_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
			<table class="login" cellpadding="3">
				<tr>
					<td colspan="3"><b>&nbsp;<?=$lblLogIn;?></b></td>
				</tr>
				<tr>
					<td colspan="3"><img src="img/1px.gif" height="60" alt=""/></td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;<?=$lblConnectToFTPServer;?></td>
				</tr>
				<tr>
					<td valign="top">&nbsp;<?=$lblServer;?></td>
					<td valign="top">
						<?php
							if($defaultServer == "") 
							{
?>
								<input type="text" name="server" size="15"/>&nbsp;
<?
							} 
							else 
							{
								$inputType=($editDefaultServer==true)?"text":"hidden";
?>
								<input type="<?=$inputType?>" name="server" value="<?=$defaultServer?>">
<?
								if($editDefaultServer==false) {
?>
									<b><?=$defaultServer?></b>&nbsp;
<?
								}
							}
						?>
					</td>
					<td valign="top">
						<table cellspacing="0">
							<tr>
								<td><?=$lblPort;?></td>
								<td><input type="text" name="port" size="3" value="21"/></td>
							</tr>
							<tr>
								<td><?=$lblPasive;?></td>
								<td><input type="checkbox" name="goPassive"/></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>&nbsp;<?=$lblUser;?></td>
					<td>
						<input type="text" name="user" size="18"/>
					</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;<?=$lblPassword;?></td>
					<td><input type="password" name="password" size="18"/></td>
					<td><input type="submit" value="Log on"/></td>
				</tr>

<?
				if($defaultLanguage == "") 
				{
?>
					<tr>
						<td>&nbsp;<?=$lblLanguage;?></td>
						<td colspan=2>
							<select name="language">
<?
								if ($handle = opendir('include/language/')) 
								{
									//Read file in directory and store them in an Array
									while (false !== ($file = readdir($handle))) 
									{
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
												<option value="<?=$file;?>" <?=($language==$file)?"selected=\"selected\"":"";?>><?=$langName;?></option>
<?
											}

										}
									}
									closedir($handle);
								}
?>
							</select>
						</td>
					</tr>
<?
				}
?>
				<tr>
					<td colspan="2"><img src="img/1px.gif" height="5" alt=""/></td>
				</tr>
			</table>
		</form>
	</body>
</html>
<?///END LOGIN SCREEN///?>

<?
		} 
		else 
		{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>Login disabled</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<style type="text/css">
			body {
				font-family: Verdana, sans-serif;
			}
		</style>
	</head>
	<body>
		Manual login functionality is disabled by your provider. <a href="#" onclick="window.close();">Close window</a>
	</body>
</html>
<?
		}
	}
?>
