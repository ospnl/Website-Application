<?php

require '../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());
$page->loadTemplate('admin/');

if(isset($_SESSION['username'])){
    $username=$_SESSION['username'];
	$password=$_SESSION['password'];

	$requestedSection = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$postedFormstate = isset($_POST['__FORMSTATE']) ? $_POST['__FORMSTATE'] : '';

	if(!userControl::user()->login($username,$password)){
		header('Location: index.php');
	}
}

/*
 * Page editing section
 * This section provides tools to edit the pages:
 * - A rich text editor (TinyMCE)
 * - Fields to edit page display name and metadata (title, keywords, content)
 *
 */
if($requestedSection=='syspageedit'){
	//Verify that the page the user requested to edit actually exists
	if($page->doesPageExist($_REQUEST['pagename'])){
		$editPage=new Page();
		//Load the page data from the database
		$editPage->loadPage($_REQUEST['pagename'],$_REQUEST['language'],TRUE);
		$page_edit_content=$editPage->getPageContent();
		$page_edit_title=$editPage->getPageTitle();
		$page_edit_description=$editPage->getPageDescription();
		$page_edit_keywords=$editPage->getPageKeywords();

		// Breadcrumb navigation
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		if($editPage->isSystemPage()){
			$page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php?s=syspages">'.$strings->getStringByName('Administration.SystemPages',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		}
		else{
			$page->addPageContent('<div class="breadcrumb_link"><p><a href="pages.php">'.$strings->getStringByName('Administration.ManagePages',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		}
		$page->addPageContent('<div class="clear"></div>');


		$page->addPageContent('<h2>'.$_REQUEST['pagename'].'</h2>');

		$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.js');
		$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.tinymce.js');
		
		//we will be using the TinyMCE editor; add the required JavaScript
		$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/tiny_mce.js');
		$page->addCustomJavaScript('$().ready(function() {'."\n"
		.'$(\'textarea.tinymce\').tinymce({'."\n"
		.'// Location of TinyMCE script'."\n"
		.'script_url : \'<%%$$SITEURL$$%%>js/tiny_mce.js\','."\n"
		.'// General options'."\n"
		.'language : "'.userControl::user()->getUserLanguage().'",'."\n"
		.'entity_encoding : "raw",'."\n"
		.'theme : "advanced",'."\n"
		.'plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups",'."\n"
		.'// Theme options'."\n"
		.'theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,forecolor,backcolor,|,formatselect,fontselect,fontsizeselect",'."\n"
		.'theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,image,code",'."\n".'theme_advanced_toolbar_location : "top",'."\n"
		.'theme_advanced_toolbar_align : "left",'."\n"
		.'relative_urls : false,'."\n"
		.'content_css : "<%%$$SITEURL$$%%>css/styles.css,<%%$$SITEURL$$%%>css/whitebody.css",'."\n"
		.'// Drop lists for link/image/media/template dialogs'."\n"
		.'template_external_list_url : "lists/template_list.js",'."\n"
		.'external_link_list_url : "lists/link_list.js",'."\n"
		.'external_image_list_url : "lists/image_list.js",'."\n"
		.'media_external_list_url : "lists/media_list.js",'."\n"
		.'});'."\n"
		.'});'."\n"
		.'function ajaxSave() {'."\n"
		.'$("#savebuttonone").attr(\'value\', \''.$strings->getStringByName('PageEditor.Saving',userControl::user()->getUserLanguage(),1).'\');'."\n"
		.'$("#savebuttontwo").attr(\'value\', \''.$strings->getStringByName('PageEditor.Saving',userControl::user()->getUserLanguage(),1).'\');'."\n"
		.'var ed = tinyMCE.get(\'pageeditnewcontent\');'."\n"
		.'ed.setProgressState(1);'."\n"
		.'var pagename = $(\'#pagename\').val()'."\n"
		.'var language = $(\'#language\').val()'."\n"
		.'var pagetitle = $(\'#pagetitle\').val()'."\n"
		.'var pagedescription = $(\'#pagedescription\').val()'."\n"
		.'var pagekeywords = $(\'#pagekeywords\').val()'."\n"
		.'$.ajax({'."\n"
		.'url: "index.php",'."\n"
		.'type: "POST",'."\n"
		.'data: ({pagename: pagename, language: language, pagetitle: pagetitle, pagedescription: pagedescription, pagekeywords: pagekeywords, pageeditnewcontent: ed.getContent()}),'."\n"
		.'dataType: \'text\','."\n"
		.'success: function (html) {'."\n"
		.'$("#savebuttonone").attr(\'value\', \''.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'\');'."\n"
		.'$("#savebuttontwo").attr(\'value\', \''.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'\');'."\n"
		.'}'."\n"
		
		.'})'."\n"
		.'ed.setProgressState(0);'."\n"
		.'}');

		$page->addPageContent('<p style="text-align:right;"><input class="submitbutton" onclick="ajaxSave();return false;" value="'.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'" type="button" id="savebuttonone" /></p>');
		
		//Display the form containing the various fields
		//Values MUST be html encoded before they are displayed
		$page->addPageContent('<form id="pageditajax" method="post" action="index.php"><p><textarea id="pageeditnewcontent" name="pageeditnewcontent" rows="30" cols="80" style="width: 100%" class="tinymce">'.htmlentities($page_edit_content,ENT_NOQUOTES,'UTF-8').'</textarea></p>');
		$page->addPageContent('<p style="text-align:right;"><input class="submitbutton" onclick="ajaxSave();return false;" value="'.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'" type="button" id="savebuttontwo" /></p>');
		$page->addPageContent('<p><input type="hidden" id="language" name="language" value="'.$_REQUEST['language'].'" /><input type="hidden" id="pagename" name="pagename" value="'.$_REQUEST['pagename'].'" /></p><h3>Title</h3><p><input type="text" id="pagetitle" name="pagetitle" size="60" value="'.$page_edit_title.'" /></p><h3>Description</h3><p><input type="text" id="pagedescription" name="pagedescription" size="60" value="'.htmlentities($page_edit_description,ENT_NOQUOTES,'UTF-8').'" /></p><h3>Keywords</h3><p><input type="text" id="pagekeywords" name="pagekeywords" size="60" value="'.htmlentities($page_edit_keywords,ENT_NOQUOTES,'UTF-8').'" /></p></form>');
		
	}
	else{
		//If the page does not exist then a message should be displayed
		//TODO - Add string
		//Additionally we could add an error to the event log.
		$page->addPageContent('<p>Page not found!</p>');
	}
	/*
	* End of page editing section                     *
	*/
}


/*
 * Page management page
 * This is the part of the pages section which is displayed to the
 * user.
 * Here is what we will present to the user:
 * 1. Form to add a new page; the posted form is processed higher up in this page
 * 2. List of pages with a language drop down to edit the different versions and links to delete the pages.
 * 3. Readme; a few little guides on how to use
 *
 * Note: System pages such as the homepage 404 pages CANNOT be deleted.
 *
 */
else{
	//Generate the XHTML for the language dropdown and store in a variable
	//This will avoid having to generate the code multiple times
	$language_dropdown=$languageControl->getLanguageDropDown(userControl::user()->getUserLanguage());

	// Breadcrumb navigation
	$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
	$page->addPageContent('<div class="clear"></div>');
	$page->addPageContent('<h2>'.$strings->getStringByName('Administration.SystemPages',userControl::user()->getUserLanguage(),1).'</h2>');

	//Table with list of pages that contains link to edit and delete
	$page->addPageContent('<table>');

	//Ugly part that fetches the data
	$pagesQuery=DB::getInstance()->query("SELECT `pagename`,`locked`,`system` FROM `pages` WHERE `deleted`=0 AND `system`=1 ORDER BY `pagename`");
	while($pages=$pagesQuery->fetch()){
		$page->addPageContent('<form action="pages.php" method="get"><tr><td>'.$pages['pagename'].'</td><td><input type="hidden" value="'.$pages['pagename'].'" name="pagename" /></td><td>'.$language_dropdown.'</td><td><input type="submit" value="'.$strings->getStringByName('Administration.ManagePages.Edit',userControl::user()->getUserLanguage()).'" class="editbutton" /><input type="hidden" name="s" value="pageedit" /></td><td>');
		if($pages['system']==0&&$pages['locked']==0){
				$page->addPageContent('<a href="pages.php?s=pagedelete&pagename='.$pages['pagename'].'" onclick="return confirm(\''.str_replace('%1',$pages['pagename'],addslashes($strings->getStringByName('Administration.ManagePages.ConfirmDelete',userControl::user()->getUserLanguage()))).'\')" class="deletebutton">'.$strings->getStringByName('Administration.ManagePages.Delete',userControl::user()->getUserLanguage()).'</a>');
		}
		$page->addPageContent('</td></tr></form>');
	}

	$page->addPageContent('</table>');

	/*
	 * End of page management section
	 */
}

$page->noGalleries();
$page->display();

?>