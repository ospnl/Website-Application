<?php

require('content.inc.php');

$imageId=$_GET['imageid'];
$extension=$_GET['extension'];
$size=$_GET['size'];
$serverName=$_SERVER['SERVER_NAME'];

$fileLocation=UPLOAD_DIR.$size.'/'.$imageId.'.'.$extension;

if(file_exists($fileLocation)){
	header("Content-type: " . image_type_to_mime_type(exif_imagetype($fileLocation)));
	header("Content-Length: ".filesize($fileLocation));
	header("Accept-Ranges: bytes");
	header("Last-Modified: ".date('D, d M Y H:i:s', filemtime($fileLocation)).' UTC');

	$fh=fopen($fileLocation,'r');
	$imageData=fread($fh,filesize($fileLocation));
	fclose($fh);
	echo $imageData;
}
else{
	echo 'File does not exist: '.$fileLocation;
}

?>
