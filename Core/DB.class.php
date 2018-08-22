<?php
namespace Core;
/**
 * 功能：数据库操作类
 * 
 */
class DB{

	protected $config = null;//数据库的配置文件
	private $conn = null;//数据库的连接
	private $tableName = '';//当前使用的数据表名称
	private $tableAlias = null;//数据表别名
	//使用条件设置
	private $sql_where = null;//where条件
	private $sql_limit = null;//limit条件
	private $sql_having = null;//having条件
	private $sql_order = null;//order条件
	private $sql_group = null;//分组的信息
	private $sql_field = null;//字段条件
	private $sql_join = null;//联表查询条件
	private $lastSql = null;//上次查询的sql语句



	function __construct($config=null){
		$this->config = array(
			//数据库配置
			'DB_TYPE'               =>  'mysqli',     // 数据库类型
		    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
		    'DB_NAME'               =>  'test',         // 数据库名
		    'DB_USER'               =>  'test',      // 用户名
		    'DB_PWD'                =>  '',          // 密码
		    'DB_PORT'               =>  '3306',        // 端口
		    'DB_PREFIX'             =>  '',    // 数据库表前缀
		);
		$this->setConfig($config);
	}

	//设置配置文件
	public function setConfig($config){
		//合并配置文件
		if(!empty($config) && is_array($config)){
			$this->config = array_merge($this->config,$config);
		}
	}

	//设置DB类中默认使用的数据表
	public function setTable($tableName,$tableAlias='t1'){
		//加上表前缀
		$this->tableName = $this->config['DB_PREFIX'].$tableName;
		$this->tableAlias = $tableAlias;
	}

	//获取最后一条SQL
	public function getLastSql(){
		return $this->lastSql;
	}

	//清空查询条件
	private function clearCondition(){
		$this->sql_where = null;//where条件
		$this->sql_limit = null;//limit条件
		$this->sql_having = null;//having条件
		$this->sql_order = null;//order条件
		$this->sql_group = null;//group条件
		$this->sql_field = null;//字段条件
		$this->sql_join = null;//联表查询
	} 

	//按照数据库的类型建立连接
	private function connect(){
		if(empty($this->conn) || !$this->conn){
			switch (strtolower($this->config['DB_TYPE'])) {
				case 'mysqli':{
					$this->conn = $this->_mysql_connect();
				}break;
				default:{
					E('数据库类型无法确定，无法建立连接！！！');
				}break;
			}
		}
	}

	//按照数据库的类型进行关闭数据库
	private function close(){
		switch (strtoupper($this->config['DB_TYPE'])) {
			case 'mysqli':{
				$this->_mysql_close($this->conn);
			}break;
			default:{
				E('数据库类型无法确定，无法建立连接！！！');
			}break;
		}
		$this->conn = null;
	}

	//建立mysqli连接
	private function _mysql_connect(){
		$c = $this->config;
		$conn = mysqli_connect ($c['DB_HOST'],$c['DB_USER'],$c['DB_PWD'], $c['DB_NAME'],$c['DB_PORT']);
		if (mysqli_connect_errno()) {
		    E('Failed to connect to MySQL: ('.mysqli_connect_errno() .')'); 
		}
		mysqli_set_charset($conn, "utf8");
		return $conn;
	}

	// 关闭mysqli连接
	private function _mysql_close($conn){
		mysqli_close($conn);
	}

	//通过sql语句进行数据库查询
	public function query($sql){
		if(empty($sql) || trim($sql) ===''){
			return false;
		}
		if(empty($this->conn) || $this->conn){
			$this->connect();
		}
		$result = mysqli_query($this->conn,$sql);
		if(!is_bool($result) && !is_numeric($result) && !is_string($result)){//只有资源类型的才处理
			$list = array();
			while($item = mysqli_fetch_array($result,MYSQLI_ASSOC)){
				$list[] = $item;
			}
			return $list;
		}else{
			return $result;
		}
	}

	//分组的条件
	public function having($having){
		if(is_string($having)){
			$this->sql_having .= ' '.$having.' ';	
		}
		return $this;
	}

	//分组的信息
	public function group($group){
		if(is_array($group)){
			foreach ($group as $key => $value) {
				if(!is_numeric($key)){
					unset($group[$key]);
				}
			}
			$group = implode(',',$group);
		}
		$this->sql_group .= $group;
		return $this;
	}

	//条件设置
	/**在条件中可能出现的情况有：
		1、字符串
		2、数组(定义逻辑符__logic)
			array(
				'column' => 'str',
				'column1' => array(
					'lt'=> '',
					'gt'=> '',
					'__logic' =>'and'
				),
				'column2' => array(
					10,
					20,
					'__logic' => 'between'
				),
				'__logic' => 'OR',
			)
	*/
	/* 功能：设置查询条件
	 * 参数： 
	 	$logic  //数组中逻辑的名字
	 	$connectWord //与上一个where的连接符 AND | OR
	 * 返回：
		本类，可以进行链式操作
	 */
	public function where($where,$logic=null,$connectWord = null){
		//不知道后面是否有条件添加，那么首先先添加，到最后再去除
		$connectWord = empty($connectWord)?'AND':$connectWord;
		$connectWord = ' '.$connectWord.' ';
		if(!empty($this->sql_where)){
			$this->sql_where .= $connectWord;
		}

		//查询条件拼接
		if(empty($where)){
			//如果传入的条件为空，那么什么都不处理
		}else if(is_string($where)){//如果是字符串，那么就简单的加入到查询字符串中
			$this->sql_where .=' '.trim($where,' ');
		}else if(is_array($where)){
			if(empty($logic)){
				$logicKey  = '__logic';//定义逻辑运算符定义
			}			
			$where = encodeStr($where);//对数组进行所有数据转义（防SQL注入）
			$logicStr = isset($where[$logicKey])?strtoupper($where[$logicKey]):'AND';
			foreach ($where as $k => $v) {
				if(is_numeric($k) || $k === $logicKey){
					continue;//如果是索引数组或者是逻辑符定义那么就不做处理
				}else{
					//判断对应的条件是否是数组
					if(is_array($v)){
						if(!isset($v[$logicKey])){
							continue;//如果没有逻辑操作符，那么就不做处理
						}
						$cArr = array();
						foreach ($v as $k1 => $v1) {
							if($k1 === $logicKey){
								continue;
							}else{
								if(is_numeric($k1)){
									$cArr[] = $v1;
								}else if(is_string($k1)){
									$cArr[] = '(`'.$k.'` '.strtoupper($k1).' \''.$v1.'\')';
								}else{
									return;
								}
							}
						}
						$tempSql = '';
						switch (strtolower($v[$logicKey])) {
							case 'between':
									$tempSql = '(`'.$k.'` BETWEEN \''.implode('\' AND \'',$cArr).'\')';
								break;
							case 'like':
									$tempSql = '(`'.$k.'` LIKE \'%'.implode('%\' OR '.$k.' LIKE \'%',$cArr).'%\')';
								break;
							case 'in':
									$tempSql = '(`'.$k.'` IN(\''.implode('\' , \'',$cArr).'\'))';
								break;
							default:
								$tempSql = ' '.implode(' '.strtoupper($v[$logicKey]).' ',$cArr);
								break;
						}

						if(!empty($this->sql_where)){
							$this->sql_where .= ' '.$logicStr;
						}
						$this->sql_where .= ' '.$tempSql.' ';
					}else if(is_numeric($v) || is_string($v)){
						if(!empty($this->sql_where)){
							$this->sql_where .= ' '.$logicStr;
						}
						$this->sql_where .= ' (`'.$k.'`=\''.$v.'\') '; 
					}
				}
			}
		}
		//如果没有添加到条件，那么需要去掉连接符
		$this->sql_where = rtrim($this->sql_where,$connectWord);
		return $this;
	}

	//limit条件设置
	public function limit($limit){
		$this->sql_limit = $limit;
		return $this;		
	}

	//分页
	public function page($pageNow=1,$pageSize=999){
		if(is_numeric($pageNow) || is_numeric($pageSize)){
			$pn = intval($pageNow);
			$pn = $pn < 1? 1: $pn;
			$ps = intval($pageSize);
			$this->limit((($pn-1)*$ps).','.$ps);
		}
		return $this;
	}

	//字段选择
	public function field($field){
		if(!empty($field)){
			if(is_array($field)){
				$field = implode('`,`', $field);
			}
			$this->sql_field .= '`'.$field.'`';
		}
		return $this;
	}

	//查询出结果集并且按照要求的字段进行封装
	public function getField($field){
		$field = trim($field);//字段处理
		//整理需要查询的字段
		if(is_string($field)){
			$this->field($field);
		}else{
			return null;
		}

		//查询字段并封装为数组
		$result = $this->select();
		if($result){
			if(is_array($result)){
				$returnArr = array();
				foreach ($result as $key => $value) {
					$returnArr[] = $value[$field];
				}
				return $returnArr;
			}
		}	
		return null;
	}

	//order设置
	public function order($order){
		if(is_array($order)){
			foreach ($order as $key => $value) {
				if(!is_numeric($key)){
					unset($order[$key]);
				}
			}
			$order = implode(',',$order);
		}
		$this->sql_order .= $order;
		return $this;
	}

	//联合查询(默认左连接)
	public function join($condition,$type = 'LEFT'){
		$this->sql_join .= ' '.$type .' JOIN '.$condition.' ';
		return $this;
	}

	//查询所有的数据($clearCondition 是否查询完毕清空条件)
	public function select($clearCondition = true){
		$result = null;//结果集
		$field = empty($this->sql_field)?'*':$this->sql_field;
		$sql = 'SELECT '.$field.' FROM '.$this->tableName.' as t1 ';
		if(!empty($this->sql_join)){
			$sql .= ' '.$this->sql_join;
		}
		if(!empty($this->sql_where)){
			$sql .=' WHERE '.$this->sql_where;
		}
		if(!empty($this->sql_group)){
			$sql .= ' GROUP BY '.$this->sql_group;
			if(!empty($this->sql_having)){
				$sql .= ' HAVING　'.$this->sql_having;
			}
		}
		if(!empty($this->sql_order)){
			$sql .= ' ORDER BY '.$this->sql_order;
		}
		if(!empty($this->sql_limit)){
			$sql .= ' LIMIT '.$this->sql_limit;
		}
		$this->lastSql = $sql;
		if($clearCondition){
			$this->clearCondition();//查询完毕，清空查询条件
		}		
		return $this->query($sql);
	}

	//获取条数($clearCondition 是否查询完毕清空条件)
	public function count($clearCondition = true){
		$result = null;//结果集
		$sql = 'SELECT count(*) as c FROM '.$this->tableName.' ';
		if(!empty($this->sql_where)){
			$sql .=' WHERE '.$this->sql_where;
		}
		if(!empty($this->sql_group)){
			$sql .= ' GROUP BY '.$this->sql_group;
			if(!empty($this->sql_having)){
				$sql .= ' HAVING　'.$this->sql_having;
			}
		}
		$this->lastSql = $sql;
		if($clearCondition){
			$this->clearCondition();//查询完毕，清空查询条件
		}
		$result = $this->query($sql);
		if($result){
			return $result[0]['c'];
		}else{
			return $result;
		}
	}

	//按照分页获取数据
	public function getPage($pageNow=1,$pageSize=10){
		$pageNow = intval($pageNow);
		$pageSize = intval($pageSize);
		$count = $this->count(false);
		$this->page($pageNow,$pageSize);
		return array(
			'pageSize' => $pageSize,
			'pageNow' => $pageNow,
			'pageNum' => ceil($count/$pageSize),
			'count' => $count,
			'data' => $this->select()
		);
	}


	//查询一条记录
	public function find(){
		$this->limit(1);
		$result = $this->select();
		return ($result && count($result) ===1)?$result[0]:null;
	}

	//添加记录
	public function add($data){
		if(!is_array($data)){
			return 0;
		}else{
			$cList = array();
			$vList = array();
			foreach (encodeStr($data) as $c => $v) {//对数组进行所有数据转义（防SQL注入）
				$cList[] = '`'.$c.'`';
				$vList[] = '\''.$v.'\'';
			}
			$this->lastSql = 'INSERT INTO '.$this->tableName.' ('.implode(',',$cList).') VALUES ('.implode(',', $vList).')';
			return $this->query($this->lastSql)?mysqli_insert_id($this->conn):0;
		}
	}

	//修改记录
	public function save($data){
		if(empty($this->sql_where) || !is_array($data)){
			return 0;
		}
		$updateArr = array();
		//对数组进行所有数据转义（防SQL注入）
		foreach (encodeStr($data) as $k => $v) {
			$updateArr[] = '`'.$k.'` = \''.$v.'\'';
		}
		if(count($updateArr)<1){
			return 0;
		}
		$this->lastSql = 'UPDATE '.$this->tableName.' SET '.implode(',',$updateArr).' WHERE '.$this->sql_where;
		return $this->query($this->lastSql)?mysqli_affected_rows($this->conn):0;
	}

	//删除记录
	public function delete(){
		if(empty($this->sql_where)){
			return 0;
		}
		$this->lastSql = 'DELETE FROM '.$this->tableName.' WHERE '.$this->sql_where;
		return $this->query($this->lastSql)?mysqli_affected_rows($this->conn):0;
	}
	
	public function __destory(){
		$this->close();		
	}
}
