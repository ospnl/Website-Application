<?php
/*
This page does not output html

*/
require '../../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());

// We will be outputing JSON data not XHTML
// this makes it easier for jQuery to process
header('Content-Type: text/javascript');
        
$jsonOutput=array();
$commitlicense=null;

if(isset($_SESSION['username'])){
    $username=$_SESSION['username'];
    $password=$_SESSION['password'];

    if(userControl::user()->login($username,$password)){
        
        
        
        
        
        if(isset($_POST['newlicensestring'])){
            if(isset($_POST['commitlicense'])){
                $commitlicense=$_POST['commitlicense'];
            }
            license::getInstance()->loadLicenseString($_POST['newlicensestring']);
            if(license::getInstance()->isLicenseValid()){
                $jsonOutput['isValid']='true';
                $jsonOutput['option']=license::getInstance()->licensedOption();
                $jsonOutput['optionValue']=license::getInstance()->licensedOptionValue();
                $jsonOutput['errorMessage']='';
                
                
                if($jsonOutput['option']=='UserPages'){
                    $jsonOutput['successMessage']=str_replace('%1',$jsonOutput['optionValue'],$strings->getStringByName('Administration.JSON.License.AddUserPageSuccessMessage',userControl::user()->getUserLanguage(),1)).'<br />';
                    $jsonOutput['successMessage'].=str_replace('%1',license::getInstance()->licenseDaysRemaining(),$strings->getStringByName('Administration.JSON.License.DaysLeft',userControl::user()->getUserLanguage(),1)).'<br />';
                    if($commitlicense){
                        if(license::getInstance()->doesLicenseExist()){
                            $jsonOutput['isValid']='false';
                            $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAlreadyExists',userControl::user()->getUserLanguage(),1);
                        }
                        else{
                            license::getInstance()->addLicense();
                            license::getInstance()->modifyLicense($jsonOutput['option'],$jsonOutput['optionValue']);
                            $jsonOutput['licenseOverview']=license::getInstance()->getLicenseOverview();
                            $jsonOutput['licenseKeyOverview']=license::getInstance()->getLicenseKeyOverview();
                            $jsonOutput['successMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAdded',userControl::user()->getUserLanguage(),1);
                        }
                    }
                    else{
                        $jsonOutput['successMessage'].=$strings->getStringByName('Administration.JSON.License.CommitPrompt',userControl::user()->getUserLanguage(),1);
                    }
                }
                // Languages section
                elseif($jsonOutput['option']=='Languages'){
                    $jsonOutput['successMessage']=str_replace('%1',$jsonOutput['optionValue'],$strings->getStringByName('Administration.JSON.License.AddLanguageSuccessMessage',userControl::user()->getUserLanguage(),1)).'<br />';
                    $jsonOutput['successMessage'].=str_replace('%1',license::getInstance()->licenseDaysRemaining(),$strings->getStringByName('Administration.JSON.License.DaysLeft',userControl::user()->getUserLanguage(),1)).'<br />';
                    if($commitlicense){
                        if(license::getInstance()->doesLicenseExist()){
                            $jsonOutput['isValid']='false';
                            $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAlreadyExists',userControl::user()->getUserLanguage(),1);
                        }
                        else{
                            license::getInstance()->addLicense();
                            license::getInstance()->modifyLicense($jsonOutput['option'],$jsonOutput['optionValue']);
                            $jsonOutput['licenseOverview']=license::getInstance()->getLicenseOverview();
                            $jsonOutput['licenseKeyOverview']=license::getInstance()->getLicenseKeyOverview();
                            $jsonOutput['successMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAdded',userControl::user()->getUserLanguage(),1);
                        }
                    }
                    else{
                        $jsonOutput['successMessage'].=$strings->getStringByName('Administration.JSON.License.CommitPrompt',userControl::user()->getUserLanguage(),1);
                    }
                }
                elseif($jsonOutput['option']=='Imaging'){
                    $jsonOutput['successMessage']=$strings->getStringByName('Administration.JSON.License.AddImagingSuccessMessage',userControl::user()->getUserLanguage(),1).'<br />';
                    $jsonOutput['successMessage'].=str_replace('%1',license::getInstance()->licenseDaysRemaining(),$strings->getStringByName('Administration.JSON.License.DaysLeft',userControl::user()->getUserLanguage(),1)).'<br />';
                    if($commitlicense){
                        if(license::getInstance()->doesLicenseExist()){
                            $jsonOutput['isValid']='false';
                            $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAlreadyExists',userControl::user()->getUserLanguage(),1);
                        }
                        else{
                            license::getInstance()->addLicense();
                            license::getInstance()->modifyLicense($jsonOutput['option'],$jsonOutput['optionValue']);
                            $jsonOutput['licenseOverview']=license::getInstance()->getLicenseOverview();
                            $jsonOutput['licenseKeyOverview']=license::getInstance()->getLicenseKeyOverview();
                            $jsonOutput['successMessage']=$strings->getStringByName('Administration.JSON.License.LicenseKeyAdded',userControl::user()->getUserLanguage(),1);
                        }
                    }
                    else{
                        $jsonOutput['successMessage'].=$strings->getStringByName('Administration.JSON.License.CommitPrompt',userControl::user()->getUserLanguage(),1);
                    }
                }
            }
            else{
                $jsonOutput['isValid']='false';
                $jsonOutput['option']='';
                $jsonOutput['optionValue']='';
                $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.InvalidLicense',userControl::user()->getUserLanguage(),1);
                $jsonOutput['successMessage']='';
            }
        }
        
        else{
            // Log error
            $jsonOutput['isValid']='false';
            $jsonOutput['option']='';
            $jsonOutput['optionValue']='';
            $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.PostError',userControl::user()->getUserLanguage(),1);
            $jsonOutput['successMessage']='';
        }
        
        
    }
}
else{
	$jsonOutput['isValid']='false';
    $jsonOutput['option']='';
    $jsonOutput['optionValue']='';
    $jsonOutput['errorMessage']=$strings->getStringByName('Administration.JSON.License.SessionExpired',userControl::user()->getUserLanguage(),1);
    $jsonOutput['successMessage']='';
}

print(json_encode($jsonOutput));


?>