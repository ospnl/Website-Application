<?php

require '../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());
$page->loadTemplate();

if(isset($_SESSION['username'])){
    $username=$_SESSION['username'];
	$password=$_SESSION['password'];

	$requestedSection = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$postedFormstate = isset($_POST['__FORMSTATE']) ? $_POST['__FORMSTATE'] : '';
}

// Check the user is logged in
if(!userControl::user()->login($username,$password)){
	header('Location: index.php');
	exit();
}
// Only allow to proceed if forms are licensed
elseif(license::getInstance()->getValue('Forms')<1){
	$page->errorPage('403');
	exit;
}
else{
	// User is logged in
	// Here comes the content
	$formId=null;
	$formLanguage=null;

	// User is trying to edit a form
	if(isset($_REQUEST['edit'])){
		$formId=$_REQUEST['edit'];
		$formLanguage=$_REQUEST['language'];
		
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="forms.php">'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		
		$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.js');
        $page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery-ui-1.8.18.custom.min.js');
		$page->addCustomCssLink('<%%$$SITEURL$$%%>css/jquery-ui-1.8.18.custom.css');
		
		$page->addCustomCss('#sortable { list-style-type: none; margin: 0; padding: 10px 0 10px 0; width: 60%; }
                  	#sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 13px; height: 16px; color:#000000; }
                  	#sortable li span { position: absolute; margin-left: -1.3em; }');
		$page->addCustomJavaScript('$(function() {
			$("#sortable").sortable({
				placeholder: \'ui-state-highlight\',
				connectWith: \'.connectedSortable\',
				update : function ()
				{
					$.ajax(
					{
						type: "POST",
						url: "update.php",
						data:
						{
							sort1:$("#sortable").sortable(\'serialize\'),
						},
						success: function(theResponse)
						{
							$(\'.success\').html(theResponse);
						}
					});
				}
			}).disableSelection();
		});');
		
		$page->addPageContent($form->getFormFieldList($formId,$formLanguage));
	}
	
	elseif(isset($_REQUEST['editfield'])){
		$fieldId=$_REQUEST['editfield'];
		$formLanguage=$_REQUEST['language'];
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="forms.php">'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		$page->addPageContent('<h2>##Showing field</h2>');
		
		$page->addPageContent($form->getFormField($fieldId,$formLanguage));
		
		$page->addPageContent('<p>##Important note, the items denoted with a * are global settings.</p>');
		
	}
	
	elseif(isset($_REQUEST['view'])){
		$formId=$_REQUEST['view'];
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="forms.php">'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		$page->addPageContent('<h2>Showing form records</h2>');
		$page->addPageContent($form->showRecords($formId));
	}
	
	elseif(isset($_REQUEST['viewresponse'])){
		$responseId=$_REQUEST['viewresponse'];
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="forms.php">'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="forms.php?view='.$form->getFormIdFromResponseId($responseId).'">'.$form->getFormNameFromResponseId($responseId).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		$page->addPageContent('<h2>Form data</h2>');
		$page->addPageContent($form->showResponse($responseId));
	}
	
	elseif(isset($_POST['formeditsave'])){
		$formId=$_POST['formid'];
		$formLanguage=$_POST['formlanguage'];
		$fieldQuery=DB::getInstance()->prepare("SELECT `id`,`fieldname`,`regexerror`,`minlengtherror`,`maxlengtherror` FROM `formfields` WHERE `formid`=:formId");
		$fieldQuery->bindValue(':formId',$formId);
		$fieldQuery->execute();
		while($field=$fieldQuery->fetch()){
			$fieldType=$_POST[$field['id'].'_type'];
			$fieldName=$_POST[$field['id'].'_name'];
			$fieldDisplayAs=$_POST[$field['id'].'_displayas'];
			$fieldRequired=$_POST[$field['id'].'_required'];
			$fieldActive=$_POST[$field['id'].'_active'];
			$fieldRegex=$_POST[$field['id'].'_regex'];
			$fieldMinLength=$_POST[$field['id'].'_minlength'];
			$fieldMaxLength=$_POST[$field['id'].'_maxlength'];
			$fieldRegexError=$_POST[$field['id'].'_regexerror'];
			$fieldMinLengthError=$_POST[$field['id'].'_minlengtherror'];
			$fieldMaxLengthError=$_POST[$field['id'].'_maxlengtherror'];
			
			// Update type
			// First check the type is valid
			if($fieldType=='text'||$fieldType=='submit'){
				$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `type`=:fieldType WHERE `id`=:fieldId");
				$fieldUpdateQuery->bindValue(':fieldType',$fieldType);
				$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
				$fieldUpdateQuery->execute();
			}
			
			// Update name
			// First check the name is at least 2 characters long
			if(strlen(trim($fieldName))>1){
				$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `name`=:fieldName WHERE `id`=:fieldId");
				$fieldUpdateQuery->bindValue(':fieldName',$fieldName);
				$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
				$fieldUpdateQuery->execute();
			}
			
			// Update active flag
			if($fieldActive=='true'){
				$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `active`=1 WHERE `id`=:fieldId");
				$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
				$fieldUpdateQuery->execute();
			}
			else{
				$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `active`=0 WHERE `id`=:fieldId");
				$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
				$fieldUpdateQuery->execute();
			}
			
			// Update min length
			// At a later stage we will do some fancy validation; at the moment we will use PDO data types
			$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `minlength`=:fieldMinLength WHERE `id`=:fieldId");
			$fieldUpdateQuery->bindValue(':fieldMinLength',$fieldMinLength,PDO::PARAM_INT);
			$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
			$fieldUpdateQuery->execute();
			
			// Update max length
			// At a later stage we will do some fancy validation; at the moment we will use PDO data types
			$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `maxlength`=:fieldMaxLength WHERE `id`=:fieldId");
			$fieldUpdateQuery->bindValue(':fieldMaxLength',$fieldMaxLength,PDO::PARAM_INT);
			$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
			$fieldUpdateQuery->execute();
			
			// Update regex
			$fieldUpdateQuery=DB::getInstance()->prepare("UPDATE `formfields` SET `regex`=:fieldRegex WHERE `id`=:fieldId");
			$fieldUpdateQuery->bindValue(':fieldRegex',$fieldRegex);
			$fieldUpdateQuery->bindValue(':fieldId',$field['id']);
			$fieldUpdateQuery->execute();
			
			// Update display as
			$strings->setOverrideStringValue($field['fieldname'],$fieldDisplayAs,$formLanguage);
			
			// Update regex error
			$strings->setOverrideStringValue($field['regexerror'],$fieldRegexError,$formLanguage);
			
			// Update min length error
			$strings->setOverrideStringValue($field['minlengtherror'],$fieldMinLengthError,$formLanguage);
			
			// Update max length error
			$strings->setOverrideStringValue($field['maxlengtherror'],$fieldMaxLengthError,$formLanguage);
			
			// Clear out page cache
			$cache=new Cache();
            $cache->flush();
            $cache=null;
		}
		
	}
	
	else{
		$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage(),1).'</h2>');
		
		$page->addPageContent($form->getFormList());
	}
	
}



$page->noGalleries();
$page->display();

?>
