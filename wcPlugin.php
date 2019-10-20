<?php
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
?>