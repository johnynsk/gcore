<?php
class _auth extends _std_object{
	public function checkSign($params,$api_key=null,$api_sig=null)
	{
		if(isset($api_key))
			$params["api_key"]=$api_key;
		if(isset($api_sig))
			$params["api_sig"]=$api_sig;
		core::check(array(
			"api_sig"=>array(true,'string'),
			"api_key"=>array(true,'string')),$params);
		$this->getApp($params["api_key"]);
		$sign=$this->makeSign($params,$this->params->api_secret);
		if($sign!=$params["api_sig"])
			throw new exception("signature is bad, authenticate problem");
		return true;
	}
	public function getApp($api_key)
	{
		$id=$this->_search("api_key",$api_key);
		if(!$id)
			throw new exception("unknown client");
		$this->_load($id);
		return $id;
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
		return $sign;
	}
	function __construct($db)
	{
		$this->type='system';
		$this->db=$db;
	}
}
