<?php
try{
	if(!defined("GENERIC_CORE_INIT"))
	{
		define("GENERIC_CORE_INIT",true);
		if(!defined("GENERIC_CORE_STDERROR"))
			require_once 'core/_std/_std_errors.php';
		//parsing parameters{
		$data=array();
		if(isset($_SERVER["REQUEST_METHOD"])&&($_SERVER["REQUEST_METHOD"]=="PUT"||$_SERVER["REQUEST_METHOD"]=="DELETE"))
		{
			$http=fopen("php://input","r");
			$tmp='';
			while($s=fread($http,1024))
				$tmp.=$s;
			fclose($http);
			parse_str($tmp,$data);
		}
		if(isset($_SERVER["HTTP_SOAPACTION"]))
		{
			$data["method"]=str_replace('"','',$_SERVER["HTTP_SOAPACTION"]);
			$http=fopen("php://input","r");
			$tmp='';
			while($s=fread($http,1024))
				$tmp.=$s;
			fclose($http);
			$tmp=str_replace("SOAP-ENV:","",$tmp);
			$xml=simplexml_load_string($tmp);
			foreach($xml->Body->parameters->item as $s)
				$data[(string)$s->key]=(string)$s->value;
			unset($xml);
			$data["format"]="soap";
		}
		$_ENV["params"]=array_merge($_GET,$_POST,$_FILES,$data);
		//parsing parameters}
		//parsing arguments{
		if(!isset($argc))
			$argc=0;
		else
			$_ENV["params"]["format"]="plain";
		if(!isset($argv))
			$argv=array();
		foreach($argv as $key=>$value)
		{
			$e=explode("=",$value);
			switch($e[0])
			{
				case '-m':
				case '--method':
				case '-method':
					$_ENV["params"]["method"]=$e[1];
					break;
				case '-f':
				case '--format':
				case '-format':
					$_ENV["params"]["format"]=$e[1];
					break;
				case '-conf':
				case '-c':
					$_ENV["configpath"]=$e[1];
					break;
				case '-t':
				case '-trace':
					$_ENV["usetrace"]=true;
					break;
				case '-h':
				case '--help':
					echo "\nUsage:\n\t-m\t\n\t--method\tExecuting method name\n".
					"\t-f\t \n\t--format\tOuput data format\n \t \tAvailable formats: json, soap, php, xml, txt, html".
					"\n\t-c\n\t--conf\tPath to config data\n \t \tDefault: conf.js:.htconf.js\n".
					"\t-t\t\n\t--trace\tShow output trace\n";
					exit;			
				default:
					if($key==0)
						continue;
					if(!preg_match("#^--(.*)#si",$e[0],$m))
					{
						echo "Unknown argument '".$e[0]."'\n";
						echo "Try to read help: --help\n";
						exit;
					}
					$_ENV["params"][$m[1]]=$e[1];
					break;
			}
		}
		if(!isset($_ENV["params"]["method"])&&!defined("GENERIC_CORE_WEBSITE"))
			$_ENV["params"]["method"]="reference";
		//parsing arguments}
		include 'core/core.php';
		$_ENV["core"]=new core();
		core::regObject($_ENV["core"],"core");

		if(!isset($_ENV["configpath"])&&file_exists(".htconf.js"))
			$_ENV["configpath"]=".htconf.js";

		if(isset($_ENV["configpath"]))
			$_ENV["core"]->loadConfig($_ENV["configpath"]);
		else
			$_ENV["core"]->loadConfig();
		if(isset($_ENV["params"]["method"])&&$_ENV["params"]["method"]=="reference")
			$_ENV["core"]->checkConfig(true);
		else
			$_ENV["core"]->checkConfig(false);
		if(!isset($_ENV["params"]["format"]))
			$_ENV["params"]["format"]="html";
		if(isset($_ENV["params"])&&isset($_ENV["params"]["method"])&&$_ENV["params"]["method"]=="reference")
			if(isset($_ENV["core"]->config->params->reference_allow)&&!in_array($_SERVER["REMOTE_ADDR"],$_ENV["core"]->config->params->reference_allow))
				throw new exception("Access to reference denied");
			else
			{
				if($_ENV["params"]["format"]=="wsdl")
					$_ENV["core"]->declarateWSDL();
				else
					$_ENV["core"]->declarateHTML();
			}
	}
	if(!defined("GENERIC_CORE_STANDALONE"))
	{
		if(!isset($_ENV["params"]["method"]))
			throw new exception("You must specify method name");
	}
	if(!empty($_ENV["params"]["method"]))
	{
		$format=$_ENV["params"]["format"];
		$method=$_ENV["params"]["method"];
		$tstart=microtime(true);
		$result=$_ENV["core"]->callMethod($method,$_ENV["params"]);
		$data=(object)array(
			GENERIC_CORE_ATTR=>(object)array(
				'state'=>'success',
				'api_version'=>core::$api_version,
				'sys_version'=>core::$version,
				'time'=>microtime(true),
				'runtime'=>round(microtime(true)-$tstart,9)
			),
			'result'=>$result);
		if(isset($_ENV["params"]["method"]))
			$data->{GENERIC_CORE_ATTR}->method=$_ENV["params"]["method"];
		if(defined('GENERIC_CORE_RETURNRESULT'))
			$_ENV["result"]=$data;
		else{
			$params=array("type"=>"success","method"=>$_ENV["params"]["method"]);
			core::getObject("mysqlix")->close();
			$_ENV["core"]->makeresponse($data,$format,NULL,$params);
		}
	}
}catch(exception $err){
	$code=$err->getCode();
	$message=$err->getMessage();
	if(!$_ENV["core"]->safemode||$_ENV["core"]->checkTrace())
	{
		if(isset($_ENV["core"]->trace)&&$_ENV["core"]->trace==true)
			$trace=$err->getTrace();
		else
			$trace=(object)array(GENERIC_CORE_ATTR=>(object)array("missing_reason"=>"disabled in config (trace option)"));
	}else{
		$trace=(object)array(GENERIC_CORE_ATTR=>(object)array("missing_reason"=>"disabled in safemode"));
		if(!$_ENV["core"]->safeCode($code))
			$message='Internal Server Error';
	}
	if(!isset($_ENV["params"]["format"]))
		$_ENV["params"]["format"]='html';
	//header("HTTP/1.0 500 Internal Server Error");
	$data=(object)array(
		GENERIC_CORE_ATTR=>(object)array(
			"state"=>"error",
			"api_version"=>core::$api_version,
			"sys_version"=>core::$version,
			"time"=>microtime(true),
			'runtime'=>round(microtime(true)-$tstart,9)
		),
		"error"=>(object)array(
			"@attr"=>(object)array(
				GENERIC_CORE_CDATA=>$message,
				"code"=>$code,
			),
		),
		"trace"=>$trace,
	);
	if(isset($_ENV["params"]["method"]))
		$data->{GENERIC_CORE_ATTR}->method=$_ENV["params"]["method"];
	if(!isset($_ENV["params"]["method"])&&defined("GENERIC_CORE_WEBSITE"))
		$_ENV["params"]["method"]="website";
	$_ENV["core"]->makeresponse($data,$_ENV["params"]["format"],NULL,array("title"=>"Произошла ошибка (исключение)","subtitle"=>$message,"code"=>$code,"type"=>"error","method"=>$_ENV["params"]["method"]));
}
