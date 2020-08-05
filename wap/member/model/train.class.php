<?php
/* *
* $Author ：PHPYUN开发团队
*
* 官网: http://www.phpyun.com
*
* 版权所有 2009-2018 宿迁鑫潮信息技术有限公司，并保留所有权利。
*
* 软件声明：未经授权前提下，不得用于商业运营、二次开发以及任何形式的再次发布。
*/
class train_controller extends wap_controller{
	function waptpl($tpname){
		$this->yuntpl(array('wap/member/train/'.$tpname));
	}
	function user_shell(){
		$userinfo=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'");
		if($userinfo['name']==""){
			$data['msg']='请先完善基本资料！';
		    $data['url']='index.php?c=info';
			$this->yunset("layer",$data);	
		}
	}	
	function index_action(){
		$info=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'");
		$this->yunset("info",$info);

		$date=date("Ymd"); 
		$reg=$this->obj->DB_select_once("member_reg","`uid`='".$this->uid."' and `usertype`='".$this->usertype."' and `date`='".$date."'"); 
		if($reg['id']){
			$signstate=1;
		}else{
			$signstate=0;
		}
		$this->yunset("signstate",$signstate);
		
		$backurl=Url('wap',array());
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->waptpl('index');
	}
	function info_action(){
		$row=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'");
		if(!$row['logo'] || !file_exists(str_replace('./',APP_PATH,$row['logo']))){
			$row['logo']=$this->config['sy_weburl']."/".$this->config['sy_px_icon'];
		}else{
			$row['logo']=str_replace("./",$this->config['sy_weburl']."/",$row['logo']);
		}
		$this->yunset("row",$row);
		if($_POST['submit']){
			$Member=$this->MODEL("userinfo");
			$_POST=$this->post_trim($_POST);
			if($row['moblie_status']==1){
				unset($_POST['linktel']);
			}else{
				$moblieNum = $Member->GetMemberNum(array("moblie"=>$_POST['linktel'],"`uid`<>'".$this->uid."'"));
				if($_POST['linktel']==''){
					$data['msg']='手机号码不能为空！';
				}elseif($_POST['linktel']&&!CheckMoblie($_POST['linktel'])){
					$data['msg']='手机号码格式错误！';
				}elseif($_POST['linktel']&&$moblieNum>0){
					$data['msg']='手机号码已存在！';
				}else{
					$mvalue['moblie']=$_POST['linktel'];
				}
			
			}
			if($row['email_status']==1){
				unset($_POST['linkmail']);
			}else{
				$emailNum = $Member->GetMemberNum(array("email"=>$_POST['linkmail'],"`uid`<>'".$this->uid."'"));
				if($_POST['linkmail']&&CheckRegEmail($_POST['linkmail'])==false){
					$data['msg']='联系邮箱格式错误！';
				}elseif($_POST['linkmail']&&$emailNum>0){
					$data['msg']='联系邮箱已存在！';
				}else{
					$mvalue['email']=$_POST['linkmail'];
				}
			}
			$_POST['content'] = str_replace(array("&amp;","background-color:#ffffff","background-color:#fff","white-space:nowrap;"),array("&",'background-color:','background-color:','white-space:'),html_entity_decode($_POST['content'],ENT_QUOTES));
			$nid=$this->obj->update_once("px_train",$_POST,array("uid"=>$this->uid));
			if($nid){
				if(!empty($mvalue)){
					$this->obj->update_once('member',$mvalue,array("uid"=>$this->uid));
				}
				$this->obj->member_log("完善基本资料",7);
				if($row['name']==""){
					$this->MODEL('integral')->company_invtal($this->uid,$this->config['integral_userinfo'],true,"首次填写基本资料",true,2,'integral',25);
				}
				$data['msg']='更新成功！';
				$data['url']='index.php';
			}else{
				$data['msg']='更新失败！';
				
			}
			echo json_encode($data);die;
			
		}
		$this->yunset($this->MODEL('cache')->GetCache(array('com','city','subject')));
		$this->yunset('header_title',"账户设置");
		$this->waptpl('info');
	}
	function subject_action(){
		if($_GET['pause_status']){
			$nid=$this->obj->DB_update_all("px_subject","`pause_status`='".(int)$_GET['pause_status']."'","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
			if($nid){
				$this->obj->member_log("设置培训课程显示状态",21,2);
				$this->layer_msg('显示状态设置成功！',9,0,$_SERVER['HTTP_REFERER']);
			}else{
				$this->layer_msg('显示状态设置失败！',8,0,$_SERVER['HTTP_REFERER']);
			}
		}
		if($_GET['del']){
			$nid=$this->obj->DB_delete_all("px_subject","`id`='".(int)$_GET['del']."' and `uid`='".$this->uid."'");
			if($nid){
				$this->obj->member_log("删除培训课程",21,3);
				$this->layer_msg('删除成功！',9,0,$_SERVER['HTTP_REFERER']);
			}else{
				$this->layer_msg('删除失败！',8,0,$_SERVER['HTTP_REFERER']);
			}
		}
		$_GET['status']=intval($_GET['status']);
		if($_GET['status']=="1"){
			$where="`status`='0' and `pause_status`='1'";
			$urlarr['status']=$_GET['status'];
		}elseif($_GET['status']=="2"){
			$where="`status`='2' and `pause_status`='1'";
			$urlarr['status']=$_GET['status'];
		}else{
			$where="`status`='1' and `pause_status`='1'";
		}
		if($_GET['pstatus']=="2"){
			$where="`pause_status`='2'";
			$urlarr['pstatus']=$_GET['pstatus'];
		}		
		$urlarr['c']="subject";
		$urlarr['page']="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_subject",$where." and `uid`='".$this->uid."' order by id desc",$pageurl,"10");
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"课程管理");
		$this->user_shell();
	
		$this->waptpl('subject');
	}
	function addsubject_action(){
		$train=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'","`name`");
		
		if($train['name']==""){
			$data['msg']="请先完善基本资料！";
			$data['url']='index.php?c=info';
			$this->yunset("layer",$data);
		}
		
		$teach=$this->obj->DB_select_num("px_teacher","`uid`='".$this->uid."' and status='1'");
		$this->yunset("teach",$teach);
		$statusnum=$this->obj->DB_select_num("px_teacher","`uid`='".$this->uid."' and status='1'","id");
		$teachinfo=$this->obj->DB_select_all("px_teacher","`uid`='".$this->uid."' and status='1'","id,name");
		$this->yunset("teachinfo",$teachinfo);
		
		if($_POST['submit']){
			$_POST=$this->post_trim($_POST);
			$rows=$this->obj->DB_select_once("px_subject","`uid`='".$this->uid."' and `id`='".(int)$_POST['id']."' and `pic`<>''");
			
			if($_POST['preview']){
				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/subject/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
 				}else{
					
					$_POST['pic']=str_replace(APP_PATH."/data/upload/subject/","./data/upload/subject/",$pic);
					if($rows['pic']){
						unlink_pic(APP_PATH.$rows['pic']);
					}
				}
			}else{
				$_POST['pic']=$rows['pic'];
			}
			
			$_POST['content']=str_replace(array("&amp;","background-color:#ffffff","background-color:#fff","white-space:nowrap;"),array("&",'background-color:','background-color:','white-space:'),html_entity_decode($_POST['content'],ENT_QUOTES));
			$_POST['ctime']=time();
 			$_POST['status']=0;
			if($_POST['id']){
				if($data['msg'] == ""){
					$where['uid']=$this->uid;
					$where['id']=$_POST['id'];
					$nid=$this->obj->update_once("px_subject",$_POST,$where);
					if($nid){
						$this->obj->member_log("更新培训课程",21,2);
						$data['msg']='更新成功！';
						$data['url']='index.php?c=subject&status=1';
					}else{
						$data['msg']='更新失败,请重新填写！';
					}
				}else{
					$data['msg']=$data['msg'];
				}
			}else{
				if($data['msg']==""){
					$_POST['uid']=$this->uid;
					$_POST['did']=$this->config['did'];
					$nid=$this->obj->insert_into("px_subject",$_POST);
					if($nid){
						$this->obj->member_log("添加培训课程",21,1);
						$data['msg']='添加成功！';
						$data['url']='index.php?c=subject&status=1';
					}else{
						$data['msg']='添加失败,请重新填写！';
					}
				}else{
					$data['msg']=$data['msg'];
				}
			}
			echo json_encode($data);die;
		}
		$row=$this->obj->DB_select_once("px_subject","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
		if($row['type']){
			$row['typeid']=@explode(",",$row['type']);
		}
		if($row['teachid']){
			$row['teach']=@explode(",",$row['teachid']);
		}
		if($row['pic']&&file_exists(str_replace('./', APP_PATH.'/', $row['pic']))){
			$row['pic']=str_replace('./', $this->config['sy_weburl'].'/', $row['pic']);
		}else{
			$row['pic']=$this->config['sy_weburl'].'/'.$this->config['sy_pxsubject_icon'];
		}
		if($row['content']){
			$row['content_tags'] = strip_tags($row['content']);
 		}
		$this->yunset("row",$row);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','subject','subjecttype')));
		$this->user_shell();
		$this->yunset('header_title',"发布课程");
		$this->waptpl('addsubject');
	}
	
	function uppic_action(){
		if($_POST['submit']){
			$pic=$this->wap_up_pic($_POST['uimage'],'train');
			if($pic['errormsg']){echo 2;die;}
			if($pic['re']){
				$px_train=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'","`logo`");
				if($px_train['logo']){
					unlink_pic(APP_PATH.$px_train['logo']);
				}
				$photo="./data/upload/train/".date('Ymd')."/".$pic['new_file'];
				$ref=$this->obj->DB_update_all("px_train","`logo`='".$photo."'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("answer","`pic`='".$photo."'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("question","`pic`='".$photo."'","`uid`='".$this->uid."'");
				if($ref){$this->obj->member_log("上传培训logo",16,1);echo 1;die;}else{echo 2;die;}
			}else{
				unlink_pic(APP_PATH."data/upload/train/".date('Ymd')."/".$pic['new_file']);
				echo 2;die;
			}
		}else{
			$px_train=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'","`logo`");
			if(!$px_train['logo'] || !file_exists(str_replace('./',APP_PATH,$px_train['logo']))){
				$px_train['logo']=$this->config['sy_weburl']."/".$this->config['sy_px_icon'];
			}else{
				$px_train['logo']=str_replace("./",$this->config['sy_weburl']."/",$px_train['logo']);
			}
			$this->yunset("px_train",$px_train);
			$backurl=Url('wap',array(),'member');
			$this->yunset('backurl',$backurl);
			$this->user_shell();
			$this->yunset('header_title',"机构LOGO");
			$this->waptpl('uppic');
		}
		
	}
	function signup_action(){
		if($_GET['status']=="1"){
	    	$oid=$this->obj->DB_update_all("px_baoming","`status`='1'","`id`='".$_GET['id']."' and `s_uid`='".$this->uid."'");
			if($oid){
				$this->obj->member_log("报名信息设为已联系",6,2);
				$this->layer_msg('设置成功！',9,0,"index.php?c=signup");
			}else{
				$this->layer_msg('设置失败！',8,0,"index.php?c=signup");
			}
		}
		if($_GET['delid']){
			$this->obj->DB_delete_all("company_order","`sid`='".$_GET['delid']."' and `type`='6'","");
			$oid=$this->obj->DB_delete_all("px_baoming","`id`='".$_GET['delid']."' and `s_uid`='".$this->uid."'","");
			if($oid){
				$this->obj->member_log("删除报名信息",6,3);
				$this->layer_msg('删除成功！',9,0,"index.php?c=signup");
			}else{
				$this->layer_msg('删除失败！',8,0,"index.php?c=signup");
			}
	    }
		if($_GET['state']=="1"){
			$where="`status`='1'";
			$urlarr['state']=$_GET['state'];
		}elseif($_GET['state']=="2"){
			$where="`status`='0'";
			$urlarr['state']=$_GET['state'];
		}else{
			$where='1';
		}
		$urlarr['c']="signup";
		$urlarr['page']="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_baoming",$where." and `s_uid`='".$this->uid."' order by id desc",$pageurl,"10");
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		if(is_array($rows)){
			foreach($rows as $v){
				$sid[]=$v['sid'];
				$ids[]=$v['id'];
			}
			$subject=$this->obj->DB_select_all("px_subject","`id` in (".pylode(",",$sid).")","id,name,isprice");
			$order=$this->obj->DB_select_all("company_order","`sid` in (".pylode(",",$ids).")","order_state,sid");
			foreach($rows as $k=>$v){
				foreach($subject as $val){
					if($v['sid']==$val['id']){
						$rows[$k]['sub_name']=$val['name'];
						$rows[$k]['isprice']=$val['isprice'];
					}
				}
				foreach($order as $val){
					if($v['id']==$val['sid']){
						$rows[$k]['order_state']=$val['order_state'];
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		
		$backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"课程预约");
		$this->waptpl('signup');
	}
	
	function subpay_action(){
		
		$userM  = $this->MODEL('userinfo');
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>4));
		
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("company_pay","`com_id`='".$this->uid."' and `type`='2' and `pay_remark` LIKE '%课程报名费%'  order by pay_time desc",$pageurl,"10");

		$this->yunset("rows",$rows);
		$statis['freeze'] = sprintf("%.2f", $statis['freeze']);
		$this->yunset("statis",$statis);
		$this->user_shell();
		$this->yunset('header_title',"金额管理");
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('subpay');
	}
	function change_action(){
		$this->yunset('header_title',"金额转换积分");
		$userM=$this->MODEL('userinfo');
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>4));
		$this->yunset("statis",$statis);
		$changeNum = $this->obj->DB_select_num("company_pay","`com_id`='".$this->uid."' and `pay_remark` LIKE '%转换积分%' and `pay_time` >= '".strtotime(date("Y-m-d 00:00:00"))."'");	
		if($_POST){
			$integral=$this->MODEL('integral');
			$changeprice=$_POST['changeprice'];
			$changeintegral=$_POST['changeintegral'];			
			if($changeNum>=$this->config['paypack_max_recharge']){
				$data['msg']="今日转换次数已达上限，请明日再来！";
				$data['url']='index.php?c=change';
				$this->yunset("layer",$data);
			}else{
				$nid=$this->obj->DB_update_all("px_train_statis","`packpay`=`packpay`-'".$changeprice."'","`uid`='".$this->uid."'");
				if($nid){
				$integral->company_invtal($this->uid,$changeintegral,true,"金额转换积分",true,2,'integral',2);
				$data['msg']='转换成功';
				$data['url']='index.php?c=changelist';
				$this->yunset("layer",$data);	
				}else{
					$data['msg']='转换失败';
					$data['url']='index.php?c=change';
					$this->yunset("layer",$data);
				}	
			}
		}
		$this->yunset("changeNum",$changeNum);
		$backurl=Url('wap',array('c'=>'subpay'),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('change');
	}
	function changelist_action(){
		$this->yunset('header_title',"金额转换积分明细");
		$urlarr["c"]="changelist";
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$where="`com_id`='".$this->uid."'";
		$where.=" and `pay_remark` LIKE '%转换积分%' order by pay_time desc";
		$rows=$this->get_page("company_pay",$where,$pageurl,"10");
		$this->yunset("rows",$rows);
		$userM=$this->MODEL('userinfo');
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>4));
		$this->yunset("statis",$statis);
		$this->waptpl('changelist');
	}
	
	function withdraw_action(){
		
		$this->yunset('header_title',"提现");
		if($_POST){

			$M		= $this->MODEL('pack');
			
			$return = $M->withDraw($this->uid,$this->usertype,$_POST['price'],$_POST['real_name']);
				
			if($return==''){
				
				$data['msg']='提现成功，请关注微信账户提醒！';
				$data['url']='index.php?c=withdrawlist';
				
				$this->yunset("layer",$data);
			}else{
			
				
				$data['msg']=$return;
				$data['url']='index.php?c=withdraw';
				$this->yunset("layer",$data);

			}

		}else{

			$userM  = $this->MODEL('userinfo');
			$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>4));

			$this->yunset("statis",$statis);
			
		}

		$backurl=Url('wap',array('c'=>'subpay'),'member');
		$this->yunset('backurl',$backurl);

		$this->waptpl('withdraw');
	}

	function withdrawlist_action(){
		
		$urlarr["c"]="withdrawlist";
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$where = "`uid`='".$this->uid."'";
		$rows=$this->get_page("member_withdraw",$where." order by id desc",$pageurl,"10");

		if(is_array($rows)){
			include (APP_PATH."/config/db.data.php");
			foreach($rows as $k=>$v){
				$rows[$k]['order_state_n']=$arr_data['withdrawstate'][$v['order_state']];
			}
		}
		$userM  = $this->MODEL('userinfo');
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>4));

		$this->yunset("statis",$statis);
		$this->yunset("rows",$rows);
		$this->yunset('header_title',"提现明细");
		$backurl=Url('wap',array('c'=>'subpay'),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('withdrawlist');
	}

	function team_action(){
		if($_GET['del']){
			$nid=$this->obj->DB_delete_all("px_teacher","`id`='".(int)$_GET['del']."' and `uid`='".$this->uid."'");
			if($nid){
				$this->obj->member_log("删除培训师",20,3);
				$this->layer_msg('删除成功！',9,0,$_SERVER['HTTP_REFERER']);
			}else{
				$this->layer_msg('删除失败！',8,0,$_SERVER['HTTP_REFERER']);
			}
		}
		$_GET['status']=intval($_GET['status']);
		if($_GET['status']=="1"){
			$where="`status`='0'";
			$urlarr['status']=$_GET['status'];
		}elseif($_GET['status']=="2"){
			$where="`status`='2'";
			$urlarr['status']=$_GET['status'];
		}else{
			$where="`status`='1'";
		}
		$urlarr['c']="team";
		$urlarr['page']="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_teacher",$where." and `uid`='".$this->uid."' order by id desc",$pageurl,"10");
		$this->yunset("rows",$rows);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','hy','subject')));
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"讲师管理");
		$this->waptpl('team');
	}
	function addteam_action(){
		if($_POST['submit']){
			$_POST=$this->post_trim($_POST);
			$rows=$this->obj->DB_select_once("px_teacher","`uid`='".$this->uid."' and `id`='".(int)$_POST['id']."' and `pic`<>''");
			if($_POST['preview']){
				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/team/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
 				}else{
					
					$_POST['pic']=str_replace(APP_PATH."/data/upload/team/","./data/upload/team/",$pic);
					if($rows['pic']){
						unlink_pic(APP_PATH.$rows['pic']);
					}
				}
			}else{
				$_POST['pic']=$rows['pic'];
			}
			
			
			$_POST['content']=str_replace(array("&amp;","background-color:#ffffff","background-color:#fff","white-space:nowrap;"),array("&",'background-color:','background-color:','white-space:'),html_entity_decode($_POST['content'],ENT_QUOTES));
			$_POST['ctime']=time();
			$_POST['status']=0;
			if($_POST['id']){
				if($data['msg']==""){
					$where['uid']=$this->uid;
					$where['id']=$_POST['id'];
					$nid=$this->obj->update_once("px_teacher",$_POST,$where);
					if($nid){
						$this->obj->member_log("更新培训师",20,2);
						$data['msg']='更新成功！';
						$data['url']='index.php?c=team&status=1';
					}else{
						$data['msg']='更新失败,请重新填写！';
					
					}
				}else{
					$data['msg']=$data['msg'];
					
				}
			}else{
				if($data['msg']==""){
					$_POST['uid']=$this->uid;
					$_POST['did']=$this->userdid;
					$nid=$this->obj->insert_into("px_teacher",$_POST);
					if($nid){
						$this->obj->member_log("添加培训师",20,1);
						$data['msg']='添加成功！';
						$data['url']='index.php?c=team&status=1';
					}else{
						$data['msg']='添加失败,请重新填写！';
					
					}
				}else{
					$data['msg']=$data['msg'];
					
				}
			}
			echo json_encode($data);die;
			
		}
		$row=$this->obj->DB_select_once("px_teacher","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
		if($row['pic']&&file_exists(str_replace('./', APP_PATH.'/', $row['pic']))){
			$row['pic']=str_replace('./', $this->config['sy_weburl'].'/', $row['pic']);
		}else{
			$row['pic']=$this->config['sy_weburl'].'/'.$this->config['sy_pxteacher_icon'];
		}
		$this->yunset("row",$row);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','hy','subject')));
		
		$this->user_shell();
		$this->yunset('header_title',"发布讲师");
		$this->waptpl('addteam');
	}
	function password_action(){
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"修改密码");
		$this->waptpl('password');
	}
	function binding_action(){
		if($_POST['moblie']){
			$row=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `check`='".$_POST['moblie']."'");
			if(!empty($row)){
				session_start();
				if($row['check2']!=$_POST['code']){
					echo 3;die;
				}else if(!$_POST['authcode']){
					echo 4;die;
				}elseif(md5(strtolower($_POST['authcode']))!=$_SESSION['authcode'] || empty($_SESSION['authcode'])){
					echo 5;die;
				}else{
					
					$this->obj->DB_update_all("resume","`moblie_status`='0'","`telphone`='".$row['check']."'");
					$this->obj->DB_update_all("company","`moblie_status`='0'","`linktel`='".$row['check']."'");
					$this->obj->DB_update_all("lt_info","`moblie_status`='0'","`moblie`='".$row['check']."'");
					$this->obj->DB_update_all("px_train","`moblie_status`='0'","`linktel`='".$row['check']."'");
					
					$this->obj->DB_update_all("member","`moblie`='".$row['check']."'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("px_train","`linktel`='".$row['check']."',`moblie_status`='1'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_cert","`status`='1'","`uid`='".$this->uid."' and `check2`='".$_POST['code']."'");
					$this->obj->member_log("手机绑定",13,1);
					$pay=$this->obj->DB_select_once("company_pay","`pay_remark`='手机绑定' and `com_id`='".$this->uid."'");
					if(empty($pay)){
						$this->MODEL('integral')->get_integral_action($this->uid,"integral_mobliecert","手机绑定");
					}
					echo 1;die;
				}
			}else{
				echo 2;die;
			}
		}
		if($_GET['type']){
			if($_GET['type']=="moblie"){
				$this->obj->DB_update_all("px_train","`moblie_status`='0'","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="email"){
				$this->obj->DB_update_all("px_train","`email_status`='0'","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="qqid"){
				$this->obj->DB_update_all("member","`qqid`='',`qqunionid`=''","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="wxid"){
				$this->obj->DB_update_all("member","`wxid`='',`wxopenid`='',`unionid`=''","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="sinaid"){
				$this->obj->DB_update_all("member","`sinaid`=''","`uid`='".$this->uid."'");
			}
			$this->waplayer_msg('解除绑定成功！');
		}
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);		
		$train=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'");
		$this->yunset("train",$train);
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='5'");
		$this->yunset("cert",$cert);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"账户绑定");
		$this->waptpl('binding');
	}	
	function bindingbox_action(){
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);
		$backurl=Url('wap',array('c'=>'binding'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"账户绑定");
		$this->waptpl('bindingbox');
	}
	
	function message_action(){
		if($_GET['reply']){
		    if($_POST['content']==''){
		        $data['msg']='回复内容不能为空！';
		    }
		    if ($data['msg']==''){
		        $nid=$this->obj->DB_update_all("px_zixun","reply='".trim($_POST['content'])."',reply_time='".time()."',status='2'","`id`='".(int)$_POST['id']."' and `s_uid`='".$this->uid."'");
		        if($nid){
		            $this->obj->member_log("回复咨询留言",18,1);
		            $data['msg']='回复成功！';
		            $data['url']=$_SERVER['HTTP_REFERER'];
		        }else{
		            $data['msg']='回复失败！';
		            $data['url']=$_SERVER['HTTP_REFERER'];
		        }
		    }
			$this->yunset("layer",$data);
		}
		if($_GET['del']){
			$oid=$this->obj->DB_delete_all("px_zixun","`id`='".(int)$_GET['del']."' and `s_uid`='".$this->uid."'");
			if($oid){
				$this->obj->member_log("删除咨询留言",18,3);
				$this->layer_msg('删除成功！',9,0);
			}else{
				$this->layer_msg('删除失败！',8,0);
			}
		}
		if($_GET['status']){
			$_GET['status']=intval($_GET['status']);
			$where="`status`='".$_GET['status']."' and ";
			$urlarr['status']=$_GET['status'];
		}
		$urlarr['c']=$_GET['c'];
		
		$urlarr['page']="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_zixun",$where."`s_uid`='".$this->uid."' order by id desc",$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $v){
				$uid[]=$v['uid'];
			}
			$minfo=$this->obj->DB_select_all('member','uid in('.pylode(',',$uid).')','uid,username');
			foreach($rows as $k=>$v){
				foreach($minfo as $val){
					if($v['uid']==$val['uid']){
						$rows[$k]['nickname']=$val['username'];
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"咨询留言");
		$this->waptpl('message');
	}
	
	function show_action(){
		if($_GET['del']){
			$row=$this->obj->DB_select_once("px_train_show","`id`='".(int)$_GET['del']."' and `uid`='".$this->uid."'","`picurl`");
			if(is_array($row)){
				unlink_pic(".".$row['picurl']);
				$oid=$this->obj->DB_delete_all("px_train_show","`id`='".(int)$_GET['del']."' and `uid`='".$this->uid."'");
			}
			if($oid){
				$this->obj->member_log("删除机构环境展示",16,3);
				$this->layer_msg('删除成功！',9,0);
			}else{
				$this->layer_msg('删除失败！',8,0);
			}
		}
		$urlarr['c']="show";
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$this->get_page("px_train_show","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
		$this->yunset('backurl','index.php');
		$this->user_shell();
		$this->yunset('header_title',"机构环境");
		$this->waptpl('show');
	}
	function addshow_action(){
	    if($_POST['submit']){
	        if($_POST['preview']){
				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/show/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
				
				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
					$data['url']='index.php?c=addshow';
 				}else{
					
					$photo=str_replace(APP_PATH."/data/upload/show/","./data/upload/show/",$pic);
					$data=array('picurl'=>$photo,'title'=>$_POST['title'],'ctime'=>time(),'uid'=>(int)$_POST['uid']);
					$id=$this->obj->insert_into("px_train_show",$data);
					$data['msg']='上传机构环境成功！';
					$data['url']='index.php?c=show';
				}

			}else{
	            $data['msg']='请上传机构环境！';
	            $data['url']='index.php?c=addshow';
	        }
	        $this->yunset("layer",$data);
	    }
		$backurl=Url('wap',array('c'=>'show'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"上传机构环境");
	    $this->waptpl('addshow');
	}
	function reward_list_action(){
		$urlarr=array("c"=>"rewardlist","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where.="`uid`='".$this->uid."'order by id desc";
		$rows=$this->get_page("change",$where,$pageurl,"13");
		if(is_array($rows)){
			foreach($rows as $key=>$val){
				$gid[]=$val['gid'];
			}
			$M=$this->MODEL('redeem');
			$gift=$M->GetReward(array('`id` in('.pylode(',', $gid).')'),array('field'=>'id,pic'));
			foreach($rows as $k=>$val){
				foreach ($gift as $v){
					if($val['gid']==$v['id']){
						$rows[$k]['pic']=$v['pic'];
					}
				}
			}
		}
		
		$dh = $sh = $wtg =0;
		if(is_array($rows)){
			foreach($rows as $value){
				if($value['status']=='0'){
					$sh +=1;
				}
				if($value['status']=='2'){
					$wtg +=1;
				}
				if($value['status']=='1'){
					$dh +=1;
				}												
			}
		}
		$this->yunset(array('dh'=>$dh,'sh'=>$sh,'wtg'=>$wtg));
		
		$statis=$this->obj->DB_select_once("px_train_statis","`uid`='".$this->uid."'","integral");
		$statis[integral]=number_format($statis[integral]);
		$this->yunset("statis",$statis);
		$this->yunset('rows',$rows);
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"兑换记录");
		$this->waptpl('reward_list');
	}
	function rewarddel_action(){
		if($this->usertype!='4' || $this->uid==''){
			$this->layer_msg('登录超时！',8,0);
		}else{
			$rows=$this->obj->DB_select_once("change","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' ");
			if($rows['id']){
				$this->obj->DB_update_all("reward","`num`=`num`-".$rows['num'].",`stock`=`stock`+".$rows['num']."","`id`='".$rows['gid']."'");
				$this->MODEL('integral')->company_invtal($this->uid,$rows['integral'],true,"取消兑换",true,2,'integral',24);
				$this->obj->DB_delete_all("change","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' ");
			}
			$this->obj->member_log("取消兑换",17,3);
			$this->layer_msg('删除成功！',9,0);
		}
	}
	function cert_action(){
		if($_POST['submit']){
			$comname=$this->obj->DB_select_num('px_train',"`uid`<>'".$this->uid."' and `name`='".$_POST['name']."'","`uid`");
			$row=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='5'");
			if($_POST['name']==""){
				$data['msg']='机构全称不能为空！';
			}elseif($comname){
				$data['msg']='机构全称已存在！';
			}elseif(!$_POST['preview']&&!$row['check']){
				$data['msg']='请上传培训执照！';
			}else{
				if($_POST['preview']){

					
					$UploadM =$this->MODEL('upload');
					$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/cert/",false);
					
					$pic     =$upload->imageBase($_POST['preview']);
					
					$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);

					if($picmsg['status']==$pic){
						$data['msg']=$picmsg['msg'];
 					}else{
						
						$photo=str_replace(APP_PATH."/data/upload/cert/","./data/upload/cert/",$pic);
						if($row['check']){
							unlink_pic(APP_PATH.$row['check']);
						}
					}
				}else{
					$photo=$row['check'];
				}
				
		
			}
			if($this->config['px_cert_status']=="1"){
				$sql['status']=0;
			}else{
				$sql['status']=1;
			}
			$this->obj->DB_update_all("px_train","`name`='".$_POST['name']."',`yyzz_status`='".$sql['status']."'","`uid`='".$this->uid."'");
			$sql['step']=1;
			$sql['check']=$photo;
			$sql['check2']="0";
			$sql['ctime']=mktime();
			$company=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."'  and type='5'","`check`");
			if(is_array($company)){
				$where['uid']=$this->uid;
				$where['type']='5';
				$this->obj->update_once("company_cert",$sql,$where);
				$this->obj->member_log("更新培训执照",13,2);
			}else{
				$sql['uid']=$this->uid;
				$sql['did']=$this->userdid;
				$sql['type']=5;
				$this->obj->insert_into("company_cert",$sql);
				$this->obj->member_log("上传培训执照",13,1);
			}
			if($data['msg']==""){
				$data['msg']='上传培训执照成功！';
			}else{
				$data['msg']=$data['msg'];
			}
			
			$data['url']='index.php?c=cert';
		}
		
		$train=$this->obj->DB_select_once("px_train","`uid`='".$this->uid."'","`name`,`yyzz_status`");
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `type`='5'");
		
		if($cert['check']){
		    $cert['old_check']=str_replace('./data','/data',$cert['check']);
		}
		
		$this->yunset("train",$train);
		$this->yunset("cert",$cert);
		$this->yunset("layer",$data);
		$this->yunset("backurl","index.php?c=binding");
		$this->user_shell();
		$this->yunset('header_title',"培训执照");
		$this->waptpl('pxcert');
	}
	function sysnews_action(){
		
		$sxrows=$this->obj->DB_select_once("sysmsg","`fa_uid`='".$this->uid."' order by ctime desc");
		$this->yunset('sxrows',$sxrows);
		
		$sxrowsnum=$this->obj->DB_select_num("sysmsg","`fa_uid`='".$this->uid."'and `remind_status`='0'");
		$this->yunset('sxrowsnum',$sxrowsnum);
	    
		$baoming=$this->obj->DB_select_once("px_baoming","`s_uid`='".$this->uid."' order by ctime desc");
		$this->yunset('baoming',$baoming);
		$subject=$this->obj->DB_select_once("px_subject","`uid`='".$this->uid."'");
		$this->yunset('subject',$subject);
		$wlnum=$this->obj->DB_select_num("px_baoming","`s_uid`='".$this->uid."' and `status`='0'");
		$this->yunset('wlnum',$wlnum);
		
		$zxrows=$this->obj->DB_select_once("px_zixun","`s_uid`='".$this->uid."' order by ctime desc");
		$this->yunset("zxrows",$zxrows);
		$zxnum=$this->obj->DB_select_num("px_zixun","`s_uid`='".$this->uid."' and `status`='1'");
		$this->yunset("zxnum",$zxnum);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"系统消息");
		$this->waptpl('sysnews');
		
	}
	
	function sxnews_action(){
		$where.= "`fa_uid`='".$this->uid."' order by `id` desc";
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('member',$urlarr);
		$rows=$this->get_page("sysmsg",$where,$pageurl,"13");
		
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"私信");
		$this->waptpl('sxnews');
	}	
	function sxnewsset_action(){
    	$id=(int)$_POST['id'];
		$remind_status=(int)$_POST['remind_status'];
		if($id&&$remind_status){
			$nid=$this->obj->update_once("sysmsg",array('remind_status'=>$remind_status),array("fa_id"=>$this->uid,"id"=>$id));
			$this->obj->member_log("更改系统消息状态（ID:".$id."）",18,2);
		}
		$nid?$this->waplayer_msg("操作成功！"):$this->waplayer_msg("操作失败！");
    }
	function delsxnews_action(){
		if($_GET['id']){
		   $nid = $this->obj->DB_delete_all("sysmsg","`id`='".(int)$_GET['id']."' and `fa_uid`='".$this->uid."'");
		    if($nid){
			$this->obj->member_log("删除系统消息",18,3);
			$this->layer_msg('删除成功！');
		    }else{
			$this->layer_msg('删除失败！');
		    }
	    } 
	}
	
	function integral_action(){
		
		$baseInfo			= false;	
		$logo				= false;	
		$emailChecked		= false;	
		$phoneChecked		= false;	
		$pay_remark         =false;
		$question        	=false;		
		$answer       		=false;		
		$answerpl           =false;		
		
		$banner				= false;	
		$signin			    = false;	
		$row = $this->obj->DB_select_once("px_train",'`uid` = '.$this->uid,
			"`name`,`sid`,
			`logo`,`email_status`,`moblie_status`");
		
		if(is_array($row) && !empty($row)){
			if($row['name'] != '' && $row['sid'] != '' )
				$baseInfo = true;
			
			if($row['logo'] != '') $logo = true;
			if($row['email_status'] != 0) $emailChecked = true;
			if($row['moblie_status'] != 0) $phoneChecked = true;
		}
		$date=date("Ymd");
		$reg=$this->obj->DB_select_once("member_reg","`uid`='".$this->uid."' and `usertype`='".$this->usertype."' and `date`='".$date."'");
		if($reg['id']){
		    $signin = true;
		}
		if($this->config['integral_question_type']=="1"){
			$question=$this->max_time('发布问题');
		}
		if($this->config['integral_answer_type']=="1"){
			$answer=$this->max_time('回答问题');
		}
		if($this->config['integral_answerpl_type']=="1"){
			$answerpl=$this->max_time('评论问答');
		}
		
		$statis=$this->obj->DB_select_once("px_train_statis","`uid`='".$this->uid."'","`integral`");
		$this->yunset("statis",$statis);
		
		$banner_num	= $this->obj->DB_select_num('px_banner','`uid` = '.$this->uid);
		if($banner_num > 0) $banner = true;
		
		$statusList = array(
		    'signin'		=>$signin,
			'baseInfo'		=>$baseInfo,
			'logo'			=>$logo,
			'emailChecked'	=>$emailChecked,
			'phoneChecked'	=>$phoneChecked,
			'pay_remark'	=>$pay_remark,
			'question'	    =>$question,
			'answer'	    =>$answer,
			'answerpl'	    =>$answerpl,
			'banner'		=> $banner	
		);
		$this->yunset("statusList",$statusList);
		$this->yunset('header_title',"积分管理");
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('integral');
	}
	
	function consume_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"consume","page"=>"{{page}}");
		$pageurl=Url('member',$urlarr);
		$where="`com_id`='".$this->uid."'";

		$where.="  order by pay_time desc";
		$rows = $this->get_page("company_pay",$where,$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $k=>$v)
			{
				$rows[$k]['order_price']=floatval($v['order_price']);
				$rows[$k]['pay_time']=date("Y-m-d H:i:s",$v['pay_time']);
			}
		}
		
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"账务明细");
		$this->waptpl('consume');
	}
	
	function integral_reduce_action(){
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"积分规则");
		$this->waptpl('integral_reduce');
	}
	function banner_action(){
		 if($_POST['submit']){
		 	
		 	if($_POST['preview']){
				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/train/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
 				}else{
					
					$photo=str_replace(APP_PATH."/data/upload/train/","./data/upload/train/",$pic);
					$datap['uid']=$this->uid;
					$datap['pic']=$photo;
				}

			}
			if($data['msg']==""){
				$row=$this->obj->DB_select_once("px_banner","`uid`='".$this->uid."'");
				if($row['id']){
					if($row['pic']){
						unlink_pic(APP_PATH.$row['pic']);
					}
					$nid=$this->obj->update_once("px_banner",$datap,array('id'=>$row['id']));
				}else{
					$nid=$this->obj->insert_into("px_banner",$datap);
				}
				if($nid){
					$this->obj->member_log("上传机构横幅",16,1);
					$IntegralM=$this->MODEL('integral');
					$IntegralM->get_integral_action($this->uid,"integral_px_banner","上传培训横幅");
					$data['msg']="设置成功！";
					$data['url']='index.php?c=integral';
				}else{
					$data['msg']="设置失败！";
					$data['url']='index.php?c=banner';
				}
			}else{
				$data['msg']=$data['msg'];
				$data['url']='index.php?c=banner';
			}
		}	 
		
		$banner=$this->obj->DB_select_once("px_banner","`uid`='".$this->uid."'");
		if($banner['pic']&&file_exists(str_replace('./',APP_PATH,$banner['pic']))){
			$banner['pic']=str_replace('./',$this->config['sy_weburl'].'/',$banner['pic']);
		}else{
			$banner['pic']='';
		}
		$this->yunset("banner",$banner);
		$this->yunset("layer",$data);
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset("backurl",$backurl);
		$this->yunset('header_title',"机构横幅");
		$this->waptpl('banner');
	}
}
?>