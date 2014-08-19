<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require '../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());
$page->loadTemplate('admin/');


if(isset($_POST['login']))
{
	$_SESSION['username']=$_POST['username'];
	$_SESSION['password']=$_POST['password'];
	header('Location: index.php');
}


if(isset($_SESSION['username']))
{
    $username=$_SESSION['username'];
    $password=$_SESSION['password'];

    $requestedSection=$_REQUEST['s'];

    if(userControl::user()->login($username,$password))
    {
        //Resource tracker switch handler
        //Sets a session variable to define whether to show the string ids or not.
        if($requestedSection=='enablers'){
            $_SESSION[WEBSITE_URL.'showstringid']=true;
        }
        if($requestedSection=='disablers'){
            $_SESSION[WEBSITE_URL.'showstringid']=false;
        }

        // Breadcrumb
        $page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
        $page->addPageContent('<div class="clear"></div>');
		
		$page->addPageContent('<p><a href="/admin/AdministrationGuide.pdf">Administration Guide</a></p>');

        $dashboard=new systemConfiguration();
        $page->addPageContent($dashboard->getDashboard());
        $dashboard=null;
    }
}
else
{
    //Login form
    //Title
    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.Login',userControl::user()->getUserLanguage()).'</h2>');
    $page->addPageContent('<form action="index.php" method="post" name="loginform"><h3>'.$strings->getStringByName('Administration.LoginUsername',userControl::user()->getUserLanguage()).'</h3><p><input type="text" name="username" /></p><h3>'.$strings->getStringByName('Administration.LoginPassword',userControl::user()->getUserLanguage()).'</h3><p><input type="password" name="password" /></p><p><input type="submit" name="login" value="'.$strings->getStringByName('Administration.LoginSubmit',userControl::user()->getUserLanguage()).'" /></p></form>');
}

$page->noGalleries();
$page->display();
?>
