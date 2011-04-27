<?php

/**
	MongoDB Mapper for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3::Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package M2
		@version 2.0.0
**/

//! MongoDB Mapper
class M2 extends Base {

	//@{ Locale-specific error/exception messages
	const
		TEXT_M2Empty='M2 is empty',
		TEXT_M2Collection='Collection %s does not exist';
	//@}

	//@{
	//! M2 properties
	private
		$db,$collection,$object,$criteria,$order,$offset;
	//@}

	/**
		M2 factory
			@return object
			@param $doc array
			@private
	**/
	private function factory($doc) {
		$m2=new self($this->collection,$this->db);
		foreach ($doc as $key=>$val)
			$m2->object[$key]=$val;
		return $m2;
	}

	/**
		Retrieve from cache; or save query results to cache if not
		previously executed
			@param $query array
			@param $ttl int
			@private
	**/
	private function cache(array $query,$ttl) {
		$cmd=json_encode($query,TRUE);
		$hash='mdb.'.self::hash($cmd);
		$cached=Cache::cached($hash);
		$db=(string)$this->db;
		$stats=&self::ref('STATS');
		if ($ttl && $cached && $_SERVER['REQUEST_TIME']-$cached<$ttl) {
			// Gather cached queries for profiler
			if (!isset($stats[$db]['cache'][$cmd]))
				$stats[$db]['cache'][$cmd]=0;
			$stats[$db]['cache'][$cmd]++;
			// Retrieve from cache
			return Cache::get($hash);
		}
		else {
			$result=$this->exec($query);
			if ($ttl)
				Cache::set($hash,$result,$ttl);
			// Gather real queries for profiler
			if (!isset($stats[$db]['queries'][$cmd]))
				$stats[$db]['queries'][$cmd]=0;
			$stats[$db]['queries'][$cmd]++;
			return $result;
		}
	}

	/**
		Execute MongoDB query
			@return mixed
			@param $query array
			@private
	**/
	private function exec(array $query) {
		$cmd=json_encode($query,TRUE);
		$hash='mdb.'.self::hash($cmd);
		// Except for save method, collection must exist
		$list=$this->db->listCollections();
		foreach ($list as &$coll)
			$coll=$coll->getName();
		if ($query['method']!='save' && !in_array($this->collection,$list)) {
			trigger_error(sprintf(self::TEXT_M2Collection,$this->collection));
			return;
		}
		if (isset($query['mapreduce'])) {
			// Create temporary collection
			$ref=$this->db->selectCollection($hash);
			$ref->batchInsert(iterator_to_array($out,FALSE));
			$map=$query['mapreduce'];
			$func='function() {}';
			// Map-reduce
			$tmp=$this->db->command(
				array(
					'mapreduce'=>$ref->getName(),
					'map'=>isset($map['map'])?
						$map['map']:$func,
					'reduce'=>isset($map['reduce'])?
						$map['reduce']:$func,
					'finalize'=>isset($map['finalize'])?
						$map['finalize']:$func
				)
			);
			if (!$tmp['ok']) {
				trigger_error($tmp['errmsg']);
				return FALSE;
			}
			$ref->remove();
			// Aggregate the result
			foreach (iterator_to_array($this->db->
				selectCollection($tmp['result'])->find(),FALSE) as $agg)
				$ref->insert($agg['_id']);
			$out=$ref->find();
			$ref->drop();
		}
		elseif (preg_match('/find/',$query['method'])) {
			// find and findOne methods allow selection of fields
			$out=call_user_func(
				array(
					$this->db->selectCollection($this->collection),
					$query['method']
				),
				isset($query['criteria'])?$query['criteria']:array(),
				isset($query['fields'])?$query['fields']:array()
			);
			if ($query['method']=='find') {
				if (isset($query['order']))
					// Sort results
					$out=$out->sort($query['order']);
				if (isset($query['offset']))
					// Skip to record offset
					$out=$out->skip($query['offset']);
				if (isset($query['limit']))
					// Limit number of results
					$out=$out->limit($query['limit']);
				// Convert cursor to PHP array
				$out=iterator_to_array($out,FALSE);
				foreach ($out as &$obj)
					$obj=$this->factory($obj);
			}
		}
		else
			$out=preg_match('/count|remove/',$query['method'])?
				// count() and remove() methods can specify criteria
				call_user_func(
					array(
						$this->db->selectCollection($this->collection),
						$query['method']
					),
					isset($query['criteria'])?$query['criteria']:array()
				):
				// All other queries
				call_user_func(
					array(
						$this->db->selectCollection($this->collection),
						$query['method']
					),
					$this->object
				);
		return $out;
	}

	/**
		Similar to M2->find method but provides more fine-grained control
		over specific fields and map-reduced results
			@return array
			@param $fields array
			@param $criteria mixed
			@param $mapreduce mixed
			@param $order mixed
			@param $limit mixed
			@param $offset mixed
			@param $ttl int
			@public
	**/
	function lookup(
		array $fields,
		$criteria=NULL,
		$mapreduce=NULL,
		$order=NULL,
		$limit=NULL,
		$offset=NULL,
		$ttl=0) {
		$query=array(
			'method'=>'find',
			'fields'=>$fields,
			'criteria'=>$criteria,
			'mapreduce'=>$mapreduce,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset
		);
		return $ttl?$this->cache($query,$ttl):$this->exec($query);
	}

	/**
		Alias of the lookup method
			@public
	**/
	function select() {
		// PHP doesn't allow direct use as function argument
		$args=func_get_args();
		return call_user_func_array(array($this,'lookup'),$args);
	}

	/**
		Return an array of collection objects matching criteria
			@return array
			@param $criteria mixed
			@param $order mixed
			@param $limit mixed
			@param $offset mixed
			@param $ttl int
			@public
	**/
	function find(
		$criteria=NULL,$order=NULL,$limit=NULL,$offset=NULL,$ttl=0) {
		$query=array(
			'method'=>'find',
			'criteria'=>$criteria,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset
		);
		return $ttl?$this->cache($query,$ttl):$this->exec($query);
	}

	/**
		Return the first object that matches the specified criteria
			@return array
			@param $criteria mixed
			@param $order mixed
			@param $limit mixed
			@param $offset mixed
			@param $ttl int
			@public
	**/
	function findOne(
		$criteria=NULL,$order=NULL,$limit=NULL,$offset=NULL,$ttl=0) {
		list($result)=
			$this->find($criteria,$order,$limit,$offset,$ttl)?:array(NULL);
		return $result;
	}

	/**
		Count records that match condition
			@return int
			@param $criteria mixed
			@public
	**/
	function found($criteria=NULL) {
		$result=$this->exec(
			array(
				'method'=>'count',
				'criteria'=>$criteria
			)
		);
		return $result['count'];
	}

	/**
		Hydrate M2 with elements from framework array variable, keys of
		which will be identical to field names in collection object
			@param $name string
			@public
	**/
	function copyFrom($name) {
		if (is_array(self::ref($name)))
			$this->object=self::ref($name);
	}

	/**
		Populate framework array variable with M2 properties, keys of
		which will have names identical to fields in collection object
			@param $name string
			@param $fields string
			@public
	**/
	function copyTo($name,$fields=NULL) {
		if (is_string($fields))
			$list=explode('|',$fields);
		foreach (array_keys($this->object) as $field)
			if (!isset($list) || in_array($field,$list)) {
				$var=&self::ref($name);
				$var[$field]=$this->object[$field];
			}
	}

	/**
		Dehydrate M2
			@public
	**/
	function reset() {
		// Dehydrate
		$this->object=NULL;
		$this->criteria=NULL;
		$this->order=NULL;
		$this->offset=NULL;
	}

	/**
		Retrieve first collection object that satisfies criteria
			@param $criteria mixed
			@param $order mixed
			@param $offset int
			@public
	**/
	function load($criteria=NULL,$order=NULL,$offset=0) {
		if (method_exists($this,'beforeLoad') && !$this->beforeLoad())
			return;
		if (!is_null($offset) && $offset>-1) {
			$this->offset=NULL;
			if ($m2=$this->findOne($criteria,$order,1,$offset)) {
				// Hydrate M2
				foreach ($m2->object as $key=>$val)
					$this->object[$key]=$val;
				list($this->criteria,$this->order,$this->offset)=
					array($criteria,$order,$offset);
				return;
			}
		}
		$this->reset();
		if (method_exists($this,'afterLoad'))
			$this->afterLoad();
	}

	/**
		Retrieve N-th object relative to current using the same criteria
		that hydrated M2
			@param $count int
			@public
	**/
	function skip($count=1) {
		if ($this->dry()) {
			trigger_error(self::TEXT_M2Empty);
			return;
		}
		self::load($this->criteria,$this->order,$this->offset+$count);
	}

	/**
		Insert/update collection object
			@public
	**/
	function save() {
		if ($this->dry() ||
			method_exists($this,'beforeSave') && !$this->beforeSave())
			return;
		// Let the MongoDB driver decide how to persist the
		// collection object in the database
		$obj=$this->object;
		$this->exec(array('method'=>'save'));
		if (!isset($obj['_id']))
			// Reload to retrieve MongoID of inserted object
			$this->object=
				$this->exec(array('method'=>'findOne','criteria'=>$obj));
		if (method_exists($this,'afterSave'))
			$this->afterSave();
	}

	/**
		Delete collection object and reset M2
			@public
	**/
	function erase() {
		if (method_exists($this,'beforeErase') && !$this->beforeErase())
			return;
		$this->exec(array('method'=>'remove','criteria'=>$this->criteria));
		$this->reset();
		if (method_exists($this,'afterErase'))
			$this->afterErase();
	}

	/**
		Return TRUE if M2 is NULL
			@return boolean
			@public
	**/
	function dry() {
		return is_null($this->object);
	}

	/**
		Synchronize M2 and MongoDB collection
			@param $coll string
			@param $db object
			@public
	**/
	function sync($coll,$db=NULL) {
		if (!in_array('mongo',get_loaded_extensions())) {
			// MongoDB extension not activated
			trigger_error(sprintf(self::subst(self::TEXT_PHPExt),'mongo'));
			return;
		}
		if (!$db)
			$db=self::$vars['DB'];
		if (method_exists($this,'beforeSync') && !$this->beforeSync())
			return;
		// Initialize M2
		list($this->db,$this->collection)=array($db,$coll);
		if (method_exists($this,'afterSync'))
			$this->afterSync();
	}

	/**
		Return value of M2-mapped field
			@return boolean
			@param $name string
			@public
	**/
	function __get($name) {
		return $this->object[$name];
	}

	/**
		Assign value to M2-mapped field
			@return boolean
			@param $name string
			@param $value mixed
			@public
	**/
	function __set($name,$value) {
		$this->object[$name]=$value;
	}

	/**
		Clear value of M2-mapped field
			@return boolean
			@param $name string
			@public
	**/
	function __unset($name) {
		unset($this->object[$name]);
	}

	/**
		Return TRUE if M2-mapped field exists
			@return boolean
			@param $name string
			@public
	**/
	function __isset($name) {
		return array_key_exists($name,$this->object);
	}

	/**
		Display class name if conversion to string is attempted
			@public
	**/
	function __toString() {
		return get_class($this);
	}

	/**
		Class constructor
			@public
	**/
	function __construct() {
		// Execute mandatory sync method
		call_user_func_array(
			array(get_called_class(),'sync'),func_get_args());
	}

}
