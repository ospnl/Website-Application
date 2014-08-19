<?php
/**********************************************

Pure-Sites 2011
Author: Oliver Smith
Build: 100

Last Revision: 14/01/2012
forms.class.php

**********************************************/


class forms
{
    private $formId;
    private $formFailed;
    private $errorMessage;
    private $fieldErrorMessages;
	private $fieldValues;
    private $successMessage;
    private $complete=false;
	private $systemForm=false;

    public function getFormByName($formName,$userLanguage){
        $formFieldQuery=DB::getInstance()->prepare("SELECT `forms`.`id`,`forms`.`formname`,`formfields`.`type`,`formfields`.`id` AS `formfieldid`,func_getStringById(`fieldname`,:language,:showstringid) AS `name` FROM `forms` INNER JOIN `formfields` ON `forms`.`id`=`formfields`.`formid` WHERE `forms`.`formname`=:formName AND `forms`.`deleted`=0 AND `formfields`.`active`=1 ORDER BY `formfields`.`order`");
        $formFieldQuery->bindParam(':formName',$formName);
        $formFieldQuery->bindParam(':language',$userLanguage);
        $formFieldQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
        $formFieldQuery->execute();
        $formCode=null;
        $fieldValue=null;
        $formId=null;
        while($data=$formFieldQuery->fetch())
        {
            if(!isset($formCode))
            {
                if(!$this->errorMessage==null){
                    $formCode='<p class="formError"><span>'.$this->errorMessage.'</span></p>';
                }
                if($this->complete){
                    $formCode='<p><span>'.$this->successMessage.'</span></p>';
                }
                $formCode.='<form name="'.$data['id'].'" method="post"><div class="form">';
                $formId=$data['id'];
            }
            $fieldId=$data['id'].'_'.$data['formfieldid'];
            if($this->formFailed)
            {
                $fieldValue=htmlentities($_POST[$fieldId],ENT_QUOTES,'UTF-8');
            }
            switch($data['type'])
            {
                case 'submit':
                    $formCode.='<div class="submitfieldlabel">&nbsp;</div><div class="submitfield"><input type="submit" name="'.$fieldId.'" value="'.$data['name'].'" class="submitbutton" /></div><div class="submitfieldmessage"></div><div class="clear"></div>';
                    break;
                case 'textarea':
                    $formCode.='<textarea name="'.$fieldId.'" class="simpletextarea">'.$data['name'].'</textarea>'.$this->fieldErrorMessages[$fieldId].'<br />';
                    break;
                case 'hidden':
                    $formCode.='<input type="hidden" name="'.$fieldId.'" value="'.$data['name'].'" />'.$this->fieldErrorMessages[$fieldId].'<br />';
                    break;
                default:
                    $formCode.='<div class="inputfieldlabel"><p>'.$data['name'].'<p></div><div class="inputfield"><input type="text" name="'.$fieldId.'" value="'.$fieldValue.'" /></div><div class="inputfieldmessage">'.$this->fieldErrorMessages[$fieldId].'</div><div class="clear"></div>';
            }
            
        }
        $formCode.='<input type="hidden" name="__FORMSTATE" value="'.$formId.'" /><br />';
        $formCode.='</div></form>';
        return $formCode;
    }

	public function getFormById($formId,$userLanguage){
        $formFieldQuery=DB::getInstance()->prepare("SELECT `forms`.`id`,`forms`.`formname`,`formfields`.`type`,`formfields`.`id` AS `formfieldid`,func_getStringById(`fieldname`,:language,:showstringid) AS `name` FROM `forms` INNER JOIN `formfields` ON `forms`.`id`=`formfields`.`formid` WHERE `forms`.`id`=:formId AND `forms`.`deleted`=0 AND `formfields`.`active`=1 ORDER BY `formfields`.`order`");
        $formFieldQuery->bindParam(':formId',$formId);
        $formFieldQuery->bindParam(':language',$userLanguage);
        $formFieldQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
        $formFieldQuery->execute();
        $formCode=null;
        $fieldValue=null;
        $formId=null;
        while($data=$formFieldQuery->fetch())
        {
            if(!isset($formCode))
            {
                if(!$this->errorMessage==null){
                    $formCode='<p class="formError"><span>'.$this->errorMessage.'</span></p>';
                }
                if($this->complete){
                    $formCode='<p><span>'.$this->successMessage.'</span></p>';
                }
                $formCode.='<form name="'.$data['id'].'" method="post"><div class="form">';
                $formId=$data['id'];
            }
            $fieldId=$data['id'].'_'.$data['formfieldid'];
            if($this->formFailed)
            {
                $fieldValue=htmlentities($_POST[$fieldId],ENT_QUOTES,'UTF-8');
            }
            switch($data['type'])
            {
                case 'submit':
                    $formCode.='<div class="submitfieldlabel">&nbsp;</div><div class="submitfield"><input type="submit" name="'.$fieldId.'" value="'.$data['name'].'" class="submitbutton" /></div><div class="submitfieldmessage"></div><div class="clear"></div>';
                    break;
                case 'textarea':
                    $formCode.='<textarea name="'.$fieldId.'" class="simpletextarea">'.$data['name'].'</textarea>'.$this->fieldErrorMessages[$fieldId].'<br />';
                    break;
                case 'hidden':
                    $formCode.='<input type="hidden" name="'.$fieldId.'" value="'.$data['name'].'" />'.$this->fieldErrorMessages[$fieldId].'<br />';
                    break;
                default:
                    $formCode.='<div class="inputfieldlabel"><p>'.$data['name'].'<p></div><div class="inputfield"><input type="text" name="'.$fieldId.'" value="'.$fieldValue.'" /></div><div class="inputfieldmessage">'.$this->fieldErrorMessages[$fieldId].'</div><div class="clear"></div>';
            }
            
        }
        $formCode.='<input type="hidden" name="__FORMSTATE" value="'.$formId.'" /><br />';
        $formCode.='</div></form>';
        return $formCode;
    }

    public function validateFormByName($formName,$userLanguage)
    {
        $formFieldQuery=DB::getInstance()->prepare("SELECT `forms`.`id`,`forms`.`formname`,`forms`.`system` AS `system`,`formfields`.`id` AS `formfieldid`,`formfields`.`required`,`formfields`.`type`,`formfields`.`minlength`,`formfields`.`maxlength`,`formfields`.`regex`,func_getStringById(`maxlengtherror`,:language,:showstringid) AS `maxlengtherror`,func_getStringById(`minlengtherror`,:language,:showstringid) AS `minlengtherror`,func_getStringById(`regexerror`,:language,:showstringid) AS `regexerror`, func_getStringById(`forms`.`successmessage`,:language,:showstringid) AS `successmessage` FROM `forms` INNER JOIN `formfields` ON `forms`.`id`=`formfields`.`formid` WHERE `forms`.`formname`=:formName AND `forms`.`deleted`=0 AND `formfields`.`active`=1");
        $formFieldQuery->bindParam(':formName',$formName);
        $formFieldQuery->bindParam(':language',$userLanguage);
        $formFieldQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
        $formFieldQuery->execute();
        while($data=$formFieldQuery->fetch())
        {
            if(!isset($result)){
                $result=true;
                $formId=$data['id'];
            }
            if($data['system']==1){
				$this->systemForm=true;
			}
			$this->successMessage=$data['successmessage'];
            $fieldValue=$_POST[$formId.'_'.$data['formfieldid']];
            $fieldValueLength=strlen($fieldValue);
            
            if($fieldValueLength<$data['minlength']&&$data['required']==1)
            {
                $this->fieldErrorMessages=array($data['id'].'_'.$data['formfieldid']=>'<p class="fieldError">'.$data['minlengtherror'].'</p>');
                $this->formFailed=true;
                $result=false;
            }
            elseif($fieldValueLength>$data['maxlength']&&$data['required']==1&&$data['maxlength']>0)
            {
                $this->fieldErrorMessages=array($data['id'].'_'.$data['formfieldid']=>'<p class="fieldError">'.$data['maxlengtherror'].'</p>');
                $this->formFailed=true;
                $result=false;
            }
            elseif(!preg_match('"'.$data['regex'].'"', $fieldValue))
            {
                $this->fieldErrorMessages=array($data['id'].'_'.$data['formfieldid']=>'<p class="fieldError">'.$data['regexerror'].'</p>');
                $this->formFailed=true;
                $result=false;
            }
            else
            {
                // The entered data for the field matches the constraints
				// Store it in an array for database insertion in the complete() method
				$this->fieldValues[$data['formfieldid']]=$fieldValue;
            }


        }
        // print_r($this->fieldErrorMessages);
        return $result;
    }
    
    public function validateFormById($formId,$userLanguage){
		$this->formId=$formId;
        $formFieldQuery=DB::getInstance()->prepare("SELECT `forms`.`id`,`forms`.`formname`,`forms`.`system` AS `system`,`formfields`.`id` AS `formfieldid`,`formfields`.`required`,`formfields`.`type` AS `type`,`formfields`.`minlength`,`formfields`.`maxlength`,`formfields`.`regex`,func_getStringById(`maxlengtherror`,:language,:showstringid) AS `maxlengtherror`,func_getStringById(`minlengtherror`,:language,:showstringid) AS `minlengtherror`,func_getStringById(`regexerror`,:language,:showstringid) AS `regexerror`, func_getStringById(`forms`.`successmessage`,:language,:showstringid) AS `successmessage` FROM `forms` INNER JOIN `formfields` ON `forms`.`id`=`formfields`.`formid` WHERE `forms`.`id`=:formId AND `forms`.`deleted`=0 AND `formfields`.`active`=1");
        $formFieldQuery->bindParam(':formId',$formId);
        $formFieldQuery->bindParam(':language',$userLanguage);
        $formFieldQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
        $formFieldQuery->execute();
        while($data=$formFieldQuery->fetch()){
            if(!isset($result))
            {
                $result=true;
                $formId=$data['id'];
            }
            if($data['system']==1){
				$this->systemForm=true;
			}
            $this->successMessage=$data['successmessage'];
            $fieldValue=$_POST[$formId.'_'.$data['formfieldid']];
            $fieldValueLength=strlen($fieldValue);
            
            if($fieldValueLength<$data['minlength']&&$data['required']==1)
            {
                $this->fieldErrorMessages[$data['id'].'_'.$data['formfieldid']]='<p class="fieldError">'.$data['minlengtherror'].'</p>';
                $this->formFailed=true;
                $result=false;
            }
            elseif($fieldValueLength>$data['maxlength']&&$data['required']==1&&$data['maxlength']>0)
            {
                $this->fieldErrorMessages[$data['id'].'_'.$data['formfieldid']]='<p class="fieldError">'.$data['maxlengtherror'].'</p>';
                $this->formFailed=true;
                $result=false;
            }
            elseif(!preg_match('"'.$data['regex'].'"', $fieldValue))
            {
                $this->fieldErrorMessages[$data['id'].'_'.$data['formfieldid']]='<p class="fieldError">'.$data['regexerror'].'</p>';
                $this->formFailed=true;
                $result=false;
            }
            else{
                // The entered data for the field matches the constraints
				// Store it in an array for database insertion in the complete() method
				$this->fieldValues[$data['formfieldid']]=$fieldValue;
            }


        }
        // print_r($this->fieldErrorMessages);
        return $result;
    }

    public function getFieldValueByName($fieldName)
    {
        $fieldQuery=DB::getInstance()->prepare("SELECT `id`,`formid` FROM `formfields` WHERE `name`=:fieldName AND `deleted`=0");
        $fieldQuery->bindParam(':fieldName',$fieldName);
        $fieldQuery->execute();
        while($data=$fieldQuery->fetch())
        {
            $fieldId=$data['formid'].'_'.$data['id'];
            $fieldValue=$_POST[$fieldId];
        }
        return $fieldValue;
    }

    public function setErrorMessage($error)
    {
        $this->errorMessage=$error;
        $this->formFailed=true;
    }
    
    public function getFormPageById()
    {
        return 'home';
    }
    
    public function complete(){
		try{
			if($this->systemForm==true){
				$this->complete=true;
			}
			else{
				DB::getInstance()->beginTransaction();
				// Create a form response
				$insertFormResponse=DB::getInstance()->prepare("INSERT INTO `formresponses` (`formid`,`ip`,`useragent`,`language`) VALUES(:formId,:ip,:userAgent,:language)");
				$insertFormResponse->bindValue(":formId",$this->formId);
				$insertFormResponse->bindValue(":ip",$_SERVER['REMOTE_ADDR']);
				$insertFormResponse->bindValue(":userAgent",$_SERVER['HTTP_USER_AGENT']);
				$insertFormResponse->bindValue(":language",userControl::user()->getUserLanguage());
				$insertFormResponse->execute();
				$formResponseId=DB::getInstance()->lastInsertId();
				
				// Query the list of field for the form and add the values in the formresponsevalues table
				$fieldQuery=DB::getInstance()->prepare("SELECT `id` FROM `formfields` WHERE `formid`=:formId AND `type`<>'submit' AND `active`=1");
				$fieldQuery->bindValue(':formId',$this->formId);
				$fieldQuery->execute();
				while($fieldInfo=$fieldQuery->fetch()){
					$insertFieldValue=DB::getInstance()->prepare("INSERT INTO `formresponsevalues` (`formresponseid`,`fieldid`,`value`) VALUES (:formResponseId,:fieldId,:value)");
					$insertFieldValue->bindValue(':formResponseId',$formResponseId);
					$insertFieldValue->bindValue(':fieldId',$fieldInfo['id']);
					$insertFieldValue->bindValue(':value',$this->fieldValues[$fieldInfo['id']]);
					$insertFieldValue->execute();
				}
				DB::getInstance()->commit();
				$this->complete=true;
			}
		}
		catch(Exception $e){
			// Do not rollback if we didn't start the transaction
			if($this->systemForm<>true){
				DB::getInstance()->rollback();
			}
			$this->errorMessage='An unknown error has occured please try again later.';
			$errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
			$errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
			$errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
			$errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
			$errorMessage.='Class: forms'."\n";
			$errorMessage.='Method: complete'."\n";
			$errorMessage.='Form id: '.$this->formId."\n";
			$errorMessage.='Error: '.$e->getMessage()."\n";
			$errorMessage.='Trace: '.$e->getTraceAsString()."\n";
			$system=new systemConfiguration();
			$system->logError($errorMessage,2);
			$system=null;
		}
    }
	
	public function getFormList(){
		$output=null;
		$languageControl=new lang();
		$languageDropdown=$languageControl->getLanguageDropDown(userControl::user()->getUserLanguage());
		$formQuery=DB::getInstance()->query("SELECT `formname`,`id` FROM `forms` WHERE `system`=0");
		while($form=$formQuery->fetch()){
			$output.='<form action="forms.php" method="get"><p>'.$form['formname'].$languageDropdown.'&nbsp;&nbsp;<input type="submit" name="editbutton" class="editbutton" value="Edit" /><input type="hidden" name="edit" value="'.$form['id'].'"/><a href="forms.php?view='.$form['id'].'">View</a></p></form>';
		}
		return $output;
	}
	
	// might want to rename this method
	public function getFormFieldList($formId,$language){
		$output=null;
		
		
		
		$formSearchQuery=DB::getInstance()->prepare("SELECT `id`,`name`,`type`,`required`,`regex`,`minlength`,`maxlength`,`active`,func_getStringById(`minlengtherror`,:language,0) AS `minlengtherror`,func_getStringById(`maxlengtherror`,:language,0) AS `maxlengtherror`,func_getStringById(`regexerror`,:language,0) AS `regexerror`,func_getStringById(`fieldname`,:language,0) AS `displayas` FROM `formfields` WHERE `formid`=:formId");
		$formSearchQuery->bindValue(':formId',$formId);
		$formSearchQuery->bindValue(':language',$language);
		$formSearchQuery->execute();
		$numberOfFields=$formSearchQuery->rowCount();
		
		$responseSearchQuery=DB::getInstance()->prepare("SELECT `id` FROM `formresponses` WHERE `formid`=:formId");
		$responseSearchQuery->bindValue(':formId',$formId);
		$responseSearchQuery->execute();
		$numberOfResponses=$responseSearchQuery->rowCount();
		
		$output.='<h2>Form Settings</h2>';
		
		
		$output.='<p>Number of fields '.$numberOfFields.'</p>';
		$output.='<p>Number of responses '.$numberOfResponses.'</p>';
		
		// Create a nice little header
		$output.='<h2>Fields</h2>';
		$output.='<ul id="sortable" class="connectedSortable">';
		while($formSearch=$formSearchQuery->fetch()){
			$output.='<li id="'.$formSearch['displayas'].'" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'.htmlentities($formSearch['displayas'],ENT_NOQUOTES,'UTF-8').'&nbsp;&nbsp;&nbsp;<a href="forms.php?editfield='.$formSearch['id'].'&amp;language='.$language.'">Edit</a></li>';
		}
		$output.='</ul>';
		return $output;
	}

	public function getFormField($fieldId,$language){
		$fieldSearchQuery=DB::getInstance()->prepare("SELECT `id`,`name`,`type`,`required`,`regex`,`minlength`,`maxlength`,`active`,func_getStringById(`minlengtherror`,:language,0) AS `minlengtherror`,func_getStringById(`maxlengtherror`,:language,0) AS `maxlengtherror`,func_getStringById(`regexerror`,:language,0) AS `regexerror`,func_getStringById(`fieldname`,:language,0) AS `displayas` FROM `formfields` WHERE `id`=:fieldId");
		$fieldSearchQuery->bindValue(':fieldId',$fieldId);
		$fieldSearchQuery->bindValue(':language',$language);
		$fieldSearchQuery->execute();
		
		$output.='<form action="forms.php" method="post">';
		$output.='<input name="formeditsave" type="submit" value="##Save"/>';
		$output.='<input name="formid" type="hidden" value="'.$fieldId.'"/>';
		$output.='<input name="formlanguage" type="hidden" value="'.$language.'"/>';
		while($fieldSearch=$fieldSearchQuery->fetch()){
			$output.='<div style="float:left;width:300px;padding-bottom:30px;"><table><tr><td>##Type</td><td><select name="'.$fieldSearch['id'].'_type">';
			$output.='<option value="text"';
			if($fieldSearch['type']=='text'){
				$output.=' selected="selected"';
			}
			$output.='>##Text</option>';
			
			$output.='<option value="textarea"';
			if($fieldSearch['type']=='textarea'){
				$output.=' selected="selected"';
			}
			$output.='>##Multiline text</option>';
			
			$output.='<option value="checkbox"';
			if($fieldSearch['type']=='checkbox'){
				$output.=' selected="selected"';
			}
			$output.='>##Checkbox</option>';
			
			$output.='<option value="submit"';
			if($fieldSearch['type']=='submit'){
				$output.=' selected="selected"';
			}
			$output.='>##Submit</option>';
			
			$output.='<option value="hidden"';
			if($fieldSearch['type']=='hidden'){
				$output.=' selected="selected"';
			}
			$output.='>##Hidden</option>';
			
			$output.='<option value="recaptcha"';
			if($fieldSearch['type']=='recaptha'){
				$output.=' selected="selected"';
			}
			$output.='>##Recaptcha</option>';
			
			$output.='</select></td></tr>';
			
			$output.='<tr><td>##Display as</td><td><input name="'.$fieldSearch['id'].'_displayas" type="text"';
			$output.=' value="'.htmlentities($fieldSearch['displayas'],ENT_NOQUOTES,'UTF-8').'"/></td></tr>';
			
			$output.='<tr><td>##Active</td><td><input name="'.$fieldSearch['id'].'_active" type="checkbox"';
			if($fieldSearch['active']==1){
				$output.=' checked="checked"';
			}
			$output.=' value="true"/></td></tr>';
			
			
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'||$fieldSearch['type']=='checkbox'){
				$output.='<tr><td>##Required</td><td><input name="'.$fieldSearch['id'].'_required" type="checkbox"';
				if($fieldSearch['required']==1){
					$output.=' checked="checked"';
				}
				$output.='/></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){
				$output.='<tr><td>##Minimum length</td><td><input name="'.$fieldSearch['id'].'_minlength" type="text"';
				$output.=' value="'.$fieldSearch['minlength'].'"/></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Error</td><td><textarea name="'.$fieldSearch['id'].'_minlengtherror">';
				$output.=htmlentities($fieldSearch['minlengtherror'],ENT_NOQUOTES,'UTF-8').'</textarea><br /><br /></td></tr>';
			}
				
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){
				$output.='<tr><td>##Maximum length</td><td><input name="'.$fieldSearch['id'].'_maxlength" type="text"';
				$output.=' value="'.$fieldSearch['maxlength'].'"/></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Error</td><td><textarea name="'.$fieldSearch['id'].'_maxlengtherror">';
				$output.=htmlentities($fieldSearch['maxlengtherror'],ENT_NOQUOTES,'UTF-8').'</textarea><br /><br /></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Regex</td><td><input name="'.$fieldSearch['id'].'_regex" type="text"';
				$output.=' value="'.htmlentities($fieldSearch['regex'],ENT_NOQUOTES,'UTF-8').'"/></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Error</td><td><textarea name="'.$fieldSearch['id'].'_regexerror">';
				$output.=htmlentities($fieldSearch['regexerror'],ENT_NOQUOTES,'UTF-8').'</textarea><br /><br /></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'||$fieldSearch['type']=='checkbox'||$fieldSearch['type']=='hidden'){
				$output.='<tr><td>##Default value</td><td><input name="'.$fieldSearch['id'].'_defaultvalue" type="text" value="'.htmlentities($fieldSearch['regexerror'],ENT_NOQUOTES,'UTF-8').'"/></td></tr>';
			}
			
			if($fieldSearch['type']=='text'||$fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Width</td><td><input name="'.$fieldSearch['id'].'_defaultvalue" type="text" value="'.htmlentities($fieldSearch['regexerror'],ENT_NOQUOTES,'UTF-8').'"/></td></tr>';
			}
				
			if($fieldSearch['type']=='textarea'){	
				$output.='<tr><td>##Height</td><td><input name="'.$fieldSearch['id'].'_defaultvalue" type="text" value="'.htmlentities($fieldSearch['regexerror'],ENT_NOQUOTES,'UTF-8').'"/></td></tr>';
			}
			
			$output.='</table></div>';
			
			$output.='<div class="clear"></div>';
			
		}
		$output.='</form>';
		
		return $output;
	}
	
	public function showRecords($formId){
		$output='<table>';
		
		// The first column will always be the date
		$output.='<tr><td><strong>Date</strong></td>';
		$fieldHeadersQuery=DB::getInstance()->prepare("SELECT func_getStringById(`fieldname`,:language,:showstringid) AS `title` FROM `formfields` WHERE `formid`=:formId AND `type`<>'submit' AND `showinsummary`=1 ORDER BY `order`");
		$fieldHeadersQuery->bindValue(':language',userControl::user()->getUserLanguage());
		$fieldHeadersQuery->bindValue(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
		$fieldHeadersQuery->bindValue(':formId',$formId);
		$fieldHeadersQuery->execute();
		while($header=$fieldHeadersQuery->fetch()){
			$output.='<td><strong>'.htmlentities($header['title'],ENT_NOQUOTES,'UTF-8').'</strong></td>';
		}
		$output.='<td></td></tr>'; // The extra column is for the "View link"
		
		$recordQuery=DB::getInstance()->prepare("SELECT `id`,`date` FROM `formresponses` WHERE `formid`=:formId ORDER BY `date` DESC");
		$recordQuery->bindValue(':formId',$formId);
		$recordQuery->execute();
		while($data=$recordQuery->fetch()){
			$output.='<tr><td>'.$data['date'].'</td>';
			$fieldQuery=DB::getInstance()->prepare("SELECT `formfields`.`id`,`formresponsevalues`.`value` FROM `formfields` LEFT JOIN `formresponsevalues` ON `formresponsevalues`.`fieldid`=`formfields`.`id` AND `formresponsevalues`.`formresponseid`=:formResponseId WHERE `formfields`.`formid`=:formId AND `formfields`.`type`<>'submit' AND `formfields`.`showinsummary`=1 ORDER BY `formfields`.`order`");
			$fieldQuery->bindValue(':formId',$formId);
			$fieldQuery->bindValue(':formResponseId',$data['id']);
			$fieldQuery->execute();
			while($fields=$fieldQuery->fetch()){
				if(strlen($fields['value'])>20){
					$output.='<td>'.htmlentities(substr($fields['value'],0,20),ENT_NOQUOTES,'UTF-8').'...</td>';
				}
				else{
					$output.='<td>'.htmlentities($fields['value'],ENT_NOQUOTES,'UTF-8').'</td>';
				}
			}
			$output.='<td><a href="forms.php?viewresponse='.$data['id'].'">View</a></td>';
			$output.='</tr>';
		}
		$output.='</table>';
		return $output;
	}
	
	public function showResponse($responseId){
		try{
			$output='<table>';
			$responseQuery=DB::getInstance()->prepare("SELECT `language`,`ip`,`useragent` FROM `formresponses` WHERE `id`=:responseId LIMIT 1");
			$responseQuery->bindValue(':responseId',$responseId);
			$responseQuery->execute();
			while($response=$responseQuery->fetch()){
				$fieldQuery=DB::getInstance()->prepare("SELECT func_getStringById(`formfields`.`fieldname`,:language,:showstringid) AS `fieldname`,`formresponsevalues`.`value` FROM `formfields` RIGHT JOIN `formresponsevalues` ON `formresponsevalues`.`fieldid`=`formfields`.`id` AND `formresponsevalues`.`formresponseid`=:formResponseId WHERE `formfields`.`type`<>'submit' ORDER BY `formfields`.`order`");
				$fieldQuery->bindValue(':formResponseId',$responseId);
				$fieldQuery->bindValue(':language',userControl::user()->getUserLanguage());
				$fieldQuery->bindValue(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
				$fieldQuery->execute();
				while($fields=$fieldQuery->fetch()){
					$output.='<tr><td><strong>'.htmlentities($fields['fieldname'],ENT_NOQUOTES,'UTF-8').'</strong></td>';
					$output.='<td>'.htmlentities($fields['value'],ENT_NOQUOTES,'UTF-8').'</td></tr>';
				}
				
				$output.='<tr><td><strong>Language</strong></td>';
				$output.='<td>'.$response['language'].'</td></tr>';
				// $output.='<tr><td><strong>IP Address</strong></td>';
				// $output.='<td>'.$response['ip'].'</td></tr>';
				// $output.='<tr><td><strong>User Agent</strong></td>';
				// $output.='<td>'.$response['useragent'].'</td></tr>';
			}
			$output.='</table>';
			return $output;
		}
		catch(Exception $e){
			$errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
			$errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
			$errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
			$errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
			$errorMessage.='Class: forms'."\n";
			$errorMessage.='Method: showResponse'."\n";
			$errorMessage.='Response id: '.$responseId."\n";
			$errorMessage.='Error: '.$e->getMessage()."\n";
			$errorMessage.='Trace: '.$e->getTraceAsString()."\n";
			$system=new systemConfiguration();
			$system->logError($errorMessage,2);
			$system=null;
		}
	}
	
	public function getFormNameFromResponseId($responseId){
		$formNameQuery=DB::getInstance()->prepare("SELECT `forms`.`formname` FROM `forms` INNER JOIN `formresponses` ON `formresponses`.`formid`=`forms`.`id` WHERE `forms`.`system`=0 AND `formresponses`.`id`=:responseId LIMIT 1");
		$formNameQuery->bindValue(':responseId',$responseId);
		$formNameQuery->execute();
		return $formNameQuery->fetchColumn();
	}
	
	public function getFormIdFromResponseId($responseId){
		$formIdQuery=DB::getInstance()->prepare("SELECT `formid` FROM `formresponses` WHERE `id`=:responseId LIMIT 1");
		$formIdQuery->bindValue(':responseId',$responseId);
		$formIdQuery->execute();
		return $formIdQuery->fetchColumn();
	}

}


?>
