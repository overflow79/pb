<?
header('Access-Control-Allow-Origin: *');
require_once "PB.php";
$m = $_REQUEST['m'];
$PB = new PB();
$PB->init($m);
?>
