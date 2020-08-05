<?php

define('BASE_PATH', dirname(__FILE__));

include(dirname(__FILE__).'/global.php');

if (file_exists(BASE_PATH . "/vendor/autoload.php")) {
  include BASE_PATH . "/vendor/autoload.php";
}

//伪静态操作statr
if(isset($_GET['yunurl']) && $_GET['yunurl']){

	$var=@explode('-',str_replace('/','-',$_GET['yunurl']));
	foreach($var as $p){
		$param=@explode('_',$p);
		$_GET[$param[0]]=$param[1];
	}
	unset($_GET['yunurl']);

}

//伪静态操作end

if(isset($_GET['m']) && $_GET['m'] && !preg_match('/^[0-9a-zA-Z\_]*$/',$_GET['c'])){
	$_GET['m'] = 'index';
}

$ModuleName = isset($_GET['m']) ? $_GET['m'] : '';
if($ModuleName=='')	$ModuleName='index';
//默认情况下，调用app/controller下与当前目录名相同的模块
//如当前目录名为ask，则默认调用的是app/controller/ask/下的控制器
//若需要调用与当前目录名不同的模块，则请修改此处的$ModuleName
//$ModuleName='job'，则此时将调用app/controller/job/下的控制器

include(LIB_PATH.'init.php');


?>