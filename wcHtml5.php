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
