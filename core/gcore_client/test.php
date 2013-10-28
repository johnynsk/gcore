<?php
require_once 'gcore_client.php';
$client=new gcore_client(true,"http://johnynsk.ru",true);
$client->api_key="developer";
$client->api_secret="internal";
$params=array(
	"method"=>"developer.test",
	"testapp"=>"123"
);
$res=$client->call($params,2);
print_r($res);
