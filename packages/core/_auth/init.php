<?php
if(!class_exists('_auth'))
	throw new exception('class _auth not defined');
if(!isset($_ENV["examples"]))
	$_ENV["examples"]=new stdclass;
$_ENV["examples"]->_auth=new _auth(core::getObject("mysqlix"));
core::regObject($_ENV["examples"]->_auth,"_auth");

