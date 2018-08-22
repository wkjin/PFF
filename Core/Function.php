<?php
//定义系统函数库

//格式输出（使用func_get_args()获取多个参数并传入var_dump中）
function dump($var){
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}

//获取|修改配置文件中
function C($key = null,$value=null){
	if(empty($key) && empty($value)){//获取所有的配置
		return $_ENV['CONF'];
	}else if(!empty($key)){
		if($value != null){//设置配置
			return $_ENV['CONF'][$key] = $value;
		}else{
			return $_ENV['CONF'][$key];
		}
	}else{
		return null;
	}
}

//抛出错误的方法
function E($msg = '出错了',$code = 0){
	throw new \Core\AppException($msg,$code);
	exit; 
}

//新建控制器的方法
function A($g_controller){
	$controller = $_ENV['CONTROLLER'];
	$g_controller = substr($controller,0,strrpos($controller,'\\')+1).ucfirst($g_controller);
	if(class_exists($g_controller)){
		return new $g_controller;
	}
	return null;
}

//新建模型的方法
function M($g_model){
	$g_model = '\\Model\\'.ucfirst($g_model);
	if(class_exists($g_model)){
		return new $g_model;
	}else{
		return null;
	}
}

//新建模型并带数据库操作
function D($g_model){
	$model = M($g_model);
	if(!empty($model)){//建立数据库与模型关联
		return $model->instanceDB();//初始化数据库
	}else{
		//如果没有那个Model的话，那么就实例化数据表操作对象
		$db = '\\Core\\DB';
		if(class_exists($db)){
			$instanceDB = new $db;
			$instanceDB->setConfig($_ENV['CONF']['DB']);
			$instanceDB->setTable(ucfirst($g_model));
			return $instanceDB;	
		}
		return null;
	}
}

//对于session进行操作（设置session的保存时间、获取session值、设置session值、清空session值）
function session($key='',$value=''){
	//如果只有一个参数，并且是数字，那么就是设置session保存时间
	if(is_int($key) && $key > 0 ){
		$tempArr = array();
		if(isset($_SESSION)){
			$tempArr = $_SESSION;
			session_destroy();//彻底销毁session
		}
		session_set_cookie_params($key);
		session_start();
		$_SESSION = $tempArr;
		return;
	}
	if(!isset($_SESSION)){
		session_start();
	}
	if($key !=='' && $key !== null){
		if($value !== '' && $value !== null){//设置值
			$_SESSION[$key] = $value;
		}else if($value === null){//清空一个值
			unset($_SESSION[$key]);
		}else if($value === ''){//获取值
			return $_SESSION[$key];
		}
	}else if($key === null){//清空所有session
		$_SESSION = array();
	}else if($key === ''){//获取所有的session
		return $_SESSION;
	}else{
		return null;
	}
}

//转码字符串
function encodeStr($data){
	if(is_array($data)){
		foreach ($data as $key => $value) {
			if(is_array($value)){
				$data[addslashes($key)] = encodeStr($value);
			}else{
				$data[addslashes($key)] = addslashes($value);
			}
		}
		return $data;
	}else{
		return addslashes($data);
	}
}

/**
	* 下划线转驼峰
	* 思路:
	* step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
	* step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
*/
function camelize($uncamelized_words,$separator='_'){
	$uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
	return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
}

/**
	* 驼峰命名转下划线命名
	* 思路:
	* 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
*/
function uncamelize($camelCaps,$separator='_'){
	return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

//深度克隆对象(把对象系列化后进行反序列化)
function deepClone($obj){
	return unserialize(serialize($obj));
}




