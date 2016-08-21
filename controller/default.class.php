<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class defaultController extends appController
{
	private $_data = [];
	private $_isAdmin = false;
	function __construct()
	{
		if(!sessionIsExist('loginStatus'))
		{
			setSession('loginStatus', false);
		}
		parent::__construct();
		$this->_isAdmin = Model_member::meta()->isAdmin();
		$this->_data['isAdmin'] = $this->_isAdmin;
	}
	
	function index()
	{
		$this->_data['title'] = $this->_data['top_title'] = '首页';

		//取出今日菜单
		$menu = Model_menu::meta()->getMenu();
		if(empty($menu)) {
			$menu['link'] = 'static/image/e.gif';
		}

		//用户登录态
		$userInfo = Model_member::meta()->getLoginStatus();
		if($userInfo !== false) {
			$this->_data['userInfo'] = $userInfo;
			$this->_data['loginStatus'] = true;
		} else {
			$this->_data['loginStatus'] = false;
		}
		$this->_data['menu'] = $menu;
		render( $this->_data );
	}

	function login() {
		$this->_data = [];

		if(v('name') != '' && v('passwd') != '') {
			$params = [
			    'name' => v('name'),
			    'passwd' => md5(v('passwd'))
			];
			$userInfo = Model_member::meta()->getUser($params);
			if(is_null($userInfo)) {
				$info = ['status' => 0, 'msg' => '登录失败'];
			} else {
				$info = ['status' => 1, 'msg' => '登录成功'];
				setSession('loginStatus', true);
				setSession('loginInfo', $userInfo);
			}
			echo json_encode($info);
		} else {
			render( $this->_data, 'web', 'default/login' );
		}
		
		exit();
	}

	/**
	 * 使用邀请码注册
	 */
	function register() {
		$this->_data = [];
		if(v('name') != '' && v('passwd') != '' && v('code') != '') {
			$params = [
			    'name' => v('name'),
			    'passwd' => v('passwd'),
			    'code' => v('code')
			];
			

			$register = Model_member::meta()->register($params);
			if(!$register['status']) {
				$info = $register;
			} else {
				$info = ['status' => 1, 'msg' => '注册成功'];
				setSession('loginStatus', true);
				setSession('loginInfo', $register['userInfo']);
			}
			echo json_encode($info);
		} else {
			render( $this->_data, 'web', 'default/register' );
		}

		exit();
	}

	function loginout() {
		setSession('loginStatus', false);
		setSession('loginInfo', '');
		header("Location:/");
	}

	function eat()
	{
		$this->_data = ['status' => 0, 'msg' => 'error'];
		$eatItem = v('eatItem');
		$eatPrice= floatval(v('eatPrice'));
		$this->_data['status'] = 0;
		if($eatItem == '' || $eatPrice <= floatval(0)) {
			$this->_data['msg'] = '请输入正确的信息';
		} else {
			//写入db
			$this->_dataParams = [
			    'name'   => z($eatItem),
			    'amount' => $eatPrice
			];
			$this->_data = Model_consume::meta()->addRecord($this->_dataParams);
		}
		
		render( $this->_data, 'web', 'default/eat' );
	}
	
	/**
	 * 个人中心
	 */
	function my()
	{
		$this->_data['title'] = $this->_data['top_title'] = '个人中心';
		//用户登录态
		$this->setUserInfo();
		$userInfo = $this->_data['userInfo'];
		$params = [
		    'u_id' => $userInfo['id']
		];

		$userInfoDB = Model_member::meta()->getRecord(['id' => $userInfo['id']]);

		//充值明细
		$balanceInfoGroup = Model_balance::meta()->getRecordGroup($params);
		$this->_data['balanceInfoGroup'] = $balanceInfoGroup;

		//消费明细
		$consumeInfo = Model_consume::meta()->getConsumeRecord($params);
		$this->_data['consumeInfo'] = $consumeInfo;
		$this->_data['userInfoDB'] = $userInfoDB[0];
		$this->_data['loginStatus'] = true;
		render( $this->_data, 'web', 'default/my' );
	}

	/**
	 * 管理员权限:审核充值，添加返利
	 */
	function admin() {
		if(!$this->_isAdmin) {
			exit('error');
		}
		$this->_data['title'] = $this->_data['top_title'] = '管理员权限';
		$this->setUserInfo();
		$userInfo = $this->_data['userInfo'];
		
		$allUser = Model_member::meta()->getRecord([], 'id desc');
		$this->_data['allUser'] = $allUser;

		$this->_data['consumesRecord'] = Model_consume::meta()->getConsumeRecordGroupByDate();

		$this->_data['menus'] = Model_menu::meta()->getRecord();

		$this->_data['allBalance'] = Model_member::meta()->countBalance()['allBalance'];
		$this->_data['loginStatus'] = true;
		render( $this->_data, 'web', 'default/admin' );
	}

	/**
	 * 激活充值
	 */
	function activate() {
		if(!$this->_isAdmin) {
			exit('error');
		}
		$this->_data['title'] = $this->_data['top_title'] = '激活充值';
		//取出全部会员的充值记录
		$getAllRecord = Model_balance::meta()->getAllRecord();
		$this->_data['getAllRecord'] = $getAllRecord;
		$this->_data['allBalance'] = Model_member::meta()->countBalance()['allBalance'];
		$this->_data['loginStatus'] = true;
		render( $this->_data, 'web', 'default/activate' );
	}

	/**
	 * 充值
	 */
	function recharge() {
		$eatPrice= floatval(v('payAmount'));
		$this->_data = ['status' => 0, 'msg' => 'error'];
		if($eatPrice <= floatval(0)) {
			$this->_data['msg'] = '请输入正确的金额';
		} else {
			//写入db
			$userInfo = Model_member::meta()->getLoginUser();
			$this->_dataParams = [
			    'point' => $eatPrice,
			    'type'  => Model_balance::TYPE_ADD,
			    'u_id'  => $userInfo['id']
			];
			$this->_data = Model_balance::meta()->addRecord($this->_dataParams);
		}
		echo json_encode($this->_data);
		exit();
	}

	/**
	 * 年度报表
	 */
	public function report() {
		$years = [2015];
		Model_balance::meta()->getBalanceReport($years);
	}


	private function setUserInfo() {
		//用户登录态
		$userInfo = Model_member::meta()->getLoginUser();
		if(!$userInfo) {
			header("Location:/");
		}
		$this->_data['userInfo'] = $userInfo;
	}
	
	function ajax_test()
	{
		return ajax_echo('1234');
	}
	
	function rest()
	{
		$this->_data = array(  );
		if( intval(v('o')) == 1 )
		{
			$this->_data['code'] = 123;
			$this->_data['message'] = 'RPWT';
		}
		else
		{
			$this->_data['code'] = 0 ;
			$this->_data['data'] = array( '2' , '4' , '6' , '8' ); 
		}
		
		render( $this->_data , 'rest' );
	}
	
	function mobile()
	{
		$this->_data['title'] = $this->_data['top_title'] = 'JQMobi';
		render( $this->_data , 'mobile' );
	}
	
	function ajax_load()
	{
		return ajax_echo('Hello ' . date("Y-m-d H:i:s"));
	}
	
	function test()
	{
		$this->_data['title'] = $this->_data['top_title'] = '自动测试页';
		$this->_data['info'] = '根据访问来源自动切换Layout';
		
		return render( $this->_data );
	}
	
	function sql()
	{
		db();
		echo $sql = prepare( "SELECT * FROM `user` WHERE `name` = ?s AND `uid` = ?i AND `level` = ?s LIMIT 1" , array( "Easy'" , '-1', '9.56' ) );	
	}
	
	
}
	