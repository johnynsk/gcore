<?php
/*
## mysqlix package
## version: 1.0.20130520
*/ 
class mysqlix extends mysqli {
	public $trace=false;
	public $count=0,$querys=array(), $times=array();
	public function real_escape_string($string)
	{
		return $this->escape($string);
	}
	public function escape($string)
	{
		if(is_numeric($string))
			return $string;
		if(!is_array($string))
		{
			$string=parent::real_escape_string($string);
			return $string;
		}
		foreach($string as $k=>$v)
			$string[$k]=parent::real_escape_string($v);
		return $string;
	}
	public function offsetquery(string $query,$limit=30,$offset=1,$safe=true,$resultmode=MYSQLI_STORE_RESULT)
	{
		if(!is_numeric($limit)||!is_bool($safe))
			throw new exception('limit and page must be numeric, safe must be boolean');
		$offset=(int)$offset;
		if($offset<0)
			$offset=0;
		if($limit!=0)
			$query.=' LIMIT '.$offset.",".(int)$limit;
		if($safe)
			return $this->safequery($query,$resultmode);
		else
			return $this->query($query,$resultmode);
	}
	public function pagequery($query,$limit=30,$page=1,$safe=true,$resultmode=MYSQLI_STORE_RESULT)
	{
		if(!is_numeric($limit)||!is_bool($safe))
			throw new exception('limit and page must be numeric, safe must be boolean');
		if($page<1)
			$page=1;
		if($limit!=0)
			$query.=' LIMIT '.((int)(($page-1)*$limit)).",".(int)$limit;
		if($safe)
			return $this->safequery($query,$resultmode);
		else
			return $this->query($query,$resultmode);
	}
	public function safequery($query,$resultmode=MYSQLI_STORE_RESULT){
		$q=$this->query($query);
		
		if(!$q)
		{
			throw new exception('safequery problem ('.$this->errno.'): '.$this->error." [original query]: ".$query,520);
			return null;
		}
		return $q;
	}
	public function query($query, $resultmode = MYSQLI_STORE_RESULT) {
		if($this->trace)
		{
			$this->querys[$this->count]=$query;
			$this->last=$query;
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$tstart = $mtime;
		}

		$res=parent::query($query,$resultmode);

		if($this->trace)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$this->times[$this->count]=round($mtime - $tstart,8);
			$this->count++;
		}
		return $res;
	}
	public function getObjectArray($res,$oneline=true)
	{
		if($res->num_rows==0)
			return null;
		if($res->num_rows==1&&$oneline)
			return $res->fetch_object();
		$a=array();
		for($i=0;$i<$res->num_rows;$i++)
			$a[$i]=$res->fetch_object();
		return $a;
	}
	public function getAssocArray($res,$oneline=true)
	{
		if($res->num_rows==0)
			return null;
		if($res->num_rows==1&&$oneline)
			return $res->fetch_array();
		$a=array();
		for($i=0;$i<$res->num_rows;$i++)
			$a[$i]=$res->fetch_array();
		return $a;
	}
	public function getArray($res,$oneline=true)
	{
		return $this->getObjectArray($res,$oneline);
	}
	function __construct($host,$user,$pass,$db=null,$trace=false)
	{
		$res=parent::__construct($host,$user,$pass,$db);
		if($trace==true)
		{
			$this->trace=true;
			$this->query("SET PROFILING=1");
		}
		return $res;
	}
};
