<?php
require_once 'gcore_client.php';
$set=array("internal"=>false,"post"=>true,"debug"=>null,"useauth"=>0,"api_key"=>"","api_secret"=>"","interface"=>0);
$params=array();
if(file_exists("config.ini"))
{
	$tmp=parse_ini_file("config.ini");
	$set=array_merge($set,$tmp);
}
foreach($argv as $key=>$value)
{
	$e=explode("=",$value);
	switch($e[0])
	{
		case '-c':
		case '-config':
			if(file_exists($e[1]))
			{
				$tmp=parse_ini_file($e[1]);
				$set=array_merge($set,$tmp);
			}
			else
				throw new exception("unavailable file");
			break;
		case '-h':
		case '-host':
			$set["host"]=$e[1];
			break;
		case '-apikey':
		case '-key':
		case '-api_key':
			$set["api_key"]=$e[1];
			break;
		case '-debug':
			$set["debug"]=$e[1];
			break;
		case '-useauth':
			$set["useauth"]=$e[1];
			break;
		case '-apisecret':
		case '-api_secret':
		case '-secret':
			$set["api_secret"]=$e[1];
			break;
		case '-proxy':
			$set["proxy"]=$e[1];
			break;
		case "-auth":
			$set["auth"]=$e[1];
			break;
		case '-post':
			if($e[1]=="0"||$e[1]=="false")
				$set["post"]=false;
			break;
		case "-i":
			$set["interface"]=false;
			break;
		case "-internal":
			$set["internal"]=true;
			break;
		case "-p":
		case "-params":
			if(file_exists($e[1]))
			{
				$tmp=file_get_contents($e[1]);
				$tmp=json_decode($tmp,true);
				if(json_last_error())
					throw new exception("data file must be JSON");
				$params=array_merge($tmp,$params);
			}else
				throw new exception("unavailable data file");
			break;
		default:
			if($key==0)
				continue;
			if(!preg_match("#^--(.*)#si",$e[0],$m))
			{
				echo "Unknown argument '".$e[0]."'\n";
				echo "Try to read help: --help\n";
				exit;
			}
			$params[$m[1]]=$e[1];
			break;
	}
}
print_r($set);
if(!isset($set["host"]))
	throw new exception("unknown application host");

$client=new gcore_client($set["internal"],$set["host"],$set["post"]);

if(!empty($set["signame"]))
	$client->signame=$set["signame"];
if(isset($set["api_key"]))
	$client->api_key=$set["api_key"];
if(isset($set["api_secret"]))
	$client->api_secret=$set["api_secret"];
if(isset($set["proxy"]))
	$client->proxy=$set["proxy"];
if(isset($set["auth"]))
	$client->auth=$set["auth"];

if($set["useauth"])
{
	$params["api_key"]=$set["api_key"];
	$params["api_secret"]=$set["api_secret"];
}

$res=$client->call($params,$set["debug"]);
print_r($res);
