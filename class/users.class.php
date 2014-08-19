<?php
/**********************************************

Pure-Sites 2010
Author: Oliver Smith
Build: 100
Last Revision: 07/01/2012

users.class.php

TAB SIZE: 4

**********************************************/


class userControl
{
    private static $userLanguage;
    private static $userObj;
    private static $dateFormat;
    
    private function __construct()
    {
        // Determine language
        // The language parameter in the URL overrides cookie setting and browser language
        
        // If the language parameter is not null then we set the language as cookie
        if(isset($_REQUEST['l'])){
          $userLanguage=$_REQUEST['l'];
          setcookie('lang_cookie',$userLanguage,time()+LANGUAGE_COOKIE_TIMEOUT,'/');
        }
        // Load the language from cookie if it is set
        elseif(isset($_COOKIE['lang_cookie'])){
          $userLanguage=$_COOKIE['lang_cookie'];
        }
        // Else fallback to browser language
        else{
          $userLanguage=substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
          setcookie('lang_cookie',$userLanguage,time()+LANGUAGE_COOKIE_TIMEOUT,'/');
        }
      
        // Verify the language exists
        $languageControl=new lang();
        if(!$languageControl->doesLanguageExist($userLanguage))
        {
          $userLanguage=FALLBACK_LANGUAGE;
        }
        self::$userLanguage=$userLanguage;
    }
    
    public static function user()
    {
      if(!self::$userObj)
      {
        self::$userObj=new userControl;
        
      }
      return self::$userObj;
    }
    
    public static function getUserLanguage()
    {
        return self::$userLanguage;
    }
    
    public function logout()
    {
        session_destroy();
    }
    
    public static function getDateFormat()
    {
      if(!self::$dateFormat)
      {
        $mysqlDateFormat=DB::getInstance()->prepare("SELECT `mysqldateformat` FROM `languages` WHERE `twocharacterabbr`=:language");
        $mysqlDateFormat->bindParam(':language',self::$userLanguage);
        $mysqlDateFormat->execute();
        self::$dateFormat=$mysqlDateFormat->fetchColumn();
      }
      return self::$dateFormat;
    }
    
    public function login($userName,$password)
    {
    	$loginQuery=DB::getInstance()->prepare("SELECT `username` FROM `users` WHERE `username`=:userName AND `password`=MD5(CONCAT(:password,`salt`))");
        $loginQuery->bindParam(':userName',$userName);
        $loginQuery->bindParam(':password',$password);
        $loginQuery->execute();
    	if($loginQuery->rowCount()==1)
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }
    
	public static function reload()
	{
		self::$userObj=null;
	}
}
?>
