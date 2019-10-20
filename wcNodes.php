<?php



class wcNodes
{
	public $log;
	
	function __construct()
	{
	}
	
	function addNode($type='***')
	{
		$db		= wcCore::getDatabaseHandle();
		$db->queryInsert('Nodes','dateCreation,dateLastUpdated,datePublishStart,datePublishEnd,contentTitle,contentSummary,contentBody,contentTags', 'now(),now(),now(),date_add(now() interval 30 year), \'\', \'\', \'\', \'\' ');
		
	}
	
	function getNode()
	{
		
	}
	function 
	
/*	
 	function updateTags()
	{
		$db		= wcCore::getDatabaseHandle();

		$log = new wcLog();
		$log->write('Clearing NodesTags table');
		$db->queryDelete("NodesTags");

		$log->write('Reading nodes');
		$data->querySelect('NodesTags','1', 'idNodes,idLanguage,tags')
		
		if (is_array($data))
		{
			if (count($data)>0)
			{
				$log->write('Processing nodes and tags');
				
				foreach($data as $row)
				
				
				$tags= explode(';', strtolower( $row['tags'] ) )  ;
				
				if (count($tags)>0)
				{
					
				}

	
			
			}
		}

		return $db->queryUpdate('News', sprintf("content = '$content'") , sprintf( "idNews = %d",  $id )  )  ;  
	}
*/	
}
?>