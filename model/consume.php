<?php

/**
 * 消费表
 */
class Model_consume extends Base_model
{
	/**
	 * 表名
	 */
	public $_table = 'consume';

	/**
	 * 添加消费记录
	 */
	public function addRecord(array $params) {
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			//start_trans();
			//取出用户id
			$userInfo = Model_member::meta()->getLoginUser();
			if($userInfo == false) {
				throw new Exception('获取用户信息失败');
			}
			$u_id = $userInfo['id'];
			//取出菜单id
			$menu = Model_menu::meta()->getMenu();
			if(empty($menu)) {
				throw new Exception('获取菜单信息失败');
			}

			$menu_id = $menu['id'];
			$time = time();
			$amount = $params['amount'];
			//判断余额是否足够
			$userInfoDB = Model_member::meta()->getRecord(['id' => $u_id]);
			$balanceCcomp = bccomp($userInfoDB[0]['balance'], $amount);
			if($balanceCcomp < 0) {
				throw new Exception('余额不足');
			}

			$sql = "INSERT INTO `{$this->_table}`(`id`, `u_id`, `menu_id`, `name`, `amount`, `insert_date`) 
			        VALUES(null,'{$u_id}', '{$menu_id}', ?s, ?s, '{$time}')";
			$sql = prepare($sql, [$params['name'], $amount]);
			$insertStatus = run_sql($sql);
			if(!$insertStatus) {
				throw new Exception('记录消费信息失败');
			}
			//扣除金额
		    $updateAmount = Model_member::meta()->updateBalance($amount, Model_member::BALANCE_SUB, $u_id);
		    if(!$updateAmount['status']) {
		    	throw new Exception($updateAmount['msg']);
		    }
		    $data = ['status' => 1, 'msg' => '订餐成功'];
			//commit_trans();
		} catch(Exception $ex) {
			//rollback_trans();
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}
		
		return $data;
	}
	
	/**
	 * 取出消费明细
	 */
	public function getConsumeRecord($params) {
		$consumeInfo = Model_consume::meta()->getRecord($params);
		$menus = [];
		foreach ($consumeInfo as $key => $lsInfo) {
			//取出店名
			$menuId = $lsInfo['menu_id'];
			if(isset($menus[$menuId])) {
				$menuName = $menus[$menuId][0];
				$menuLink  = $menus[$menuId][1];
			} else {
				$menuParams = ['id' => $menuId];
				$menuInfo = Model_menu::meta()->getRecord($menuParams);
				if(empty($menuInfo)) {
					$menuName = '';
					$menuLink = 'javascript:;';
				} else {
					$menuName = $menuInfo[0]['name'];
					$menuLink = $menuInfo[0]['link'];
				}
				$menus[$menuId] = [$menuName, $menuLink];
			}
			$consumeInfo[$key]['menuName'] = $menuName;
			$consumeInfo[$key]['menuLink'] = $menuLink;
		}
		return $consumeInfo;
	}

	/**
	 * 取出消费明细按日期分组
	 */
	public function getConsumeRecordGroupByDate(array $params = []) {
		$consumeInfo = Model_consume::meta()->getRecord($params, 'id desc');
		$countDate = [];
		$consumeInfoGroup = [];
		foreach ($consumeInfo as $key => $lsInfo) {
			$date = date('Y/m/d', $lsInfo['insert_date']);
			if(!isset($consumeInfoGroup[$date])) {
				$consumeInfoGroup[$date] = [];
			}
			//取出用户
			$userInfo = Model_member::meta()->getUser(['id' => $lsInfo['u_id']]);
			$lsInfo['userInfo'] = $userInfo;
			$consumeInfoGroup[$date][] = $lsInfo;
		}
		return $consumeInfoGroup;
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