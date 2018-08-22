<?php
namespace Core;
class Controller {
	//默认的构造函数
	function __construct(){}

	//默认的起始方法
	function _init($method,$controller){}

	//空方法处理
	protected function _empty($method,$controller){
		E('请求的方法不存在：'.$controller.'->'.$method);
	}
	//保证内部的类可以调用方法
	public function _empty_hdfgaprwsdfnsptrptnsdfirtnsdpfbneprshfos($method,$controller){
		$this->_empty($method,$controller);
	}

	
	/* 功能：json输出
	 * 参数： $data 返回去的数据 
	 * 返回： 返回到页面json数据
	 */
	private function _ajaxReturn($data){
        ob_end_clean();//清除以前的输出，保证输出的为json格式
		header('Content-Type: application/json;charset=utf-8');
		exit(json_encode($data));
	}

	private function  _return($data,$error,$msg,$type = 'json'){
		if($this->IS_AJAX() || $type = 'json'){
			$data = array(
				'error' =>  $error,//系统错误为-1，没有错误为0，有错误为1
				'msg' => $msg,//提示信息
				'data' => $data//返回的数据段
			);
			$this->_ajaxReturn($data);
		}else{
			throw new AppException("请求的方式不对");
		}
	}

	public  function success($data = null,$msg = '操作成功'){
		$this->_return($data,0,$msg,'json');
	}

	//错误返回
	public  function error($msg = '操作失败',$data = null){
		$this->_return($data,1,$msg,'json');
	}

	//系统错误返回
	public function  fail($msg = '系统错误',$data=null){
		$this->_return($data,-1,$msg,'json');
	}

	//判断是否是ajax请求
	public function IS_AJAX(){
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
	}

	//跨域设置与会话信息验证
	public function setCorsHeader($isCredentials = false){
		$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
		$orginArr = C('CORSORIGINS');
		if(in_array($origin,$orginArr)){//按照请求的名字设置
			header('Access-Control-Allow-Origin: '.$origin);
		}else if(in_array('*', $orginArr)){//如果设置中设置*，那么就设置为*
			header('Access-Control-Allow-Origin: *');
		}

		//设置可以携带cookie（一般都是用在有身份验证的时候）
		if($isCredentials){
			header('Access-Control-Allow-Credentials: true');
		}
	}

	//空方法统一处理
	public function __call($method,$argusment){
		$this->_empty($method,$_ENV['CONTROLLER']);
	}


}