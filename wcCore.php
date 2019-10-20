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
