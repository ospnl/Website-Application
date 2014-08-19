<?php
/**********************************************
Pure-Sites 2011
Author: Oliver Smith
Build: 99
Last Revision: 26/12/2011
system.class.php
**********************************************/

class systemConfiguration{
	private $systemSettings; //We will use this variable as an array to store all the system settings
	private $eventLog;
	private $eventLogPagination;
	private $dashboard;

	public function __construct(){
		$systemSettings=array();
		foreach(DB::getInstance()->query("SELECT `keyname`,`keyvalue` FROM `configuration`") as $systemSettingsArray){
			$systemSettings[$systemSettingsArray['keyname']]=$systemSettingsArray['keyvalue'];
		}
		$this->systemSettings=$systemSettings;
	}
	public function getVersion(){
		$systemSettings=$this->systemSettings;
		if(!is_array($systemSettings)){
			throw new Exception('Could not retrieve settings; systemSettings attribute is not an array.');
		}
		return $systemSettings['DATABASE_VERSION'];
	}

	public function getBuild(){
            $systemSettings=$this->systemSettings;
            if(!is_array($systemSettings)){
                    throw new Exception('Could not retrieve settings; systemSettings attribute is not an array.');
            }
            return $systemSettings['DATABASE_BUILD'];
	}

	public function getSetting($key){
		$systemSettings=$this->systemSettings;
		if(!is_array($systemSettings)){
				throw new Exception('Could not retrieve settings; systemSettings attribute is not an array.');
		}
		return $systemSettings[$key];
	}


	public function getDatabaseSize(){
		$dbSizeQuery=DB::getInstance()->query("SELECT sum(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024)) AS size FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA like '".DB_NAME."'");
		while($result=$dbSizeQuery->fetch()){
			$dbSize=$result['size'];
		}
		$dbSize=number_format($dbSize,2,'.',' ');
		return $dbSize;
	}
		
	public function getWebsiteSize(){
		$outputSize=shell_exec('du -s -k '.UPLOAD_DIR);
		$outputSize=str_replace(UPLOAD_DIR,'',$outputSize);
		$outputSize=trim($outputSize);
		$outputSize=round($outputSize/1024,2);
		return $outputSize;
	}

	public function takeSiteOffline(){
		// This should ALWAYS be called before upgrading
		// We do not want to store such a flag in the database to minimise possible conflicts with DB queries
		// Creating a blank file is enough - if the file exists site is offline.
	}

	public function logError($errorMessage,$level){
		$errorMessageQuery=DB::getInstance()->prepare("INSERT INTO `eventlog`(`date`,`message`,`level`) VALUES(now(),:errorMessage,:level)");
		$errorMessageQuery->bindParam(':errorMessage',$errorMessage);
		$errorMessageQuery->bindParam(':level',$level);      $errorMessageQuery->execute();
	}

	public function getEventLog(){
		// As the event log typically contains a considerable amount of entries
		// this page supports pagination.
		// Get the page name passed through the query string
		// Therefore there is nothing to stop a user from maliciously changing the pagename value:
		// Treating the variable as a int data type and requiring the value to be positive; resolved the issue.
		// We send users that specify an 'out of bounds' value to the 404 page
		$requestedPageNumber=(int) $_REQUEST['pn'];
		if($requestedPageNumber<1){
			$requestedPageNumber=1;
		}
		// This defines how many entries are shown on a page
		// We can possibly make this dynamic later on.
		$listLimit=8;
		// Do some maths for the pagination
		// Let's determine how many entries there are in the eventlog
		$eventsNumQuery=DB::getInstance()->query("SELECT `id` FROM `eventlog`");
		$eventCount=$eventsNumQuery->rowCount();
		// If there are no entries then a message should be displayed
		// The values should be returned immediately
		if($eventCount<1){
			$this->eventLog='<p>sorry no events!</p>';
			$this->eventLogPagination='';
			// We can also skip the pagination
			$eventLogCode=array('eventlog'=>$this->eventLog,'pagination'=>$this->eventLogPagination);
			return $eventLogCode;
		}
		// Calculate the amount of pages
		$numberOfPages=ceil($eventCount/$listLimit);
		// Calculate the start value for the LIMIT clause of the query
		// Because the first value is 0 we have to subtract 1.
		$valueStart=($requestedPageNumber-1)*$listLimit;
		// Query the database for events based on the requested page and the list limit
		$eventLogQuery=DB::getInstance()->prepare("SELECT `message`,date_format(`date`,:dateFormat) AS `time`,`level` FROM `eventlog` ORDER BY `date` DESC LIMIT :valueStart,:listLimit");
		$eventLogQuery->bindParam(':valueStart',$valueStart,PDO::PARAM_INT);
		$eventLogQuery->bindParam(':listLimit',$listLimit,PDO::PARAM_INT);
		$eventLogQuery->bindParam(':dateFormat',userControl::user()->getDateFormat());
		$eventLogQuery->execute();
		while($events=$eventLogQuery->fetch()){
			$this->eventLog.='<p class="eventloglevel'.$events['level'].'">';
			$this->eventLog.='<span class="date">'.$events['time'].'</span><br />';
			$this->eventLog.=nl2br(htmlentities($events['message'],ENT_QUOTES,'UTF-8'));
			$this->eventLog.='</p>';
		}
		// If there were entries in the event log and the page number requested does not return any results
		// then log a notification message in the event log.
		// 9 times out of 10 this will be caused by someone trying to modify the parameters in the URL
		if($eventCount<>0&&$eventLogQuery->rowCount()==0){
			$errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
			$errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
			$errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
			$errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
			$errorMessage.='Message: User entered an out of range value when accessing the event log.'."\n";
			$errorMessage.='Total Events: '.$eventCount."\n";
			$errorMessage.='List Limit: '.$listLimit."\n";
			$errorMessage.='Requested Page: '.$requestedPageNumber."\n";
			$system=new systemConfiguration();
			$system->logError($errorMessage,1);
			$system=null;
			header('Location: '.WEBSITE_URL.'404_'.userControl::user()->getUserLanguage().'.html');
		}
		// Create the pagination
		$this->eventLogPagination='<p class="eventlogpagination">';
		for ($i = 1; $i <= $numberOfPages; $i++){
			// Current page should not be a link
			if($i==$requestedPageNumber){
				$this->eventLogPagination.='<span class="current">'.$i.'</span>';
			}
			// Always display the first page in the pagination
			elseif($i==1){
				$this->eventLogPagination.='<span class="first"><a href="index.php?s=eventlog&amp;pn='.$i.'">'.$i.'</a></span>';
			}
			// Always display the last page in the pagination
			elseif($i==$numberOfPages){
				$this->eventLogPagination.='<span class="last"><a href="index.php?s=eventlog&amp;pn='.$i.'">'.$i.'</a></span>';
			}
			// Display links to the two previous pages
			elseif($requestedPageNumber-$i<=2&&$i<$requestedPageNumber){
				$this->eventLogPagination.='<span class="normal"><a href="index.php?s=eventlog&amp;pn='.$i.'">'.$i.'</a></span>';
			}
			// Display links to the two following pages
			elseif($i-$requestedPageNumber<=2&&$i>$requestedPageNumber){
				$this->eventLogPagination.='<span class="normal"><a href="index.php?s=eventlog&amp;pn='.$i.'">'.$i.'</a></span>';
			}
			// Display an 'abbrevation'
			elseif($i==$requestedPageNumber-3||$i==$requestedPageNumber+3){
				$this->eventLogPagination.='<span class="blank">&nbsp;</span>';
			}
		}
		$this->eventLogPagination.='</p>';
		$eventLogCode=array('eventlog'=>$this->eventLog,'pagination'=>$this->eventLogPagination);
		return $eventLogCode;
	}
	
	public function getTinyMCEIcons(){
		
		$icons='theme_advanced_buttons1 : "';
		
		if($this->getSetting('TinyMCE_ShowBold')=='true') { $icons.='bold,'; }
		if($this->getSetting('TinyMCE_ShowItalic')=='true') { $icons.='italic,'; }
		if($this->getSetting('TinyMCE_ShowUnderline')=='true') { $icons.='underline,'; }
		if($this->getSetting('TinyMCE_ShowStrikethrough')=='true') { $icons.='strikethrough,'; }
		
		
		if($this->getSetting('TinyMCE_ShowJustifyLeft')=='true') { $icons.='justifyleft,'; }
		if($this->getSetting('TinyMCE_ShowJustifyCenter')=='true') { $icons.='justifycenter,'; }
		if($this->getSetting('TinyMCE_ShowJustifyRight')=='true') { $icons.='justifyright,'; }
		if($this->getSetting('TinyMCE_ShowJustifyFull')=='true') { $icons.='justifyfull,'; }
		
		
		if($this->getSetting('TinyMCE_ShowForeColor')=='true') { $icons.='forecolor,'; }
		if($this->getSetting('TinyMCE_ShowBackColor')=='true') { $icons.='backcolor,'; }
		
		
		if($this->getSetting('TinyMCE_ShowFormatSelect')=='true') { $icons.='formatselect,'; }
		if($this->getSetting('TinyMCE_ShowFontSelect')=='true') { $icons.='fontselect,'; }
		if($this->getSetting('TinyMCE_ShowFontSizeSelect')=='true') { $icons.='fontsizeselect,'; }
		
		$icons.="\",\n";
		$icons.='theme_advanced_buttons2 : "';
		
		if($this->getSetting('TinyMCE_ShowCut')=='true') { $icons.='cut,'; }
		if($this->getSetting('TinyMCE_ShowCopy')=='true') { $icons.='copy,'; }
		if($this->getSetting('TinyMCE_ShowPaste')=='true') { $icons.='paste,'; }
		if($this->getSetting('TinyMCE_ShowPasteText')=='true') { $icons.='pastetext,'; }
		if($this->getSetting('TinyMCE_ShowPasteWord')=='true') { $icons.='pasteword,'; }
		
		
		if($this->getSetting('TinyMCE_ShowUndo')=='true') { $icons.='undo,'; }
		if($this->getSetting('TinyMCE_ShowRedo')=='true') { $icons.='redo,'; }
		
		
		if($this->getSetting('TinyMCE_ShowBulList')=='true') { $icons.='bullist,'; }
		if($this->getSetting('TinyMCE_ShowNumList')=='true') { $icons.='numlist,'; }
		
		
		if($this->getSetting('TinyMCE_ShowOutdent')=='true') { $icons.='outdent,'; }
		if($this->getSetting('TinyMCE_ShowIndent')=='true') { $icons.='indent,'; }
		if($this->getSetting('TinyMCE_ShowBlockQuote')=='true') { $icons.='blockquote,'; }
		
		
		if($this->getSetting('TinyMCE_ShowLink')=='true') { $icons.='link,'; }
		if($this->getSetting('TinyMCE_ShowUnlink')=='true') { $icons.='unlink,'; }
		if($this->getSetting('TinyMCE_ShowImage')=='true') { $icons.='image,'; }
		if($this->getSetting('TinyMCE_ShowCode')=='true') { $icons.='code,'; }
		
		$icons.="\",\n";
		
		// $icons.='theme_advanced_buttons3 : "';
		// $icons.="\",\n";
		
		return $icons;
	}
    
    public function clearEventLog(){
        DB::getInstance()->query("DELETE FROM `eventlog`");
        $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
        $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
        $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
        $errorMessage.='User language: '.$this->userLanguage."\n";
        $errorMessage.='Class: system'."\n";
        $errorMessage.='Method: clearEventLog'."\n";
        $errorMessage.='Message: Event log cleared.'."\n";
        $system=new systemConfiguration();
        $system->logError($errorMessage,1);
        $system=null;
    }

	public function getDashboard(){
		//Dashboard section
		//Shows useful information about the website
		//Create object to handle strings
		$strings=new stringControl();
		// Make sure the variable is null when the method starts
		$this->dashboard=null;
		// Dashboard title
		$this->dashboard.='<h2>'.$strings->getStringByName('Dashboard.DashboardTitle',userControl::user()->getUserLanguage(),1).'</h2>';
		// Application related settings
		$this->dashboard.=$page_content='<p class="dashboard">';
		// Version
		$this->dashboard.=str_replace('%1',$this->getVersion(),$strings->getStringByName('Dashboard.SoftwareVersion',userControl::user()->getUserLanguage(),1)).'<br />';
		// Build
		$this->dashboard.=str_replace('%1',$this->getBuild(),$strings->getStringByName('Dashboard.SoftwareBuild',userControl::user()->getUserLanguage(),1)).'<br />';
		// Website URL
		$this->dashboard.=str_replace('%1',WEBSITE_URL,$strings->getStringByName('Dashboard.WebsiteURL',userControl::user()->getUserLanguage(),1)).'<br />';
		// Website Size
		$this->dashboard.=str_replace('%1',$this->getWebsiteSize(),$strings->getStringByName('Dashboard.WebsiteSize',userControl::user()->getUserLanguage(),1)).'<br />';
		//  Mailer address
		$this->dashboard.=str_replace('%1',WEBSITE_MAILER_ADDRESS,$strings->getStringByName('Dashboard.WebsiteMailer',userControl::user()->getUserLanguage(),1)).'<br />';
		// Caching - a different message should be displayed
		if(DISABLE_CACHING==false){
			$this->dashboard.=$strings->getStringByName('Dashboard.CachingEnabled',userControl::user()->getUserLanguage(),1).'<br />';
		}
		else{
			$this->dashboard.=$strings->getStringByName('Dashboard.CachingDisabled',userControl::user()->getUserLanguage(),1).'<br />';
		}
		// Number of USER added pages
		$activePagesCountQuery=DB::getInstance()->query("SELECT `pagename` FROM `pages` WHERE `deleted`=0 AND `system`=0");		$active_pages_count=$activePagesCountQuery->rowCount();
		$this->dashboard.=str_replace('%1',$active_pages_count,$strings->getStringByName('Dashboard.Pages',userControl::user()->getUserLanguage(),1)).'<br />';
		// Number of system pages (eg 404)
		$systemPagesCountQuery=DB::getInstance()->query("SELECT `pagename` FROM `pages` WHERE `deleted`=0 AND `system`=1");
		$system_pages_count=$systemPagesCountQuery->rowCount();		$this->dashboard.=str_replace('%1',$system_pages_count,$strings->getStringByName('Dashboard.SystemPages',userControl::user()->getUserLanguage(),1)).'<br />';
		// Number of active languages
		$activeLanguagesCountQuery=DB::getInstance()->query("SELECT `id` FROM `languages` WHERE `deleted`=0 AND `active`=1");
		$active_languages_count=$activeLanguagesCountQuery->rowCount();		$this->dashboard.=str_replace('%1',$active_languages_count,$strings->getStringByName('Dashboard.Languages',userControl::user()->getUserLanguage(),1)).'<br />';
		// Total number of strings
		$activeStringsCountQuery=DB::getInstance()->query("SELECT `id` FROM `localisationstrings`");
		$active_strings_count=$activeStringsCountQuery->rowCount();		$this->dashboard.=str_replace('%1',$active_strings_count,$strings->getStringByName('Dashboard.Strings',userControl::user()->getUserLanguage(),1)).'<br />';
		// String tracker - 2 different messages should be shown
		if($_SESSION[WEBSITE_URL.'showstringid']==true){
			$this->dashboard.=$strings->getStringByName('Dashboard.StringTrackerEnabled',userControl::user()->getUserLanguage(),1).'&nbsp;&nbsp;<a href="dashboard.php?s=disablers">'.$strings->getStringByName('Dashboard.DisableStringTracker',userControl::user()->getUserLanguage(),1).'</a><br />';
		}
		else{
			$this->dashboard.=$strings->getStringByName('Dashboard.StringTrackerDisabled',userControl::user()->getUserLanguage(),1).'&nbsp;&nbsp;<a href="dashboard.php?s=enablers">'.$strings->getStringByName('Dashboard.EnableStringTracker',userControl::user()->getUserLanguage(),1).'</a><br />';
		}
		// User date
		$currentTimeQuery=DB::getInstance()->prepare("SELECT DATE_FORMAT(NOW(),:dateFormat) AS `currenttime`");
		$currentTimeQuery->bindParam(':dateFormat',userControl::user()->getDateFormat());
		$currentTimeQuery->execute();
		$this->dashboard.=str_replace('%1',$currentTimeQuery->fetchColumn(),$strings->getStringByName('Dashboard.UserTime',userControl::user()->getUserLanguage(),1)).'<br />';
		// UTC date
		$currentUTCTimeQuery=DB::getInstance()->prepare("SELECT DATE_FORMAT(UTC_TIMESTAMP(),:dateFormat) AS `currenttime`");
        $currentUTCTimeQuery->bindParam(':dateFormat',userControl::user()->getDateFormat());
		$currentUTCTimeQuery->execute();
		$this->dashboard.=str_replace('%1',$currentUTCTimeQuery->fetchColumn(),$strings->getStringByName('Dashboard.UTCTime',userControl::user()->getUserLanguage(),1)).'<br />';
		// End application related settings
		$this->dashboard.='</p>';
		// Start PHP settings section
		$this->dashboard.='<h2>'.$strings->getStringByName('Dashboard.Section.PHP',userControl::user()->getUserLanguage(),1).'</h2>';
		$this->dashboard.='<p class="dashboard">';
		// PHP Version
		$this->dashboard.=str_replace('%1',phpversion(),$strings->getStringByName('Dashboard.PHP.Version',userControl::user()->getUserLanguage(),1)).'<br />';
		// PDO Version
		$this->dashboard.=str_replace('%1',phpversion('PDO'),$strings->getStringByName('Dashboard.PHP.PDOVersion',userControl::user()->getUserLanguage(),1)).'<br />';
		// PDO Enabled drivers
		$pdoDriversList=null;
                                    foreach(PDO::getAvailableDrivers() as $pdoDriver){
			$pdoDriversList.=$pdoDriver.'&nbsp;';
		}
		$this->dashboard.=str_replace('%1',$pdoDriversList,$strings->getStringByName('Dashboard.PHP.PDOEnabledDrivers',userControl::user()->getUserLanguage(),1)).'<br />';
		// Magic quotes - different messages need to be displayed based on whether MQ are enabled or not
		if(get_magic_quotes_gpc()){
			$this->dashboard.=$strings->getStringByName('Dashboard.MagicQuotesEnabled',userControl::user()->getUserLanguage(),1).'<br />';
		}
		else{
			$this->dashboard.=$strings->getStringByName('Dashboard.MagicQuotesDisabled',userControl::user()->getUserLanguage(),1).'<br />';
		}
		// End PHP settings section
		$this->dashboard.='</p>';
		// Start database settings section
		$this->dashboard.='<h2>'.$strings->getStringByName('Dashboard.Section.Database',userControl::user()->getUserLanguage(),1).'</h2>';
		$this->dashboard.='<p class="dashboard">';
		// Database type - at the moment only MySQL is available
		$this->dashboard.=str_replace('%1','MySQL',$strings->getStringByName('Dashboard.Database.Type',userControl::user()->getUserLanguage(),1)).'<br />';
		// Database server version
		$dbVersionQuery=DB::getInstance()->query("SELECT @@version");
		$this->dashboard.=str_replace('%1',$dbVersionQuery->fetchColumn(),$strings->getStringByName('Dashboard.Database.Version',userControl::user()->getUserLanguage(),1)).'<br />';
		//  Database size
		$this->dashboard.=str_replace('%1',$this->getDatabaseSize(),$strings->getStringByName('Dashboard.DatabaseSize',userControl::user()->getUserLanguage(),1)).'<br />';
		// Max amount of connections (system wide)
		$maxConnectionsQuery=DB::getInstance()->query("SELECT @@max_connections");
		$this->dashboard.=str_replace('%1',$maxConnectionsQuery->fetchColumn(),$strings->getStringByName('Dashboard.Database.MaxConnections',userControl::user()->getUserLanguage(),1)).'<br />';
		// Max amount of connections (user level)
		$maxUserConnectionsQuery=DB::getInstance()->query("SELECT @@max_user_connections");
		$this->dashboard.=str_replace('%1',$maxUserConnectionsQuery->fetchColumn(),$strings->getStringByName('Dashboard.Database.MaxUserConnections',userControl::user()->getUserLanguage(),1)).'<br />';
		// Database Time Zone - this should be set to UTC.
		$systemTimezoneQuery=DB::getInstance()->query("SELECT @@global.time_zone");
		$this->dashboard.=str_replace('%1',$systemTimezoneQuery->fetchColumn(),$strings->getStringByName('Dashboard.Database.SystemTimezone',userControl::user()->getUserLanguage(),1)).'<br />';
		// End database settings section
		$this->dashboard.='</p>';

                // Show the bug fixes
                $this->dashboard.='<h2>'.$strings->getStringByName('Administration.RecentlyResolvedIssues.Title',userControl::user()->getUserLanguage(),1).'</h2>';
                $this->dashboard.='<p class="dashboard">';
                $buildsShownOnDashboard=(int) $this->getSetting('BuildsShownOnDashboard');
                $buildQuery=DB::getInstance()->prepare("SELECT func_getStringById(`name`,'en',0) AS `name`,`buildnumber` FROM `builds` ORDER BY `buildnumber` DESC LIMIT :limit");
                $buildQuery->bindValue(':limit',$buildsShownOnDashboard,PDO::PARAM_INT);

                $buildQuery->execute();
                while($builds=$buildQuery->fetch()){
                    $this->dashboard.='<strong>'.$builds['name'].'</strong><br />';
                    $bugQuery=DB::getInstance()->prepare("SELECT `bugid`,func_getStringById(`description`,'en',0) AS `description` FROM `bugs` WHERE `buildid`=:buildId AND `deleted`=0 AND `feature`=0 ORDER BY `bugid`");
                    $bugQuery->bindValue(':buildId',$builds['buildnumber']);
                    $bugQuery->execute();

                    while($bugs=$bugQuery->fetch()){
                        $this->dashboard.=$bugs['description'].'&nbsp;('.$bugs['bugid'].')<br />';
                    }
                    $this->dashboard.='<br />';
                }

                $this->dashboard.='</p>';
				
				// Show new features
                $this->dashboard.='<h2>'.$strings->getStringByName('Administration.RecentlyAddedFeatures.Title',userControl::user()->getUserLanguage(),1).'</h2>';
                $this->dashboard.='<p class="dashboard">';
                $buildsShownOnDashboard=(int) $this->getSetting('BuildsShownOnDashboard');
                $buildQuery=DB::getInstance()->prepare("SELECT func_getStringById(`name`,'en',0) AS `name`,`buildnumber` FROM `builds` ORDER BY `buildnumber` DESC LIMIT :limit");
                $buildQuery->bindValue(':limit',$buildsShownOnDashboard,PDO::PARAM_INT);

                $buildQuery->execute();
                while($builds=$buildQuery->fetch()){
                    $this->dashboard.='<strong>'.$builds['name'].'</strong><br />';
                    $bugQuery=DB::getInstance()->prepare("SELECT `bugid`,func_getStringById(`description`,'en',0) AS `description` FROM `bugs` WHERE `buildid`=:buildId AND `deleted`=0 AND `feature`=1 ORDER BY `bugid`");
                    $bugQuery->bindValue(':buildId',$builds['buildnumber']);
                    $bugQuery->execute();

                    while($bugs=$bugQuery->fetch()){
                        $this->dashboard.=$bugs['description'].'&nbsp;('.$bugs['bugid'].')<br />';
                    }
                    $this->dashboard.='<br />';
                }

                $this->dashboard.='</p>';
		// Free up some memory; destroy the string object
		$string=null;
		// Return the dashboard
		return $this->dashboard;
	}
}

class Cache{
	public function flush(){
		foreach(glob(CACHE_DIRECTORY.'*') as $filename){
			@unlink($filename);
		}
		foreach(glob(CACHE_DIRECTORY.'strings/*') as $filename){
			@unlink($filename);
		}

	}

	public function flushLanguageSettings(){
		foreach(glob(SETTINGS_DIRECTORY.'lang/*') as $filename){
			@unlink($filename);
		}
	}
}
?>