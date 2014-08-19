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
	if(isset($_POST['widgetnewcontent'])){
		/*
		* Page edit form handler
		* This part makes the changes in the database for the edited pages
		* Note that the strings stored in the database are ALWAYS html unencoded.
		*/
		
		$updateWidget=DB::getInstance()->prepare('UPDATE `widgets` SET `content`=:content WHERE `id`=:widgetId');
		$updateWidget->bindParam(':content',$_POST['widgetnewcontent']);
		$updateWidget->bindParam(':widgetId',$_POST['widgetid']);
		$updateWidget->execute();

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
