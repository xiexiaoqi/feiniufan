<?php

// db functions
function db( $host = null, $port = null, $user = null, $password = null, $db_name = null, $dns = 'mysql' )
{
	$db_key = MD5( $host .'-'. $port .'-'. $user .'-'. $password .'-'. $db_name .'-'. $dns );
	
	if( !isset( $GLOBALS['LP_'.$db_key] ) )
	{
		include_once( AROOT .  'config/db.config.php' );
		//include_once( CROOT .  'lib/db.function.php' );
		
		$db_config = $GLOBALS['config']['db'];
		if( $dns == null ) $dns = $db_config['dns'];
		if( $host == null ) $host = $db_config['db_host'];
		if( $port == null ) $port = $db_config['db_port'];
		if( $user == null ) $user = $db_config['db_user'];
		if( $password == null ) $password = $db_config['db_password'];
		if( $db_name == null ) $db_name = $db_config['db_name'];

		try{
			$dbh = new PDO( "{$dns}:host={$host};dbname={$db_name}", $user, $password );
			$GLOBALS['LP_'.$db_key] = $dbh;
		} catch (PDOException $ex) {
			echo 'can\'t connect to database';
			return false;
		}
		$dbh->query("SET NAMES 'UTF8'");
	}
	
	return $GLOBALS['LP_'.$db_key];
}

function s( $str , $db = NULL )
{
	return addslashes($str)  ;
}

// $sql = "SELECT * FROM `user` WHERE `name` = ?s AND `id` = ?i LIMIT 1 "
function prepare( $sql , $array )
{
	foreach( $array as $k=>$v )
		$array[$k] = s($v);
	
	$reg = '/\?([is])/i';
	$sql = preg_replace_callback( $reg , 'prepair_string' , $sql  );
	$count = count( $array );
	for( $i = 0 ; $i < $count; $i++ )
	{
		$str[] = '$array[' .$i . ']';	
	}
	
	$statement = '$sql = sprintf( $sql , ' . join( ',' , $str ) . ' );';
	eval( $statement );
	return $sql;
	
}

function prepair_string( $matches )
{
	if( $matches[1] == 's' ) return "'%s'";
	if( $matches[1] == 'i' ) return "'%d'";	
}


function get_data( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	
	$GLOBALS['LP_LAST_SQL'] = $sql;
	$stmt = $db->query($sql);
	if(!$db->errorCode()) {
		return false;
	}
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $data = $stmt->fetchAll();
	if( count( $data ) > 0 )
		return $data;
	else
		return false;
}

function get_line( $sql , $db = NULL )
{
	$data = get_data( $sql , $db  );
	return @reset($data);
}

function get_var( $sql , $db = NULL )
{
	$data = get_line( $sql , $db );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return get_var( "SELECT LAST_INSERT_ID() " , $db );
}

function run_sql( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	$GLOBALS['LP_LAST_SQL'] = $sql;
	$exec = $db->exec($sql);

	if(!$db->errorCode()) {
		return false;
	}
    return $exec;
}

function db_errno( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return $db->errorInfo();
}


function db_error( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return $db->errorInfo();
}

function last_error()
{
	if( isset( $GLOBALS['LP_DB_LAST_ERROR'] ) )
	return $GLOBALS['LP_DB_LAST_ERROR'];
}

function close_db( $db = NULL )
{
	if( $db == NULL )
		$db = $GLOBALS['LP_DB'];
		
	unset( $GLOBALS['LP_DB'] );
}

/**
 * 开启事务
 */
function start_trans($db = NULL) {
	if( $db == NULL ) $db = db();
	$db->beginTransaction();
}

/**
 * 提交事务
 */
function commit_trans($db = NULL) {
	if( $db == NULL ) $db = db();
	$db->commit();
}

/**
 * 回滚事务
 */
function rollback_trans($db = NULL) {
	if( $db == NULL ) $db = db();
	$db->rollBack();
}

/**
 * 返回执行错误code正常情况返回0000
 */
function check_error() {
	if( $db == NULL ) $db = db();
	$code = $db->errorCode();
	if($code == '00000') {
		return true;
	}
	return $code;
}