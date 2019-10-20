<?php
class wcEvents
{
	static function getLatest($params=array())
	{
		/* PARAMS:
			maxitem: max item count to return;
			days: max days between today and events
		// affiche les jours de différence..	SELECT  datediff(FROM_UNIXTIME(dateStart), now()), v.* FROM vscCalendar v WHERE 1
		*/	
		global $ctrl;
		$limit 	= 10;
		$idlanguage			= 'en';
		
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
		$items = $db->querySelect('Events e left join vsc_Nodes n on e.idNode = n.idNode ', sprintf( "CURDATE() < e.dateEnd and n.idLanguage = '%s' and n.attrStatus = 1 order by e.dateStart desc ", $idlanguage ), "DATE_FORMAT(e.dateStart, '%Y-%m-%d %H:%i') timestampStart,DATE_FORMAT(e.dateEnd, '%Y-%m-%d %H:%i') timestampEnd,n.title , e.idNode" ,  $limit);  
		return $items;
	}
}
?>