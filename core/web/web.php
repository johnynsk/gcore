<?php
class web{
	static function getblock($element)
	{
		if(!is_callable("getblock"))
			throw new exception("global function getblock not defined");
		return getblock($element);
	}
	static function escapehtml($str)
	{
		$str=str_replace("<","&lt;",$str);
		$str=str_replace(">","&gt;",$str);
		return $str;
	}
	static function prepare($html,$data=null,$object=null)
	{
		if(is_object($object))
		{
			# simply execute function
			preg_match_all("/%exec\(([0-9a-z_]+)\);/",$html,$m);
			for($i=0;$i<count($m[0]);$i++)
			{
				if(is_callable(array($object,$m[1][$i])))
				{
					$res=call_user_func(array($object,$m[1][$i]));
					$html=str_replace("%exec(".$m[1][$i].");",$res,$html);
				}
			}
			preg_match_all("/%exec\(([0-9a-z_]+),([0-9a-z_\-]+)\);/",$html,$m);
			for($i=0;$i<count($m[0]);$i++)
			{
				if(is_callable(array($object,$m[1][$i])))
				{
					$res=call_user_func(array($object,$m[1][$i]),$m[2][$i]);
					$html=str_replace("%exec(".$m[1][$i].",".$m[2][$i].");",$res,$html);
				}
			}
			# execute function with variables
			preg_match_all("/%exec\(([0-9a-z_]+),\\$([0-9a-z_\-]+)\);/",$html,$m);
			for($i=0;$i<count($m[0]);$i++)
			{
				if(is_callable(array($object,$m[1][$i]))&&isset($data[$m[2][$i]]))
				{
					$res=call_user_func(array($object,$m[1][$i]),$data[$m[2][$i]]);
					$html=str_replace("%exec(".$m[1][$i].",".$m[2][$i].");",$res,$html);
				}
			}
		}
		if(is_array($data))
		{
			foreach($data as $key=>$value)
				if(is_string($value)||is_numeric($value))
					$html=str_replace("%".$key.";",$value,$html);
		}
		return $html;
	}
	function moduleexists($params)
	{
		if(!isset($params["module"]))
			$params["module"]="index";
		switch($params["module"])
		{
			case "index":
				return true;
			default:
				throw new exception("unknown module");
		}
	}
	function __construct()
	{
		$this->core=core::getObject("core");
	}
	static function navigation($link,$total,$limit,$from=0)
	{
		$cur=(int)($from/($limit))+1;
		$max=(($total-1)/($limit))+1;

		if((int)$max!=$max)
			$max=(int)$max;
		else
			$max=(int)$max;

		$range=5;

		if($cur>$max)
			$cur=$max;
		if($cur<1)
			$cur=1;
		$from=$cur-$range;
		$to=$cur+$range;
		$left=$from-$range-1;
		$right=$to+$range+1;
		if($from<1)
			$from=1;
		if($to>$max)
			$to=$max;
		if($left<1)
			$left=1;
		if($right>$max)
			$right=$max;
	
		$leftdot=0;
		$rightdot=0;
	
		$html=<<<html
<a href="%link;" class="pagenav %class;">%text;</a>
html;
		$html=web::prepare($html,array("link"=>$link));
		$tmp='';
		if($from>1)
		{
			$tmp.=web::prepare($html,array("page"=>1,"class"=>"pagenav_first","text"=>1));
			if($left!=$from&&$left!=($from-1))
			{
				$jumpto=$left*$limit;
				$tmp.=web::prepare($html,array("page"=>$left,"class"=>"pagenav_dotted","text"=>"..."));
			}
		}

		for($i=$from;$i<=$to;$i++)
		{
			$jumpto=$i*$limit;
			if($i==$cur)
				$tmp.=web::prepare($html,array("page"=>$i,"class"=>"pagenav_current","text"=>$i));
			else
			{
				if($i==1)
					$tmp.=web::prepare($html,array("page"=>$i,"class"=>"pagenav_first","text"=>$i));
				else
					if($i==$max)
						$tmp.=web::prepare($html,array("page"=>$i,"class"=>"pagenav_last","text"=>$i));
					else
						$tmp.=web::prepare($html,array("page"=>$i,"class"=>"","text"=>$i));
			}
		}
	
		if($to<$max)
		{
			if($to!=$right&&$right!=($to+1))
			{
				$jumpto=$right*$limit;
					$tmp.=web::prepare($html,array("page"=>$right,"class"=>"pagenav_dotted","text"=>"..."));
			}
			$jumpto=$max*$limit;
				$tmp.=web::prepare($html,array("page"=>$max,"class"=>"pagenav_last","text"=>$max));
		}
		return $tmp;

	}

}
