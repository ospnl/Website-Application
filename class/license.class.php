<?php
/**********************************************
Pure-Sites 2012
Author: Oliver Smith
Build: 100
Last Revision: 03/03/2012
license.class.php
**********************************************/
class license{
	private static $licenseObj;
	private static $license;
    private static $key;
    
    // Used for license string validation
    private static $licenseString;
    private static $licenseURL;
    private static $licenseValidFrom;
    private static $licenseValidUntil;
    private static $licenseKey;
    private static $licenseValue;
    
    // Used for licensing
    private static $salt;
    private static $pass;

	// Prevent users creating object with new
	private function __construct() {
        $this->salt='1^kn7-~?XS(5$L<q/O~=@+xn.6,@Q/';
        $this->pass = '11^32$##@)!!aa\',.ww';
    }
	// Prevent users creating object with clone
	private function __clone() {}
	
	public static function getInstance(){
		if(!isset($licenseObj)){
			$license=array();
			$licenseObj=new license();
			foreach(DB::getInstance()->query("SELECT `keyname`,`keyvalue` FROM `license`") as $licenseValues){
				$license[$licenseValues['keyname']]=$licenseValues['keyvalue'];
			}
			self::$license=$license;
		}
		return $licenseObj;
	}
	
	public static function getValue($keyname){
		return self::$license[$keyname];
	}
	
	public static function getLicenseOverview(){
        $licenseOverview='<p style="text-indent:0;">';
		$licenseQuery=DB::getInstance()->prepare("SELECT func_getStringById(`displayas`,:userLanguage,0) AS `displayas`,`keyvalue` FROM `license` WHERE `keyvalue`>0");
		$licenseQuery->bindParam(':userLanguage',userControl::user()->getUserLanguage());
		$licenseQuery->execute();
		while($license=$licenseQuery->fetch()){
			$licenseOverview.=str_replace('%1',$license['keyvalue'],$license['displayas']).'<br />';
		}
        $licenseOverview.='</p>';
		return $licenseOverview;
	}
    
    public static function getLicenseKeyOverview(){
        // String control object
        $strings=new StringControl();
        
		$licenseQuery=DB::getInstance()->prepare("SELECT `license`,`keyname`,`keyvalue`,DATE(`validuntil`) AS `validuntil` FROM `licensekeys` WHERE `deleted`=0 AND `validuntil`>NOW()");
		$licenseQuery->bindParam(':userLanguage',userControl::user()->getUserLanguage());
        $licenseOverview='<div style="width:200px;float:left;word-wrap: break-word;"><p style="text-indent:0;"><strong>'.$strings->getStringByName('Administration.License.KeySummary.Key',userControl::user()->getUserLanguage(),1).'</strong></p></div>';
        $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;"><strong>'.$strings->getStringByName('Administration.License.KeySummary.Item',userControl::user()->getUserLanguage(),1).'</strong></p></div>';
        $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;"><strong>'.$strings->getStringByName('Administration.License.KeySummary.Quantity',userControl::user()->getUserLanguage(),1).'</strong></p></div>';
        $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;"><strong>'.$strings->getStringByName('Administration.License.KeySummary.ExpiryDate',userControl::user()->getUserLanguage(),1).'</strong></p></div>';
        $licenseOverview.='<div style="width:90px;float:left;text-align:center;"></div>';
        $licenseOverview.='<div class="clear"></div>';
		$licenseQuery->execute();
		while($license=$licenseQuery->fetch()){
			$licenseOverview.='<div style="width:200px;float:left;word-wrap: break-word;"><p style="text-indent:0;">'.$license['license'].'<br /></p></div>';
            $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;">'.$license['keyname'].'</p></div>';
            $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;">'.$license['keyvalue'].'</p></div>';
            $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;">'.$license['validuntil'].'</p></div>';
            $licenseOverview.='<div style="width:90px;float:left;text-align:center;"><p style="text-indent:0;">'.$strings->getStringByName('Administration.License.KeySummary.Active',userControl::user()->getUserLanguage(),1).'</p></div>';
            $licenseOverview.='<div class="clear"></div>';
		}
		return $licenseOverview;
	}
    
    public function loadLicenseString($enteredLicense){
        $implodedLicense=$this->decrypt($enteredLicense);
        $licenseArray=explode('*',$implodedLicense);
        if(count($licenseArray)==5){
            self::$licenseString=$enteredLicense;
            self::$licenseURL=$licenseArray[0];
            self::$licenseValidFrom=$licenseArray[1];
            self::$licenseValidUntil=$licenseArray[2];
            self::$licenseKey=$licenseArray[3];
            self::$licenseValue=$licenseArray[4];
        }
    }
    
    public function isLicenseValid(){
            if(self::$licenseURL<>WEBSITE_URL){
                return false;
            }
            else{
                return true;
            }
     }
    
    public function isLicenseExpired(){
        if(self::$licenseValidUntil>date("U")){
            return false;
        }
        else{
            return true;
        }
    }
    
    public function licensedOption(){
        return self::$licenseKey;
    }
    
    public function licensedOptionValue(){
        return self::$licenseValue;
    }
    
    public function licenseDaysRemaining(){
            $secondsRemaining=self::$licenseValidUntil - date("U");
            $daysRemaining=floor($secondsRemaining/86400);
            return $daysRemaining;
    }
    
    public function modifyLicense($name,$value){
        $licenseKeyUpdateQuery=DB::getInstance()->prepare("UPDATE `license` SET  `keyvalue`=:value WHERE `keyname`=:name");
        $licenseKeyUpdateQuery->bindValue(':value',$value);
        $licenseKeyUpdateQuery->bindValue(':name',$name);
        $licenseKeyUpdateQuery->execute();
    }
    
    public function doesLicenseExist(){
        $checkLicenseQuery=DB::getInstance()->prepare("SELECT `deleted` FROM `licensekeys` WHERE `license`=:license AND `deleted`=0");
        $checkLicenseQuery->bindValue(':license',self::$licenseString);
        $checkLicenseQuery->execute();
        if($checkLicenseQuery->rowCount()==0){
            return false;
        }
        else{
            return true;
        }
    }
    
    public function addLicense(){
        if(self::$licenseValidUntil>0){
            $addLicenseQuery=DB::getInstance()->prepare("INSERT INTO `licensekeys` (`license`,`validfrom`,`validuntil`,`siteurl`,`keyname`,`keyvalue`) VALUES(:license,FROM_UNIXTIME(:validFrom),FROM_UNIXTIME(:validUntil),:siteUrl,:keyName,:keyValue)");
            $addLicenseQuery->bindValue(':license',self::$licenseString);
            $addLicenseQuery->bindValue(':validFrom',self::$licenseValidFrom);
            $addLicenseQuery->bindValue(':validUntil',self::$licenseValidUntil);
            $addLicenseQuery->bindValue(':validFrom',self::$licenseValidFrom);
            $addLicenseQuery->bindValue(':siteUrl',self::$licenseURL);
            $addLicenseQuery->bindValue(':keyName',self::$licenseKey);
            $addLicenseQuery->bindValue(':keyValue',self::$licenseValue);
            $addLicenseQuery->execute();
            return true;
        }
        else{
            return false;
        }
    }
    
     
        /** Encryption Procedure
         *
         *  @param mixed msg message/data
         *  @param string k encryption key
         *  @param boolean base64 base64 encode result
         *
         *  @return string iv+ciphertext+mac or
         * boolean false on error
        */
        public function encrypt($msg) {
            $k=$this->pbkdf2();
            $base64 = true;
            # open cipher module (do not change cipher/mode)
            if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
                return false;
     
            $msg = serialize($msg);                         # serialize
            $iv = mcrypt_create_iv(32, MCRYPT_RAND);        # create iv
     
            if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
                return false;
     
            $msg = mcrypt_generic($td, $msg);               # encrypt
            $msg = $iv . $msg;                              # prepend iv
            $mac = $this->pbkdf2($msg, $k, 1000, 32);       # create mac
            $msg .= $mac;                                   # append mac
     
            mcrypt_generic_deinit($td);                     # clear buffers
            mcrypt_module_close($td);                       # close cipher module
     
            if ( $base64 ) $msg = base64_encode($msg);      # base64 encode?
     
            return $msg;                                    # return iv+ciphertext+mac
        }
     
        /** Decryption Procedure
         *
         *  @param string msg output from encrypt()
         *  @param string k encryption key
         *  @param boolean base64 base64 decode msg
         *
         *  @return string original message/data or
         * boolean false on error
        */
        public function decrypt( $msg) {
            $k=$this->pbkdf2();
            $base64 = true;
            if ( $base64 ) $msg = base64_decode($msg);          # base64 decode?
     
            # open cipher module (do not change cipher/mode)
            if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
                return false;
     
            $iv = substr($msg, 0, 32);                          # extract iv
            $mo = strlen($msg) - 32;                            # mac offset
            $em = substr($msg, $mo);                            # extract mac
            $msg = substr($msg, 32, strlen($msg)-64);           # extract ciphertext
            $mac = $this->pbkdf2($iv . $msg, $k, 1000, 32);     # create mac
     
            if ( $em !== $mac )                                 # authenticate mac
                return false;
     
            if ( mcrypt_generic_init($td, $k, $iv) !== 0 )      # initialize buffers
                return false;
     
            $msg = mdecrypt_generic($td, $msg);                 # decrypt
            $msg = unserialize($msg);                           # unserialize
     
            mcrypt_generic_deinit($td);                         # clear buffers
            mcrypt_module_close($td);                           # close cipher module
     
            return $msg;                                        # return original msg
        }
     
        /** PBKDF2 Implementation (as described in RFC 2898);
         *
         *  @param string p password
         *  @param string s salt
         *  @param int c iteration count (use 1000 or higher)
         *  @param int kl derived key length
         *  @param string a hash algorithm
         *
         *  @return string derived key
        */
        public function pbkdf2() {
     
            $a = 'sha256';
            $c=20000;
            $kl=32;
            $hl = strlen(hash($a, null, true)); # Hash length
            $kb = ceil($kl / $hl);              # Key blocks to compute
            $dk = '';                           # Derived key
     
            # Create key
            for ( $block = 1; $block <= $kb; $block ++ ) {
     
                # Initial hash for this block
                $ib = $b = hash_hmac($a, self::$salt . pack('N', $block), self::$pass, true);
     
                # Perform block iterations
                for ( $i = 1; $i < $c; $i ++ )
     
                    # XOR each iterate
                    $ib ^= ($b = hash_hmac($a, $b, self::$pass, true));
     
                $dk .= $ib; # Append iterated block
            }
     
            # Return derived key of correct length
            return substr($dk, 0, $kl);
        }
        
}
?>
