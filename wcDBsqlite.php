<?php
//! Database abstraction class
/*!
	Database abstraction class, currently using mysqli library
 * mysql error codes: http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html
 *
*/



class wcDBsqlite
{
	/// once a successful connection is made, this variable holds the mysqli handle 
	public $dbHandle;

	/// file path
	public $dbFilePath;

	/// last error
	public $dbLastError;

	/// default constructor, initialize variables, you must supply all values for a successful database connection
	function __construct($dsn)
	{
		$values = explode( ';', $dsn );
		$this->dbHandle=null;
        if (count( $values ) >= 5)
        {
            $this->dbFilePath		= $values[0];
        }
	}
	/// check if the last function returned an error
	function checkError()
	{
//		if (mysqli_connect_errno()) 
//			return mysqli_connect_error();
	}

	/// connect to the database, if already connected will re-use the currect connection
	function connect()
	{
		if ($this->dbHandle === null)
		{
			
			if ( strlen($this->dbSocket)> 0)
				$this->dbHandle = @mysqli_connect( '' , $this->dbUser , $this->dbPassword , $this->dbDatabase,0, $this->dbSocket);
			else
				$this->dbHandle = @mysqli_connect( $this->dbHost, $this->dbUser , $this->dbPassword , $this->dbDatabase, $this->dbPort);

			if ($this->dbHandle === false)
			{
				$dbLastError=mysqli_connect_error();
//				echo $dbLastError;
			}
			else
			{
//				var_dump($this);
				$this->dbHandle->query( 'SET NAMES utf8' );
				$this->dbHandle->set_charset('utf8');
				$dbLastError='';
			}
		}
		return $this->dbHandle;
	}
	/// close the current database connection
	function disconnect()
	{
		if ($this->dbHandle)
			mysqli_close($this->dbHandle);
		$this->dbHandle = null;
	}
	
	/// return the last insert ID (as int) for the last insert
	function getLastID()
	{
		return $this->dbHandle->insert_id;
	}
	/// return an 'escaped' version of your supplied string, escaped version makes sure that the SQL query won't be broken
	function escapeString($value)
	{
		return $this->dbHandle->real_escape_string( $value );
	}
	
//! execute the specified query
/*!
	you need to indicate the raw query that you want to execute, the results if any will be returned as an array
	<br /><br />ie: 
	<br /> <em>$db->query("select * from News where id=5");</em>
*/	
	function query($sql)
	{
		$arr=array(); 
		if ($this->Connect())
		{
			//echo "<br />sql: $sql<br />";
            $result = $this->dbHandle->query( $sql );
			if ( $result != false)
			{
				if (is_object($result))
				{
					while ($row =  mysqli_fetch_assoc( $result )  ) 
					{
						$arr[]=$row;
					}
					$result->close();
				}
			}
		}
		return $arr;
	}
	
//! helper function for inserting values 
/*!
	you must supply the table name , the fields where to insert the values and the values the table name will automatically prefixed with the SITE prefix from the config file.
	<br /><br />ie: 
	<br /> <em>$db->queryInsert("News", 'id,content', "5, 'this is news'");</em>
*/
	function queryInsert($table, $fields, $values)
	{

		if ($this->Connect() != null )
		{
			$sql = sprintf("insert into %s_%s (%s) values (%s)", SITE, $table, $fields, $values );
//			echo "<br />$sql<br />";
			$this->dbHandle->query( $sql );
			return $this->dbHandle->affected_rows;
		}
		return 0;
	}
	
//! helper function for selecting values 
/*!
	you must supply the table name and the <em>where</em> condition, if no <em>where</em> condition is specified, 1 will be used, which means all records.
	optionally, you can supply the fields to return, by default '*' or all columns will be returned.
	the table name will automatically prefixed with the SITE prefix from the config file.
	 <br /><br />ie: 
	 <br /> <em>$db->querySelect('News', 'id=4');</em>
	 <br /> <em>$db->querySelect('News', 'id=4','content');</em>
	 <br /> <em>$db->querySelect('News', 'id=4 order by dateAdded desc','content',5);</em>  (last 5 news)
	
*/
	function querySelect($table, $condition='1', $fields='*', $limit=0, $offset=0 )
	{
//		echo "wcdb: ". SITE."<br />";

		if (defined('SITE')=== FALSE)
			die('wcdb: UNDEFINED SITE');

		$arr=array();
		if ($this->Connect())
		{
			if ($this->dbHandle == NULL)
				return NULL;

			if ($condition == '1')
				$sql = sprintf("select %s from %s_%s", $fields,  SITE, $table);
			else
				$sql = sprintf("select %s from %s_%s where %s ", $fields,  SITE, $table, $condition);
			if ($limit > 0)
				$sql.=" limit $offset, $limit"; 
//			echo "<br />sql: $sql<br />";
			if ( $result = $this->dbHandle->query( $sql ))
			{
				if (is_object($result))
				{
					while ($row =  mysqli_fetch_assoc( $result )  ) 
					{
						$arr[]=$row;
					}
					$result->close();
				}
			}
		}
		return $arr;
	}

//! helper function for updating values 
/*!
	you must supply the table name, the column(s) to set and the <em>where</em> condition, if no <em>where</em> condition is specified, 1 will be used, which means all records
	the table name will automatically prefixed with the SITE prefix from the config file.  <br /><br />ie: <br /> <em>$db->queryUpdate('News', "content='this is news'", 'id=4');</em>
*/
	function queryUpdate($table, $set, $condition='1')
	{
		if ($this->Connect())
		{
			$sql = sprintf("update  %s_%s set %s where %s", SITE, $table, $set, $condition);
			//echo "<br />$sql<br />";
			$this->dbHandle->query( $sql );
			return $this->dbHandle->affected_rows;
		}
		return 0;
	}
//! helper function for deleting values 
/*!
	you must supply the table name and the <em>where</em> condition, if no <em>where</em> condition is specified, 1 will be used, which means all records
	the table name will automatically prefixed with the SITE prefix from the config file. <br /><br />ie: <br /> <em>$db->queryDelete('News', 'id=4');</em>
*/
	function queryDelete($table,$condition='1')
	{
		if ($this->Connect())
		{
			$sql = sprintf("delete from %s_%s where %s", SITE, $table, $condition);
			$this->dbHandle->query( $sql ); 
			return $this->dbHandle->affected_rows;
		}
		return 0;
	}
}
?>
