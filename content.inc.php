<?php
/*
Oliver Smith

This page handles all the whole front end
*/

$serverName=$_SERVER['SERVER_NAME'];

if(substr($serverName,0,4)=='www.'){
	$serverName=substr($serverName,4);
}

$serverName=str_replace('.','',$serverName);
$serverName=str_replace('-','',$serverName);

require '/home/'.$serverName.'/config.inc.php';

require CLASS_DIR.'page.class.php';
require CLASS_DIR.'galleries.class.php';
require CLASS_DIR.'strings.class.php';
require CLASS_DIR.'system.class.php';
require CLASS_DIR.'db.class.php';
require CLASS_DIR.'forms.class.php';
require CLASS_DIR.'users.class.php';
require CLASS_DIR.'lang.class.php';
require CLASS_DIR.'images.class.php';
require CLASS_DIR.'templates.class.php';
require CLASS_DIR.'license.class.php';

header('Content-Type: text/html; charset=utf-8');

?>
