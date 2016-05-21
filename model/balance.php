<?php
/**
 * 余额表
 */
class Model_balance extends Base_model
{
	CONST TYPE_ADD = 'add';
	CONST TYPE_REBATE = 'rebate';
	CONST TYPE_REFUND = 'refund';

	static public $balanceType = [
	    self::TYPE_ADD    => '充值',
	    self::TYPE_REBATE => '返利',
	    self::TYPE_REFUND => '退款'
	];
	/**
	 * 表名
	 */
	public $_table = 'balance';

	public function getRecordGroup($params) {
		$balanceRecord = $this->getRecord($params, 'id desc');
		$balanceInfo = [];
		foreach($balanceRecord as $balance) {
			$type = $balance['type'];
			if(!isset($balanceInfo[$type])) {
				$balanceInfo[$type] = [];
			}
			$balanceInfo[$type][] = $balance;
		}
		return $balanceInfo;
	}

	/**
	 * 取出所有充值记录
	 * 根据用户名分组
	 */
	public function getAllRecord() {
		$allRecord = $this->getRecord([], 'id desc');
		$returnData = [];
		$members = [];
		foreach($allRecord as $record) {
			$uId = $record['u_id'];
			if(isset($members[$uId])) {
				$userName = $members[$uId];
			} else {
				$memberInfo = Model_member::meta()->getRecord(['id' => $uId]);
				if(empty($memberInfo)) {
					continue;
				}
				$userName = $memberInfo[0]['nick_name'];
			}
			if(!isset($returnData[$userName])) {
				$returnData[$userName] = [];
			}
			$returnData[$userName][] = $record;
		}
		return $returnData;
	}

	/**
	 * 统计年度账户报表
	 */
	public function getBalanceReport(array $params) {
	    foreach ($params as $year) {
	    	$startDate = strtotime($year . '0101000000');
	    	$endDate   = strtotime(($year + 1) . '0101000000');

	    	echo $sql = "SELECT * FROM {$this->_table} WHERE insert_date >= {$startDate} AND insert_date < {$endDate}";
	    	exit();
	    }
	}


	public function addRecord(array $dataParams) {
		$data = ['status' => 0, 'msg' => 'error'];
		try{
			//start_trans();
			$isAdmin = Model_member::meta()->isAdmin();
			$u_id = intval($dataParams['u_id']);

			if($u_id == 0) {
				throw new Exception("找不到用户");
			}
			
			$time = time();
			$type = $dataParams['type'];
			$point= $dataParams['point'];

			//返利和退款 统计到用户表余额
			switch ($type) {
				case self::TYPE_REBATE:
				case self::TYPE_REFUND:
				    if(!$isAdmin) {
				    	throw new Exception("权限不够，别乱搞");
				    }
				    $doBalance = Model_member::meta()->updateBalance($point, Model_member::BALANCE_ADD, $u_id);
				    if(!$doBalance) {
						throw new Exception("累加余额失败");
					}
					$status = 1;
				break;
				case self::TYPE_ADD:
				    $status = 0;
				break;
				
				default:
				break;
			}

			$sql = "INSERT INTO `{$this->_table}`(`id`, `u_id`, `point`, `type`, `status`, `insert_date`)
			        VALUES(null, '{$u_id}', ?s, ?s, ?i, '{$time}')";
			$prepare = [
			    $point,
			    $type,
			    $status,
			];
			$sql = prepare($sql, $prepare);
			$do  = run_sql($sql);
			if(!$do) {
				throw new Exception("操作余额失败");
			}


			$data = ['status' => 1, 'msg' => 'ok'];
			//commit_trans();
		} catch(Exception $ex) {
			//rollback_trans();
			$data = ['status' => 0, 'msg' => $ex->getMessage()];
		}

		return $data;
		
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

