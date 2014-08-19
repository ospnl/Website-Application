<?php

/**********************************************



Pure-Sites 2010

Author: Oliver Smith

Build: 87

// <!-- phpDesigner :: Timestamp [05/10/2010 08:23:14] -->



templates.class.php



**********************************************/

class templateControl
{
    /*
     * Declare attributes
     * All in private
     */
     private $templateCode;
     
     /*
      * Construct method
      */
      public function __construct()
      {
        // Nothing to do here
      }
      
      public function getTemplateCode($templateName)
      {
        $templateNameQuery=DB::getInstance()->prepare("SELECT `template` FROM `templates` WHERE `templatename`=:templateName");
        $templateNameQuery->bindParam(':templateName',$templateName);
        $templateNameQuery->execute();
        
        //Check if the template exists - if not then throw error
        if($templateNameQuery->rowCount()<>1)
        {
            $systemControl=new systemConfiguration();
            $systemControl->logError('Could not load template \''.$templateName.'\'; the template name specified does not return any results.',2);
            $systemControl=null;
            throw new Exception('Could not load template \''.$templateName.'\'; the template name specified does not return any results.');
        }
        
        // Store and return value
        $template=$templateNameQuery->fetchColumn();
        $this->templateCode=$template;
        return $template;
      }
      
    public function getTemplateCodeHtmlEncoded($templateName)
    {
        $templateNameQuery=DB::getInstance()->prepare("SELECT `template` FROM `templates` WHERE `templatename`=:templateName");
        $templateNameQuery->bindParam(':templateName',$templateName);
        $templateNameQuery->execute();
        
        //Check if the template exists - if not then throw error
        if($templateNameQuery->rowCount()<>1)
        {
            $systemControl=new systemConfiguration();
            $systemControl->logError('Could not load template \''.$templateName.'\'; the template name specified does not return any results.',2);
            $systemControl=null;
            throw new Exception('Could not load template \''.$templateName.'\'; the template name specified does not return any results.');
        }
        
        // Store and return value
        $template=$templateNameQuery->fetchColumn();
        $template=htmlentities($template,ENT_NOQUOTES,'UTF-8');
        $this->templateCode=$template;
        return $template;
    }
}

?>