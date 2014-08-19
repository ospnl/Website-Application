<?php
/*
 * LAST REVISED
 * 04/12/11
 *
 */


class Page{
    private $pageName;
    private $pageLanguage;
    private $cacheable;
    private $userLanguage;
    private $template;
	private $isTemplateModern;
	private $templateLoadScript;
	private $jQueryPageFunctions;
    private $resources;
    private $code;
    private $title;
	private $pageMenuName;
	private $pageHyperlink;
	private $isPageExternal;
	private $doesLinkOpenInNewWindow;
    private $keywords;
    private $description;
    private $content;
    private $modernContent;
    private $menu;
    private $starttime;
    private $dontProcessGalleries;
	private $dontProcessForms;
    private $system;
    private $isWidget;
	private $languageBarStyle;

    public function __construct(){
        //Initialise attributes
        $this->cacheable=0;
        $this->pageName='';
        $this->pageLanguage='';
        $this->resources='';
        $this->code='';
        $this->menu='';
        $this->starttime=microtime();
        $this->userLanguage=$this->getUserLanguage();
        $this->dontProcessGalleries=0;
		$this->dontProcessForms=0;
        $this->system=0;
        $this->isWidget=false;
		$this->languageBarStyle='';

        // Set regional settings - this sets regional settings such as timezones and date formats in MySQL
        $regionalSettingsQuery=DB::getInstance()->prepare("CALL sp_loadRegionalSettings(:language)");
        $regionalSettingsQuery->bindParam(':language',userControl::user()->getUserLanguage());
        $regionalSettingsQuery->execute();
    }

    public function setPageName($newPageName){
        //Although leaving unencoded pagenames may work; it is strongly discouraged.
        $urlEncodedPageName=urlencode($newPageName);

        //check the pagename is not null
        if(empty($urlEncodedPageName)){
            throw new Exception('Pagename cannot be null.');
            return false;
        }

        //check the page is no longer than 100 characters
        if(strlen($urlEncodedPageName)>100){
            throw new Exception('Pagename exceeds character limit.');
            return false;
        }

        if(!$this->pageName=$urlEncodedPageName){
            throw new Exception('Could not set pagename attribute.');
            return false;
        }
    }

    public function doesPageExist($pageName){

        try{
            // Query to return the amount of pages with the specified pagename
            // In theory the query should only return 0 or 1 row
            $pageQuery=DB::getInstance()->prepare("SELECT `pagename` FROM `pages` WHERE `deleted`=0 AND `system`=0 AND`pagename`=:pagename");
            $pageQuery->bindParam(':pagename',$pageName,PDO::PARAM_STR);
            $num=$pageQuery->execute();
            $num=$pageQuery->rowCount();

            if($num==0){
                // No records found; the page does not exist
                return false;
            }
            elseif($num==1){
                // One record found; the page exists
                return true;
            }
            else{
                // More than one record found; something is wrong.
                throw new Exception('More than one record has been found for page '.$pageName);
            }
        }
        catch(Exception $e){
            // Create error message
            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
            $errorMessage.='User language: '.$this->userLanguage."\n";
            $errorMessage.='Class: page'."\n";
            $errorMessage.='Method: doesPageExist'."\n";
            $errorMessage.='Error: '.$e->getMessage()."\n";
            $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
            $errorMessage.='$pageName: '.$pageName."\n";

            // Log error message into database
            $system=new systemConfiguration();
            $system->logError($errorMessage,2);
			if(USE_NEWURL_STYLE){
				header('Location: '.WEBSITE_URL.$this->userLanguage.'/500');
			}
			else{
				header('Location: '.WEBSITE_URL.'500_'.$this->userLanguage.'.html');
			}

        }
    }
	
	public function doesSysPageExist($pageName){

        try{
            // Query to return the amount of pages with the specified pagename
            // In theory the query should only return 0 or 1 row
            $pageQuery=DB::getInstance()->prepare("SELECT `pagename` FROM `pages` WHERE `deleted`=0 AND `system`=1 AND`pagename`=:pagename");
            $pageQuery->bindParam(':pagename',$pageName,PDO::PARAM_STR);
            $num=$pageQuery->execute();
            $num=$pageQuery->rowCount();

            if($num==0){
                // No records found; the page does not exist
                return false;
            }
            elseif($num==1){
                // One record found; the page exists
                return true;
            }
            else{
                // More than one record found; something is wrong.
                throw new Exception('More than one record has been found for page '.$pageName);
            }
        }
        catch(Exception $e){
            // Create error message
            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
            $errorMessage.='User language: '.$this->userLanguage."\n";
            $errorMessage.='Class: page'."\n";
            $errorMessage.='Method: doesPageExist'."\n";
            $errorMessage.='Error: '.$e->getMessage()."\n";
            $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
            $errorMessage.='$pageName: '.$pageName."\n";

            // Log error message into database
            $system=new systemConfiguration();
            $system->logError($errorMessage,2);
            if(USE_NEWURL_STYLE){
				header('Location: '.WEBSITE_URL.$this->userLanguage.'/500');
			}
			else{
				header('Location: '.WEBSITE_URL.'500_'.$this->userLanguage.'.html');
			}

        }
    }

    public function errorPage($errorCode){
        try{
            // Look for the localised version of the error page
            if(file_exists(CACHE_DIRECTORY.'error/'.$errorCode.'_'.userControl::user()->getUserLanguage())&&filesize(CACHE_DIRECTORY.'error/'.$errorCode.'_'.userControl::user()->getUserLanguage())>0){
                $cache_file_handle = fopen(CACHE_DIRECTORY.'error/'.$errorCode.'_'.userControl::user()->getUserLanguage(), 'r');
                $errorPage=fread($cache_file_handle,filesize(CACHE_DIRECTORY.'error/'.$errorCode.'_'.userControl::user()->getUserLanguage()));
                fclose($cache_file_handle);
                print $errorPage;
            }
            // Localised version of the error page does not exist, try the error page in the fallback language
            elseif(file_exists(CACHE_DIRECTORY.'error/'.$errorCode.'_'.FALLBACK_LANGUAGE)&&filesize(CACHE_DIRECTORY.'error/'.$errorCode.'_'.FALLBACK_LANGUAGE)>0){
                $cache_file_handle = fopen(CACHE_DIRECTORY.'error/'.$errorCode.'_'.FALLBACK_LANGUAGE, 'r');
                $errorPage=fread($cache_file_handle,filesize(CACHE_DIRECTORY.'error/'.$errorCode.'_'.FALLBACK_LANGUAGE));
                fclose($cache_file_handle);
                print $errorPage;
            }
            // Neither the localised or the fallback language error page has been found, log as an error and display a generic Service Unavailable message
            else{
                print 'Service Unavailable';
                throw new Exception('Could not find error page for code '.$errorCode);
            }
        }
        catch(Exception $e){
            // Create error message
            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
            $errorMessage.='User language: '.$this->userLanguage."\n";
            $errorMessage.='Class: page'."\n";
            $errorMessage.='Method: errorPage'."\n";
            $errorMessage.='Error: '.$e->getMessage()."\n";
            $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
            $errorMessage.='$pageName: '.$pageName."\n";

            // Log error message into database
            $system=new systemConfiguration();
            $system->logError($errorMessage,3);
        }

    }

    public function setCacheable(){
        if($this->cacheable=1){}
        else
        {
                throw new Exception('Could not set cacheable attribute.');
        }
    }

    public function createPage($pageName){
            try
            {
                // This method will be running quite a few queries;
                // to avoid ending up with orphaned data we will use a transaction
                DB::getInstance()->beginTransaction();

                // Check the legality of the page name
                $pageNameLength=strlen($pageName);
                if($pageNameLength==0){
                    throw new Exception('Pagename cannot be empty.');
                }
                elseif($pageNameLength>100){
                    throw new Exception('Pagename cannot be longer than 100 characters.');
                }
                elseif(!ctype_alnum($pageName)){
                    throw new Exception('Pagename must contain letters and numbers only.');
                }

                // New strings are required by the new page
                // Create strings and store IDs in variables
                $pageStrings=new StringControl();
                $titleStringId=$pageStrings->createString();
                $contentStringId=$pageStrings->createString();
				$modernContentStringId=$pageStrings->createString();
                $descriptionStringId=$pageStrings->createString();
                $keywordsStringId=$pageStrings->createString();
                $nameStringId=$pageStrings->createString();
                $hyperlinkStringId=$pageStrings->createString();

                // Create new page in the database and assign string IDs
                $pageInsertQuery=DB::getInstance()->prepare("INSERT INTO `pages`(`pagename`,`title`,`name`,`content`,`moderncontent`,`description`,`keywords`,`hyperlink`,`cacheable`,`template`) VALUES(:pageName,:titleId,:nameId,:contentId,:modernContentId,:descriptionId,:keywordsId,:hyperlinkId,:cacheable,:templateName)");
                $pageInsertQuery->bindParam(':pageName',$pageName);
                $pageInsertQuery->bindParam(':titleId',$titleStringId);
                $pageInsertQuery->bindParam(':nameId',$nameStringId);
                $pageInsertQuery->bindParam(':contentId',$contentStringId);
                $pageInsertQuery->bindParam(':modernContentId',$modernContentStringId);
                $pageInsertQuery->bindParam(':descriptionId',$descriptionStringId);
                $pageInsertQuery->bindParam(':keywordsId',$keywordsStringId);
                $pageInsertQuery->bindParam(':hyperlinkId',$hyperlinkStringId);
                $pageInsertQuery->bindValue(':cacheable',1);
                $pageInsertQuery->bindValue(':templateName',DEFAULT_THEME);
                $pageInsertQuery->execute();

                // To be revised for caching improvements
                $pageNameInsertQuery=DB::getInstance()->prepare("UPDATE `localisationstrings` SET `default`=:pageName WHERE `stringid`=:pageNameStringId");
                $pageNameInsertQuery->bindParam(':pageName',$pageName);
                $pageNameInsertQuery->bindParam(':pageNameStringId',$nameStringId);
                $pageNameInsertQuery->execute();
				
				$pageStrings->buildStringCache();
				
                // We are done here commit the transaction
                DB::getInstance()->commit();
				
				$cacheControl=new Cache();
				$cacheControl->flush();
				$cacheControl=null;

            }
            catch(Exception $e)
            {
                // Oops something went wrong - rollback changes
                DB::getInstance()->rollBack();

                $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
                $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
                $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
                $errorMessage.='User language: '.$this->userLanguage."\n";
                $errorMessage.='Class: page'."\n";
                $errorMessage.='Method: createPage'."\n";
                $errorMessage.='Error: '.$e->getMessage()."\n";
                $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
                $errorMessage.='$pageName: '.$pageName."\n";

                $system=new systemConfiguration();
                $system->logError($errorMessage,2);
                $this->errorPage('500');
                exit;
            }
    }

    public function deletePage($pageName){
        try{
            // We'll do this in a transaction
            DB::getInstance()->beginTransaction();

            if(!$this->doesPageExist($pageName)){
                throw new Exception('Page does not exist.');
            }

            // As the pagename is the primary key; we cannot just flag the page as deleted.
            // We therefore append the date to the name.
            $deletedPageName=date("U").'-'.$pageName;

            // As a rule we do NOT delete data from the database
            // We simply mark the page as deleted.
            $pageDeleteQuery=DB::getInstance()->prepare("UPDATE `pages` SET `pagename`=:deletedPageName,deleted=1 WHERE `pagename`=:pageName AND `system`=0 AND `locked`=0");
            $pageDeleteQuery->bindParam(':deletedPageName',$deletedPageName);
            $pageDeleteQuery->bindParam(':pageName',$pageName);
            $pageDeleteQuery->execute();

            // We are done; commit the transaction
            DB::getInstance()->commit();
        }
        catch(Exception $e){
            //Rollback database changes
            DB::getInstance()->rollBack();

            //Build up as much information as possible for the error message
            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
            $errorMessage.='User language: '.$this->userLanguage."\n";
            $errorMessage.='Class: page'."\n";
            $errorMessage.='Method: deletePage'."\n";
            $errorMessage.='Error: '.$e->getMessage()."\n";
            $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
            $errorMessage.='$pageName: '.$pageName."\n";

            //Insert the error message into the database
            $system=new systemConfiguration();
            $system->logError($errorMessage,3);
            $system=null;

            //Redirect the user to server error page
            $this->errorPage('500');

            //Make sure the script stops executing code.
            exit;
        }


	}

    public function getUserLanguage(){
        if(isset($_REQUEST['l'])){
                $userlanguage=$_REQUEST['l'];
        }
        elseif($_COOKIE['lang_cookie']<>''){
                $userlanguage=$_COOKIE['lang_cookie'];
        }
        else{
                $userlanguage=substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                setcookie('lang_cookie',$language,time()+LANGUAGE_COOKIE_TIMEOUT);
        }

        return $userlanguage;
    }

    public function getLanguageBar(){

        try{
			if($this->isTemplateModern==1){
			
				// Language bar styles
				// Style 1: Vertical dropdown using jQuery
				// Style 2: Language names with localised links (ex: English Français Nederlands)
				// Style 3: Language names non localised links (ex: English French Dutch)
				// Style 4: Language names with localised links without current language
				// Style 5: Language names non localised links without current language
			
				if($this->languageBarStyle==1){
					$languageBar='<div id="languagedrop" class="languageBarStyle_'.$this->languageBarStyle.'><div class="dropbottom"><div class="dropmid"><ul>';
				}
				elseif($this->languageBarStyle==2||$this->languageBarStyle==3||$this->languageBarStyle==4||$this->languageBarStyle==5){
					$languageBar='<div id="languagebar" class="languageBarStyle_'.$this->languageBarStyle.'"><p>';
				}
				else{
					$languageBar='<div id="languagebar" class="languageBarStyle_'.$this->languageBarStyle.'">';
				}
				
				
				// Query the DB for available languages
				$languageQuery=DB::getInstance()->prepare("SELECT func_getStringById(`languagename`,`twocharacterabbr`,:showStringId) AS `languagename`,`twocharacterabbr` FROM `languages` WHERE `active`=1 AND `deleted`=0 ORDER BY `languagename`");
				$languageQuery->bindParam(':showStringId',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
				$languageQuery->execute();

				// Add entries for each language
				while($languages=$languageQuery->fetch()){
					if($this->languageBarStyle==1){
						$languageBar.='<li><a href="'.WEBSITE_URL. $languages['twocharacterabbr'].'">'.$languages['languagename'].'</a></li>';
						if($languages['twocharacterabbr']==$this->userLanguage){
							$languageBarHeader='<div id="language_selector"><a href="#" id="languageclick">'.$languages['languagename'].'</a>';
						}
					}
					elseif($this->languageBarStyle==2||$this->languageBarStyle==3||$this->languageBarStyle==4||$this->languageBarStyle==5){
						$languageBar.='<a href="'.WEBSITE_URL. $languages['twocharacterabbr'].'">'.$languages['languagename'].'</a>&nbsp;';
					}
					else{
						if(!$errorMessage){
							// Create error message
				            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
				            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
				            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
				            $errorMessage.='User language: '.$this->userLanguage."\n";
				            $errorMessage.='Class: page'."\n";
				            $errorMessage.='Method: getLanguageBar'."\n";
				            $errorMessage.='Error: There is no valid language bar style defined on the template. Current value is "'.$this->languageBarStyle.'"'."\n";
				            $errorMessage.='$pageName: '.$pageName."\n";

				            // Log error message into database
				            $system=new systemConfiguration();
				            $system->logError($errorMessage,2);
							$languageBar='<p>Error</p>';
						}
					}
				}
				
				if($this->languageBarStyle==1){
					$languageBar.='</ul></div></div></div></div>';
					$languageBar=$languageBarHeader . $languageBar;
				}
				elseif($this->languageBarStyle==2||$this->languageBarStyle==3||$this->languageBarStyle==4||$this->languageBarStyle==5){
					$languageBar.='</p></div>';
				}
				else{
					$languageBar.='</div>';
				}
				
				$this->jQueryPageFunctions.='$("#languageclick").click(function () {'."\n";
				$this->jQueryPageFunctions.='$("#languagedrop").slideDown(\'slow\');'."\n";
				$this->jQueryPageFunctions.='});'."\n";
				$this->jQueryPageFunctions.='$("#language_selector").mouseleave(function () {'."\n";
				$this->jQueryPageFunctions.='$("#languagedrop").slideUp(\'slow\');'."\n";
				$this->jQueryPageFunctions.='});'."\n";
			}
			else{
				// This method generates the ajaxified language selector
				// Purely XHTML will be output
				$languageBar='<div id="languages_panel" style="background-image:url('.WEBSITE_URL.'images/'.  strtolower($this->userLanguage).'.png);background-repeat:no-repeat;background-position:18px 5px;">';
				$languageBar.='<form id="page-changer" action="" method="post">';
				$languageBar.='<select name="nav">';

				// Query the DB for available languages
				$languageQuery=DB::getInstance()->prepare("SELECT func_getStringById(`languagename`,`twocharacterabbr`,:showStringId) AS `languagename`,`twocharacterabbr` FROM `languages` WHERE `active`=1 AND `deleted`=0 ORDER BY `languagename`");
				$languageQuery->bindParam(':showStringId',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
				$languageQuery->execute();

				// Add entries for each language
				while($languages=$languageQuery->fetch()){
					if(!USE_NEWURL_STYLE){
						$languageBar.='<option value="'.WEBSITE_URL.$this->pageName.'_'. $languages['twocharacterabbr'].'.html"';
					}
					else{
						$languageBar.='<option value="'.WEBSITE_URL. $languages['twocharacterabbr'].'/'.$this->pageName.'"';
					}
					// The language in use should be the default value
					if($languages['twocharacterabbr']==$this->userLanguage){
						$languageBar.=' selected="selected"';
					}
					$languageBar.='>'.$languages['languagename'].'</option>';
				}
				$languageBar.='</select>';
				$languageBar.='<input type="submit" value="Go" id="submit" style="display:none;"/></form></div>';
			
			}
            

            return $languageBar;
        }
        catch(Exception $e){

        }

    }

    public function loadTemplate($pageName='home'){
        // Note: the default template is defined by the template of the "home" page
        try{
			if($pageName=='admin/'){
				$pageName='505';
			}
			
            $defaultTemplateQuery=DB::getInstance()->prepare("SELECT `templates`.`template`,`templates`.`templatename`,`templates`.`html5`,`templates`.`loadscript`,`templates`.`languagebarstyle` FROM `templates` INNER JOIN `pages` ON `pages`.`template`=`templates`.`templatename` WHERE `templates`.`active`=1 AND `pages`.`pagename`=:pageName LIMIT 1");
            $defaultTemplateQuery->bindValue(':pageName',$pageName);
            $defaultTemplateQuery->execute();

            while($templateData=$defaultTemplateQuery->fetch())
            {
                $this->template=$templateData['template'];
                $templateName=$templateData['templatename'];
				$this->isTemplateModern=$templateData['html5'];
				$this->templateLoadScript=$templateData['loadscript'];
				$this->languageBarStyle=$templateData['languagebarstyle'];
            }

            if(strlen($this->template)<1){
                throw new Exception('Template data cannot be empty.');
            }

            // Load the template into the code attribute
            // we will build off this attribute
            $this->code=$this->template;

            // Query the database for page resources
            $scriptQuery=DB::getInstance()->prepare("SELECT func_getStringById(`page_resources`.`data`,:language,0) AS `data`,`page_resources`.`type`,`page_resources`.`behaviour`,`page_resources`.`link` FROM `page_resources` INNER JOIN `template_resources_attributions` ON `page_resources`.`id`=`template_resources_attributions`.`page_resource_id` WHERE `page_resources`.`deleted`=0 AND `template_resources_attributions`.`deleted`=0 AND `template_resources_attributions`.`template_name`=:templateName ORDER BY `template_resources_attributions`.`order`");
            $scriptQuery->bindParam(':templateName',$templateName);
            $scriptQuery->bindValue(':language',userControl::user()->getUserLanguage());
            $scriptQuery->execute();

            while($script=$scriptQuery->fetch()){
                if($script['type']=='css'){
                    switch($script['behaviour']){
                        case 'database':
                            $this->resources=$this->resources.'<style type="text/css">'."\n".$script['data']."\n".'</style>'."\n";
                    }
                }
                elseif($script['type']=='javascript'){
                    switch($script['behaviour']){
                        case 'database':
                            $this->resources=$this->resources.'<script type="text/javascript">'."\n".$script['data']."\n".'</script>'."\n";
                    }
                }
                else{
                    throw new Exception('Page resource for template '.$templateName.' does not have a supported type.');
                }
            }
			
			// If this is a HTML5 template we will create the javascript functions to be able to switch pages quickly
			if($this->isTemplateModern==1){

				$language=$this->pageLanguage;
				$templateLoadScript=$this->templateLoadScript;

				$showStringId=null;
				if(isset($_SESSION[WEBSITE_URL.'showstringid'])){
					$showStringId=$_SESSION[WEBSITE_URL.'showstringid'];
				}
			
				$this->jQueryPageFunctions='loadSections();';
				$this->jQueryPageFunctions.='function loadSections(){'."\n";
				
				
				$pageQuery=DB::getInstance()->prepare("SELECT func_getStringById(`title`,:language,:showstringid) AS `title`,func_getStringById(`keywords`,:language,:showstringid) AS `keywords`,func_getStringById(`moderncontent`,:language,:showstringid) AS `content`,func_getStringById(`description`,:language,:showstringid) AS `description`,`cacheable`,`system`,`pagename`,`loadscript` FROM `pages` WHERE `deleted`=0 AND `system`=0");
                $pageQuery->bindParam(':language',$language);
                $pageQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

                $pageQuery->execute();

                // Store page variables into attributes
                while($pageData=$pageQuery->fetch()){
				$this->jQueryPageFunctions.="\n\n".'/*********************'."\n".'LOADING SECTION:'."\n".$pageData['pagename']. "\n".'*********************/'."\n";
					$this->jQueryPageFunctions.='if(window.location.hash=="#'.$pageData['pagename'].'"';
					if($pageData['pagename']==$pageName) { $this->jQueryPageFunctions.='||window.location.hash==""' ; }
					$this->jQueryPageFunctions.='){'."\n";
					
					// Load the load scripts
					// First check there are no page override load scripts
					if($pageData['loadscript']<>NULL){
						$loadScript=$pageData['loadscript'];
					}
					else{
						$loadScript=$this->templateLoadScript;
					}

					$sanitisedContent=addslashes(preg_replace(array('/\r/', '/\n/'), '', ($pageData['content'])));
					$sanitisedContent=str_replace('<script>','<\script>',$sanitisedContent);
					$sanitisedContent=str_replace('</script>','<\/script>',$sanitisedContent);
					$sanitisedContent=str_replace('<link>','<\link>',$sanitisedContent);
					$sanitisedContent=str_replace('</link>','<\/link>',$sanitisedContent);
					$sanitisedContent=str_replace('<style>','<\style>',$sanitisedContent);
					$sanitisedContent=str_replace('</style>','<\/style>',$sanitisedContent);
					
					$loadScript=str_replace('{{LOAD_CONTENT}}','$("#content").html("'.$sanitisedContent.'");',$loadScript);
					$loadScript=str_replace('{{LOAD_TITLE}}','document.title =\''.addslashes(($pageData['title'])).'\';',$loadScript);
					// $loadScript=str_replace('{{ADDTHIS_DESCRIPTION_CHANGER}}','addthis.update(\'share\', \'url\''.addslashes(($pageData['description'])).'\');'."\n",$loadScript);
					$loadScript=str_replace('{{ADDTHIS_URL_CHANGER}}','addthis.update(\'share\', \'url\',\''.WEBSITE_URL.$language.'/'.$pageData['pagename'].'\');'."\n".'addthis.url = "'.WEBSITE_URL.$language.'/'.$pageData['pagename'].'";'."\n".'addthis.toolbox(".addthis_toolbox");',$loadScript);
					
					$this->jQueryPageFunctions.=$loadScript;
					$this->jQueryPageFunctions.='}'."\n";
                }
				
				$this->jQueryPageFunctions.='}'."\n";
			}
        }
        catch(Exception $e){
            // Create error message
            $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
            $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
            $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
            $errorMessage.='User language: '.$this->userLanguage."\n";
            $errorMessage.='Class: page'."\n";
            $errorMessage.='Method: loadTemplate'."\n";
            $errorMessage.='Error: '.$e->getMessage()."\n";
            $errorMessage.='Trace: '.$e->getTraceAsString()."\n";
            $errorMessage.='$pageName: '.$pageName."\n";

            // Log error message into database
            $system=new systemConfiguration();
            $system->logError($errorMessage,2);
            if(USE_NEWURL_STYLE){
				header('Location: '.WEBSITE_URL.$this->userLanguage.'/500');
			}
			else{
				header('Location: '.WEBSITE_URL.'500_'.$this->userLanguage.'.html');
			}
            exit();
        }

    }

    public function loadPage($pageName,$language,$disableCache=FALSE){
        try{

            // This method retrieves all page information ready for page building
            $this->pageName=$pageName;
            $this->pageLanguage=$language;

			$showStringId=null;
			if(isset($_SESSION[WEBSITE_URL.'showstringid'])){
				$showStringId=$_SESSION[WEBSITE_URL.'showstringid'];
			}
			
            // Do not waste time querying the database if the page will be loaded from cache
            if(!file_exists(CACHE_DIRECTORY.$pageName.'_'.$language)||DISABLE_CACHING==TRUE||$showStringId==TRUE||isset($_POST['__FORMSTATE'])||$disableCache==TRUE)
            {
                $pageQuery=DB::getInstance()->prepare("SELECT func_getStringById(`title`,:language,:showstringid) AS `title`,func_getStringById(`keywords`,:language,:showstringid) AS `keywords`,func_getStringById(`name`,:language,:showstringid) AS `menuname`,func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink`,func_getStringById(`content`,:language,:showstringid) AS `content`,func_getStringById(`moderncontent`,:language,:showstringid) AS `moderncontent`,func_getStringById(`description`,:language,:showstringid) AS `description`,`cacheable`,`system`,`external`,`newwindow` FROM `pages` WHERE `pagename`=:pageName AND `deleted`=0 LIMIT 1");
                $pageQuery->bindParam(':pageName',$pageName);
                $pageQuery->bindParam(':language',$language);
                $pageQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

                $pageQuery->execute();

                // Store page variables into attributes
                while($pageData=$pageQuery->fetch()){
                    $this->title=$pageData['title'];
                    $this->description=$pageData['description'];
                    $this->content=$pageData['content'];
					$this->modernContent=$pageData['moderncontent'];
					$this->pageMenuName=$pageData['menuname'];
					$this->pageHyperlink=$pageData['hyperlink'];
					$this->isPageExternal=$pageData['external'];
					$this->doesLinkOpenInNewWindow=$pageData['newwindow'];
                    $this->keywords=$pageData['keywords'];
                    $this->cacheable=$pageData['cacheable'];
                    $this->system=$pageData['system'];
                }

                // Load the page template
                $this->loadTemplate($pageName);

                // Create XHTML code for menu in the form of an unordered list
                $this->menu='<ul id="nav">';
                $menuQuery=DB::getInstance()->prepare("SELECT func_getStringById(`text`,:language,:showstringid) AS `text`,`page`,`menu`.`id` AS `parentid`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menu` INNER JOIN `pages` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 ORDER BY `menu`.`order` ASC");
                $menuQuery->bindParam(':language',$this->pageLanguage);
                $menuQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

                $menuQuery->execute();

                while($menuData=$menuQuery->fetch())
                {
                    if($menuData['page']==$this->pageName){
						if($this->isTemplateModern==0){
							$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
						}
						else{
							$this->menu.='<li id="page_'.$menuData['page'].'" class="active parent"><a class="parentlink" href="#'.$menuData['page'].'"';
						}
                        
                        if($menuData['newwindow']==1){
                            $this->menu.=' onclick="window.open(this.href); return false;"';
                        }
                        $this->menu.='>'.$menuData['text'].'</a>';
						
						// Get menu children
						// Only compatible with modern UIs
						if($this->isTemplateModern){
							
							$menuChildrenQuery=DB::getInstance()->prepare("SELECT func_getStringById(`pages`.`name`,:language,:showstringid) AS `text`,`page`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menuchildren` INNER JOIN `pages` ON `menuchildren`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 AND `menuchildren`.`parentid`=:parentId ORDER BY `menuchildren`.`order` ASC");
							$menuChildrenQuery->bindParam(':language',$this->pageLanguage);
							$menuChildrenQuery->bindParam(':parentId',$menuData['parentid']);
							$menuChildrenQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

							$menuChildrenQuery->execute();
							
							$num=$menuChildrenQuery->rowCount();
							
							if($num>0){
								$this->menu.='<ul class="children">';

								while($menuChildrenData=$menuChildrenQuery->fetch())
								{
									if($menuChildrenData['external']==1&&$menuChildrenData['newwindow']==1){
										$this->menu.='<li class="child"><a class="childlink" href="'.$menuChildrenData['hyperlink'].'" target="_BLANK" />'.$menuChildrenData['text'].'</li>';
									}
									elseif($menuChildrenData['external']==1&&$menuChildrenData['newwindow']==0){
										$this->menu.='<li class="child"><a class="childlink" href="'.$menuChildrenData['hyperlink'].'" />'.$menuChildrenData['text'].'</li>';
									}
									else{
										$this->menu.='<li class="child"><a class="childlink" href="#'.$menuChildrenData['page'].'" />'.$menuChildrenData['text'].'</li>';
									}
								}
								$this->menu.='</ul>';
							}
							
							$this->menu.='</li>';
						}
					}
                    elseif($menuData['external']==1){
                        $this->menu.='<li><a class="parentlink" href="'.$menuData['hyperlink'].'"';
                        if($menuData['newwindow']==1){
                            $this->menu.=' onclick="window.open(this.href); return false;"';
							}
                        $this->menu.='>'.$menuData['text'].'</a></li>';
                    }
                    else{
                        if($this->isTemplateModern==0){
							$this->menu.='<li><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
						}
						else{
							$this->menu.='<li id="page_'.$menuData['page'].'" class="parent"><a class="parentlink" href="#'.$menuData['page'].'"';
						}
                        if($menuData['newwindow']==1){
                            $this->menu.=' onclick="window.open(this.href); return false;"';
                        }
                        $this->menu.='>'.$menuData['text'].'</a>';
						
						// Get menu children
						// Only compatible with modern UIs
						if($this->isTemplateModern){
						
							
							
							$menuChildrenQuery=DB::getInstance()->prepare("SELECT func_getStringById(`pages`.`name`,:language,:showstringid) AS `text`,`page`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menuchildren` INNER JOIN `pages` ON `menuchildren`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 AND `menuchildren`.`parentid`=:parentId ORDER BY `menuchildren`.`order` ASC");
							$menuChildrenQuery->bindParam(':language',$this->pageLanguage);
							$menuChildrenQuery->bindParam(':parentId',$menuData['parentid']);
							$menuChildrenQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

							$menuChildrenQuery->execute();
							
							$num=$menuChildrenQuery->rowCount();
							
							if($num>0){
								$this->menu.='<ul class="children">';

								while($menuChildrenData=$menuChildrenQuery->fetch())
								{
									if($menuChildrenData['external']==1&&$menuChildrenData['newwindow']==1){
										$this->menu.='<li class="child"><a class="childlink" href="'.$menuChildrenData['hyperlink'].'" target="_BLANK" />'.$menuChildrenData['text'].'</li>';
									}
									elseif($menuChildrenData['external']==1&&$menuChildrenData['newwindow']==0){
										$this->menu.='<li class="child"><a class="childlink" href="'.$menuChildrenData['hyperlink'].'" />'.$menuChildrenData['text'].'</li>';
									}
									else{
										$this->menu.='<li class="child"><a class="childlink" href="#'.$menuChildrenData['page'].'" />'.$menuChildrenData['text'].'</li>';
									}
								}
								$this->menu.='</ul>';
							}
							
							$this->menu.='</li>';
						}
                    }
                }
                $this->menu=$this->menu.'</ul>';
            }
        }
        catch(Exception $e){

        }

    }

    public function processResources(){
        try{
            $resourcesQuery=DB::getInstance()->prepare("SELECT `variable`,func_getStringByName(`stringname`,:language,:showstringid) AS `string`,`prefix`,`suffix`,`showvariabledefault` FROM `resources` WHERE `deleted`=0");
            $resourcesQuery->bindParam(':language',$this->pageLanguage);
            $resourcesQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
            $resourcesQuery->execute();

            while($resourceData=$resourcesQuery->fetch()){
                if($resourceData['showvariabledefault']==0){
                    $whiteListQuery=DB::getInstance()->prepare("SELECT `id` FROM `resourcepagewhitelist` WHERE `pagename`=:pageName AND `variable`=:variableName");
                    $whiteListQuery->bindParam(':pageName',$this->pageName);
                    $whiteListQuery->bindValue(':variableName',$resourceData['variable']);
                    $whiteListQuery->execute();
                    $isInWhiteList=$whiteListQuery->rowCount();
                    if($isInWhiteList==0){
                        $this->code=str_replace($resourceData['variable'],'', $this->code);
                    }
                    else{
                        $this->code=str_replace($resourceData['variable'], $resourceData['prefix'].$resourceData['string'].$resourceData['suffix'], $this->code);
                    }
                    
                }
                else{
                    $blackListQuery=DB::getInstance()->prepare("SELECT `id` FROM `resourcepageblacklist` WHERE `pagename`=:pageName AND `variable`=:variableName");
                    $blackListQuery->bindParam(':pageName',$this->pageName);
                    $blackListQuery->bindValue(':variableName',$resourceData['variable']);
                    $blackListQuery->execute();
                    $isInBlackList=$blackListQuery->rowCount();
                    if($isInBlackList>0){
                        $this->code=str_replace($resourceData['variable'], '', $this->code);
                    }
                    else{
                        $this->code=str_replace($resourceData['variable'],$resourceData['prefix'].$resourceData['string'].$resourceData['suffix'], $this->code);
                    }
                }
            }
        }
        catch(Exception $e){

        }

    }

    public function processGalleries(){
        try{
            $galleryQuery=DB::getInstance()->prepare("SELECT `replacecode`,`replacecode2`,func_getStringById(`code`,:language,0) AS `code`,`type` FROM `galleries`");
            $galleryQuery->bindParam(':language',$this->pageLanguage);
            $galleryQuery->execute();

            while($galleryData=$galleryQuery->fetch()){
                // Figure out if any of the galleries codes are contained in the code
                if(substr_count($this->code,'<p>'.$galleryData['replacecode'].'</p>')<>0||substr_count($this->code,'<p>'.$galleryData['replacecode2'].'</p>')<>0||substr_count($this->code,$galleryData['replacecode'])<>0||substr_count($this->code,$galleryData['replacecode2'])){
                    // If they are then replace them with the XHTML code
                    $this->code=str_replace('<p>'.$galleryData['replacecode'].'</p>', $galleryData['code'], $this->code);
                    $this->code=str_replace('<p>'.$galleryData['replacecode2'].'</p>', $galleryData['code'], $this->code);
                    $this->code=str_replace($galleryData['replacecode'], $galleryData['code'], $this->code);
                    $this->code=str_replace($galleryData['replacecode2'], $galleryData['code'], $this->code);

                    // Load the required resources
                    $pageResourceQuery=DB::getInstance()->prepare("SELECT func_getStringById(`page_resources`.`data`,:language,0) AS `data`,`page_resources`.`type` FROM `page_resources` INNER JOIN `gallerytype_resources_attributions` ON `gallerytype_resources_attributions`.`page_resource_id`=`page_resources`.`id` WHERE `gallerytype_resources_attributions`.`gallerytype_id`=:galleryTypeId ORDER BY `gallerytype_resources_attributions`.`order`");
                    $pageResourceQuery->bindParam(':galleryTypeId',$galleryData['type']);
                    $pageResourceQuery->bindValue(':language',$this->userLanguage);
                    $pageResourceQuery->execute();
                    while($pageResourceData=$pageResourceQuery->fetch()){
                        if($pageResourceData['type']=='css')
                        {
                            $this->addCustomCss($pageResourceData['data']);
                        }
                        elseif($pageResourceData['type']=='javascript'){
                            $this->addCustomJavaScript($pageResourceData['data']);
                        }
                        else{
                            throw new Exception('Gallery type '.$galleryData['type'].' has a resource in an unrecognisable format.');
                        }

                    }
                }

            }
        }
        catch(Exception $e){

        }

    }

    public function addCustomJavaScript($script){
        try{
            if(substr_count($this->resources,$script)==0){
                $this->resources=$this->resources.'<script type="text/javascript">'.$script.'</script>'."\n";
            }
        }
        catch(Exception $e){

        }

    }

    public function addCustomJavaScriptLink($link){
        try{
            if(substr_count($this->resources,'<script type="text/javascript" src="'.$link.'"></script>')==0){
                $this->resources=$this->resources.'<script type="text/javascript" src="'.$link.'"></script>'."\n";
            }
        }
        catch(Exception $e){

        }

    }

    public function addCustomCss($script){
        try{
            $this->resources=$this->resources.'<style type="text/css">'.$script.'</style>'."\n";
        }
        catch(Exception $e){

        }

    }

    public function addCustomCssLink($link){
        try{
            $this->resources=$this->resources.'<link rel="stylesheet" type="text/css" href="'.$link.'" media="screen" />'."\n";
        }
        catch(Exception $e){

        }
    }

    public function setPageLanguage($language){
        try{
            $this->pageLanguage=$language;
        }
        catch(Exception $e){

        }
    }

    public function setPageTitle($title){
        $this->title=$title;
    }

    public function setPageKeywords($keywords){
        $this->keywords=$keywords;
    }

    public function setPageDescription($description){
        $this->description=$description;
    }

    public function addPageContent($content){
        $this->content=$this->content."\n".$content;
    }

    public function noGalleries(){
        $this->dontProcessGalleries=1;
    }

	public function noForms(){
        $this->dontProcessForms=1;
    }

    public function updatePageTitle($pageName,$language,$newTitle){
        try{
            DB::getInstance()->beginTransaction();

            // First we update the string override
            $updatePageTitleQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
                                                            INNER JOIN `pages` ON `pages`.`title`=`localisationoverrides`.`stringid`
                                                            SET `localisationoverrides`.`".$language."`=:newTitle
                                                            WHERE `pages`.`pagename`=:pageName");
            $updatePageTitleQuery->bindParam(':pageName',$pageName);
            $updatePageTitleQuery->bindParam(':newTitle',$newTitle);
            $updatePageTitleQuery->execute();
            $updatePageTitleQuery=null;

            // Then we update the cache - this avoids having to go through the lengthy cache purging
            $updatePageTitleCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
                                                                INNER JOIN `pages` ON `pages`.`title`=`stringcache`.`stringid`
                                                                SET `stringcache`.`override`=:newTitle,
                                                                `stringcache`.`string`=:newTitle
                                                                WHERE `pages`.`pagename`=:pageName
                                                                AND `stringcache`.`language`=:language");
            $updatePageTitleCacheQuery->bindParam(':pageName',$pageName);
            $updatePageTitleCacheQuery->bindParam(':language',$language);
            $updatePageTitleCacheQuery->bindParam(':newTitle',$newTitle);
            $updatePageTitleCacheQuery->execute();
            $updatePageTitleCacheQuery=null;

            // We're done - commit the transaction
            DB::getInstance()->commit();
        }
        catch(Exception $e){
            DB::getInstance()->rollBack();
        }

    }

    public function updatePageDescription($pageName,$language,$newDescription){
        try{
        DB::getInstance()->beginTransaction();

        // First we update the string override
        $updatePageDescriptionQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
                                                        INNER JOIN `pages` ON `pages`.`description`=`localisationoverrides`.`stringid`
                                                        SET `localisationoverrides`.`".$language."`=:newDescription
                                                        WHERE `pages`.`pagename`=:pageName");
        $updatePageDescriptionQuery->bindParam(':pageName',$pageName);
        $updatePageDescriptionQuery->bindParam(':newDescription',$newDescription);
        $updatePageDescriptionQuery->execute();
        $updatePageDescriptionQuery=null;

        // Then we update the cache - this avoids having to go through the lengthy cache purging
        $updatePageDescriptionCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
                                                            INNER JOIN `pages` ON `pages`.`description`=`stringcache`.`stringid`
                                                            SET `stringcache`.`override`=:newDescription,
                                                            `stringcache`.`string`=:newDescription
                                                            WHERE `pages`.`pagename`=:pageName
                                                            AND `stringcache`.`language`=:language");
        $updatePageDescriptionCacheQuery->bindParam(':pageName',$pageName);
        $updatePageDescriptionCacheQuery->bindParam(':language',$language);
        $updatePageDescriptionCacheQuery->bindParam(':newDescription',$newDescription);
        $updatePageDescriptionCacheQuery->execute();
        $updatePageDescriptionCacheQuery=null;

        // We're done - commit the transaction
        DB::getInstance()->commit();
        }
        catch(Exception $e){
            DB::getInstance()->rollBack();
        }
    }

    public function updatePageKeywords($pageName,$language,$newKeywords){
        try{
            DB::getInstance()->beginTransaction();

            // First we update the string override
            $updatePageKeywordsQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
                                                            INNER JOIN `pages` ON `pages`.`keywords`=`localisationoverrides`.`stringid`
                                                            SET `localisationoverrides`.`".$language."`=:newKeywords
                                                            WHERE `pages`.`pagename`=:pageName");
            $updatePageKeywordsQuery->bindParam(':pageName',$pageName);
            $updatePageKeywordsQuery->bindParam(':newKeywords',$newKeywords);
            $updatePageKeywordsQuery->execute();
            $updatePageKeywordsQuery=null;

            // Then we update the cache - this avoids having to go through the lengthy cache purging
            $updatePageKeywordsCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
                                                                INNER JOIN `pages` ON `pages`.`keywords`=`stringcache`.`stringid`
                                                                SET `stringcache`.`override`=:newKeywords,
                                                                `stringcache`.`string`=:newKeywords
                                                                WHERE `pages`.`pagename`=:pageName
                                                                AND `stringcache`.`language`=:language");
            $updatePageKeywordsCacheQuery->bindParam(':pageName',$pageName);
            $updatePageKeywordsCacheQuery->bindParam(':language',$language);
            $updatePageKeywordsCacheQuery->bindParam(':newKeywords',$newKeywords);
            $updatePageKeywordsCacheQuery->execute();
            $updatePageKeywordsCacheQuery=null;

            // We're done - commit the transaction
            DB::getInstance()->commit();
            }
            catch(Exception $e){
                DB::getInstance()->rollBack();
            }
    }
	
	public function updatePageMenuName($pageName,$language,$newMenuName){
			try{
				DB::getInstance()->beginTransaction();

				// First we update the string override
				$updatePageMenuNameQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
																INNER JOIN `pages` ON `pages`.`name`=`localisationoverrides`.`stringid`
																SET `localisationoverrides`.`".$language."`=:newMenuName
																WHERE `pages`.`pagename`=:pageName");
				$updatePageMenuNameQuery->bindParam(':pageName',$pageName);
				$updatePageMenuNameQuery->bindParam(':newMenuName',$newMenuName);
				$updatePageMenuNameQuery->execute();
				$updatePageMenuNameQuery=null;

				// Then we update the cache - this avoids having to go through the lengthy cache purging
				$updatePageMenuNameCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
																	INNER JOIN `pages` ON `pages`.`name`=`stringcache`.`stringid`
																	SET `stringcache`.`override`=:newMenuName,
																	`stringcache`.`string`=:newMenuName
																	WHERE `pages`.`pagename`=:pageName
																	AND `stringcache`.`language`=:language");
				$updatePageMenuNameCacheQuery->bindParam(':pageName',$pageName);
				$updatePageMenuNameCacheQuery->bindParam(':language',$language);
				$updatePageMenuNameCacheQuery->bindParam(':newMenuName',$newMenuName);
				$updatePageMenuNameCacheQuery->execute();
				$updatePageMenuNameCacheQuery=null;

				// We're done - commit the transaction
				DB::getInstance()->commit();
				}
				catch(Exception $e){
					DB::getInstance()->rollBack();
				}
		}
		
		public function updatePageHyperlink($pageName,$language,$newHyperlink){
			try{
				DB::getInstance()->beginTransaction();

				// First we update the string override
				$updatePageHyperlinkQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
																INNER JOIN `pages` ON `pages`.`hyperlink`=`localisationoverrides`.`stringid`
																SET `localisationoverrides`.`".$language."`=:newHyperlink
																WHERE `pages`.`pagename`=:pageName");
				$updatePageHyperlinkQuery->bindParam(':pageName',$pageName);
				$updatePageHyperlinkQuery->bindParam(':newHyperlink',$newHyperlink);
				$updatePageHyperlinkQuery->execute();
				$updatePageHyperlinkQuery=null;

				// Then we update the cache - this avoids having to go through the lengthy cache purging
				$updatePageHyperlinkCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
																	INNER JOIN `pages` ON `pages`.`hyperlink`=`stringcache`.`stringid`
																	SET `stringcache`.`override`=:newHyperlink,
																	`stringcache`.`string`=:newHyperlink
																	WHERE `pages`.`pagename`=:pageName
																	AND `stringcache`.`language`=:language");
				$updatePageHyperlinkCacheQuery->bindParam(':pageName',$pageName);
				$updatePageHyperlinkCacheQuery->bindParam(':language',$language);
				$updatePageHyperlinkCacheQuery->bindParam(':newHyperlink',$newHyperlink);
				$updatePageHyperlinkCacheQuery->execute();
				$updatePageHyperlinkCacheQuery=null;

				// We're done - commit the transaction
				DB::getInstance()->commit();
				}
				catch(Exception $e){
					DB::getInstance()->rollBack();
				}
		}
		
		public function updatePageExternal($pageName,$newValue){
			try{
				DB::getInstance()->beginTransaction();

				$updatePageExternalQuery=DB::getInstance()->prepare("UPDATE `pages` SET `external`=:newValue
																WHERE `pagename`=:pageName");
				$updatePageExternalQuery->bindParam(':pageName',$pageName);
				$updatePageExternalQuery->bindParam(':newValue',$newValue);
				$updatePageExternalQuery->execute();
				$updatePageExternalQuery=null;

				// We're done - commit the transaction
				DB::getInstance()->commit();
				}
				catch(Exception $e){
					DB::getInstance()->rollBack();
				}
		}
		
		public function updatePageNewWindow($pageName,$newValue){
			try{
				DB::getInstance()->beginTransaction();

				$updatePageNewWindowQuery=DB::getInstance()->prepare("UPDATE `pages` SET `newwindow`=:newValue
																WHERE `pagename`=:pageName");
				$updatePageNewWindowQuery->bindParam(':pageName',$pageName);
				$updatePageNewWindowQuery->bindParam(':newValue',$newValue);
				$updatePageNewWindowQuery->execute();
				$updatePageNewWindowQuery=null;

				// We're done - commit the transaction
				DB::getInstance()->commit();
				}
				catch(Exception $e){
					DB::getInstance()->rollBack();
				}
		}

    public function updatePageContent($pageName,$language,$newContent){
        try{
            DB::getInstance()->beginTransaction();

            // First we update the string override
			if($this->isTemplateModern){
				$updatePageContentQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
                                                            INNER JOIN `pages` ON `pages`.`moderncontent`=`localisationoverrides`.`stringid`
                                                            SET `localisationoverrides`.`".$language."`=:newContent
                                                            WHERE `pages`.`pagename`=:pageName");
			}
			else{
				$updatePageContentQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides`
                                                            INNER JOIN `pages` ON `pages`.`content`=`localisationoverrides`.`stringid`
                                                            SET `localisationoverrides`.`".$language."`=:newContent
                                                            WHERE `pages`.`pagename`=:pageName");
			}
            
            $updatePageContentQuery->bindParam(':pageName',$pageName);
            $updatePageContentQuery->bindParam(':newContent',$newContent);
            $updatePageContentQuery->execute();
            $updatePageContentQuery=null;

            // Then we update the cache - this avoids having to go through the lengthy cache purging
			if($this->isTemplateModern){
				$updatePageContentCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
                                                                INNER JOIN `pages` ON `pages`.`moderncontent`=`stringcache`.`stringid`
                                                                SET `stringcache`.`override`=:newContent,
                                                                `stringcache`.`string`=:newContent
                                                                WHERE `pages`.`pagename`=:pageName
                                                                AND `stringcache`.`language`=:language");
			}
			else{
				$updatePageContentCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache`
                                                                INNER JOIN `pages` ON `pages`.`content`=`stringcache`.`stringid`
                                                                SET `stringcache`.`override`=:newContent,
                                                                `stringcache`.`string`=:newContent
                                                                WHERE `pages`.`pagename`=:pageName
                                                                AND `stringcache`.`language`=:language");
			}
            
            $updatePageContentCacheQuery->bindParam(':pageName',$pageName);
            $updatePageContentCacheQuery->bindParam(':language',$language);
            $updatePageContentCacheQuery->bindParam(':newContent',$newContent);
            $updatePageContentCacheQuery->execute();
            $updatePageContentCacheQuery=null;

            // We're done - commit the transaction
            DB::getInstance()->commit();
            }
            catch(Exception $e){
                DB::getInstance()->rollBack();
            }
    }

    public function getPageContent(){
		if($this->isTemplateModern){
			return $this->modernContent;
		}
		else{
			return $this->content;
		}
    }

    public function getPageTitle(){
        return $this->title;
    }

    public function getPageKeywords(){
        return $this->keywords;
    }

	public function getPageMenuName(){
        return $this->pageMenuName;
    }
	
	public function getHyperlink(){
        return $this->pageHyperlink;
    }
	
	public function getIsExternal(){
        return $this->isPageExternal;
    }
	
	public function getDoesLinkOpenInNewWindow(){
        return $this->doesLinkOpenInNewWindow;
    }
	
    public function getPageDescription(){
        return $this->description;
    }

    public function isSystemPage(){
        return $this->system;
    }
    
    public function loadWidget($widgetId,$language){
		
		$this->isWidget=true;
		$this->pageLanguage=$language;
		
		$widgetQuery=DB::getInstance()->prepare('SELECT `widgets`.`content`,`embedcodetemplates`.`template` FROM `widgets` INNER JOIN `embedcodetemplates` ON `widgets`.`template`=`embedcodetemplates`.`id` WHERE `widgets`.`id`=:widgetId AND `widgets`.`deleted`=0');
		$widgetQuery->bindValue(':widgetId',$widgetId);
		$widgetQuery->execute();
		
		while($widget=$widgetQuery->fetch()){
			$this->code=str_replace('<%%$$CONTENT$$%%>',$widget['content'],$widget['template']);
		}
		
	}


    public function display(){
			
			error_log("Entering the display function at " .date("r"));
			
            // We only load from cache if the following conditions are met:
            // 1. Caching is not disabled
            // 2. Resource tracker is turned off
            // 3. Page is not a form response
            // 4. Page exists in cache
			
			error_log("Checking showstringid at " .date("r"));
			$showStringId=null;
			if(isset($_SESSION[WEBSITE_URL.'showstringid'])){
				$showStringId=$_SESSION[WEBSITE_URL.'showstringid'];
			}
			
			error_log("Checking cache at " .date("r"));
            if(file_exists(CACHE_DIRECTORY.$this->pageName.'_'.$this->pageLanguage)&&DISABLE_CACHING==FALSE&&$showStringId<>true&&!isset($_POST['__FORMSTATE'])&&!$this->isWidget)
            {
                $cache_file_handle = fopen(CACHE_DIRECTORY.$this->pageName.'_'.$this->pageLanguage, 'r');
				$this->code=fread($cache_file_handle,filesize(CACHE_DIRECTORY.$this->pageName.'_'.$this->pageLanguage));
				fclose($cache_file_handle);
                $generationTime=round(microtime() - $this->starttime,4);
                $this->code=str_replace('<%%$$STATISTICS$$%%>','Page generated in '.$generationTime .'s (from cache)',$this->code);

            }
            else
            {
				error_log("No cache found at " .date("r"));
				error_log("Replacing content variable at " .date("r"));
                $this->code=str_replace('<%%$$CONTENT$$%%>',$this->content,$this->code);
                
				error_log("About to process resources at " .date("r"));
                $this->processResources();
				error_log("Processed resources at " .date("r"));
				if($this->dontProcessGalleries==0)
                {
					error_log("About to process galleries at " .date("r"));
                    $this->processGalleries();
					error_log("Processed galleries at " .date("r"));
                }
				
				error_log("About to process variables at " .date("r"));
				
				error_log("JS at " .date("r"));
                $this->code=str_replace('<%%$$JAVASCRIPT$$%%>',$this->resources,$this->code);
				error_log("CSS at " .date("r"));
                $this->code=str_replace('<%%$$CSS$$%%>','',$this->code);
				error_log("SITEURL at " .date("r"));
                $this->code=str_replace('<%%$$SITEURL$$%%>',WEBSITE_URL,$this->code);
				error_log("TITLE at " .date("r"));
                $this->code=str_replace('<%%$$TITLE$$%%>',htmlentities($this->title,ENT_QUOTES,'UTF-8'),$this->code);
				error_log("KEYWORDS at " .date("r"));
                $this->code=str_replace('<%%$$KEYWORDS$$%%>',htmlentities($this->keywords,ENT_QUOTES,'UTF-8'),$this->code);
				error_log("DESC at " .date("r"));
                $this->code=str_replace('<%%$$DESCRIPTION$$%%>',htmlentities($this->description,ENT_QUOTES,'UTF-8'),$this->code);
				error_log("LB1 at " .date("r"));
                $this->code=str_replace('<%%$$LANGUAGEBAR$$%%>',$this->getLanguageBar(),$this->code);
				error_log("LB2 at " .date("r"));
				$this->code=str_replace('{{LANGUAGE_SELECTOR}}',$this->getLanguageBar(),$this->code);
				error_log("YEAR at " .date("r"));
				$this->code=str_replace('{{CURRENT_YEAR}}',date("Y"),$this->code);
				error_log("LANG at " .date("r"));
                $this->code=str_replace('<%%$$LANGUAGE$$%%>',$this->pageLanguage,$this->code);
				
				$this->code=str_replace('{{JQUERY_PAGEFUNCTIONS}}',$this->jQueryPageFunctions,$this->code);

                $this->code=str_replace('<%%$$MENU$$%%>',$this->menu,$this->code);
				
				error_log("Finished processing variables at " .date("r"));

				error_log("About to process forms at " .date("r"));
                $form=new forms();
                if(isset($_POST['__FORMSTATE']))
                {
                    if($form->validateFormById($_POST['__FORMSTATE'],userControl::user()->getUserLanguage()))
                    {
                        try
                        {
                            $form->complete();
                        }
                        catch(Exception $e)
                        {

                        }

                    }
                }
				error_log("Done processing forms at " .date("r"));

				error_log("Stripping out forms at " .date("r"));
                // Find forms in page and replace with form code.
				if($this->dontProcessForms==0){
					$formsArray=null;
					preg_match("[::FORM::[0-9a-f]{32}]", $this->code,$formsArray);
					foreach($formsArray as $key => $value){
						$formId=str_replace('::FORM::','',$value);
						$this->code=str_replace($value,$form->getFormById($formId,userControl::user()->getUserLanguage()),$this->code);
					}
				}
				error_log("Done stripping out forms at " .date("r"));


                if(DISABLE_CACHING==false&&$this->cacheable==1&&$_SESSION[WEBSITE_URL.'showstringid']<>true&&!isset($_POST['__FORMSTATE']))
                {
                    $cached_filename=CACHE_DIRECTORY.$this->pageName.'_'.$this->pageLanguage;
                    $cache_file_handle = fopen($cached_filename, 'w');
                    $cached_page = fwrite($cache_file_handle ,$this->code);
                    fclose($cache_file_handle);
                }
                $generationTime=round(microtime() - $this->starttime,4);
                $this->code=str_replace('<%%$$STATISTICS$$%%>','Page generated in '.$generationTime .'s (from database)',$this->code);
            }
			
			error_log("About to print code at " .date("r"));
			error_log("Memory usage: ".memory_get_usage()." at " .date("r"));
			
			error_log(strlen($this->code));
			
			try
			{
				echo $this->code;
			}
			catch(Exception $e)
			{
				error_log($e->getMessage());
				error_log($e->getTraceAsString());
			}
            
			error_log("Done printing code at " .date("r"));
			
        }
	
	public function displayErrorPage($errorPage){
		// We only load from cache if the following conditions are met:
            // 1. Caching is not disabled
            // 2. Resource tracker is turned off
            // 3. Page is not a form response
            // 4. Page exists in cache
			$this->setPageName($errorPage);
			$this->pageLanguage=userControl::user()->getUserLanguage();
			
            if(file_exists(CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage)&&DISABLE_CACHING==FALSE&&$_SESSION[WEBSITE_URL.'showstringid']<>true&&!isset($_POST['__FORMSTATE']))
            {
                $cache_file_handle = fopen(CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage, 'r');
				$this->code=fread($cache_file_handle,filesize(CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage));
				fclose($cache_file_handle);
                $generationTime=round(microtime() - $this->starttime,4);
                $this->code=str_replace('<%%$$STATISTICS$$%%>','Page generated in '.$generationTime .'s (from cache)',$this->code);
            }
            else
            {
				  $pagesQuery=DB::getInstance()->prepare('SELECT `pages`.`pagename`, func_getStringById(`pages`.`title`,:language,0) AS `title`,func_getStringById(`pages`.`content`,:language,0) AS `content`,func_getStringById(`pages`.`keywords`,:language,0) AS `keywords`,func_getStringById(`pages`.`description`,:language,0) AS `description` FROM `pages` WHERE `pages`.`system`=1 AND `pagename`=:errorPage LIMIT 1');
				  $pagesQuery->bindValue(':errorPage',$errorPage);
				  $pagesQuery->bindValue(':language',$this->pageLanguage);
				  $pagesQuery->execute();
				  while($pages=$pagesQuery->fetch())
				  {
					$this->loadTemplate();
					$this->title=$pages['title'];
					$this->description=$pages['description'];
					$this->content=$pages['content'];
					$this->keywords=$pages['keywords'];
					$this->pageName=$pages['pagename'];

					$this->menu='<ul>';
					$menuQuery=DB::getInstance()->prepare("SELECT func_getStringById(`text`,:language,:showstringid) AS `text`,`page`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menu` INNER JOIN `pages` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 ORDER BY `menu`.`order` ASC");
					$menuQuery->bindParam(':language',$this->pageLanguage);
					$menuQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

					$menuQuery->execute();
					while($menuData=$menuQuery->fetch())
					{
						if($menuData['page']==$this->pageName){
							if(USE_NEWURL_STYLE){
								$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
							}
							else{
								$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
							}
							if($menuData['newwindow']==1){
								$this->menu.=' onclick="window.open(this.href); return false;"';
							}
							$this->menu.='>'.$menuData['text'].'</a></li>';
						}
						elseif($menuData['external']==1){
							$this->menu.='<li><a href="'.$menuData['hyperlink'].'"';
							if($menuData['newwindow']==1){
								$this->menu.=' onclick="window.open(this.href); return false;"';
							}
							$this->menu.='>'.$menuData['text'].'</a></li>';
						}
						else{
							if(USE_NEWURL_STYLE){
								$this->menu.='<li><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
							}
							else{
								$this->menu.='<li><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
							}
							if($menuData['newwindow']==1){
								$this->menu.=' onclick="window.open(this.href); return false;"';
							}
							$this->menu.='>'.$menuData['text'].'</a></li>';
						}
					}
					$this->menu=$this->menu.'</ul>';

					$this->code=str_replace('<%%$$CONTENT$$%%>',$this->content,$this->code);
					if($this->dontProcessGalleries==0)
					{
						$this->processGalleries();
					}
					$this->processResources();
					$this->code=str_replace('<%%$$JAVASCRIPT$$%%>',$this->resources,$this->code);
					$this->code=str_replace('<%%$$CSS$$%%>',$this->resources,$this->code);
					$this->code=str_replace('<%%$$SITEURL$$%%>',WEBSITE_URL,$this->code);
					$this->code=str_replace('<%%$$TITLE$$%%>',htmlspecialchars($this->title,ENT_QUOTES,'UTF-8'),$this->code);
					$this->code=str_replace('<%%$$KEYWORDS$$%%>',htmlspecialchars($this->keywords,ENT_QUOTES,'UTF-8'),$this->code);
					$this->code=str_replace('<%%$$DESCRIPTION$$%%>',htmlspecialchars($this->description,ENT_QUOTES,'UTF-8'),$this->code);
					$this->code=str_replace('<%%$$MENU$$%%>',$this->menu,$this->code);
					
					$this->code=str_replace('{{JQUERY_PAGEFUNCTIONS}}',$this->jQueryPageFunctions,$this->code);
					
					if(DISABLE_CACHING==false&&$this->cacheable==1&&$_SESSION[WEBSITE_URL.'showstringid']<>true&&!isset($_POST['__FORMSTATE'])){
						$cached_filename=CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage;
						$cached_filename=CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage;
						$cache_file_handle = fopen($cached_filename, 'w');
						$cached_page = fwrite($cache_file_handle ,$this->code);
						fclose($cache_file_handle);
					}
					
				  }
		  }
		  print $this->code;

    }
	
    public function buildErrorPages(){

		foreach(glob(CACHE_DIRECTORY.'error/*') as $filename){
			@unlink($filename);
		}
		
      $pagesQuery=DB::getInstance()->query('SELECT `languages`.`twocharacterabbr`,
      `pages`.`pagename`,
      func_getStringById(`pages`.`title`,`languages`.`twocharacterabbr`,0) AS `title`,
      func_getStringById(`pages`.`content`,`languages`.`twocharacterabbr`,0) AS `content`,
      func_getStringById(`pages`.`keywords`,`languages`.`twocharacterabbr`,0) AS `keywords`,
      func_getStringById(`pages`.`description`,`languages`.`twocharacterabbr`,0) AS `description`
      FROM `languages`,`pages`
      WHERE `languages`.`active`=1 AND `pages`.`system`=1');
      while($pages=$pagesQuery->fetch())
      {
        $this->loadTemplate();
		$this->title=$pages['title'];
        $this->description=$pages['description'];
        $this->content=$pages['content'];
        $this->keywords=$pages['keywords'];
        $this->pageLanguage=$pages['twocharacterabbr'];
        $this->pageName=$pages['pagename'];

        $this->menu='<ul>';
        $menuQuery=DB::getInstance()->prepare("SELECT func_getStringById(`text`,:language,:showstringid) AS `text`,`page`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menu` INNER JOIN `pages` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 ORDER BY `menu`.`order` ASC");
        $menuQuery->bindParam(':language',$this->pageLanguage);
        $menuQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

        $menuQuery->execute();
        while($menuData=$menuQuery->fetch())
        {
            if($menuData['page']==$this->pageName){
                if(USE_NEWURL_STYLE){
					$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
				}
				else{
					$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
				}
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
            elseif($menuData['external']==1){
                $this->menu.='<li><a href="'.$menuData['hyperlink'].'"';
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
            else{
                if(USE_NEWURL_STYLE){
					$this->menu.='<li><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
				}
				else{
					$this->menu.='<li><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
				}
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
        }
        $this->menu=$this->menu.'</ul>';

        $this->code=str_replace('<%%$$CONTENT$$%%>',$this->content,$this->code);
        if($this->dontProcessGalleries==0)
        {
            $this->processGalleries();
        }
        $this->processResources();
        $this->code=str_replace('<%%$$JAVASCRIPT$$%%>',$this->resources,$this->code);
        $this->code=str_replace('<%%$$CSS$$%%>',$this->resources,$this->code);
        $this->code=str_replace('<%%$$SITEURL$$%%>',WEBSITE_URL,$this->code);
        $this->code=str_replace('<%%$$TITLE$$%%>',htmlspecialchars($this->title,ENT_QUOTES,'UTF-8'),$this->code);
        $this->code=str_replace('<%%$$KEYWORDS$$%%>',htmlspecialchars($this->keywords,ENT_QUOTES,'UTF-8'),$this->code);
        $this->code=str_replace('<%%$$DESCRIPTION$$%%>',htmlspecialchars($this->description,ENT_QUOTES,'UTF-8'),$this->code);

        $this->code=str_replace('<%%$$MENU$$%%>',$this->menu,$this->code);
		
		$this->code=str_replace('{{JQUERY_PAGEFUNCTIONS}}',$this->jQueryPageFunctions,$this->code);
		
        $cached_filename=CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage;
        $cache_file_handle = fopen($cached_filename, 'w');
        $cached_page = fwrite($cache_file_handle ,$this->code);
        fclose($cache_file_handle);

        $this->code=null;
        $this->resources=null;
        $this->resources=null;

      }

    }
	
	public function buildErrorPage($pageName){
		foreach(glob(CACHE_DIRECTORY.'error/'.$pageName.'_*') as $filename){
			@unlink($filename);
		}
		
      $pagesQuery=DB::getInstance()->prepare('SELECT `languages`.`twocharacterabbr`,
      `pages`.`pagename`,
      func_getStringById(`pages`.`title`,`languages`.`twocharacterabbr`,0) AS `title`,
      func_getStringById(`pages`.`content`,`languages`.`twocharacterabbr`,0) AS `content`,
      func_getStringById(`pages`.`keywords`,`languages`.`twocharacterabbr`,0) AS `keywords`,
      func_getStringById(`pages`.`description`,`languages`.`twocharacterabbr`,0) AS `description`
      FROM `languages`,`pages`
      WHERE `languages`.`active`=1 AND `pages`.`system`=1 AND `pages`.`pagename`=:pageName');
	  $pagesQuery->bindValue(':pageName',$pageName);
	  $pagesQuery->execute();
      while($pages=$pagesQuery->fetch())
      {
        $this->loadTemplate();
		$this->title=$pages['title'];
        $this->description=$pages['description'];
        $this->content=$pages['content'];
        $this->keywords=$pages['keywords'];
        $this->pageLanguage=$pages['twocharacterabbr'];
        $this->pageName=$pages['pagename'];

        $this->menu='<ul>';
        $menuQuery=DB::getInstance()->prepare("SELECT func_getStringById(`text`,:language,:showstringid) AS `text`,`page`,`pages`.`external`,`pages`.`newwindow`, func_getStringById(`hyperlink`,:language,:showstringid) AS `hyperlink` FROM `menu` INNER JOIN `pages` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 ORDER BY `menu`.`order` ASC");
        $menuQuery->bindParam(':language',$this->pageLanguage);
        $menuQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);

        $menuQuery->execute();
        while($menuData=$menuQuery->fetch())
        {
            if($menuData['page']==$this->pageName){
                if(USE_NEWURL_STYLE){
					$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
				}
				else{
					$this->menu.='<li class="active"><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
				}
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
            elseif($menuData['external']==1){
                $this->menu.='<li><a href="'.$menuData['hyperlink'].'"';
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
            else{
                if(USE_NEWURL_STYLE){
					$this->menu.='<li><a href="'.WEBSITE_URL.$this->pageLanguage.'/'.$menuData['page'].'"';
				}
				else{
					$this->menu.='<li><a href="'.WEBSITE_URL.$menuData['page'].'_'.$this->pageLanguage.'.html"';
				}
                if($menuData['newwindow']==1){
                    $this->menu.=' onclick="window.open(this.href); return false;"';
                }
                $this->menu.='>'.$menuData['text'].'</a></li>';
            }
        }
        $this->menu=$this->menu.'</ul>';

        $this->code=str_replace('<%%$$CONTENT$$%%>',$this->content,$this->code);
        if($this->dontProcessGalleries==0)
        {
            $this->processGalleries();
        }
        $this->processResources();
        $this->code=str_replace('<%%$$JAVASCRIPT$$%%>',$this->resources,$this->code);
        $this->code=str_replace('<%%$$CSS$$%%>',$this->resources,$this->code);
        $this->code=str_replace('<%%$$SITEURL$$%%>',WEBSITE_URL,$this->code);
        $this->code=str_replace('<%%$$TITLE$$%%>',htmlspecialchars($this->title,ENT_QUOTES,'UTF-8'),$this->code);
        $this->code=str_replace('<%%$$KEYWORDS$$%%>',htmlspecialchars($this->keywords,ENT_QUOTES,'UTF-8'),$this->code);
        $this->code=str_replace('<%%$$DESCRIPTION$$%%>',htmlspecialchars($this->description,ENT_QUOTES,'UTF-8'),$this->code);
		
		$this->code=str_replace('{{JQUERY_PAGEFUNCTIONS}}',$this->jQueryPageFunctions,$this->code);

        $this->code=str_replace('<%%$$MENU$$%%>',$this->menu,$this->code);
        $cached_filename=CACHE_DIRECTORY.'error/'.$this->pageName.'_'.$this->pageLanguage;
        $cache_file_handle = fopen($cached_filename, 'w');
        $cached_page = fwrite($cache_file_handle ,$this->code);
        fclose($cache_file_handle);

        $this->code=null;
        $this->resources=null;
        $this->resources=null;

      }

    }
}

?>
