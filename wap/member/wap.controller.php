<?php
/*
* $Author ：PHPYUN开发团队
*
* 官网: http://www.phpyun.com
*
* 版权所有 2009-2018 宿迁鑫潮信息技术有限公司，并保留所有权利。
*
* 软件声明：未经授权前提下，不得用于商业运营、二次开发以及任何形式的再次发布。
 */
class wap_controller extends common{ 

	function __construct($tpl,$db,$def="",$model="index",$m="") {
		$this->common($tpl,$db,$def,$model,$m);
		
		if($this->usertype=='1' && $this->config['user_resume_status']=='1' && $_GET['c']!='addresume' && $_GET['c']!='kresume'&& $_GET['c']!='idcard'&& $_GET['c']!='info'&& $_GET['c']!='userLog'){
			
			$myresumenum=$this->obj->DB_select_num("resume_expect","`uid`='".$this->uid."'");
			if($myresumenum<1){
				header("location:"."index.php?c=addresume");
			}
		}
		if($this->usertype=='2'){
			$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
			$guweninfo=$this->obj->DB_select_once("company_consultant","`id` ='".$company['conid']."'");
			if($guweninfo['logo']&&file_exists(str_replace('./',APP_PATH,$guweninfo['logo']))){
				$guweninfo['logo']=str_replace("./",$this->config['sy_weburl']."/",$guweninfo['logo']);
			}else{
				$guweninfo['logo']=$this->config['sy_weburl'].'/'.$this->config['sy_guwen'];
			}
			if (!$guweninfo){
			    $sy_qq=explode(',', $this->config['sy_qq']);
			    $sy_qq = $sy_qq[0];
			    $this->yunset('sy_qq',$sy_qq);
			}
			$this->yunset("guweninfo",$guweninfo);
		}
		include PLUS_PATH."/tplmoblie.cache.php";
		$this->yunset('tplmoblie',$tplmoblie);
		$this->yunset('membernav',1);
	}
	function waplayer_msg($msg,$url='1',$tm=2){
		$msg = preg_replace('/\([^\)]+?\)/x',"",str_replace(array("（","）"),array("(",")"),$msg));
		$layer_msg['msg']=$msg; 
		$layer_msg['url']=$url;
		$layer_msg['tm']=$tm;
		$msg = json_encode($layer_msg);
		echo $msg;die;
	}

	function member_log($content,$opera='',$type=''){
		if($this->uid){
			$value="`uid`='".(int)$this->uid."',";
			$value.="`usertype`='".(int)$this->usertype."',";
			$value.="`content`='".$content."',";
			$value.="`opera`='".$opera."',";
			$value.="`type`='".$type."',";
			$value.="`ip`='".fun_ip_get()."',";
			$value.="`ctime`='".time()."'";
			$this->obj->DB_insert_once("member_log",$value);
		}
	}
	function resume($table,$where){
		include(LIB_PATH."page.class.php");
		$limit=10;
		$page=$_GET['page']<1?1:$_GET['page'];
		$ststrsql=($page-1)*$limit;
		$page_url = "index.php?c=".$_GET['c']."&page={{page}}";
		$count = $this->obj->DB_select_alls($table,"resume_expect",$where." ORDER BY a.id DESC");
 		$num = count($count);
 		$pages=ceil($num/$limit);
		if($pages>1){
			$page = new page($page,$limit,$num,$page_url);
			$pagenav=$page->numPage();
			$this->yunset("pagenav",$pagenav);	
		}
 		
		
		$list = $this->obj->DB_select_alls($table,"resume_expect",$where."  ORDER BY a.id DESC LIMIT $ststrsql,$limit","a.*,a.id as did,b.*");
		include PLUS_PATH."/user.cache.php";
		include PLUS_PATH."/job.cache.php";
		include(CONFIG_PATH."db.data.php");
		unset($arr_data['sex'][3]);
		$this->yunset("arr_data",$arr_data);
		if(is_array($list)){
			$uid=array();
			foreach($list as $val){
				if(in_array($val['uid'],$uid)==false){
					$uid[]=$val['uid'];
				}
			}
			$resume=$this->obj->DB_select_all("resume","`uid` in(".pylode(',',$uid).")","`uid`,`name`");
			foreach($list as $k=>$v){
				foreach($resume as $value){
					if($value['uid']==$v['uid']){
						$list[$k]['name']=$value['name'];
					}
				}
				$list[$k]['sex']=$arr_data['sex'][$v['sex']];
				$list[$k]['exp']=$userclass_name[$v['exp']];
				$list[$k]['edu']=$userclass_name[$v['edu']];
				if($v['job_classid']!=""){
					$job=@explode(",",$v['job_classid']);
					$joblist=array();
					foreach($job as $val){
						$joblist[]=$job_name[$val];
					}
					$list[$k]['joblist']=@implode(",",$joblist);
				}
			}
		}
		$this->yunset("list",$list);
	}
	function wap_up_pic($post,$file){
		preg_match('/^(data:\s*image\/(\w+);base64,)/', $post, $result);
		$uimage=str_replace($result[1], '', str_replace('#','+',$post));
 		
		if(in_array(strtolower($result[2]),array('jpg','png','gif','jpeg'))){
			$new_file = time().rand(1000,9999).".".$result[2];
		}else{
			$new_file = time().rand(1000,9999).".jpg";
		}
		
		$im = imagecreatefromstring(base64_decode($uimage));
		if ($im === false) {
			$data['msg']='请重新上传';
		}
		if (!file_exists(DATA_PATH."upload/".$file."/".date('Ymd')."/")){
			
			mkdir(DATA_PATH."upload/".$file."/");
			chmod(DATA_PATH."upload/".$file."/",0777);
			
			mkdir(DATA_PATH."upload/".$file."/".date('Ymd')."/");
			chmod(DATA_PATH."upload/".$file."/".date('Ymd')."/",0777);
			
		}
		$re=file_put_contents(DATA_PATH."upload/".$file."/".date('Ymd')."/".$new_file, base64_decode($uimage));
		chmod(DATA_PATH."upload/".$file."/".date('Ymd')."/".$new_file,0777);
		return array('re'=>$re,'new_file'=>$new_file,'errormsg'=>$data['msg']);
	}
}
?>