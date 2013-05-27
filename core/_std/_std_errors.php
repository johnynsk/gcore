<?php
function errhandle($errno,$errstr,$errfile,$errline,$errcontext)
{
	$type=false;
	switch($errno)
	{
		case E_USER_ERROR:
			$type='Fatal Error';
			break;
		case E_USER_WARNING:
			$type='Warning';
			break;
		case E_USER_NOTICE:
			$type='Notice';
			break;
		default:
			$type='Unknown';
			break;
	}
	$msg='';
	$id=substr(md5(microtime(true)),8);
	$msg.=date("[Y/m/d H:i:s] ");
	$msg.='@'.$id."\n!".$type." #".$errno.": ".$errstr."\n";
	$msg.="Line ".$errline.' in '.$errfile."\n";
	if(!file_exists("./logs/"))
		mkdir('./crash');
	if(!file_exists("./logs/".date('Y')))
		mkdir("./logs/".date('Y'));
	if(!file_exists("./logs/".date('Y/m')))
		mkdir("./logs/".date('Y/m'));
	if(!file_exists("./logs/".date('Y/m/d')))
		mkdir("./logs/".date('Y/m/d'));
	$link='.';
	if(isset($_ENV["core"])&&is_callable(array($_ENV["core"],"getConfig")))
	{
		$tmp=$_ENV["core"]->getConfig();
		if(isset($tmp->params)&&isset($tmp->params->httproot))
			$link=$tmp->params->httproot;
	}
	$msg.="Debug JSON: ".$link."logs/".date("Y/m/d")."/debug_".date("Y-m-d")."-".$id.".js\n";
	if(isset($_SERVER)&&isset($_SERVER["REMOTE_ADDR"]))
		$msg.="IP: ".$_SERVER["REMOTE_ADDR"]."\n";
	if(isset($_ENV["clientid"]))
		$msg.="Client ID: ".$_ENV["clientid"]."\n";
	if(isset($_SERVER)&&isset($_SERVER["LOGNAME"])&&isset($_SERVER["PWD"]))
		$msg.="CLI Login: ".$_SERVER["LOGNAME"]."; PWD=".$_SERVER["PWD"]."\n";
	$msg.="\n";
	file_put_contents('./logs/'.date("Y/m/d").'/less_'.date("Y-m-d").'-'.$id.'.log',$msg);
	file_put_contents('./logs/summary_'.date("Y-m-d").'.log',$msg,FILE_APPEND);
	exec('chmod 777 ./logs/summary_'.date("Y-m-d").'.log 1>/dev/null 2>/dev/null');

	file_put_contents('./logs/'.date("Y/m/d").'/debug_'.date("Y-m-d").'-'.$id.'.js',json_encode(@debug_backtrace()));

	if($errno==E_USER_ERROR)
	{
		header("HTTP/1.0 500 Internal Server Error");
		if(!isset($argc))
		{
		echo <<<eof
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf8" />
	<title>Error / Ошибка</title>
</head>
<body>
<h1>Ошибка!</h1>
</body>
</html>
eof;
	die;
		}else{
			return true;
		}
	}
	return true;
}
set_error_handler('errhandle');

