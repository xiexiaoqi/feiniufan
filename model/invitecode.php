<?php
class Model_inviteCode extends Base_model
{
	/**
	 * 表名
	 */
	public $_table = 'invite_code';


	/**
	 * 取出code
	 */
	public function getCode(array $params) {
		$param = $this->parseDBParam($params);
		$sql = "SELECT * FROM {$this->_table} WHERE {$param['cond']}";
		$sql = prepare($sql, $param['values']);
		return get_line($sql);
	}

	/**
	 * 批量插入code
	 */
	public function insertCode(array $codes) {
		$codeStr = [];
		foreach($codes as $code) {
		    $codeStr[] = "('','{$code}',0)";
		}
		$values = implode($codeStr, ',');
		$insert = "INSERT INTO {$this->_table}(`id`, `invite_code`, `status`) VALUES{$values}";
		run_sql($insert);
	}

	public function updateCode(array $params) {
		$paramCond = $this->parseDBParam($params['cond']);
		$paramVal  = $this->parseDBParam($params['value']);
		$setFeilds = str_replace(' AND ', ', ', $paramVal['cond']);
		$condFeilds= $paramCond['cond'];

		$val = array_merge($paramVal['values'], $paramCond['values']);
		$update = "UPDATE {$this->_table} SET $setFeilds WHERE {$condFeilds}";
		$update = prepare($update, $val);
		return run_sql($update);

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