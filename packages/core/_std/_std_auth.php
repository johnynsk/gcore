<?php
class _std_auth{
	public $params;
	protected $path;
	protected $clients;
	public function checkSign($params,$api_key=null,$api_sig=null,$noexception=null)
	{
		if(isset($api_key))
			$params["api_key"]=$api_key;
		if(isset($api_sig))
			$params["api_sig"]=$api_sig;
		core::check(array(
			"api_sig"=>array(true,'string'),
			"api_key"=>array(true,'string')),$params);
		$this->getApp($params["api_key"]);
		$res=$this->makeSign($params,$this->params->api_secret);
		$sign=$res->sign;
		if($sign!=$params["api_sig"])
			if(!isset($noexception)||!$noexception)
				throw new exception("signature is bad, authenticate problem ".$res->string);
		return null;
		return true;
	}
	public function getApp($api_key)
	{
		$id=$this->_search("api_key",$api_key);
		if(!isset($id[0]))
			throw new exception("unknown client");
		$this->params=$id[0];
		return $id[0];
	}
	static public function makeSign($params,$secret)
	{
		ksort($params);
		unset($params["api_sig"]);
		unset($params["format"]);
		unset($params["callback"]);
		$str='';
		foreach($params as $key=>$value)
			$str.=$key.$value;
		$str.=$secret;
		$sign=md5($str);
		return (object)array("sign"=>$sign,"string"=>$str);
	}
	function _remove($api_key)
	{
		foreach($this->clients as $k=>$v)
		{
			if(isset($v->api_key)&&$v->api_key==$api_key)
				unset($this->clients[$k]);
		}
		$this->_save();
	}
	function _create($params)
	{
		core::check(array(
			"api_key"=>array(true,"string"),
			"api_secret"=>array(true,"string")),$params);
		$this->clients[]=(object)$params;
		return true;
	}
	function _search($key,$value=null,$limit=null,$page=null)
	{
		$res=array();
		$match=0;
		if(!isset($limit))
			$limit=1;
		if(!isset($page))
			$from=0;
		else
			$from=$page*$limit;
		foreach($this->clients as $k=>$v)
		{
			if(isset($v->{$key})&&$v->{$key}==$value)
			{
				if($match>=$from&&$match<=$from+$limit)
					$res[]=$v;
				$match++;
			}
		}
		return $res;
	}
	public function _save($path=null)
	{
		if(isset($path))
			$this->path=$path;
		$s=json_encode($this->clients);
		file_put_contents($this->path,$s);
		return true;
	}
	public function _load($path=null)
	{
		if(isset($path))
			$this->path=$path;
		if(!file_exists("File does not exists!"));
		$f=file_get_contents($this->path);
		$j=json_decode($f);
		if(json_last_error())
			throw new exception("Parse JSON error",json_last_error());
		$this->clients=$j;
		return true;
	}
	function __construct()
	{
		$core=core::getObject('core');
		$tmp=$core->getConfig();
		$tmp=$tmp->packages->_std_auth;
		if(!isset($tmp->params)||!isset($tmp->params->file_clients))
			throw new exception("parameter 'file_clients' must be set in settings of package _std_auth");
		$this->path=$tmp->params->file_clients;
		$this->_load();
	}
}
