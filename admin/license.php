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
				$page->addPageContent('<h2>'.$strings->getStringByName('Administration.License',userControl::user()->getUserLanguage(),1).'</h2>');

                $page->addPageContent('<div class="licenseoverview">');
				$page->addPageContent(license::getInstance()->getLicenseOverview());
                $page->addPageContent('</div>');
                
                $page->addPageContent('<h2>'.$strings->getStringByName('Administration.License.AddLicense',userControl::user()->getUserLanguage(),1).'</h2>');
                
                $page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.js');
                $page->addCustomJavaScript('function ajaxSave() {'."\n"
                    .'$("#savebutton").attr(\'value\', \''.$strings->getStringByName('Administration.License.AddLicense.Adding',userControl::user()->getUserLanguage(),1).'\');'."\n"
                    .'var newlicense = $(\'#newlicensestring\').val()'."\n"
                    .'$.ajax({'."\n"
                    .'url: "ajax/updatelicense.php",'."\n"
                    .'type: "POST",'."\n"
                    .'data: ({newlicensestring: newlicense}),'."\n"
                    .'dataType: \'json\','."\n"
                    .'success: function (json) {'."\n"
                    .'$("#savebutton").attr(\'value\', \''.$strings->getStringByName('Administration.License.AddLicense.Add',userControl::user()->getUserLanguage(),1).'\');'."\n"
                    .'if(json.isValid==\'true\'){'."\n"
                    .'$("div.output").show();'."\n"
                    .'$("div.output").html(\'<p style="text-indent:0;">\' + json.successMessage + \'</p>\');'."\n"
                    .'$("#newlicensestring").hide();'."\n"
                    .'$("#savebutton").hide();'."\n"
                    .'$("#commitbuttons").show();'."\n"
                    .'}'."\n"
                    .'else{'."\n"
                    .'$("div.output").show();'."\n"
                    .'$("div.output").html(\'<p style="color:red;text-indent:0;">\' +json.errorMessage + \'</p>\');'."\n"
                    .'$("div.output").fadeOut(3000);'."\n"
                    .'}'."\n"
                    .'}'."\n"
                    .'})'."\n"
                    .'}'."\n"
                    .'function ajaxCommitLicense() {'."\n"
                    .'$("#savebutton").attr(\'value\', \''.$strings->getStringByName('Administration.License.AddLicense.Adding',userControl::user()->getUserLanguage(),1).'\');'."\n"
                    .'var newlicense = $(\'#newlicensestring\').val()'."\n"
                    .'var commit =\'true\' '."\n"
                    .'$.ajax({'."\n"
                    .'url: "ajax/updatelicense.php",'."\n"
                    .'type: "POST",'."\n"
                    .'data: ({newlicensestring: newlicense, commitlicense: commit}),'."\n"
                    .'dataType: \'json\','."\n"
                    .'success: function (json) {'."\n"
                    .'$("#savebutton").attr(\'value\', \''.$strings->getStringByName('Administration.License.AddLicense.Add',userControl::user()->getUserLanguage(),1).'\');'."\n"
                    .'if(json.isValid==\'true\'){'."\n"
                    .'$("div.output").html(\'<p style="text-indent:0;">\' + json.successMessage + \'</p>\');'."\n"
                    .'$("div.licenseoverview").html(json.licenseOverview);'."\n"
                    .'$("div.licensekeyoverview").html(json.licenseKeyOverview);'."\n"
                    .'$("#newlicensestring").val(\'\');'."\n"
                    .'$("#newlicensestring").show();'."\n"
                    .'$("#savebutton").show();'."\n"
                    .'$("#commitbuttons").hide();'."\n"
                    .'$("div.output").fadeOut(3000);'."\n"
                    .'}'."\n"
                    .'else{'."\n"
                    .'$("div.output").show();'."\n"
                    .'$("#commitbuttons").hide();'."\n"
                    .'$("#newlicensestring").val(\'\');'."\n"
                    .'$("#newlicensestring").show();'."\n"
                    .'$("#savebutton").show();'."\n"
                    .'$("div.output").html(\'<p style="color:red;text-indent:0;">\' +json.errorMessage + \'</p>\');'."\n"
                    .'$("div.output").fadeOut(3000);'."\n"
                    .'}'."\n"
                    .'}'."\n"
                    .'})'."\n"
                    .'}'."\n"
                    .'function commitCancel(){'."\n"
                    .'$("#commitbuttons").hide();'."\n"
                    .'$("#newlicensestring").show();'."\n"
                    .'$("#savebutton").show();'."\n"
                    .'$("div.output").html(\'\');'."\n"
                    .'}');
                $page->addPageContent('<div style="float:left;">');
                $page->addPageContent('<div class="output"></div>');
                $page->addPageContent('<form action="license.php" method="post"><textarea name="newlicensestring" id="newlicensestring" rows="4" cols="60"></textarea></form>');
                $page->addPageContent('</div>');
                $page->addPageContent('<div style="float:left;">');
                $page->addPageContent('<p><input class="submitbutton" onclick="ajaxSave();return false;" value="'.$strings->getStringByName('Administration.License.AddLicense.Add',userControl::user()->getUserLanguage(),1).'" type="button" id="savebutton" /></p>');
                $page->addPageContent('<p id="commitbuttons" style="display:none;">&nbsp;&nbsp;<a href="#" onclick="ajaxCommitLicense();return false;"><strong>'.$strings->getStringByName('Administration.License.AddLicense.CommitYes',userControl::user()->getUserLanguage(),1).'</strong></a>&nbsp;&nbsp;<a href="#" onclick="commitCancel();return false;">'.$strings->getStringByName('Administration.License.AddLicense.CommitCancel',userControl::user()->getUserLanguage(),1).'</a></p>');
                $page->addPageContent('</div>');
                $page->addPageContent('<div class="clear"></div>');
                
                
                
                $page->addPageContent('<h2>'.$strings->getStringByName('Administration.License.Keys',userControl::user()->getUserLanguage(),1).'</h2>');
                $page->addPageContent('<div class="licensekeyoverview">'.license::getInstance()->getLicenseKeyOverview().'</div>');
                
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
