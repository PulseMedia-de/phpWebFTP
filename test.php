<?
//error_reporting(E_ALL);
session_start();
$_SESSION["bart"] = "kuik";
?><pre><?
print_r($_SESSION);
?></pre><?
phpinfo();
?>
