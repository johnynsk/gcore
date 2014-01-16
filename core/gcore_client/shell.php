<?php
require_once 'gcore_client.php';
$set=array("internal"=>false,"post"=>true,"debug"=>null,"useauth"=>0,"api_key"=>"","api_secret"=>"","interface"=>0);
$params=array();
$mode="direct";
$connected=false;
$cycle=true;
$eax=null;
$ebx=null;
$ecx=null;
$edx=null;
print_r("Welcome to gcore shell\n");
if(file_exists("config.ini"))
{
	print_r("<  Loading config file config.ini");
	$tmp=parse_ini_file("config.ini");
	$set=array_merge($set,$tmp);
}
function done()
{
	global $mode;
	$mode="direct";
}
while($cycle)
{
	try{
		$welcome="\n>: ";
		if($mode=="php")
			$welcome="";
		$line=readline($welcome);
		$e=explode(" ",$line);
		if($mode=="php")
		{
			if(strtolower($line)=="eof"||strtolower($line)=="done"||strtolower($line)=="quit")
			{
				$mode="direct";
				break;
			}
			eval($line);
			continue;
		}
		switch(strtolower($e[0]))
		{
			case "php":
				$expr=preg_match("#php (.*)#usi",$line,$m);
				if(empty($m[1]))
				{
					echo "<  Switching mode to inline-PHP\n";
					$mode="php";
					break;
				}
				eval($m[1]);
				break;
			case "connect":
				if(!empty($e[1]))
					$set["host"]=$e[1];
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
				echo "<  Connected to ".$set["host"]."\n";
				$connected=true;
				break;
			case "exec":
				if(!$connected)
				{
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
				}
				if($set["useauth"])
				{
					$params["api_key"]=$set["api_key"];
				}

				$res=$client->call($params,$set["debug"]);
				if(!empty($e[1])&&strtolower($e[1])=="into"&&!empty($e[2])&&preg_match("#[a-z0-9]+#usi",$e[2]))
				{
					echo "<  Result stored into \$".$e[2]."\n";
					${$e[2]}=$res;
					break;
				}
				echo "<  Result stored into \$eax\n";
				$eax=$res;
				print_r($res);
				break;
			case "print":
				if(empty($e[1]))
					throw new exception("you must specify what you're printing: config or data");
				if(strtolower($e[1])=="config")
				{
					print_r($set);
					break;
				}
				if(strtolower($e[1])=="data")
				{
					print_r($params);
					break;
				}
				if(!empty($e[1]))
				{
					if(isset(${$e[1]}))
					{
						print_r("<  ");
						print_r(${$e[1]});
						break;
					}
				}
				throw new exception("you must specify what you're printing: config or data");
			case "load":
				if(empty($e[1]))
					throw new exception("you must specify what you're loading: config or data path");
				if(strtolower($e[1])=="config")
				{
					if(file_exists($e[2]))
					{
						$tmp=parse_ini_file($e[2]);
						$set=array_merge($set,$tmp);
					}
					else
						throw new exception("unavailable file");
					break;
				}
//				if(strtolower($e[1])=="data")
//				{
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
//				}
//				throw new exception("you must specify what you're loading: config or data");
			case "clear":
				if(empty($e[1]))
				{
					print chr(27)."[H".chr(27)."[2J";
					break;
				}
				if(strtolower($e[1])=="config")
				{
					$set=array();
					break;
				}
				if(strtolower($e[1])=="data")
				{
					$params=array();
					break;
				}
				break;
			case "set":
				if(strtolower($e[1])=="config")
				{
					$e=explode("=",$e[2]);
					$set[$e[0]]=$e[1];
					break;
				}
				$p=preg_match("#^set (.*)#usi",$line,$m);
				if(strlen(trim($m[1]))==0)
					throw new exception("empty string");
				$p=explode(",",$m[1]);
				foreach($p as $i=>$sec)
				{
					$e=explode("=",trim($sec));
						$params[$e[0]]=$e[1];
				}
				break;
			case "quit":
				$cycle=false;
				break;
		}
	}catch(exception $err){
		print_r("<  Error\n");
		print_r("<  #".$err->getCode().": ".$err->getMessage()."\n");
	}
}
if($connected)
	echo "Connection closed\n";
echo "Terminated\n";
exit;

