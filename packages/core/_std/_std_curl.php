<?php
class _std_curl{
	public $proxy;
	public $headers;
	public $cookies; //netscape cookie file format
	public $post=false;
	public $auth='';
	protected $curl;
	protected function post($url,$post=null,$timeout=null)
	{
		$old=$this->post;
		$this->post=true;
		$ret=$this->exec($url,$post,$timeout);
		$this->post=$old;
		return $ret;
	}
	protected function get($url,$timeout=null)
	{
		$old=$this->post;
		$this->post=false;
		$ret=$this->exec($url,null,$timeout);
		$this->post=$old;
		return $ret;
	}
	protected function exec($url,$post=null,$timeout=null)
	{
		if(empty($this->curl))
			$this->curl=curl_init();
	
			if(isset($this->proxy))
			{
				curl_setopt($this->curl,CURLOPT_HTTPPROXYTUNNEL,true);
				curl_setopt($this->curl,CURLOPT_PROXY,$this->proxy);
			}
			if(isset($this->useragent))
			{
				curl_setopt($this->curl,CURLOPT_USERAGENT,$this->useragent);
			}
			curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($this->curl,CURLOPT_BINARYTRANSFER,true);
			curl_setopt($this->curl,CURLOPT_AUTOREFERER,true);
			curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,true);
			curl_setopt($this->curl,CURLOPT_MAXREDIRS,5);
		
		if(is_array($this->headers))
			curl_setopt($this->curl,CURLOPT_HTTPHEADER,$this->headers);
		if(is_string($this->cookies))
			curl_setopt($this->curl,CURLOPT_COOKIEFILE,$this->cookies);
		if(isset($timeout))
			curl_setopt($this->curl,CURLOPT_CONNECTTIMEOUT,$timeout);
		if($this->post)
		{
			curl_setopt($this->curl,CURLOPT_POST,1);
			curl_setopt($this->curl,CURLOPT_POSTFIELDS,$post);
		}
		if(!empty($this->auth))
			curl_setopt($this->curl,CURLOPT_USERPWD,$this->auth);
		curl_setopt($this->curl,CURLOPT_URL,$url);
		$res=curl_exec($this->curl);
		if(curl_errno($this->curl))
			throw new exception(curl_error($this->curl),curl_errno($this->curl));
		return $res;
	}
}
