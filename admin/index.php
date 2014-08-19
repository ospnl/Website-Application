<?php
/*
Oliver Smith

This page handles all the whole front end
*/

error_log("* Loading page at " .date("r"));

if(isset($page)){
	error_log('$page is already set exiting at ' .date("r"));
	exit();
}
else{
	error_log('$page is not set.');
}

require '../content.inc.php';

session_start();

$form=new forms();
$strings=new StringControl();
$page=new Page();
$languageControl=new lang();
$page->setPageLanguage(userControl::user()->getUserLanguage());
$page->loadTemplate('admin/');

if(!isset($_REQUEST['l'])&&USE_NEWURL_STYLE){
	header('Location: '.WEBSITE_URL.userControl::user()->getUserLanguage().'/admin/');
}

if(isset($_POST['login'])){
	$_SESSION['username']=$_POST['username'];
	$_SESSION['password']=$_POST['password'];
	// header('Location: '.WEBSITE_URL.userControl::user()->getUserLanguage().'/admin/index.php');
}

if(isset($_SESSION['username']))
{
    $username=$_SESSION['username'];
	$password=$_SESSION['password'];

        $requestedSection = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
        $postedFormstate = isset($_POST['__FORMSTATE']) ? $_POST['__FORMSTATE'] : '';

		error_log("Login at " .date("r"));
	if(userControl::user()->login($username,$password))
	{
		
			if($requestedSection=='eventlog'||$requestedSection=='cleareventlog')
		{
			try
			{
				$system=new systemConfiguration();
                if($requestedSection=='cleareventlog'){
                    $system->clearEventLog();
                }
                $eventlog=$system->getEventLog();
				// Breadcrumb navigation
				$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
				$page->addPageContent('<div class="clear"></div>');
				$page->addPageContent('<h2>'.$strings->getStringByName('Administration.EventLog',userControl::user()->getUserLanguage(),1).'</h2>');
				$page->addPageContent($eventlog['pagination']);
				$page->addPageContent($eventlog['eventlog']);
				$page->addPageContent($eventlog['pagination']);
                $page->addPageContent('<p><a href="index.php?s=cleareventlog">'.$strings->getStringByName('EventLog.ClearEventLog',userControl::user()->getUserLanguage(),1).'</a></p>');
			}
	      catch(Exception $e){
			$errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
			$errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
			$errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
			$errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
			$errorMessage.='Error: '.$e->getMessage()."\n";
			$errorMessage.='Trace: '.$e->getTraceAsString()."\n";

			$system->logError($errorMessage,3);
			header('Location: '.WEBSITE_URL.'500_'.userControl::user()->getUserLanguage().'.html');
			exit;
		}
	}
		
		elseif($requestedSection=='menueditor'||isset($_POST['newmenuitemsubmit'])||isset($_REQUEST['deletemenuitem']))
		{
                    /*
                     * Menu editor section
                     * This section allows the editing of the menu.
                     * Which is displayed on the website.
                     * Although this has been AJAXified this part needs serious revision.
                     * the updating of the menu is handled by update.php
                     *
                     */

			// Breadcrumb navigation
			$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
			$page->addPageContent('<div class="clear"></div>');
                    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageMenu',userControl::user()->getUserLanguage()).'</h2>');


                    //Display a list of links currently in the site menu
                    $page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageMenu.ActiveMenuLinks',userControl::user()->getUserLanguage()).'</h3>');

                    //Nothing extraordinary here
                    $page->addPageContent('<ul id="sortable1" class="connectedSortable">');
                    $menuQuery=DB::getInstance()->prepare("SELECT func_GetStringById(`text`,:userLanguage,:showstringid) AS `name`,`text` AS `id` FROM `menu` INNER JOIN `pages` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 AND `menu`.`deleted`=0");
                    $menuQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
                    $menuQuery->bindParam(':userLanguage',userControl::user()->getUserLanguage());
                    $menuQuery->execute();

                    while($menu_array=$menuQuery->fetch())
                    {
                            $page->addPageContent('<li id="menu_'.$menu_array['id'].'" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'.$menu_array['name'].'</li>');
                    }
                    $page->addPageContent('</ul>');


                    //Display a list of pages which are not present in the menu
                    $page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageMenu.Pages',userControl::user()->getUserLanguage()).'</h3>');
                    $page->addPageContent('<ul id="sortable2" class="connectedSortable">');
                    //BUILD88 - Localise
                    $unlinkedPagesQuery=DB::getInstance()->prepare("SELECT func_GetStringById(`pages`.`name`,:language,:showstringid) AS `name`,`pages`.`name` AS `id` FROM `pages` LEFT JOIN `menu` ON `menu`.`page`=`pages`.`pagename` WHERE `pages`.`deleted`=0 AND `pages`.`system`=0 AND `menu`.`deleted` IS NULL");
                    $unlinkedPagesQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
					$unlinkedPagesQuery->bindValue(':language',userControl::user()->getUserLanguage());
                    $unlinkedPagesQuery->execute();

                    while($unlinked_pages=$unlinkedPagesQuery->fetch())
                    {
                            $page->addPageContent('<li id="page_'.$unlinked_pages['id'].'" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'.$unlinked_pages['name'].'</li>');
                    }
                    $page->addPageContent('</ul>');

                    //This is the container that the AJAX content is inserted into
                    $page->addPageContent('<div class="success"></div>');

                    //Add the jQuery JavaScript required on page load

                    $page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery.js');
                    $page->addCustomJavaScriptLink('<%%$$SITEURL$$%%>js/jquery-ui-1.8.18.custom.min.js');
                    $page->addCustomCssLink('<%%$$SITEURL$$%%>css/jquery-ui-1.8.18.custom.css');
                    $page->addCustomCss('#sortable1,#sortable2 { list-style-type: none; margin: 0; padding: 10px 0 10px 0; width: 60%; }
                  	#sortable1 li,#sortable2 li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 13px; height: 16px; color:#000000; }
                  	#sortable1 li span,#sortable2 li span { position: absolute; margin-left: -1.3em; }');
                    $page->addCustomJavaScript('$(function() {
                  		$("#sortable1, #sortable2").sortable({
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
                  						sort1:$("#sortable1").sortable(\'serialize\'),
                  						sort2:$("#sortable2").sortable(\'serialize\')
                  					},
                  					success: function(theResponse)
                  					{
                  						$(\'.success\').html(theResponse);
                  					}
                  				});
                  			}
                  		}).disableSelection();
                  	});');
		}
		elseif(isset($_POST['pageeditnewcontent']))
		{
		      /*
		       * Page edit form handler
		       * This part makes the changes in the database for the edited pages
		       * Note that the strings stored in the database are ALWAYS html unencoded.
		       * BUILD87 - Move up below page editing section
		       * This part will drastically change when the editor becomes AJAXified
		       */
		       $pageEdit=new Page();
		       $pageEdit->updatePageTitle($_POST['pagename'],$_POST['language'],$_POST['pagetitle']);
		       $pageEdit->updatePageDescription($_POST['pagename'],$_POST['language'],$_POST['pagedescription']);
		       $pageEdit->updatePageKeywords($_POST['pagename'],$_POST['language'],$_POST['pagekeywords']);
		       $pageEdit->updatePageContent($_POST['pagename'],$_POST['language'],$_POST['pageeditnewcontent']);
		       $pageEdit=null;

                       // Clear string cache
                       // $strings->buildStringCache();

		      //Remove the old pages from the cache else the changes will not appear.
		      $sitecache = new Cache();
		      $sitecache->flush();
		      $sitecache=null;

		      //Redirect the user back to the editing page
		      //header('Location: index.php?s=pageedit&pagename='.$_POST['pagename'].'&language='.$_POST['language'].'&saved=1');
		}
		elseif($requestedSection=='images'||isset($_POST['imagefileupload'])||isset($_POST['moveimages']))
		{
			/*
       * Images section
       * This section allows users to:
       * - Upload images (Images are resized when uploaded)
       * - Create galleries
       * - Delete galleries
       * - Move images into galleries
       * - Delete images
       * - Rename images
       * - Change image captions
       *
       * Galleries enable users to quickly create a slideshow on a page.
       * Each gallery has a unique code (in the following form: <$$%%GALLLERY1%%$$>) which when inserted into a page
       * is substituted with XHTML,CSS and JavaScript code which is stored as a string.
       * NOTE: The XHTML,CSS and JavaScript code for performance reasons is NOT generated on the fly when building the page
       * it is generated when changes are made to the gallery.
       *
       */

			if(isset($_POST['moveimages']))
			{
        //Move image action
        //When moving an image from one gallery to another, both the "source" gallery and
        //the "destination" gallery codes will need to be regenerated.

        //Destination gallery ID
        $new_gallery_id= (int) $_POST['new_gallery'];

        //Cycle through the unclassified images and move the ones which have been checked
        $unclassifiedImagesQuery=DB::getInstance()->query("SELECT `imageid` FROM `images` WHERE `deleted`=0 AND `gallery`=0");
        while($unclassifiedImages=$unclassifiedImagesQuery->fetch())
        {
            if($_POST['image_'.$unclassifiedImages['imageid']]==true)
            {
                $imageid_to_move=$unclassifiedImages['imageid'];
                $imageMoveQuery=DB::getInstance()->prepare("UPDATE `images` SET `gallery`=:newGalleryId WHERE `imageid`=:imageIdToMove");
                $imageMoveQuery->bindParam(':imageIdToMove',$imageid_to_move);
                $imageMoveQuery->bindParam(':newGalleryId',$new_gallery_id);
                $imageMoveQuery->execute();
            }
        }

        //Rebuild the destination gallery
        try
        {
            $gallery = new Gallery($new_gallery_id);
            $gallery->rebuild();
            $gallery=null;
        }
        catch (Exception $e)
        {
            //TODO - To be changed to the new event log type
            $error='---AUTOMATICALLY GENERATED ERROR MESSAGE---'."<br />";
            $error.='/admin/create.php reported the following error message when attempting to use the class \'Gallery\':'."<br />";
            $error.=$e->getMessage();
            header('Location: '.WEBSITE_URL.'500_en.html');
        }
        // End of move images action

			}

      //The images section content

	// Breadcrumb navigation
	$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
	$page->addPageContent('<div class="clear"></div>');
      $page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageImages',userControl::user()->getUserLanguage(),1).'</h2>');

      //Image upload form
      //BUILD87 - Add string
			$page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageImages.UploadTitle',userControl::user()->getUserLanguage(),1).'</h3>');

                        //As the form contains an image upload the enctype must be set to multipart/form-data
			$page->addPageContent('<form enctype="multipart/form-data" action="index.php" method="POST">');
			$page->addPageContent('<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />'); //It's actually 20MB just to be safe
			$page->addPageContent('<p>'.$strings->getStringByName('Administration.ManageImages.UploadPrompt',userControl::user()->getUserLanguage(),1).'</p><input name="uploadedimage" type="file" />');
			$page->addPageContent('<input name="imagefileupload" type="submit" value="'.$strings->getStringByName('Administration.ManageImages.UploadButton',userControl::user()->getUserLanguage(),1).'" />');
			$page->addPageContent('</form>');

			if(isset($_POST['imagefileupload']))
			{
          //File upload handler
          //The file is first uploaded to a temp directory (handled by apache)
          //then moved into the original subdirectory of uploaded images; it is also given a unique filename
          //it is then finally resized into the different thumbnail sizes
          $images_path = UPLOAD_DIR;
          $image_id= md5(date("U").basename($_FILES['uploadedimage']['name']));
          

          $fileName=$_FILES['uploadedimage']['name'];
		  $fileExtension=strtolower(end(explode(".", $fileName)));
		  
		  $target_path = $images_path .'original/'.$image_id.'.'.$fileExtension;
		  
		  $allowedExtensions = array('jpg','jpeg','gif','png');
		  
		  
			
		if(!in_array($fileExtension, $allowedExtensions)){
			$page->addPageContent($strings->getStringByName('Administration.ManageImages.FileUploadUnsupportedFormat',userControl::user()->getUserLanguage(),1));
		  }
          //Move the image from the apache temporary directory to the correct folder
          elseif(move_uploaded_file($_FILES['uploadedimage']['tmp_name'], $target_path))
          {
              //Instantiate the image resizer; and resize the images
              $work = new ImgResizer2($target_path);
              $work -> resize(100, $images_path .'100/'.$image_id.'.'.$fileExtension);
              $work -> resize(250, $images_path .'250/'.$image_id.'.'.$fileExtension);
              $work -> resize(400, $images_path .'400/'.$image_id.'.'.$fileExtension);
              $work -> resize(800, $images_path .'800/'.$image_id.'.'.$fileExtension);
              $work -> resize(1200, $images_path .'1200/'.$image_id.'.'.$fileExtension);

              //Error handling with imaging functions seems to be a little fragile in PHP
              //We just check if the largest thumbnail was created; if memory issues arise it will be with this one.
              if(!file_exists($images_path .'1200/'.$image_id.'.'.$fileExtension))
              {
                      $page->addPageContent($strings->getStringByName('Administration.ManageImages.FileUploadError',userControl::user()->getUserLanguage(),1));
              }
              else
              {
                  //Add a string for image captions
                  $caption_string_id=$strings->createString();

                  //Associate the string id for the caption to the image in the database
                  //BUILD87 - Convert to PDO
                 $insertImageQuery=DB::getInstance()->prepare("INSERT INTO `images`(`imageid`,`title`,`caption`,`extension`) VALUES(:imageId,:fileName,:captionStringId,:extension)");
                 $insertImageQuery->bindParam(':imageId',$image_id);
                 $insertImageQuery->bindParam(':fileName',$fileName);
                 $insertImageQuery->bindParam(':captionStringId',$caption_string_id);
				 $insertImageQuery->bindParam(':extension',$fileExtension);
                 $insertImageQuery->execute();
                  
				  $page->addPageContent('<table><tr><td><img src="'.WEBSITE_URL.'upload/100/'.$image_id.'.'.$fileExtension.'" alt="upload"></td><td>'.str_replace('&lt;%%$$1$$%%&gt;',basename( $_FILES['uploadedimage']['name']),$strings->getStringByName('Administration.ManageImages.FileUploadSuccess',userControl::user()->getUserLanguage(),1)).'</td></tr></table>'); //BUILD87 - Add string
				  
              }
          }
          else
          {
              $page->addPageContent($strings->getStringByName('Administration.ManageImages.FileUploadError',userControl::user()->getUserLanguage(),1)); //BUILD97 - Use same string as above
          }
			}

      //Create a list of images that are not associated with any gallery (unsorted)
      //Title
      //BUILD88 - Add string
			$page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageImages.Unsorted',userControl::user()->getUserLanguage(),1).'</h3><form action="index.php" method="post"><table>');

			$imageLibraryQuery=DB::getInstance()->query("SELECT * FROM `images` WHERE `deleted`=0 AND `gallery`=0 ORDER BY `title`");
			while($imageLibrary=$imageLibraryQuery->fetch())
			{
				$page->addPageContent('<tr><td><input type="checkbox" name="image_'.$imageLibrary['imageid'].'" /></td><td>');
				$page->addPageContent('<img src="'.WEBSITE_URL.'upload/100/'.$imageLibrary['imageid'].'.'.$imageLibrary['extension'].'" alt="'.$imageLibrary['title'].'" /></td><td>'.$imageLibrary['title']);
				$page->addPageContent('</td></tr>');
			}

      //Dropdown to select galeries
			$page->addPageContent('</table><select name="new_gallery"><option value="0">'.$strings->getStringByName('Administration.ManageImages.MoveIntoGallery',userControl::user()->getUserLanguage(),1).'</option>');

            $imageGalleryQuery=DB::getInstance()->query("SELECT * FROM `galleries` WHERE `deleted`=0 ORDER BY `name`");
			while($imageGallery=$imageGalleryQuery->fetch())
			{
				$page->addPageContent('<option value="'.$imageGallery['ID'].'">'.htmlentities($imageGallery['name'],ENT_QUOTES,'UTF-8').'</option>');
			}

      //Form submit button
			$page->addPageContent('<input type="submit" name="moveimages" value="'.$strings->getStringByName('Administration.ManageImages.MoveIntoGallerySubmit',userControl::user()->getUserLanguage(),1).'" /></form>');
      /*
       * End of images section
       */

		}
		elseif($requestedSection=='galleries'||isset($_POST['addgallerysubmit']))
		{
			if(isset($_POST['newgallery']))
			{
				  //Create a new gallery action
				  //This part is straight forward
				  //just a case of creating the strings and the gallery itself
				  //and redirecting to the same page

				  $newgalleryname=$_POST['newgallery'];

				  //Add the required strings
				  $code_stringid=$strings->createString();
				  $description_stringid=$strings->createString();
				  $code_stringid=$strings->createString();

				  //Insert the gallery
				  $insertGalleryQuery=DB::getInstance()->prepare("INSERT INTO `galleries`(`name`,`code`,`description`) VALUES(:newGalleryName,:codeStringId,:descriptionStringId)");
				  $insertGalleryQuery->bindParam(':newGalleryName',$newgalleryname);
				  $insertGalleryQuery->bindParam(':codeStringId',$code_stringid);
				  $insertGalleryQuery->bindParam(':descriptionStringId',$description_stringid);
				  $insertGalleryQuery->execute();
				  $gallery_id=DB::getInstance()->lastInsertId();


				  $updateGalleryCodeQuery=DB::getInstance()->prepare("UPDATE `galleries` SET `replacecode`=:replaceCode,`replacecode2`=:replaceCode2 WHERE ID=:galleryId");
                                  $updateGalleryCodeQuery->bindValue(':galleryId',$gallery_id);
				  $updateGalleryCodeQuery->bindValue(':replaceCode','<%%$$GALLERY'.$gallery_id.'$$%%>');
				  $updateGalleryCodeQuery->bindValue(':replaceCode2','&lt;%%$$GALLERY'.$gallery_id.'$$%%&gt;');
				  $updateGalleryCodeQuery->execute();
			}

			// Breadcrumb navigation
			$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
			$page->addPageContent('<div class="clear"></div>');
			$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageGalleries',userControl::user()->getUserLanguage(),1).'</h2><table>');

			//Form for adding gallery
			//BUILD92 - This can be handled by the forms class
			$page->addPageContent('<form name="addgalleryform" action="index.php" method="post"><p>'.$strings->getStringByName('Galleries.AddLabel',userControl::user()->getUserLanguage(),1).'<input type="text" name="newgallery" style="margin-right:10px;" /><input type="submit" name="addgallerysubmit" value="'.$strings->getStringByName('Galleries.AddButton',userControl::user()->getUserLanguage(),1).'" class="submitbutton" /></p></form>');

			//Create a list of galleries with links to manage
			$galleryQuery=DB::getInstance()->query("SELECT `galleries`.`name`,`galleries`.`ID` FROM `galleries` WHERE `galleries`.`deleted`=0 ORDER BY `galleries`.`name`");
				while($galleries=$galleryQuery->fetch())
				{
					$page->addPageContent('<tr><td></td><td><a href="index.php?galleryid='.$galleries['ID'].'">'.htmlentities($galleries['name'],ENT_QUOTES,'UTF-8').'</a></td></tr>');
				}
			$page->addPageContent('</table>');
		}
		elseif($requestedSection=='languages'||isset($_REQUEST['enablelang'])||isset($_REQUEST['disablelang']))
		{
		/*
		* Language section
		* Not much in this section at the moment.
		* The objective here will be enable users to:
		* - Enable/Disable language
		* - Preferences such as language detection priorities
		* - Export/Import strings
		*
		* NOTE: It is by design that the language cannot be added by a user
		* They are chargeable features of the product
		*/

		// Breadcrumb navigation
		//$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');

		// Title
		$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageLanguages',userControl::user()->getUserLanguage(),1).'</h2>');

		// User has requested to enable a language
		if(isset($_REQUEST['enablelang']))
		{
			// Determine how many languages pages are in use
			$activeLanguagesCountQuery=DB::getInstance()->query("SELECT `id` FROM `languages` WHERE `deleted`=0 AND `active`=1");
			$activeLanguagesCount=$activeLanguagesCountQuery->rowCount();
			// Check the license
			if($activeLanguagesCount>=license::getInstance()->getValue('Languages'))
			{
				$page->addPageContent('<p class="formError"><span>Check license</span></p>');
			}
			else
			{
                            $updateLanguageQuery=DB::getInstance()->prepare("UPDATE `languages` SET `active`=1 WHERE `id`=:languageId");
				$updateLanguageQuery->bindParam(':languageId',$_REQUEST['enablelang']);
				$updateLanguageQuery->execute();
				// Blank file names must be created for each enabled language
				$activeLanguagesQuery=DB::getInstance()->query('SELECT `twocharacterabbr` FROM `languages` WHERE `active`=1');
				while($activeLanguages=$activeLanguagesQuery->fetch())
				{
					$langFileHandle=fopen(SETTINGS_DIRECTORY.'lang/'.$activeLanguages['twocharacterabbr'], 'w');
					fwrite($langFileHandle ,'');
					fclose($langFileHandle);
				}
			}
		}
		elseif(isset($_REQUEST['disablelang']))
		{
			$updateLanguageQuery=DB::getInstance()->prepare("UPDATE `languages` SET `active`=0 WHERE `id`=:languageId");
			$updateLanguageQuery->bindParam(':languageId',$_REQUEST['disablelang']);
			$updateLanguageQuery->execute();

			// Clear language settings cache
			$cache=new Cache();
			$cache->flushLanguageSettings();
			$cache=null;

			// Blank file names must be created for each enabled language
			$activeLanguagesQuery=DB::getInstance()->query('SELECT `twocharacterabbr` FROM `languages` WHERE `active`=1');
			while($activeLanguages=$activeLanguagesQuery->fetch())
			{
				$langFileHandle=fopen(SETTINGS_DIRECTORY.'lang/'.$activeLanguages['twocharacterabbr'], 'w');
				fwrite($langFileHandle ,'');
				fclose($langFileHandle);
			}

			// If the user disables a language he is currently using, we need to reload the user object so that the user language is redetermined
			userControl::user()->reload();

			// We also need to make sure we reload the page
			$page=null;
			$page=new Page();
			$page->setPageLanguage(userControl::user()->getUserLanguage());
			$page->loadTemplate('admin/');

			// Add the title again as it will have been overwritten by the template
			// Breadcrumb navigation
			$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
			$page->addPageContent('<div class="clear"></div>');
			$page->addPageContent('<h2>'.$strings->getStringByName('Administration.ManageLanguages',userControl::user()->getUserLanguage(),1).'</h2>');

		}

		    //Create a table with the list of installed languages
		    $page->addPageContent('<table>');

		    //BUILD88 - Localise
		    $languageQuery=DB::getInstance()->prepare("SELECT func_GetStringById(`languagename`,:language,:showstringid) AS `name`,`id`,`active`,`twocharacterabbr` FROM `languages` WHERE `deleted`=0 ORDER BY `name`");
		    $languageQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
		    $languageQuery->bindParam(':language',userControl::user()->getUserLanguage());
		    $languageQuery->execute();

			while($languages=$languageQuery->fetch())
			{
				$page->addPageContent('<tr><td>'.$languages['name'].'</td>');
				if($languages['twocharacterabbr']==strtolower(FALLBACK_LANGUAGE))
				{
				    $page->addPageContent('<td><strong>'.$strings->getStringByName('Administration.ManageLanguages.PrimaryLanguage',userControl::user()->getUserLanguage(),1).'</strong></td><td><a href="index.php?s=languages&l='.$languages['twocharacterabbr'].'">'.$strings->getStringByName('Administration.ManageLanguages.UseLanguage',userControl::user()->getUserLanguage(),1).'</a></td></tr>');
				}
				elseif($languages['active']==1)
				{
				    $page->addPageContent('<td><a href="index.php?disablelang='.$languages['id'].'">'.$strings->getStringByName('Administration.ManageLanguages.DisableLanguage',userControl::user()->getUserLanguage(),1).'</a></td><td><a href="index.php?s=languages&l='.$languages['twocharacterabbr'].'">'.$strings->getStringByName('Administration.ManageLanguages.UseLanguage',userControl::user()->getUserLanguage(),1).'</a></td></tr>');
				}
				else
				{
				    $page->addPageContent('<td><a href="index.php?enablelang='.$languages['id'].'">'.$strings->getStringByName('Administration.ManageLanguages.EnableLanguage',userControl::user()->getUserLanguage(),1).'</a></td><td></td></tr>');
				}
			}
			$page->addPageContent('</table>');
		}
                elseif($requestedSection=='stringeditor'||isset($_POST['searchstringid'])||isset($_POST['editstringsave']))
                {
                    /*
                     * String editor
                     * Allows a user to customise any string throughout the webapplication
                     * As it stands a user must use the resource tracker to find the number of a string.
                     * A search functionnality will be added.
                     *
                     * A form is presented on this page where the user fills in the string id
                     * And can then edit it
                     *
                     */

                    //Store the stringid into a variable (making sure it is of integer type)
                    $stringSearch=$_POST['searchstringid'];

                    //Handle changes made to a string
                    if(isset($_POST['editstringsave']))
                    {
                        $stringSearch=(int) $_POST['editstringid'];
                        $stringLanguage=$_POST['language'];
                        $newValue=$_POST['newstring'];

                        $strings->setOverrideStringValue($stringSearch, $newValue, $stringLanguage);


                        // $strings->buildStringCache();

                        //The whole cache must be flushed as it is unclear on which pages a string appears.
                        // $sitecache = new Cache();
                        // $sitecache->flush();
                    }
			// Breadcrumb navigation
			$page->addPageContent('<div class="backtoadminhome_large"><p><a href="index.php">'.$strings->getStringByName('Administration.BackToAdministrationHome',userControl::user()->getUserLanguage(),1).'</a></p></div>');
			$page->addPageContent('<div class="clear"></div>');

			// Title
			$page->addPageContent('<h2>'.$strings->getStringByName('Administration.StringEditor',userControl::user()->getUserLanguage()).'</h2>');

                    $page->addPageContent('<p><form action="index.php" method="post">'.$strings->getStringByName('StringEditor.StringSearchLabel',userControl::user()->getUserLanguage()).'<input type="text" name="searchstringid" value="'.htmlentities($_POST['searchstringid'],ENT_QUOTES,'UTF-8').'" />'.$languageControl->getLanguageDropDown(userControl::user()->getUserLanguage(),$_POST['language']).'<input type="submit" value="'.$strings->getStringByName('StringEditor.StringSearchSumbitButton',userControl::user()->getUserLanguage()).'" class="searchbutton" style="margin-left:10px;" /></form></p><form action="index.php" method="post">');

                    //Handle search for a string
                    if(isset($_POST['searchstringid'])||isset($_POST['editstringsave']))
                    {
                        //Check if the string exists and if so display a textarea for each enabled language.
                        $stringQuery=DB::getInstance()->prepare("SELECT `stringid`,`default`,`override` FROM `stringcache` WHERE (`stringid`=:stringSearch OR `stringname`=:stringSearch) AND LENGTH(:stringSearch)>0 AND `language`=:stringLanguage LIMIT 1");
                        $stringQuery->bindParam(':stringSearch',$stringSearch);
                        $stringQuery->bindParam(':stringLanguage',$_POST['language']);
                        $stringQuery->execute();
                        $stringArray=$stringQuery->fetch();
                        $stringid=$stringArray['stringid'];
                        if($stringQuery->rowCount()>0)
                        {
                            //BUILD87 - Add string

                            $page->addPageContent('<div style="float:left"><p><br />'.$strings->getStringByName('StringEditor.OverrideValueTitle',userControl::user()->getUserLanguage(),1).'</p>');
                            $page->addPageContent('<textarea name="newstring" class="simpletextarea" rows="3" cols="50">'.htmlentities($stringArray['override'],ENT_QUOTES,'UTF-8').'</textarea>');
                            $page->addPageContent('<p><br />'.$strings->getStringByName('StringEditor.DefaultValueTitle',userControl::user()->getUserLanguage(),1).'</p>');
                            $page->addPageContent('<textarea name="defaultstring" class="simpletextarea" rows="3" cols="50" disabled="disabled" style="background-color:#CFCFCF;border:1px solid #AAAAAA;">'.htmlentities($stringArray['default'],ENT_QUOTES,'UTF-8').'</textarea></div>');
                            $page->addPageContent('<p style="float:right;"><br /><input type="submit" name="editstringsave" value="'.$strings->getStringByName('StringEditor.Save',userControl::user()->getUserLanguage(),1).'" class="submitbutton" /></p>');
                            $page->addPageContent('<div class="clear"></div>');
                            $page->addPageContent('<input type="hidden" name="editstringid" value="'.$stringid.'"/>');
                            $page->addPageContent('<input type="hidden" name="language" value="'.$_POST['language'].'"/>');
                            $page->addPageContent('</form>');
                        }
                        else
                        {
                                $page->addPageContent('<p>'.$strings->getStringByName('StringEditor.StringCouldNotBeFound',userControl::user()->getUserLanguage()).'</p>');
                        }
                    }
                    /*
                     * end of string editor section
                     */
		}
		elseif(isset($_REQUEST['imgid']))
		{
			//View image details action
			$imageDetails=new ImageControl($_REQUEST['imgid']);
			$galleryId=$imageDetails->getGalleryId();
			$imageExtension=$imageDetails->getImageExtension();
			$galleryControl=new Gallery($galleryId);
			$galleryName=htmlspecialchars($galleryControl->getGalleryName(),ENT_NOQUOTES,'UTF-8');

			// Breadcrumb navigation
			$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
			$page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php">'.$strings->getStringByName('Administration.ManageGalleries',userControl::user()->getUserLanguage(),1).'</a></p></div>');
			$page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php?galleryid='.$galleryId.'">'.$galleryName.'</a></p></div>');
			$page->addPageContent('<div class="clear"></div>');


			// $page->addPageContent('<p class="backtoadminhome"><span><a href="index.php?galleryid='.$imageDetails->getGalleryId().'">Back to image details</a></span></p>');
			$page->addPageContent('<h2>'.$imageDetails->getImageName().'</h2>');
			//BUILD87 - Add strings for image sizes
			$page->addPageContent('<table>');
			$page->addPageContent('<tr><td>Extra Small</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/100/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td rowspan="6"><img src="'.WEBSITE_URL.'upload/100/'.$_REQUEST['imgid'].'.'.$imageExtension.'" /><td></tr>');
			$page->addPageContent('<tr><td>Small</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/250/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td><td></tr>');
			$page->addPageContent('<tr><td>Medium</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/400/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td><td></tr>');
			$page->addPageContent('<tr><td>Large</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/800/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td><td></tr>');
			$page->addPageContent('<tr><td>Extra Large</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/1200/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td><td></tr>');
			$page->addPageContent('<tr><td>Original</td><td><textarea class="simpletextarea" readonly="readonly" cols="50" rows="2">'.WEBSITE_URL.'upload/original/'.$_REQUEST['imgid'].'.'.$imageExtension.'</textarea></td><td><td></tr>');
			$page->addPageContent('</table>');
			//end of view image details action
			$imageDetails=null;
		}
		elseif(isset($_REQUEST['editimgid'])||isset($_POST['editimage']))
		{
			//Edit image action
			if(isset($_POST['editimage']))
			{
				//The form to edit the image has been posted
				//This part will now process the form.

				$imageDetails=new ImageControl($_POST['imageid']);

				$imageDetails->updateImageName($_POST['imagename']);

				//Get the new gallery value - if it is 0 then the user has chosen not to move the image
				$newgallery=(int)$_POST['new_gallery'];
				if($newgallery<>0)
				{
				   $old_gallery_id=$imageDetails->getGalleryId(); //VERY important so that the gallery can be rebuilt later on in the script
				   $imageDetails->moveGallery($newgallery);

					//Rebuild the old gallery
					try {
						$gallery = new Gallery($old_gallery_id);
						$gallery->rebuild();
						$gallery=null;
					}
					//BUILD87 - Convert to the new event logging system
					catch (Exception $e)
					{
						$error='/admin/index.php reported the following error message when attempting to use the class \'Gallery\':'."<br />";
						$error.=$e->getMessage();
						header('Location: '.WEBSITE_URL.'500_en.html');
					}

					//Rebuild the new one
					try {
						$gallery = new Gallery($newgallery);
						$gallery->rebuild();
						$gallery=null;

						//Free up some memory
						$gallery=null;
					}
					//BUILD87 - Convert to the new event logging system
					catch (Exception $e)
					{
						$error='/admin/create.php reported the following error message when attempting to use the class \'Page\':'."<br />";
						$error.=$e->getMessage();
						header('Location: '.WEBSITE_URL.'500_en.html');
					}

					//Clear out cache
					//TODO: clear out only affected pages.
					$sitecache = new Cache();
					$sitecache->flush();
					header('Location: index.php?galleryid='.$newgallery);
					exit();
				}

                //Cycle through the enabled language updating the captions
                //BUILD87 - Convert to PDO
				$languagesQuery=DB::getInstance()->query("SELECT `twocharacterabbr` FROM `languages`");
				while($languages=$languagesQuery->fetch())
				{
					$languageabbr=$languages['twocharacterabbr'];
					$newcaption=$_POST['caption_'.$languageabbr];
                    $imageDetails->setCaption($newcaption,$languageabbr);
				}

				$galleryid=$imageDetails->getGalleryId();
				$gallery = new Gallery($galleryid);
                $gallery->rebuild();

                //Free up some memory
                $gallery=null;

                //Clear out the cache
				$sitecache = new Cache();
				$sitecache->flush();

                $imageDetails=null;
                $imageDetails=new ImageControl($_POST['imageid']);
			}
			else
			{
				$imageDetails=new ImageControl($_REQUEST['editimgid']);
			}

                //No specific action has been defined
                //Show the image details

                //Gallery ID is required to get the name of the gallery
    			$galleryid=$imageDetails->getGalleryId();
                $imageId=$imageDetails->getImageId();
				$imageExtension=$imageDetails->getImageExtension();
    			$galleryname=htmlspecialchars($imageDetails->getGalleryName(),ENT_NOQUOTES,'UTF-8');
    			$imagename=htmlspecialchars($imageDetails->getImageName(),ENT_NOQUOTES,'UTF-8');

                // Breadcrumb navigation
		$page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php?s=galleries">'.$strings->getStringByName('Administration.ManageGalleries',userControl::user()->getUserLanguage(),1).'</a></p></div>');
		$page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php?galleryid='.$galleryid.'">'.$galleryname.'</a></p></div>');
		$page->addPageContent('<div class="clear"></div>');
    			$page->addPageContent('<h2>'.$imagename.'</h2>');

                //Create form with input for image title and captions for each language
                $page->addPageContent('<form action="index.php" method="post"><table>');
                $page->addPageContent('<input type="hidden" name="imageid" value="'.$imageId.'" />');
                //BUILD87 - Add string
                $page->addPageContent('<tr><td><img src="'.WEBSITE_URL.'upload/100/'.$imageId.'.'.$imageExtension.'" /></td><td>'.$strings->getStringByName('Administration.ManageGalleries.Image.Name',userControl::user()->getUserLanguage(),1).'</td><td><input type="text" name="imagename" value="'.$imagename.'" /></td></tr></table>');
                //BUILD87 - Add string
                $page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageGalleries.Image.Caption',userControl::user()->getUserLanguage(),1).'</h3><table>');
                //BUILD88 - Localise
                $captionsInputQuery=DB::getInstance()->prepare("SELECT func_getStringById(`languagename`,:language,:showstringid) AS `language`,`twocharacterabbr` FROM `languages`");
                $captionsInputQuery->bindParam(':showstringid',$_SESSION[WEBSITE_URL.'showstringid'],PDO::PARAM_BOOL);
                $captionsInputQuery->bindParam(':language',userControl::user()->getUserLanguage());
                $captionsInputQuery->execute();

                $imageControl=new ImageControl();
        		while($caption_inputs=$captionsInputQuery->fetch())
        		{
        			$page->addPageContent('<tr><td>'.$caption_inputs['language'].'</td><td><input type="text" name="caption_'.$caption_inputs['twocharacterabbr'].'" value="'.htmlentities($imageControl->getImageCaption($imageId,$caption_inputs['twocharacterabbr']),ENT_QUOTES,'UTF-8').'" /></td><td></td></tr>');
        		}
        		$page->addPageContent('</table>');
                $imageControl=null;

                //Create the gallery drop down
                //This is used for moving an image to another gallery
                //BUILD87 - Add string
        		$page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageGalleries.Image.Gallery',userControl::user()->getUserLanguage(),1).'</h3>');
        		$page->addPageContent('<select name="new_gallery"><option value="0"></option>');

                $imageGalleryQuery=DB::getInstance()->query("SELECT `ID`,`name` FROM `galleries` WHERE `deleted`=0 ORDER BY `name`");
        		while($image_gallery=$imageGalleryQuery->fetch())
        		{
        			$page->addPageContent('<option value="'.$image_gallery['ID'].'">'.htmlentities($image_gallery['name'],ENT_QUOTES,'UTF-8').'</option>');
        		}

                        //Save button
                        //BUILD87 - Add string
			$page->addPageContent('</select><br /><input type="submit" name="editimage" value="'.$strings->getStringByName('Administration.ManageGalleries.Image.Save',userControl::user()->getUserLanguage(),1).'" /></form>');

		}
		elseif(isset($_REQUEST['delimgid'])||isset($_REQUEST['remimgid'])||isset($_REQUEST['rebuildgalleryid'])||isset($_REQUEST['galleryid']))
		{
			if(isset($_REQUEST['delimgid']))
			{
			    //Delete an image action
                $imageDeleteControl=new ImageControl($_REQUEST['delimgid']);

                $galleryId=$imageDeleteControl->getGalleryId();
                $imageDeleteControl->deleteImage($_REQUEST['delimgid']);
                try {
                        $gallery=new Gallery($galleryId);
                        $gallery->rebuild();
                        $gallery=null;

                        // Clear out cached items
                        $cacheControl=new Cache();
                        $cacheControl->flush();
                }
                catch (Exception $e)
                {
                        //BUILD87 - Change to new style of error logging
                        $error='/admin/create.php reported the following error message when attempting to use the class \'Page\':'."<br />";
                        $error.=$e->getMessage();
                        header('Location: '.WEBSITE_URL.'500_en.html');
                }
                $imageDeleteControl=null;
			}

            //Rebuild gallery action is not yet used in the interface but can be very helpful
            //This will be implemented through the interface at sometime (although being a very low priority).
            //Users should not have to take care of this type of things
            if(isset($_REQUEST['rebuildgalleryid']))
			{
                try {
                    $gallery = new Gallery($_REQUEST['rebuildgalleryid']);
                    $gallery->rebuild();
                    $gallery=null;
                }
                catch (Exception $e)
                {
                    //BUILD87 - Change to new style of error logging
                    $error='/admin/create.php reported the following error message when attempting to use the class \'Page\':'."<br />";
                    $error.=$e->getMessage();
                    header('Location: '.WEBSITE_URL.'500_en.html');
                }
			}

            //Display a gallery
			$galleryControl=new Gallery($_REQUEST['galleryid']);
            $galleryId=$galleryControl->getGalleryId();
			$galleryname=htmlentities($galleryControl->getGalleryName(),ENT_NOQUOTES,'UTF-8');

            //Breadcrumb
	    $page->addPageContent('<div class="backtoadminhome_small"><p><a href="index.php">&nbsp;</a></p></div>');
	    $page->addPageContent('<div class="breadcrumb_link"><p><a href="index.php?s=galleries">'.$strings->getStringByName('Administration.ManageGalleries',userControl::user()->getUserLanguage(),1).'</a></p></div>');
	    $page->addPageContent('<div class="clear"></div>');
			$page->addPageContent('<h2>'.$galleryname.'</h2>');
			//BUILD87 - Add string
            $page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageGalleries.Images',userControl::user()->getUserLanguage(),1).'</h3><table>');

			$imageLibraryQuery=DB::getInstance()->prepare("SELECT `imageid`,`title`,`extension` FROM `images` WHERE `deleted`=0 AND `gallery`=:galleryId ORDER BY `title`");
            $imageLibraryQuery->bindParam(':galleryId',$galleryId);
            $imageLibraryQuery->execute();
			while($image_library=$imageLibraryQuery->fetch())
			{
				$page->addPageContent('<tr><td>');
				$page->addPageContent('<img src="'.WEBSITE_URL.'upload/100/'.$image_library['imageid'].'.'.$image_library['extension'].'" alt="'.$image_library['title'].'" /></td><td>'.$image_library['title']);
                //BUILD87 - Add strings
				$page->addPageContent('<br /><a href="index.php?imgid='.$image_library['imageid'].'">'.$strings->getStringByName('Administration.ManageGalleries.ImageLinks',userControl::user()->getUserLanguage(),1).'</a>&nbsp;|&nbsp;<a href="index.php?editimgid='.$image_library['imageid'].'">'.$strings->getStringByName('Administration.ManageGalleries.EditImage',userControl::user()->getUserLanguage(),1).'</a>&nbsp;&nbsp;|&nbsp;<a href="index.php?delimgid='.$image_library['imageid'].'&galleryid='.$galleryId.'" style="color: #ff0000;" onclick="return confirm(\''.str_replace('&lt;%%$$1$$%%&gt;',$image_library['title'],$strings->getStringByName('Administration.ManageGalleries.DeleteImagePrompt',userControl::user()->getUserLanguage(),1)).'\')">'.$strings->getStringByName('Administration.ManageGalleries.DeleteImage',userControl::user()->getUserLanguage(),1).'</a></td></tr>');
			}
			$page->addPageContent('</table>');

            //Display the gallery code
            //The gallery code is the code that the users put in the page editor and is automagically converted into an AJAX gallery
            //BUILD87 - Add string
			$page->addPageContent('<h3>'.$strings->getStringByName('Administration.ManageGalleries.GalleryCode',userControl::user()->getUserLanguage(),1).'</h3>');
			$page->addPageContent('<p>'.htmlentities($galleryControl->getGalleryReplaceCode(),ENT_NOQUOTES,'UTF-8'));
			$page->addPageContent('</p>');
		}
                elseif($requestedSection=='logoff')
                {
                    /*
                     * Logging out a user is just a question of destroying the session
                     * In the future keeping track of user actions could be something interesting.
                     */
                    userControl::user()->logout();
                    header('Location: index.php');
                }
                elseif($requestedSection=='forms'){
                    $page->addPageContent('<h2>Title Forms</h2>');

                    try{
                        $formsQuery=DB::getInstance()->query("select `id`,`formname` from `forms` order by `formname`");
                        while($forms=$formsQuery->fetch()){
                            $page->addPageContent('<a href="index.php?formid='.$forms['id'].'">'.$forms['formname'].'</a><br />');
                        }
                    }
                    catch(Exception $e){
                        $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
                        $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
                        $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
                        $errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
                        $errorMessage.='Error: '.$e->getMessage()."\n";
                        $errorMessage.='Trace: '.$e->getTraceAsString()."\n";

                        $system=new systemConfiguration();
                        $system->logError($errorMessage,3);
                        $system=null;
                        header('Location: '.WEBSITE_URL.'500_'.userControl::user()->getUserLanguage().'.html');
                        exit;
                    }


                }
                elseif(isset($_REQUEST['formid'])){
                    $page->addPageContent('<h2>Form '.$_REQUEST['formid'].'</h2>');

                    $page->addPageContent('<h2>Fields</h2>');

                    $page->addPageContent('<table>');
                    $page->addPageContent('<tr><td>Name</td><td>Type</td><td>Required</td></tr>');

                    try{
                        $formQuery=DB::getInstance()->prepare("select `formfields`.`name`,`formfields`.`type` from `forms` inner join `formfields` on `forms`.`id`=`formfields`.`formid` where `forms`.`id`=:formId");
                        $formQuery->bindParam(':formId',$_REQUEST['formid']);
                        $formQuery->execute();
                        while($forms=$formQuery->fetch()){
                            $page->addPageContent('<tr><td>'.$forms['name'].'</td><td>'.$forms['type'].'</td></tr>');
                        }
                    }
                    catch(Exception $e){
                        $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
                        $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
                        $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
                        $errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
                        $errorMessage.='Error: '.$e->getMessage()."\n";
                        $errorMessage.='Trace: '.$e->getTraceAsString()."\n";

                        $system=new systemConfiguration();
                        $system->logError($errorMessage,3);
                        $system=null;
                        header('Location: '.WEBSITE_URL.'500_'.userControl::user()->getUserLanguage().'.html');
                        exit;
                    }
                    $page->addPageContent('</table>');

                }
                elseif($requestedSection=='email'){
                    $page->addPageContent('<h2>Email</h2>');


                    try{
                        // To send HTML mail, the Content-type header must be set
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/plain; charset=utf-8' . "\r\n";

                        // Additional headers
                        $headers .= 'To: Oliver Smith <oliver.smith@pure-sites.com>' . "\r\n";
                        $headers .= 'From: Pure-Sites <noreply@pure-demos.com>' . "\r\n";
                        $headers .= 'X-Mailer: Pure-Sites' . "\r\n";

                        // Mail it
                        mail($to, 'test subject', 'Hi Oliver, here is a test message from PHP.', $headers);
                    }
                    catch(Exception $e){
                        $errorMessage='Requested URL: '.$_SERVER['REQUEST_URI']."\n";
                        $errorMessage.='IP Address: '.$_SERVER['REMOTE_ADDR']."\n";
                        $errorMessage.='User agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
                        $errorMessage.='User language: '.userControl::user()->getUserLanguage()."\n";
                        $errorMessage.='Error: '.$e->getMessage()."\n";
                        $errorMessage.='Trace: '.$e->getTraceAsString()."\n";

                        $system=new systemConfiguration();
                        $system->logError($errorMessage,3);
                        $system=null;
                        header('Location: '.WEBSITE_URL.'500_'.userControl::user()->getUserLanguage().'.html');
                        exit;
                    }


                }
		else
		{


                    /*
                     * The Main administration menu
                     * Here is the Home screen of the administration section.
                     * This is where all the links should be placed.
                     *
                     */
					
					set_time_limit(10);
					
					if(USE_NEWURL_STYLE){
						$adminFolderPrefix=WEBSITE_URL.userControl::user()->getUserLanguage().'/admin/';
					}
					else{
						$adminFolderPrefix=WEBSITE_URL.'admin/';
					}

                    //Title
                    $page->addPageContent('<h1>'.$strings->getStringByName('Administration.AdministrationHome',userControl::user()->getUserLanguage()).'</h1>');
					

                    //Links
                    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.Content',userControl::user()->getUserLanguage()).'</h2>');
                    $page->addPageContent('<div class="adminitem adminpages"><p><a href="'.$adminFolderPrefix.'pages.php">'.$strings->getStringByName('Administration.ManagePages',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="adminitem adminmenu"><p><a href="'.$adminFolderPrefix.'index.php?s=menueditor">'.$strings->getStringByName('Administration.ManageMenu',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="adminitem adminstringeditor"><p><a href="'.$adminFolderPrefix.'index.php?s=stringeditor">'.$strings->getStringByName('Administration.StringEditor',userControl::user()->getUserLanguage()).'</a></p></div>');
                    //$page->addPageContent('<div class="adminitem admintemplates"><p><a href="'.$adminFolderPrefix.'index.php?s=templates">'.$strings->getStringByName('Administration.ManageTemplates',userControl::user()->getUserLanguage()).'</a></p></div>');
					
					

					error_log("Doing licensing stuff at " .date("r"));
					if(license::getInstance()->getValue('Forms')>0){
						$page->addPageContent('<div class="adminitem admintemplates"><p><a href="'.$adminFolderPrefix.'forms.php">'.$strings->getStringByName('Administration.ManageForms',userControl::user()->getUserLanguage()).'</a></p></div>');
					}
					
					if(license::getInstance()->getValue('Widget')>0){
						$page->addPageContent('<div class="adminitem adminwidgets"><p><a href="'.$adminFolderPrefix.'widgets.php">'.$strings->getStringByName('Administration.ManageWidgets',userControl::user()->getUserLanguage()).'</a></p></div>');
					}
					

                    $page->addPageContent('<div class="clear"></div>');

                    if(license::getInstance()->getValue('Imaging')==1)
                    {
                        $page->addPageContent('<h2>'.$strings->getStringByName('Administration.Imaging',userControl::user()->getUserLanguage()).'</h2>');
                        $page->addPageContent('<div class="adminitem adminimages"><p><a href="'.$adminFolderPrefix.'index.php?s=images">'.$strings->getStringByName('Administration.ManageImages',userControl::user()->getUserLanguage()).'</a></p></div>');
                        $page->addPageContent('<div class="adminitem admingalleries"><p><a href="'.$adminFolderPrefix.'index.php?s=galleries">'.$strings->getStringByName('Administration.ManageGalleries',userControl::user()->getUserLanguage()).'</a></p></div>');
                        $page->addPageContent('<div class="clear"></div>');
                    }

					error_log("Done licensing stuff at " .date("r"));
					
                    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.RegionalSettings',userControl::user()->getUserLanguage()).'</h2>');
                    $page->addPageContent('<div class="adminitem adminlanguages"><p><a href="'.$adminFolderPrefix.'index.php?s=languages">'.$strings->getStringByName('Administration.ManageLanguages',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="clear"></div>');

                    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.System',userControl::user()->getUserLanguage()).'</h2>');
                    $page->addPageContent('<div class="adminitem admindashboard"><p><a href="'.$adminFolderPrefix.'dashboard.php">'.$strings->getStringByName('Dashboard.DashboardTitle',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="adminitem admineventlog"><p><a href="'.$adminFolderPrefix.'index.php?s=eventlog">'.$strings->getStringByName('Administration.EventLog',userControl::user()->getUserLanguage()).'</a></p></div>');
                    // $page->addPageContent('<div class="adminitem admineventlog"><p><a href="'.$adminFolderPrefix.'index.php?s=email">'.$strings->getStringByName('Administration.Email',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="adminitem adminsystempages"><p><a href="'.$adminFolderPrefix.'syspages.php">'.$strings->getStringByName('Administration.SystemPages',userControl::user()->getUserLanguage()).'</a></p></div>');
					$page->addPageContent('<div class="adminitem adminlicense"><p><a href="'.$adminFolderPrefix.'license.php">'.$strings->getStringByName('Administration.License',userControl::user()->getUserLanguage()).'</a></p></div>');
					$page->addPageContent('<div class="adminitem admincache"><p><a href="'.$adminFolderPrefix.'cache.php">'.$strings->getStringByName('Administration.Cache',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="adminitem adminlogout"><p><a href="'.$adminFolderPrefix.'index.php?s=logoff">'.$strings->getStringByName('Administration.Logout',userControl::user()->getUserLanguage()).'</a></p></div>');
                    $page->addPageContent('<div class="clear"></div>');
		}

	}
	else
	{
            //Login failed section
            //Title
            $page->addPageContent('<h2>'.$strings->getStringByName('Administration.Login',userControl::user()->getUserLanguage()).'</h2>');
            $page->addPageContent('<p>'.$strings->getStringByName('Administration.LoginFailed',userControl::user()->getUserLanguage()).'</p>');
            $page->addPageContent('<form action="index.php" method="post"><h3>'.$strings->getStringByName('Administration.LoginUsername',userControl::user()->getUserLanguage()).'</h3><p><input type="text" name="username" /></p><h3>'.$strings->getStringByName('Administration.LoginPassword',userControl::user()->getUserLanguage()).'</h3><p><input type="password" name="password" /></p><p><input type="submit" name="login" value="'.$strings->getStringByName('Administration.LoginSubmit',userControl::user()->getUserLanguage()).'" /></p></form>');
	}
}
else
{
    //Login form
    //Title
    $page->addPageContent('<h2>'.$strings->getStringByName('Administration.Login',userControl::user()->getUserLanguage()).'</h2>');
    $page->addPageContent('<form action="'.WEBSITE_URL.userControl::user()->getUserLanguage().'/admin/index.php" method="post" name="loginform"><h3>'.$strings->getStringByName('Administration.LoginUsername',userControl::user()->getUserLanguage()).'</h3><p><input type="text" name="username" /></p><h3>'.$strings->getStringByName('Administration.LoginPassword',userControl::user()->getUserLanguage()).'</h3><p><input type="password" name="password" /></p><p><input type="submit" name="login" value="'.$strings->getStringByName('Administration.LoginSubmit',userControl::user()->getUserLanguage()).'" /></p></form>');
}





$page->noGalleries();
$page->noForms();
$strings=null;
error_log("Diaplaying at " .date("r"));
$page->display();
error_log("############################################ Done at " .date("r"));
exit();

?>
