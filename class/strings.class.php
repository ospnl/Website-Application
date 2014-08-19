<?php

class StringControl
{
	private $stringId;
	private $stringValues;
	private $defaultStringValues;
	private $stringName;
	private $abortOnDuplicateString;

	public function __construct()
        {
		$this->stringId=0;
		$this->abortOnDuplicateString=true;
	}

	public function doNotAbortOnDuplicateString()
	{
		$this->abortOnDuplicateString=false;
	}

	public function setStringId($stringId)
	{
		$stringId=(int) $stringId;
		if($this->stringId=$stringId){}
		else
		{
			 throw new Exception('Could not set stringId attribute.');
		}
	}

        public function getStringId()
        {
            return $this->stringId;
        }

	public function setStringName($stringName)
	{
		if($this->stringName=$stringName){}
		else
		{
			 throw new Exception('Could not set stringName attribute.');
		}
	}

        /*
         * This method is the way to add a new string to the database
         * It adds an entry for each language in the database (even deleted ones) and a Default (fallback) language.
         *
         * Note: this method should be called within a transaction.
         */

        public function createString($stringName=null)
        {
            /*
                If we create a string with a stringname we need to ensure that it does not already exist
            */
            if($stringName<>null||$stringName<>'')
            {
                $verifyStringExistsQuery=DB::getInstance()->prepare("SELECT `id` FROM `localisationstrings` WHERE `stringname`=:stringName");
                $verifyStringExistsQuery->bindParam(':stringName',$stringName);
                $verifyStringExistsQuery->execute();
                if($verifyStringExistsQuery->rowCount()<>0)
                {
                    return false;
                }
            }

            $stringInsertQuery=DB::getInstance()->prepare("INSERT INTO `localisationstrings` (`stringname`,`stringid`) SELECT :stringName, MAX(`stringid`)+1 AS `newstringid` FROM `localisationstrings`");
            $stringInsertQuery->bindValue(':stringName',$stringName);
            $stringInsertQuery->execute();

            $lastInsertedString=DB::getInstance()->lastInsertId();

            $getStringIdQuery=DB::getInstance()->prepare("SELECT `stringid` FROM `localisationstrings` WHERE `id`=:lastInsertedString");
            $getStringIdQuery->bindParam(':lastInsertedString',$lastInsertedString);
            $getStringIdQuery->execute();
            $stringId=$getStringIdQuery->fetchColumn();

            $stringInsertOverrideQuery=DB::getInstance()->prepare("INSERT INTO `localisationoverrides` (`stringname`,`stringid`) VALUES(:stringName,:stringId)");
            $stringInsertOverrideQuery->bindValue(':stringName',$stringName);
            $stringInsertOverrideQuery->bindValue(':stringId',$stringId);
            $stringInsertOverrideQuery->execute();

            $languageQuery=DB::getInstance()->query("SELECT `twocharacterabbr` FROM `languages`");
            while($languages=$languageQuery->fetch()){
                $stringInsertCacheQuery=DB::getInstance()->prepare("INSERT INTO `stringcache` (`stringname`,`language`,`stringid`) VALUES(:stringName,:language,:stringId)");
                $stringInsertCacheQuery->bindValue(':stringName',$stringName);
                $stringInsertCacheQuery->bindValue(':stringId',$stringId);
                $stringInsertCacheQuery->bindValue(':language',$languages['twocharacterabbr']);
                $stringInsertCacheQuery->execute();
                $stringInsertCacheQuery=null;
            }

            return $stringId;
        }

	public function setOverrideValue($overrideLanguage,$newOverrideValue)
	{
		$newStringValues=$this->stringValues;
		if(!is_array($newStringValues))
		{
			throw new Exception('Error while using method setOverrideValue; $newStringValues is not an array. Please use getOverrideValues first.');
		}
		$stringId=$this->stringId;
		if(!is_int($stringId))
		{
			throw new Exception('Error while using method setOverrideValue; $stringId is not of integer type.');
		}
		$newStringValues[$overrideLanguage]=$newOverrideValue;
		if($this->stringValues=$newStringValues){}
		else
		{
			 throw new Exception('Could not set stringValues attribute.');
		}
	}

	public function setDefaultValue($defaultLanguage,$newDefaultValue)
	{
		$newDefaultStringValues=$this->defaultStringValues;
		if(!is_array($newDefaultStringValues))
		{
			throw new Exception('Error while using method setDefaultValue; $newDefaultStringValues is not an array. Please use getDefaultValues first.');
		}
		$stringId=$this->stringId;
		if(!is_int($stringId))
		{
			throw new Exception('Error while using method setDefaultValue; $stringId is not of integer type.');
		}
		$newDefaultStringValues[$defaultLanguage]=$newDefaultValue;

		if($this->defaultStringValues=$newDefaultStringValues){}
		else
		{
			 throw new Exception('Could not set defaultStringValues attribute.');
		}
	}


        public function getStringByName($stringName,$language,$htmlEncode=false)
        {

            if(file_exists(CACHE_DIRECTORY.'strings/'.$stringName.'_'.$language)&&DISABLE_CACHING==false&&$_SESSION[WEBSITE_URL.'showstringid']==false)
            {
                $stringCacheFile=CACHE_DIRECTORY.'strings/'.$stringName.'_'.$language;
                $fh = fopen($stringCacheFile, 'r');
                $theString = fread($fh, filesize($stringCacheFile));
                fclose($fh);
            }
            else
            {
                $stringQuery=DB::getInstance()->prepare("SELECT func_getStringByName(:stringName,:language,:showstringid) AS `string`");

                $stringQuery->bindParam(':stringName',$stringName);
                $stringQuery->bindParam(':language',$language);

                $stringQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
                $stringQuery->execute();
                while($string=$stringQuery->fetch())
                {
                    $theString=$string['string'];
                }
                if(DISABLE_CACHING==false&&$_SESSION[WEBSITE_URL.'showstringid']==false&&strlen($theString)>0)
                {
                    $fh=fopen(CACHE_DIRECTORY.'strings/'.$stringName.'_'.$language,'w+');
                    fwrite($fh, $theString);
                    fclose($fh);
                }
            }

            if($htmlEncode==true)
            {
                $theString=htmlspecialchars($theString,ENT_QUOTES,'UTF-8');
            }
            return $theString;
        }

        public function setDefaultStringValue($stringId,$stringValue,$language){
            $stringUpdateQuery=DB::getInstance()->prepare("UPDATE `localisationstrings` SET `".$language."`=:stringValue WHERE `stringid`=:stringId");
            $stringUpdateQuery->bindParam(':stringId',$stringId);
            $stringUpdateQuery->bindValue(':stringValue',$stringValue);
            $stringUpdateQuery->execute();
        }

        public function setDefaultStringValueByName($stringName,$stringValue,$language){
            $stringUpdateQuery=DB::getInstance()->prepare("UPDATE `localisationstrings` SET `".$language."`=:stringValue WHERE `stringname`=:stringName");
            $stringUpdateQuery->bindParam(':stringName',$stringName);
            $stringUpdateQuery->bindParam(':stringValue',$stringValue);
            $stringUpdateQuery->execute();
        }

        public function setOverrideStringValue($stringId,$stringValue,$language)
        {
            $stringUpdateQuery=DB::getInstance()->prepare("UPDATE `localisationoverrides` SET `".$language."`=:stringValue WHERE `stringid`=:stringId");
            $stringUpdateQuery->bindParam(':stringId',$stringId);
            $stringUpdateQuery->bindParam(':stringValue',$stringValue);
            $stringUpdateQuery->execute();

            $stringUpdateCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache` SET `string`=:stringValue,`override`=:stringValue WHERE `stringid`=:stringId AND `language`=:language");
            $stringUpdateCacheQuery->bindParam(':stringId',$stringId);
            $stringUpdateCacheQuery->bindParam(':stringValue',$stringValue);
            $stringUpdateCacheQuery->bindParam(':language',$language);
            $stringUpdateCacheQuery->execute();
			
			$cache=new Cache();
            $cache->flush();
            $cache=null;
        }

        public function buildStringCache(){
            DB::getInstance()->exec("TRUNCATE TABLE `stringcache`");
            $languageQuery=DB::getInstance()->query("SELECT `twocharacterabbr` FROM `languages`");
            while($languages=$languageQuery->fetch()){
                $stringQuery=DB::getInstance()->prepare("SELECT `localisationstrings`.`".$languages['twocharacterabbr']."` AS `localdefault`,`localisationoverrides`.`".$languages['twocharacterabbr']."` AS `localoverride`,`localisationstrings`.`default` AS `systemdefault`,`localisationstrings`.`stringid` AS `stringid`,`localisationstrings`.`stringname` AS `stringname` FROM `localisationstrings` INNER JOIN `localisationoverrides` ON `localisationstrings`.`stringid`=`localisationoverrides`.`stringid`");
                $stringQuery->execute();
                while($stringArray=$stringQuery->fetch()){

                    $cacheEntryQuery=DB::getInstance()->prepare("INSERT INTO `stringcache` (`stringid`,`stringname`,`language`,`string`,`default`,`override`) VALUES(:stringid,:stringname,:language,:string,:default,:override)");
                    $cacheEntryQuery->bindValue(":stringid",$stringArray['stringid']);
                    $cacheEntryQuery->bindValue(":stringname",$stringArray['stringname']);
                    $cacheEntryQuery->bindValue(":language",$languages['twocharacterabbr']);

                    if(strlen($stringArray['localoverride'])>0){
                        $cacheEntryQuery->bindValue(":string",$stringArray['localoverride']);
                        $cacheEntryQuery->bindValue(":override",$stringArray['localoverride']);
                        $cacheEntryQuery->bindValue(":default",$stringArray['localdefault']);
                    }
                    elseif(strlen($stringArray['localdefault'])>0){
                        $cacheEntryQuery->bindValue(":override",NULL);
                        $cacheEntryQuery->bindValue(":string",$stringArray['localdefault']);
                        $cacheEntryQuery->bindValue(":default",$stringArray['localdefault']);
                    }
                    else{
                        $cacheEntryQuery->bindValue(":override",NULL);
                        $cacheEntryQuery->bindValue(":default",NULL);
                        $cacheEntryQuery->bindValue(":string",$stringArray['systemdefault']);
                    }

                    $cacheEntryQuery->execute();
                }

            }

            DB::getInstance()->query("OPTIMIZE TABLE `stringcache`");
        }

}
?>