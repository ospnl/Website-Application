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

        if($requestedSection=='stringcache'||$requestedSection=='rebuildallcache'){
            $strings->buildStringCache();
        }
        if($requestedSection=='pagecache'||$requestedSection=='rebuildallcache'){
            $cache=new Cache();
            $cache->flush();
            $cache=null;
        }
		if($requestedSection=='builderror'||$requestedSection=='rebuildallcache'){
            $errorPages=new Page();
			$errorPages->buildErrorPages();
			$errorPages=null;
        }

        // Breadcrumb
        $page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
        $page->addPageContent('<div class="clear"></div>');

		$page->addPageContent('<h2>'.$strings->getStringByName('Administration.Cache.Title',userControl::user()->getUserLanguage(),1).'</h2>');
		
		
        $page->addPageContent('<p><a href="cache.php?s=stringcache">'.$strings->getStringByName('Administration.Cache.RebuildStringCache',userControl::user()->getUserLanguage(),1).'</a></p>');
        $page->addPageContent('<p><a href="cache.php?s=pagecache">'.$strings->getStringByName('Administration.Cache.RebuildPageCache',userControl::user()->getUserLanguage(),1).'</a></p>');
        $page->addPageContent('<p><a href="cache.php?s=rebuildallcache">'.$strings->getStringByName('Administration.Cache.RebuildAllCache',userControl::user()->getUserLanguage(),1).'</a></p>');

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