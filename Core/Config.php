<?php
//在这里配置系统相关配置
return array(
	//系统配置文件
	'configFile' => array(
		SYS_CORE.'/Config.php',//默认的配置文件
		APP_ROOT.'/Common/Config.php',//系统默认的用户配置文件
	),
	//函数库
	'functionLib' => array(
		SYS_CORE.'/Function.php',//默认的函数库
		APP_ROOT.'/Common/function.php',//系统默认的用户函数库
	),
	//跨域源
	'CORSORIGINS' => array(
		'*'
	),
	//默认返回
	'returnType' => 'application/json',//默认json返回
	//数据库配置
	'DB' => array(
		//数据库配置
		'DB_TYPE'               =>  'mysqli',     // 数据库类型(暂时只是支持mysqli)
	    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
	    'DB_NAME'               =>  'test',         // 数据库名
	    'DB_USER'               =>  'root',      // 用户名
	    'DB_PWD'                =>  '',          // 密码
	    'DB_PORT'               =>  '3306',        // 端口
	    'DB_PREFIX'             =>  '',    // 数据库表前缀
	),
	
);
