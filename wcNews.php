<?php



class wcNews
{
    //TODO ADD FUNCTION FOR ARCHIVING
    //TODO Add function for taxonomy, so different news categories can be possible
 

	static function addNews($params=array())
	{
		$idLanguage	= 'en';
		$content 	= '';
		$status 	= 2;
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['content'] ))
					$content = $params['content'];
	
				if ( isset( $params['idLanguage'] ))
					$idlanguage = $params['idLanguage'];
					
				if ( isset( $params['status'] ))
					$status = $params['status'];					
			}
		}
		if (strlen($content)>0)
			if (strlen($idLanguage))
			{
				$db		= wcCore::getDatabaseHandle(1); //GET WRITE ACCESS
						  $db->query('START transaction');
				$data 	= $db->querySelect('News', '1', "max(idNews)+1 id" );  
						  $db->queryInsert('News', 'idNews, idLanguage, content,attrStatus' ,  sprintf( "%d, '%s','%s',%d", $data[0]['id'], $idlanguage, addslashes($content), $status ) );
				$result = $db->query('commit');
				
				return $result;   
			}
		return 0;
	}
	
	
	/// get latest news in the system , limit variable control the number of returned news articles, you must also specify the id of the language
	static function getLatest($params=array())
	{
		//PARAMS: idnode + number of news items to show
		/*
			TODO ADD SUPPORT FOR OLDER NEWS PAGE
		*/
		$limit 		=  10;
		
		//$idlanguage	= 'en';
//		$idlanguage = wcCore::getLanguage();
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];
	
//				if ( isset( $params['idlanguage'] ))
//					$idlanguage = $params['idlanguage'];
			}
		}
		$db		= wcCore::getDatabaseHandle();
//		$items = $db->querySelect('Nodes', sprintf( "idNodeType = 'news' and attrPublished = 1 order by datePublishStart desc" ), "DATE_FORMAT(datePublishStart, '%Y-%m-%d %H:%i') timestamp, contentTitle" ,  $limit);  
        if (isset($items))
            return $items;
        return null;
	}
	
	
	
	static function updateNewsContent($id, $content)
	{
		$db		= wcCore::getDatabaseHandle();
		$content= addslashes( $content );

		return $db->queryUpdate('News', sprintf("content = '$content'") , sprintf( "idNews = %d",  $id )  )  ;  
	}
}
?>