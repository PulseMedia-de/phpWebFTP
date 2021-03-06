<?php
	$currentVersion = "4.0";

	# maxFileSize
	#----------
	# Enter here the maximum downnload size in Bytes. Make sure that your php configuration allows the
	# same size (e.g. upload_max_filesize = 20M)
	#
	# Example :
	#    $maxFileSize = 2000000;
	$maxFileSize = 2000000;

	# downloadDir
	#----------
	# Enter here the full path to the directory to store temporary files
	# The directory must be writable for the apache/php process owner (usualy 'apache' or 'nobody').
	# note: do not use the same directory that php uses to store session data (usually /tmp/)
	#
	# Example (Windows):
	#    $downloadDir = "c:\\MySite\phpWebFTP\tmp\\";
	# Example (Unix/Linux):
	# 	 $downloadDir = "/MySite/phpWebFTP/tmp/";
	
	$downloadDir = "tmp/";  # This is the tmp directory in the phpwebftp directory and should work by default
	
	# resumeDownload
	#----------
	# Resume download is only supported by PHP version 4.3.0 and higher
	#
	# Enable resumeDownload:
	#    $resumeDownload = true;
	# Disable resumeDownload (default value);
	#    $resumeDownload = false;
	
	$resumeDownload = true;

	# defaultLanguage
	#----------
	# Use the defaultLanguage variable to set a static language.
	# Please check the includes/language directory for available languages
	#
	# Example :
	#    $defaultLanguage = "en";
	# Disable default language (default value);
	#    $defaultLanguage = "";
	
	$defaultLanguage = "en";

	# defaultServer
	#----------
	# Use the defaultServer variable to set a static server rather then having the user type a server.
	# This could be usefull for ISP's that use phpWebFTP for their customers and only want to provide access to their own servers.
	#
	# Example :
	#    $defaultServer = "ftp.phpwebftp.tk";
	# Disable default server (default value);
	#    $defaultServer = "";

	$defaultServer = "";

	/*
		disable login screen and POST login. For use inside hosting management
		systems, where you don't want the user to be able to do anything else
		than automagically being logged in to their own home directory.

		example code:


		$_SESSION["server"] = "ftp.example.net";
		$_SESSION["user"] = "joesixpack"
		$_SESSION["password"] = "joe66"
		$_SESSION["language"] = "en";
		$_SESSION["port"] = "21";
		$_SESSION["passive"] = false;

		header("Location: http://url/of/phpwebftp/index.php");
	*/
	$disableLoginScreen = false;


	# editDefaultServer
	#----------
	# Use the defaultServer variable to set a static server rather then having the user type a server.
	# This could be usefull for ISP's that use phpWebFTP for their customers and only want to provide access to their own servers.
	#
	# Disable editable default server :
	#    $editDefaultServer = false;
	# Enable editable default server (default value);
	#    $editDefaultServer = true;
	
	$editDefaultServer = true;


	# clearTemp
	#----------
	# If set on true, WebFTP will empty the temporary download directory after each
	# file upload and download. 
	#
	# If you want to empty the downloadDir
	#	$clearTemp = true;
	# If you don't want to empty the downloadDir
	#   $clearTemp = false (default);
	
	$clearTemp = true;   // delete all files in the temp dir

	//That's all there is to configure

?>
