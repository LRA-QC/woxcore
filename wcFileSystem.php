<?php
//!Filesystem helper class, manage local files and directories
/*!
*/
class wcFileSystem
{
	const TYPE_FILE 	= 1;
	const TYPE_DIR 		= 2; 
	const TYPE_ALL		= 3;
	
	const MIME_UNKNOWN	= '*UNKNOWN*' ;
	const MIME_JPEG		= 'image/jpeg';
	const MIME_JPEG2000 = 'image/jpeg2000';
	const MIME_GIF		= 'image/gif';
	const MIME_PNG		= 'image/png';
	const MIME_PDF		= 'application/x-pdf';
	const MIME_ZIP		= 'application/zip';
	const MIME_BZIP		= 'application/x-bzip';
	const MIME_GZIP		= 'application/x-gzip';

//!retrieve a list of directory entries, you can specify to retrieve files, directories or both
/*!
retrieve a list of directory entries, you can specify to retrieve files, directories or both
*/
	static function getDirContents($path, $mode=3)
	{
		// mode = 1 for files, mode=2 for directories , mode=3 for files and directories
		$entries	= array();
		
		$path = rtrim ($path, '/').DIRECTORY_SEPARATOR;

		if (is_dir($path)) 
		{
		    if ($dh = opendir($path)) 
		    {
		        while (($file = readdir($dh)) !== false) 
		        {
					if ($file == '.' )			continue;
					if ($file == '..')			continue;

		        	$entry = $path.$file;
					
					if ($mode == 3)
					{
	        			$entries[] = $entry;
						continue;
					}						
					
					if ($mode == 1)
					{
						
						if (is_file($entry))
						{
		        			$entries[] = $entry;
						}
						continue;
					}
					
					if ($mode == 2)
					{
						if (is_dir($entry))
		        			$entries[] = $entry;
						continue;
					}
	            
		        }
		        closedir($dh);
		    }
		}
		return $entries;
	}
	

//!create a directory and reset the filesystem cache, you can specify the access attributes
/*!
create a directory and reset the filesystem cache, you can specify the access attributes
*/

	static function createDir($dir, $access = 0775)
	{
		@mkdir($dir, $access);
		clearstatcache();
	}
	

//!return the mime type for the specified filename
/*!
return the mime type for the specified filename
*/
    static function getFileType($filename)
    {
    	$fin= fopen($filename, 'rb');
    	if ($fin)
    	{
    		$buf= fread($fin, 30);
    		//BZIP
			if( substr($buf,0,3) == 'BZh' )				
				return wcFileSystem::MIME_BZIP;
			//GIF   		
			if( substr($buf,0,4) == 'GIF8' )				
				return wcFileSystem::MIME_GIF;
            //GZIP   		
    		if ( bin2hex(substr($buf,0,2)) == '1f8b' )	
    			return wcFileSystem::MIME_GZIP;
    		//JPEG-JFIF 
    		if ( bin2hex(substr($buf,0,2)) == 'ffd8' ) 
    			return wcFileSystem::MIME_JPEG;
    		//JPEG2000
    		if ( bin2hex(substr($buf,0,23)) == '0000000c6a5020200d0a870a00000014667479706a7032' )	
    			return wcFileSystem::MIME_JPEG2000;
    		//PDF
    		if ( bin2hex(substr($buf,0, 4)) == '25504446' )	
    			return wcFileSystem::MIME_PDF;
    		//PNG
    		if ( bin2hex(substr($buf,0, 8)) == '89504e470d0a1a0a' )	
    			return wcFileSystem::MIME_PNG;
    		fclose($fin);
    	}
    	return wcFileSystem::MIME_UNKNOWN;
    }
//!return detailed information about an image, extract iptc and exif if available
/*!
return detailed information about an image, extract iptc and exif if available
*/
	static function getImageFileInfo( $filename )
	{
		$info =array();
		$size = getimagesize( $filename , $iptcinfo);
		if (isset($size) === true)  // will be false if invalid image format
		{
			//rebuild an array for information
			$info['type'] = $size['mime'];
			$info['dimension']['width'] 	= $size[0];
			$info['dimension']['height'] 	= $size[1];
			$info['dimension']['xratio'] 	= $size[0] / $size[1];
			$info['dimension']['yratio'] 	= $size[1] / $size[0];
			
			//extract EXIF information
			$exif = exif_read_data( $filename , 'IFD0');     
			
			//add EXIF information to information array
			if (is_array($exif))
			{
				$info['exif']['filename'] 			= (isset($exif['FileName'])) 	 	? $exif['FileName'] : '';
				$info['exif']['datetimestamp'] 		= (isset($exif['FileDateTime']))	? $exif['FileDateTime'] : '';
				$info['exif']['size'] 				= (isset($exif['FileSize'])) 		? $exif['FileSize'] : '';

				$info['exif']['make'] 				= (isset($exif['Make'])) 			? $exif['Make'] : '';
				$info['exif']['model'] 				= (isset($exif['Model'])) 			? $exif['Model'] : '';
				$info['exif']['datetimeoriginal']	= (isset($exif['DateTimeOriginal']))? $exif['DateTimeOriginal'] : '';
				$info['exif']['exposuretime']		= (isset($exif['ExposureTime'])) 	? $exif['ExposureTime'] : '';
				$info['exif']['fnumber'] 			= (isset($exif['FNumber'])) 		? $exif['FNumber'] : '';
				$info['exif']['isospeedrating']		= (isset($exif['ISOSpeedRatings'])) ? $exif['ISOSpeedRatings'] : '';
				$info['exif']['shutterspeedvalue']	= (isset($exif['ShutterSpeedValue']))? $exif['ShutterSpeedValue'] : '';
				$info['exif']['aperturevalue']		= (isset($exif['ApertureValue'])) 	? $exif['ApertureValue'] : '';

				$info['exif']['exposurebias'] 		= (isset($exif['ExposureBiasValue']))? $exif['ExposureBiasValue'] : '';
				$info['exif']['maxaperturevalue'] 	= (isset($exif['MaxApertureValue'])) ? $exif['MaxApertureValue'] : '';
				$info['exif']['flash'] 				= (isset($exif['Flash'])) 			 ? $exif['Flash'] : '';
				$info['exif']['focallength'] 		= (isset($exif['FocalLength'])) 	 ? $exif['FocalLength'] : '';
				
				$info['exif']['meteringmode'] 				= (isset($exif['MeteringMode'])) 			 ? $exif['MeteringMode'] : '';
/*
				switch($exif['Flash'])
				{
					case 0:
						$info['exif']['flash'] = 'No flash';
						break;
					case 1:
						$info['exif']['flash'] = 'Fired';
						break;
					case 5:
						$info['exif']['flash'] = 'Fired, return not detected';
						break;
					case 7:
						$info['exif']['flash'] = 'Fired, return detected';
						break;
					case 8:
						$info['exif']['flash'] = 'On, dit not fire';
						break;
					case 9:
						$info['exif']['flash'] = 'On, return not detected';
						break;
						
					case 13:
						$info['exif']['flash'] = 'On, Return not detected';
						break;
					case 15:
						$info['exif']['flash'] = 'On, Return detected';
						break;
					case 16:
						$info['exif']['flash'] = 'Off, Did not fire ';
						break;

					case 24:
						$info['exif']['flash'] = 'Auto, Did not fire';
						break;
					case 25:
						$info['exif']['flash'] = 'Auto, Fired ';
						break;
					case 29:
						$info['exif']['flash'] = 'Auto, Fired, Return not detected ';
						break;
					case 31:
						$info['exif']['flash'] = 'Auto, Fired, Return detected ';
						break;
					case 32:
						$info['exif']['flash'] = 'No flash function ';
						break;
					case 48:
						$info['exif']['flash'] = 'Off, No flash function ';
						break;
					case :
						$info['exif']['flash'] = 'Fired, Red-eye reduction';
						break;
					case :
						$info['exif']['flash'] = 'Fired, Red-eye reduction, Return not detected';
						break;
					case :
						$info['exif']['flash'] = 'Fired, Red-eye reduction, Return detected';
						break;
						
						 
					case :
						$info['exif']['flash'] = 'On, Red-eye reduction';
						break;
					case :
						$info['exif']['flash'] = 'On, Red-eye reduction, Return not detected ';
						break;
					case :
						$info['exif']['flash'] = 'On, Red-eye reduction, Return detected ';
						break;
					case :
						$info['exif']['flash'] = 'Off, Red-eye reduction';
						break;
					case :
						$info['exif']['flash'] = 'Auto, Did not fire, Red-eye reduction';
						break;
					case :
						$info['exif']['flash'] = 'Auto, Fired, Red-eye reduction ';
						break;
					case :
						$info['exif']['flash'] = 'Auto, Fired, Red-eye reduction, Return not detected';
						break;
					case :
						$info['exif']['flash'] = 'Auto, Fired, Red-eye reduction, Return detected';
						break;

						
					default:
						$info['exif']['flash'] = 'Unknown';
						break;				}
				
				switch($exif['MeteringMode'])
				{
					case 1:
						$info['exif']['meteringmode'] = 'Average';
						break;
					case 2:
						$info['exif']['meteringmode'] = 'Center-weighted average';
						break;
					case 3:
						$info['exif']['meteringmode'] = 'Spot';
						break;
					case 4:
						$info['exif']['meteringmode'] = 'Multi-spot';
						break;
					case 5:
						$info['exif']['meteringmode'] = 'Multi-segment';
						break;
					case 6:
						$info['exif']['meteringmode'] = 'Partial';
						break;
					case 255:
						$info['exif']['meteringmode'] = 'Other';
						break;
					default:
						$info['exif']['meteringmode'] = 'Unknown';
						break;
				}
*/			}
			// add IPTC to information array
			if (isset($iptcinfo["APP13"]))   //extract IPTC information 
			{
			    $iptc = iptcparse($iptcinfo["APP13"]);

				$info['iptc']['title'] 			= (isset($iptc['2#005'][0])) ? $iptc['2#005'][0] : '';
				$info['iptc']['keywords'] 		= (isset($iptc['2#025'][0])) ? implode( '|', $iptc['2#025'] ) : '';
				$info['iptc']['creationdate']	= (isset($iptc['2#055'][0])) ? $iptc['2#055'][0] : '';
				$info['iptc']['caption']		= (isset($iptc['2#120'][0])) ? $iptc['2#055'][0] : '';
			}
		}
		else
		{
			$info['type'] = wcFileSystem::getFileType($filename);
		}
		$info['size'] = filesize  ( $filename  );
		return $info;
	}
	
}

?>
