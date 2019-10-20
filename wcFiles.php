<?php /** @noinspection ALL */

class wcFiles
{
	static function getImagesSet( $params=array() )
	{
		global $ctrl;
	
		$limit 		=  10;
		$idlanguage	= 'en';
		$mode 		= 'random';
		$data	= '';
		$rel	= '';
		$max	= 3;
		$nodeid = 0;
		$pageid = 0;
		$width  = 0;
		$height = 0;
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];

				if ( isset( $params['nodeid'] ))
					$nodeid = $params['nodeid'];

				if ( isset( $params['width'] ))
					$width = $params['width'];

				if ( isset( $params['height'] ))
					$height = $params['height'];

				if ( isset( $params['pageid'] ))
					$pageid = $params['pageid'];

				if ( isset( $params['idlanguage'] ))
					$idlanguage = $params['idlanguage'];

				if ( isset( $params['rel'] ))
					$rel = sprintf(' rel="%s" ',$params['rel']);

				if ( isset( $params['mode'] ))
				{
					switch( $params['mode'] )
					{
						case 'latest':
							$mode = 'latest';
							break;

						case 'gallery':
							$mode = 'latest';
							break;
												
						default:
							$mode = 'random';
							break;
					}
				}
			}
		}

		$db=wcCore::getDatabaseHandle();

		switch( $mode )
		{
			case 'pageid':
				$sql = sprintf(" idNode = %d and attrStatus = 5 and fileType='image/jpeg' order by attrWeight", $nodeid);
				$items = $db->querySelect('Files', $sql , "idFile,idNode,caption" ,  $limit);  
				break;


			case 'latest':
				$items = $db->querySelect('Files', " attrStatus = 5 and fileType='image/jpeg' order by dateAdded desc", "idFile,idNode,caption" ,  $limit);  
				break;
									
			default:
				$items = $db->querySelect('Files', " attrStatus = 5 and fileType='image/jpeg' ORDER BY RAND() ", "idFile,idNode,caption" ,  $limit);  
				break;
		}


		$kitems=count($items);

		for ($k=0;$k<$kitems;$k++)
		{
			$large =sprintf('files/%d/%d_m.jpg', $items[$k]['idNode'],$items[$k]['idFile']);
			$thumb =sprintf('files/%d/%d_t.jpg', $items[$k]['idNode'],$items[$k]['idFile']);
			$title = htmlspecialchars( $items[$k]['caption'], ENT_QUOTES );
			$size  = '';
			if ($width > 0)
				$size  =  'width="'.$width.'"';
			if ($height > 0)
				$size .= ' height="'.$height.'"';
			
			$data.=sprintf('<li><a href="%s" %s title="%s"><img src="%s" %s alt=""/></a></li>',$large,$rel, $title,$thumb,$size, $title);
		}			

		return $data;
	}
/*	
	static function getFiles( $params=array() )
	{
		global $ctrl;
	
		$limit 		=  10;
		$idlanguage	= 'en';
		$mode 		= 'random';
		$data	= '';
		$rel	= '';
		$max	= 3;
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];
	
				if ( isset( $params['idlanguage'] ))
					$idlanguage = $params['idlanguage'];

				if ( isset( $params['rel'] ))
					$rel = sprintf(' rel="%s" ',$params['rel']);

				if ( isset( $params['mode'] ))
				{
					switch( $params['mode'] )
					{
						case 'latest':
							$mode = 'latest';
							break;
												
						default:
							$mode 		= 'random';
							break;
					}
				}

			}
		}


		$db=wcCore::getDatabaseHandle();

		switch( $mode )
		{
			case 'latest':
				$items = $db->querySelect('Files', " attrStatus = 5 and fileType='image/jpeg' order by dateAdded desc", "idFile,idNode,caption" ,  $limit);  
				break;
									
			default:
				$items = $db->querySelect('Files', " attrStatus = 5 and fileType='image/jpeg' ORDER BY RAND() ", "idFile,idNode,caption" ,  $limit);  
				break;
		}


		$kitems=count($items);

		for ($k=0;$k<$kitems;$k++)
		{
			$large=sprintf('files/%d/%d_m.jpg', $items[$k]['idNode'],$items[$k]['idFile']);
			$thumb=sprintf('files/%d/%d_t.jpg', $items[$k]['idNode'],$items[$k]['idFile']);
			$title = htmlspecialchars( $items[$k]['caption'], ENT_QUOTES );
			$data.=sprintf('<li><a href="%s" %s title="%s"><img src="%s" width="75" height="75" alt=""/></a></li>',$large,$rel, $title,$thumb);
		}			

		return $data;
	}
*/	
}

?>