<?php
class gcore_client{
	public $apphost;
	public $proxy;
	public $headers;
	public $cookies;
	public $post=false;
	public $internal=true;
	public $auth='';

	public $api_key='';
	public $api_secret='';
	public $signame="api_sig";
	private $curl;

	static function sign($params,$postfix='',$prefix='')
	{
		$str=$prefix;
		foreach($params as $key=>$value)
			$str.=$key.$value;
		$str.=$postfix;
		$sign=md5($str);
		return (object)array("sign"=>$sign,"string"=>$str);
	}

	static function makeSign($params,$secret)
	{
		ksort($params);
		unset($params["api_sig"]);
		unset($params["format"]);
		unset($params["callback"]);
		return self::sign($params,$secret);
	}

	public function __construct($internal=false,$apphost='',$post=false, $noexception=false)
	{
		$this->internal=$internal;
		$this->apphost=$apphost;
		$this->post=$post;
		$this->noexception=$noexception;
	}

	public function exec($url,$post=null,$timeout=null)
	{
		if(!isset($this->curl))
			$this->curl=curl_init();

		if($this->post)
		{
			curl_setopt($this->curl,CURLOPT_POST,1);
			curl_setopt($this->curl,CURLOPT_POSTFIELDS,$post);
		}

		if(isset($this->proxy))
		{
			curl_setopt($this->curl,CURLOPT_HTTPPROXYTUNNEL,true);
			curl_setopt($this->curl,CURLOPT_PROXY,$this->proxy);
		}

		if(isset($this->useragent))
			curl_setopt($this->curl,CURLOPT_USERAGENT,$params["useragent"]);

		curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->curl,CURLOPT_BINARYTRANSFER,true);
		curl_setopt($this->curl,CURLOPT_AUTOREFERER,true);
		curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($this->curl,CURLOPT_MAXREDIRS,5);
		curl_setopt($this->curl,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
		if(is_array($this->headers))
			curl_setopt($this->curl,CURLOPT_HTTPHEADER,$this->headers);
		if(is_string($this->cookies))
			curl_setopt($this->curl,CURLOPT_COOKIEFILE,$this->cookies);
		if(isset($timeout))
			curl_setopt($this->curl,CURLOPT_CONNECTTIMEOUT,$timeout);
		if(!empty($this->auth))
			curl_setopt($this->curl,CURLOPT_USERPWD,$this->auth);

		curl_setopt($this->curl,CURLOPT_URL,$url);

		$res=curl_exec($this->curl);
		if(curl_errno($this->curl))
			if(!$this->noexception)
			throw new exception(curl_error($this->curl),curl_errno($this->curl));
		return $res;
	}
	public function prepare_call($params,$test=null)
	{
		if($this->internal)
			if(!is_string($params["method"])&&!is_numeric($params["method"]))
				if(!$this->noexception)
					throw new exception("method must be string");

		if($this->internal)	//internal 
		{
			unset($params["callback"]);
			unset($params["format"]);
		}
		if(!empty($this->api_key))
		{
			$params["api_key"]=$this->api_key;
		}

		if(!empty($this->api_secret)) //may be used by facebook, last.fm and other api's. used md5 principle;
		{
			$params2=$params;
			unset($params2["callback"]);
			unset($params2["format"]);
			$params[$this->signame]=self::makeSign($params2,$this->api_secret)->sign;
		}

		$method=$params["method"];

		if($this->internal)
			unset($params["method"]);

		if($this->internal)
		{
			$params["format"]="json";
			$url=$this->apphost."/method/".$method;
		}
		else
			$url=$this->apphost;

		if($this->post)
		{
			$res=(object)array(
				"url"=>$url,
				"get"=>null,
				"post"=>$params,
				"method"=>$method);
		}else{
			$get='';
			foreach($params as $key=>$value)
			{
				if(!empty($get))
					$get.='&';
				$get.=urlencode($key)."=".urlencode($value);
			}
			$res=(object)array(
				"url"=>$url."?".$get,
				"get"=>$get,
				"post"=>null,
				"method"=>$method);
		}
		return $res;
	}
	public function call($params,$test=null)
	{
		if(!is_string($params["method"])&&!is_numeric($params["method"]))
			if(!$this->noexception)
				throw new exception("method must be string");
		$data=$this->prepare_call($params,$test);
		if($test==1||(is_bool($test)&&$test==true))
			return $data;
		$res=$this->exec($data->url,$data->post,10);
		if($test==2)
			return $res;
		$res=json_decode($res);
		if(json_last_error())
			if(!$this->noexception)
				throw new exception('JSON parse error',json_last_error());
		return $res;
	}
}
