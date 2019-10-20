<?php
class wcMembers
{
	/// TODO http://code.google.com/apis/maps/documentation/geocoding/#JSON  to translate address in coordinates
	
	
	///Get latest entries added in the directory
	static function getLatest($params=array())
	{
		//PARAMS: idnode + number of news items to show
		/*
			TODO ADD SUPPORT FOR OLDER NEWS PAGE
		*/
		global $ctrl;
		$limit 		=  10;
		$idlanguage	= 'en';
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];
	
			}
		}
	
		$result = array();
		$db		= wcCore::getDatabaseHandle();
		$items = $db->querySelect('Members', sprintf( "1 order by dateCreated desc" ), "id, idLanguage, attrStatus, email, hash1, hash2, firstname, lastname, nickname, ifnull(dateLastlogin,'---') dateLastlogin, dateCreated" ,  $limit);  
		$ki=count($items);
		for($k=0; $k<$ki; ++$k)
		{

			$items[$k]['email'] 	= wcHtml5::enforceMailto( $items[$k]['email'] );
			$result[]= $items[$k];
		}
		return $result;
	}
	
	///Get all the links matching the suggested letter
	static function browseRange($params)
	{
		global $ctrl;
		$limit 		=  10;
		$idlanguage	= 'en';
		$value='';
		
		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];
	
				if ( isset( $params['value'] ))
					$value = $params['value'];

			}
		}
		$result = array();
		if ( $value == '')
			return $result;
		$db		= wcCore::getDatabaseHandle();
		
		$items = $db->querySelect('Members', sprintf( "nickname like '%s%%' order by nickname desc", $value ), "id, idLanguage, attrStatus, email, hash1, hash2, firstname, lastname, nickname, ifnull(dateLastlogin,'---') dateLastlogin, dateCreated" ,  1000);  
		$ki=count($items);
		for($k=0; $k<$ki; ++$k)
		{
			$items[$k]['email'] 	= wcHtml5::enforceMailto( $items[$k]['email'] );
			$result[]= $items[$k];
		}			
		return $result;
	}
	
	
}
?>