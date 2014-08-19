<?php

require '../../content.inc.php';
session_start();
if(isset($_POST['sort1']))
{
    $_SESSION['formOrder']=$_POST['sort1'];
}


if(isset($_POST['commitMenuChanges'])){
	/*
    DB::getInstance()->query("DELETE FROM `menu`");
    $menuArray=explode("&",$_SESSION['menuEditorOrder']);
    $order=1;
    foreach($menuArray as $value)
    {
            $menu_item=explode('[]=',$value);
            $menuItem=$menu_item[1];
            $insertMenuItemQuery=DB::getInstance()->prepare("INSERT INTO `menu`(`page`,`text`,`order`) SELECT `pagename`,:menuItem,:order FROM `pages` WHERE `name`=:menuItem");
            $insertMenuItemQuery->bindParam(':menuItem',$menuItem);
            $insertMenuItemQuery->bindParam(':order',$order);
            $insertMenuItemQuery->execute();
            $order=$order+1;
    }
    
    $sitecache = new Cache();
    $sitecache->flush();
    header('Location: index.php?s=menueditor');
	*/
}
print_r($_POST);
// echo '<form action="ajax/arrangeformfields.php" method="post"><input type="submit" name="commitMenuChanges" value="Save" /></form>';

?>