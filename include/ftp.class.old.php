<?php
/** FTP class is designed to work with FTP Connections
@author Edwin van Wijk, www.v-wijk.net
@email info@vwijk.net
*/

class ftp {
	/** FTP server */
	var $server="";
	/** FTP server port */
	var $port=21;
	/** FTP user */
	var $user="";
	/** User specific directory (for zip and download) */
	var $userDir="";
	/** password */
	var $password = "";
	/** FTP connection */
	var $connection = "";
	/** Passive FTP connection */
	var $passive = false;
	/** Type of FTP server (UNIX, Windows, ...) */
	var $systype = "";
	/** Binary (1) or ASCII (0) mode */
	var $mode = 1;
	/** Logon indicator */
	var $loggedOn = false;
	/** resume broken downloads */
	var $resumeDownload = false;
	/** temporary download directory on local server */
	var $downloadDir = "";

	/**	constructor
	@param none
	Set FTP settings and logon to the server
	*/
	function ftp($server, $port, $user, $password, $passive=false){
		$this->server = $server;
		$this->port = $port;
		$this->user = $user;
		$this->userDir = $user . "_tmp";
		$this->password = $password;

		// connect to server
		$this->connect();

		// switch to passivemode(?)
		$this->setPassive($passive);
	}

	/** connect to a ftp server */
	function connect() {
		$this->connection = @ftp_connect($this->server, $this->port);
		$this->loggedOn = @ftp_login($this->connection, $this->user, $this->password);
		$this->systype = @ftp_systype($this->connection);
		return;
	}

	/** set passive connection */
	function setPassive($passive) {
		$this->passive=$passive;
		@ftp_pasv($this->connection, $this->passive);
		return;
	}

	/** Set transfermode */
	function setMode($mode=0) {
		$this->mode = $mode;
		return;
	}

	/** set and goto current directory on ftp server */
	function setCurrentDir($dir=false) {
		if ($dir==true)
		{
			ftp_chdir($this->connection, $dir);
		}
		$this->currentDir = ftp_pwd($this->connection);
		return $this->currentDir;
	}

	function getCurrentDirectoryShort() {
		$string = $this->currentDir;
		$stringArray = split("/",$string);
		$level = count($stringArray);
		$returnString = $stringArray[$level-1];
		if(trim($returnString)=="") {
			$returnString = "/";
		}
		return $returnString;
	}

	function setDownloadDir($dir) {
		$this->downloadDir = $dir;
		return;
	}

	function setResumeDownload($resume) {
		$this->resumeDownload = $resume;
		return;
	}

	function chmod($permissions, $file) {
		return @ftp_site($this->connection, "chmod $permissions $file");
	}

	function cd($directory) {
		$blnSuccess = false;

		if ($directory=="..") {
			$blnSuccess = @ftp_cdup($this->connection);
		} else {
			$blnSuccess = @ftp_chdir($this->connection, $this->currentDir . $directory);

			if (!$blnSuccess) {
				$blnSuccess = @ftp_chdir($this->connection, $directory); // Symbolic link directory 
			}
		}
		$this->currentDir=ftp_pwd($this->connection);;
		return $blnSuccess;
	}

	/* get file from ftp server */
	function get($file,$destination = "") {
		if($destination == ""){
			$destination = $this->downloadDir;
		}
		$ok=true;
		if($this->resumeDownload) {
			$fp = fopen($destination . $file, "a+");
			$ok = ftp_fget($this->connection,$fp,"$file",$this->mode, filesize($destination . $file));
		} else {
			$fp = fopen($destination . $file, "w");
			$ok = ftp_fget($this->connection,$fp,"$file",$this->mode);
		}
		fclose($fp);
		return $ok;
	}

	/* put file to ftp server */
	function put($remoteFile,$localFile) {
		$ok=false;
		if(file_exists($localFile)) {
			if($this->mode == 0)
				ftp_put($this->connection, $remoteFile, $localFile, FTP_ASCII);
			else
				ftp_put($this->connection, $remoteFile, $localFile, FTP_BINARY);
			$ok=true;
		}
		return $ok;
	}

	/* Download file from server and send it to the browser */
	function download($file) {
		$fileStream = "";
		if($this->get($file)) {
			//Send header to browser to receive a file
			header("Content-disposition: attachment; filename=\"$file\"");
			header("Content-type: application/octet-stream");
			header("Pragma: ");
			header("Cache-Control: cache");
			header("Expires: 0");
			$data = readfile($this->downloadDir . $file);
			$i=0;
			while ($data[$i] != "")
			{
				$fileStream .= $data[$i];
				$i++;
			}
			unlink($this->downloadDir . $file);
			echo $fileStream;
			exit;
		} else {
			return false;
		}
	}
	
	function upload($uploadFile) {
		$tempFileName = $uploadFile['tmp_name'];
		$fileName = $uploadFile['name'];
		return $this->put($this->currentDir . "/" . filePart(StripSlashes($fileName)), $tempFileName);
	}

	function deleteFile($file) {
		return @ftp_delete($this->connection, "$file");
	}

	// BK20061117
	function deleteDir($dir) {
		return ftp_rmdir($this->connection, "$dir");
	}

	// deleteRecursive : rewrite BK20061117
	function deleteRecursive($item){

		// if $item is dir, cd to it
		if (@$this->cd($item)) {

			// get list of files
			$list = $this->ftpRawList($this->currentDir);

			// directory not empty?
			if (is_array($list)) {

				// each item
				foreach ($list as $listItem) {

					// delete recursive
					$this->deleteRecursive($listItem["name"]);
				}
			}

			// cd down
			$this->cd("..");

			// delete empty dir
			$this->deleteDir($item);

		// else remove file
		} else {
			$this->deleteFile($item);
		}
	}

	function rename($old, $new) {
		return @ftp_rename($this->connection, "$old", "$new");
	}

	function makeDir($directory) {
		return @ftp_mkdir($this->connection, "$directory");
	}

	function getRecursive($baseDir,$file){
		$files = $this->ftpRawList($baseDir . "/$file");

		for ($x=0;$x<count($files);$x++){
			if ($files[$x]["name"] != '.' or $files[$x]["name"] != '..') {
				$downloadLocation = $this->downloadDir  . ereg_replace($this->currentDir."/",$this->userDir."/",$baseDir . "/$file/");
				$downloadLocation = ereg_replace("//","/",$downloadLocation);
				$ftpFileDir = ereg_replace($this->currentDir . "/","",$baseDir . "/$file/");
				//print $downloadLocation . "(" . $baseDir . "/$file/" . ")<br>";
				mkdir($downloadLocation);

				if ($files[$x]["is_dir"]==1)
				{
					$this->getRecursive($baseDir . "/$file/",$files[$x]["name"]);
				} else {
					$localFile = $this->downloadDir . $this->userDir . "/" . ereg_replace($this->currentDir . "/","",$baseDir . "/$file/") . $files[$x]["name"];;
					$remoteFile = $baseDir . "/" . $file . "/" . $files[$x]["name"];
					
					if($this->resumeDownload) {
						$fp = fopen($localFile, "a+");
						$ok = ftp_fget($this->connection,$fp,"$remoteFile",$this->mode, filesize($localFile));
					} else {
						$fp = fopen($localFile, "w");
						$ok = ftp_fget($this->connection,$fp,"$remoteFile",$this->mode);
					}
					fclose($fp);
				}
			}
		}
	}

	// BK20061116
	function putRecursive($ftpDir, $localDir = ".") {

		if ($localDir != ".." and $localDir != ".") {
			chdir($localDir) or die;
		}

		$list = glob("*");

		foreach ($list as $item) {
			if (is_dir($item)) {
				if (!$this->cd($item)) {
					$this->makeDir($item);
					$this->cd($item);
				}

				$this->putRecursive($item, $item);
				chdir("..");
				$this->cd("..");
			} else {
				$this->put($item, $item);
			}
		}
	}

	function ftpRawList($directory = "") {
		if($directory=="") {
			$directory = $this->currentDir;
		}
		$list=Array();
		$list = ftp_rawlist($this->connection, "-a " . $directory);
		if ($this->systype == "UNIX")
		{
			//$regexp = "([-ldrwxs]{10})[ ]+([0-9]+)[ ]+([A-Z|0-9|-]+)[ ]+([A-Z|0-9|-]+)[ ]+([0-9]+)[ ]+([A-Z]{3}[ ]+[0-9]{1,2}[ ]+[0-9|:]{4,5})[ ]+(.*)";
			//$regexp = "([-ldrwxs]{10})[ ]+([0-9]+)[ ]+([A-Z|0-9|-|_]+)[ ]+([A-Z|0-9|-|_]+)[ ]+([0-9]+)[ ]+([A-Z]{3}[ ]+[0-9]{1,2}[ ]+[0-9|:]{4,5})[ ]+(.*)";
			//$regexp = "([-ltdrwxs]{10})[ ]+([0-9]+)[ ]+([A-Z|0-9|-|_]+)[ ]+([A-Z|0-9|-|_]+)[ ]+([0-9]+)[ ]+([A-Z]{3}[ ]+[0-9]{1,2}[ ]+[0-9|:]{4,5})[ ]+(.*)";
			$regexp = "/";
			$regexp .= "([\-ltdrwxs]{10})"; 	// permissions, $regs[1]
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "(\d+)";					// numbers (?), $regs[2]
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "([\d\w\-_]+)";			// user, $regs[3]
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "([\d\w\-_]+)";			// group, $regs[4]
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "(\d+)";					// size, $regs[5]
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "(";						/* start of date ($regs[6]) */ 
			$regexp .= "\w{3}";					// month
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "\d{1,2}";				// day of month
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "[\d:]{4,5}";			// time
			$regexp .= ")";						/* end of date ($regs[6]) */ 
			$regexp .= "\s+";					// one or more spaces
			$regexp .= "(.+)";					// filename, $regs[7]
			$regexp .= "/";

			$i=0;
			foreach ($list as $line) 
			{
				$is_dir = $is_link = FALSE;
				$target = "";

				if (preg_match($regexp, $line, $regs))
				{
//					if (!preg_match("/^\./", $regs[7])) //hide hidden files
					if (!preg_match("/^\.$/", $regs[7])) 	// hide "."
					if (!preg_match("/^\.{2}/", $regs[7]))	// hide ".."
					{
						$i++;
						if (preg_match("/^d/", $regs[1]))
						{
							$is_dir = TRUE;
						}
						elseif (preg_match("/^l/", $regs[1])) 
						{ 
							$is_link = TRUE;
							list($regs[7], $target) = split(" -> ", $regs[7]);
						}

						//Get extension from file name
						$regs_ex = explode(".",$regs[7]);
						if ((!$is_dir)&&(count($regs_ex) > 1))
						   $extension = $regs_ex[count($regs_ex)-1];
						else $extension = "";

						$files[$i] = array (
						"is_dir"	=> $is_dir,
						"extension"	=> $extension,
						"name"		=> $regs[7],
						"perms"		=> $regs[1],
						"num"		=> $regs[2],
						"user"		=> $regs[3],
						"group"		=> $regs[4],
						"size"		=> $regs[5],
						"date"		=> $regs[6],
						"is_link"	=> $is_link,
						"target"	=> $target );						
					}
				}
			}
		}
		else
		{
			//$regexp = "([0-9\-]{8})[ ]+([0-9:]{5}[APM]{2})[ ]+([0-9|<DIR>]+)[ ]+(.*)";

			$regexp = "/";
			$regexp .= "([\d\-]{8})";				// date, $regs[1]
			$regexp .= "\s+";						// one or more spaces
			$regexp .= "(\d{1,2}:\d{1,2}[AP][M])";	// time, $regs[2]
			$regexp .= "\s+";						// one or more spaces
			$regexp .= "([0-9|<DIR>]+)";			// dir or file size, $regs[3]
			$regexp .= "\s+";						// one or more spaces
			$regexp .= "(.*)";						// filename, $regs[4]
			$regexp .= "/";

			$i = 0;
			foreach ($list as $line) 
			{
				$is_dir = false;
				if (preg_match($regexp, $line, $regs)) 
				{
					if (!preg_match("/^\./", $regs[4]))
					{
						if($regs[3] == "<DIR>")
						{
							$is_dir = true;
							$regs[3] = '';
						}
						$i++;
	
						// Get extension from filename
						$regs_ex = explode(".",$regs[4]);
						if ((!$is_dir)&&(count($regs_ex) > 1))
						   $extension = $regs_ex[count($regs_ex)-1];
						else $extension = "";

						$files[$i] = array (
							"is_dir"	=> $is_dir,
							"extension"	=> $extension,
							"name"		=> $regs[4],
							"date"		=> $regs[1],
							"time"		=> $regs[2],
							"size"		=> $regs[3],
							"is_link"	=> 0,
							"perms"		=> "",
							"user"		=> "",
							"group"		=> "",
							"target"	=> "",
							"num"		=> "" );
					}
				}
			}
		}
		if (isset($files) and is_array($files)  AND count($files) > 0)
		{
			$files=array_sort_multi($files, 1, 3);
		}

		if (isset($files)) {
			return $files;
		} else {
			return null;
		}
	}

}
?>
