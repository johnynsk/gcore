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
			while($data=fread($http,1024))
				$tmp.=$data;
			fclose($http);
			parse_str($tmp,$data);
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
			if(isset($e[0])&&isset($e[1]))
			{
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
					case '--conf':
						$_ENV["configpath"]=$e[1];
						break;
					case '-t':
					case '-trace':
					case '--trace':
						$_ENV["usetrace"]=true;
						break;
					default:
						if($key>1)
						{
							if($e[0]!='-h'&&$e[0]!='--help')
								echo "Unknown argument '".$e[0]."'\n";
							echo "Usage:\n--method call method\n-m\n\n--format Output format\n";
							exit;
						}
				}
			}else{
				if($key>1)
				{
					echo "Unknown argument '".$e[0]."'\n";
					echo "Usage:\n--method call method\n-m\n\n--format Output format\n";
					exit;
				}
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
				$_ENV["core"]->declarateHTML();
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
		$result=$_ENV["core"]->callMethod($method,$_ENV["params"]);
		$data=(object)array(
			GENERIC_CORE_ATTR=>(object)array(
				'state'=>'success',
				'api_version'=>$_ENV["core"]->api_version,
				'time'=>microtime(true),
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
			"api_version"=>$_ENV["core"]->api_version,
			"time"=>time(),
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
	$_ENV["core"]->makeresponse($data,$_ENV["params"]["format"],NULL,array("title"=>"Произошла ошибка (исключение)","type"=>"error","method"=>$_ENV["params"]["method"]));
}
