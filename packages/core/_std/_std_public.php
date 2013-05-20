<?php
abstract class _std_public extends _std_object{
	public function get($params)
	{
		if(is_numeric($params))
			$this->_load($params);
		else
			$this->_load($params["id"]);
		return $this->params;
	}
	public function attr($params)
	{
		$this->_load($params["id"]);
		if(isset($params["value"]))
		{
			$this->params->{$params["key"]}=$params["value"];
			$this->_save();
		}
		return $this->params;
	}
	public function create($params)
	{
		$this->_new();
		if(is_array($params))
			unset($params["id"]);
		if(is_object($params))
			unset($params->id);
		$this->_set($params);
		$this->_save();
		$this->_load();
		return $this->params;
	}
	public function remove($params)
	{
		$this->_load($params["id"]);
		$this->_remove();
		return $this->params;
	}
	public function restore($params)
	{
		$this->_load($params["id"]);
		$this->_restore();
		return $this->params;
	}
	public function search($params)
	{
		$limit=null;
		$page=null;
		if(isset($params["limit"]))
			$limit=$params["limit"];
		if(isset($params["page"]))
			$page=$params["page"];
		unset($params["limit"]);
		unset($params["page"]);
		return $this->_search($params,null,$limit,$page);
	}
}
