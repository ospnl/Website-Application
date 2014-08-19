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
    if(isset($_REQUEST['s'])){
        $requestedSection=$_REQUEST['s'];
    }

    if(userControl::user()->login($username,$password)){

        try{
				// Breadcrumb navigation
				$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
				$page->addPageContent('<div class="clear"></div>');
				// Title
				$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ResetPassword',userControl::user()->getUserLanguage(),1).'</h2>');
				
				if(isset($_POST['oldpw'])){
					$saltQuery=DB::getInstance()->prepare("SELECT `salt` FROM `users` WHERE `username`=:username AND `deleted`=0");
                    $saltQuery->bindValue(':username',$username);
                    $saltQuery->execute();

                    while($saltResult=$saltQuery->fetch()){
                        $salt=$saltResult['salt'];
                    }
					
					if($_POST['oldpw']<>$_SESSION['password']){
						$page->addPageContent('<p>'.$strings->getStringByName('ResetPassword.IncorrectOldPassword',userControl::user()->getUserLanguage(),1).'</p>');
					}
					elseif($_POST['newpw']<>$_POST['confirmpw']){
						$page->addPageContent('<p>'.$strings->getStringByName('ResetPassword.PasswordMismatch',userControl::user()->getUserLanguage(),1).'</p>');
					}
					else{
						$changePW=DB::getInstance()->prepare("UPDATE `users` SET `password`=:password WHERE `username`=:username");
						$changePW->bindParam(':username',$username);
						$changePW->bindParam(':password',md5($_POST['newpw'].$salt));
						$changePW->execute();
						$_SESSION['password']=$_POST['newpw'];
						$page->addPageContent('<p>'.$strings->getStringByName('Administration.PasswordChanged',userControl::user()->getUserLanguage(),1).'</p>');
					}
				}
				
                $page->addPageContent('<div class="resetpassword">');
				$page->addPageContent('<form action="resetpassword.php" method="post"><table>');
				$page->addPageContent('<tr><td>'.$strings->getStringByName('ResetPassword.OldPassword',userControl::user()->getUserLanguage(),1).'</td><td><input type="password" name="oldpw" /></td></tr>');
				$page->addPageContent('<tr><td>'.$strings->getStringByName('ResetPassword.NewPassword',userControl::user()->getUserLanguage(),1).'</td><td><input type="password" name="newpw" /></td></tr>');
				$page->addPageContent('<tr><td>'.$strings->getStringByName('ResetPassword.ConfirmPassword',userControl::user()->getUserLanguage(),1).'</td><td><input type="password" name="confirmpw" /></td></tr>');
				$page->addPageContent('</table>');
				$page->addPageContent('<input type="submit" name="savepw" value="'.$strings->getStringByName('ResetPassword.SavePassword',userControl::user()->getUserLanguage(),1).'" />');
                $page->addPageContent('</div>');
                
                
                
			}
			catch(Exception $e)
			{
				$errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
				$errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
				$errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
				$errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
				$errorMessage.='Error: '.$e->getMessage()."\n";
				$errorMessage.='Trace: '.$e->getTraceAsString()."\n";

				$system=new systemConfiguration();
				$system->logError($errorMessage,3);
				$system=null;
				header('Location: '.WEBSITE_URL.'500_'.userControl::user()->getUserLanguage().'.html');
				exit;
			}

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
