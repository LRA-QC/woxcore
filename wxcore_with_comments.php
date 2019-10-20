<?php
//! Commenting system class
/*!
functions for managing comments
*/
class wcComment
{
    //!add a comment
    static function add( $idnode, $idfile,$author, $authoremail, $comment, $language)
    {
        if(filter_var( $authoremail, FILTER_VALIDATE_EMAIL) )
	    {
	     	$idnode = intval($idnode);
	     	$idfile = intval($idfile);

            if ( $idnode>= 0 )
            {
                if ( $idfile >= 0 )
                {
                    $db	= wcCore::getDatabaseHandle();
                    $temp = sprintf( "%d,%d,'%s','%s','%s','%s'" , $idnode, $idfile , htmlspecialchars ($author, ENT_QUOTES, 'UTF-8'), $authoremail , htmlspecialchars ($comment, ENT_QUOTES, 'UTF-8') , $language);
                    if ($db->queryInsert('Comments', 'idNode, idFile, author, email, comment, idLanguage', $temp)>0)
                    {
                        wcComment::updateNodeCommentsCount($idnode, $idfile);
                        return 0;
                    }
                }
                else
                {
                    return 4;
                }
            }
            else
            {
                return 3;
            }
	     }
		 else
         {
            return 2;
         }
		 return 1;
	}
    //!approve a comment
    static function approve($idcomment)
    {
    }

    //!disable a comment
    static function disable($idcomment)
    {
    }
		
    //!get a list of comments
    static function fetch( $language, $idnode, $idfile=0, $page=0, $pageentries=10 )
    {
        $db			= wcCore::getDatabaseHandle();
    $idnode 	= intval($idnode);
    $idfile 	= intval($idfile);

    //echo "node: $idnode, $idfile<br />";

        $language 	= filter_var($language, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

//      echo "language: $language<br />";

        if ( $idnode >= 0 )
        {
            if ( $idfile >= 0 )
            {
                $temp = sprintf( "idNode = %d and idFile = %d and idLanguage = '%s'" , $idnode, $idfile , $language );
//					echo "$temp<br />";
                return $db->querySelect('Comments', $temp);
            }
        }
        return null;
    }

    //!get a specific comment
    static function get($idcomment)
    {
    }
		
		
    //!delete a comment
    static function remove($idcomment)
    {
    }
		
    static function updateNodeCommentsCount($idnode, $idfile)
    {
        $result = 0;
        $db		  = wcCore::getDatabaseHandle();

        $idnode = intval($idnode);
        $idfile = intval($idfile);

        $where = sprintf('idNode = %d and idFile = %d  group by idNode,idFile', $idnode, $idfile);
        $nodes 	= $db->querySelect('Comments', $where, 'idNode, idFile, count(idNode) kc','1');
        $kn 	= count($nodes);

        if ($kn > 0 ) //only process if the previous query returned something
        {
            if ($idfile == 0) //update node comment count
            {
                        if ($db->queryUpdate('Nodes','countComment='.$nodes[0]['kc'], 'idNode = '.$nodes[0]['idNode']) > 0)
                         $result++;
            }
            else              //update a specific file comment count
            {
                        if ($db->queryUpdate('Files','countComment='.$nodes[0]['kc'], 'idNode = '.$nodes[0]['idNode']. ' and idFile='.$nodes[0]['idFile']) > 0)
                            $result++;
            }
        }
			return $result;
    }
		
    static function updateAllCommentsCount()
    {
        $db		= wcCore::getDatabaseHandle();
        $nodes 	= $db->querySelect('Comments', '1 group by idNode,idFile','idNode, idFile, count(idNode) kc');
        $kn 	= count($nodes);

        $result = 0;

        for ($k=0 ; $k < $kn; $k++)
        {
            if (intval($nodes[$k]['idFile'])==0)
            {
                if ($db->queryUpdate('Nodes','countComment='.$nodes[$k]['kc'], 'idNode = '.$nodes[$k]['idNode']) > 0)
                    $result++;
            }
            else
            {
                if ($db->queryUpdate('Files','countComment='.$nodes[$k]['kc'], 'idNode = '.$nodes[$k]['idNode']. ' and idFile='.$nodes[$k]['idFile']) > 0)
                    $result++;
            }
        }
        return $result;
    }
}
?><?php
//! Controller class, which is the backbone of a web page
/*!
	The controller class handle the actions for the specific web page. It then render the view associated to the controller
*/
class wcController
{
	//!name of the controller
	public $Name;
	//!current action
	public $Action;
	//!the $Data variable is used by the view to provide dynamic rendering, every variable held inside the $Data array can be used easily in the view
	public $Data;
	//!the body of the web page
	public $Body;
	//!each page can have several types (full html, mobile,...)
	public $Type;
	//!each page can have different templates (ie: about page)
	public $Template = '';


	//!constructor, responsible of initialization, will read the Q parameter if it's used in the URL and will map it to the action. Will also handle the 'language' parameter.
	public function __construct($name='',$type='',$area='')
	{
		$this->Name		= $name;
		$this->Action	= wcCore::varGet('q');
		$this->Data		= array();
		$this->Body		= '';
		$this->Type		= $type;
		$this->Area  	= $area;

		$this->Data['LANGUAGE']	=   wcCore::getLanguage($area);
		$this->Data['URLSTATIC']=   wcCore::cacheFetch(SITE.'urlStaticTheme');	//USED BY HTML STATIC CONTENT LINKS
		$this->Data['PATHSITE']	=   wcCore::cacheFetch(SITE.'pathSite');	//USED BY HTML STATIC CONTENT LINKS
		$this->Template			= 'default';
//		var_dump($this);
		ob_start();

	}
	//!load a specific view template from disk
	public function loadTemplate($path='')
	{
		if (strlen($path)==0)	// index.html.fr.php
			$path=sprintf('./views/%s-%s-%s.php', $this->Name, $this->Template, $this->Type );
// 	CONTROLLER WITH LANGUAGE
//			$path=sprintf('./views/%s-%s-%s-%s.php', $this->Name, $this->Template, $this->Type ,$this->Data['LANGUAGE']);

		if (file_exists($path) )
		{
			ob_start();
			include $path;
			$this->Body = ob_get_contents();
			ob_end_clean();
		}
        else
        {

        	//echo "template not found:$path ".' -- GETCWD:'.getcwd();
        	echo "<br />";
        }

	}
	//!this function load the template specified, launch the plugins specified in the templates, insert dynaminc data and flush out the output
	public function render()
	{
		$this->loadTemplate();
		//var_dump($this);
		$this->processPlugins();
		$this->processData();
		// DISABLE CACHE WHILE DEVELOPING
		// $this->saveCache();
		echo $this->Body ;


function renderPage()
{
	global $ctrl;
	$ctrl->loadTemplate();
	$ctrl->processData();
	$ctrl->processPlugins();
	$ctrl->processData();
	$ctrl->render();
}

	}
	//!this function will search for specific keywords in the template, when keywords are found they are looked up in the $Data array, if they are found, they will be replaced by the actual value
	public function processData()
	{
//		var_dump($this->Data);
//		die;
		$page=$this->Body;

		foreach($this->Data as $key => $value)
		{
			$search='<!--[-'.$key.'-]-->';
			$page=str_replace( $search ,$value, $page);
		}
		$this->Body = $page;
	}
	//!this function will search for specific keywords in the template, when keywords are found, we try to call the appropriate plugin and the output results will be sent back to the template
	public function processPlugins()
	{
		$page=$this->Body;
		while($p=strpos($page,'<['))
		{
//			die('process');
			$pos=$p+2;
			if ($pe=strpos($page,']>',$pos))
			{
				$pcmd=strpos($page,':',$pos);
				$v=substr($page,$pcmd+1,$pe-$pcmd-1 );
				$args=array();
				$kv=strlen($v);
				$cmd=$val='';
				$bcmd=true;
//			echo "v:$v [$kv]<br />";
				for ($k=0;$k<$kv;$k++)
				{
					$a=$v[$k];
					if (($a=='|') || ($a=="\n")|| ($a=="\r"))
					{
						$args[$cmd]=$val;
						$bcmd=true;
						$cmd=$val='';
						continue;
//						echo sprintf("cmd [%s] val [%s]<br />",$cmd,$val);
					}
					if ($a=='=')
						$bcmd=false;
					else
					{
						if ($bcmd)
							$cmd.=$a;
						else
							$val.=$a;

					}
				}
				$args[$cmd]=$val;
//				var_dump($args);
				$cmd= substr($page,$pos, $pcmd-$pos);

				//echo "**{$cmd}**".var_dump($args)."**";

				$func='plugin__'.$cmd;

				$temp=substr($page,0,$pos-2).$func($args).substr($page,$pe+2);
				$page=$temp;
				//$pos=0;
				//str_replace(,,$page);

			}
			else
				break;
		}
		$this->Body = $page;
	}

	public function loadCache()
	{
		if (is_null( $this->Action))
			$path=sprintf('./cache/%s-%s-%s.php', $this->Name, $this->Template, $this->Type );
		else
			$path=sprintf('./cache/%s-%s-%s.php', $this->Name, $this->Action, $this->Type );
		if (file_exists($path))
		{
			$mtime=@filemtime($path);
			if ( $mtime!== FALSE )
			{
				$dtime = time()-$mtime;
				//echo sprintf("%d - %d - %d",time(),$mtime,$dtime);
				if ($dtime < 60)
				{
					$fin = fopen($path, "r");
					if (FALSE  !== $fin)
					{
						$this->Body = fread($fin, filesize($path));	
						fclose($fin);

						echo $this->Body;
						echo "*** CACHE *** ";
						return true;
					}
				}
				else
					unlink($path);
			}
			return false;
		}
		return false;
	}

	public function saveCache()
	{
	
		$path=sprintf('./cache/%s-%s-%s.php', $this->Name, $this->Template, $this->Type );
		$fout = fopen($path, "w");
		if ($fout !== FALSE)
		{
			fwrite($fout, $this->Body);
			fclose($fout);
		}
	}

	//!Set the page to a specific template
	public function setTemplate($name)
	{
		$name = wcCore::cleanText($name);
		$this->Template			= $name;

	}

	//!output the content of a specific variable in the $data array
	public function write($name)
	{
		if (isset($this->Data[$name]))
			echo $this->Data[$name];
	}
}
?>
<?php
//	define ('__DIR__','/home/../public_html/');
//	function __autoload($class_name) {    include ''. $class_name . '.php';}
	function my_autoloader($class_name) {    include ''. $class_name . '.php';}
	spl_autoload_register('my_autoloader');
	wcCore::initialize();

//!Interface: object interface for components
/*!
	list of all methods related to components. Describe componentInstall and componentUninstall
*/
interface iComponent
{
	//! this method will be called on installation
	public static function componentInstall();
	//! this method will be called on uninstallation
	public static function componentUninstall();
}

//!Interface: HTML interface for HTML Helper classes
/*!
	list of all methods related to HTML helper.
*/
interface iHtml
{
	static function lineBreak();
	static function encode($text,$symbol,$option);
	static function encodeCode($text, $option);
	static function encodeCss($filename);
	static function encodeJs($filename);
	static function encodeLink($text,$link,$option);
	static function encodeHeader($text,$size);
	static function encodeNavList($labelnav,$items,$optionNAV,$optionsUL,$optionsLI);
	static function encodeList($items,$options,$optionsli);
	static function encodeFormTime($fieldname,$timestamp,$inc_min);
	static function encodeFormDate($fieldname,$timestamp,$inc_min);
	static function encodeLabel($id,$text,$options);
	static function encodeRadio($name,$value,$default,$options);
	static function encodeInputbox($name,$value,$options);
	static function enforceUrl($link);
	static function enforceMailto($link);
}


//!Timer class, used to return time delta
/*!
On initilization, it will contain the current time. When calling the elapsed() function, will return the delta
*/
class wcTimer
{
	//!private, store the time of initialization of the timer in ms
	var $ts;
	//! constructor, store current time of initialization
    function __construct()
    {
        $this->ts=microtime(true);
    }
    //! return the elapsed time since the initialization
    function elapsed()
    {
        return (round(microtime(true) - $this->ts,4));
    }
}


//!core class for woxcore, contains caching, string and utilities functions
/*!
core class for woxcore, contains caching, string and utilities functions
*/
class wcCore
{
	const accessModeReadOnly=0;
	const accessModeReadWrite=1;
	const accessModeOwner=2;
	const Version='1.0';

	//! store value in cache
	static function cacheStore($var,$value,$ttl=3600,$cachetype=0)
	{
		if (defined('EXT_APC')==true)
			apc_store($var,$value,$ttl);
	}
	//! fetch value from cache
	static function cacheFetch($var,$cachetype=0)
	{
		if (defined('EXT_APC')==true)
			return apc_fetch($var);
		return false;
	}
	//! sanitize a string
	static function cleanText($text)
	{
		return filter_var( html_entity_decode($text), FILTER_SANITIZE_STRING );
	}
	//! dump the internal structure of the variable and its content into a html safe output. Html version of var_dump
	static function debugVar( $va, $option='')
	{
		echo '<pre '.$option.'>';
		var_dump($va);
		echo '</pre>';
	}

	//! get the platform and browser version
	static function getPlatform()
	{
		$agent = $_SERVER["HTTP_USER_AGENT"] ;
//		echo "AGENT: $agent<br />";
//safari		preg_match( "/(M\w.*)\/(.*)\s\((.*)\)\s(.*)\sVersion\/(.*)\s(.*)\/(.*)/", $agent, $data );
/*
	SAFARI:
			Mozilla/5.0
					(Macintosh; U; Intel Mac OS X 10_6_3; en-us)
						AppleWebKit/531.22.7 (KHTML, like Gecko) Version/4.0.5
							Safari/531.22.7
	FFOX:
			Mozilla/5.0
					(Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.3)
						Gecko/20100401
							Firefox/3.6.3

	Chrome
			Mozilla/5.0
					(Macintosh; U; Intel Mac OS X 10_6_3; en-US)
						AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.422.0 Safari/534.1

*/

		$result = array();
		$result['browser_name']='';
		$result['browser_version']='';
		$result['browser_build']='';
		$ksl = strlen($agent);
		$pos = strpos ( $agent, '/'  );
		if ($pos !== false)
		{
			$result['browser_name'] 	= substr($agent, 0, $pos);
			$opos = $pos;
			$pos = strpos ( $agent, ' ',$pos  );
			if ($pos !== false)
			{
				$result['browser_version'] 	= substr($agent, ++$opos, $pos-$opos);

				$opos = $pos;
				$pos = strpos ( $agent, '(',$pos  );


				if ($pos !== false)
				{

					$agent = substr($agent,  $pos+1, $ksl);


					$pos = strpos ( $agent, ')',$pos  );
					$result['platform'] = substr($agent, 0, $pos);

					$result['misc'] = trim(strtolower(substr($agent,  $pos+1, $ksl)));

				}
			}
		}

		$os = explode(';', strtolower($result['platform'] ));

		$result['device']					= trim($os[0]);
		$result['device_detailed']			= trim($os[2]);
		$result['regional_settings']		= trim($os[3]);

		if ( strpos( $result['misc'], 'firefox/' )  )
		{
			$result['browser_name'] 	= 'firefox';

			if ( $pos = strpos( $result['misc'], 'gecko/' ) >= 0 )
			{

				$pose = strpos( $result['misc'], ' ' );
     			$result['browser_build']  = substr($result['misc'], 5+$pos, $pose-($pos+5));
			}

			if ($pos = strpos( $result['misc'], 'firefox/' ))
     			$result['browser_version']  = substr($result['misc'], 8+$pos, $ksl);
		}

		if ( $pos = strpos( $result['misc'], 'safari/' )  )
		{
			$result['browser_name'] 	= 'safari';
     		$result['browser_build']  = substr($result['misc'], 7+$pos, $ksl);
     		$pos = strpos( $result['misc'], 'version/' );
     		if ( $pos >= 0 )
			{
				$pose = strpos( $result['misc'], ' ', $pos+1 );
     			$result['browser_version']  = substr($result['misc'], 8+$pos, $pose-($pos+7));
			}


		}

		if ( $pos = strpos( $result['misc'], 'chrome/' )  )
		{
			$result['browser_name'] 	= 'chrome';

			$pose = strpos( $result['misc'], ' ', $pos+1 );
   			$result['browser_build']  = substr($result['misc'], 7+$pos, $pose-($pos+7));
     		$result['browser_version'] = $result['browser_build'];
		}

		$result['browser_name'] = strtolower($result['browser_name']);
		return $result;
	}

	//! return current database handle, establish connection if database connection is not open. You can ask for 3 types of access. This will let you specify a different write server and you can set a different pool for read.

	static function getDatabaseHandle($access_mode = 0)
	{
        global $CONFIG;
//        var_dump($access_mode);
		switch($access_mode)
		{
			case 0: //READ
				$dbHandle =new wcDB($CONFIG['dbDSNR']);
				break;
			case 1: //READ-WRITE
				$dbHandle=new wcDB($CONFIG['dbDSNW']);
				break;
			case 2: //OWNER
				$dbHandle=new wcDB($CONFIG['dbDSNO']);
				break;

		}
		if (isset($dbHandle))
			return $dbHandle;
		return null;
	}

	static function getGUID()
	{
		if (function_exists('com_create_guid') !== true)
		{
			$result = array();
			for ($i = 0; $i < 8; ++$i)
			{
				switch ($i)
				{
					case 3:
						$result[$i] = mt_rand(16384, 20479);
					break;
					case 4:
						$result[$i] = mt_rand(32768, 49151);
					break;
					default:
						$result[$i] = mt_rand(0, 65535);
					break;
				}
			}
			return vsprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', $result);
		}
		return trim(com_create_guid(), '{}');
	}



	//! return the current page language
	static function getLanguage($area='')
	{
		global $CONFIG;

		$lang = wcCore::cacheFetch(SITE.$area.'language');
		//echo "LANG: ".$lang."<br />";

		if (empty($lang) )
		{
			if ( $lang === false)  //read language from cache if possible
			{	// fallback on config file

				if (isset($CONFIG['language']))
					$lang=$CONFIG['language'];
				else	//fallback on english by default
					$lang=1;
			}
			// if language is defined in url, will override current value
			if (isset($_GET[$area.'language']))
			{
				$langSession=$_GET[$area.'language'];
			}
			else	// if not defined, did we already have the language defined in the session
			{
				$langSession=wcCore::varSession($area.'LANGUAGE');
			}

			if (is_null($langSession)===false)		// if language defined in session or url, override current language
				$lang = $langSession;

//			echo $lang;

		}
/*
		if (strlen($lang) == 0)
		{
			if ( $lang === false)  //read language from cache if possible
			{	// fallback on config file

				if (isset($CONFIG['language']))
					$lang=$CONFIG['language'];
				else	//fallback on english by default
					$lang='en';
			}
			// if language is defined in url, will override current value
			if (isset($_GET[$area.'language']))
			{
				$langSession=$_GET[$area.'language'];
			}
			else	// if not defined, did we already have the language defined in the session
			{
				$langSession=wcCore::varSession($area.'LANGUAGE');
			}

			if (is_null($langSession)===false)		// if language defined in session or url, override current language
				$lang = $langSession;

			//echo $lang;

		}
*/
		$_SESSION[$area.'LANGUAGE']=$lang;		// store the new default language in session

//		echo "LANG:".$lang;

		return $lang;
	}

    //!Return a list of available languages for the web site
    /*!
        By default, will return enabled languages, but you can override by changing the status value. Refer to the table schema for possible values
    */
    static function getLanguagesSite($status=1,$area='')
    {
        $db     = wcCore::getDatabaseHandle();
//        var_dump($db);
        $status= intval($status);

        if (is_object($db))
        {
	        if ($area=='admin')
                $result = $db->querySelect('LanguagesIso639','attrEnabledAdmin=\''.$status.'\' order by description','code,description');
//	            $result = $db->querySelect('languagesIso639','attrEnabledAdmin=\''.$status.'\' order by description','code,description');
	        else
	            $result = $db->querySelect('languagesIso639','attrEnabled=\''.$status.'\' order by description','code,description');
            return $result;
        }
        return null;
    }
    //!function that return an integer corresponding to this server name
    static function getServerID()
    {
	    return crc32(sha1(php_uname('n')));
    }

    //!function that return an unique Hex ID  (11 bytes)
    static function getUniqueIDHex()
    {
	    $s=uniqid(null,true);
        $s1=substr($s,0,14);
        $s2=substr($s,15);
        return "0x$s1$s2";
	}
    //!function that return an unique integer ID (big int)
    static function getUniqueIDInt()
    {
	    $s=uniqid(null,true);
        $s1=substr($s,0,14);
        $s2=substr($s,15);
        $num = base_convert("0x$s1$s2", 16,10);
        return $num;
	}

	//!function that return an unique string (12 bytes wide)
	function getUniqueIDString()
    {
        $s=uniqid(null,true);
        $s1=substr($s,0,14);
        $s2=substr($s,15);

		return base_convert($s1.$s2, 16, 36);
    }


	//!function that initialize the framework
	static function initialize()
	{
	 	if (defined('WCCORE')==true)
			die('wcCore already defined');

		define('WCCORE','1');

		// check for APC library support
		if (extension_loaded('APC'))
			define('EXT_APC','1');

		// check for MEMCACHE library support
		if (extension_loaded('memcache'))
			define('EXT_MEMCACHE','1');

		session_start();
		// TODO: remove later, this is disabling the caching of data
		//  apc_clear_cache();

		if (wcCore::cacheFetch('dbEnv')===false)     //check if configuration is in cache
		{
//			echo "<br />root: ".$_SERVER["DOCUMENT_ROOT"];
//			echo "<br />cwd: ".getcwd();
//echo "<br />Path: ".realpath(__DIR__.'/../') . '/config.php';
//			var_dump($GLOBALS);
//			include(realpath(__DIR__.'/../') . '/config.php');
		}
		if (defined('SITE')=== FALSE)
			die('WCCORE: UNDEFINED SITE');
//		echo SITE."<br />";
	}

	//! ** MAY GET REMOVED, DB DEPENDANT ** fetch one or multiple setting from the settings table, site independant..
	static function getSettings($var)
	{
		$db=wcCore::getDatabaseHandle();
        $items = $db->querySelect('Settings', "keyname in ($var)");
		$val = array();
		$ki=count($items);
		for ($k=0 ; $k < $ki ; ++$k)
		{
			$val[ $items[$k]['keyname'] ] = $items[$k]['keyvalue'];
		}
		return $val;
	}


	//!prepare database schema
	static function prepareDatabaseSchema($uninstall=false)
	{
		if ($uninstall === true) //uninstall
		{
		}
		else					 //install
		{

		}
	}

	//!redirect to a given url
	static function redirect($url='index.php',$type='js')
	{
		switch ($type)
		{
			case 'php':
				header('location: '.$url);
				break;
			default:
				echo sprintf("<script type=\"text/javascript\">window.location = '%s';</script>",$url);
				break;
		}
	}
	//!encode string into html entity
	static function stringEncode($data)
	{
		return htmlentities($data,ENT_QUOTES,"UTF-8");
	}
	//!decode string from html entity
	static function stringDecode($data)
	{
		return html_entity_decode($data,ENT_QUOTES,"UTF-8");
	}

	//!parse the $_GET for a value and return it
	static function varGet($name,$filter=-1)
	{
		if (isset($_GET[$name]))
		{
			if ($filter>=0)
			{
				//echo "<br />filter: $name - $filter";
			return filter_var($_GET[$name],$filter);
			}
			else
			return $_GET[$name];
		}
		return null;
	}
	//!parse the $_POST for a value and return it
	static function varPost($name,$filter=-1)
	{
		if (isset($_POST[$name]))
		{
			if ($filter>=0)
			{
				//echo "<br />filter: $name - $filter";
                return filter_var($_POST[$name],$filter);
			}
			else
			 return $_POST[$name];
		}
		return null;
	}
	//!parse the $_SESSION for a value and return it
	static function varSession($name)
	{
		if (isset($_SESSION[$name]))
			return $_SESSION[$name];
		return null;
	}

}
<?php
//! Database abstraction class
/*!
	Database abstraction class, currently using mysqli library
 * mysql error codes: http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html
 *
*/


class wcDB
{
    /// once a successful connection is made, this variable holds the mysqli handle
    public $dbHandle;
    /// user used to establish the connection
    var $dbUser;
    /// mysql serve host
    var $dbHost;
    /// name of the database holding your tables
    var $dbDatabase;
    /// password used to establish the connection
    var $dbPassword;
    /// database port number
    var $dbPort;
    /// socket for the connection
    var $dbSocket;
    /// last error
    var $dbLastError;

    /// default constructor, initialize variables, you must supply all values for a successful database connection
    function __construct($dsn)
    {
        $values = explode(';', $dsn);
        $this->dbHandle = null;
        if (count($values) >= 5) {
            $this->dbHost = $values[0];
            $this->dbDatabase = $values[1];
            $this->dbUser = $values[2];
            $this->dbPassword = $values[3];
            $this->dbPort = $values[4];
            if (count($values) >= 6)
                $this->dbSocket = $values[5];
        }
    }

    /// check if the last function returned an error
    function checkError()
    {
        if (mysqli_connect_errno())
            return mysqli_connect_error();
        return null;
    }

    /// connect to the database, if already connected will re-use the currect connection
    function connect()
    {
        if ($this->dbHandle === null) {

            if (strlen($this->dbSocket) > 0)
                $this->dbHandle = @mysqli_connect('', $this->dbUser, $this->dbPassword, $this->dbDatabase, 0, $this->dbSocket);
            else
                $this->dbHandle = @mysqli_connect($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbDatabase, $this->dbPort);

            if ($this->dbHandle === false) {
                $dbLastError = mysqli_connect_error();
//				echo $dbLastError;
            } else {
//				var_dump($this);
                $this->dbHandle->query('SET NAMES utf8');
                $this->dbHandle->set_charset('utf8');
                $dbLastError = '';
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
        return $this->dbHandle->real_escape_string($value);

    }

//! execute the specified query
    /*!
        you need to indicate the raw query that you want to execute, the results if any will be returned as an array
        <br /><br />ie:
        <br /> <em>$db->query("select * from News where id=5");</em>
    */
    function query($sql)
    {
        $arr = array();
        if ($this->Connect()) {
//			echo "<br />QUERY SQL: $sql<br />";
            $result = $this->dbHandle->query($sql);
            if ($result != false) {
                if (is_object($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $arr[] = $row;
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

        if ($this->Connect() != null) {
            $sql = sprintf("insert into %s_%s (%s) values (%s)", SITE, $table, $fields, $values);
//			echo "<br />$sql<br />";
            $this->dbHandle->query($sql);
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
    function querySelect($table, $condition = '1', $fields = '*', $limit = 0, $offset = 0)
    {
//		echo "wcdb: ". SITE."<br />";

        if (defined('SITE') === FALSE)
            die('wcdb: UNDEFINED SITE');

        $arr = array();
        if ($this->Connect()) {
            if ($this->dbHandle == NULL)
                return null;

            if ($condition == '1')
                $sql = sprintf("select %s from %s_%s", $fields, SITE, $table);
            else
                $sql = sprintf("select %s from %s_%s where %s ", $fields, SITE, $table, $condition);
            if ($limit > 0)
                $sql .= " limit $offset, $limit";
//			echo "<br />sql: $sql<br />";
            if ($result = $this->dbHandle->query($sql)) {
                if (is_object($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $arr[] = $row;
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
    function queryUpdate($table, $set, $condition = '1')
    {
        if ($this->Connect()) {
            $sql = sprintf("update  %s_%s set %s where %s", SITE, $table, $set, $condition);
//			echo "<br />UPDATE: {$sql}<br />";
            if (!$this->dbHandle->query($sql)) {
                printf("ERROR: %s<br />", $this->dbHandle->error);
            }
//			var_dump($this->dbHandle);
//			echo "<br />QUERYUPDATE<br />";
            return $this->dbHandle->affected_rows;
        }
        return 0;
    }
//! helper function for deleting values
    /*!
        you must supply the table name and the <em>where</em> condition, if no <em>where</em> condition is specified, 1 will be used, which means all records
        the table name will automatically prefixed with the SITE prefix from the config file. <br /><br />ie: <br /> <em>$db->queryDelete('News', 'id=4');</em>
    */
    function queryDelete($table, $condition = '1')
    {
        if ($this->Connect()) {
            $sql = sprintf("delete from %s_%s where %s", SITE, $table, $condition);
            $this->dbHandle->query($sql);
            return $this->dbHandle->affected_rows;
        }
        return 0;
    }
}

?>
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
?><?php
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
?><?php /** @noinspection ALL */

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

?><?php
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
<?php
//! HTML5 helper class
/*!
this class holds functions to help you render HTML5 web code
*/
class wcHtml5 //implements iHtml
{
	/// encode line break
	static function lineBreak()
	{
		return '<br>';
	}	
	/// encode any text with the specified symbol, you can also add options 
	static function encode($text,$symbol,$option='')
	{
		return "<{$symbol} {$option}>{$text}</{$symbol}>";
	}
	/// encode text as source code, you can also add options 
	static function encodeCode($text, $option='')
	{
		return "<pre $option>$text</pre>";
	}
	/// encode a CSS link (for HTML head)
	static function encodeCss($filename)
	{
		return sprintf("<link type=\"text/css\" href=\"%s\" rel=\"stylesheet\"  />\n", $filename);
	}
	/// encode a Javascript link (for HTML head)
	static function encodeJs($filename)
	{
		return sprintf("<script type=\"text/javascript\" src=\"%s\"></script>\n", $filename);
	}
	/// encode a hyperlink
	static function encodeLink($text,$link,$option='')
	{
		return sprintf('<a href="%s" %s>%s</a>',$link,$option,$text);
	}
	/// encode title with the specified size
	static function encodeHeader($text,$size=1)
	{
		return "<h{$size}>{$text}</h{$size}>";
	}
	/// encode a list
	static function encodeNavList($labelnav,$items,$optionsNAV,$optionsUL='',$optionsLI='')
	{
		$kc=count($items);
		$d="<nav {$optionsNAV}><h5>{$labelnav}</h5><ul {$optionsUL}>";
		for ($k=0;$k<$kc;$k++)
		{
			$d.="<li {$optionsLI}>".$items[$k].'</li>';
		}
		$d.='</ul></nav>';
		return $d;
	}
	/// encode a navigation list
	static function encodeList($items,$options='',$optionsli='')
	{
		$kc=count($items);
		$d="<ul {$options}>";
		for ($k=0;$k<$kc;$k++)
		{
			$d.="<li {$optionsli}>".$items[$k].'</li>';
		}
		$d.='</ul>';
		return $d;
	}
	/// encode a form with time
	static function encodeFormTime($fieldname,$timestamp=0,$inc_min=1)
	{
		if ($timestamp==0)
			$timestamp=time();
			
		$hour=date('H',$timestamp);
		$min=date('i',$timestamp);

		$code="<select name=\"{$fieldname}_hour\"";
		for ($k=1;$k<25;$k++)
		{
			if ($k==$hour)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_minute\"";
		for ($k=0;$k<60;$k+=$inc_min)
		{
			if ($k==$min)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		return $code;
	}
	/// encode a form with date
	static function encodeFormDate($fieldname,$timestamp=0,$inc_min=10)
	{
		if ($timestamp==0)
			$timestamp=time();

		$year=date('Y',$timestamp);
		$month=date('n',$timestamp);
		$day=date('j',$timestamp);
		$code="<select name=\"{$fieldname}_month\"";
		for ($k=1;$k<13;$k++)
		{
			if ($k==$month)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_day\"";
		for ($k=1;$k<32;$k++)
		{
			if ($k==$day)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_year\"";
		
		for ($k=$year;$k<$year+$inc_min;$k++)
		{
			if ($k==$year)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		return $code;
	}
	// encode a label with supplied text
	static function encodeLabel($id,$text,$options='')
	{
		return sprintf("<label for=\"%s\" %s>%s</label>", $id, $options, $text);
	}
	/// encode radio button
	static function encodeRadio($name,$value,$default='',$options='')
	{
		if ($value==$default)
			$checked=" checked=\"checked\"";
		else
			$checked='';
		$code=sprintf("<input type=\"radio\" name=\"%s\" value=\"%s\" %s %s/>", $name, $value , $checked, $options);	
		return $code;
	}
	/// encode input box
	static function encodeInputbox($name,$value,$options='')
	{
		$code=sprintf("<input type=\"text\" name=\"%s\" value=\"%s\" %s />", $name, $value, $options);
		return $code;
	}

	/// encode a table list
	static function encodeTable($items,$options='',$optionstr='', $optionstd='')
	{
		$kc=count($items);
		$d="<table {$options}>";
		for ($k=0;$k<$kc;$k++)
		{
			$d.="<tr {$optionstr}>";
			
			if (is_array($items[$k]))
			{
				$ks=count($items[$k]);
				
				foreach ($items[$k] as $i => $value) 
				{
					$d.="<td {$optionstd}>{$value}</td>";
				}
				$d.="<tr />";
			}
		}
		$d.='</table>';
		return $d;
	}
	
	///make sure than an URL is standardized, will be prefixed with http://
	static function enforceUrl($link)
	{
		$link = trim(strtolower( $link ));
		if (substr($link,0,7) != 'http://' )			
			$link = 'http://'. $link;
		return $link;
	}
	/// enforce a mailto hyperlink with supplied value
	static function enforceMailto($link)
	{
		$link = trim(strtolower($link));
		
		if ( ($link == '-') || ($link==''))
		{
			$link = '';
		}
		else
		{
			if (substr($link,0,7) != 'mailto:' )			
				$link = 'mailto:'. $link;
		}
		return $link;
	}

}
?>
<?php
//! Import class
/*! Import class
*/
class wcImport
{
	/// return an array of all hashed associated to a specified node
	static function getHashes($mode=0,$idnode=0)
	{
		global $CONFIG;


	 	$hashes=array();
        $id=$idnode;

        if ($id>0)
        {
        	$path=trim($CONFIG['pathSite']);
        	
        	
        	if (strlen($path)>0)
        	{
	            $path=sprintf("%sfiles/%d", $path ,$id);
    	        @mkdir($path,0775);
        	}
        
        }
        switch($mode)
        {
            case 2:     //from database, get hash only
            	$db	= wcCore::getDatabaseHandle();
				//$sql	= sprintf( 'SELECT hashMD5 FROM tkFiles WHERE idNode=%d;' , $id);
				$hashes = $db->querySelect('Files', 'idNode = '. $id, 'hashMD5');

                break;
            case 1:     //from disk
                break;
            default:    //from database
            
            	$db		= wcCore::getDatabaseHandle();
				$sql	= sprintf( 'SELECT * FROM tkFiles WHERE idNode="%d";' , $this->data['nodeContent']['idNode']);
				$hashes = $db->query($sql);
			    break;
        }
	}
		
	/// resize the image to a specific size and specify jpeg quality
	static function imageResize($filenameSrc,$filenameDest,$width=100,$height=100,$watermark='',$quality=75)
	{
		$info=getimagesize($filenameSrc);
		if ($watermark!='')
			$imgW=@imagecreatefrompng($watermark);
		$owidth=$width;
		if (count($info)>0)
		{
			switch($info[2])	
			{
				case IMG_GIF:
					$imgS = @imagecreatefromgif($filenameSrc);
					break;
				case IMG_JPG:
					$imgS = @imagecreatefromjpeg($filenameSrc);
					break;
                case IMG_JPEG:
                    $imgS = @imagecreatefromjpeg($filenameSrc);
                    break;
				case IMG_PNG:
					$imgS = @imagecreatefrompng($filenameSrc);
					break;
				case IMG_BMP:
					$imgS = @imagecreatefromwbmp($filenameSrc);
					break;
				case IMG_WBMP:
					$imgS = @imagecreatefromwbmp($filenameSrc);
					break;
				default:
					echo "File: $filenameSrc<br />";
					die('invalid media type');
					break;
			}
			if (isset($imgS))
			{
				$imgD = imageCreateTrueColor($width,$height);	
				$dst_x=0;
				$dst_y=0;
				if ($width==$height) //SQUARE
				{

					if ($info[0]>$info[1])  //W>H
					{
						$diff=($info[0]-$info[1])/2;  //800-600=200/2
						$src_w=$src_h=$info[1];
						$src_x=$diff;
						$src_y=0;
						if ($info[1]<$height)
						{
						  $src_y=($height-$info[1])/2;
						}
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );

					}
					if ($info[0]==$info[1])  //W==H
					{
						$src_w=$src_h=$info[0];
						$src_x=0;
						$src_y=0;
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
						
					}
					if ($info[0]<$info[1])  //W<H
					{
						$diff=($info[1]-$info[0])/2;  //800-600=200/2
						$src_w=$src_h=$info[0];
						$src_y=$diff;
						$src_x=0;
 		        		imageCopyResampled($imgD, $imgS,0,0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
					}
				}
				else
				{
					if ($info[0]>$info[1])  //W>H
					{
              
             			$nh=ceil(($width/$info[0])*$info[1]);	
  		        		imageCopyResampled($imgD, $imgS, 0, $height-$nh, 0  , 0 , $width , $nh , $info[0]  , $info[1] );
						
						
            /*
            if ($info[1] < $height) //image is shorter than expected size
  		        imageCopyResampled( $imgD, $imgS, 0, ($height-$info[1]) / 2 , 0  , 0 , $width , $info[1] , $info[0]  , $info[1] );
            else
  		        imageCopyResampled($imgD, $imgS, 0, 0, 0  , 0 , $width , $height , $info[0]  , $info[1] );
  		        */
					}
					if ($info[0]==$info[1])  //W==H
					{
						if ($info[0]>=$width)
						{
							
						}
						else
						{
							
						}
					}
					if ($info[0]<$info[1])  //W<H
					{
						$nw=ceil(($height/$info[1])*$info[0]);
		   		        imageCopyResampled($imgD, $imgS, ($width-$nw)/2 , 0, 0  , 0 , $nw , $height , $info[0]  , $info[1] );
					}
				}
				
//				$temp=sprintf("IMG: X:%d Y:%d  SRCX: %d SRCY:%d W:%d H:%d SRC_W:%d SRC_H:%d <br />", 0, 0, $src_x  , $src_y  , $width , $height , $src_w  , $src_h);
//				echo $temp;
				
//		        imageCopyResampled($imgD, $imgS, $dst_x, $dst_y, $src_x  , $src_y  , $width , $height , $src_w  , $src_h );
		        if (isset($imgW))
		        {	
		        	$infow=getimagesize($watermark);
//					imageCopy($imgD,$imgW,0,0,0,0,$infow[0],$infow[1]);		        	

					if ($info[0]<$info[1])  //W<H
					{
						$newh=($owidth*$infow[1])/$infow[0];
						imageCopyResampled($imgD,$imgW,0,0,0,0,$owidth,$newh,$infow[0],$infow[1]);		        	
					}
					else
						imageCopyResampled($imgD,$imgW,0,0,0,0,$width,($width*$infow[1])/$infow[0],$infow[0],$infow[1]);		        	
			        imageDestroy($imgW);
		        }
		        imageDestroy($imgS);				
		       
		        imageJPEG($imgD,$filenameDest,$quality);
						       
		        imageDestroy($imgD);
				if (file_exists($filenameDest))
					return true;
			}
		}
		return false;
	}



    ///import all the files specified in array $filenames into node id $idnode
    static function importFiles($idnode , $filenames )
    {
    	if (is_array($filenames) === true )
    	{
    		
    		$kf = count($filenames);

			echo "Processing $kf files<br />";

			if ($kf == 0 )
				return 0;

			$target_dir = sprintf( "%s/_%s/files/%d", dirname(getcwd() ), SITE, $idnode);
			
			@mkdir($target_dir, 0777, true);

			echo "Target dir [$target_dir]<br />";

			
			$processfiles=0;

	        $db		= wcCore::getDatabaseHandle();
			

			$hashes = wcImport::getHashes(2, $idnode); //FETCH EXISTING FILE HASHES FROM DATABASE SO THAT WE DONT IMPORT DUPLICATES IN THE SAME FOLDER    
        	
	        $ki = count($hashes);
	 		echo "<hr /><h2>Hashes</h2>";
    	    var_dump($hashes);
 			echo "<hr /><br />";
    		
    		echo $kf;
    		for ($k = 0 ; $k < $kf ; ++$k )
    		
    		{
				$filename  	= $filenames[$k];
				echo "<hr />- Processing $filename<br />";

		        if (file_exists($filename))
				{
			        $path_parts = pathinfo( $filename );
	        		$hash       = md5_file( $filename );
			        $size       = filesize( $filename ); 
			        
/*					$fn = substr( $path_parts['filename'], -2, 2 );
					
					if ($fn == '_t' )
						continue;
					if ($fn == '_m' )
						continue;
*/						
					$caption 	= strtolower ( filter_var($path_parts['filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES ) );
					$caption 	= strtr( $caption, '_!$.=,','      ' ); 
	        
				    $caption 	= preg_replace('/\[.*\]/', '', $caption);
				    $extension 	= strtolower ( $path_parts['extension']);
	
//					echo $caption."<br />";
	
			        $values=sprintf(" %d , now(), '%s', '%s' ,'%s' , %d", $idnode, $extension , $caption , $hash, $size );
			        $db->queryInsert('Files','idNode , dateAdded, fileExt ,  caption , hashMD5, fileSize', $values);
				
			        $id = $db->dbHandle->insert_id;
					if ($id > 0)
					{
				        echo "inserted id [$id]<br />";
						echo getcwd().'<br />';

				        $dest = sprintf ( "%s/%d.%s" , $target_dir, $id , $extension ); 
						echo "SRC <br />[$filename]<br />[$dest]<br /><br />";
			    		rename( $filename, $dest ); 
						if (file_exists( $dest ))
						{
							echo "- File copied successfully<br />";

						}
						else
						{
							echo "File copy failed..<br />";
		
							$temp = sprintf("idFile=%d",$id);
					        $db->queryDelete('Files', $temp);
						}
					} 
				}
				else
				{
					echo "File copy failed..<br />";
				}	
		        
    			
	   		}
    	}
    	return 0;
    }
    /// FILE INDEXING PROCESS
    static function indexMissingFiles()
    {
        $db	= wcCore::getDatabaseHandle();
        
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, looking for missing files');
        
        
        $items = $db->querySelect('Files', 'attrStatus=5','idFile,idNode,fileExt');

		global $CONFIG;
    	$path = $CONFIG['pathSite'];

		if (strlen(trim($path))==0)
		{
	    	$log->write("Fatal error, base path for the current site is empty, check config file under woxdata/<sitename>, variable is pathSite");
		}
    	else
    	{
	    	$log->write("Processing path:".$path);
	    	
	    	$ki=count($items);
	    	$missing=0;
	    	for ($k=0;$k < $ki ; ++$k)
	    	{
	    	   $file = sprintf("%sfiles/%s/%s.%s", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
	    	   if (file_exists($file) === false)
	    	   {
	    	       ++$missing;
	    	       $log->write("Missing: $file");
	//               $db->queryUpdate('Files','attrStatus=4','idFile='.$items[$k]['idFile']);
	           }
	    	}
			$log->write('admin interface, found '. $missing . ' missing file(s)');
			$log->write('admin interface, missing files scanning is done');
    	}
    	return $log->idBatch;

    }
    
    /// FILE INDEXING PROCESS
    static function indexFilesMetadata($max='0')
    {
		global $CONFIG;

    	$db	= wcCore::getDatabaseHandle();

		$log = new wcLog();
		$log->init();
		$log->write('admin interface, beginning metadata scanning');


    	$items = $db->querySelect('Files', 'attrStatus=0','idFile,idNode,fileExt');
    	$path = $CONFIG['pathSite'].'/files';
    	$ic = 0;	
    	$ki=count($items);
    	
    	
    	echo "Path: $path";
    	//var_dump($items);
    	
    	for ($k=0;$k < $ki ; ++$k)
    	{
    		$file = sprintf("%s/%s/%s.%s", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
    		//echo $file;
    		if (file_exists($file) === true)
    		{
    			$info  = wcFileSystem::getImageFileInfo( $file );
    		    
    		    $title  = trim( (isset($info['iptc']['title'])) 			? $info['iptc']['title'] : '' ); 
    		    $date   = trim( (isset($info['exif']['datetimeoriginal'])) ? $info['exif']['datetimeoriginal'] : ''); 
    		    
    		    $width  = trim( (isset($info['dimension']['width' ])) ? $info['dimension']['width' ] : 0);   
    		    $height = trim( (isset($info['dimension']['height'])) ? $info['dimension']['height'] : 0);   
    		    
    		    $type   = trim( (isset($info['type'])) ? $info['type'] : 'unknown'); 
    //            wcCore::debugVar($info);
    
    			$set='attrStatus=4';
    			if (strlen($title)>0)
    				$set.= sprintf( ", caption='%s'",  filter_var($title, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    			if (strlen($date)>0)
    				$set.= sprintf( ", dateAdded= timestamp('%s')", $date );
    			if ($width  != '0')
    				$set.= sprintf( ", attrInt1  = %s", $width );
    			if ($height != '0')
    				$set.= sprintf( ", attrInt2 = %s", $height );
    
    			if (strlen($type)>0)
    				$set.= sprintf( ", fileType='%s'",  filter_var($type, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    
    			if (strlen($type)>0)
    				$set.= sprintf( ", fileType='%s'",  filter_var($type, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES |  FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) );
    
    			
    		    $set .= ", metadata = '". addslashes(base64_encode(serialize($info)))."'";
    			
    			$ic += $db->queryUpdate('Files',$set ,'idFile='.$items[$k]['idFile']);
    
    			if ($max > 0)    //exit if specified limit is reached
    			 if ($ic >= $max)
    			     break;
    		}
    	}
		$log->write('admin interface, metadata import: '. $ic . ' file(s) processed');
		$log->write('admin interface, ending metadata scanning');
    	return $log->idBatch;
    }

    ///FILE INDEXING PROCESS
    static function indexGenerateThumbnails($max=0)
    {
		global $CONFIG;
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, thumbnail generation started');

        $db			= wcCore::getDatabaseHandle();
		$settings	= wcCore::getSettings("'image_thumbnailsize_width', 'image_thumbnailsize_height', 'image_mediumsize_width', 'image_mediumsize_height'");

		if (count($settings) != 4)
		{
			$msg='missing one of the following parameter in table <site>_Settings : '."'image_thumbnailsize_width', 'image_thumbnailsize_height', 'image_mediumsize_width', 'image_mediumsize_height'";
			$log->write($msg);

			die($msg);
		}
        
        $items = $db->querySelect('Files', 'attrStatus=4','idFile,idNode,fileExt');
    	$ki=count($items);

    	$path = $CONFIG['pathSite'].'/files';
   	
    	$ic=0;
    	for ($k=0;$k < $ki ; ++$k)
    	{
			$fileSrc 	 	= sprintf("%s/%s/%s.%s",	$path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
			$fileDstThumb 	= sprintf("%s/%s/%s_t.jpg",	$path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );
			$fileDstMedium	= sprintf("%s/%s/%s_m.jpg", $path, $items[$k]['idNode'], $items[$k]['idFile'], $items[$k]['fileExt'] );

			if (file_exists($fileSrc) === true)
			{
				//remove previous thumbnail and medium version
				@unlink($fileDstThumb);
				@unlink($fileDstMedium);

			    if (wcImport::imageResize($fileSrc, $fileDstThumb, intval( $settings['image_thumbnailsize_width'] ) , intval( $settings['image_thumbnailsize_height'] )) === true)
			    {
   					$log->write('thumbnail generation, generation of thumbnail: '. $fileDstThumb);

				    if (wcImport::imageResize($fileSrc, $fileDstMedium, intval( $settings['image_mediumsize_width'] ) , intval( $settings['image_mediumsize_height'] ), '',85) === true)
				    {
       					$log->write('thumbnail generation, generation of medium sized file: '. $fileDstMedium);
				    	//if both files were created successfully, we update the status in the database
				        $ic++;  	       
				        $db->queryUpdate('Files','attrStatus=5','idFile='.$items[$k]['idFile']);
				    }
				    else
       					$log->write('thumbnail generation, failed generation of medium sized file: '. $fileDstMedium);
				    	
			    }
   				else
   					$log->write('thumbnail generation, failed generation of thumbnail: '. $fileDstThumb);
   			}
			else
				$log->write('thumbnail generation: cannot find source file : '. $fileSrc);
    	}
  		$log->write('admin interface, thumbnail generation, '. $ic . ' file(s) processed');
		$log->write('admin interface, thumbnail generation ended');
    	return $log->idBatch;
    }
    /// rebuild order of the index
    static function indexRebuildOrder()
    {
        $db	= wcCore::getDatabaseHandle();
        
		$log = new wcLog();
		$log->init();
		$log->write('admin interface, indexRebuildOrder');
        
        $items = $db->querySelect('Files', '1 order by idNode','distinct idNode');
    	
    	$ki=count($items);
    	
    	$errors=0;
    	
    	for ($k=0;$k < $ki ; ++$k)
    	{
			$buffer = sprintf(" - Processing idNode [%d]<br />",  $items[$k]['idNode']);
			$log->write($buffer);

	        $itemsN = $db->querySelect('Files', 'idNode='.$items[$k]['idNode']. ' and attrStatus=5 and fileExt = "jpg" order by attrWeight' ,'idFile,attrWeight');
			$weight = 0 ;
	    	$kiN=count($itemsN);
	    	
	    	for ($kN=0;$kN < $kiN ; ++$kN)
    		{
    			if ( $itemsN[$kN]['attrWeight'] != $weight )
    			{
    				echo $itemsN[$kN]['idFile'].'/'.$itemsN[$kN]['attrWeight'].' -- Weight not matching , rebuilding<br />';
    				
    				$db->queryUpdate('Files','attrWeight='.$weight, 'idFile='.$itemsN[$kN]['idFile']);
    				
    				++$errors;
    			}
				++$weight;	
			}
		}
		$log->write('admin interface, errors: '.$errors);
		$log->write('admin interface, indexRebuildOrder is done');
    	return $log->idBatch;
    }

    
}

?><?php
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
?><?php

//! Members class
/*!
	The wcMember class handle all members actions (registration, modification, ...)
*/
class wcMember
{
    ///register the user in the system
    static function register($data)
    {
        /*
         * 0= success
         * 1= invalid data is passed, should be an array
         * 2= nickname is too short
         * 3= email address seems invalid
         * 4= password doesnt match
         * 5= unknown error
         * 6= cannot insert member into table because of index violation (like a duplicate member using already existing email address)
         * 7= email blank
         * 8= password1 is blank
         * 9= password2 is blank
         * 10=nickname or email already in use
         */

        if (is_array($data) == false)
            return 1;

        //empty local variables
        $password = $password1 = $password2='';
        $nickname = $email = '';

        //parse supplied array for specific keys
        foreach( $data as $key => $value)
        {
            switch ($key)
            {
                case 'nickname':                //extract nickname
                    $nickname = htmlspecialchars( trim( strip_tags( $value ) ) );
                    if (strlen ($nickname) < 2 )
                        return 2;
                    break;
                case 'email':                   //extract email
                    $value = htmlspecialchars(  filter_var(strip_tags($value), FILTER_VALIDATE_EMAIL)); //PHP53: sanitize string
                    if ($value === false)   //exit on error
                        return 3;
                    $email = trim ($value) ;    //trim variables of spaces
                    break;
                case 'password1':               //extract password1
                    $password1=trim($value);
                    break;
                case 'password2':               //extract password2
                    $password2=trim($value);
                    break;
                case 'language':                //extract language
                    $language=trim($value);
                    break;
                default:
                   //echo $key.' -- '.$value.'<br />';
                    break;
            }

        }
      

        //exit if email is blank
        if (strlen($email) == 0)
            return 7;

        //exit if password1 is blank
        if (strlen($password1) == 0)
            return 8;

        //exit if password1 is blank
        if (strlen($password2) == 0)
            return 9;

        //if passwords don't match, return an error
        if ($password1 == $password2)
            $password = $password1;
        else
            return 4;

        //TODO: should validate in the system settings if the registration process is allowed

        //no check are done for existing duplicate nickname or email because an unique index is already present
        //insterting a duplicate will fail and return an error
        $db = wcCore::getDatabaseHandle();
        $db->connect();

        //This will check if there is a member with same nickname or email address
        $condition = sprintf( "nickname = '%s' or email = '%s' " , $nickname, $email);
        $result = $db->querySelect('Members', $condition, 'count(*) as cnt');

        //on success, an array is returned
        if (is_array($result))  
        {
            $count = intval( $result[0]['cnt'] ) ;  //get number of existing rows matching
            if ( $count > 0)                        //already an entry, exiting with error
            {
                return 10;
            }
            else
            {   //create member
                $values = sprintf( "'%s','%s','%s','%s', '%s',now() ", $language, $nickname, $email, sha1($password), sha1(rand()) );
                $result = $db->queryInsert('Members', 'idLanguage, nickname, email, hash1, hash2 , dateCreated', $values);

                //TODO: Need to send email for validation, send ID + hash 2, create other field in table , make sure that account has ben validated in 48 hours
                //TODO: Need to do validation page

                //debug purpose, when new error
                //var_dump($result);
                if ($result==-1)
                {
                    switch ($db->dbHandle->errno)
                    {
                        case 1062:
                            return 6;
                        default:
                            return 5;
                            break;
                    }
                }
                else
                {
                    if ($result == 1)
                    {
                        return 0;
                    }
                }
            }
        }
        return 5;
    }
}
?><?php
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
?><?php
	
	
class wcNewsArticle
{
	
	
	function getArticle($id)
	{
		
	}
	
}

?><?php



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
?><?php

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
?><?php
// v1.0
//if (defined('WCCORE')===false)   die('ACCESS DENIED');
function getLatestCalendarEntries($params=array())
{
/* PARAMS:
	maxitem: max item count to return;
	days: max days between today and events
// affiche les jours de différence..	SELECT  datediff(FROM_UNIXTIME(dateStart), now()), v.* FROM vscCalendar v WHERE 1
	
*/	
	global $ctrl;
	
	$sql=sprintf("SELECT v.* , n.title, n.summary,n.idFile	FROM %sCalendar v 	JOIN %sNodes n ON v.idNode = n.idNode 	WHERE n.attrStatus =5 and n.codeLanguage='%s'	ORDER BY dateStart	LIMIT 0 , 10",SITE,SITE, $ctrl->Data['LANGUAGE']);
//	echo $sql;
	$db=wcCore::getDatabaseHandle();
	$data=$db->query($sql);
	$kdata=count($data);
	$result=array();
	for ($k=0;$k<$kdata;$k++)
	{

		$link	= sprintf("page-%d.html",$data[$k]['idNode']);
		$temp 	= date(date("m/d/Y", $data[$k]['dateStart']));
		$t		= utf8_encode(htmlspecialchars ($data[$k]['summary']));
		$title	= utf8_encode( htmlspecialchars( $data[$k]['title'])) ;

		$result[]= sprintf( '<span class="formatDate">%s</span>&nbsp; <span class="formatLink"><a href="%s" title="%s">%s</a></span><br /><span class="formatP">%s</span>', $temp, $link, $title , $title, $t );
	}
	return $result;
}


function getLatestNews($params=array())
{
	//PARAMS: idnode + number of news items to show
	/*
		TODO ADD SUPPORT FOR OLDER NEWS PAGE
	*/
	global $ctrl;
	$sql=sprintf("SELECT n . * , f.idNode FROM %sNews n LEFT JOIN vscFiles f ON n.idFile = f.idFile where codeLanguage='%s' and attrStatus=1 order by attrWeight desc, datePublishStart desc limit 0,10",SITE,$ctrl->Data['LANGUAGE']);
//	echo $sql;
	$db=wcCore::getDatabaseHandle();
	
	$patht=wcCore::CacheFetch(SITE.'urlStaticTheme');
	$path=wcCore::CacheFetch(SITE.'urlFiles');
	$news=$db->query($sql);
	$knews=count($news);
	$result=array();
	for ($k=0;$k<$knews;$k++)
	{
    $title='';
    $link='';
		$dlink=$news[$k]['link'];
		if (substr($dlink,0,1) == '[')
		{
		 	if ( substr($dlink,-1,1) == ']')
			{
				$dlinka=explode(':',substr($dlink,1,strlen($dlink)-2));
				if (count($dlinka)==2)
				{
				  switch($dlinka[0])
				  {
				    case 'page':
              $link=sprintf('<a href="page-%s.html">lien</a>',$dlinka[1],utf8_encode($news[$k]['title']) );	
			      
				      break;
				      default:
				        break;
				  }
				  
				}
			}
		}
    	$title=sprintf('<h4>%s</h4>',utf8_encode($news[$k]['title']) );
		$idfile=$news[$k]['idFile'];
		$data='';
		if ($idfile>0)
			$data.= sprintf('<img src="%s/%d/t-%d.jpg" alt="photo"/>',$path,$news[$k]['idNode'],$idfile);
		else
			$data.= sprintf('<img src="%snopic.jpg" alt="pas de photo" />',$patht);
    $data.=sprintf( '%s<p>%s</p><p>%s</p> ' ,$title,  utf8_encode($news[$k]['content']), $link);
		$result[]=$data;
	}
	return $result;
}

//////////////////

function plugin__getChildNodes($params=array())
{
    $data='';


	if (is_array($params))
	{
		if (isset($params['lng']))
		{
            $id	 = wcCore::varGet('id');
            $lng = $params['lng'];
            
			$items=array();
			
			$db=wcCore::getDatabaseHandle();
			$sql=sprintf("SELECT * FROM tk_nodes_%s  where idNodeParent=%d order by attrWeight, title", $lng, $id);
			$row=$db->query($sql);
			$krow=count($row);
			if ($krow>0)
			{
				$data.= '<ul>';
				for ($k=0;$k<$krow;$k++)
				{
/*
					switch($row[$k]['attrType'])
					{
						case 'gallery':
							$data.=sprintf("<li><a href=\"gallery-%d.html\">%s</a></li>", $row[$k]['idNode'] , wcCore::stringHtmlEncode( $row[$k]['title']) ) ;
							break;
						default:
							$data.=sprintf("<li><a href=\"page-%d.html\">%s</a></li>", $row[$k]['idNode'] , wcCore::stringHtmlEncode( $row[$k]['title']) ) ;
							break;
					}
*/
					$data.=sprintf("<li><a href=\"%s-%d.html\">%s</a></li>", $row[$k]['attrType'], $row[$k]['idNode'] , wcCore::stringHtmlEncode( $row[$k]['title']) ) ;

				}	
				$data.= '</ul>';		
			}
			
//			$data.= wcHtml::EncodeList();
		}
	}
	/*
		TODO ADD SUPPORT FOR OLDER NEWS PAGE
	*/
	return $data;
}


//////////////////








function plugin__getFilesLastAdded($params=array())
{
	$data='';
	$max=3;
	$path=wcCore::CacheFetch(SITE.'urlFiles');
	if (is_array($params))
	{
		if (isset($params['rel']))
		  $rel=sprintf(' rel="%s" ',$params['rel']);
    else
      $rel='';
		if (isset($params['max']))
		{
			$max=$params['max'];
			$db=wcCore::getDatabaseHandle();
			$sql=sprintf("SELECT idFile,idNode,caption FROM %sFiles  where fileExt='jpg' order by dateAdded desc limit 0,%d",SITE,$max);
			$row=$db->query($sql);
			$krow=count($row);
			for ($k=0;$k<$krow;$k++)
			{
			  $large=sprintf('%s/%d/m-%d.jpg',$path,$row[$k]['idNode'],$row[$k]['idFile']);
			  $thumb=sprintf('%s/%d/t-%d.jpg',$path,$row[$k]['idNode'],$row[$k]['idFile']);
				$data.=sprintf('<li><a href="%s" %s><img src="%s" alt=""/></a></li>',$large,$rel,$thumb);
			}			
		}
	}
	/*
		TODO ADD SUPPORT FOR OLDER NEWS PAGE
	*/
	return $data;
}

function plugin__snippet($params=array())
{
	$data='';
	$max=3;
	$id='';
	
	$width=800;
	$height=600;
	
	if (is_array($params))
	{
		if (isset($params['rel']))
		  $rel=sprintf(' rel="%s" ',$params['rel']);
    else
      $rel='';
		if (isset($params['w']))
      $width=$params['w'];
		if (isset($params['h']))
      $height=$params['h'];
    $data.=sprintf('<a href="snippet-%s.html" title="information" rel="clearbox(%s,,%s,,click)">%s</a> ',$params['id'],$width,$height,$params['text']);
  }
	return $data;
}

function plugin__relatedLinks($params=array())
{
	$data='';
	$max=3;
	$id=wcCore::varGet('id');
	
	if (is_array($params))
	{
  }
	if ($id)
	{
	
		$db=wcCore::getDatabaseHandle();
		$sql=sprintf("SELECT idChild,n.attrWeight,n.title, n.idPage FROM %sNodesRelation nr join %sNodes n on nr.idChild= n.idNode  WHERE idParent=%s order by attrWeight desc",SITE,SITE,$id);
    $row=$db->query($sql);
    $krow=count($row);
		$data.='<ul id="relatedLinks">';
		if ($krow>0)
    {
      $data.='<h2>Pages additionnelles</h2>';
     for ($k=0;$k<$krow;$k++)
    	$data.= sprintf('<li><a href="page-%s.html">%s</a></li>', $row[$k]['idChild'], $row[$k]['title']);
    }
/*		$sql=sprintf("SELECT idChild,n.attrWeight,n.title, n.idPage FROM %sNodesRelation nr join %sNodes n on nr.idChild= n.idNode  WHERE idParent=%s order by attrWeight desc",SITE,SITE,$id);
    $row=$db->query($sql);
    $krow=count($row);
    for ($k=0;$k<$krow;$k++)
    	$data.= sprintf('<li><a href="index.php?pid=%s&id=%s">%s</a></li>', $row[$k]['idPage'] , $row[$k]['idChild'], $row[$k]['title']);
*/
		$data.='</ul>';
	}
	return $data;
}
/*
SELECT idChild,n.attrWeight,n.title, n.idPage FROM vscNodesRelation nr join vscNodes n on nr.idChild= n.idNode  WHERE idParent=9 order by attrWeight desc
*/
function plugin__showFilesFromArray($files,$options)
{
	global $ctrl,$CONFIG;
	$data='';
  $kpage=0;
  $kcol=7;
  $maxperpage=21;
  /*
  if (is_array($params))
	{
		if (isset($params['kcol']))
		    $kcol=$params['kcol'];
		if (isset($params['rel']))
		  $rel=sprintf(' rel="%s" ',$params['rel']);
  }
  */
  $krows=count($files);
  
//  var_dump($files);
//  die;
  if ($krows>0)
  {
  		$data.='<div id="gallery-thumbs"><ul><li>';
  		for ($k=0;$k<$krows;$k++)
  		{
  		  if (($k%$kcol)==0)
  		    if ($k>0)
  		      $data.='</li><li>';
			  $large=sprintf('%sfiles/%d/m-%d.jpg',$CONFIG['pathSite'],$ctrl->info[0]['idNode'],$files[$k]['idFile']);
			  $thumb=sprintf('%sfiles/%d/t-%d.jpg',$CONFIG['pathSite'],$ctrl->info[0]['idNode'],$files[$k]['idFile']);
			  $c=utf8_encode($files[$k]['caption']);
				$data.=sprintf('<a href="%s" title="%s" %s><img src="%s" alt="%s"/></a>',$large,$c,$options,$thumb,$c);
//  			$data.=sprintf('<img src="%sfiles/%d/t-%d.%s" alt="%s"/>',$CONFIG['pathSite'],$rows[$k]['idNode'],$rows[$k]['idFile'],$rows[$k]['fileExt'],$rows[$k]['caption']);
      }    
  		$data.='</li></ul></div>';
  }
    
  return $data;
}
?><?php 
//! Class that manage the authentication
/*! Class that manage the authentication
*/
class wcSecurity
{
	/// this function return a boolean indicating if the user is currently authenticated
	static function isAuthenticated()
	{
		if ( isset( $_SESSION['user']['id'] ) )
			return true;
		return false;
	}
	
	/// this command will redirect the client if he his not currently authenticated
	static function requireAuthentication( $urlRedirect = 'login.php' )
	{
		if ( wcSecurity::isAuthenticated() == false )
			wcCore::redirect( $urlRedirect );
	}
	
	/// authenticate the client with the specified username and password (username is currently the email address)
	static function authenticate($user,$password)
	{
		//wcSecurity::logoff();
		$db 	= wcCore::getDatabaseHandle();
	
		$email	= trim( wcCore::stringEncode( strip_tags ( stripslashes  ( $user ) ) ) );
       
		


		$db		= wcCore::getDatabaseHandle();
		$data = $db->querySelect('members', sprintf( "email = '%s'  and hash1='%s' ", $email, sha1($password)), '*' , 1);  

        $kdata	= count($data);
        
        if ($kdata>0)
        {
			$_SESSION['user']['email'] =  $email;
			$_SESSION['user']['nickname'] = $data[0]['nickname'] ;
			$_SESSION['user']['id'] = $data[0]['id'] ;
			$_SESSION['user']['firstname'] = $data[0]['firstname'] ;
			$_SESSION['user']['lastname'] = $data[0]['lastname'] ;

			$sql=sprintf("email = '%s' and hash1='%s' ", $email, sha1($password) );
			$db->queryUpdate('Members', 'dateLastlogin=now()', $sql);
			
//			echo sprintf("{ success: true, nickname: \"%s\", lastlogin: \"%s\"}", $data[0]['nickname'],$data[0]['dateLastlogin']);
      	}
	}
	
	/// terminate the current connection, logoff and close our session
	static function logoff($urlRedirect='/')
	{
		unset( $_SESSION['user'] );
		if ( isset( $_COOKIE[ session_name() ] ) )   //kill session cookie
			setcookie(session_name(), '', time()-42000, '/');
		wcCore::redirect( $urlRedirect );
	}
	
	/// get the current username of our current session
	static function getUsername()
	{
		if ( isset( $_SESSION['user']['name'] ) )
			return $_SESSION['user']['name'];
		return null;	
	}
}

?>
<?php
//!
/*!
*/
class wcXHtml2 implements iHtml
{
	/// encode line break
	static function lineBreak()
	{
		return '<br />';
	}
	/// encode any text with the specified symbol, you can also add options 
	static function encode($text,$symbol,$option='')
	{
		return "<{$symbol} {$option}>{$text}</{$symbol}>";
	}
	/// encode text as source code, you can also add options 	
	static function encodeCode($text, $option='')
	{
		return "<pre $option>$text</pre>";
	}
	/// encode a CSS link (for HTML head)
	static function encodeCss($filename)
	{
		return sprintf("<link type=\"text/css\" href=\"%s\" rel=\"stylesheet\"  />\n", $filename);
	}
	/// encode a Javascript link (for HTML head)
	static function encodeJs($filename)
	{
		return sprintf("<script type=\"text/javascript\" src=\"%s\"></script>\n", $filename);
	}
	/// encode a hyperlink
	static function encodeLink($text,$link,$option='')
	{
		return sprintf('<a href="%s" %s>%s</a>',$link,$option,$text);
	}
	/// encode title with the specified size
	static function encodeHeader($text,$size=1)
	{
		return "<h{$size}>{$text}</h{$size}>";
	}
	/// encode a navigation list
	static function encodeNavList($labelnav,$items,$optionsNAV,$optionsUL='',$optionsLI='')
	{
		$kc=count($items);
		$d="<nl {$optionsNAV}><label>{$labelnav}</label><ul {$optionsUL}>";
		for ($k=0;$k<$kc;$k++)
		{
			$d.="<li {$optionsLI}>".$items[$k].'</li>';
		}
		$d.='</ul></nl>';
		return $d;
	}	
	/// encode a list
	static function encodeList($items,$options='',$optionsli='')
	{
		$kc=count($items);
		$d="<ul {$options}>";
		for ($k=0;$k<$kc;$k++)
		{
			$d.="<li {$optionsli}>".$items[$k].'</li>';
		}
		$d.='</ul>';
		return $d;
	}
	/// encode a form with time
	static function encodeFormTime($fieldname,$timestamp=0,$inc_min=1)
	{
		if ($timestamp==0)
			$timestamp=time();
			
		$hour=date('H',$timestamp);
		$min=date('i',$timestamp);

		$code="<select name=\"{$fieldname}_hour\"";
		for ($k=1;$k<25;$k++)
		{
			if ($k==$hour)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_minute\"";
		for ($k=0;$k<60;$k+=$inc_min)
		{
			if ($k==$min)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		return $code;
	}
	/// encode a form with date
	static function encodeFormDate($fieldname,$timestamp=0,$inc_min=10)
	{
		if ($timestamp==0)
			$timestamp=time();

		$year=date('Y',$timestamp);
		$month=date('n',$timestamp);
		$day=date('j',$timestamp);
		$code="<select name=\"{$fieldname}_month\"";
		for ($k=1;$k<13;$k++)
		{
			if ($k==$month)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_day\"";
		for ($k=1;$k<32;$k++)
		{
			if ($k==$day)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		$code.="<select name=\"{$fieldname}_year\"";
		
		for ($k=$year;$k<$year+$inc_min;$k++)
		{
			if ($k==$year)
				$code.="<option value=\"{$k}\" selected=\"selected\">{$k}</option>";
			else
				$code.="<option value=\"{$k}\">{$k}</option>";
		}
		$code.="</select>";
		return $code;
	}
	/// encode a label with supplied text
	static function encodeLabel($id,$text,$options='')
	{
		return sprintf("<label for=\"%s\" %s>%s</label>", $id, $options, $text);
	}
	/// encode radio button
	static function encodeRadio($name,$value,$default='',$options='')
	{
		if ($value==$default)
			$checked=" checked=\"checked\"";
		else
			$checked='';
		$code=sprintf("<input type=\"radio\" name=\"%s\" value=\"%s\" %s %s/>", $name, $value , $checked, $options);	
		return $code;
	}
	/// encode input box
	static function encodeInputbox($name,$value,$options='')
	{
		$code=sprintf("<input type=\"text\" name=\"%s\" value=\"%s\" %s />", $name, $value, $options);
		return $code;
	}
	
	///make sure than an URL is standardized, will be prefixed with http://
	static function enforceUrl($link)
	{
		$link = trim(strtolower( $link ));
		if (substr($link,0,7) != 'http://' )			
			$link = 'http://'. $link;
		return $link;
	}
	/// enforce a mailto hyperlink with supplied value	
	static function enforceMailto($link)
	{
		$link = trim(strtolower($link));
		
		if ( ($link == '-') || ($link==''))
		{
			$link = '';
		}
		else
		{
			if (substr($link,0,7) != 'mailto:' )			
				$link = 'mailto:'. $link;
		}
		return $link;
	}

}
?>
