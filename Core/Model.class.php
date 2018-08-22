<?php
namespace Core;
/**
 * 功能：模型类
 */
class Model{

	private $DB = null;//保存数据库的操作对象
	private $modelName = '';//保存当前模型的名字

	function __construct($table = null){
		//保存模型的名字
		$this->modelName = trim(get_class($this),'Model\\');
		$this->instanceDB($table);//建立数据库实例
	}

	//注入数据库操作类
	public function instanceDB($table = null){
		$this->DB = new DB(C('DB'));
		$table = ($table == null)?$this->modelName:$table;
		return $this->setTableName($table);
	}

	//设置使用的数据库
	protected function setTableName($table = null,$tableAlias =null){
		//把类的名称转下划线
		$this->DB->setTable(uncamelize(lcfirst($table)),$tableAlias);
		return $this;
	}

	//定义的魔方方法只是对数据库操作类进行关联
	function __call($method,$argument){
		if(!empty($this->DB) && $this->DB ){
			//添加一个参数为tableName

			//记得返回，否则无法返回值
			return call_user_func_array(array($this->DB, $method), $argument);
		}else{
			E('找不到方法：'.$method);
		}
	}


}