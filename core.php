<?php
date_default_timezone_set('UTC');

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function ($className) {
    $fileName = $className;
    $fileName = preg_replace('/_/', '/', $fileName);
    $fileName = preg_replace('/\\\\/', '/', $fileName);
    $fileName = __DIR__ . DIRECTORY_SEPARATOR . 'sources' . DIRECTORY_SEPARATOR . $fileName . '.php';
    if (file_exists($fileName)) {
        return require $fileName;
    }
}
);
try{
//extra fault level
	try{
		$tstart=microtime(true);
		if(!defined("GENERIC_CORE_ATTR"))
			define("GENERIC_CORE_ATTR","@attr");
		if(!defined("GENERIC_CORE_CDATA"))
			define("GENERIC_CORE_CDATA","CDATA");
		if(!defined("GENERIC_CORE_INIT"))
		{
			define("GENERIC_CORE_INIT",true);
			if(!defined("GENERIC_CORE_STDERROR"))
				require_once dirname(__FILE__).'/core/_std/_std_errors.php';
			//parsing parameters{
			$data=array();
			$_ENV["requestid"]=null;
			$_ENV["multicall"]=array();
			if((isset($_SERVER["REQUEST_METHOD"])&&($_SERVER["REQUEST_METHOD"]=="PUT"||$_SERVER["REQUEST_METHOD"]=="DELETE"))||(isset($_GET["format"])&&$_GET["format"]=="jsonrpc"))
			{
				$http=fopen("php://input","r");
				$tmp='';
				while($s=fread($http,1024))
					$tmp.=$s;
				fclose($http);
				$s=json_decode($tmp);
				if(!json_last_error())
				{
					$tmp=$s;
					if(is_array($tmp))
					{
						foreach($tmp as $el)
							if(is_object($el)&&isset($el->jsonrpc)&&$el->jsonrpc=="2.0")
							{
								$e=new stdclass;
								if(isset($el->method))
									$e->method=$el->method;
								if(empty($e->method))
									$e->method="";
								if(isset($el->params))
									$e->params=(array)$el->params;
								if(empty($e->params))
									$e->params=array();
								$e->params["method"]=$e->method;
								$e->id=null;
								if(!empty($el->id)&&is_numeric($el->id))
									$e->id=(int)$el->id;
								$_ENV["multicall"][]=$e;
							}
					}
					if(is_object($tmp)&&isset($tmp->jsonrpc)&&$tmp->jsonrpc=="2.0")
					{
						if(isset($tmp->id))
							$_ENV["requestid"]=$tmp->id;
						if(!isset($tmp->params))
							$tmp->params=array();
						$data=(array)$tmp->params;
						$data["method"]=$tmp->method;
					}
				}else
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
			if(!isset($argc)||$argc<2)
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
			include dirname(__FILE__).'/core/core.php';
			$_ENV["core"]=new core(dirname(__FILE__));
			core::regObject($_ENV["core"],"core");

			if(!isset($_ENV["configpath"])&&file_exists(core::$dir."/.htconf.js"))
				$_ENV["configpath"]=core::$dir."/.htconf.js";

			if(isset($_ENV["configpath"]))
				$_ENV["core"]->loadConfig($_ENV["configpath"]);
			else
				$_ENV["core"]->loadConfig();
			if(isset($_ENV["params"]["method"])&&$_ENV["params"]["method"]=="reference")
				$_ENV["core"]->checkConfig(true);
			else
				if(isset($_ENV["params"]["method"]))
					$_ENV["core"]->checkConfig(array("method"=>$_ENV["params"]["method"]));
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
						if($_ENV["params"]["format"]=="jsonrpc")
						{
							if(count($_ENV["multicall"])==0)
								throw new exception("Invalid Request",-32600);
						}else
							$_ENV["core"]->declarateHTML();
				}
		}
		if(!defined("GENERIC_CORE_STANDALONE"))
		{
			if(!isset($_ENV["params"]["method"]))
				throw new exception("You must specify method name");
		}
		if(count($_ENV["multicall"])>0&&$_ENV["params"]["format"]=="jsonrpc")
		{
			$out=array();
			foreach($_ENV["multicall"] as $cur)
				try{
					$tstart=microtime(true);
					$result=$_ENV["core"]->callMethod($cur->method,$cur->params);
					$tend=microtime(true);
					$data=(object)array(
						"jsonrpc"=>"2.0",
						GENERIC_CORE_ATTR=>(object)array(
							"state"=>"success",
							"api_version"=>core::$api_version,
							"sys_version"=>core::$version,
							"time"=>microtime(true),
							"runtime"=>round($tend-$tstart,9),
							"method"=>$cur->method
						),
						"result"=>$result,
						"id"=>$cur->id
					);
					$out[]=$data;
				}catch(exception $e){
					$message=$e->getMessage();
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
					$data=(object)array(
						"jsonrpc"=>"2.0",
						GENERIC_CORE_ATTR=>(object)array(
							"state"=>"error",
							"api_version"=>core::$api_version,
							"sys_version"=>core::$version,
							"time"=>microtime(true),
							"method"=>$cur->method,
						),
						"error"=>(object)array(
							"code"=>$e->getCode(),
							"message"=>$message
						),
						"id"=>$cur->id
					);
				}
			$_ENV["core"]->makeresponse($out,"json",NULL,array());
		}

		if(!empty($_ENV["params"]["method"]))
		{
			$format=$_ENV["params"]["format"];
			$method=$_ENV["params"]["method"];
			$tstart=microtime(true);
			$result=$_ENV["core"]->callMethod($method,$_ENV["params"]);
			$tend=microtime(true);
			$data=(object)array(
				GENERIC_CORE_ATTR=>(object)array(
					'state'=>'success',
					'api_version'=>core::$api_version,
					'sys_version'=>core::$version,
					'time'=>microtime(true),
					'runtime'=>round($tend-$tstart,9),
				),
				'result'=>$result,
				"id"=>$_ENV["requestid"]
			);
			if(isset($_ENV["params"]["method"]))
				$data->{GENERIC_CORE_ATTR}->method=$_ENV["params"]["method"];
			if(defined('GENERIC_CORE_RETURNRESULT'))
				$_ENV["result"]=$data;
			else{
				$params=array("type"=>"success","method"=>$_ENV["params"]["method"],"id"=>$_ENV["requestid"]);
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
				GENERIC_CORE_ATTR=>(object)array(
					GENERIC_CORE_CDATA=>$message,
					"code"=>$code,
				),
			),
			"id"=>$_ENV["requestid"],
			"trace"=>$trace,
		);
		if($_ENV["params"]["format"]=="jsonrpc")
			$data->error=(object)array("code"=>$code,"message"=>$message);
		if(isset($_ENV["params"]["method"]))
			$data->{GENERIC_CORE_ATTR}->method=$_ENV["params"]["method"];
		if(!isset($_ENV["params"]["method"])&&defined("GENERIC_CORE_WEBSITE"))
			$_ENV["params"]["method"]="website";
		$_ENV["core"]->makeresponse($data,$_ENV["params"]["format"],NULL,array("title"=>"Произошла ошибка (исключение)","subtitle"=>$message,"code"=>$code,"type"=>"error","method"=>$_ENV["params"]["method"]));
	}
}catch(exception $err){
	$code=$err->getCode();
	$message=$err->getMessage();
	$data=(object)array(
		GENERIC_CORE_ATTR=>(object)array(
			"state"=>"error",
			"api_version"=>core::$api_version,
			"sys_version"=>core::$version,
			"time"=>microtime(true),
			'runtime'=>round(microtime(true)-$tstart,9)
		),
		"error"=>(object)array(
			GENERIC_CORE_ATTR=>(object)array(
				GENERIC_CORE_CDATA=>$message,
				"code"=>$code,
			),
		),
	);
	$_ENV["core"]->makeresponse($data,"html",NULL,array("title"=>"Произошла ошибка (исключение)","subtitle"=>$message,"code"=>$code,"type"=>"error","method"=>$_ENV["params"]["method"]));

}
