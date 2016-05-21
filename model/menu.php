<?php
class Model_menu extends Base_model
{
	/**
	 * 表名
	 */
	public $_table = 'menu';

	/**
	 * 取菜单
	 */
	public function getMenu() {
		$sql = "SELECT * FROM {$this->_table} WHERE used = 1 LIMIT 0, 1";
		$result = get_line($sql);
		$result = $result == false ? [] : $result;
		return $result;
	}

	public function addMenu(array $data) {

		//先更新
		$updateSql = "UPDATE {$this->_table} SET used = 0";
		$update = run_sql($updateSql);
		if($update === false) {
			//更新失败
			return false;
		}
		$sql = "INSERT INTO {$this->_table} (id, name, link, used, insert_date) VALUES(null, ?s, ?s, ?i, ?s)";
		$sql = prepare($sql, [$data['name'], $data['link'], 1, time()]);
		return run_sql($sql);
	}

	public function setMenu($id) {
		//先更新
		$updateSql = "UPDATE {$this->_table} SET used = 0";
		$update = run_sql($updateSql);
		if($update === false) {
			//更新失败
			return false;
		}
		$updateSql = "UPDATE {$this->_table} SET used = 1 where id = {$id}";
		return run_sql($updateSql);
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

