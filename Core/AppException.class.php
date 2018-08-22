<?php
namespace Core;
class AppException extends \Exception {
	public function __construct($message,$code=0){
		$data = array(
			'error' => -1,//系统错误
			'data' => null,
			'msg' => $message
		);
		if(!$code){//正常系统抛出错误代码不填，默认为0
			ob_end_clean();//清除缓冲区
			header('Content-Type: application/json;charset=utf-8');
			exit(json_encode($data));
		}else{
			parent::__construct($message,$code);
		}
	}
}