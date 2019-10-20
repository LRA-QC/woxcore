<?php

class wcNode
{
	public $currentNode;

	function __construct()
	{
		$this->initialize();
	}

	/// return an array with several nodes
	static function getList($params=array())
	{
		$limit 		=  10;
		$idNodeType = 0;
		//$idlanguage	= 'en';
		$idlanguage = wcCore::getLanguage();

		if (isset( $params) )
		{
			if (is_array( $params ))
			{
				if ( isset( $params['limit'] ))
					$limit = $params['limit'];

				if ( isset( $params['idNodeType'] ))
						$idNodeType = $params['idNodeType'];


				if ( isset( $params['idlanguage'] ))
					$idlanguage = $params['idlanguage'];
			}
		}
		$db		= wcCore::getDatabaseHandle();
		$items = $db->querySelect('nodes', sprintf( "idNodeType = %d and  attrPublished = 1 order by datePublishStart desc", $idNodeType ), "DATE_FORMAT(datePublishStart, '%Y-%m-%d %H:%i') timestamp, contentTitle" ,  $limit);

		return $items;
	}

	function initialize()
	{
		$this->currentNode['id']=0;
		$this->currentNode['idLanguage']=0;
		$this->currentNode['idMediaThumbnail']=0;
		$this->currentNode['idParent']=0;
		$this->currentNode['idNodeType']=0;
		$this->currentNode['dateCreation']=date("Y-m-d H:i:s");
		$this->currentNode['dateLastUpdated']=date("Y-m-d H:i:s");
		$this->currentNode['datePublishStart']=date("Y-m-d H:i:s");
		$this->currentNode['datePublishEnd']=date("Y-m-d H:i:s",strtotime('+50 year'));
		$this->currentNode['idGUID']=wcCore::getGUID();
		$this->currentNode['attrPublished']=0;
		$this->currentNode['contentTitle']='';
		$this->currentNode['contentSummary']='';
		$this->currentNode['contentBody']='';
		$this->currentNode['contentTags']='';
//		var_dump($this->currentNode);
	}

	function loadbyGUID($id)
	{
//		echo "Load by GUID {$id}<br />";
		$db		= wcCore::getDatabaseHandle();
		$data = $db->querySelect('Nodes','idGUID=\''.$id.'\'','*');
//		var_dump($db);
//		var_dump($data);
		if (count($data)>0)
		{
			$this->currentNode = $data[0];

			$this->currentNode['contentTitle']  = wcCore::stringDecode( $this->currentNode['contentTitle']);
			$this->currentNode['contentSummary']= wcCore::stringDecode( $this->currentNode['contentSummary'] );
			$this->currentNode['contentBody']   = wcCore::stringDecode( $this->currentNode['contentBody']);
			$this->currentNode['contentTags']   = wcCore::stringDecode( $this->currentNode['contentTags'] );

			return true;
		}
		else
		{
			$this->initialize();
		}
		return false;
	}


	function loadbyID($id)
	{
//		echo "Load by id {$id}<br />";
		$db		= wcCore::getDatabaseHandle();
		$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
		$data = $db->querySelect('Nodes','id='.$id,'*');
//		var_dump($data);
		if (count($data)>0)
		{
			$this->currentNode = $data[0];
		}
		else
		{
			$this->initialize();
		}
//		var_dump($this->currentNode);
	}
	function save()
	{
		$err=0;
		$db		= wcCore::getDatabaseHandle(1);
		$db->connect();
//		var_dump($db);
		$set = 	 sprintf("dateLastUpdated=now(), contentTitle='%s', contentSummary='%s', contentBody='%s', contentTags='%s' ",  wcCore::stringEncode( $this->currentNode['contentTitle']),  wcCore::stringEncode($this->currentNode['contentSummary']) , wcCore::stringEncode($this->currentNode['contentBody']), wcCore::stringEncode($this->currentNode['contentTags']));
//		$set = 	 sprintf("dateLastUpdated=now() ");
		$where = sprintf("idGUID='%s'", $this->currentNode['idGUID'] );

		$err= $db->queryUpdate('Nodes', $set, $where);
//		var_dump($err);
		return $err;
	}
}

?>
