<?php
/*
This page does not output html

*/
require '../../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());

if(!userControl::user()->login($username,$password)){
	if(isset($_POST['pageeditnewcontent'])){
		/*
		* Page edit form handler
		* This part makes the changes in the database for the edited pages
		* Note that the strings stored in the database are ALWAYS html unencoded.
		*/

		$pageEdit=new Page();
		$pageEdit->loadTemplate($_POST['pagename']);
		$pageEdit->updatePageTitle($_POST['pagename'],$_POST['language'],$_POST['pagetitle']);
		$pageEdit->updatePageDescription($_POST['pagename'],$_POST['language'],$_POST['pagedescription']);
		$pageEdit->updatePageKeywords($_POST['pagename'],$_POST['language'],$_POST['pagekeywords']);
		$pageEdit->updatePageContent($_POST['pagename'],$_POST['language'],$_POST['pageeditnewcontent']);
		$pageEdit->updatePageHyperlink($_POST['pagename'],$_POST['language'],$_POST['pagehyperlink']);
		$pageEdit->updatePageMenuName($_POST['pagename'],$_POST['language'],$_POST['pagemenuname']);
		
		if($_POST['external']==1){
			$pageEdit->updatePageExternal($_POST['pagename'],1);
			$pageEdit->updatePageNewWindow($_POST['pagename'],0);
		}
		elseif($_POST['external']==2){
			$pageEdit->updatePageExternal($_POST['pagename'],1);
			$pageEdit->updatePageNewWindow($_POST['pagename'],1);
		}
		else{
			$pageEdit->updatePageExternal($_POST['pagename'],0);
			$pageEdit->updatePageNewWindow($_POST['pagename'],0);
		}
		$pageEdit=null;

		if($page->doesSysPageExist($_POST['pagename'])){
			$page->buildErrorPage($_POST['pagename']);
		}
		//Remove the old pages from the cache else the changes will not appear.
		$sitecache = new Cache();
		$sitecache->flush();
		$sitecache=null;

		//Output success message
		print('Saved');
	}
	else{
		// Log error
	}
}
else{
	print('Error user not logged in.');
}



?>