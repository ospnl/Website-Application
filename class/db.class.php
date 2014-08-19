<?php
/**********************************************

Pure-Sites 2011

Author: Oliver Smith
Build: 90
db.class.php

**********************************************/

class db{

    /*** Declare instance ***/
    private static $instance = null;
    
    private static $isError = false;

    /**
    *
    * the constructor is set to private so
    * so nobody can create a new instance using new
    *
    */
    private function __construct() {
      /*** maybe set the db name here later ***/
	  error_log("Contructing DB instance at " .date("r"));
    }

    /**
    *
    * Return DB instance or create intitial connection
    *
    * @return object (PDO)
    *
    * @access public
    *
    */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            try
            {
				error_log("Get instance new PDO at " .date("r"));
                self::$instance = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME, DB_USER, DB_PASS);
				error_log("Setting attributes at " .date("r"));
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				error_log("Setting names at " .date("r"));
                self::$instance->exec("SET NAMES utf8");
            }
            catch(PDOException $e)
            {
              echo 'PDO ERROR!!!';
              $emailBody=null;
              // All DB errors should be mailed straight away to support
              $emailBody.='***** ERROR REPORT *****'."\r\n";
              $emailBody.='Website URL: '.WEBSITE_URL."\r\n";
              $emailBody.='Failed to connect to database'."\r\n";
              $emailBody.='Error Message:'."\r\n";
              $emailBody.=$e->getMessage()."\r\n";
              // Commented out as this sends DB credentials (password)
              // $emailBody.='Stack Trace:'."\r\n";
              // $emailBody.=$e->getTraceAsString()."\r\n";  
              $emailBody.='***** END OF ERROR REPORT *****';
              
              // mail('support@pure-sites.com','Error Report',$emailBody);
              
              
              // Determine if the max connections has been reached
              // If it has we want to redirect them to a server busy page
              if($e->getCode()=='1040'||$e->getCode()=='1203') // 1040 is the error message for max_connections_reached.
              {
                $page=new Page();
                $page->errorPage('503',userControl::user()->getUserLanguage());
                $page=null;
              }
              else
              {
                $page=new Page();
                $page->errorPage('500',userControl::user()->getUserLanguage());
                $page=null;
              }
              
              
              self::$isError=true;
              
              throw new Exception('Could not connect to the database.');
              die();
              
            }
            
            
        }
		
		error_log("Returning instance at " .date("r"));
        return self::$instance;
		error_log("Returned at " .date("r"));
    }

    /**
    *
    * Like the constructor, we make __clone private
    * so nobody can clone the instance
    *
    */
    private function __clone(){
    }
	
	/*
	* Called when object is destroyed or script ends
	*/
	
	private function __destruct(){
		error_log("Destructing at " .date("r"));
		self::$instance=null;
	}

}
?>
