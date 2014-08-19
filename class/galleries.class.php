<?php
/*
Pure-Sites 2010
*/

class Gallery
{
    private $galleryId;
    private $galleryName;
    private $replaceCode;
	
	public function __construct($galleryId)
	{
		//Make sure value is an int
		$galleryId= (int) $galleryId;
		//check galleryid is a positive int
		if($galleryId<1)
		{
			throw new Exception('Gallery ID must be superior to 0.');
			return false;
		}
		
		if($this->galleryId=$galleryId){}
		else
		{
			 throw new Exception('Could not set galleryId attribute.');
		}
        
        $galleryInfoQuery=DB::getInstance()->prepare("SELECT `name`,`replacecode` FROM `galleries` WHERE `id`=:galleryId AND `deleted`=0 LIMIT 1");
        $galleryInfoQuery->bindParam(':galleryId',$this->galleryId);
        $galleryInfoQuery->execute();
        while($galleryInfo=$galleryInfoQuery->fetch())
        {
            $this->replaceCode=$galleryInfo['replacecode'];
            $this->galleryName=$galleryInfo['name'];
        }
	}
    
    public function getGalleryName()
    {
        return $this->galleryName;
    }
    
    public function getGalleryId()
    {
        return $this->galleryId;
    }
	
    public function getGalleryReplaceCode()
    {
        return $this->replaceCode;
    }
    
	public function setGalleryType($galleryType)
	{
		$galleryId=$this->galleryId;
		if($galleryId==null)
		{
			throw new Exception('Gallery ID has not been defined. Please use the setGalleryId method.');
			return false;
		}
		
		//Make sure value is an int
		$galleryType= (int) $galleryType;
		//check gallerytype is a positive int
		if($galleryType<1)
		{
			throw new Exception('Gallery Type must be superior to 0.');
			return false;
		}
		
        //DB::getInstance()->query("UPDATE `galleries` SET `type`=$galleryType WHERE `deleted`=0");
		
		//Needless to say a gallery rebuild should be performed!
	}
	
	public function rebuild()
	{
		$galleryId=$this->galleryId;
		$stringControl=new StringControl();
		if($galleryId==null)
		{
			throw new Exception('Gallery ID has not been defined. Please use the setGalleryId method.');
			return false;
		}
		$languageQuery=DB::getInstance()->query("SELECT `twocharacterabbr` AS `id` FROM `languages`");
		while($languages=$languageQuery->fetch())
		{
			$language_id=$languages['id'];
			$imagesQuery=DB::getInstance()->query("SELECT `galleries`.`code` AS `codestringid`,`images`.`imageid`,`images`.`extension`,func_getStringById(`images`.`caption`,'$language_id',0) AS `description`,`gallerytypes`.`precode`,`gallerytypes`.`postcode`,`gallerytypes`.`itemcode` FROM `galleries` INNER JOIN `images` ON `images`.`gallery`=`galleries`.`ID` INNER JOIN `gallerytypes` ON `galleries`.`type`=`gallerytypes`.`id` WHERE `galleries`.`ID`=$galleryId AND `images`.`deleted`=0 ORDER BY `images`.`title`");
			while($gallery_images=$imagesQuery->fetch())
			{
				$preCode=$gallery_images['precode'];
				$postCode=$gallery_images['postcode'];
				$codeStringId=$gallery_images['codestringid'];
				$galleryItemCode=str_replace('<%%$$DESCRIPTION$$%%>',htmlentities($gallery_images['description'],ENT_QUOTES,'UTF-8'),$gallery_images['itemcode']);
				$galleryItemCode=str_replace('<%%$$GALLERYID$$%%>',$galleryId,$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$XSMALL_IMAGE_URL$$%%>',WEBSITE_URL.'upload/100/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$SMALL_IMAGE_URL$$%%>',WEBSITE_URL.'upload/250/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$MEDIUM_IMAGE_URL$$%%>',WEBSITE_URL.'upload/400/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$LARGE_IMAGE_URL$$%%>',WEBSITE_URL.'upload/800/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$XLARGE_IMAGE_URL$$%%>',WEBSITE_URL.'upload/1200/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryItemCode=str_replace('<%%$$ORIGINAL_IMAGE_URL$$%%>',WEBSITE_URL.'upload/original/'.$gallery_images['imageid'].'.'.$gallery_images['extension'],$galleryItemCode);
				$galleryCode.=$galleryItemCode;
			}
			$galleryCode=$preCode.$galleryCode.$postCode;
            $stringControl->setOverrideStringValue($codeStringId, $galleryCode,$language_id);
			$galleryCode='';
		}
		
		return true;
	}

}


?>