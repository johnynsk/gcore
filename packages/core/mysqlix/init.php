<?php
if(!isset($_ENV["examples"]))
	$_ENV["examples"]=new stdclass();
$tmp=$_ENV["core"]->getConfig()->packages->mysqlix->params;
if(isset($tmp->trace))
	$_ENV["mysqlix"]=new mysqlix($tmp->host,$tmp->user,$tmp->password,$tmp->database,$tmp->trace);
else
	$_ENV["mysqlix"]=new mysqlix($tmp->host,$tmp->user,$tmp->password,$tmp->database);

unset($tmp);
$_ENV["mysqlix"]->query("SET NAMES `utf8`");
$_ENV["examples"]->mysqlix=&$_ENV["mysqlix"];
core::regObject($_ENV["examples"]->mysqlix,"mysqlix");
