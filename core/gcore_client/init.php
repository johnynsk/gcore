<?php
if(!function_exists('curl_setopt'))
	throw new exception('curl support must be enabled in php');
core::regObject(new gcore_client(),"gcore_client");

