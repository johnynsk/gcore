<?php
/*
 * package core
 * written by johny (info@johnynsk.ru)
 * core version 1.0.20130520
 * see documentation
 */
class core{
	public $config;
	public $safemode=false;
	public $trace=false;
	static $objects=array();
	protected $client;
	protected $secret;
	public $api_client=false;
	public $api_auth=false;
	static $api_version=0.9;
	static $version="1.1.20140416";
	static $dir="";
	function __construct($dirname="")
	{
		self::$dir=$dirname;
	}
	public function setLimit($params)
	{
		if(!isset($params["limit"])||$params["limit"]<=0)
		{
			if(isset($this->config->limit_default))
				$params["limit"]=$this->config_limit_default;
			else
				$params["limit"]=30;
		}else{
			if(isset($this->config->limit_max)&&$params["limit"]>$this->config->limit_max)
				$params["limit"]=$this->config->limit_max;
		}
		return $params;
	}
	static function regObject($object,$name,$noexception=null)
	{
		if(!is_array(self::$objects))
			self::$objects=array();
		if(isset(self::$objects[$name]))
			if(!isset($noexception)||!$noexception)
				throw new exception("object '$name' has already registred",0);
			else
				return null;
		self::$objects[$name]=$object;
		return true;
	}
	static function getObject($name,$noexception=null)
	{
		if(!is_array(self::$objects))
			self::$objects=array();
		if(!isset(self::$objects[$name]))
			if(!isset($noexception)||!$noexception)
				throw new exception("object '$name' is unavailable");
			else
				return null;
		return self::$objects[$name];
	}
	static function val($key,$val=null)
	{
		if(!isset(self::${$key}))
			throw new exception("unknown key");
		if(!isset($val))
			return self::${$key};
		self::${$key}=$val;
		return self::${$key};
	}
	static function replace($params,$data,$reverse=false)
	{
		foreach($params as $key=>$value)
			if(!$reverse)
				$data=str_replace($key,$value,$data);
			else
				$data=str_replace($value,$key,$data);
		return $data;
	}
	static function setempty($params,&$data)
	{
		if(!isset($params)||!is_array($params)||!isset($data)||!is_array($data))
			throw new exception('both arguments must be array');
		foreach($params as $key=>$value)
		{
			if(is_numeric($key))
			{
				if(!isset($data[$value]))
					$data[$value]='';
			}else{
				if(!isset($data[$key]))
					if(isset($value))
						$data[$key]=$value;
					else
						$data[$key]='';
			}
		}
		return $data;
	}
	
	static function checktype($data,$type,$expr='//')
	{
		switch($type)
		{
			case 'string':
				if(!is_string($data)&&!is_numeric($data))
					return false;
				break;
			case 'uint':
				if((int)$data<0)
					return false;
			case 'int':
				if(!is_numeric($data))
					return false;
				break;
			case 'ufloat':
				if((float)$data<0)
					return false;
			case 'float':
				if(!is_numeric($data))		
					return false;
				break;
			case 'array':
				if(!is_array($data))
					return false;
				break;
			case 'object':
				if(!is_object($data))
					return false;
				break;
			case 'bool':
				if(!is_bool($data)&&!is_numeric($data)&&!is_string($data))
					return false;
				if(is_string($data))
					if(strtolower($data)!="true"&&strtolower($data)!="false"&&$data!="0"&&$data!="1")
						return false;
				break;
			case 'enum':
				if(!is_array($expr))
					throw new exception("allowed values array miss");
				foreach($expr as $value)
					if($data==$value)
						return true;
				return false;
				break;
			case 'regexp':
				if(!preg_match($expr,$data))
					return false;
				break;
			case 'hex':
				return self::checktype($data,'regexp','/^[a-f0-9]+$/i');
			case 'oct':
				return self::checktype($data,'regexp','/^0[0-7]+$/i');
			default:
				throw new exception("unknown type for check data",16);
		}
		return true;
	}
	
	static function check($params,&$data,$sanitize=false)
	{
		if(!is_array($params))
			throw new exception('params must be array',516);
		if(!is_array($data))
			throw new exception('data must be array',517);

		foreach($params as $key=>$opt)
		{
			if(!is_array($opt))
				throw new exception("params for $key must be array",1);
			
			if(!isset($opt[0])||!isset($opt[1]))
				throw new exception("params for $key must contain 2 items",2);

			if($opt[1]=="regexp"||$opt[1]=="enum")
			{
				if(!isset($opt[2]))
					throw new exception("params for $key must contain 3 items, specified by type",5);
				if($opt[0]&&(!isset($data[$key])||!self::checktype($data[$key],$opt[1],$opt[2])))
					throw new exception("field '$key' must be ".$opt[1],3);
				if(!$opt[0]&&isset($data[$key])&&!self::checktype($data[$key],$opt[1],$opt[2]))
					if($sanitize&&strlen($data[$key])==0)
						unset($data[$key]);
					else
						throw new exception("field '$key' should be ".$opt[2],4);
				}else{
				if($opt[0]&&!isset($data[$key])&&isset($opt[2])&&isset($data[$opt[2]]))
					continue;
				if($opt[0]&&(!isset($data[$key])||!self::checktype($data[$key],$opt[1])))
					throw new exception("field '$key' must be ".$opt[1],3);
				if(!$opt[0]&&isset($data[$key])&&!self::checktype($data[$key],$opt[1]))
					if($sanitize&&strlen($data[$key])==0)
						unset($data[$key]);
					else
						throw new exception("field '$key' should be ".$opt[1],4);

			}
		}
		return true;
	}
	static function getParams($pkg=null)
	{
		$core=core::getObject("core");
		if(!isset($core->config->packages->{$pkg}))
			throw new exception("undefined package");
		if(!isset($core->config->packages->{$pkg}->params))
			return (object)array();
		return $core->config->packages->{$pkg}->params;
	}
	public function getConfig($tree=null)
	{
		if(isset($tree))
			if(isset($this->config->packages->{$tree})&&isset($this->config->packages->{$tree}->params))
				return $this->config->packages->{$tree}->params;
			else
				return null;
			
		return $this->config;
	}
	public function callMethod($name,$params)
	{
		$name=$this->parseMethodName(array("method"=>$name));

		if($name['type']!='method')
			throw new exception("name of method must be same as scheme [ServiceName].[MethodName]",17);

		$package=$name['service'];
		$method=$name['name'];

		$pkg=&$this->config->apiTree;
		if(!isset($pkg->{$package}))
			throw new exception("Package '{$package}' not defined",0);
		if(!isset($pkg->{$package}->{$method}))
			throw new exception("Method '{$method}' is not declarated like public method of service {$package}",0);
		
		$mtree=&$pkg->{$package}->{$method};
		$ignoreauth=false;
		if(isset($this->config->params->ignore_auth)&&is_array($this->config->params->ignore_auth))
		{
			if(isset($_SERVER)&&isset($_SERVER["REMOTE_ADDR"])&&in_array($_SERVER["REMOTE_ADDR"],$this->config->params->ignore_auth))
				$ignoreauth=true;
		}
		if(isset($this->config->params->ignore_auth)&&isset($_ENV["LOGNAME"]))
			$ignoreauth=true;
		if(isset($mtree->auth)&&$mtree->auth==true&&$this->api_auth&&!$ignoreauth)
		{
			$authorize=true;
			if(isset($this->config->params)&&!empty($this->config->params->authorize_package))
				$auth=self::getObject($this->config->params->authorize_package);
			else
				$auth=self::getObject("_std_auth");
			if(!$auth)
				throw new exception("authorize package not initialized");
			$this->mergeDefaultParam($package,$method,"api_key");
			$this->mergeDefaultParam($package,$method,"api_sig");
		}

		$o=self::getObject($package);
		if(!$o)
			throw new exception("default package example not defined for ".$package);

		if(!is_callable(array($o,$method)))
			throw new exception('this method is not available now');
		$this->checkParams($method,$package,$params);

		if(isset($authorize))
		{
			if(!empty($this->config->params->authorize_method))
				call_user_func(array($auth,$this->config->params->authorize_method),$params);
			else
				$auth->checkSign($params);

			if(isset($auth->params->api_client))
				$this->api_client=$auth->params->api_client;
		}else{
			if(isset($this->config->params->api_client))
				$this->api_client=&$this->config->params->api_client;
			else
				$this->api_client=false;
		}
		$this->loadDefaults($method,$package,$params);
//$auth->checkgrants($params,$package,$method);
		//insert grants checking
		//reuse getConfig method

		return call_user_func(array($o,$method),$params);
	}
	function checkParams($method,$package,&$params,$sanitize=false)
	{
		
		$req=&$this->config->apiTree->{$package}->{$method};
		if(!isset($req->params))
			$req->params=null;
		$req=&$req->params;
		if(!is_array($req)&&!is_object($req))
			return true;
		$check=array();
		foreach($req as $k=>$v)
		{
			$check[$k]=array();
			
			if(isset($v->required)&&$v->required==true)
				$check[$k][0]=true;
			else
				$check[$k][0]=false;

			if(!isset($v->type))
				$v->type='string';
			else
				if($v->type=='regexp'&&isset($v->regexp))
					$check[$k][2]=$v->regexp;
				else
					if($v->type=='enum'&&isset($v->values))
						$check[$k][2]=$v->values;
			$check[$k][1]=$v->type;
			
			if(isset($v->unless))
				if($v->type=='regexp')
					$check[$k][3]=$v->unless;
				else
					$check[$k][2]=$v->unless;
				
		}
		return self::check($check,$params,$sanitize);
	}
	public function loadDefaults($method,$package,&$params)
	{
		$req=&$this->config->apiTree->{$package}->{$method};
		if(!isset($req->params))
			$req->params=null;
		$req=&$req->params;
		if(!is_array($req)&&!is_object($req))
			return true;
		foreach($req as $k=>$v)
		{
			if(!isset($v->default))
				continue;
			if(!isset($params[$k]))
				$params[$k]=$v->default;
		}
		return $params;
	}
	public function parseMethodName($params)
	{
		if(preg_match("/^([a-zA-Z_0-9]{1,48})$/U",$params['method'],$match))
		{
			$params['type']='service';
			$params['name']=$match[1];
			return $params;
		}
		if(preg_match("/^([a-zA-Z_0-9]{1,48})\.(.{1,96})$/U",$params['method'],$match))
		{
			$params['type']='method';
			$params['service']=$match[1];
			$params['name']=$match[2];
			return $params;
		}
		$params['type']='core';
		return $params;
	}
	private function getTree($params=null)
	{
		if(!isset($params['method']))
			if(isset($_GET["wmethod"]))
				$params['method']=$_GET["wmethod"];
		if(!is_string($params['method'])&&!empty($params['method']))
			throw new exception('problem with gets method name');
		$params=$this->parseMethodName($params);
		switch($params['type'])
		{
			case 'service':
				if(isset($this->config->apiTree->{$params['name']}))
					$params['tree']=$this->config->apiTree->{$params['name']};
				else
					$params['tree']=null;
				break;
			case 'method':
				if(!isset($this->config->apiTree->{$params['service']}))
					$params['tree']=null;
				else
					if(!isset($this->config->apiTree->{$params['service']}->{$params['name']}))
						$params['tree']=null;
					else
						$params['tree']=$this->config->apiTree->{$params['service']}->{$params['name']};
				break;
			case 'core':
				$params['tree']=$this->config->apiTree;
				break;
			default:
				$params['tree']=null;
		}
		return $params;
	}
	function genCoreHTML($tree=null,$prefix='')
	{
		if(isset($this->config->params)&&isset($this->config->params->linkroot))
			$linkroot=$this->config->params->linkroot;
		else
			$linkroot='/';
		$template='<li><a href="'.$linkroot.'reference/%Uservice">%service<br />%description</a></li>';
		$labelnew='<span class="new">new</span>';
		$labeldepr='<span class="deprecated">deprecated</span>';
		$labeltest='<span class="untested">untested</span>';
		$wrap='<ul class="tree">%tree</ul>';
		$i=0;
		$tmp='';
		if(isset($tree))
		foreach($tree as $key=>$value)
		{
			if($key=="hidden"&&is_bool($value))
				continue;
			if(isset($value->hidden)&&$value->hidden==true)
				continue;
			$tmp.=$template;
			$tmp=str_replace('%Uservice',urlencode($prefix.$key),$tmp);
			$tag='';
			if(isset($value->new))
				$tag.=$labelnew;
			if(isset($value->deprecate))
				$tag.=$labeldepr;
			if(isset($value->untested))
				$tag.=$labeltest;
			$servicename=$prefix.$key.$tag;
			$tmp=str_replace('%service',$servicename,$tmp);
			if(isset($value->description))
					$tmp=str_replace('%description',$value->description,$tmp);
			else
				$tmp=str_replace('%description','',$tmp);
		}
		if(!empty($tmp))
			$wrap=str_replace('%tree',$tmp,$wrap);
		else
			$wrap='No packages/methods available';
		return $wrap;
	}
	function getParamsForm($params=null)
	{
		$out='<table class="form" id="testform"><tr><th class="name">ключ</th><th class="checkbox"><span title="Не передавать параметр">?</span></th><th>значение</th></tr>';//<tr class="using"><td class="name"><span class="optional">format</span></td><td></td><td><select name="format"><option>html</option><option>json</option><option>xml</option><option>php</option><option>plain</option><option>soap</option></select></td></tr><tr class="using"><td class="name"><span class="optional">callback</span></td><td></td><td><select name="format"><option>html</option><option>json</option><option>xml</option><option>php</option><option>plain</option><option>soap</option></select></td></tr>';
		if(!isset($params))
			return '<p>Принимаемые параметры не перечислены в документации</p>';
		$params2=array("format"=>(object)array("type"=>"enum","description"=>"Формат данных ответа","values"=>array("html","json","xml","php","plain","soap"),"default"=>"html","notnull"=>true,"required"=>true),"callback"=>(object)array("type"=>"expression","description"=>"Название функции для JSONP","regexp"=>"//"));
		$params2+=(array)$params;
		$params=(object)$params2;
		foreach($params as $key=>$value)
		{
			if(isset($value->hidden)&&$value->hidden==true)
				continue;
			if(isset($value->notnull)&&$value->notnull==true)
				$out.='<tr class="using">';
			else
				$out.='<tr>';

			$add="";
			$name="";
			if(isset($value->notnull)&&$value->notnull=true)
				$name=' name="'.$key.'" ';
			if(!isset($value->type))
				$value->type="string";

			$input="";
			switch($value->type)
			{
				case "uint":
					$tmp='unsigned int';
					break;
				case "int":
					$tmp='int';
					break;
				case "float":
					$tmp='float';
					break;
				case "text":
					$tmp="text";
					$input='<textarea id="form_input_'.$key.'" placeholder="text" '.$name.' data-name="'.$key.'" class="form_input"></textarea>';
					break;
				case "ufloat":
					$tmp="unsigned float";
					break;
			case "array":
					$tmp="array";
					break;
				case "object":
					$tmp="object";
					break;
				case "bool":
					$tmp="boolean";
					$input='<input type="checkbox" id="form_input_'.$key.'" class="form_input"/><span id="form_check_'.$key.'" class="value sysval bool">false</span><input type="hidden" data-name="'.$key.'" '.$name.' class="form_checkfake" value="false"/>';
					break;
				case "hex":
					$tmp="hex";
					break;
				case "oct":
					$tmp="oct";
					break;
				case "enum":
					$tmp="enum";
					$add=" values";
					if(isset($value->values)&&!empty($value->values))
					{
						$input='<select id="form_input_'.$key.'" data-name="'.$key.'" '.$name.' class="form_input">';
//						if(!isset($value->required)||$value->required==false)
							$input.='<option class="null" value="">выберите из списка</option>';
						foreach($value->values as $k=>$v)
							if(isset($value->default)&&$v==$value->default)
								$input.='<option value='.$v.' selected="selected">'.$v.'</option>';
							else
								$input.='<option value='.$v.'>'.$v.'</option>';
						$input.='</select>';
					}
					break;
				case "regexp":
					if(isset($value->example))
						$tmp=$value->example;
					else
						$tmp=$value->regexp;
					break;
				default:
					$tmp="string";
			}
			if(strlen($input)<1)
				$input='<input id="form_input_'.$key.'" type="text" data-name="'.$key.'" placeholder="'.$tmp.'" class="form_input"/>';

			$keyname=$key;
			if(isset($value->description))
				$keyname='<span class="info" title="'.$value->description.'">'.$key.'</span>';

			if(isset($value->required)&&$value->required==true)
				if(isset($value->unless))
					$out.='<td class="name"><label for="form_input_'.$key.'"><span class="required">'.$keyname.'* <span class="unless">заменяется '.$value->unless.'</span></span></label></td>';
				else
					$out.='<td class="name"><label for="form_input_'.$key.'"><span class="required">'.$keyname.'*</span></label></td>';
			else
				$out.='<td class="name"><label for="form_input_'.$key.'"><span class="optional">'.$keyname.'</span></label></td>';

//			if(isset($value->description))
			if(!isset($value->notnull)||$value->notnull==false)
				$out.='<td class="checkbox"><input type="checkbox" class="form_null" title="Не передавать параметр '.$key.'" checked="checked" /></td>';
			else
				$out.='<td class="checkbox"></td>';
			$out.='<td>'.$input.'</td>';
				
			$out.='</tr>';
		}
		$out.='<tr class="done"><td colspan="3"><input type="submit" value="Отправить запрос" /></td></tr></table><p>* - обязательный параметр</p>';
		return $out;
	}
	function getParamsHTML($params=null)
	{
		$out='';
		if(!isset($params))
			return '<p>Принимаемые параметры не перечислены в документации</p>';
		foreach($params as $key=>$value)
		{
			if(isset($value->hidden)&&$value->hidden==true)
				continue;
			$out.='<div class="key">';
			if(isset($value->values))
			{
				$tmp='';
				foreach($value->values as $k=>$v)
					$tmp.='<li>'.$v.'</li>';
				$out.='<ul class="allvalues right">'.$tmp.'</ul>';
			}
			$add="";
			if(!isset($value->type))
				$value->type="string";
			switch($value->type)
			{
				case "uint":
					$tmp='unsigned int';
					break;
				case "int":
					$tmp='int';
					break;
				case "float":
					$tmp='float';
					break;
				case "ufloat":
					$tmp="unsigned float";
					break;
				case "array":
					$tmp="array";
					break;
				case "object":
					$tmp="object";
					break;
				case "bool":
					$tmp="boolean";
					break;
				case "hex":
					$tmp="hex";
					break;
				case "oct":
					$tmp="oct";
					break;
				case "enum":
					$tmp="enum";
					$add=" values";
					break;
				case "regexp":
					if(isset($value->example))
						$tmp=$value->example;
					else
						$tmp="expression";
					break;
				default:
					$tmp="string";
			}

			$out.='<span class="type right'.$add.'">'.$tmp.'</span>';
			$out.='<span class="name">'.$key.'</span>';


			if(isset($value->required)&&$value->required==true)
				if(isset($value->unless))
					$out.='<span class="required">обязательный <span class="unless">заменяется '.$value->unless.'</span></span>';
				else
					$out.='<span class="required">обязательный</span>';
			else
				$out.='<span class="optional">опция</span>';

			if(isset($value->description))
				$out.='<span class="description">'.$value->description."</span>";
				
			$out.='</div>';
		}
		return $out;
	}
	function declarateWSDL($params=null)
	{
		$tree=$this->getTree($params);
		if(isset($this->config->params)&&isset($this->config->params->linkroot))
			$linkroot=$this->config->params->linkroot;
		else
			$linkroot='/';
		$wsdl=<<<xml
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:mime="http://schemas.xmlsoap.org/wsdl/mime/" xmlns:s="http://www.w3.org/2001/XMLSchema" xmlns:s0="http://cinema.hnet.ru" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" targetNamespace="%root;">

	<types>
		<s:schema elementFormDefault="qualified" targetNamespace="%root;">
			<s:element name="soapResponse">
				<s:complexType>
					<s:sequence>
						<s:element name="soapResult" type="s0:Info" />
					</s:sequence>
				</s:complexType>
			</s:element>
		</s:schema>
	</types>
	
	<message name="SoapIn">
		<part name="parameters" element="s:string"/>
	</message>
	<message name="SoapOut">
		<part name="parameters" element="s0:soapResponse"/>
	</message>
	
	<portType name="cinemaSoap">
%operations;
	</portType>
	
	<binding name="cinemaSoap" type="s0:cinemaSoap">
		<soap:binding transport="http://schemas.xmlsoap.org/soap/http" style="document"/>
%bindings;
	</binding>

	<service name="cinema">
		<port name="cinemaSoap" binding="s0:cinemaSoap">
			<soap:address location="%root;soap/"/>
		</port>
	</service>
</definitions>
xml;
		if($tree["type"]!="service")
			throw new exception("wrong use");
		$op='';
		$bnd='';
		foreach($tree["tree"] as $method=>$params)
		{
			$bnd.=<<<xml
			<operation name="{$method}"><soap:operation soapAction="{$tree["method"]}.{$method}" style="document"/><input><soap:body use="literal" /></input><output><soap:body use="literal" /></output></operation>

xml;
			$op.=<<<xml
			<operation name="{$method}"><input message="s0:SoapIn" /><output message="s0:SoapOut" /></operation>

xml;
		}
		$wsdl=str_replace("%operations;",$op,$wsdl);
		$wsdl=str_replace("%bindings;",$bnd,$wsdl);
		$wsdl=str_replace("%root;",$linkroot,$wsdl);
		header("Content-Type: text/xml");
		echo $wsdl;
		die;
	}
	function declarateHTML($params=null)
	{
		$form="";
		$tree=$this->getTree($params);
		if(isset($this->config->params)&&isset($this->config->params->linkroot))
			$linkroot=$this->config->params->linkroot;
		else
			$linkroot='/';

		switch($tree['type'])
		{
			case 'method':
			if(!isset($tree['tree']->description))
				$tree['tree']->description='<i>У данного метода отсутствет описание</i>';
			if(!isset($tree['tree']->params))
				 $tree['tree']->params=null;
			$p=&$tree['tree']->params;
			if(isset($tree['tree']->auth_ip)&&$tree['tree']->auth_ip)
			{
				$auth='<p>Этому методу <i>требуется</i> проверка подлинности</p><p>Используется ограничение доступа к веб-методу по IP-адресам. Уточнить и внести изменения можно в конфигурации веб-метода</p>';
			}else
			if($this->api_auth&&isset($tree['tree']->auth)&&$tree['tree']->auth==true)
			{
				if(isset($this->config->dataparams))
				{
					if(!isset($p->api_key))
						if(!isset($this->config->dataparams->api_key))
							$p->api_key=(object)array(
								"type"=>"string",
								"required"=>"true",
								"description"=>"Application key"
							);
						else
							$p->api_key=$this->config->dataparams->api_key;
					if(!isset($p->api_sig))
						if(!isset($this->config->dataparams->api_sig))
							$p->api_sig=(object)array(
								"type"=>"string",
								"required"=>"true",
								"description"=>"Signature for request"
							);
						else
							$p->api_sig=$this->config->dataparams->api_sig;
					}

					$auth='<p>Этому методу <strong><i>требуется</i></strong> проверка подлинности</p><p>Используется проверка подлинности с помощью подписи запроса. Подпись запроса формируется следующим образом: md5-хэш от всех параметров + секретный ключ приложения. Все параметры должны быть отсортированы по ключу в алфавитном порядке и объединены в одну строку по следующей схеме &lt;key&gt;&lt;value&gt; (без&nbsp;символов&nbsp;&lt;&nbsp;&gt;). Вы не должны включать в эту строку параметры format и callback. К получившейся строке из параметров вы должны добавить ключ приложения. md5-хэш от результирующей строки и будет являться подписью запроса.</p><p>api_sig=md5(api_keyxxxxxxxmethodyyyyyyyapplicationsecret)</p>';
			}
			else
				$auth='<p>Этому методу <strong>не требуется</strong> проверка подлинности</p>';
			$params=$this->getParamsHTML($tree['tree']->params);
			$form=$this->getParamsForm($tree['tree']->params);
			
			if(isset($tree['tree']->deprecate))
			{
				$dtime=$tree['tree']->deprecate;
				if(is_numeric($dtime))
					$dtime=date("d.m.Y",$dtime);
				$deprecate=<<<EOF
<div class="warning"><span class="title">Deprecated</span><span class="text">This method will be banned since <span class="date">{$dtime}</span></span></div>
EOF;
			 }else
				 $deprecate='';
			 if(isset($tree['tree']->untested))
				 $deprecate.='<div class="warning"><span class="title">Untested</span><span class="text">This method not tested yet, can be unavailable</span></div>';
			 $text=<<<EOF
$deprecate
<p>{$tree["tree"]->description}</p>
<p><a class="newtab" href="{$linkroot}method/{$tree['service']}.{$tree['name']}">Вызвать метод: /method/{$tree['service']}.{$tree['name']} без параметров</a></p>
<h3><span class="toggler active" data-toggle="block-params">Входные параметры</span><span class="toggler" data-toggle="block-form">Форма для тестирования</span></h3>
<div class="toggler-block" data-toggle="block-params">{$params}</div>
<div class="toggler-block hidden" data-toggle="block-form"><form action="/method/{$tree['service']}.{$tree['name']}" method="post">{$form}</form>
</div>
<h3>Аутентификация</h3>
{$auth}
EOF;
				return self::makeresponse($tree['tree'],NULL,NULL,array('format'=>'html','nodata'=>true,'text'=>$text,'title'=>'Описание веб-метода \''.$tree['service'].'.'.$tree['name'].'\'','h1'=>'Справочник по API: описание веб-метода','method'=>$tree['service']));
				break;
			case 'service':
			$reference=$this->genCoreHTML($tree['tree'],$tree['name'].".");
			$text=<<<EOF
	<h3>Новинка!</h3>
	<p>Доступ к веб-сервису <strong>{$tree['method']}</strong> по протоколу SOAP: <a href="/reference/{$tree['method']}.wsdl">WSDL</a></p>
	<h3>Доступные методы</h3>
	{$reference}
EOF;
				return self::makeresponse($tree['tree'],NULL,NULL,array('format'=>'html','nodata'=>true,'h1'=>'Справочник по API: описание веб-сервиса','text'=>$text,'title'=>'Описание веб-сервиса \''.$tree['name'].'\''));
				break;
			case 'core':
			default:
			$reference=$this->genCoreHTML($tree['tree']);
			$text=<<<EOF
<h3>Доступные сервисы</h3>
{$reference}
EOF;
				return self::makeresponse($tree['tree'],NULL,NULL,array('format'=>'html','nodata'=>true,'h1'=>'Справочник по API: список веб-сервисов','text'=>$text,'title'=>'Список веб-сервисов системы gcore'));
				break;
		}
	}
	static function json_decode($res)
	{
		$res=json_decode($res);
		if(json_last_error())
			throw new exception("JSON parse error",json_last_error());
		return $res;
	}
	static function json_remote($path)
	{
		@$res=file_get_contents($path);
		if(!$res)
			throw new exception('file '.$path.' does not avaliable or empty',0);
		return self::json_decode($res);
	}
	public function getReq($package)
	{
		$cfg=&$this->config;	
		if(!isset($cfg->packages->{$package}))
			throw new exception("try to get undeclarated package");
		$cfg->packages->{$package};
		$ret=array();
		if(!isset($cfg->packages->{$package}))
			return $ret;
		$pkg=&$cfg->packages->{$package};
		if(!isset($pkg->dependence))
			return $ret;
		foreach($pkg->dependence as $rpkg)
		{
			$ret[]=$rpkg;
			$ret=array_merge($this->getReq($rpkg),$ret);
		}
		return $ret;
	}
	public function initReq($level,$package,$mode="require")
	{
		$pkgl=$this->getReq($package);
		$cfg=&$this->config;
		foreach($cfg->packages as $key=>$value)
		{
			if(!in_array($key,$pkgl))
				continue;
			if(!isset($value->{$level}))
				continue;
			if(isset($value->disabled)&&$value->disabled==true)
				continue;
			switch($mode)
			{
				case "tree":
					$cfg->$opt->$key=self::json_remote(self::$dir."/".$value->{$level});
					$tmp=&$cfg->$opt->$key;
					foreach($tmp as $method=>$settings)
					{
						if(!isset($settings->dataparams))
							continue;
					
						foreach($settings->dataparams as $name)
						{
							if(!isset($cfg->dataparams))
								throw new exception("missing dataparams parameter in config");
							if(!isset($cfg->dataparams->$name))
								throw new exception("missing standart definition for '".$name."' parameter");
							$this->mergeParam($key,$method,$name,$cfg->dataparams->$name,$opt);
						}
					}
					break;

					break;
				case "require":
					if(!file_exists(self::$dir."/".$value->{$level}))
						throw new exception("file ".$value->{$level}." does not available or empty",0);
					require_once(self::$dir."/".$value->{$level});
					break;
			}
		}
		return true;
	}
	public function mergeDefaultParam($package,$method,$name,$tree="apiTree")
	{
		$cfg=&$this->config;
		if(!isset($cfg->dataparams))
			throw new exception("missing dataparams parameter in config");
		if(!isset($cfg->dataparams->$name))
			throw new exception("missing standart definition for '".$name."' parameter");

		$this->mergeParam($package,$method,$name,$cfg->dataparams->$name,$tree);
	}
	public function mergeParam($package,$method,$name,$settings,$tree="apiTree")
	{
		$cfg=&$this->config;
		if(!isset($cfg->$tree))
			throw new exception("undefined tree '".$tree."'");
		if(!isset($cfg->$tree->$package))
			throw new exception("undefined package '".$package."' in tree '".$tree."'");

		$pkg=&$cfg->$tree->$package;
		if(!isset($pkg->$method))
			throw new exception("undefined method '".$method."' in package '".$package."'");
		if(!isset($pkg->$method->params)||(!is_object($pkg->$method->params)&&!is_array($pkg->$method->params)))
			$pkg->$method->params=(object)array();
		$params=&$pkg->$method->params;
		if(!isset($pkg->$method->params->$name))
			$params->$name=(object)array();
		
		//merge
		//TODO WARNING (undefined definition of parameter :D)
		$params->$name=(object)((array)$params->$name+(array)$settings);
		return $params->$name;
	}
	public function initLevel($level,$mode="require",$opt=false)
	{
		$cfg=&$this->config;
		foreach($cfg->packages as $key=>$value)
		{
			if(!isset($value->{$level}))
				continue;
			if(isset($value->disabled)&&$value->disabled==true)
				continue;
			switch($mode)
			{
				case "tree":
					$cfg->$opt->$key=self::json_remote(self::$dir."/".$value->{$level});
					$tmp=&$cfg->$opt->$key;
					foreach($tmp as $method=>$settings)
					{
						if(!isset($settings->dataparams))
							continue;
					
						foreach($settings->dataparams as $name)
						{
							if(!isset($cfg->dataparams))
								throw new exception("missing dataparams parameter in config");
							if(!isset($cfg->dataparams->$name))
								throw new exception("missing standart definition for '".$name."' parameter");
							$this->mergeParam($key,$method,$name,$cfg->dataparams->$name,$opt);
						}
					}
					break;
				case "require":
					if(!file_exists(self::$dir."/".$value->{$level}))
						throw new exception("file ".$value->{$level}." does not available or empty",0);
					require_once(self::$dir."/".$value->{$level});
					break;
			}

		}
		return true;
	}
	public function checkDependence()
	{
		$cfg=$this->config;
		$dpd=array();
		$inc=array();
		foreach($cfg->packages as $key=>$value)
		{
			if(isset($value->dependence))
				foreach($value->dependence as $k1=>$val)
					$dpd[$val]=0;
		}
		$this->initLevel("public","tree","apiTree");
		$this->initLevel("location");
		foreach($dpd as $key=>$value)
			if(!class_exists($key))
				throw new exception('required class '.$key.' mismatch',0);
		return true;
	}
	public function checkConfig($flag=false)
	{
		if(!$this->config)
			throw new exception('configuration is empty',0);
		$tmp=$this->config;
		if(!isset($tmp->packages))
			throw new exception('configuration syntax is bad',0);
		if(isset($tmp->params)&&isset($tmp->params->config))
		{
			$this->loadconfig($tmp->params->config);
			$this->checkConfig($flag);
		}
		$this->checkDependence();
		if(is_string($flag))
		{
			$res=$this->parseMethodName($flag);
			if($res["type"]=="method")
				return $res->initReq("init",$res["service"]);
			elseif($res["type"]=="service")
				return $res->initReq("init",$res["name"]);
		}
		$this->initLevel("init");
		return true;
	}
	public function loadConfig($file=null)
	{
		if(!isset($file))
			$file=self::$dir."/conf.js";
		if(!file_exists($file))
			throw new exception('configuration file mismatch',15);
		$res=file_get_contents($file);
		if(empty($res))
			throw new exception('configuration is empty',16);
		$res=json_decode($res);
		if(!$res)
			throw new exception('configuration syntax is bad',16);
		$this->config=$res;
		if(isset($res->params))
		{
			if(isset($res->params->safemode)&&$res->params->safemode==true)
				$this->safemode=true;
			
			if(isset($res->params->api_auth)&&$res->params->api_auth==true)
				$this->api_auth=true;

			if(isset($res->params->trace)&&$res->params->trace==true)
				$this->trace=true;

			if(isset($res->params->api_version))
				self::$api_version=$res->params->api_version;
		}else{
			$this->params=new stdClass();
		}
		if(!isset($this->config->apiTree))
			$this->config->apiTree=new stdClass();
		if(!isset($this->config->errorTree))
			$this->config->apiTree=new stdClass();


		if(!defined("GENERIC_CORE_ATTR"))
			if(!isset($this->config->params->xml_attr))
				define("GENERIC_CORE_ATTR","@attr");
			else
				define("GENERIC_CORE_ATTR",$this->config->params->xml_attr);

		if(!defined("GENERIC_CORE_CDATA"))
			if(!isset($this->config->params->xml_attr))
				define("GENERIC_CORE_CDATA","TEXT");
			else
				define("GENERIC_CORE_CDATA",$this->config->params->xml_cdata);

		return true;
	}
	private function checkGrants($package,$method)
	{
		$pkg=&$this->config->apiTree;
		$permit=false;
		if(isset($pkg->{$package})&&isset($pkg->{$package}->{$method}))
		{

			$mtree=&$pkg->{$package}->{$method};
		
			if((!isset($mtree->auth)||$mtree->auth==false)&&(!isset($mtree->auth_ip)||$mtree->auth_ip==false))
				$permit=true;


			if(isset($mtree->allow_client))
			{
				if(!$this->api_client)
					$permit=false;
				else
					foreach($mtree->allow_client as $client)
						if($client==$this->api_client)
							$permit=true;
			}else
				if(isset($mtree->auth)&&$mtree->auth==true&&!isset($mtree->allow_client))
					$permit=true;

			if(isset($mtree->auth_ip)&&$mtree->auth_ip==true&&isset($mtree->allow_ip))
			{
				if(!isset($_SERVER)||!isset($_SERVER["REMOTE_ADDR"]))
					$permit=false;
				foreach($mtree->allow_ip as $ip)
					if($ip==$_SERVER["REMOTE_ADDR"])
						$permit=true;
			}
		}else
			$permit=false;

		if(!$permit)
			throw new exception("Access denied. Please contact administrator.",43);

		return true;
	}
	public function checkTrace()
	{
		if(!$this->safemode)
			return true;
		if(!isset($this->config->params->trace_allow))
			return false;
		if(isset($_SERVER)&&isset($_SERVER["REMOTE_ADDR"]))
		{
			//remote call
			foreach($this->config->params->trace_allow as $key)
			{	
				if($key==$_SERVER["REMOTE_ADDR"])
					return true;
			}
		}
		return false;
	}
	public function initPackages()
	{
		$cfg=$this->config;
		foreach($cfg->packages as $key=>$value)
		{
			if(isset($value->init))
				require_once(self::$dir."/".$value->init);
		}
		return true;
	}
	static function xml_attribute($data,$xml)
	{
		foreach($data as $k=>$v)
			if($k!=GENERIC_CORE_CDATA)
				$xml->addAttribute($k,$v);
		return $xml;
	}
	static function xml_prepare($data,$xml)
	{
		foreach($data as $key=>$val)
		{
			$order=false;
			if(is_numeric($key))
			{
				$order=(string)$key;
				$key="item";
			}

			if($key==GENERIC_CORE_ATTR)
			{
				self::xml_attribute($val,$xml);
				continue;
			}
			if(is_string($val)||is_numeric($val)||is_bool($val))
			{
				if(is_bool($val))
					if($val)
						$val="true";
					else
						$val="false";
				if(is_numeric($val)||is_bool($val))
					$item=$xml->addChild($key,(string)$val);
				else
					if(!preg_match('/^([0-9a-zA-Z-_ а-я\/\"\'\:\,\.\(\)\;]*)$/usi',$val))
						$item=$xml->addChild($key,"<![CDATA[$val]]>");
					else
						$item=$xml->addChild($key,$val);
				if(null!=$order)
					if($order==0)
						$item->addAttribute("order","0");
					else
						$item->addAttribute("order",$order);
				continue;
			}
			if(isset($val)&&isset($val->{GENERIC_CORE_ATTR})&&isset($val->{GENERIC_CORE_ATTR}->{GENERIC_CORE_CDATA}))
				$item=$xml->addChild($key,"<![CDATA[".$val->{GENERIC_CORE_ATTR}->{GENERIC_CORE_CDATA}."]]>");
			else
				$item=$xml->addChild($key,null);

			if(null!=$order)
				if($order==0)
					$item->addAttribute("order","0");
				else
					$item->addAttribute("order",$order);
			self::xml_prepare($val,$item);
		}
	}
	static function xml_encode($data,$xml=null,$rootname="data")
	{
		if(!$xml)
			$xml=new SimpleXMLElement("<$rootname></$rootname>");
		self::xml_prepare($data,$xml);
		$res=$xml->asXML();
		$res=str_replace("&lt;![CDATA[","<![CDATA[",$res);
		$res=str_replace("]]&gt;","]]>",$res);
//		$res=str_replace("&ldquo;","&#x201C;",$res);
//		$res=str_replace("&rdquo;","&#x201D;",$res);
		return $res;
	}
	public static function php_response($data,$return=false)
	{
		$response=serialize($data);
		if(!$return)
		{
			header("Content-Type: text/plain");
			echo $response;
			exit;
		}
		return $response;
	}
	public static function json_response($data,$return=false,$callback=null)
	{
		if(isset($callback)||isset($_GET["callback"]))
		{
			if(!isset($_GET["callback"])||isset($callback)||empty($_GET["callback"]))
				$_GET["callback"]=$callback;
			header("Content-Type: text/javascript");
			if(!preg_match('/([a-zA-Z]{1})([a-zA-Z0-9\_]{0,127})$/Usi',$_GET["callback"]))
			{
				echo json_encode(array('error'=>'0','message'=>'callback MUST contain a-zA-Z0-9_ characters and must start from character'));
				exit;
			}
			$response=$_GET["callback"].'('.json_encode($data).');';
		}else
			$response=json_encode($data);
		if(!$return)
		{
			header("Content-Type: application/json");
			echo $response;
			exit;
		}
		return $response;
	}
	public static function objectToAssoc($data)
	{
		return json_decode(json_encode($data),true);
	}
	public static function xml_response($data,$return=false,$rootname="data",$xml=null)
	{
//		$wrap='data';
//		$data=self::objectToAssoc($data);
//		$data=array($wrap=>$data);
		if(!$return)
		{
			header("Content-Type: text/xml");
			echo self::xml_encode($data,$xml,$rootname);
			exit;
		}
		return self::xml_encode($data,$xml,$rootname);
	}
	public function html_var_prepare($data)
	{
		$str='';
		if(is_string($data)||is_numeric($data))
		{
			if(is_numeric($data))
				return '<span class="sysval numeric">'.$data.'</span>';
			$data=str_replace("<","&lt;",$data);
			$data=str_replace(">","&gt;",$data);
			$data=nl2br($data);
			return $data;
		}
		if(is_bool($data))
			if($data)
				return '<span class="sysval bool">true</span>';
			else
				return '<span class="sysval bool">false</span>';
		if(is_null($data))
			return '<span class="sysval">null</span>';
		if(!self::isbindeep($data))
		{
			$str.='<ul>';
			foreach($data as $key=>$value)
				$str.="<li><b>$key</b>: ".$this->html_var_prepare($value)."</li>";
			$str.='</ul>';
		}else{
			$str.='<table class="result">';
			$i=0;
			foreach($data as $key=>$value)
			{
				if($i%2==1)
					$str.='<tr>';
				else
					$str.='<tr class="first">';
				$i++;
				$str.='<td class="key">'.$key.'</td><td>'.$this->html_var_prepare($value).'</td></tr>';
			}
			$str.='</table>';
		}
		return $str;
	}
	public function plain_prepare($data,$offset=0)
	{
		$str='';
		if($offset>0)
			$str.="\n";
		if(is_bool($data))
			if($data)
				return 'true';
			else
				return 'false';

		if(is_string($data)||is_numeric($data))
			return $data;
		foreach($data as $key=>$value)
		{
			for($i=0;$i<$offset;$i++)
				$str.=chr(9);
			$str.=$key.": ".$this->plain_prepare($value,$offset+1);
			$str.="\n";
		}
		return $str;
	}
	public function isbindeep($array)
	{
		foreach($array as $key=>$value)
		{
			if(is_object($value)||is_array($value))
				return false;
		}
		return true;
	}
	public function plain_response($data=null,$return=false)
	{
//		$response=var_export($data,true);
		$response=$this->plain_prepare($data);
		if(!$return)
		{
			header("Content-Type: text/plain");
			echo $response;
			exit;
		}
		return $response;
	}
	public function safeCode($code)
	{
		if($code==0)
			return true;
		if($code>=0x200&&$code<0x400)
			return false;
		if($code>=0x2000&&$code<0x3FFF)
			return false;
		if($code>=0x6000&&$code<0x7FFE)
			return false;
		return true;
	}
	function soap_response($data,$fault=false,$params=null)
	{
		if(!$fault)
			$soapbody=<<<xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:s="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Body><soapResponse>%data;</soapResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>
xml;
		else
			$soapbody=<<<xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:s="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>SOAP-ENV:Server</faultcode><faultstring>%message;</faultstring><detail>%data;</detail></SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>
xml;
//		$data=array(
		header("Content-Type: text/xml");
		$xml=$this->xml_response($data,true,"soapResult");
		$xml=str_replace('<?xml version="1.0" encoding="UTF-8"?'.'>','',$xml);
		$xml=str_replace('<?xml version="1.0"?'.'>','',$xml);
		$xml=str_replace("%data;",$xml,$soapbody);
		if(is_array($params))
		{
			if(isset($params["subtitle"]))
				$xml=str_replace("%message;",$params["subtitle"],$xml);
		}
		print_r($xml);
	}
	public function makeresponse($data,$format=null,$callback=null,$params=null)
	{
		header("Cache-Control: no-store, no-cache, must-revalidate");
		if(isset($params)&&isset($params["format"])&&!isset($format)&&!isset($_GET["format"]))
			$format=$params['format'];
		if(!isset($format)&&isset($_GET["format"]))
			$format=$_ENV["params"]["format"];

		if(isset($this->config->params->name))
			$name=$this->config->params->name;
		else
			$name="Generic Core";
		if(isset(self::$version))
			$version=self::$version;
		else
			$version="1.0";
		if(isset($this->config->params->support))
			$mail=$this->config->params->support;
		else
			$mail='root@localhost';
		header("X-Powered-By: ".$name."/".$version);
		if(isset($params))
		{
			self::check(array(
				'title'=>array(false,'string'),
				'header'=>array(false,'string'),
				'text'=>array(false,'string')),$params);
			if(isset($params['title'])&&!empty($params['title']))
				$title=$params['title'];
			else
				$title='Undefined';
			if(isset($params['header']))
				header($params['header']);
			if(!isset($params['text']))
				$text='';
			else
				$text=$params['text'];
		}else
			$params=array();
		$params=self::setempty(array(
			'method'=>'reference'),$params);
		if(!isset($format))
			$format='json';
		if(!is_array($data)&&is_string($data))
			$data=array('error'=>$data);
		switch($format)
		{
			case 'soap':
				if(isset($params["type"])&&$params["type"]=="error")
					$this->soap_response($data,true,$params);
				else
					$this->soap_response($data);
				break;
			case 'php':
				$this->php_response($data);
				break;
			case 'plain':
				$this->plain_response($data);
				break;
			case 'xml':
				$this->xml_response($data);
				break;
			case 'json':
				$this->json_response($data,false,$callback);
				break;
			case 'html':
			default:
				if(!isset($params))
					$params=array();
				if(isset($params['title']))
					$title=$params['title'];
				else
					if(isset($params['method']))
						$title="Результат вызова метода '".$params["method"]."'";
					else
						$title="The result";
				if(isset($params['text']))
					$text=$params['text'];
				else
					$text='';

				if(!empty($params['method']))
					$h1='Результат вызова метода \''.$params['method'].'\'';
				else
					$h1='The result of empty call';

				if(isset($params['h1']))
					$h1=$params['h1'];
				else
					if(empty($h1))
						$h1='The result of the action';
				if(!isset($params['nodata'])||!$params['nodata'])
					$text.=$this->html_var_prepare($data);
				if(isset($params['type']))
					$extstyle=' class="'.$params['type'].'"';
				else
					$extstyle='';
				$date=date('d M Y H:i:s');
				if(isset($this->config->params)&&isset($this->config->params->linkroot))
					$linkroot=$this->config->params->linkroot;
				else	
					$linkroot="/";
				if(!empty($params['method'])&&$params["method"]!="reference")
				{
					$button='главная/'.$params['method'];
					$buttonlink=$linkroot.'reference/'.$params['method'];
				}else{
					$button='главная/';
					$buttonlink=$linkroot.'reference';
				}
				if(isset($this->config->params)&&isset($this->config->params->httproot))
					$httproot=$this->config->params->httproot;
				else
					$httproot="/";
				$tmp=$_ENV["params"];
				unset($tmp["format"]);
				$query=http_build_query($tmp);
				$linkxml=$linkroot.'method/'.$params["method"].'.xml?'.$query;
				$linktxt=$linkroot.'method/'.$params["method"].'.txt?'.$query;
				$linkphp=$linkroot.'method/'.$params["method"].'.php?'.$query;
				$linkhtml=$linkroot.'method/'.$params["method"].'.html?'.$query;
				$linksoap=$linkroot.'method/'.$params["method"].'.soap?'.$query;
				$linkjson=$linkroot.'method/'.$params["method"].'.json?'.$query;
				$api_version=self::$api_version;
				$subtitle="";
				if(isset($params["subtitle"]))
					$subtitle="<h3>".$params["subtitle"]."</h3>";
				if(isset($params['method'])&&!isset($params["title"])||(isset($params["type"])&&$params["type"]=="error"))
				{
					$links=<<<html
					<span class="formats">Доступные форматы вывода данных: <a href="{$linkjson}">JSON</a> <a href="{$linkxml}">XML</a> <a href="{$linktxt}">Plain</a> <a href="{$linkhtml}">HTML</a> <a href="{$linksoap}">SOAP</a> <a href="{$linkphp}">PHP</a></span>
html;
					$text='<div class="result">'.$text.'</div>';
				}
				else
					$links='';
				$script='<script type="text/javascript" src="'.$httproot.'core/public/api.js"></script>';
				if(isset($this->config->params->optimize)&&$this->config->params->optimize&&file_exists(dirname(__FILE__)."/public/api.js"))
					$script='<script type="text/javascript">'.str_replace(array("\t","\n"),array("",""),file_get_contents(dirname(__FILE__)."/public/api.js")).'</script>';
				$css='<link rel="stylesheet" type="text/css" href="'.$httproot.'core/public/api.css" />';
				if(isset($this->config->params->optimize)&&$this->config->params->optimize&&file_exists(dirname(__FILE__)."/public/api.css"))
					$css='<style type="text/css">'.str_replace(array("\t","\n"),array("",""),file_get_contents(dirname(__FILE__)."/public/api.css")).'</style>';
				echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<meta name="robots" content="nofollow" />
	<meta name="robots" content="noindex" />
	<meta name="author" content="http://johnynsk.ru" />
	<title>{$title}</title>
	<link rel="icon" type="image/vdn.microsoft.icon" href="{$httproot}core/public/api.ico" />
	{$css}
	{$script}
	<script type="text/javascript" src="{$httproot}core/public/api.js"></script>
	<!--
		coding&design by johny
		http://johnynsk.ru/
		me@johnynsk.ru
	-->
</head>
<body$extstyle>
<div id="top"><h1>{$h1} <span class="right-floated"><a href="{$buttonlink}" class="ref-button">{$button}</a></span></h1></div>
<div id="wrap">
<h2>{$title}{$links}</h2>
{$subtitle}
{$text}
<p class="info">Вы также можете получить результат и в других форматах.<br />
Доступные форматы: JSON, XML, Plain-Text, HTML, SOAP, PHP (serialize)</p>
</div>
<div id="copy"><span class="right-floated"><a href="mailto:{$mail}">{$mail}</a></span>
<address>Generated with {$name}/{$version}, API Version: {$api_version} at {$date}</address></div>
</body>
</html>
EOF;
				exit;
				break;
		}
		return null;
	}
}
