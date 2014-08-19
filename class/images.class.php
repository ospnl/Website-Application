<?php


//Image management class
class ImageControl
{
	private $imageId;
        private $imageName;
        private $galleryId;
        private $galleryName;
        private $captionId;
		private $imageExtension;
	
	public function __construct($imageId=NULL)
	{
		$this->imageId=$imageId;
        if($this->imageId<>NULL)
        {
            $imageInfoQuery=DB::getInstance()->prepare("SELECT `images`.`title`,`images`.`gallery`,`images`.`caption`,`images`.`extension`,`galleries`.`name` AS `galleryname` FROM `images` INNER JOIN `galleries` ON `images`.`gallery`=`galleries`.`id` WHERE `images`.`imageid`=:imageId AND `images`.`deleted`=0 LIMIT 1");
            $imageInfoQuery->bindParam(':imageId',$imageId);
            $imageInfoQuery->execute();
        	while($imageInfo=$imageInfoQuery->fetch())
        	{
        		$this->imageName=$imageInfo['title'];
				$this->imageExtension=$imageInfo['extension'];
                $this->galleryId=$imageInfo['gallery'];
                $this->captionId=$imageInfo['caption'];
                $this->galleryName=$imageInfo['galleryname'];
        	}
        }
	}

    public function getImageCaption($imageId,$language)
    {
        $imageCaptionQuery=DB::getInstance()->prepare("SELECT func_getStringById(`caption`,:language,0) AS `caption` FROM `images` WHERE `imageid`=:imageId LIMIT 1");
        $imageCaptionQuery->bindParam(':imageId',$imageId);
        $imageCaptionQuery->bindParam(':language',$language);
        $imageCaptionQuery->execute();
        while($imageCaption=$imageCaptionQuery->fetch())
        {
            return $imageCaption['caption'];
        }
    }
    
    public function getImageId()
    {
    	return $this->imageId;
    }
	
	public function getImageExtension()
    {
    	return $this->imageExtension;
    }
    
    public function getImageName()
    {
    	return $this->imageName;
    }
    
    public function getGalleryId()
    {
        return $this->galleryId;
    }
    
    public function getGalleryName()
    {
        return $this->galleryName;
    }
    
    public function deleteImage($imageId)
    {
    	$deleteImageQuery=DB::getInstance()->prepare("UPDATE `images` SET `deleted`=1 WHERE `imageid`=:imageId");
        $deleteImageQuery->bindParam(':imageId',$imageId);
        $deleteImageQuery->execute();
    }
    
    public function removeImage($imageId)
    {
        $deleteImageQuery=DB::getInstance()->prepare("UPDATE `images` SET `gallery`=0 WHERE `imageid`=:imageId");
        $deleteImageQuery->bindParam(':imageId',$imageId);
        $deleteImageQuery->execute();
    }
    
    public function updateImageName($newImageName)
    {
        $updateImageNameQuery=DB::getInstance()->prepare("UPDATE `images` SET `title`=:newImageName WHERE `imageid`=:imageId AND `deleted`=0");
        $updateImageNameQuery->bindParam(':imageId',$this->imageId);
        $updateImageNameQuery->bindParam(':newImageName',$newImageName);
        $updateImageNameQuery->execute();
    }
    
    public function moveGallery($newGalleryId)
    {
        $moveGalleryQuery=DB::getInstance()->prepare("UPDATE `images` SET `gallery`=:newGalleryId WHERE `imageid`=:imageId AND `deleted`=0");
        $moveGalleryQuery->bindParam(':imageId',$this->imageId);
        $moveGalleryQuery->bindParam(':newGalleryId',$newGalleryId);
        $moveGalleryQuery->execute();
        $this->galleryId=$newGalleryId;
    }
    
    public function setCaption($newCaption,$language){
        $updateCaptionQuery=DB::getInstance()->prepare("UPDATE `localisationstrings` SET `".$language."`=:newCaption WHERE `stringid`=:captionId");
        $updateCaptionQuery->bindParam(':newCaption',$newCaption);
        $updateCaptionQuery->bindParam(':captionId',$this->captionId);
        $updateCaptionQuery->execute();
        
        $updateCaptionCacheQuery=DB::getInstance()->prepare("UPDATE `stringcache` SET `string`=:newCaption WHERE `stringid`=:captionId AND `language`=:language");
        $updateCaptionCacheQuery->bindParam(':newCaption',$newCaption);
		$updateCaptionCacheQuery->bindParam(':language',$language);
        $updateCaptionCacheQuery->bindParam(':captionId',$this->captionId);
        $updateCaptionCacheQuery->execute();

    }

}


//IMAGE RESIZE CLASS
class ImgResizer {
	private $originalFile = '';
	public function __construct($originalFile = '') {
		$this -> originalFile = $originalFile;
	}
	public function resizeFromWidth($newWidth, $targetFile) {
		if (empty($newWidth) || empty($targetFile)) {
			return false;
		}
		$src = imagecreatefromjpeg($this -> originalFile);
		list($width, $height) = getimagesize($this -> originalFile);
		$newHeight = ($height / $width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		if (file_exists($targetFile)) {
			unlink($targetFile);
		}
		imagejpeg($tmp, $targetFile, 100); // 85 is my choice, make it between 0 – 100 for output image quality with 100 being the most luxurious
	}
	public function resize($newHeight, $targetFile) {
		if (empty($newHeight) || empty($targetFile)) {
			return false;
		}
		$src = imagecreatefromjpeg($this -> originalFile);
		list($width, $height) = getimagesize($this -> originalFile);
		$newWidth = $newHeight / ($height / $width);
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		if (file_exists($targetFile)) {
			unlink($targetFile);
		}
		imagejpeg($tmp, $targetFile, 100); // 85 is my choice, make it between 0 – 100 for output image quality with 100 being the most luxurious
	}
}


//IMAGE RESIZE CLASS
class ImgResizer2 {
	private $originalFile;
	public function __construct($originalFile) {
		$this -> originalFile = $originalFile;
	}
	public function resize($newSize, $targetFile) {
		//exec(IMAGE_MAGICK_PATH.'convert '.$this->originalFile.' -geometry x'.$newSize.' '.$targetFile);
		exec(IMAGE_MAGICK_PATH.'convert '.$this->originalFile.' -resize '.$newSize.'x'.$newSize.'\> '.$targetFile);
	}
}

?>
