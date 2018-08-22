<?php
namespace Core;
class Start {
	private static $isLoadController = false;
	//按照命名空间加载
	public static function loadByNamespace($class){
		$class = trim(str_replace('\\','/',$class),'/');
		if(!preg_match("/(((.*?)\/)*){0,1}([a-zA-Z\-\_]+)$/i",$class,$mArr)){
			return false;//没有匹配到类名那就就无法引入（无需抛出错误）
		}
		//判断是不是核心库，如果是核心，那么就只是加载核心类
		$coreClassName = array('Controller','AppException','Model','DB');
		if(substr($class,0,4) === ltrim(SYS_CORE,'./') || in_array($class, $coreClassName)){
			$classPath = preg_replace("/^(".ltrim(SYS_CORE,'./')."\/){0,1}/",SYS_CORE.'/',$class);
		}else{
			$classPath = APP_ROOT.'/'.ltrim($class,'/');
		}
		$classPath .= CLASS_EXT;
		if(file_exists($classPath)){
			require($classPath);
		}else{
			if(!Start::$isLoadController){//如果是在初始化阶段就抛出错误
				throw new AppException('加载类文件失败：'.$classPath);
			}else{
				//需要使用class_exists判断是否存在，那么就不能抛出错误
			}
		}
	}

	//按照文件或者文件夹进行加载
	public static function loadByFile($input,$ext = '.php'){
		if(is_dir($input)){//遍历里面的文件，并引入
			$handler = opendir($input);//打开文件操作器
			$ext = trim($ext);
			$ext = empty($ext)?'.php':$ext;
			$requireData = array();//保存require进行的数据
			while( ($filename = readdir($handler)) !== false ) {
				if($filename != '.' && $filename != '..' && substr($filename,strlen($filename)-strlen($ext)) === $ext){
					//把引入的文件的结果保存到数组中，统一返回进行处理
					$filePathName = $input.'/'.$filename;
					if(file_exists($filePathName)){
						$requireData[] = include_once($filePathName);
					}else{
						$requireData[] = null;
					}
				}
			}
			closedir($handler);
			return $requireData;
		}else{
			$filePathName = rtrim($input,$ext).$ext;
			if(file_exists($filePathName)){
				return include_once($filePathName);
			}else{
				return null;
			}
		}
	}

	//系统的起始方法
	public static function init(){
		//1、加载配置文件
		Start::initConfigFile();
		//2、加载函数库
		Start::initFunctionFile();
		//3、加载控制器
		Start::initController();
	}

	//加载配置文件
	private static function initConfigFile(){
		//把核心配置配置读取出放进全局数组中
		$_ENV['CONF'] = Start::loadByFile(SYS_CORE.'/Config.php','');
		if(!isset($_ENV['CONF']['configFile'])){
			throw new AppException("系统核心配置文件出错");
		}else{
			function loadConfig($configFileArr){
				//记录有没有配置项加入
				$isChange = false;
				foreach ($configFileArr as $value) {
					@$c = Start::loadByFile($value);
					if(is_array($c)){
						$isChange = (count($c)>0 || $isChange)?true:false;//还有配置文件添加
						//为了数组中的不进行覆盖
						foreach ($c as $k => $v) {
							if(!empty($v)){
								if(is_array($v)){
									if(isset($_ENV['CONF'][$k])){
										$_ENV['CONF'][$k] = array_unique(array_merge($_ENV['CONF'][$k],$v));
									}else{
										$_ENV['CONF'][$k] = $v;
									}
									continue;
								}
							}
							$_ENV['CONF'][$k] = $v;
						}
					}
				}
				return $isChange;
			}
			do{
				$isChange =  loadConfig($_ENV['CONF']['configFile']);
			}while($isChange);
		}
	}

	//加载函数库
	private static function initFunctionFile(){
		if(isset($_ENV['CONF']['functionLib'])&&!empty($_ENV['CONF']['functionLib'])){
			//加载函数库
			foreach ($_ENV['CONF']['functionLib'] as $functionFile) {
				Start::loadByFile($functionFile);
			}
		}
	}

	//分析URL获取请求地址，进行加载数据
	private static function initController(){
		Start::$isLoadController = true;//加载完控制程序
		//获取请求的pathinfo进行获取相应的文件
		$pathInfo = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO']: '';
		//使用/对path_info进行分割
		$piArr = explode('/',ltrim($pathInfo,'/'));
		
		//获取到控制器的名字
		$cf = (isset($piArr[0])&&$piArr[0])?trim($piArr[0]):'Index';
		//获取到方法的名字
		$ff = (isset($piArr[1])&&$piArr[1])?$piArr[1]:'Index';

		$emptyController = 'Empty';//空控制器名字
		$emptyMethod = '_empty_hdfgaprwsdfnsptrptnsdfirtnsdpfbneprshfos';//空方法名字
		$initMethod = '_init';//默认的起始方法
		$controller = 'Controller';//控制器文件夹名字

		//加载控制器文件
		$c_path = APP_ROOT.'/'.$controller.'/';
		if(!Start::loadByFile($c_path.$cf,CLASS_EXT)){
			//文件不存在，那么就引入空控制器处理文件
			Start::loadByFile($c_path.$emptyController,CLASS_EXT);
		}
		
		//加载控制器的类(对空控制器与空方法进行处理)
		$cfN = '\\'.$controller.'\\'.ucfirst($cf);

		if(!class_exists($cfN)){
			if(!class_exists('\\'.$controller.'\\'.$emptyController)){
				$cfN = '\\'.$controller.'\\'.$emptyController;
			}else{
				throw new AppException("请求的控制器不存在！！！");
			}
			//如果空类处理类不存在，那么就直接报错
			if(!class_exists($cfN)){
				throw new AppException("请求的控制器不存在！！！");
			}
		}
		//把调用的控制器与方法保存到全局配置中
		$_ENV['CONTROLLER'] = $cfN;
		$_ENV['METEOD'] = $ff;
		
		//开始初始化类与方法
		$controller = new $cfN;
		$controller->$initMethod($ff,$cfN);//把方法名，控制器名称返回去
		if(method_exists($controller,$ff)){
			$controller->$ff();
		}else{
			if(method_exists($controller,$emptyMethod)){
				$controller->$emptyMethod($ff,$cfN);//把方法名，控制器名称返回去
			}else{
				E('METHOD IS NO EXISTS：'.$ff);
			}
		}	
	}
}
ob_start();//建立输出的缓冲区
//定义以下常用的变量
define('EXT','.php');
define('CLASS_EXT','.class.php');

//使用$_ENV做为全局保存环境变量使用
$_ENV['APP_ROOT'] = APP_ROOT;
$_ENV['SYS_CORE'] = SYS_CORE;

//按照命名空间自动导入
spl_autoload_register(__NAMESPACE__.'\Start::loadByNamespace');
	

