<?php
class Base_model
{
    protected static $_metas = array();

	protected function __construct() {
		$this->init();
	}
	
	/**
     * 继承类的元对象唯一实例
     *
     * @param string $class
     *
     */
    static function instance($class)
    {
        if (!isset(self::$_metas[$class]))
        {
            self::$_metas[$class] = new $class();
        }
        return self::$_metas[$class];
    }

    protected function db() {
    	
    }

    protected function init() {

    }

    /**
     * 更新数据库信息
     */
    public function updateRecord(array $params) {
        $paramCond = $this->parseDBParam($params['cond']);
        $paramVal  = $this->parseDBParam($params['value']);
        $setFeilds = str_replace(' AND ', ', ', $paramVal['cond']);
        $condFeilds= $paramCond['cond'];

        $val = array_merge($paramVal['values'], $paramCond['values']);
        $update = "UPDATE `{$this->_table}` SET $setFeilds WHERE {$condFeilds}";
        $update = prepare($update, $val);
        return run_sql($update);
    }

    /**
     * 查询信息
     */
    public function getRecord(array $params = [], $order = '') {
        if(!empty($params)) {
            $parseFeild = $this->parseDBParam($params);
            $sql = "SELECT * FROM `{$this->_table}` WHERE {$parseFeild['cond']}";
            $sql = prepare($sql, $parseFeild['values']);
        } else {
            $sql = "SELECT * FROM `{$this->_table}`";
        }

        if($order != '') {
            $sql .= " ORDER BY {$order}";
        }

        
        $data = get_data($sql);
        return $data == false ? [] : $data;
    }

    /**
     * 根据传入的数组，将其解析成where使用的条件和对应的值
     *@param array $data
     *@return array
     */
    public function parseDBParam(array $data) {
        $feilds = [];
        $values = [];

        foreach($data as $f => $v) {
            $type = is_numeric($v) && !is_float($v) ? '?i' : '?s';
            $feilds[] = "{$f} = {$type}";
            $values[]   = $v;
        }

        $cond = implode($feilds, ' AND ');

        return ['cond' => trim($cond), 'values' => $values];
    }
}