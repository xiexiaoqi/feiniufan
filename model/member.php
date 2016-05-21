<?php

class Model_member extends Base_model
{

	CONST ADMIN = 'admin';
	CONST USER = 'user';
	CONST BALANCE_ADD = 'add';
	CONST BALANCE_SUB = 'sub';
	/**
	 * 表名
	 */
	public $_table = 'member';

	/**
	 * 查询用户信息
	 */
	public function getUser(array $params) {
		$parseFeild = $this->parseDBParam($params);
		$sql = "SELECT * FROM `{$this->_table}` WHERE {$parseFeild['cond']}";
		$sql = prepare($sql, $parseFeild['values']);
		return get_line($sql);
	}

	/**
	 * 统计总金额
	 */
	public function countBalance() {
		$sql = "SELECT SUM(balance) AS allBalance FROM `{$this->_table}`";
		return get_line($sql);
	}

	/**
	 * 用户余额操作
	 */
	public function updateBalance($amount, $act, $u_id) {
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			$amount = floatval($amount);
			if($amount == floatval(0)) {
				$data['status'] = 1;
			} else {
				$userInfoDB = $this->getUser(['id' => $u_id]);

				if(empty($userInfoDB)) {
					throw new Exception('用户不存在');
				}
				
				$balance = $userInfoDB['balance'];
				switch ($act) {
					case self::BALANCE_ADD:
					    //加
					    $newAmount = bcaddFloat($balance, $amount, 2);
					break;

					case self::BALANCE_SUB:
					    //减
					    $newAmount = bcsubFloat($balance, $amount, 2);
					    if($newAmount < 0) {
					    	throw new Exception('余额不足');
					    }
					default:
					break;
				}

				if(isset($newAmount)) {
					//更新
					$params = [
					    'cond' => ['id' => $u_id],
					    'value' => ['balance' => $newAmount]
					];
					$data['status'] = $this->updateRecord($params);
					$data['msg'] = 'ok';
				}
			}
		
		} catch(Exception $ex) {
			$data['status'] = 0;
			$data['msg']    = $ex->getMessage();
		}
		
		return $data;
	}

	/**
	 * 注册
	 */
	public function register(array $params) {
		//判断邀请码是否有效
		$code = $params['code'];
		$codeParam = ['invite_code' => $code, 'status' => 0];
		$checkCode = Model_inviteCode::meta()->getCode($codeParam);
	    if(empty($checkCode)) {
	    	return ['status' => 0, 'msg' => '邀请码无效'];
	    }
		
		//判断用户是否存在
		$name = $params['name'];
		$paramName = ['name' => $name];
		$checkUser = $this->getUser($paramName);
		if(!empty($checkUser)) {
			return ['status' => 0, 'msg' => '用户已存在'];
		}

		//注册： 1 更新邀请码，2：添加用户
		$codeUp = [
		    'cond' => ['invite_code' => $code],
		    'value'=> ['status' => 1]
		];
		$update = 1;//Model_inviteCode::meta()->updateCode($codeUp);
		if($update) {
			//添加用户
			$passwd = md5($params['passwd']);
			$time = time();
			$ip = getRealIp();
			$addMember = "INSERT INTO `{$this->_table}`(`id`, `name`, `passwd`, `status`, `balance`, `role`, `last_login_date`, `invite_code`, `ip`)
                          VALUES(null, ?s, ?s, '1', '', 'user', '{$time}', ?s, '{$ip}')";
            $prepareVal = [$name, $passwd, $code];
            echo $addMember;
            $addMember = prepare($addMember, $prepareVal);
            echo $addMember;
            exit();
            $add = run_sql($addMember);

            var_dump($add);

            if($add) {
            	$loginParam = ['name' => $name];
            	$userInfo = $this->getUser($loginParam);
            	return ['status' => 1, 'userInfo' => $userInfo, 'msg' => '注册成功'];
            } else {
            	return ['status' => 0, 'msg' => '注册失败'];
            }
		}

		return ['status' => 0, 'msg' => '更新邀请码失败'];
	}

	/**
	 * 获取用户登录态
	 */
	public function getLoginStatus() {
		if(sessionIsExist('loginStatus') && getSession('loginStatus') === true ){
			return getSession('loginInfo');
		} else {
			return false;
		}
	}

	public function getLoginUser() {
		$userInfo = $this->getLoginStatus();
		if($userInfo != false) {
			return json_decode($userInfo, true);
		}
		return false;
	}

	/**
	 * 获取登录用户角色
	 */
	public function getMemberRole() {
		$userInfo = $this->getLoginStatus();
		if($userInfo != false) {
			$userInfo = json_decode($userInfo, true);
			return $userInfo['role'];
		}
		return false;
	}

	public function isAdmin() {
		return $this->getMemberRole() == Model_member::ADMIN;
	}

	/**
	 * 类的元数据对象
     *
     * @static
     */
    static function meta()
    {
        return self::instance(__CLASS__);
    }
}