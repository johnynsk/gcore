<?php
abstract class _std_object{
	protected $type;
	protected $id;
	protected $cparams;
	protected $oparams;
	protected $params;
	protected $__new;
	protected $db;
	protected $member=0;
	protected $log=true;
	function __toString()
	{
		return $this->id;
	}
	function __destruct()
	{
		unset($this->type,$this->id,$this->oparams,$this->params,$this->db,$this->owner);
	}
	function _load($id=null)
	{ 
		if(isset($id))
			$this->id=$id;
		if(!isset($this->id)||!isset($this->type))
			throw new exception('type & id must be set');
		$q=$this->db->safequery("SELECT * FROM `".$this->db->real_escape_string($this->type)."` WHERE `id` =".$this->db->real_escape_string($this->id)." LIMIT 1;");
		if($q->num_rows==0)
		{
			$this->params=null;
			$this->oparams=null;
			throw new exception('failed to load object (type: '.$this->db->real_escape_string($this->type).'; id:'.$this->db->real_escape_string($this->id).')');
		}
		$this->params=$q->fetch_object();
		$this->oparams=clone $this->params;
		return true;
	}
	function _save()
	{
		$str='';
		$actions=array();
		if($this->__new)
			$action='create';
		else
			$action='update';
		foreach($this->oparams as $key=>$value)
		{
			if(!isset($this->params->{$key}))
			{
				if(!empty($str))
					$str.=", ";
				$str.=" `".$key."` =null ";
				$actions[]=array("object"=>$this->type,"id"=>$this->id,"key"=>$key,"value"=>$value,"nvalue"=>null,"action"=>'delete','member'=>$this->member);
			}else
				if($this->params->{$key}!=$value)
				{
					if(!empty($str))
						$str.=", ";
					$actions[]=array("object"=>$this->type,"id"=>$this->id,"key"=>$key,"value"=>$value,"nvalue"=>$this->params->{$key},"action"=>$action,"member"=>$this->member);
					$str.=" `".$this->db->real_escape_string($key)."`='".$this->db->real_escape_string($this->params->{$key})."' ";
				}
		}
		if(empty($str))
			return false;
		$this->db->safequery("UPDATE `".$this->db->real_escape_string($this->type)."` SET ".$str." WHERE `id` ='".$this->db->real_escape_string($this->id)."' LIMIT 1;");
		$this->oparams=clone $this->params;
		return true;
	}
	function _new()
	{
		$this->_create();
		$this->__new=true;
		$this->_load();
		return $this->id;
	}
	function _create()
	{
		$this->db->safequery("INSERT INTO `".$this->db->real_escape_string($this->type)."` (`id`,`active`) VALUES (null,'1');");
		$this->id=$this->db->insert_id;
		return $this->id;
	}
	function _remove()
	{
		$this->params->active=0;
		$this->_save();
	 return true;
	}
	function _set($params)
	{
		foreach($params as $key=>$value)
			$this->params->{$key}=$value;
		return true;
	}
	function _restore()
	{
		$this->params->active=1;
		$this->_save();
		return true;
	}
	function _search($key,$value=null,$limit=null,$page=null,$order=null,$orderto=null)
	{
		$where='';
		if(!is_array($key))
			$key=array($key=>$value);
		foreach($key as $k=>$v)
		{
			if(!empty($where))
				$where.=" AND ";
			$where.=" `".$this->db->real_escape_string($k)."`='".$this->db->real_escape_string($v)."' ";
		}
		if(!empty($where))
			$where=' WHERE '.$where;
		if(isset($order))
			if(!isset($orderto)||strtoupper($orderto)=='ASC')
				$order='ORDER BY `'.$this->db->real_escape_string($order).'` ASC';
			else
				$order='ORDER BY `'.$this->db->real_escape_String($order).'` DESC';
		else
			$order='';
		if(!isset($limit))
			if(isset($this->cparams->limit_default))
				$limit=$this->cparams->limit_default;
			else
				$limit=1;
		else
			if(isset($this->cparams->limit_max)&&$limit>$this->cparams->limit_max)
				$limit=$this->cparams->limit_max;
		if(isset($page))
			$from=$page*$limit.','.$limit;
		else
			$from=$limit;

		$query="SELECT * FROM `".$this->db->real_escape_string($this->type)."` ".$where." ".$order." LIMIT ".$from."";
		$q=$this->db->safequery($query);

		if($q->num_rows==0)
			return null;
		$r=$q->fetch_object();
		return $r->id;
	}
	function __construct()
	{
		$core=core::getObject('core');
		$this->cparams=$core->getConfig()->params;
	}
}
