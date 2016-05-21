<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class funController extends appController
{
	function __construct()
	{
		if(!$this->isAdmin()) {
			redirectAlert('/', '嘿嘿....');
		}
		parent::__construct();
	}

	/**
	 * 生成10条邀请码
	 */
	function generateInviteCode() {
		$codes = [];
		$num = 10;
		while(true) {
			//查询code是否存在
			$code = $this->createNoncestr();
			$params = ['invite_code' =>$code];
			$check = Model_inviteCode::meta()->getCode($params);
			if(!empty($check)) {
				continue;
			}
			$codes[] = "('','{$code}',0)";
			if(count($codes) == $num) {
				break;
			}
		}
		$values = implode($codes, ',');
		$insert = "INSERT INTO invite_code(`id`, `invite_code`, `status`) VALUES{$values}";
		run_sql($insert);
	}

	/**
	 * 激活充值
	 */
	public function balanceAct() {
		$data = ['status' => 0, 'msg' => 'error'];
		try{

			$id = intval(v('id'));
			if($id == 0) {
				throw new Exception("id错了");
			}
			$balanceRecord = Model_balance::meta()->getRecord(['id' => $id]);
			if(empty($balanceRecord)) {
				throw new Exception("找不到记录");
			}
			$balanceRecord = $balanceRecord[0];
			$actBalance = $balanceRecord['point'];
			$params = [
			    'cond' => ['id' => $id],
			    'value' => ['status' => 1]
			];
			$update = Model_balance::meta()->updateRecord($params);
			if(!$update) {
				throw new Exception("激活金额失败");
			}
			//将激活的金额追加到用户总账上
			$memberInfo = Model_member::meta()->getRecord(['id' => $balanceRecord['u_id']]);
			if(empty($memberInfo)) {
				throw new Exception("用户找不到了".$balanceRecord['u_id']);
			}
			$balance = $memberInfo[0]['balance'];
			$newBalance = bcaddFloat($balance, $actBalance, 2);
			$memberParams = [
			    'cond' => ['id' => $balanceRecord['u_id']],
			    'value' => ['balance' => $newBalance]
			];
			$memberUpdate = Model_member::meta()->updateRecord($memberParams);
			if(!$memberUpdate) {
				throw new Exception("激活金额加到用户总账上失败");
			}

			$data = ['status' => 1, 'msg' => 'ok'];
			
		} catch(Exception $ex) {
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}

		echo json_encode($data);
		exit();
	}

	public function admBalance() {
		
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			$uIds   = v('u_ids');
			$amount = floatval(v('amount'));
			$type   = v('type');
		    if($amount <= 0 || $uIds == false) {
		    	throw new Exception('老大金额还有人别忘记了');
		    }
			//插入充值记录，标识为返利，并记录总账
			$error = [];
			foreach ($uIds as $uid) {
				$params = [
					'u_id' => $uid,
					'type' => $type,
					'point'=> $amount
				];
				$add = Model_balance::meta()->addRecord($params);
				if(!$add['status']) {
					$error[] = $params;
				}
			}
			if(count($error)) {
				throw new Exception(json_encode($error));
			}
			$data = ['status' => 1, 'msg' => 'ok'];

		} catch(Exception $ex) {
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}

		echo json_encode($data);
		exit();
	}

	/**
	 * 	作用：产生随机字符串，不长于10位
	 */
	public function createNoncestr($length = 10) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ ) {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	/**
	 * 订馆子
	 */
	public function addMenu() {
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			$menuName   = v('menuName');
			$menuLink = v('menuLink');
		    if($menuName == '' || $menuLink == '') {
		    	throw new Exception('必填');
		    }
			//插入充值记录
			$insertData = ['name' => $menuName, 'link' => $menuLink];
			$insert = Model_menu::meta()->addMenu($insertData);
			if(!$insert) {
				throw new Exception('插入失败');
			}
			$data = ['status' => 1, 'msg' => 'ok'];
		} catch(Exception $ex) {
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}

		echo json_encode($data);
		exit();
	}

	public function setMenu() {
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			$id   = intval(v('id'));
		    if($id == '') {
		    	throw new Exception('必选');
		    }
			$set = Model_menu::meta()->setMenu($id);
			if($set === false) {
				throw new Exception('设置失败');
			}
			$data = ['status' => 1, 'msg' => 'ok'];
		} catch(Exception $ex) {
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}

		echo json_encode($data);
		exit();
	}


	/**
	 * 判断是否管理员
	 */
	private function isAdmin() {
		$role = Model_member::meta()->getMemberRole();
		return $role == Model_member::ADMIN;
	}
}