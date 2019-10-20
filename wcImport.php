<?php
//! Import class
/*! Import class
*/
class wcImport
{
	/// return an array of all hashed associated to a specified node
	static function getHashes($mode=0,$idnode=0)
	{
		global $CONFIG;


	 	$hashes=array();
        $id=$idnode;

        if ($id>0)
        {
        	$path=trim($CONFIG['pathSite']);
        	
        	
        	if (strlen($path)>0)
        	{
	            $path=sprintf("%sfiles/%d", $path ,$id);
    	        @mkdir($path,0775);
        	}
        
        }
        switch($mode)
        {
            case 2:     //from database, get hash only
            	$db	= wcCore::getDatabaseHandle();
				//$sql	= sprintf( 'SELECT hashMD5 FROM tkFiles WHERE idNode=%d;' , $id);
				$hashes = $db->querySelect('Files', 'idNode = '. $id, 'hashMD5');

                break;
            case 1:     //from disk
                break;
            default:    //from database
            
            	$db		= wcCore::getDatabaseHandle();
				$sql	= sprintf( 'SELECT * FROM tkFiles WHERE idNode="%d";' , $this->data['nodeContent']['idNode']);
				$hashes = $db->query($sql);
			    break;
        }
	}
		
	/// resize the image to a specific size and specify jpeg quality
	static function imageResize($filenameSrc,$filenameDest,$width=100,$height=100,$watermark='',$quality=75)
	{
		$info=getimagesize($filenameSrc);
		if ($watermark!='')
			$imgW=@imagecreatefrompng($watermark);
		$owidth=$width;
		if (count($info)>0)
		{
			switch($info[2])	
			{
				case IMG_GIF:
					$imgS = @imagecreatefromgif($filenameSrc);
					break;
				case IMG_JPG:
					$imgS = @imagecreatefromjpeg($filenameSrc);
					break;
                case IMG_JPEG:
                    $imgS = @imagecreatefromjpeg($filenameSrc);
                    break;
				case IMG_PNG:
					$imgS = @imagecreatefrompng($filenameSrc);
					break;
				case IMG_BMP:
					$imgS = @imagecreatefromwbmp($filenameSrc);
					break;
				case IMG_WBMP:
					$imgS = @imagecreatefromwbmp($filenameSrc);
					break;
				default:
					echo "File: $filenameSrc<br />";
					die('invalid media type');
					break;
			}
			if (isset($imgS))
			{
				$imgD = imageCreateTrueColor($width,$height);	
				$dst_x=0;
				$dst_y=0;
				if ($width==$height) //SQUARE
				{

					if ($info[0]>$info[1])  //W>H
					{
						$diff=($info[0]-$info[1])/2;  //800-600=200/2
						$src_w=$src_h=$info[1];
						$src_x=$diff;
						$src_y=0;
						if ($info[1]<$height)
						{
						  $src_y=($height-$info[1])/2;
						}
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );

					}
					if ($info[0]==$info[1])  //W==H
					{
						$src_w=$src_h=$info[0];
						$src_x=0;
						$src_y=0;
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
						
					}
					if ($info[0]<$info[1])  //W<H
					{
						$diff=($info[1]-$info[0])/2;  //800-600=200/2
						$src_w=$src_h=$info[0];
						$src_y=$diff;
						$src_x=0;
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
					}
				}
				else
				{
					if ($info[0]>$info[1])  //W>H
					{
              
             			$nh=ceil(($width/$info[0])*$info[1]);	
  		        		imageCopyResampled($imgD, $imgS, 0, $height-$nh, 0  , 0 , $width , $nh , $info[0]  , $info[1] );
						
						
            /*
            if ($info[1] < $height) //image is shorter than expected size
  		        imageCopyResampled( $imgD, $imgS, 0, ($height-$info[1]) / 2 , 0  , 0 , $width , $info[1] , $info[0]  , $info[1] );
            else
  		        imageCopyResampled($imgD, $imgS, 0, 0, 0  , 0 , $width , $height , $info[0]  , $info[1] );
  		        */
					}
					if ($info[0]==$info[1])  //W==H
					{
						if ($info[0]>=$width)
						{
							
						}
						else
						{
							
						}
					}
					if ($info[0]<$info[1])  //W<H
					{
						$nw=ceil(($height/$info[1])*$info[0]);
		   		        imageCopyResampled($imgD, $imgS, ($width-$nw)/2 , 0, 0  , 0 , $nw , $height , $info[0]  , $info[1] );
					}
				}
				
//				$temp=sprintf("IMG: X:%d Y:%d  SRCX: %d SRCY:%d W:%d H:%d SRC_W:%d SRC_H:%d <br />", 0, 0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h);
//				echo $temp;
				
//		        imageCopyResampled($imgD, $imgS, $dst_x, $dst_y, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
		        if (isset($imgW))
		        {	
		        	$infow=getimagesize($watermark);
//					imageCopy($imgD,$imgW,0,0,0,0,$infow[0],$infow[1]);		        	

					if ($info[0]<$info[1])  //W<H
					{
						$newh=($owidth*$infow[1])/$infow[0];
						imageCopyResampled($imgD,$imgW,0,0,0,0,$owidth,$newh,$infow[0],$infow[1]);		        	
					}
					else
						imageCopyResampled($imgD,$imgW,0,0,0,0,$width,($width*$infow[1])/$infow[0],$infow[0],$infow[1]);		        	
			        imageDestroy($imgW);
		        }
		        imageDestroy($imgS);				
		       
		        imageJPEG($imgD,$filenameDest,$quality);
						       
		        imageDestroy($imgD);
				if (file_exists($filenameDest))
					return true;
			}
		}
		return false;
	}



    ///import all the files specified in array $filenames into node id $idnode
    static function importFiles($idnode , $filenames )
    {
    	if (is_array($filenames) === true )
    	{
    		
    		$kf = count($filenames);

			echo "Processing $kf files<br />";

			if ($kf == 0 )
				return 0;

			$target_dir = sprintf( "%s/_%s/files/%d", dirname(getcwd() ), SITE, $idnode);
			
			@mkdir($target_dir, 0777, true);

			echo "Target dir [$target_dir]<br />";

			
			$processfiles=0;

	        $db		= wcCore::getDatabaseHandle();
			

			$hashes = wcImport::getHashes(2, $idnode); //FETCH EXISTING FILE HASHES FROM DATABASE SO THAT WE DONT IMPORT DUPLICATES IN THE SAME FOLDER    
        	
	        $ki = count($hashes);
	 		echo "<hr /><h2>Hashes</h2>";
    	    var_dump($hashes);
 			echo "<hr /><br />";
    		
    		echo $kf;
    		for ($k = 0 ; $k < $kf ; ++$k )
    		
    		{
				$filename  	= $filenames[$k];
				echo "<hr />- Processing $filename<br />";

		        if (file_exists($filename))
				{
			        $path_parts = pathinfo( $filename );
	        		$hash       = md5_file( $filename );
			        $size       = filesize( $filename ); 
			        
/*					$fn = substr( $path_parts['filename'], -2, 2 );
					
					if ($fn == '_t' )
						continue;
					if ($fn == '_m' )
						continue;
*/						
					$caption 	= strtolower ( filter_var($path_parts['filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES ) );
					$caption 	= strtr( $caption, '_!$.=,','      ' ); 
	        
				    $caption 	= preg_replace('/\[.*\]/', '', $caption);
				    $extension 	= strtolower ( $path_parts['extension']);
	
//					echo $caption."<br />";
	
			        $values=sprintf(" %d , now(), '%s', '%s' ,'%s' , %d", $idnode, $extension , $caption , $hash, $size );
			        $db->queryInsert('Files','idNode , dateAdded, fileExt ,  caption , hashMD5, fileSize', $values);
				
			        $id = $db->dbHandle->insert_id;
					if ($id > 0)
					{
				        echo "inserted id [$id]<br />";
						echo getcwd().'<br />';

				        $dest = sprintf ( "%s/%d.%s" , $target_dir, $id , $extension ); 
						echo "SRC <br />[$filename]<br />[$dest]<br /><br />";
			    		rename( $filename, $dest ); 
						if (file_exists( $dest ))
						{
							echo "- File copied successfully<br />";

						}
						else
						{
							echo "File copy failed..<br />";
		
							$temp = sprintf("idFile=%d",$id);
					        $db->queryDelete('Files', $temp);
						}
					} 
				}
				else
				{
					echo "File copy failed..<br />";
				}	
		        
    			
	   		}
    	}
    	return 0;
    }
    /// FILE INDEXING PROCESS
    static function indexMissingFiles()
    {
        $db	= wcCore::getDatabaseHandle();
        
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, looking for missing files');
        
        
        $items = $db->querySelect('Files', 'attrStatus=5','idFile,idNode,fileExt');

		global $CONFIG;
    	$path = $CONFIG['pathSite'];

		if (strlen(trim($path))==0)
		{
	    	$log->write("Fatal error, base path for the current site is empty, check config file under woxdata/<sitename>, variable is pathSite");
		}
    	else
    	{
	    	$log->write("Processing path:".$path);
	    	
	    	$ki=count($items);
	    	$missing=0;
	    	for ($k=0;$k < $ki ; ++$k)
	    	{
	    	   $file = sprintf("%sfiles/%s/%s.%s", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
	    	   if (file_exists($file) === false)
	    	   {
	    	       ++$missing;
	    	       $log->write("Missing: $file");
	//               $db->queryUpdate('Files','attrStatus=4','idFile='.$items[$k]['idFile']);
	           }
	    	}
			$log->write('admin interface, found '. $missing . ' missing file(s)');
			$log->write('admin interface, missing files scanning is done');
    	}
    	return $log->idBatch;

    }
    
    /// FILE INDEXING PROCESS
    static function indexFilesMetadata($max='0')
    {
		global $CONFIG;

    	$db	= wcCore::getDatabaseHandle();

		$log = new wcLog();
		$log->init();
		$log->write('admin interface, beginning metadata scanning');


    	$items = $db->querySelect('Files', 'attrStatus=0','idFile,idNode,fileExt');
    	$path = $CONFIG['pathSite'].'/files';
    	$ic = 0;	
    	$ki=count($items);
    	
    	
    	echo "Path: $path";
    	//var_dump($items);
    	
    	for ($k=0;$k < $ki ; ++$k)
    	{
    		$file = sprintf("%s/%s/%s.%s", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
    		//echo $file;
    		if (file_exists($file) === true)
    		{
    			$info  = wcFileSystem::getImageFileInfo( $file );
    		    
    		    $title  = trim( (isset($info['iptc']['title'])) 			? $info['iptc']['title'] : '' ); 
    		    $date   = trim( (isset($info['exif']['datetimeoriginal'])) ? $info['exif']['datetimeoriginal'] : ''); 
    		    
    		    $width  = trim( (isset($info['dimension']['width' ])) ? $info['dimension']['width' ] : 0);   
    		    $height = trim( (isset($info['dimension']['height'])) ? $info['dimension']['height'] : 0);   
    		    
    		    $type   = trim( (isset($info['type'])) ? $info['type'] : 'unknown'); 
    //            wcCore::debugVar($info);
    
    			$set='attrStatus=4';
    			if (strlen($title)>0)
    				$set.= sprintf( ", caption='%s'",  filter_var($title, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    			if (strlen($date)>0)
    				$set.= sprintf( ", dateAdded= timestamp('%s')", $date );
    			if ($width  != '0')
    				$set.= sprintf( ", attrInt1  = %s", $width );
    			if ($height != '0')
    				$set.= sprintf( ", attrInt2 = %s", $height );
    
    			if (strlen($type)>0)
    				$set.= sprintf( ", fileType='%s'",  filter_var($type, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    
    			if (strlen($type)>0)
    				$set.= sprintf( ", fileType='%s'",  filter_var($type, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    
    			
    		    $set .= ", metadata = '". addslashes(base64_encode(serialize($info)))."'";
    			
    			$ic += $db->queryUpdate('Files',$set ,'idFile='.$items[$k]['idFile']);
    
    			if ($max > 0)    //exit if specified limit is reached
    			 if ($ic >= $max)
    			     break;
    		}
    	}
		$log->write('admin interface, metadata import: '. $ic . ' file(s) processed');
		$log->write('admin interface, ending metadata scanning');
    	return $log->idBatch;
    }

    ///FILE INDEXING PROCESS
    static function indexGenerateThumbnails($max=0)
    {
		global $CONFIG;
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, thumbnail generation started');

        $db			= wcCore::getDatabaseHandle();
		$settings	= wcCore::getSettings("'image_thumbnailsize_width', 'image_thumbnailsize_height', 'image_mediumsize_width', 'image_mediumsize_height'");

		if (count($settings) != 4)
		{
			$msg='missing one of the following parameter in table <site>_Settings : '."'image_thumbnailsize_width', 'image_thumbnailsize_height', 'image_mediumsize_width', 'image_mediumsize_height'";
			$log->write($msg);

			die($msg);
		}
        
        $items = $db->querySelect('Files', 'attrStatus=4','idFile,idNode,fileExt');
    	$ki=count($items);

    	$path = $CONFIG['pathSite'].'/files';
   	
    	$ic=0;
    	for ($k=0;$k < $ki ; ++$k)
    	{
			$fileSrc 	 	= sprintf("%s/%s/%s.%s",	$path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
			$fileDstThumb 	= sprintf("%s/%s/%s_t.jpg",	$path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
			$fileDstMedium	= sprintf("%s/%s/%s_m.jpg", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );

			if (file_exists($fileSrc) === true)
			{
				//remove previous thumbnail and medium version
				@unlink($fileDstThumb);
				@unlink($fileDstMedium);

			    if (wcImport::imageResize($fileSrc, $fileDstThumb, intval( $settings['image_thumbnailsize_width'] ) , intval( $settings['image_thumbnailsize_height'] )) === true)
			    {
   					$log->write('thumbnail generation, generation of thumbnail: '. $fileDstThumb);

				    if (wcImport::imageResize($fileSrc, $fileDstMedium, intval( $settings['image_mediumsize_width'] ) , intval( $settings['image_mediumsize_height'] ), '',85) === true)
				    {
       					$log->write('thumbnail generation, generation of medium sized file: '. $fileDstMedium);
				    	//if both files were created successfully, we update the status in the database
				        $ic++;  	       
				        $db->queryUpdate('Files','attrStatus=5','idFile='.$items[$k]['idFile']);
				    }
				    else
       					$log->write('thumbnail generation, failed generation of medium sized file: '. $fileDstMedium);
				    	
			    }
   				else
   					$log->write('thumbnail generation, failed generation of thumbnail: '. $fileDstThumb);
   			}
			else
				$log->write('thumbnail generation: cannot find source file : '. $fileSrc);
    	}
  		$log->write('admin interface, thumbnail generation, '. $ic . ' file(s) processed');
		$log->write('admin interface, thumbnail generation ended');
    	return $log->idBatch;
    }
    /// rebuild order of the index
    static function indexRebuildOrder()
    {
        $db	= wcCore::getDatabaseHandle();
        
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, indexRebuildOrder');
        
        $items = $db->querySelect('Files', '1 order by idNode','distinct idNode');
    	
    	$ki=count($items);
    	
    	$errors=0;
    	
    	for ($k=0;$k < $ki ; ++$k)
    	{
			$buffer = sprintf(" - Processing idNode [%d]<br />",  $items[$k]['idNode']);
			$log->write($buffer);

	        $itemsN = $db->querySelect('Files', 'idNode='.$items[$k]['idNode']. ' and attrStatus=5 and fileExt = "jpg" order by attrWeight' ,'idFile,attrWeight');
			$weight = 0 ;
	    	$kiN=count($itemsN);
	    	
	    	for ($kN=0;$kN < $kiN ; ++$kN)
    		{
    			if ( $itemsN[$kN]['attrWeight'] != $weight )
    			{
    				echo $itemsN[$kN]['idFile'].'/'.$itemsN[$kN]['attrWeight'].' -- Weight not matching , rebuilding<br />';
    				
    				$db->queryUpdate('Files','attrWeight='.$weight, 'idFile='.$itemsN[$kN]['idFile']);
    				
    				++$errors;
    			}
				++$weight;	
			}
		}
		$log->write('admin interface, errors: '.$errors);
		$log->write('admin interface, indexRebuildOrder is done');
    	return $log->idBatch;
    }

    
}

?>