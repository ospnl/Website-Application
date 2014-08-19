<?php
/************************************
PURE-SITES ©
LAST MODIFIED: 22/03/12

WIDGETS.PHP

************************************/

// Include all required classes
require '../content.inc.php';

// Start the user session (contains user information)
session_start();

// Create new objects which we will need in the script
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());
$page->loadTemplate('admin/');

// Declare variables
if(isset($_SESSION['username'])){
    $username=$_SESSION['username'];
	$password=$_SESSION['password']; // Encrypted of course

	$requestedSection = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$postedFormstate = isset($_POST['__FORMSTATE']) ? $_POST['__FORMSTATE'] : '';
}

// Check the user is logged in
if(!userControl::user()->login($username,$password)){
	header('Location: index.php');
	exit();
}
// Only allow to proceed if widgets are licensed
elseif(license::getInstance()->getValue('Widget')<1){
	// Not licensed for widgets
	// Display an access denied error page.
	$page->errorPage('403');
	exit;
}
else{
	// User is logged in
	// Here comes the content
	
	// A widget ID has been specified.
	// the user wants to edit the widget
	//
	// Here is what we are going to display to the user on this page:
	// I. Breadcrumb (links back to admin home, and main widget page)
	// II. Widget name
	// III. Rich text editor (TinyMCE) with ajax Save buttons
	// IV. Widget embed code
	// V. Preview of widget; this is also ajaxified; upon saving this will be refreshed.
	if(isset($_REQUEST['wid'])){
		
		// I. Breadcrumb
		// Display the breadcrumb with links back to the admin home and the widgets main page
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="widgets.php">'.$strings->getStringByName('Administration.ManageWidgets',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		
		// Retrieve widget information
		$widgetQuery=DB::getInstance()->prepare('SELECT `widgets`.`id`,`widgets`.`name`,`widgets`.`content`,`embedcodetemplates`.`height`,`embedcodetemplates`.`width`,`embedcodetemplates`.`editorwidth`,`embedcodetemplates`.`editorheight` FROM `widgets` INNER JOIN `embedcodetemplates` ON `widgets`.`template`=`embedcodetemplates`.`id`  WHERE `widgets`.`id`=:widgetId AND `widgets`.`deleted`=0');
		$widgetQuery->bindValue(':widgetId',$_REQUEST['wid']);
		$widgetQuery->execute();
		while($widget=$widgetQuery->fetch()){
			
			// Widget name field
			$page->addPageContent('<h2>##Name</h2>');
			$page->addPageContent('<p><input type="text" id="widgetname" value="'.$widget['name'].'"/>');
			
			// Hidden field with widget id
			// this is how the form processing script (/admin/ajax/updatewidget.php) knows which widget to edit
			$page->addPageContent('<input type="hidden" id="widgetid" value="'.$widget['id'].'"/></p>');
			
			// We will be using ajax
			// therefore we need to load the jQuery toolkit
			$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.js');
			
			// And the TinyMCE jQuery plugin; allowing us to use ajax with the editor
			$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.tinymce.js');
			
			// ... and the TinyMCE javascript code itself
			$page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/tiny_mce.js');
			
			// Obtain the list of icons to enable in the TinyMCE editor
			$systemConfig=new systemConfiguration();
			$tinyMCEIcons=$systemConfig->getTinyMCEIcons();
			$systemConfig=null;
			
			// Alright! time for some fun!
			// The jQuery init script
			// What we need to do in this part:
			// III. a. Load the TinyMCE editor
			// III. b. Define the ajax save function; which is called when a user hits the Save button (duh)
			$page->addCustomJavaScript('$().ready(function() {'."\n"
				// III. a. Load the TinyMCE editor
				.'$(\'textarea.tinymce\').tinymce({'."\n"
				.'// Location of TinyMCE script'."\n"
				.'script_url : \'<%%$$SITEURL$$%%>js/tiny_mce.js\','."\n"
				.'// General options'."\n"
				// TinyMCE is localised - set the editor to use the same language as the rest of the site
				.'language : "'.userControl::user()->getUserLanguage().'",'."\n"
				.'entity_encoding : "raw",'."\n"
				// Dimensions of the editor can be slightly different to the dimensions of the iframe
				.'height : "'.$widget['editorheight'].'",'."\n"
				.'width : "'.$widget['editorwidth'].'",'."\n"
				.'theme : "advanced",'."\n"
				.'plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups",'."\n"
				.'// Theme options'."\n"
				.$tinyMCEIcons
				.'theme_advanced_toolbar_location : "external",'."\n"
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
				.'var ed = tinyMCE.get(\'widgetnewcontent\');'."\n"
				.'ed.setProgressState(1);'."\n"
				.'var widgetid = $(\'#widgetid\').val()'."\n"
				.'var widgetname = $(\'#widgetname\').val()'."\n"
				.'$.ajax({'."\n"
				.'url: "ajax/updatewidget.php",'."\n"
				.'type: "POST",'."\n"
				.'data: ({widgetid: widgetid, widgetname: widgetname, widgetnewcontent: ed.getContent()}),'."\n"
				.'dataType: \'text\','."\n"
				.'success: function (html) {'."\n"
				.'$("#savebuttonone").attr(\'value\', \''.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'\');'."\n"
				.'$("#savebuttontwo").attr(\'value\', \''.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'\');'."\n"
				.'$("div.iframePreview").html(\'<iframe src="'.WEBSITE_URL.'widget/'.$widget['id'].'" width="'.$widget['width'].'" height="'.$widget['height'].'" frameborder="0" style="border: 1px solid #444444;"></iframe>\');'."\n"
				.'}'."\n"
				
				.'})'."\n"
				.'ed.setProgressState(0);'."\n"
			.'}');

			
			$page->addPageContent('<p style="text-align:right;"><input class="submitbutton" onclick="ajaxSave();return false;" value="'.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'" type="button" id="savebuttonone" /></p>');
			
			//Display the form containing the various fields
			//Values MUST be html encoded before they are displayed
			$page->addPageContent('<form id="pageditajax" method="post" action="index.php"><p><textarea id="widgetnewcontent" name="pageeditnewcontent" class="tinymce">'.htmlentities($widget['content'],ENT_NOQUOTES,'UTF-8').'</textarea></p>');
			$page->addPageContent('<p style="text-align:right;"><input class="submitbutton" onclick="ajaxSave();return false;" value="'.$strings->getStringByName('PageEditor.Save',userControl::user()->getUserLanguage(),1).'" type="button" id="savebuttontwo" /></p>');
			
			$page->addPageContent('<h2>##Code</h2>');
			$page->addPageContent('<p><textarea readonly="readonly" cols="50">&lt;iframe src="'.WEBSITE_URL.'widget/'.$widget['id'].'" width="'.$widget['width'].'" height="'.$widget['height'].'" frameborder="0"&gt;&lt;/iframe&gt;</textarea></p>');
			
			$page->addPageContent('<h2>##Preview</h2>');
			$page->addPageContent('<div class="iframePreview"><iframe src="'.WEBSITE_URL.'widget/'.$widget['id'].'" width="'.$widget['width'].'" height="'.$widget['height'].'" frameborder="0" style="border: 1px solid #444444;"></iframe></div>');
			
			
		}
		
	}
	else{

		$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
		$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageWidgets',userControl::user()->getUserLanguage(),1).'</h2>');
		
		$widgetsQuery=DB::getInstance()->query('SELECT `name`,`id` FROM `widgets` WHERE `deleted`=0 ORDER BY `name`');
		while($widgets=$widgetsQuery->fetch()){
			$page->addPageContent('<p><a href="widgets.php?wid='.$widgets['id'].'">'.$widgets['name'].'</a></p>');
		}
		
		
		
		$page->addPageContent('<h2>##Templates</h2>');
		
		$page->addPageContent('<select>');
		$templateQuery=DB::getInstance()->query('SELECT `name` FROM `embedcodetemplates` WHERE `deleted`=0 ORDER BY `name`');
		while($templates=$templateQuery->fetch()){
			$page->addPageContent('<option>'.$templates['name'].'</option>');
		}
		
		$page->addPageContent('</select>');
	}

	
}

// On admin pages we do not show galleries.
// Galleries may be used in the widgets; this will not be affect the rendering of iframes.
// Widgets are processed by /index.php (not /admin/index.php)
$page->noGalleries();

// We are done!
// Show the page to the user
$page->display();

?>
