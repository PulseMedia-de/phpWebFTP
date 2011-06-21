var selectItem = "";
var renameOn = false;
var permissionsOn = false;
permission="";

function logOff() {
	document.actionform.actionType.value="logoff";
	document.actionform.submit();
}

function anonymousAccess()
{
	if(document.logon.user.value == 'anonymous')
	{
	  document.logon.user.value = '';
	  document.logon.user.focus();
	} else {
	  document.logon.user.value = 'anonymous';
	  document.logon.password.focus();
	}
};

function submitForm(action, file, file2)
{
  document.actionform.actionType.value = action;
  document.actionform.file.value = file;
  document.actionform.file2.value = file2;
  document.actionform.submit();
};

function toggle(layer) {
	if (document.getElementById(layer).style.display=="none")
	{
		document.getElementById(layer).style.display="block";
	} else {
		document.getElementById(layer).style.display="none";
	}
}

function setNewFileName()
{
	if(renameOn) {
		document.getElementById("renameFileEntry").style.display="none";
		renameOn = false;
	} else {
		document.actionform.actionType.value="rename";
		document.getElementById("renameFileEntry").style.display="block";
		document.renameFile.newName.value=document.actionform.file.value;
		document.renameFile.newName.focus();
		renameOn=true;
	}
};

function renameItem()
{
	oldName = document.actionform.file.value;
	newName = document.renameFile.newName.value
	if (confirm("rename " + document.actionform.file.value + " to " + document.renameFile.newName.value + "?\n"))
	{
		submitForm("rename", oldName, newName)
	}
};

function setPermissions()
{
	if(permissionsOn) {
		document.actionform.actionType.value="";
		document.getElementById("setPermissions").style.display="none";
		permissionsOn = false;
	} else {
		document.actionform.actionType.value="chmod";
		document.getElementById("setPermissions").style.display="block";
		permission=document.actionform.permissions.value;
		permissionsOn = true;

		Or=permission.substring(1,2);
		Gr=permission.substring(4,5);
		Pr=permission.substring(7,8);

		Ow=permission.substring(2,3);
		Gw=permission.substring(5,6);
		Pw=permission.substring(8,9);

		Ox=permission.substring(3,4);
		Gx=permission.substring(6,7);
		Px=permission.substring(9,10);

		focus();
		if(Or!="-") { document.permissions.iOr.checked = true }
		if(Gr!="-") { document.permissions.iGr.checked = true }
		if(Pr!="-") { document.permissions.iPr.checked = true }

		if(Ow!="-") { document.permissions.iOw.checked = true }
		if(Gw!="-") { document.permissions.iGw.checked = true }
		if(Pw!="-") { document.permissions.iPw.checked = true }

		if(Ox!="-") { document.permissions.iOx.checked = true }
		if(Gx!="-") { document.permissions.iGx.checked = true }
		if(Px!="-") { document.permissions.iPx.checked = true }
	}
}

function resetEntries()
{
	document.actionform.actionType.value = "";
	document.actionform.delaction.value = "";
	document.actionform.file.value = "";
	document.actionform.file2.value = "";

	counter=0;

	while(document.getElementById("filename" + counter)) {
	  document.getElementById("filename" + counter).style.background = "#F7F7F7";
	  document.getElementById("filename" + counter).style.color = "black";
	  counter++;
	}

	document.getElementById("setPermissions").style.display="none";
	permissionsOn = false;

	document.getElementById("renameFileEntry").style.display="none";
	renameOn = false;

	document.getElementById("action_delete").style.display="none";
	document.getElementById("action_zipdl").style.display="none";
	document.getElementById("action_unzip").style.display="none";
	document.getElementById("action_edit").style.display="none";
	// view file
	document.getElementById("action_view").style.display="none";
	document.getElementById("action_rename").style.display="none";
	document.getElementById("action_permissions").style.display="none";

}

function selectEntry(action, file, item, owner, permissions, delaction)
{
	//to disable user check, set owner to match currentUser
	owner = currentUser;


	resetEntries()
	document.actionform.actionType.value = action;
	document.actionform.delaction.value = delaction;
	document.actionform.file.value = file;
	document.actionform.permissions.value = permissions;

	document.actionform.extension.value = "";
	if (file.match(/\.(..?.?.?)$/)) {
		document.actionform.extension.value = file.match(/\.(..?.?.?)$/i)[1];
		document.actionform.extension.value = document.actionform.extension.value.toLowerCase();
	}

	document.getElementById(item).style.color = "white";
	document.getElementById(item).style.backgroundColor = "#316AC5";
	selectItem=item;

	// only delete if user owns the file (or if owner is empty (Windows))
	if (
		owner == "" 
		|| 
		owner == currentUser
	) 
	{
		document.getElementById("action_delete").style.display="block";
	}

	document.getElementById("action_zipdl").style.display="block";

	var ext;
	ext = isZip(document.actionform.file.value);

	if (ext) {
		var filename = document.actionform.file.value;
		filename = filename.substr(0, filename.length - ext.length - 1);
		document.getElementById("unzipto").innerHTML = "unzip to " + filename + "/";
		document.getElementById("action_unzip").style.display="block";
	}

	// show view or edit button.
	// index.php decides if the file should be viewable, editable, or nothing.
	var ext = document.actionform.extension.value;
	if (
		(
			owner == "" 
			|| 
			owner == currentUser
		)
		&&
		delaction == "delfile"
		&&
		(
			// editable 
			ext == "html" ||
			ext == "htm" ||
			ext == "php" ||
			ext == "xml" ||
			ext == "txt" ||
			ext == "css" ||
			ext == "pl" ||
			ext == "py" ||
			ext == "sh"
		)
	)
	{

		document.getElementById("action_edit").style.display="block";
	}

	// view file
	if (
		(
			owner == "" 
			|| 
			owner == currentUser
		)
		&&
		delaction == "delfile"
		&&
		(
			// viewable 
			ext == "html" ||
			ext == "htm" ||
			ext == "php" ||
			ext == "xml" ||
			ext == "txt" ||
			ext == "css" ||
			ext == "pl" ||
			ext == "py" ||
			ext == "sh" ||
			ext == "jpg" ||
			ext == "jpeg" ||
			ext == "gif" ||
			ext == "png"
		)
	)
	{

		document.getElementById("action_view").style.display="block";
	}

	// only rename if user owns the file (or if owner is empty (Windows))
	if (
		owner == "" 
		|| 
		owner == currentUser
	) 
	{
		document.getElementById("action_rename").style.display="block";
	}

	// only edit permissions if user owns the file
	// this disables editing permissions for Windows users
	if (owner == currentUser) 
	{
		document.getElementById("action_permissions").style.display="block";
	}
}


function createDirectory(directory)
{
  if(directory)
  {submitForm("createdir", directory);}
  else
  {alert('Enter a directory name first');}
};

function changeMode(mode)
{
	document.actionform.actionType.value = "changemode";
  document.actionform.mode.value = mode;
  document.actionform.submit();
};


function deleteFile()
{
	if (confirm("Really delete this Item ?\n"))
	{
		document.actionform.actionType.value = document.actionform.delaction.value;
		document.actionform.submit();
	}
};

function editFile()
{
	if(document.actionform.delaction.value == "delfile") {
		document.actionform.actionType.value = "edit";
		document.actionform.submit();
	} else {
		alert("Sorry, this function is only available for files");
	}
};

// view file if inside www or public_html folder
function viewFile() {
	window.open("http://" + site + "/" + dir + "/" + document.actionform.file.value, "view", "resizable=yes,directories=yes,status=yes,scrollbars=yes,toolbar=yes,location=yes");
};

function zipFile()
{
	if(document.actionform.delaction.value == "deldir") {
		document.actionform.actionType.value = "getzip";
		document.actionform.submit();
	} else {
		document.actionform.actionType.value = "get";
		document.actionform.submit();
	}
};

// BK20061114
function unzipFile()
{
	document.actionform.actionType.value = "unzip";
	document.actionform.submit();
}

function cancelEditFile()
{
	document.editFileForm.actionType.value = "";
	document.editFileForm.submit();
}

function Confirmation(URL)
{
  if (confirm("Really delete this Item ?\n"))
  {location = String(URL);}
  else
  {
	  //Do nothing
  }
};

function ConfirmationUnzip(URL)
{
  if (confirm("Unzip File in the current dir ?\n"))
  {location = String(URL);}
};


function changePermissions()
{
	O=0;
	P=0;
	G=0;

	if(document.permissions.iOr.checked == true) { O=O+4 }
	if(document.permissions.iGr.checked == true) { G=G+4 }
	if(document.permissions.iPr.checked == true) { P=P+4 }

	if(document.permissions.iOw.checked == true) { O=O+2 }
	if(document.permissions.iGw.checked == true) { G=G+2 }
	if(document.permissions.iPw.checked == true) { P=P+2 }

	if(document.permissions.iOx.checked == true) { O=O+1 }
	if(document.permissions.iGx.checked == true) { G=G+1 }
	if(document.permissions.iPx.checked == true) { P=P+1 }

	document.actionform.permissions.value=O+""+G+""+P;
	document.actionform.action.value="chmod";
	document.actionform.submit()
}

// BK20061114
function isZip(file) {
	var ext;

	for (i=0; i<ucNum; i++) {
		ext = uncompress[i];
		if (file.match("\\." + ext + "$")) {
			return ext;
		}
	}

	return false;
}

function resize() {
	document.getElementById("filelist").style.height = (window.height - 40) + "px";
}
