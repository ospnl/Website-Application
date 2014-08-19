<?php
require '../content.inc.php';
$strings=new StringControl();
$stringId=$strings->createString();
print $stringId;
?>
