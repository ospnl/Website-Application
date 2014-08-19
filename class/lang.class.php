<?php
/**********************************************



Pure-Sites 2010

Author: Oliver Smith

Build: 88

Last Revision: 13/09/2010



lang.class.php



**********************************************/


class lang
{
    public function __construct() {

    }

    public function getLanguageDropDown($userLanguage,$selected=null)
    {
        $languageDropdownQuery=DB::getInstance()->prepare("SELECT `twocharacterabbr`,func_getStringById(`languagename`,:language,:showstringid) AS `language` FROM `languages` WHERE `active`=1");
        $languageDropdownQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
        $languageDropdownQuery->bindParam(':language',$userLanguage);
        $languageDropdownQuery->execute();
        if($selected==null)
        {
            $selected=$userLanguage;
        }

    	$dropdown='<select name="language">';
    	while($language_dropdown_data=$languageDropdownQuery->fetch())
    	{
    		if($language_dropdown_data['twocharacterabbr']==$selected)
            {
                $dropdown.='<option value="'.$language_dropdown_data['twocharacterabbr'].'" selected="selected">'.htmlspecialchars($language_dropdown_data['language'],ENT_QUOTES,'UTF-8').'</option>';
            }
            else
            {
                $dropdown.='<option value="'.$language_dropdown_data['twocharacterabbr'].'">'.htmlspecialchars($language_dropdown_data['language'],ENT_QUOTES,'UTF-8').'</option>';
            }

    	}
    	$dropdown.='</select>';
    	return $dropdown;
    }

    public function doesLanguageExist($language)
    {
        if(file_exists(SETTINGS_DIRECTORY.'lang/'.$language)&&strlen($language)==2)
        {
          return true;
        }
        else
        {
          return false;
        }
    }
}
?>
