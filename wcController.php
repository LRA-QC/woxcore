<?php
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
		$this->DataChunk	= array(); //all variables declared in this will be reprocessed a second time, to allow templating nesting 
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
			$path=sprintf('./views/%s-%s.%s', $this->Name, $this->Template, $this->Type );
			//$path=sprintf('./views/%s-%s-%s.php', $this->Name, $this->Template, $this->Type );
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
		foreach(	$this->DataChunk as $chunk )
			$this->Data[$chunk] = $this->processDataChunk( $this->Data[$chunk] );
		$this->processData();
		// DISABLE CACHE WHILE DEVELOPING
		// $this->saveCache();
		echo $this->Body ;
	}


	//!this function will search for specific keywords in the template, when keywords are found they are looked up in the $Data array, if they are found, they will be replaced by the actual value
	public function processDataChunk($chunk)
	{
		$res=$chunk;

		foreach($this->Data as $key => $value)
		{
			$search='<!--[-'.$key.'-]-->';
			$res=str_replace( $search ,$value, $res);
		}
		return $res;
	}

	//!this function will search for specific keywords in the template, when keywords are found they are looked up in the $Data array, if they are found, they will be replaced by the actual value
	public function processData()
	{
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
