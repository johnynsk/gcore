<?php
class developer{
	function test($params)
	{
		if(!isset($params["string"]))
			return "Authorization success!";
		return $params["string"];
	}
	function time($params)
	{
		return date("d.m.Y H:i:s",$params["timestamp"]);
	}
	function md5($params)
	{
		return md5($params["string"]);
	}
	function ip2long($params)
	{
		return ip2long($params["ip"]);
	}
	function genSignature($params)
	{
		$secret=$params["api_secret"];
		unset($params["api_secret"]);
		$params["method"]=$params["method_name"];
		unset($params["method_name"]);
		$res=$this->auth->makeSign($params,$secret);
		return (object)array(GENERIC_CORE_ATTR=>$res);
	}
	function __construct($db=null,$id=null)
	{
		$this->db=$db;
		$this->type='song';
		if(isset($id))
			$this->id=$id;
		$this->auth=core::getObject('_std_auth');
	}
}

