<?php
/******************************
OS PHOTOGRAPHY
/index.php
Version: Espresso
******************************/

$scriptName = '/index.php'

// Enable Gzip output compression
ob_start("ob_gzhandler");

// Start user session
session_start();

// Include classes
require 'content.inc.php';

// Stored requested page in variable
$requestedPage = isset($_REQUEST['p']) ? $_REQUEST['p'] : '';

// Stored requested page in variable
$requestedWidget = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';

// Create page object
$page=new Page();

if($requestedWidget<>null){
	$page->loadWidget($requestedWidget,userControl::user()->getUserLanguage());
	$page->display();
	exit();
}

// If no page is specified, forward to home page
if($requestedPage==null){
	header('Location: '.WEBSITE_URL.userControl::user()->getUserLanguage().'/home');
	exit;
}

// Check the page exists; if it does not exist (even as an error page) forward to 404 page
if(!$page->doesPageExist($requestedPage,0)&&$page->doesPageExist($requestedPage,1)){
	$page->errorPage('404');
	exit;
}

// Load and display requested page
$page->loadPage($requestedPage,userControl::user()->getUserLanguage());
$page->display();


?>
