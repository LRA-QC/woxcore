<?php
//!Provide easy database based logging functionnalities
/*!
Provide easy database based logging functionnalities. Log entries are handled in batches.
Each group of log entries will have a distinct and unique batch ID. Upon calling the
 <em>Init</em> function a batch ID will automatically be generated for you.
The idea behind batch logging is to regroup specific log entries together. So if you want 
to import a bunch of images, you can easily create a batch, write all the debugging info
 and then later, retrieve all entries related to that specific task.
*/
class wcLog
{
  ///This is the ID of the batch. 
	public $idBatch;
	public $db;
	
  ///default constructor, empty builtin variables
	function __construct()
	{
		$this->idBatch	= '';
		$this->db		= null;
		$logs			= array();
	}
  ///the init function must be call before writing any entries. A batch ID will be generated automatically for you. 
	function init()
	{
		$this->db		= wcCore::getDatabaseHandle();
		$items 			= $this->db->query('select uuid() uuid' );
		$this->idBatch 	= $items[0]['uuid'];
	}
	///write log entries, you can use the summary and detailed fields for stocking your log entries
	function write($summary, $detailed='')
	{
		if (strlen($this->idBatch)>0)
		{
			if (is_object($this->db))
			{
				if ( strlen($summary) > 0)
				{
					if ( strlen($detailed) == 0)
						$this->db->queryInsert('Logs', "idBatch,summary", sprintf("'%s', '%s'", $this->idBatch, addslashes( $summary)) );
					else
						$this->db->queryInsert('Logs', "idBatch,summary, detailed", sprintf("'%s', '%s','%s'", $this->idBatch, addslashes( $summary),  addslashes($detailed)) );
				}
			}
		}
	}
	///retrieve as an array all log entries associated to a specific batch ID 
  	static function getLogEntries($idbatch)
	{
		$db		= wcCore::getDatabaseHandle();
		$items 	= $db->querySelect('Logs' , " idBatch='$idbatch'", 'dateLogged, summary, detailed' );
		return $items;
	}

}
?>