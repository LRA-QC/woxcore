<?php
class wcDirectory
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
	
				if ( isset( $params['idlanguage'] ))
					$idlanguage = $params['idlanguage'];
			}
		}
	
		$result = array();
		$db		= wcCore::getDatabaseHandle();
		$items = $db->querySelect('Directory', sprintf( "idLanguage = '%s' order by dateAdded desc", $idlanguage ), "*" ,  $limit);  
		$ki=count($items);
		for($k=0; $k<$ki; ++$k)
		{
			$items[$k]['website'] 	= wcHtml5::enforceUrl   ( $items[$k]['website'] );
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
	
				if ( isset( $params['idlanguage'] ))
					$idlanguage = $params['idlanguage'];

				if ( isset( $params['value'] ))
					$value = $params['value'];

			}
		}
		$result = array();
		if ( $value == '')
			return $result;
		$db		= wcCore::getDatabaseHandle();
		
		$items = $db->querySelect('Directory', sprintf( "idLanguage = '%s' and name like '%s%%' order by attrWeight desc, name ", $idlanguage, $value ), "*" ,  1000);  
		$ki=count($items);
		for($k=0; $k<$ki; ++$k)
		{
			$items[$k]['website'] 	= wcHtml5::enforceUrl   ( $items[$k]['website'] );
			$items[$k]['email'] 	= wcHtml5::enforceMailto( $items[$k]['email'] );
			$result[]= $items[$k];
		}			
		return $result;
	}
	
	
}
?>