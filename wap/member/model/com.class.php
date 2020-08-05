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
class com_controller extends wap_controller{
	
	function get_user(){
		$rows=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if(!$rows['name'] || !$rows['address'] || !$rows['pr']){
			$data['msg']='请先完善企业资料！';
			$data['url']='index.php?c=info';
			$this->yunset("layer",$data);
		}
		if(!$rows['logo'] || $rows['logo_status']!='0' ||!file_exists(str_replace('./',APP_PATH,$rows['logo']))){
			$rows['logo']=$this->config['sy_weburl']."/".$this->config['sy_unit_icon'];
		}else{
			$rows['logo']=str_replace("./",$this->config['sy_weburl']."/",$rows['logo']);
		}
		$this->yunset("company",$rows);
		return $rows;
	}
	
	function waptpl($tpname){
		$this->yuntpl(array('wap/member/com/'.$tpname));
	}

	function index_action(){
		$this->rightinfo();
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'","`login_date`,`status`");
		$this->yunset("member",$member);
		
		$date=date("Ymd"); 
		$reg=$this->obj->DB_select_once("member_reg","`uid`='".$this->uid."' and `usertype`='".$this->usertype."' and `date`='".$date."'"); 
		if($reg['id']){
			$signstate=1;
		}else{
			$signstate=0;
		}
		$this->yunset("signstate",$signstate);
		
		$jobs=$this->obj->DB_select_all("company_job","`state`=1 and `r_status`<>2 and `status`<>1 and `uid`='".$this->uid."'");
		if($jobs && is_array($jobs)){
			foreach($jobs as $key=>$v){
				$ids[]=$v['id'];
			}
			$jobids ="".@implode(",",$ids)."";
			$this->yunset("jobids",$jobids);
		}		
		
		$statis = $this->company_satic();
		$this->yunset("statis",$statis);
		$this->cookie->SetCookie("updatetoast",'1',time() + 86400);
		$this->get_user();
		$this->yunset('backurl',Url('wap',array()));
		if($this->config['sy_chat_open']==1){
		    $chatM = $this->MODEL('chat');
		    $unread = $chatM->getChats(array('to'=>$this->uid,'status'=>2),array('field'=>'`from`,count(*) as num','groupby'=>'`from`'));
		    $chatunread = 0;
		    foreach ($unread as $v){
		        $chatunread += $v['num'];
		    }
		    $this->yunset('chatunread',$chatunread);
		}

 		$this->waptpl('index');
	}
	function com_action(){
		
		$this->rightinfo();
		$this->company_satic();
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		if($statis['rating']){
			$rating=$this->obj->DB_select_once("company_rating","`id`='".$statis['rating']."'");
		}
		$com=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if($statis['rating']>0){
			if($statis['vip_etime']>time()){
				$days=round(($statis['vip_etime']-mktime())/3600/24) ;
				$this->yunset("days",$days);
			}
		}
		$allprice=$this->obj->DB_select_once("company_pay","`com_id`='".$this->uid."' and `type`='1' and `order_price`<0","sum(order_price) as allprice");
		if($allprice['allprice']==''){$allprice['allprice']='0';}
		$this->yunset("integral",number_format(str_replace("-","", $allprice['allprice'])));
		$this->yunset("com",$com);
		$this->yunset("statis",$statis);
		$this->yunset("rating",$rating);
		$backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		
		$this->yunset('header_title',"我的服务");
		$this->get_user();
		$this->waptpl('com');
	}
    function map_action(){
        if($_POST['submit']){
            if($_POST['xvalue']==""){
                $data['msg']='请设置企业地图！';
            }else{
                $IntegralM=$this->MODEL('integral');
                $rows = $this->obj->DB_select_once("company","`uid`='".$this->uid."'","`x`,`y`");
                if($rows['x'] == "" && $rows['y'] == ""){
                    $IntegralM->get_integral_action($this->uid,"integral_map","设置企业地图");
                }
                $data['x']=(float)$_POST['xvalue'];
                $data['y']=(float)$_POST['yvalue'];
                $nid=$this->obj->update_once("company",$data,array("uid"=>$this->uid));
                if($nid){
					$this->obj->DB_update_all("company_job","`x`='".$data['x']."',`y`='".$data['y']."'","`uid`='".$this->uid."'");
                    $this->obj->member_log("设置企业地图",15,1);
                    $data['msg']='地图设置成功！';
                    $data['url']="index.php?c=set";
                }else{
					$data['msg']='地图设置失败！';
                    $data['url']=$_SERVER['HTTP_REFERER'];
                }
            }
           
            
        }
        $this->yunset("layer",$data);
        $urlarr=array("c"=>"map","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
        $row=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","x,y,address,provinceid,cityid,three_cityid");
		$this->yunset("row",$row);
		$this->yunset($this->MODEL('cache')->GetCache(array('city')));
		
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		
		$this->yunset('header_title',"设置地图");
		
		$this->get_user();
        $this->waptpl('map');
    }
     function reportlist_action(){
        
        if($_POST['submit']){
            if($_POST['reason']==""){
                $data['msg']='请选择举报原因！';
            }else{
                $data['c_uid']=(int)$_GET['uid'];
                $data['inputtime']=mktime();
                $data['p_uid']=$this->uid;
                $data['did']=$this->userid;
                $data['usertype']=(int)$this->usertype;
                $data['eid']=(int)$_GET['eid'];
                $data['r_name']=$_GET['r_name'];
                $data['username']=$this->username;
                $data['r_reason']=@implode(',',$_POST['reason']);
                $nid=$this->obj->insert_into("report",$data);
                if($nid){
                    $this->obj->member_log("举报简历",23,1);
                    $data['msg']='举报成功！';
                    $data['url']='index.php?c=down';
                }else{
                    $data['msg']='举报失败！';
                    $data['url']='index.php?c=down';
                }
            }
            
            
        }
        $this->yunset("layer",$data);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"举报简历");
		$this->get_user();
		$this->waptpl('reportlist');
	}
	function info_action(){
		$this->rightinfo();
		if($_POST['submit']){
			$this->cookie->SetCookie("delay", "", time() - 60);
			$_POST=$this->post_trim($_POST);
			$comname=$this->obj->DB_select_num('company',"`uid`<>'".$this->uid."' and `name`='".$_POST['name']."'","`uid`");
			 
			if($data['msg']==''){
 				if($_POST['name']==""){
					$data['msg']='企业全称不能为空！';
				}elseif($comname>1){
					$data['msg']='企业全称已存在！';
				}elseif($_POST['hy']==""){
					$data['msg']='从事行业不能为空！';
				}elseif($_POST['pr']==""){
					$data['msg']='企业性质不能为空！';
				}elseif($_POST['provinceid']==""){
					$data['msg']='所在地不能为空！';
				}elseif($_POST['mun']==""){
					$data['msg']='企业规模不能为空！';
				}else if($_POST['address']==""){
					$data['msg']='公司地址不能为空！';
				}else if($_POST['linkphone']==""&&$_POST['linktel']==""){
					$data['msg']='手机或电话必填一项！';
				}elseif($_POST['content']==""){
					$data['msg']='企业简介不能为空！';
				}
			}
			if($data['msg']==''){
				delfiledir("../data/upload/tel/".$this->uid);
				$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
				$Member=$this->MODEL("userinfo");
				if($company['moblie_status']==1){
					unset($_POST['linktel']);
				}elseif($_POST['linktel']){
					$moblieNum = $Member->GetMemberNum(array("moblie"=>$_POST['linktel'],"`uid`<>'".$this->uid."'"));
					if(!CheckMoblie($_POST['linktel'])){
						$data['msg']='手机号码格式错误！';
					}elseif($moblieNum>0){
						$data['msg']='手机号码已存在！';
					}else{
						$mvalue['moblie']=$_POST['linktel'];
					}

				}
				if($company['email_status']==1){
					unset($_POST['linkmail']);
				}elseif($_POST['linkmail']){
					$emailNum = $Member->GetMemberNum(array("email"=>$_POST['linkmail'],"`uid`<>'".$this->uid."'"));
					if(CheckRegEmail($_POST['linkmail'])==false){
						$data['msg']='联系邮箱格式错误！';
					}elseif($emailNum>0){
						$data['msg']='联系邮箱已存在！';
					}else{
						$mvalue['email']=$_POST['linkmail'];
					}
				}
				if($company['yyzz_status']=='1'){
					$_POST['name'] = $company['name'];
				}
				
				
				
				if(is_uploaded_file($_FILES['comqcode']['tmp_name'])){
					
 				    $UploadM=$this->MODEL('upload');
				    $upload=$UploadM->Upload_pic(APP_PATH."/data/upload/company/",false);
				    
				    $pictures=$upload->picture($_FILES['comqcode']);
				    
				    $pic=str_replace(APP_PATH."/data/upload/company/","./data/upload/company/",$pictures);
				    $_POST['comqcode']=$pic;
				}
				
				if($_POST['preview']){
					$UploadM =$this->MODEL('upload');
					$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/company/",false);
					$pic     =$upload->imageBase($_POST['preview']);
					$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
					if($picmsg['status']==$pic){
						$data['msg']=$picmsg['msg'];
	 				}else{
						$_POST['comqcode']=str_replace(APP_PATH."/data/upload/company/","./data/upload/company/",$pic);
						if($company['comqcode']){
							unlink_pic(APP_PATH.$company['comqcode']);
						}
					}
				} 
				
				
				
				unset($_POST['submit']);
				$where['uid']=$this->uid;
				$_POST['lastupdate']=time();
				$_POST['welfare']=@explode(',',$_POST['welfare']);
				foreach($_POST['welfare'] as $v){
					if($v){
						$welfare[]=$v;
					}
				}
				$_POST['welfare']=@implode(',',$welfare);
				if($data['msg']==""){
					$nid=$this->obj->update_once("company",$_POST,$where);
					if($nid){
						if(!empty($mvalue)){
							$this->obj->update_once('member',$mvalue,array("uid"=>$this->uid));
						}
						$data['com_name']=$_POST['name'];
						$data['pr']=$_POST['pr'];
						$data['mun']=$_POST['mun'];
						$data['com_provinceid']=$_POST['provinceid'];
						$data['welfare']=@implode(',',$_POST['welfare']);
						$data['com_logo']=$_POST['photo'];
						$this->obj->update_once("company_job",$data,array("uid"=>$this->uid));
						if($company['name']!=$_POST['name']){
							$this->obj->update_once("partjob",array("com_name"=>$_POST['name']),array("uid"=>$this->uid));
							$this->obj->update_once("userid_job",array("com_name"=>$_POST['name']),array("com_id"=>$this->uid));
							$this->obj->update_once("fav_job",array("com_name"=>$_POST['name']),array("com_id"=>$this->uid));
							$this->obj->update_once("report",array("r_name"=>$_POST['name']),array("c_uid"=>$this->uid));
							$this->obj->update_once("blacklist",array("com_name"=>$_POST['name']),array("c_uid"=>$this->uid));
							$this->obj->update_once("msg",array("com_name"=>$_POST['name']),array("job_uid"=>$this->uid));
						}

						if($company['lastupdate']<1){
							if($this->config['integral_userinfo_type']=="1"){
								$auto=true;
							}else{
								$auto=false;
							}
							$this->sendredpack(array('type'=>'4','uid'=>$this->uid));

							$this->MODEL('integral')->company_invtal($this->uid,$this->config['integral_userinfo'],$auto,"首次填写基本资料",true,2,'integral',25);
						}
						$this->obj->member_log("修改企业资料",7,1);
						$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$_POST['logid']."'");
						$data['msg']='更新成功！';
						$data['url']='index.php';
					}else{
						$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$_POST['logid']."'");
 						$data['msg']='更新失败！';
						$data['url']='index.php?c=info';
					}
				}else{
					$data['msg']=$data['msg'];
				}				
				
			}
			echo json_encode($data);die;
		}
		$row=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if ($row['comqcode']){
			$row['comqcode']=str_replace('./', $this->config['sy_weburl'].'/', $row['comqcode']);
		}
		if ($row['logo']){
			$row['logo']=str_replace('./', $this->config['sy_weburl'].'/', $row['logo']);
		}
		 
		if ($row['welfare']){
			$row['arraywelfare']=explode(',', $row['welfare']);
		}
		
		if ($row['content']){
			$row['content_t']=strip_tags($row['content']);
		}
 
		$this->yunset($this->MODEL('cache')->GetCache(array('city','com','hy')));
		$this->yunset("row",$row);
		
		$this->yunset('header_title',"基本信息");
		$this->waptpl('info');
	}
	
	function get_com($type){
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		
		if($statis['rating_type'] && $statis['rating']) {

			if($type==1){
				if($statis['rating_type']=='1' && $statis['job_num']>0 && ($statis['vip_etime']<1 || $statis['vip_etime']>=time())){
					$value="`job_num`=`job_num`-1";
				}elseif($statis['rating_type']=='2' && ($statis['vip_etime']>time() || $statis['vip_etime']=='0')){
					$value=null;
				}else{
					return "你的套餐不够发布职位！";
				}
			}elseif($type==4){
				if($statis['rating_type']=='1' && $statis['lt_job_num']>0 && ($statis['vip_etime']<1 || $statis['vip_etime']>=time())){
					$value="`lt_job_num`=`lt_job_num`-1";
				}elseif($statis['rating_type']=='2' && ($statis['vip_etime']>time() || $statis['vip_etime']=='0')){
					$value=null;
				}else{
					return "你的套餐不够发布职位！";
				}
			}elseif($type==7){
				if($statis['rating_type']=='1' && $statis['part_num']>0 && ($statis['vip_etime']<1 || $statis['vip_etime']>=time())){
					$value="`part_num`=`part_num`-1";
				}elseif($statis['rating_type']=='2' && ($statis['vip_etime']>time() || $statis['vip_etime']=='0')){
					$value=null;
				}else{
					return "你的套餐不够发布兼职！";
				}
			}
			if($value){
				$this->obj->DB_update_all("company_statis",$value,"`uid`='".$this->uid."'");
			}
		}else{
			return "你的会员已经到期！";
		}
	}
	 
	function jobadd_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$this->get_user();
		$statics=$this->company_satic();
		
		if(!$_GET['id']){
			if($statics['addjobnum']==0){ 
				$data['msg']="您的会员已到期！";
				$data['url']='index.php?c=rating';
			}
			if($statics['addjobnum']==2){
				if($this->config['integral_job']!='0'){
					$data['msg']="您的套餐已用完！";
					$data['url']='index.php?c=rating';
				}else{
					$this->obj->DB_update_all("company_statis","`job_num` = '1'","`uid`='".$this->uid."'");
				}
			}
		}

		$row=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if($row['lastupdate']<1){
			$data['msg']="请先完善基本资料！";
			$data['url']='index.php?c=info';
		}
		$this->rightinfo();
		$msg=array();
		
		$isallow_addjob="1";
		
		if($this->config['com_enforce_emailcert']=="1"){
		    if($row['email_status']!="1"){
				$isallow_addjob="0";
				$msg[]="邮箱认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_mobilecert']=="1"){
		    if($row['moblie_status']!="1"){
		    	$isallow_addjob="0";
				$msg[]="手机认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_licensecert']=="1"){
		    if($row['yyzz_status']!="1"){
		    	$isallow_addjob="0";
				$msg[]="营业执照认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_setposition']=="1"){
		    if(empty($row['x'])||empty($row['y'])){
				$isallow_addjob="0";
				$msg[]="企业地图设置";
				$data['url']="index.php?c=map";
			}
		}
		
		if($isallow_addjob=="0"){
			
			$data['msg']="请先完成".implode(",",$msg)."！";
			
		}else if($_GET['id']){
			$job=$this->obj->DB_select_once("company_job","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
			$arr_data1=$arr_data['sex'][$job['sex']];
			$this->yunset("arr_data1",$arr_data1);
			if($job['id']){
				$job['langid']=$job['lang'];
				if($job['lang']!=""){
					$job['lang']= @explode(",",$job['lang']);
				}
				$job['days']= ceil(($job['edate']-$job['sdate'])/86400);
				$job_link=$this->obj->DB_select_once("company_job_link","`jobid`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
				$this->yunset("job_link",$job_link);
				$job['islink']=$job_link['link_type'];
 				$job['isemail']=$job_link['email_type'];
 				if($job['description']){
 					$job['description_t']=strip_tags($job['description']);
 				}
				$this->yunset("row",$job);
			}else{
				$data['msg']='非法操作！';
				$data['url']='index.php?c=job';
			}
		}
		if($_POST['submit']){

  			$id=intval($_POST['id']);
			$state= intval($_POST['state']);
			$logid = $_POST['logid'];

			unset($_POST['submit']);
			unset($_POST['id']);
			unset($_POST['state']);
			unset($_POST['logid']);

			$companycert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."'and type=3","uid,type,status");
			if($this->config['com_free_status']=="1"&&$companycert['status']=="1"){	
				$_POST['state']=1;
			}else{
				$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'","`status`,`did`");
				if($member['status']!="1"){
					$_POST['state']=0;
				}else{
					$_POST['state']=$this->config['com_job_status'];
				}
			}
			$_POST['r_status']=1;
			if(!empty($_POST['lang'])){
				$_POST['lang'] = pylode(",",$_POST['lang']);
			}else{
				$_POST['lang'] = "";
			}
			$_POST['sdate']=time();
 			
			$mapinfo=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`x`,`y`");
			if($mapinfo){
				$_POST['x']=$mapinfo['x'];
 				$_POST['y']=$mapinfo['y'];
			}
			$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'","`rating`");
			$_POST['com_name']=$row['name'];
			$_POST['com_logo']=$row['logo'];
			$_POST['com_provinceid']=$row['provinceid'];
			$_POST['pr']=$row['pr'];
			$_POST['mun']=$row['mun'];
			$_POST['rating']=$statis['rating'];
			$islink=(int)$_POST['islink'];
			$tblink=$_POST['tblink'];
			$link_type=$islink;
			if($islink<3){
				$linktype=$islink;
				$islink=1;
			}else{
				$islink=0;
			}
			$isemail=(int)$_POST['isemail'];
			$emailtype=$isemail;
			if($isemail<3){
				$isemail=1;
			}else{
				$isemail=0;
			}
			if($_POST['salary_type']==1){
				$_POST['minsalary']=$_POST['maxsalary']=0;
			}
			$_POST['is_link']=$islink;
			$_POST['link_type']=$linktype;
 			$_POST['is_email']=$isemail;
			$link_moblie=$_POST['link_moblie'];
			$email=$_POST['email'];
			$link_man=$_POST['link_man'];
			unset($_POST['salary_type']);
			unset($_POST['link_moblie']);
			unset($_POST['islink']);
			unset($_POST['isemail']);
			unset($_POST['link_man']);
			unset($_POST['email']);
			if($this->config['com_job_status']=="0" && $_POST['state']!=1){
			    $msg=",请等待审核";
			}else{
				$msg="";
			}
			if(!$id){
				$_POST['lastupdate']=time();
				$_POST['uid']=$this->uid;
				$_POST['did']=$member['did'];

				$data['msg']=$this->get_com(1);
				if($data['msg']==''){
					$_POST['source']=2;
					$nid=$this->obj->insert_into("company_job",$_POST);
					$name="添加职位";
					if($nid){
						$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$logid."'");
						$this->obj->DB_update_all("company","`jobtime`='".$_POST['lastupdate']."'","`uid`='".$this->uid."'");
						$state_content = "发布了新职位 <a href=\"".Url("job",array("c"=>"comapply","id"=>$nid))."\" target=\"_blank\">".$_POST['name']."</a>。";
						$this->addstate($state_content);
						$this->obj->member_log("发布了新职位 ".$_POST['name'],1,1);
						$Warning=$this->MODEL("warning");
						$Warning->warning("1");
					}else{
						$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$logid."'");
					}
					$data['msg']=$nid?$name."成功".$msg:$name."失败";
				}
			}else{
				$where['id']=$id;
				$where['uid']=$this->uid;
				
			 
				$nid=$this->obj->update_once("company_job",$_POST,$where);
				$name="更新职位";
				if($nid){
					$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$logid."'");
					$this->obj->member_log("更新职位《".$_POST['name']."》",1,2);
				}else{
					$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$logid."'");
				}
				$data['msg']=$nid?$name."成功".$msg:$name."失败";
				 
			}
			$joblink=array();
			$joblink[]="`email`='".trim($email)."',`is_email`='".$isemail."',`email_type`='".$emailtype."'";
			if($linktype==2){
				$joblink[]="`link_man`='".$link_man."',`link_moblie`='".$link_moblie."'";
			}
			if ($link_type){
				$joblink[]="`link_type`='".$link_type."'";
			}
			if($id){
				delfiledir("../data/upload/tel/".$this->uid);
				$linkid=$this->obj->DB_select_once("company_job_link","`uid`='".$this->uid."' and `jobid`='".$id."'","id");
				if($linkid['id']){
					if ($tblink==1){
						$this->obj->DB_update_all("company_job_link",@implode(',',$joblink),"`uid`='".$this->uid."'");
						$this->obj->DB_update_all("company_job","`link_type`='2'","`uid`='".$this->uid."'");
					}else {
						$this->obj->DB_update_all("company_job_link",@implode(',',$joblink),"`id`='".$linkid['id']."'");
					}
				}else{
					$joblink[]="`uid`='".$this->uid."'";
					$sid=$this->obj->DB_insert_once("company_job_link",@implode(',',$joblink).",`jobid`='".(int)$id."'");
					if($sid && $tblink==1){
						$this->obj->DB_update_all("company_job_link",@implode(',',$joblink),"`uid`='".$this->uid."'");
						$this->obj->DB_update_all("company_job","`link_type`='2'","`uid`='".$this->uid."'");
					}
				}
			}else if($nid>0){
				$joblink[]="`uid`='".$this->uid."'";
				$sid=$this->obj->DB_insert_once("company_job_link",@implode(',',$joblink).",`jobid`='".(int)$nid."'");
				if($sid && $tblink==1){
					$this->obj->DB_update_all("company_job_link",@implode(',',$joblink),"`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_job","`link_type`='2'","`uid`='".$this->uid."'");
				}
			}
			$data['error']=0;
			
			if($this->config['com_job_status']=="0"){
				$data['url']='index.php?c=job';
			}else if($this->config['com_job_status']=="1"){
				if($id){
					$data['url_tg']='index.php?c=job_tg&id='.$id;
				} else if($nid > 0){
					$data['url_tg']='index.php?c=job_tg&id='.$nid;
				}
			}
			echo json_encode($data);die;
		}
		$this->yunset("layer",$data);
		$cacheList=$this->MODEL('cache')->GetCache(array('city','com','hy','job'));
		$this->yunset($cacheList);
		$this->yunset('header_title',"发布职位");
		$this->waptpl('jobadd');
	}
	
	function job_tg_action(){
		$this->company_satic();
		if($_GET['id']){
			$id = (int)$_GET['id'];
			$job = $this->obj->DB_select_once("company_job","`id`='".$id."' and `state`='1' and `status`='0'");
			
			if($job && is_array($job)){
				$this->yunset('job',$job);
			}else{
				$data['msg']="该职位未满足推广条件";
				$data['url']="index.php?c=job";
				$this->yunset("layer",$data);
			}
		}
		
		$backurl=Url('wap',array('c'=>'job'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职位推广");
		$this->waptpl('job_tg');
	}
	function job_action(){
		$this->rightinfo();
		$jobM = $this->MODEL('job');
		$rows = $jobM->GetComjobList(array('uid'=>$this->uid),array('orderby'=>'`lastupdate`','desc'=>'desc'));
		$zp=$sh=$xj=0;
		if(is_array($rows)){
			$jobids = array();
		    foreach($rows as $value){
		    	$jobids[] = $value['id'];
		        if($value['state']==1 && $value['status']!=1){
		            $zp +=1;
		        }
		        if($value['state']!='1'){
		            $sh +=1;
		        }
		        if($value['status']=='1'){
		            $xj +=1;
		        }
		    }
		    $jobnum=$this->obj->DB_select_all("userid_job","`job_id` in(".pylode(',',$jobids).") and `com_id`='".$this->uid."' GROUP BY `job_id`","`job_id`,count(`id`) as `num`");
			foreach($rows as $k=>$v){
				$rows[$k]['jobnum']=0;
				foreach($jobnum as $val){
					if($v['id']==$val['job_id']){
						$rows[$k]['snum']=$val['num'];
					}
				}
 			}
		    
		}
		$this->yunset(array('zp'=>$zp,'sh'=>$sh,'xj'=>$xj));
		$this->yunset("rows",$rows);
		$this->company_satic();
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职位管理");
		$this->waptpl('job');
	}
	function refreshjob_action(){
		if($_GET['up']){
 			$nid=$this->obj->DB_update_all("company_job","`lastupdate`='".time()."'","`uid`='".$this->uid."' and `id`='".(int)$_GET['up']."'");
			if($nid){
				$this->obj->DB_update_all("company","`jobtime`='".time()."'","`uid`='".$this->uid."'");
				$job=$this->obj->DB_select_once("company_job","`id`='".(int)$_GET['up']."'","name");
				$job_sx=$this->obj->member_log("刷新职位《".$job['name']."》",1,4);
				$this->layer_msg('刷新职位成功！',9,0,$_SERVER['HTTP_REFERER']);
			}else{
				$this->layer_msg('刷新失败！',8,0,$_SERVER['HTTP_REFERER']);
			}
		}
	}
	function getserver_action(){
		if($this->config['wxpay']=='1'){
			$paytype['wxpay']='1';
		}
		if($this->config['alipay']=='1' &&  $this->config['alipaytype']=='1'){
			$paytype['alipay']='1';
		}
		if($paytype){
			$this->yunset("paytype",$paytype);
		}
		if($_GET['id']){
			$jobid=intval($_GET['id']);
			$info['id']=$jobid;
			$info['count']=1;
		}
		if($_GET['ids']){
			$info['id']=$_GET['ids'];
			$ids=@explode(",",$_GET['ids']);
			$count=count($ids);
			$info['count']=$count;
		}
		$server=intval($_GET['server']);
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		$this->yunset("info",$info);
		$this->yunset("statis",$statis);
		
		
 		$this->get_user();
 		
 		switch($server){
			case 1:$header_title="自动刷新";
			break;
			case 2:$header_title="职位置顶";
			break;
			case 3:$header_title="职位推荐";
			break;
			case 4:$header_title="紧急招聘";
			break;
			case 5:$header_title="刷新职位";
			break;
			case 6:$header_title="猎头职位刷新";
			break;
			case 7:$header_title="下载简历";
			break;
			case 8:$header_title="发布职位";
			break;
			case 9:$header_title="发布兼职";
			break;
			case 10:$header_title="发布猎头职位";
			break;
			case 11:$header_title="邀请面试";
			break;
			case 12:$header_title="下载简历";
			break;
			case 13:$header_title="兼职刷新";
			break;
		}
		$this->yunset('header_title',$header_title);
		
		$this->waptpl('getserver');
	}


	function jobset_action(){
		if($_GET['status']){
			if($_GET['status']==2){
				$_GET['status']=0;
			}
			$this->obj->update_once('company_job',array('status'=>intval($_GET['status'])),array('uid'=>$this->uid,'id'=>intval($_GET['id'])));
			$this->obj->member_log("修改职位招聘状态",1,2);
			$this->get_user();
			$this->waplayer_msg("设置成功！");
		}
	}

	function jobdel_action(){
		if($_GET['id']){
			
			$rewardJobNum = $this->obj->DB_select_num("company_job_reward","`uid`='".$this->uid."' AND `jobid`='".(int)$_GET['id']."'");

			$shareJobNum = $this->obj->DB_select_num("company_job_share","`uid`='".$this->uid."' AND `jobid`='".(int)$_GET['id']."'");

			if($rewardJobNum>0 || $shareJobNum>0){
				
				$this->waplayer_msg("您还有赏金职位未处理！");
			}else{
				$nid=$this->obj->DB_delete_all("company_job","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
				if($nid){
					$newest=$this->obj->DB_select_once("company_job","`uid`='".$this->uid."' order by lastupdate DESC","`lastupdate`");
					$this->obj->DB_delete_all("userid_job","`com_id`='".$this->uid."' and `job_id`='".(int)$_GET['id']."'");
					$this->obj->DB_delete_all("look_job","`com_id`='".$this->uid."' and `jobid`='".(int)$_GET['id']."'");
					$this->obj->DB_delete_all("fav_job","`job_id`='".(int)$_GET['id']."'"," ");
					$this->obj->DB_delete_all("user_entrust_record","`jobid`='".(int)$_GET['id']."' and `comid`='".$this->uid."'","");
					$this->obj->DB_delete_all("report","`usertype`=1 and `type`=0 and `eid`='".(int)$_GET['id']."'","");
					$this->obj->update_once("company",array("jobtime"=>$newest['lastupdate']),array("uid"=>$this->uid));
					$this->obj->DB_delete_all("company_job_link","`uid`='".$this->uid."' and `jobid`='".(int)$_GET['id']."'");
					$this->obj->member_log("删除职位记录（ID:".(int)$_GET['id']."）",1,3);
					$this->waplayer_msg("删除成功！");
				}else{
					$this->waplayer_msg("删除失败！");
				}
			}
			
		}
	}
	
	function partapply_action(){
		$this->rightinfo();
		
		if($_GET['del']){
			$nid=$this->obj->DB_delete_all("part_apply","`id`='".(int)$_GET['del']."' and `comid`='".$this->uid."'");
			if($nid){
				$data['msg']="删除成功!";
				$this->obj->member_log("删除兼职报名",6,3);
			}else{
				$data['msg']="删除失败！";
			}
			$data['url']='index.php?c=partapply';
			$this->yunset("layer",$data);
		}
		
		if((int)$_GET['id']&&(int)$_GET['status']){
			$nid=$this->obj->update_once("part_apply",array('status'=>(int)$_GET['status']),array("comid"=>$this->uid,"id"=>(int)$_GET['id']));
			if($nid){
			    $this->obj->member_log("更改兼职报名状态（ID:".(int)$_GET['id']."）",6,2);
				$this->waplayer_msg("操作成功！");
			}else{
				$this->waplayer_msg("操作失败！");
			}
		}
		$urlarr=array("c"=>"partapply","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("part_apply","`comid`='".$this->uid."'",$pageurl,"10");
		if(is_array($rows)&&$rows){
			include PLUS_PATH."/user.cache.php";
			include(CONFIG_PATH."db.data.php");
			unset($arr_data['sex'][3]);
			$this->yunset("arr_data",$arr_data);
			foreach($rows as $val){
				$jobid[]=$val['jobid'];
				$uid[]=$val['uid'];
			}
			$joblist=$this->obj->DB_select_all("partjob","`id` in(".pylode(',',$jobid).")","`id`,`name`");
			$uselist=$this->obj->DB_select_all("resume","`uid` in (".pylode(",",$uid).") and `r_status`<>'2'","`name`,`sex`,`edu`,`uid`,`birthday`,`telphone`,`def_job`,`birthday`");
		}
		foreach($rows as $key=>$val){
			foreach($joblist as $k=>$v){
				if($val['jobid']==$v['id']){
					$rows[$key]['job_name']=$v['name'];
				}
			}
			foreach($uselist as $k=>$va){
				if($val['uid']==$va['uid']){
					$rows[$key]['username']=$va['name'];
					$rows[$key]['moblie']=$va['telphone'];
					$rows[$key]['sex']=$arr_data['sex'][$va['sex']];
					$rows[$key]['edu']=$userclass_name[$va['edu']];
					$rows[$key]['age']=ceil((time()-strtotime($va['birthday']))/31104000);
					$rows[$key]['resumeid']=$va['def_job'];
					$rows[$key]['birthday']=$va['birthday'];
				}
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'part'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"兼职报名");
		$this->get_user();
		$this->waptpl('partapply');
	}
	
	function hr_action(){
		$this->rightinfo();
		$urlarr=array("c"=>"hr","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("userid_job","`com_id`='".$this->uid."' ORDER BY is_browse asc,datetime desc",$pageurl,"10");
		if(is_array($rows) && !empty($rows)){
			$uid=$eid=array();
			foreach($rows as $v){
				$uid[]=$v['uid'];
				$eid[]=$v['eid'];
			}
			$userrows=$this->obj->DB_select_all("resume","`uid` in (".pylode(",",$uid).") and `r_status`<>'2'","`name`,`sex`,`edu`,`uid`,`exp`");
			$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uid).")","uid,jobid");
			$expect=$this->obj->DB_select_all("resume_expect","`id` in (".pylode(",",$eid).")","`id`,`job_classid`,`salary`,`height_status`");
			
			if(is_array($userrows)){
				include(PLUS_PATH."user.cache.php");
				include(PLUS_PATH."job.cache.php");
				include(CONFIG_PATH."db.data.php");
				unset($arr_data['sex'][3]);
				$this->yunset("arr_data",$arr_data);
				$expectinfo=array();
				foreach($expect as $key=>$val){
					$jobids=@explode(',',$val['job_classid']);
					$jobname=array();
					foreach($jobids as $v){
						$jobname[]=$job_name[$v];
					}
					$expectinfo[$val['id']]['jobname']=@implode('、',$jobname);
					$expectinfo[$val['id']]['salary']=$userclass_name[$val['salary']];
					$expectinfo[$val['id']]['height_status']=$val['height_status'];
				}
				foreach($rows as $k=>$v){
					$rows[$k]['jobname']=$expectinfo[$v['eid']]['jobname'];
					$rows[$k]['salary']=$expectinfo[$v['eid']]['salary'];
					$rows[$k]['height_status']=$expectinfo[$v['eid']]['height_status'];

					foreach($userrows as $val){
						if($v['uid']==$val['uid']){
							$rows[$k]['name']=$val['name'];

							$rows[$k]['edu']=$userclass_name[$val['edu']];
							$rows[$k]['exp']=$userclass_name[$val['exp']];
							$rows[$k]['sex']=$arr_data['sex'][$val['sex']];
						}
					}
					foreach($userid_msg as $val){
						if($v['uid']==$val['uid']){
							$rows[$k]['userid_msg']=1;
						}
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"应聘简历");
		$this->get_user();
		$this->waptpl('hr');
	}
	function hrset_action(){
		$id=(int)$_GET['id'];
		$browse=(int)$_GET['is_browse'];

 		if($id&&$browse){
			$nid=$this->obj->update_once("userid_job",array('is_browse'=>$browse),array("com_id"=>$this->uid,"id"=>$id));
			$this->obj->member_log("更改申请职位状态（ID:".$id."）",6,2);
			
			if($browse==4){
				$resumeuid=$this->obj->DB_select_once("userid_job","`id`='".$id."'",'eid,job_id');
				$resumeexp=$this->obj->DB_select_once("resume_expect","`id`='".$resumeuid['eid']."' and `r_status`<>'2' and `status`='1'",'uid,uname');
				$uid=$this->obj->DB_select_once("resume","`uid`='".$resumeexp['uid']."'","telphone,email");
				$comjob=$this->obj->DB_select_once("company_job","`uid`='".$this->uid."' and `id`='".$resumeuid['job_id']."'","name,com_name");
				$data['uid']=$resumeexp['uid'];
				$data['cname']=$this->username;
				$data['name']=$resumeexp['uname'];
				$data['type']="sqzwhf";
				$data['cuid']=$this->uid;
				$data['company']=$comjob['com_name'];
				$data['jobname']=$comjob['name'];
				if($this->config['sy_msg_sqzwhf']=='1'&&$uid["telphone"]&&$this->config["sy_msguser"]&&$this->config["sy_msgpw"]&&$this->config["sy_msgkey"]&&$this->config['sy_msg_isopen']=='1'){$data["moblie"]=$uid["telphone"]; }
				if($this->config['sy_email_sqzwhf']=='1'&&$uid["email"]&&$this->config['sy_email_set']=="1"){$data["email"]=$uid["email"]; }
				if($data["email"]||$data['moblie']){
					$notice = $this->MODEL('notice');
					$notice->sendEmailType($data);
					$notice->sendSMSType($data);
				}
			}
			$nid?$this->waplayer_msg("操作成功！"):$this->waplayer_msg("操作失败！");
		}
	}
	function delhr_action(){
		$nid=$this->obj->DB_delete_all("userid_job","`id`='".(int)$_GET['id']."' and `com_id`='".$this->uid."'");
		$this->obj->member_log("删除申请职位记录（ID:".(int)$_GET['id']."）",6,3);
		$nid?$this->waplayer_msg("删除成功！"):$this->waplayer_msg("删除失败！");
	}
	function password_action(){
		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"修改密码");
		$this->get_user();
		$this->waptpl('password');
	}
	
	function time_action(){
		$com=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
		$this->yunset("com",$com);
		
		$pser=$this->obj->DB_select_all("company_service","`display`='1'" );
		$this->yunset("pser",$pser);
		
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($this->config['com_vip_type'] == 2){
			$where = '`type` = 1 ';
		}
		else if($this->config['com_vip_type'] == 1){
			$where = '`type` = 2';
		}
		else{
			
			$where = '`type` = 2 ';
		}

		$rows=$this->obj->DB_select_all("company_rating","`category`='1' and `display`='1' and `service_price` > 0  and {$where} order by `type` asc,`sort` desc");
		$row=$this->obj->DB_select_once("company_rating","`category`='1' and `display`='1' and `service_price` > 0  and {$where} order by `type` asc,`sort` desc");
		if(is_array($rows)&&$rows){
			foreach($rows as $v){
				$couponid[]=$v['coupon'];
			}
			if(empty($coupon)){
				$coupon=$this->obj->DB_select_all("coupon","`id` in (".@implode(",",$couponid).")","`id`,`name`");
			}
			if(is_array($coupon)){
				foreach($rows as $k=>$v){
					foreach($coupon as $val){
						if($v['coupon']==$val['id']){
							$rows[$k]['couponname']=$val['name'];
						}
					}
				}
			}
		}
		if($rows&&is_array($rows)){
			foreach ($rows as $k=>$v){
				$rname=array();
				if($v['job_num']>0){$rname[]='发布职位:'.$v['job_num'].'份';}
				if($v['breakjob_num']>0){$rname[]='刷新职位:'.$v['breakjob_num'].'份';}
				if($v['resume']>0){$rname[]='下载简历:'.$v['resume'].'份';}
				if($v['interview']>0){$rname[]='邀请面试:'.$v['interview'].'份';}
				if($v['part_num']>0){$rname[]='发布兼职职位:'.$v['part_num'].'份';}
				if($v['breakpart_num']>0){$rname[]='刷新兼职职位:'.$v['breakpart_num'].'份';}
				if($v['lt_job_num']>0){$rname[]='发布猎头职位:'.$v['lt_job_num'].'份';}
				if($v['lt_breakjob_num']>0){$rname[]='刷新猎头职位:'.$v['lt_breakjob_num'].'份';}
				if($v['lt_resume']>0){$rname[]='下载高级简历:'.$v['lt_resume'].'份';}
				if($v['msg_num']>0){$rname[]='短信数:'.$v['msg_num'].'份';}
				$rows[$k]['rname']=@implode('+',$rname);
			}
		}
		$this->yunset("rows",$rows);	
		$this->yunset("row",$row);
		$this->yunset("js_def",4);
		
		
		$this->yunset('header_title',"购买会员");

		$this->get_user();
		if($this->config['com_vip_type'] == 1 || $this->config['com_vip_type'] == 0){
			$this->waptpl('member_time');
		}else if($this->config['com_vip_type'] == 2){
			$this->waptpl('member_rating');
		}
	}
	function rating_action(){
		$com=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		
		$this->yunset("statis",$statis);
		$this->yunset("com",$com);
		
		$pser=$this->obj->DB_select_all("company_service","`display`='1'" );
		$this->yunset("pser",$pser);
		
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		
 		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($this->config['com_vip_type'] == 2){
			$where = '`type` = 1 ';
		}
		else if($this->config['com_vip_type'] == 1){
			$where = '`type` = 2';
		}
		else{
			
			$where = '`type` = 1 ';
		}

		$rows=$this->obj->DB_select_all("company_rating","`category`='1' and `display`='1' and `service_price` > 0 and {$where} order by `type` asc,`sort` desc");
		if(is_array($rows)&&$rows){
			foreach($rows as $v){
				$couponid[]=$v['coupon'];
			}
			if(empty($coupon)){
				$coupon=$this->obj->DB_select_all("coupon","`id` in (".@implode(",",$couponid).")","`id`,`name`");
			}
			if(is_array($coupon)){
				foreach($rows as $k=>$v){
					foreach($coupon as $val){
						if($v['coupon']==$val['id']){
							$rows[$k]['couponname']=$val['name'];
						}
					}
				}
			}
		}
		if($rows&&is_array($rows)){
			foreach ($rows as $k=>$v){
				$rname=array();
				if($v['job_num']>0){$rname[]='发布职位:'.$v['job_num'].'份';}
				if($v['breakjob_num']>0){$rname[]='刷新职位:'.$v['breakjob_num'].'份';}
				if($v['resume']>0){$rname[]='下载简历:'.$v['resume'].'份';}
				if($v['interview']>0){$rname[]='邀请面试:'.$v['interview'].'份';}
				if($v['part_num']>0){$rname[]='发布兼职职位:'.$v['part_num'].'份';}
				if($v['breakpart_num']>0){$rname[]='刷新兼职职位:'.$v['breakpart_num'].'份';}
				if($v['lt_job_num']>0){$rname[]='发布猎头职位:'.$v['lt_job_num'].'份';}
				if($v['lt_breakjob_num']>0){$rname[]='刷新猎头职位:'.$v['lt_breakjob_num'].'份';}
				if($v['lt_resume']>0){$rname[]='下载高级简历:'.$v['lt_resume'].'份';}
				if($v['msg_num']>0){$rname[]='短信数:'.$v['msg_num'].'份';}

				
				if($this->config['com_vip_type'] == 1){
					$rows[$k]['rname'] = '时间模式会员，有效时间内，发布职位、下载简历等操作不受限制！';
				}else{
					$rows[$k]['rname']=@implode('+',$rname);
				}
			}
			
 		}
		$this->yunset("rows",$rows);
		$this->yunset("row",$rows[0]);
		$this->yunset("js_def",4);
		
		
		$this->yunset('header_title',"购买会员");
		$this->get_user();
		
 		$this->waptpl('member_rating');
		if($this->config['com_vip_type'] == 2 || $this->config['com_vip_type'] == 0){
			$this->waptpl('member_rating');
		}else if($this->config['com_vip_type'] == 1){
			$this->waptpl('member_time');
		}

	}
	function added_action(){
		$id=intval($_GET['id']);
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		$rows=$this->obj->DB_select_all("company_service","`display`='1' order by sort desc" );
 		
		if($id){
			$info=$this->obj->DB_select_all("company_service_detail","`type` = '$id' order by `sort`desc");
			$service_one = $this->obj->DB_select_once("company_service_detail","`type` = '".$id."' order by `sort`desc");
		}else{
			$row=$this->obj->DB_select_once("company_service","`display`='1'  order by sort desc","id");
			$info=$this->obj->DB_select_all("company_service_detail","`type` = '".$row['id']."' order by `sort`desc");
			$service_one = $this->obj->DB_select_once("company_service_detail","`type` = '".$row['id']."' order by `sort`desc");
		}
		
		if($statis['rating']>0){
			if($statis['vip_etime']>time()){
				$days=round(($statis['vip_etime']-mktime())/3600/24) ;
				$this->yunset("days",$days);
			}
		}
		
		if ($statis){
			$rating=$statis['rating'];
			$discount=$this->obj->DB_select_once("company_rating","`id`=$rating");
			$this->yunset("discount",$discount);
		}
		$this->yunset("statis",$statis);
		
		$this->yunset("info",$info);
		$this->yunset("p_once",$service_one);
		
		$this->yunset("rows",$rows);
		$this->yunset("js_def",4);
		$this->yunset('header_title',"增值服务");
		$this->get_user();
		$this->waptpl('added');

	}
	
	function pay_action(){
 		
		if($this->config['wxpay']=='1'){
			$paytype['wxpay']='1';
		}
		if($this->config['alipay']=='1' &&  $this->config['alipaytype']=='1'){
			$paytype['alipay']='1';
		}
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		if($this->config['bank']=='1' &&  $banks){
			$paytype['bank']='1';
		}
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($paytype){
			$statis=$this->company_satic();
			if($_POST['usertype']=='price'){
				
				$id=(int)$_POST['id'];
				if ($id){

					$rows=$this->obj->DB_select_once("company_rating","`service_price`<>'' and `service_time`>'0' and `id`='".$id."' and `display`='1' and `category`=1 order by sort desc","name,time_start,time_end,service_price,yh_price,coupon,id");

					
					if((int)$rows['service_price'] == 0){

 					
						$ratingM =  $this->MODEL('rating');
						$value=$ratingM->rating_info($id);
				 
						$status=$this->obj->DB_update_all('company_statis',$value,"`uid`= '".$this->uid."' ");
						$this->obj->DB_update_all("company_job","`rating`= {$id} ","`uid`='".$this->uid."'");

						if($status){
							$data['msg']="会员服务购买成功！";
							$data['url']='index.php?c=com';
							$this->yunset("layer",$data);
						}else{
							$data['msg']="服务购买失败，请稍后重试！";
							$data['url']=$_SERVER['HTTP_REFERER'];
							$this->yunset("layer",$data);
						}	

					}else{

						if ($rows['time_start']<time() && $rows['time_end']>time()){
							if ($rows['coupon']>0){
								$coupon=$this->obj->DB_select_once("coupon","`id`='".$rows['coupon']."'");
								$this->yunset("coupon",$coupon);
							}
						}

					}

				}else{
					$typeWhere = "`type` = 1";
					if($this->config['com_vip_type'] == 1){
						$typeWhere = '`type` = 2';
					}
					else if($this->config['com_vip_type'] == 0){
						$typeWhere = '`type` in (1,2) ';
					}
					$rows=$this->obj->DB_select_all("company_rating","`service_price`<>'' and `service_time`>'0' and `display`='1' and `category`=1 and {$typeWhere} order by sort desc","name,time_start,time_end,service_price,yh_price,id");
				}
				$this->yunset("rows",$rows);




			}elseif($_POST['usertype']=='service'){
 				if($data['msg'] == ''){
				$id=(int)$_POST['id'];
				if($id){
					$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
					if ($statis){
						$rating=$statis['rating'];
						$discount=$this->obj->DB_select_once("company_rating","`id`='".$rating."'");
						$this->yunset("discount",$discount);
					}
					$rows=$this->obj->DB_select_once("company_service_detail","`id`='".$id."'","type,service_price,id");
					if ($rows['type']){
						$service=$this->obj->DB_select_once("company_service","`id`='".$rows['type']."'");
						$this->yunset("service",$service);
					}
					$this->yunset("rows",$rows);
				}else{
					$data['msg']="请选择套餐！";
					$data['url']=$_SERVER['HTTP_REFERER'];
					$this->yunset("layer",$data);
				}}else{
					$data['url']=$_SERVER['HTTP_REFERER'];
					$this->yunset("layer",$data);
				}

			}elseif($_GET['id']){
				$order=$this->obj->DB_select_once("company_order","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."'");
				if(empty($order)){
					$this->ACT_msg($_SERVER['HTTP_REFERER'],"订单不存在！");
				}elseif($order['order_state']!='1'){
					header("Location:index.php?c=paylog");
				}else{
					$this->yunset("order",$order);
				}
			}
			$this->yunset("statis",$statis);
			$remark="姓名：\n联系电话：\n留言：";
			$this->yunset("paytype",$paytype);
			$this->yunset("remark",$remark);
			$this->yunset("js_def",4);
		}else{
			$data['msg']="暂未开通手机支付，请移步至电脑端充值！";
			$data['url']=$_SERVER['HTTP_REFERER'];
			$this->yunset("layer",$data);
		}
		$nopayorder=$this->obj->DB_select_num("company_order","`uid`=".$this->uid." and `order_state`=1");
		$this->yunset('nopayorder',$nopayorder);
		$this->yunset($this->MODEL('cache')->GetCache(array('integralclass')));
		
		
		$this->yunset('header_title',"充值积分");
		$this->get_user();
		$this->waptpl('pay');
	}
	
	function payment_action(){
 		if($this->config['wxpay']=='1'){
			$paytype['wxpay']='1';
		}
		if($this->config['alipay']=='1' &&  $this->config['alipaytype']=='1'){
			$paytype['alipay']='1';
		}
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		if($this->config['bank']=='1' &&  $banks){
			$paytype['bank']='1';
		}
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($paytype){
			$statis=$this->company_satic();
			if($_GET['id']){
				$order=$this->obj->DB_select_once("company_order","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."'");
				if(empty($order)){
					$this->ACT_msg($_SERVER['HTTP_REFERER'],"订单不存在！");
				}elseif($order['order_state']!='1'){
					header("Location:index.php?c=paylog");
				}else{
					$this->yunset("order",$order);
				}
			}
			$this->yunset("statis",$statis);
 			$this->yunset("paytype",$paytype);
 			$this->yunset("js_def",4);
		}else{
			$data['msg']="暂未开通手机支付，请移步至电脑端充值！";
			$data['url']=$_SERVER['HTTP_REFERER'];
			$this->yunset("layer",$data);
		}


		$this->yunset('header_title',"订单确认");
		$this->get_user();
		$this->waptpl('payment');
	}

	
	function company_satic(){
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'"); 
		if($statis['rating']){
			$rating=$this->obj->DB_select_once("company_rating","`id`='".$statis['rating']."'"); 
		}
		if($statis['vip_etime'] < time()){
			if($statis['vip_etime'] > '1'){ 
				$nums=0;
			}else if($statis['vip_etime'] < '1' && $statis['rating']!="0"){
				$nums=1;
			}else{
				$nums=0;
			}
			if($nums == 0){
				if($this->config['com_vip_done']=='0'){
					$data['job_num']=$data['down_resume']=$data['invite_resume']=$data['editjob_num']=$data['breakjob_num']=$data['part_num']=$data['editpart_num']=$data['breakpart_num']=$data['zph_num']=$data['lt_job_num']=$data['lt_editjob_num']=$data['lt_down_resume']=$data['lt_breakjob_num']='0';
					$data['oldrating_name']=$statis['rating_name'];
					$statis['rating_name']=$data['rating_name']="过期会员";
					$statis['rating_type']=$statis['rating']=$data['rating_type']=$data['rating']="0"; 
					$where['uid']=$this->uid;
					$this->obj->DB_update_all("company_job","`rating`='0'","`uid`='".$this->uid."'");
					$this->obj->update_once("company_statis",$data,$where);
				}elseif ($this->config['com_vip_done']=='1'){
					$ratingM = $this->MODEL('rating');
					$rat_value=$ratingM->rating_info();
					$this->obj->DB_update_all("company_job","`rating`='".$this->config['com_rating']."'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_statis",$rat_value,"`uid`='".$this->uid."'");
				}
			}
		}
		if($statis['autotime']>=time()){
			$statis['auto'] = 1;
		}
		
		if($statis['vip_etime']>time() || $statis['vip_etime']==0){
			if($statis['rating_type']=="2"){
				$addjobnum=$addpartjobnum=$editjobnum=$editpartjobnum='1';
			}else if($statis['rating_type']=="1"){
				if($statis['job_num']>0){
					$addjobnum='1';
				}else{
 					$addjobnum='2';
				}
				if($statis['part_num']>0){
					$addpartjobnum='1';
				}else{
					$addpartjobnum='2';
				}
				if($statis['lt_job_num']>0){
					$addltjobnum='1';
				}else{
					$addltjobnum='2';
				}
			}else{
				$addjobnum=$addpartjobnum=$editjobnum=$editpartjobnum='0';
			}
		}else {
			$addjobnum=$addpartjobnum=$addltjobnum='0';
		}
		$statis['addjobnum']=$addjobnum;
		$statis['addltjobnum']=$addltjobnum;
		$statis['addpartjobnum']=$addpartjobnum;
		$statis['pay_format']=number_format($statis['pay'],2);
		$statis['integral_format']=number_format($statis['integral']);
		$this->yunset("addltjobnum",$addltjobnum);
		$this->yunset("addjobnum",$addjobnum);
		$this->yunset("addpartjobnum",$addpartjobnum);
		$this->yunset("statis",$statis);
		$this->yunset("rating",$rating);
		return $statis;
	}


	function getOrder_action(){
		if($_POST){
       		$M=$this->MODEL('compay');
			if($_POST['server']=='autojob'){
 				$return = $M->buyAutoJob($_POST);
				$msg="购买职位自动刷新";
			}elseif ($_POST['server']=='zdjob'){
				$return = $M->buyZdJob($_POST);
				$msg="购买职位置顶";
			}elseif ($_POST['server']=='ujob'){
				$return = $M->buyUrgentJob($_POST);
				$msg="购买紧急招聘";
			}elseif ($_POST['server']=='recjob'){
				$return = $M->buyRecJob($_POST);
				$msg="购买职位推荐";

			}elseif ($_POST['server']=='sxjob'){
				$return = $M->buyRefreshJob($_POST);
				$msg="购买刷新职位";
			}elseif ($_POST['server']=='sxpart'){
				$return = $M->buyRefreshPart($_POST);
				$msg="购买刷新兼职";
			}elseif ($_POST['server']=='sxltjob'){
				$return = $M->buyRefreshLtJob($_POST);
				$msg="购买刷新高级职位";

			}elseif ($_POST['server']=='issue'){
				$return = $M->buyIssueJob($_POST);
				$msg="购买职位发布";
			}elseif ($_POST['server']=='issuepart'){
				$return = $M->buyIssuePart($_POST);
				$msg="购买兼职发布";
			}elseif ($_POST['server']=='issueltjob'){
				$return = $M->buyIssueLtJob($_POST);
				$msg="购买高级职位发布";

			}elseif ($_POST['server']=='downresume'){
				$return = $M->buyDownresume($_POST);
			}elseif ($_POST['server']=='invite'){
				$return = $M->buyInviteResume($_POST);

			}elseif ($_POST['server']=='rewardjob'){
				$packM = $this->MODEL('pack');
				$return = $packM->rewardPackOrder($_POST);
				$msg="悬赏职位推广";
			}elseif ($_POST['server']=='sharejob'){
				$packM = $this->MODEL('pack');
				$return = $packM->redPackOrder($_POST);
				$msg="分享职位推广";
			}


			if($return['order']['order_id'] && $return['order']['id']){
				$dingdan = $return['order']['order_id'];
				$price = $return['order']['order_price'];
				$id = $return['order']['id'];

				$this->obj->member_log($msg.",订单ID".$dingdan,88);
				
				$_POST['dingdan']=$dingdan;
				$_POST['dingdanname']=$dingdan;
				$_POST['alimoney']=$price;
				$data['msg']="下单成功，请付款！";
				
				
				if($_POST['paytype']=='alipay'){
					$url=$this->config['sy_weburl'].'/api/wapalipay/alipayto.php?dingdan='.$dingdan.'&dingdanname='.$dingdan.'&alimoney='.$price;
					header('Location: '.$url);exit();
				}elseif($_POST['paytype']=='wxpay'){
					$url='index.php?c=wxpay&id='.$id;
					header('Location: '.$url);exit();
				}
			}else{
				
				if($return['error']){
					$data['msg']=$return['error'];
				}else{
					$data['msg']="提交失败，请重新提交订单！";
				}
				
				$data['url']=$_SERVER['HTTP_REFERER'];
			}
 		}else{
			$data['msg']="参数不正确，请正确填写！";
			$data['url']=$_SERVER['HTTP_REFERER'];
		}
		$this->yunset("layer",$data);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->get_user();
		$this->waptpl('pay');
	}

	function dkzf_action(){
		if($_POST){
   			$M=$this->MODEL('jfdk');
			if ($_POST['jobautoids']){
				$return = $M->buyAutoJob($_POST);
			}elseif($_POST['zdjobid']){		
				$return = $M->buyZdJob($_POST);
			}elseif ($_POST['recjobid']){
				$return = $M->buyRecJob($_POST);
			}elseif ($_POST['ujobid']){
				$return = $M->buyUrgentJob($_POST);
			}elseif ($_POST['sxjobid']){
				$return = $M->buyRefreshJob($_POST);
			}elseif ($_POST['sxltjobid']){
				$return = $M->buyRefreshLtJob($_POST);
			}elseif ($_POST['eid']){
				$return = $M->downresume($_POST);
			}elseif ($_POST['issuejob']){
				$return = $M->buyIssueJob($_POST);
			}elseif ($_POST['issuepart']){
				$return = $M->buyIssuePart($_POST);
			}elseif ($_POST['issueltjob']){
				$return = $M->buyIssueLtJob($_POST);
			}elseif ($_POST['invite']){
				$return = $M->buyInviteResume($_POST);
			}elseif($_POST['tcid']){
				$return = $M->buyPackOrder($_POST);
			}elseif($_POST['id']){
				$return = $M->buyVip($_POST);
			}elseif ($_POST['sxpartid']){
				$return = $M->buyRefreshPart($_POST);
			}
			if($return['status']==1){
				if($_POST['logid']){
					$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$_POST['logid']."'");
				}
				
				echo json_encode(array('error'=>0,'msg'=>$return['msg']));
			}else{
				if($_POST['logid']){
					$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$_POST['logid']."'");
				}
				
				echo json_encode(array('error'=>1,'msg'=>$return['error'],'url'=>$return['url']));
			}
		}else{
			echo json_encode(array('error'=>1,'msg'=>'参数错误，请重试！'));
		}
	}
	function dingdan_action(){
		$data['msg']="参数不正确，请正确填写！";
		$data['url']=$_SERVER['HTTP_REFERER'];
		
		if($_POST['price']){
			
			$statis = $this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'","`integral`");
			
			if($_POST['comvip']){
				$comvip=(int)$_POST['comvip'];
				$ratinginfo =  $this->obj->DB_select_once("company_rating","`id`='".$comvip."'");
				
				$dkjf=(int)$_POST['dkjf'];
				
				if($dkjf >= (int)$statis['integral']){
					$dkjf = $statis['integral'];
				}
				
				if($dkjf){
					$price_ = $dkjf / $this->config['integral_proportion'];
				}else{
					$price_ = 0;
				}
				
				if($ratinginfo['time_start']<time() && $ratinginfo['time_end']>time()){
					$price = $ratinginfo['yh_price'] - $price_ ;
				}else{
					$price = $ratinginfo['service_price'] - $price_;
				}
				
				$data['type']='1';

			}elseif($_POST['comservice']){
				
 				
				$id=(int)$_POST['comservice'];
				
				$dkjf=(int)$_POST['dkjf'];
				
				if($dkjf >= (int)$statis['integral']){
					$dkjf = (int)$statis['integral'];
				}
				
				if($dkjf){
					$price_ = $dkjf / $this->config['integral_proportion'];
				}else{
					$price_ = 0;
				}
				
				$price=$_POST['price'] - $price_ ;

				$data['type']='5';

			}elseif($_POST['price_int']){
			    if($this->config['integral_min_recharge'] && $_POST['price_int']<$this->config['integral_min_recharge']){
			        $data['msg']="充值不得低于".$this->config['integral_min_recharge'];
			        $data['url']=$_SERVER['HTTP_REFERER'];
			        $this->yunset("layer",$data);
			        $this->waptpl('pay');exit;
			    }
			    $integralid=intval($_POST['integralid']);
			    $CacheMclass=$this->MODEL('cache')->GetCache(array('integralclass'));
			    $discount=$CacheMclass['integralclass_discount'][$integralid]/100;
			    if($integralid&&$discount>0){
			        $price = $_POST['price_int']/$this->config['integral_proportion']*$discount;
			    }else{
			        $price = $_POST['price_int']/$this->config['integral_proportion'];
			    }
			    $price=floor($price*100)/100;
			    
				$data['type']='2';
			}
			

			 
	
			if($_POST['coupon']){
 				$coupon = $this->obj->DB_select_once("coupon_list","`uid`='".$this->uid."' and `id`='".$_POST['coupon']."' and `validity`>'".time()."'  and `status`='1'");
 				
 				if($coupon && (int)$coupon['coupon_scope'] <= (int)$price && (int)$coupon['coupon_amount'] < (int)$price){
 
					$price = sprintf("%.2f", $price-$coupon['coupon_amount']);
					$data['coupon']=$_POST['coupon'];

				}elseif((int)$price <=(int)$coupon['coupon_amount'] && (int)$coupon['coupon_scope'] <= (int)$price){

					 
					
					if($_POST['comvip']){
 
						$ratingM = $this->MODEL('rating');
						$value=$ratingM->rating_info($_POST['comvip'],$this->uid);

						$status=$this->obj->DB_update_all("company_statis",$value,"`uid`='".$this->uid."'");
						
						$this->obj->DB_update_all("company_job","`rating`='".$_POST['comvip']."'","`uid`='".$this->uid."'");

						$data['url']=Url('wap',array('c'=>'com'),'member');

					}else if($_POST['comservice']){
						
						$row=$this->obj->DB_select_once("company_service_detail","`id`='".$_POST['comservice']."'");
						$value.="`job_num`=`job_num`+'".$row['job_num']."',";
						$value.="`down_resume`=`down_resume`+'".$row['resume']."',";
						$value.="`invite_resume`=`invite_resume`+'".$row['interview']."',";
						$value.="`breakjob_num`=`breakjob_num`+'".$row['breakjob_num']."',";
						$value.="`part_num`=`part_num`+'".$row['part_num']."',";
						$value.="`breakpart_num`=`breakpart_num`+'".$row['breakpart_num']."',";
						$value.="`all_pay`=`all_pay`+'".$order["order_price"]."',";
						$value.="`lt_job_num`=`lt_job_num`+'".$row['lt_job_num']."',";
						$value.="`lt_down_resume`=`lt_down_resume`+'".$row['lt_resume']."',";
						$value.="`lt_breakjob_num`=`lt_breakjob_num`+'".$row['lt_breakjob_num']."'";
						$status=$this->obj->DB_update_all("company_statis",$value,"`uid`='".$this->uid."'");

						$data['url']=Url('wap',array('c'=>'com'),'member');
							
					}elseif($_POST['price_int']){
					
 						$status=$this->obj->DB_update_all("company_statis","`integral`=`integral`+'".$_POST['price_int']."'","`uid`='".$this->uid."'");

						if($status){
							$integralM = $this->MODEL('integral');
							$integralM->insert_company_pay($_POST['price_int'],2,$this->uid,"优惠券购买".$this->config['integral_pricename'],1,2,true);
						}

						$data['url']=Url('wap',array('c'=>'com'),'member');
					}

					if($status){
						$this->obj->DB_update_all("coupon_list","`status`='2',`xf_time`='".time()."'","`id`='".$_POST['coupon']."'");
						$data['msg']="购买成功！";
					}else{
						$data['msg']="购买失败！";
				        $data['url']=$_SERVER['HTTP_REFERER'];
					}
					
			        $this->yunset("layer",$data);
			        $this->waptpl('com');exit;
				}
			}
			
			$dingdan=mktime().rand(10000,99999);
			$data['order_id']=$dingdan;
			$data['order_dkjf']=$dkjf;
			$data['order_price']=$price;
			$data['order_time']=mktime();
			$data['order_state']="1";
			$data['order_type']=$_POST['paytype'];
			$data['order_remark']=trim($_POST['remark']);
			$data['uid']=$this->uid;
			$data['rating']=$_POST['comvip']?$_POST['comvip']:$_POST['comservice'];
			$data['integral']=$_POST['price_int'];
			
			if(is_uploaded_file($_FILES['pic']['tmp_name'])){
			    $UploadM=$this->MODEL('upload');
			    $upload=$UploadM->Upload_pic(APP_PATH."/data/upload/order/",false);
			    $pictures=$upload->picture($_FILES['pic']);
			    $pic=str_replace(APP_PATH."/data/upload/order/","./data/upload/order/",$pictures);
			    $data['order_pic']=$pic;
			}
			
			$id=$this->obj->insert_into("company_order",$data);
			
			if($id){
				$this->obj->DB_update_all("user_log","`status`=3,`orderid`='".$dingdan."'","`id`='".$_POST['logid']."'");
				$this->cookie->SetCookie("delay","",time() + 1);

				$this->obj->DB_update_all("coupon_list","`status`='2',`xf_time`='".time()."'","`id`='".$coupon['id']."'");
				
				if($_POST['comservice']){
					$this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买增值包",true,2,'integral',11);
					$this->obj->member_log("购买增值服务,订单ID".$dingdan,88);
				}else if($_POST['comvip']){
				    $this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买会员",true,2,'integral',27);
					$this->obj->member_log("购买会员,订单ID".$dingdan,88);
				}else if($_POST['price_int']){
					$this->obj->member_log("充值积分,订单ID".$dingdan,88);
				}
				
				$data['msg']="下单成功，请付款！";
				
				if($_POST['paytype']=='alipay'){
				    $url=$this->config['sy_weburl'].'/api/wapalipay/alipayto.php?dingdan='.$dingdan.'&dingdanname='.$dingdan.'&alimoney='.$price;
					header('Location: '.$url);exit();
				}elseif($_POST['paytype']=='wxpay'){
					$url='index.php?c=wxpay&id='.$id;
					header('Location: '.$url);exit();
				}
			}else{				
				$this->obj->DB_update_all("user_log","`status`=2","`id`='".$_POST['logid']."'");
				$data['msg']="提交失败，请重新提交订单！";
				$data['url']=$_SERVER['HTTP_REFERER'];
			}
		}else{
			$data['msg']="参数不正确，请正确填写！";
			$data['url']=$_SERVER['HTTP_REFERER'];
		}
		$this->yunset("layer",$data);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->get_user();
		$this->waptpl('pay');
	}
	function wxpay_action(){
		if($_GET['id']){
			$id = (int)$_GET['id'];
			$order = $this->obj->DB_select_once("company_order","`uid`='".$this->uid."' AND `id`='".$id."'");
			if(!empty($order)){
				require_once(LIB_PATH.'wxOrder.function.php');
				
				if(!is_weixin()){
					$jsApiParameters = wxWapOrderMweb(array('body'=>'充值','id'=>$order['order_id'],'url'=>$this->config['sy_weburl'],'total_fee'=>$order['order_price']));
					if($jsApiParameters['mweb_url']){
						header('Location: '.$jsApiParameters['mweb_url'].'&redirect_url='.urlencode($this->config['sy_wapdomain'].'/member/index.php?c=pay&id='.$order['id']));
						exit();
					}else{
						if($jsApiParameters['err_code_des']){
							$data['msg']=$jsApiParameters['err_code_des'];
						}elseif($jsApiParameters['return_msg']){
							$data['msg']=$jsApiParameters['return_msg'];
						}else{
							$data['msg']='支付失败';
						}
						$data['url']='index.php?c=com';
						$this->yunset("layer",$data);
					}
				}else{
					$jsApiParameters = wxWapOrder(array('body'=>'充值','id'=>$order['order_id'],'url'=>$this->config['sy_weburl'],'total_fee'=>$order['order_price']));
					if($jsApiParameters){
						$this->yunset('jsApiParameters',$jsApiParameters);
					}else{
						$data['msg']="支付失败，请重新支付！";
						$data['url']='index.php?c=com';
						$this->yunset("layer",$data);
					}
				}
			}else{
				$data['msg']="参数不正确，请正确填写！";
				$data['url']=$_SERVER['HTTP_REFERER'];
				$this->yunset("layer",$data);
			}

			$this->yunset('id',(int)$_GET['id']);
			$this->waptpl('wxpay');
		}else{
			$data['msg']="参数不正确，请正确填写！";
			$data['url']=$_SERVER['HTTP_REFERER'];
			$this->yunset("layer",$data);
			$backurl=Url('wap',array(),'member');
			$this->yunset('backurl',$backurl);
			$this->get_user();
			$this->waptpl('pay');
		}
	}
	function paybank_action(){
		
  			if($_POST['bank_name']==""){
				$data['msg']="请填写汇款银行！";
				$data['url']=$_SERVER['HTTP_REFERER'];
				$this->yunset("layer",$data);
			}elseif($_POST['bank_number']==""){
				$data['msg']="请填写汇入账号！";
				$data['url']=$_SERVER['HTTP_REFERER'];
				$this->yunset("layer",$data);
			}elseif($_POST['bank_price']==""){
				$data['msg']="请填写汇款金额！";
				$data['url']=$_SERVER['HTTP_REFERER'];
				$this->yunset("layer",$data);
			}elseif($_POST['bank_time']==""){
				$data['msg']="请填写汇款时间！";
				$data['url']=$_SERVER['HTTP_REFERER'];
				$this->yunset("layer",$data);
			}
			
			$id=intval($_GET['id']);
			$orderbank=$_POST['bank_name'].'@%'.$_POST['bank_number'].'@%'.$_POST['bank_price'];
			if($_POST['bank_time']){
				$banktime=strtotime($_POST['bank_time']);
			}else{
				$banktime="";
			}
			
			if($_POST['preview']){

				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/order/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);

				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
 				}else{
					$_POST['order_pic']=str_replace(APP_PATH."/data/upload/order/","./data/upload/order/",$pic);
				}
			} 
			
			if($id){
				$order=$this->obj->DB_select_once("company_order","`id`='".$id."' and `uid`='".$this->uid."'");
 				
 				if($order['id']){
				 	$company_order="`order_type`='bank',`order_state`='3',`order_remark`='".$_POST['remark']."',`order_pic`='".$_POST['order_pic']."',`order_bank`='".$orderbank."',`bank_time`='".$banktime."'";

					$this->obj->DB_update_all("company_order",$company_order,"`order_id`='".$order['order_id']."'");
					$data['msg']="操作成功，请等待管理员审核！";
					$data['url']="index.php?c=paylog";
					$this->yunset("layer",$data);
				}else{
					$data['msg']="非法操作！";
					$data['url']=$_SERVER['HTTP_REFERER'];
					$this->yunset("layer",$data);
				}
			}else{
				
				$statis = $this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'","`integral`");
				
				if($_POST['price']){
					if($_POST['comvip']){
						$comvip=(int)$_POST['comvip'];
						
						$ratinginfo =  $this->obj->DB_select_once("company_rating","`id`='".$comvip."'");
						
						$dkjf=(int)$_POST['dkjf'];
						
						if($dkjf >= (int)$statis['integral']){
							$dkjf = $statis['integral'];
						}
						
						if($dkjf){
							$price_ = $dkjf / $this->config['integral_proportion'];
						}else{
							$price_ = 0;
						}
						
						if($ratinginfo['time_start']<time() && $ratinginfo['time_end']>time()){
							$price = $ratinginfo['yh_price'] - $price_ ;
						}else{
							$price = $ratinginfo['service_price'] - $price_;
						}
						 
						$data['type']='1';
					}elseif($_POST['comservice']){

						$id=(int)$_POST['comservice'];
				
						$dkjf=(int)$_POST['dkjf'];
						
						if($dkjf >= (int)$statis['integral']){
							$dkjf = (int)$statis['integral'];
						}
						
						if($dkjf){
							$price_ = $dkjf / $this->config['integral_proportion'];
						}else{
							$price_ = 0;
						}
						
						$price=$_POST['price'] - $price_ ;
		
						$data['type']='5';
								
					}elseif($_POST['price_int']){
					    if($this->config['integral_min_recharge'] && $_POST['price_int']<$this->config['integral_min_recharge']){
					        $data['msg']="充值不得低于".$this->config['integral_min_recharge'];
					        $data['url']=$_SERVER['HTTP_REFERER'];
					        $this->yunset("layer",$data);
					        $this->waptpl('pay');exit;
					    }
					    $integralid=intval($_POST['integralid']);
					    $CacheMclass=$this->MODEL('cache')->GetCache(array('integralclass'));
					    $discount=$CacheMclass['integralclass_discount'][$integralid]/100;
					    if($integralid&&$discount>0){
					        $price =  $_POST['price_int']/$this->config['integral_proportion']*$discount;
					    }else{
					        $price = $_POST['price_int']/$this->config['integral_proportion'];
					    }
					    $price=floor($price*100)/100;

						if($_POST['coupon']!=''){
							$coupon=$this->obj->DB_select_once("coupon_list","`id`='".$_POST['coupon']."' and `uid`='".$this->uid."' and `validity`>'".time()."' and `coupon_scope`<='".$price."' and `status`='1'");
							if($coupon['id']){
								$price=$price-$coupon['coupon_amount'];
 								$this->obj->DB_update_all("coupon_list","`status`='2',`xf_time`='".time()."'","`id`='".$coupon['id']."' and `uid`='".$this->uid."'");
							}
						}
					    $data['type']='2';
					}
					$dingdan=mktime().rand(10000,99999);
					$data['order_id']=$dingdan;
					$data['order_dkjf']=$dkjf;
					$data['order_price']=$price;
					$data['order_time']=mktime();
					$data['order_state']="3";
					$data['order_type']="bank";
					$data['order_remark']=trim($_POST['remark']);
					$data['order_pic']=$_POST['order_pic'];
					$data['order_bank']=$orderbank;
					$data['bank_time']=$banktime;
					$data['uid']=$this->uid;
					$data['rating']=$_POST['comvip']?$_POST['comvip']:$_POST['comservice'];
					$data['integral']=$_POST['price_int'];
					
					
					$id=$this->obj->insert_into("company_order",$data);
					if($id){
						if($_POST['comservice']){
							$this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买增值包",true,2,'integral',11);
							$this->obj->member_log("购买增值服务,订单ID".$dingdan,88);
						}else if($_POST['comvip']){
							$this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买会员",true,2,'integral',27);
							$this->obj->member_log("购买会员,订单ID".$dingdan,88);
						}else if($_POST['price_int']){
							$this->obj->member_log("充值积分,订单ID".$dingdan,88);
						}
						
						$this->obj->DB_update_all("user_log","`status`=3,`orderid`='".$dingdan."'","`id`='".$_POST['logid']."'");
						$this->cookie->SetCookie("delay","",time() + 1);

						$data['msg']="操作成功，请等待管理员审核！";
						$data['url']="index.php?c=paylog";
						$this->yunset("layer",$data);
					}else{
						$this->obj->DB_update_all("user_log","`status`=2","`id`='".$_POST['logid']."'");
						$data['msg']="提交失败，请重新提交订单！";
						$data['url']=$_SERVER['HTTP_REFERER'];
					}
				}else{
					$data['msg']="参数不正确，请正确填写！";
					$data['url']=$_SERVER['HTTP_REFERER'];
				}
			}
		
			$this->yunset("layer",$data);
			
			$backurl=Url('wap',array(),'member');
			$this->yunset('backurl',$backurl);
			$this->get_user();
			$this->waptpl('payment');
	}
	function look_job_action(){
		$urlarr['c']='look_job';
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("look_job","`com_id`='".$this->uid."' and `com_status`='0' order by datetime desc",$pageurl,"10");
		if(is_array($rows))
		{
			foreach($rows as $v)
			{
				$uid[]=$v['uid'];
				$jobid[]=$v['jobid'];
			}
			$cjob=$this->obj->DB_select_all("company_job","`id`in(".@implode(',',$jobid).")","`name`,`id`");
			$resume=$this->obj->DB_select_all("resume","`uid` in (".pylode(",",$uid).")","`uid`,`name`,`edu`,`exp`,`sex`,`def_job` as `eid`");
			$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uid).")","uid");
			include(PLUS_PATH."user.cache.php");
			include(PLUS_PATH."job.cache.php");
			include(CONFIG_PATH."db.data.php");
			unset($arr_data['sex'][3]);
			$this->yunset("arr_data",$arr_data);
			foreach($resume as $val){
				$eid[]=$val['eid'];
			}
			$expect=$this->obj->DB_select_all("resume_expect","`id` in (".pylode(",",$eid).")","`id`,`uid`,`salary`,job_classid");
			foreach($rows as $key=>$val)
			{
				foreach($expect as $v){
					if($val['uid']==$v['uid']){
						$rows[$key]['resume_id']=$v['id'];
						$rows[$key]['salary']=$userclass_name[$v['salary']];
						if($v['job_classid']!=""){
							$job_classid=@explode(",",$v['job_classid']);
							$rows[$key]['jobname']=$job_name[$job_classid[0]];
						}
					}
				}
				foreach($resume as $va)
				{
					if($val['uid']==$va['uid'])
					{
						$rows[$key]['sex']=$arr_data['sex'][$va['sex']];
						$rows[$key]['exp']=$userclass_name[$va['exp']];
						$rows[$key]['edu']=$userclass_name[$va['edu']];
						$rows[$key]['name']=$va['name'];
					}
				}
				foreach($userid_msg as $va)
				{
					if($val['uid']==$va['uid'])
					{
						$rows[$key]['userid_msg']=1;
					}
				}
				foreach($cjob as $va)
				{
					if($val['jobid']==$va['id'])
					{
						$rows[$key]['comjob']=$va['name'];
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$this->yunset("js_def",5);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"谁看过我");
		$this->get_user();
		$this->waptpl('look_job');
	}
	function lookresumedel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_update_all("look_resume","`com_status`='1'","`id`='".(int)$_GET['id']."' and `com_id`='".$this->uid."'");
			if($nid){
			    $this->obj->member_log("删除已浏览简历记录（ID:".(int)$_GET['id']."）",26,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function lookjobdel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_update_all("look_job","`com_status`='1'","`id`='".(int)$_GET['id']."' and `com_id`='".$this->uid."'");
			if($nid){
			    $this->obj->member_log("删除已浏览简历记录（ID:".(int)$_GET['id']."）",26,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}

	function look_resume_action(){
		$urlarr['c']='look_resume';
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("look_resume","`com_id`='".$this->uid."' and `com_status`='0' order by datetime desc",$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $v){
				$resume_id[]=$v['resume_id'];
				$uid[]=$v['uid'];
			}
			$resume=$this->obj->DB_select_alls("resume","resume_expect","a.uid=b.uid and b.`id` in (".pylode(",",$resume_id).")","a.`name`,a.`sex`,a.`exp`,a.`edu`,a.`birthday`,b.`id`,b.job_classid,b.`salary`");
			$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uid).")","uid");
			if(is_array($resume)){
				include(PLUS_PATH."user.cache.php");
				include(PLUS_PATH."job.cache.php");
				include(CONFIG_PATH."db.data.php");
				unset($arr_data['sex'][3]);
				$this->yunset("arr_data",$arr_data);
				$age=date("Y",time());
				$time=date("Y",0);
				foreach($rows as $key=>$val){
					foreach($resume as $va){
						if($val['resume_id']==$va['id']){
							$rows[$key]['name']=$va['name'];
							$rows[$key]['salary']=$userclass_name[$va['salary']];
							$rows[$key]['birthday']=$va['birthday'];
							$rows[$key]['sex']=$arr_data['sex'][$va['sex']];
							$rows[$key]['exp']=$userclass_name[$va['exp']];
							$rows[$key]['edu']=$userclass_name[$va['edu']];
							if($va['job_classid']!=""){
								$job_classid=@explode(",",$va['job_classid']);
								$rows[$key]['jobname']=$job_name[$job_classid[0]];
							}
						}
					}
					foreach($userid_msg as $va){
						if($va['uid']&&$val['uid']&&$val['uid']==$va['uid']){
							$rows[$key]['userid_msg']=1;
						}
					}
				}
			}
		}
		$this->yunset("age",$age);
		$this->yunset("time",$time);
		$this->yunset("rows",$rows);
		$this->yunset("js_def",5);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"浏览简历");
		$this->get_user();
		$this->waptpl('look_resume');
	}
	

	function talent_pool_remark_action()
	{
		if($_POST['remark']=="")
		{
			$this->ACT_layer_msg("备注内容不能为空！",8,$_SERVER['HTTP_REFERER']);
		}else{
			$nid=$this->obj->DB_update_all("talent_pool","`remark`='".$_POST['remark']."'","`id`='".(int)$_POST['id']."' and `cuid`='".$this->uid."'");
			if($nid){

			    $this->obj->member_log("收藏人才备注".$_POST['r_name'],5,1);
				
				$data['msg']="备注成功！";
				$data['url']=$this->config['sy_weburl'].'/wap/member/index.php?c=talent_pool';
			}else{
				
				$data['msg']="备注失败！";
				$data['url']=$this->config['sy_weburl'].'/wap/member/index.php?c=talent_pool';
			}
		}
		$this->yunset("layer",$data);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		
		$this->get_user();
		$this->waptpl('talent_pool');
	}
	function talentpooldel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("talent_pool","`id`='".(int)$_GET['id']."' and `cuid`='".$this->uid."'");
			if($nid){
			    $this->obj->member_log("删除收藏简历人才（ID:".(int)$_GET['id']."）",5,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function talent_pool_action(){
		$where="`cuid`='".$this->uid."'";
		$urlarr['c']='talent_pool';
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("talent_pool",$where."  order by id desc",$pageurl,"10");
		if(is_array($rows)) {
			foreach($rows as $v) {
				$uid[]=$v['uid'];
				$eid[]=$v['eid'];
			}
			$resume=$this->obj->DB_select_alls("resume","resume_expect","a.uid=b.uid and a.`r_status`<>'2' and a.uid in (".pylode(',',$uid).")","a.`name`,a.`uid`,a.`sex`,a.`birthday`,b.`edu`,a.`exp`,b.`job_classid`,b.id as eid,b.salary");
			$user=$this->obj->DB_select_all("resume","`birthday`limit 2");

			$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uid).")","uid");
			if(is_array($resume)) {
				include(PLUS_PATH."user.cache.php");
				include(PLUS_PATH."job.cache.php");
				include(CONFIG_PATH."db.data.php");
				unset($arr_data['sex'][3]);
				$this->yunset("arr_data",$arr_data);
				$age=date("Y",time());
				$time=date("Y",0);
				foreach($rows as $key=>$val) {
					foreach($resume as $va) {
						if($val['uid']==$va['uid'])
						{
							$rows[$key]['birthday']=$va['birthday'];
							$rows[$key]['eid']=$va['eid'];
							$rows[$key]['name']=$va['name'];
							$rows[$key]['sex']=$arr_data['sex'][$va['sex']];
							$rows[$key]['exp']=$userclass_name[$va['exp']];
							$rows[$key]['edu']=$userclass_name[$va['edu']];
							if($va['job_classid']!="")
							{
								$job_classid=@explode(",",$va['job_classid']);
								$rows[$key]['jobname']=$job_name[$job_classid[0]];
							}
						}
					}
					foreach($user as $value){
						if($val['uid']==$value['uid']){
							$rows[$key]['age']=$user['age'];
						}
					}
					foreach($userid_msg as $va)
					{
						if($val['uid']==$va['uid'])
						{
							$rows[$key]['userid_msg']=1;
						}
					}
				}
			}
		}
		$this->yunset("age",$age);
		$this->yunset("time",$time);
		$this->yunset("rows",$rows);
		$this->company_satic();
		$this->yunset("js_def",5);
		if($_GET['type']){
			$backurl=Url('wap',array(),'member');
		}else{
			$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		}
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"收藏人才");
		$this->get_user();
		$this->waptpl('talent_pool');
	}
	function atn_teacher_action(){
		if($_GET['del']){
			$id=$this->obj->DB_delete_all("atn","`id`='".$_GET['del']."' AND `uid`='".$this->uid."'");
			$this->obj->DB_update_all("px_teacher","`ant_num`=`ant_num`-1","`id`='".$_GET['tid']."'");
			if($id){
				$this->waplayer_msg('取消成功！');
			}else{
				$this->waplayer_msg('取消失败！');
			}
		}
		$urlarr=array("c"=>"atn_teacher","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("atn","`uid`='".$this->uid."' and `tid`<>'' and `sc_usertype`='4' order by `id` desc",$pageurl,"20");
		if($rows&&is_array($rows)){
			foreach($rows as $val){
				$tids[]=$val['tid'];
			}
			$tids=array_unique($tids);
			$teacher=$this->obj->DB_select_all("px_teacher","`id` in(".pylode(',',$tids).") and `status`=1 and `r_status`<>2","`name`,`id`,`pic`");
			$where=1;
			foreach ($tids as $k=>$v){
				if($k==0){
					$where1=" and (FIND_IN_SET('".$v."',`teachid`)";
				}else{
					$where1.=" or FIND_IN_SET('".$v."',`teachid`)";
				}
			}
			$where1.=")";
			$subject=$this->obj->DB_select_all("px_subject",$where.$where1." and `status`=1 and `r_status`<>2","`uid`,`name`,`id`,`teachid`");
			foreach($subject as $v){
				$url=Url('wap',array("c"=>"train",'a'=>'subshow',"id"=>$v['id']));
				$teachids=explode(',', $v['teachid']);
				if (!empty($teachids)){
					if (count($teachids)>1){
						foreach ($teachids as $val){
							$sname[$val][]="<a href='".$url."'>".$v['name']."</a>";
						}
					}else{
						$sname[$v['teachid']][]="<a href='".$url."'>".$v['name']."</a>";
					}
				}
			}

			foreach ($teacher as $v){
				$teacherids[]=$v['id'];
			}
			foreach ($rows as $key=>$val){
				if(!in_array($val['tid'], $teacherids)){
					unset($rows[$key]);
				}
			}
			foreach($rows as $key=>$val){
				foreach($teacher as $v){
					if($val['tid']==$v['id']){
						$rows[$key]['teacher']=$v['name'];
						if($v['pic']){
							$rows[$key]['pic']=$v['pic'];
						}else{
							$rows[$key]['pic']=$this->config['sy_pxteacher_icon'];
						}
					}
				}
				foreach($sname as $k=>$v){
					if($val['tid']==$k){
						$rows[$key]['snum']=count($v);
						$i=0;
						foreach($v as $value){
							if($i<2){
								$slist[$key][]=$value;
							}
							$i++;
						}
						$rows[$key]['sname']=@implode(",",$slist[$key]);
					}
				}
			}
		}
		$this->yunset("js_def",7);
		$this->yunset("rows", $rows);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职业培训");
		$this->get_user();
		$this->waptpl('atn_teacher');
	}
	function fav_subject_action(){
		$urlarr=array("c"=>"fav_subject","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_subject_collect","`uid`='".$this->uid."' order by id desc",$pageurl,"10");

		if($rows&&is_array($rows)){
			foreach($rows as $val){
				$sid[]=$val['sid'];
				$s_uid[]=$val['s_uid'];
			}
			$train=$this->obj->DB_select_all("px_train","`uid` in(".pylode(',',$s_uid).")","`uid`,`name`");
			$subject=$this->obj->DB_select_all("px_subject","`id` in(".pylode(',',$sid).")","`id`,`name`,`address`,`pic`");
			foreach($rows as $key=>$val){
				foreach($subject as $v){
					if($val['sid']==$v['id']){
						$rows[$key]['name']=$v['name'];
						$rows[$key]['address']=$v['address'];
						if($v['pic']){
							$rows[$key]['pic']=$v['pic'];
						}else{
							$rows[$key]['pic']=$this->config['sy_pxsubject_icon'];
						}
					}
				}
				foreach($train as $v){
					if($val['s_uid']==$v['uid']){
						$rows[$key]['train_name']=$v['name'];
					}
				}

			}
		}
		$this->yunset("js_def",7);
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职业培训");
		$this->get_user();
		$this->waptpl('fav_subject');
	}

	function fav_subjectdel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("px_subject_collect","`id`='".(int)$_GET['id']."'"," ");
			if($nid){
			    $this->obj->member_log("删除已收藏的课程（ID:".(int)$_GET['id']."）",5,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}

	function baoming_subject_action(){
		if($_GET['del']){
			$del=(int)$_GET['del'];
			$nid=$this->obj->DB_delete_all("px_baoming","`id`='".$del."' and `uid`='".$this->uid."'");
			$this->obj->DB_delete_all("company_order","`sid`='".$del."' and `uid`='".$this->uid."'");
			if($nid){
				$this->waplayer_msg('取消成功！');
			}else{
				$this->waplayer_msg('取消失败！');
			}
		}
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"baoming_subject","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_baoming","`uid`='".$this->uid."' order by id desc",$pageurl,"10");

		if($rows&&is_array($rows)){
			foreach($rows as $val){
				$sid[]=$val['sid'];
				$s_uid[]=$val['s_uid'];
				$ids[]=$val['id'];
			}
			$subject=$this->obj->DB_select_all("px_subject","`id` in(".pylode(',',$sid).")","`id`,`name`,`pic`,`price`,`isprice`");
			$train=$this->obj->DB_select_all("px_train","`uid` in(".pylode(',',$s_uid).")","`uid`,`name`");
			$order=$this->obj->DB_select_all("company_order","`sid` in(".pylode(',',$ids).") and `type`=6","`id`,`sid`,`order_state`");
			foreach($rows as $key=>$val){
				foreach($subject as $v){
					if($val['sid']==$v['id']){
						$rows[$key]['name']=$v['name'];
						$rows[$key]['price']=floatval($v['price']);
						$rows[$key]['isprice']=$v['isprice'];
						if($v['pic']){
							$rows[$key]['pic']=$v['pic'];
						}else{
							$rows[$key]['pic']=$this->config['sy_pxsubject_icon'];
						}
					}
				}
				foreach($train as $v){
					if($val['s_uid']==$v['uid']){
						$rows[$key]['train_name']=$v['name'];
					}
				}
				foreach($order as $v){
					if($val['id']==$v['sid']){
						$rows[$key]['order_state']=$v['order_state'];
						$rows[$key]['oid']=$v['id'];
					}
				}
			}
		}
		$this->yunset("js_def",7);
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职业培训");
		$this->get_user();
		$this->waptpl('baoming_subject');

	}
	
	function subject_zixun_action(){
		$urlarr=array("c"=>"subject_zixun","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("px_zixun","`uid`='".$this->uid."' order by id desc",$pageurl,"10");

		if($rows&&is_array($rows)){
			foreach($rows as $val){
				$sid[]=$val['s_uid'];
			}
			$train=$this->obj->DB_select_all("px_train","`uid` in(".pylode(',',$sid).")","`uid`,`name`,`logo`");
			$sub=$this->obj->DB_select_all("px_subject","`uid` in(".pylode(',',$sid).")  and `status`=1 and `r_status`<>2","`id`,`uid`,`name`");
			
			foreach($sub as $v){
				$url=Url('train',array("c"=>"subshow","id"=>$v['id']));
				$subname[$v['uid']][]="<a href='".$url."' target='_bank'>".$v['name']."</a>";
			}
			
			foreach($rows as $key=>$val){
				foreach($train as $v){
					if($val['s_uid']==$v['uid']){
						$rows[$key]['name']=$v['name'];
						if($v['logo']){
							$rows[$key]['logo']=$v['logo'];
						}else{
							$rows[$key]['logo']=$this->config['sy_px_icon'];
						}
					}
				}
				foreach($subname as $k=>$v){
					if($val['s_uid']==$k){
						$rows[$key]['num']=count($v);
						$i=0;
						foreach($v as $value){
							if($i<2){
								$sublist[$key][]=$value;
							}
							$i++;
						}
						$rows[$key]['subname']=@implode(",",$sublist[$key]);
					}
				}
			}
		}
		$this->yunset("js_def",7);
		$this->yunset("rows",$rows);
		
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职业培训");
		$this->get_user();
		$this->waptpl('subject_zixun');

	}
	
	function subject_zixundel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("px_zixun","`id`='".(int)$_GET['id']."'"," ");
			if($nid){
			    $this->obj->member_log("删除培训留言（ID:".(int)$_GET['id']."）",18,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	
	function invite_action(){
		$urlarr['c']='invite';
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("userid_msg"," `fid`='".$this->uid."' order by id desc",$pageurl,"10");
		if(is_array($rows) && !empty($rows)){
			foreach($rows as $v){
				$uid[]=$v['uid'];
			}
			$resume=$this->obj->DB_select_all("resume","`uid` in (".pylode(",",$uid).") and `r_status`<>'2'","`uid`,`name`,`exp`,`sex`,`edu`,`def_job` as `eid`");
			foreach($resume as $val){
				$eid[]=$val['eid'];
			}
			$expect=$this->obj->DB_select_all("resume_expect","`id` in (".pylode(",",$eid).")","`salary`,`id`,`job_classid`");
			if(is_array($resume)){
				$user=array();
				include(PLUS_PATH."user.cache.php");
				include(PLUS_PATH."job.cache.php");
				include(CONFIG_PATH."db.data.php");
				unset($arr_data['sex'][3]);
				$this->yunset("arr_data",$arr_data);
				foreach($resume as $val){
					foreach($expect as $v){
						if($v['id']==$val['eid']){
							$user[$val['uid']]['salary']=$userclass_name[$v['salary']];
							if($v['job_classid']!=""){
								$job_classid=@explode(",",$v['job_classid']);
								$user[$val['uid']]['jobname']=$job_name[$job_classid[0]];
							}
						}
					}

					$user[$val['uid']]['eid']=$val['eid'];
					$user[$val['uid']]['name']=mb_substr($val['name'],0,8);
					$user[$val['uid']]['exp']=$userclass_name[$val['exp']];
					$user[$val['uid']]['edu']=$userclass_name[$val['edu']];
					$user[$val['uid']]['sex']=$arr_data['sex'][$val['sex']];
				}
			}

			$this->yunset("user",$user);
		}
		$this->yunset("rows",$rows);
		$this->yunset("js_def",5);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"面试邀请");
		$this->get_user();
		$this->waptpl('invite');
	}
	
	function invite_del_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("userid_msg","`id`='".(int)$_GET['id']."' and `fid`='".$this->uid."'");
			if($nid){
			    $this->obj->member_log("删除已邀请面试的人才",4,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function part_action(){
		$this->rightinfo();
		$urlarr=array("c"=>"part","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("partjob","`uid`='".$this->uid."'",$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$rows[$k]['applynum']=$this->obj->DB_select_num("part_apply","`jobid`='".$v['id']."'");
			}
		}
		$this->company_satic();
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"兼职管理");
		$this->get_user();
		$this->waptpl('part');
	}
	function partadd_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$statics=$this->company_satic();
		if($_GET['id']){
			$row=$this->obj->DB_select_once("partjob","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."'");
			$row['work']=$row['worktime'];
			$row['worktime']=explode(',', $row['worktime']);
			$arr_data1=$arr_data['sex'][$row['sex']];
			$this->yunset("arr_data1",$arr_data1);
			$row['content_t']=strip_tags($row['content']);
			$this->yunset("row",$row);
		}else{
			if($statics['addpartjobnum']==0){ 
				$data['msg']="您的会员已到期！";
				$data['url']='index.php?c=rating';
			}
			if($statics['addpartjobnum']==2){ 
				if($this->config['integral_partjob']!='0'){
					$data['msg']="您的套餐已用完！";
					$data['url']='index.php?c=rating';
				}else{
					$this->obj->DB_update_all("company_statis","`part_num` = '1'","`uid`='".$this->uid."'");
				}
			}
		}


		if($_POST['submit']){

 			$_POST['content']=str_replace(array("&amp;","background-color:#ffffff","background-color:#fff","white-space:nowrap;"),array("&",'background-color:','background-color:','white-space:'),$_POST['content']);
			$_POST['sdate']=strtotime($_POST['sdate']);
			
			if($_POST['timetype']!='1'){
				$_POST['edate']="";
				$_POST['deadline']="";
			}else{
				$_POST['edate']=strtotime($_POST['edate']);
				$_POST['deadline']=strtotime($_POST['deadline']);
			}
			$_POST['state'] = $this->config['com_partjob_status'];
			$id=(int)$_POST['id'];
			$logid=(int)$_POST['logid'];
			unset($_POST['submit']);
			unset($_POST['id']);
			unset($_POST['logid']);

			if(!$id){
				$_POST['addtime'] = time();
				$_POST['uid'] = $this->uid;
				$_POST['lastupdate'] = time();
				$data['msg']=$this->get_com(7);
				if($data['msg']==''){
					$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
					$_POST['com_name']=$company['name'];

					
					if(!isset($_POST['state']) || ($_POST['state'] != 0 && $this->usertype == 2)){
						$member = $this->obj->DB_select_once("member", "`uid`='{$this->uid}'", "`status`");
						if($member['status'] != 1){
							$_POST['state'] = 0;
						}
					}

					$nid=$this->obj->insert_into("partjob",$_POST);
					$name="添加兼职职位";
					if($nid){
						$state_content = "新发布了兼职职位 <a href=\"".$this->config['sy_weburl']."/part/index.php?c=show&id=$nid\" target=\"_blank\">".$_POST['name']."</a>。";
						$this->addstate($state_content,2);
						$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$logid."'");
						$data['msg']=$name."成功！";
					}else{
						$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$logid."'");
						$data['msg']=$name."失败！";
					}
				}
			}else{
				$job=$this->obj->DB_select_once("partjob","`id`='".$id."' and `uid`='".$this->uid."'","state");
				if($job['state']=="1" || $job['state']=="2"){
					$data['msg']="";
				}
				if($data['msg']==''){
					$where['id']=$id;
					$where['uid']=$this->uid;
					$nid=$this->obj->update_once("partjob",$_POST,$where);
					$name="更新兼职职位";
					if($nid){
						$this->obj->DB_update_all("user_log","`status`='1'","`id`='".$logid."'");
						$data['msg']=$name."成功！";
					}else{
						$this->obj->DB_update_all("user_log","`status`='2'","`id`='".$logid."'");
						$data['msg']=$name."失败！";
					}
				}
			}
			$data['url']='index.php?c=part';
			echo json_encode($data);die;
		}
		$this->rightinfo();
		$this->yunset("layer",$data);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','part')));
		$morning=array('0101','0201','0301','0401','0501','0601','0701');
		$noon=array('0102','0202','0302','0402','0502','0602','0702');
		$afternoon=array('0103','0203','0303','0403','0503','0603','0703');
		$this->yunset(array('morning'=>$morning,'noon'=>$noon,'afternoon'=>$afternoon));
		$this->yunset("today",date("Y-m-d"));
		$this->yunset('header_title',"发布兼职");
		$this->get_user();
		$this->waptpl('partadd');
	}
	function partdel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("partjob","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
			if($nid){
				$this->obj->DB_delete_all("part_collect","`jobid`='".(int)$_GET['id']."'","");
				$this->obj->DB_delete_all("part_apply","`jobid`='".(int)$_GET['id']."'","");
				$this->obj->member_log("删除兼职",9,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function photo_action(){
		if($_POST['submit']){
 			$pic=$this->wap_up_pic($_POST['uimage'],'company');
			if($pic['errormsg']){echo 4;die;}
			if($pic['re']){
				$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`logo`");
				if(!$company['logo']){
					$this->MODEL('integral')->get_integral_action($this->uid,"integral_avatar","上传LOGO");
				}
				unlink_pic(APP_PATH.$company['logo']);
				$photo="./data/upload/company/".date('Ymd')."/".$pic['new_file'];
				if($this->config['com_logo_status']=='1'){
					$this->obj->DB_update_all("company","`logo`='".$photo."',`logo_status`='1'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_job","`com_logo`=''","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("answer","`pic`=''","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("question","`pic`=''","`uid`='".$this->uid."'");
					echo 3;die;
				}else{
					$this->obj->DB_update_all("company","`logo`='".$photo."',`logo_status`='0'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_job","`com_logo`='".$photo."'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("answer","`pic`='".$photo."'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("question","`pic`='".$photo."'","`uid`='".$this->uid."'");
					echo 1;die;
				}
				
			}else{
				unlink_pic(APP_PATH."data/upload/company/".date('Ymd')."/".$pic['new_file']);
				echo 2;die;
			}
		}else{
			$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`logo`");
			
			if(!$company['logo'] || !file_exists(str_replace('./',APP_PATH,$company['logo']))){
				$company['logo']=$this->config['sy_weburl']."/".$this->config['sy_unit_icon'];
			}else{
				$company['logo']="";
			}
			$this->yunset("company",$company);
			if($_GET['t']){
				$backurl=Url('wap',array(),'member');
			}else if($_GET['type']){
				$backurl=Url('wap',array('c'=>'integral'),'member');
			}else{
				$backurl=Url('wap',array('c'=>'info'),'member');
			}
			$this->yunset('backurl',$backurl);
			
			$this->yunset('header_title',"企业LOGO");
			
			$this->get_user();
			$this->waptpl('photo');
		}
	}

	function comcert_action(){
		if($_POST['submit']){
			$comname=$this->obj->DB_select_num('company',"`uid`<>'".$this->uid."' and `name`='".$_POST['name']."'","`uid`");
            $row=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='3'");
            if($_POST['name']==""){
				$data['msg']='企业全称不能为空！';
			}elseif($comname){
				$data['msg']='企业全称已存在！';
			}elseif(!$_POST['preview']&&!$row['check']){
				$data['msg']='请上传营业执照！';
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
			if($data['msg']==""){
				if($this->config['com_cert_status']=="1"){
					$sql['status']=0;
				}else{
					$sql['status']=1;
				}
				$this->obj->DB_update_all("company","`name`='".$_POST['name']."',`yyzz_status`='".$sql['status']."'","`uid`='".$this->uid."'");
				$sql['step']=1;
				$sql['check']=$photo;
				$sql['check2']="0";
				$sql['ctime']=mktime();
				$company=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."'  and type='3'","`check`");
				if(is_array($company)){
					$where['uid']=$this->uid;
					$where['type']='3';
					$this->obj->update_once("company_cert",$sql,$where);
					$this->obj->member_log("更新营业执照",13,2);
				}else{
					$sql['uid']=$this->uid;
					$sql['did']=$this->userdid;
					$sql['type']=3;
					$this->obj->insert_into("company_cert",$sql);
					$this->obj->member_log("上传营业执照",13,1);
					if($this->config['com_cert_status']!="1"){
						$this->MODEL('integral')->get_integral_action($this->uid,"integral_comcert","认证营业执照");
					}
				}
				$this->obj->DB_update_all("user_log","`status`='13'","`id`='".$_POST['logid']."'");

				$data['msg']='上传营业执照成功！';
				$data['url']='index.php?c=set';
			}else{
				$data['msg']=$data['msg'];
				$data['url']='index.php?c=comcert';
			}
		}
		$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`name`");
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `type`='3'");
		if($cert['check']){
		    $cert['old_check']=str_replace('./data','/data',$cert['check']);
		}
		$this->yunset("company",$company);
		$this->yunset("cert",$cert);
		$this->yunset("layer",$data);
		
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		
		$this->yunset('header_title',"营业执照");
		$this->get_user();
		$this->waptpl('comcert');
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
					$this->obj->DB_update_all("company","`linktel`='".$row['check']."',`moblie_status`='1'","`uid`='".$this->uid."'");
					$this->obj->DB_update_all("company_cert","`status`='1'","`uid`='".$this->uid."' and `check2`='".$_POST['code']."'");
					$this->obj->member_log("手机绑定",13,1);
					$this->obj->DB_update_all("user_log","`status`='11'","`id`='".$_POST['logid']."'");
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
			if($_GET['type']=="moblie")
			{
				$this->obj->DB_update_all("company","`moblie_status`='0'","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="email")
			{
				$this->obj->DB_update_all("company","`email_status`='0'","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="wxid")
			{
				$this->obj->DB_update_all("member","`wxid`='',`wxopenid`='',`unionid`=''","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="qqid")
			{
				$this->obj->DB_update_all("member","`qqid`='',`qqunionid`=''","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="sinaid")
			{
				$this->obj->DB_update_all("member","`sinaid`=''","`uid`='".$this->uid."'");
			}
			$this->waplayer_msg('解除绑定成功！');
		}
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);
		$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		$this->yunset("company",$company);
		 
		if($company['yyzz_status']!=1){
			$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `type`='3'","`id`,`status`,`statusbody`");
			$this->yunset("cert",$cert);
		}

		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"社交账号绑定");
		$this->get_user();
		$this->waptpl('binding');
	}
	function bindingbox_action(){
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);
		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"账户绑定");
		$this->get_user();
		$this->waptpl('bindingbox');
	}
	function setname_action(){
		if($_POST['username']){
			$user=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
			if($user['restname']=="1"){
				echo "您无权修改账户！";die;
			}
			$username=$_POST['username'];
			$num = $this->obj->DB_select_num("member","`username`='".$username."'");
			if($num>0){
				echo "用户名已存在！";die;
			}
			if($this->config['sy_regname']!=""){
				$regname=@explode(",",$this->config['sy_regname']);
				if(in_array($username,$regname)){
					echo "该用户名禁止使用！";die;
				}
			}
			
			$oldpass = md5(md5($_POST['password']).$user['salt']);
			if($user['password']!=$oldpass){
				echo "密码错误！";die;
			}
			$data['username']=$username;
			$data['restname']=1;
			$this->obj->update_once('member',$data,array('uid'=>$this->uid));
			
			$this->cookie->unset_cookie();
			
			$this->obj->member_log("修改账户",11);
			echo 1;die;
		}
		$user=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		
		if($user['restname']=="1"){
			$data['msg']="您无权修改账户！";
			$data['url']='index.php?c=set';
			$this->yunset("layer",$data);
		}
		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"修改用户名");
		$this->get_user();
		$this->waptpl('setname');
	}

	function reward_list_action(){
		$urlarr['c']='reward_list';
		$urlarr["page"]="{{page}}";	
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("change","`uid`='".$this->uid."' order by id desc",$pageurl,"10");		
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
		
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'","rating_name,integral");
		$statis[integral]=number_format($statis[integral]);
		$this->yunset("statis",$statis);
		$this->yunset('rows',$rows);
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"兑换记录");
		$this->get_user();
		$this->waptpl('reward_list');
	}

	function delreward_action(){
		if($this->usertype!='2' || $this->uid==''){
			$this->waplayer_msg('登录超时！');
		}else{
			$rows=$this->obj->DB_select_once("change","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' ");
			if($rows['id']){
				$this->obj->DB_update_all("reward","`num`=`num`-".$rows['num'].",`stock`=`stock`+".$rows['num']."","`id`='".$rows['gid']."'");
				$this->MODEL('integral')->company_invtal($this->uid,$rows['integral'],true,"取消兑换",true,2,'integral',24);
				$this->obj->DB_delete_all("change","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' ");
			}
			$this->obj->member_log("取消兑换",17,3);
			$this->waplayer_msg('取消成功！');
		}
	}
	function paylog_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"paylog","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`uid`='".$this->uid."' order by order_time desc";
		$rows=$this->get_page("company_order",$where,$pageurl,"10");
		foreach($rows as $v){
			$ord[]=$v['order_id'];
		}
		$ords=@implode(',',$ord);
		$order=$this->obj->DB_select_all("invoice_record","`order_id` in(".$ords.") and `uid`='".$this->uid."'","`status`,`order_id`");
		if($rows&&is_array($rows)&&$this->config['sy_com_invoice']=='1'){
			$last_days=strtotime("-7 day");
			foreach($rows as $key=>$val){
				if($val['order_time']>=$last_days && $val['order_remark']!="使用充值卡"){
					$rows[$key]['invoice']='1';
				}
				foreach($order as $k=>$v){
					if($val['order_id']==$v['order_id']){
						$rows[$key]['status']=$v['status'];
					}
				}
			}
			$this->yunset("rows",$rows);
		}
		$this->yunset('header_title',"财务明细");
		$this->get_user();
		$this->waptpl('paylog');
	}

	function delpaylog_action(){
		if($this->usertype!='2' || $this->uid==''){
			$this->waplayer_msg('登录超时！');
		}else{
			$oid=$this->obj->DB_select_once("company_order","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' and `order_state`='1'","`id`,`order_id`");
			if(empty($oid)){
				$this->waplayer_msg('订单不存在！');
			}else{
				$this->obj->DB_update_all("user_log","`status`=4","`orderid`='".$oid['order_id']."' and `uid`='".$this->uid."'");
				$this->obj->DB_delete_all("company_order","`id`='".$oid['id']."' and `uid`='".$this->uid."'");
				$this->obj->DB_delete_all("invoice_record","`order_id`='".$oid['order_id']."'  and `uid`='".$this->uid."'");
				$this->waplayer_msg('取消成功！');
			}
		}
	}

	function consume_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"consume","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`com_id`='".$this->uid."'";
			
		$where.="  order by pay_time desc";
		$rows = $this->get_page("company_pay",$where,$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$rows[$k]['pay_time']=date("Y-m-d H:i:s",$v['pay_time']);
				$rows[$k]['order_price']=str_replace(".00","",$rows[$k]['order_price']);
			}
		}
		if ($_GET['type']==1){
			$this->yunset('backurl',Url('wap',array('c'=>'com'),'member'));
		}else{
			$backurl=Url('wap',array('c'=>'integral'),'member');			
		}
		$this->yunset('backurl',$backurl);
		$this->yunset("rows",$rows);
		$this->yunset('header_title',"财务明细");
		$this->get_user();
		$this->waptpl('consume');
	}

	function down_action(){
		$where="`comid`='".$this->uid."'";
		$urlarr['c']='down';
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("down_resume","$where order by id desc",$pageurl,"10");
		if(is_array($rows)&&$rows){
			if(empty($resume)){
				foreach($rows as $v){
					$uid[]=$v['uid'];
					$eid[]=$v['eid'];
				}
				$resume=$this->obj->DB_select_alls("resume","resume_expect","a.uid=b.uid and a.`r_status`<>'2' and a.uid in (".@implode(",",$uid).") and b.id in (".@implode(",",$eid).")","a.`name`,a.`uid`,a.`exp`,a.`sex`,a.`edu`,b.`id`,b.`minsalary`,b.`maxsalary`,b.`job_classid`,b.`height_status`");
			}
			$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uid).")","uid");
			if(is_array($resume)){
				include(PLUS_PATH."user.cache.php");
				include(PLUS_PATH."job.cache.php");
				include(CONFIG_PATH."db.data.php");
				unset($arr_data['sex'][3]);
				$this->yunset("arr_data",$arr_data);
				foreach($rows as $key=>$val){
					foreach($resume as $va){
						if($val['eid']==$va['id']){
							$rows[$key]['name']=$va['name'];
							$rows[$key]['sex']=$arr_data['sex'][$va['sex']];
							$rows[$key]['exp']=$userclass_name[$va['exp']];
							$rows[$key]['edu']=$userclass_name[$va['edu']];
							$rows[$key]['minsalary']=$va['minsalary'];
							$rows[$key]['maxsalary']=$va['maxsalary'];
							$rows[$key]['height_status']=$va['height_status'];
							if($va['job_classid']!=""){
								$job_classid=@explode(",",$va['job_classid']);
								$rows[$key]['jobname']=$job_name[$job_classid[0]];
							}
						}
					}
					foreach($userid_msg as $va){
						if($val['uid']==$va['uid']){
							$rows[$key]['userid_msg']=1;
						}
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"下载简历");
		$this->get_user();
		$this->waptpl('down');
	}
	function jobpack_action(){
		$this->company_satic();

		if($_GET['t']=='r'){
			$this->rewardjob();
		}else{
			$this->sharejob();
		}
	}
	function sharejob(){
		$urlarr=array("c"=>"jobpack","page"=>"{{page}}");
		$where="`uid`='".$this->uid."' ";
		
		
		$pageurl=Url('wap',$urlarr,'member');

		$rows=$this->get_page("company_job_share",$where,$pageurl,'10');

		if(is_array($rows) && !empty($rows)){
			$jobids=array();
			foreach($rows as $v){
				$jobids[]=$v['jobid'];
			}
			$joblist = $this->obj->DB_select_all("company_job","`uid`='".$this->uid."' AND `id` IN (".@implode(',',$jobids).")");

			
			$shareNum = $this->obj->DB_select_all("company_job_sharelog","`jobid` IN (".@implode(',',$jobids).") group by jobid","count(*) as num,jobid");
			
			
			foreach($rows as $k=>$v){
				
				$rows[$k]['nowprice']=sprintf("%.2f", $rows[$k]['packnum']*$rows[$k]['packmoney']);

				foreach($joblist as $val){
					if($v['jobid']==$val['id']){
						$rows[$k]['name']=$val['name'];
						$rows[$k]['status']=$val['status'];
						$rows[$k]['lastupdate']=$val['lastupdate'];
						
					}
				}

				foreach($shareNum as $val){
					if($v['jobid']==$val['jobid']){
						$rows[$k]['sharenum']=$val['num'];
						
					}
				}
				$rows[$k]['sharenum'] = $rows[$k]['sharenum']?$rows[$k]['sharenum']:0;

			}
		}
		
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
 		$this->yunset("backurl",$backurl);
 		
 		$this->yunset("header_title","赏金推广职位");
		$this->yunset("rows",$rows);
		$this->waptpl('jobshrelist');
	
	}
	function deljobpackreward_action(){
		if($_GET['id']){
			$packM = $this->MODEL('pack');
			$return = $packM->delrewardJob($this->uid,$_GET['id']);
			if($return['msg']){
				$this->waplayer_msg($return['msg']);
			}else{
				$this->waplayer_msg('悬赏职位取消成功！');
			}
		}else{
			$this->waplayer_msg('请选择正确的职位！');
		}
	}
	function downdel_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("down_resume","`id`='".(int)$_GET['id']."' and `comid`='".$this->uid."'"," ");
			if($nid){
			    $this->obj->member_log("删除已下载简历记录（ID:".(int)$_GET['id']."）",3,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function lt_jobadd_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$CacheList=$this->MODEL('cache')->GetCache(array('lt','lthy','ltjob','city','com','hy'));
		$this->yunset($CacheList);
		$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if($company['lastupdate']<1){
			$data['msg']="请先完善基本资料！";
			$data['url']='index.php?c=info';
		}
		$statics=$this->company_satic();
		
		if(!$_GET['id']){
			if($statics['addltjobnum']==0){ 
				$data['msg']="您的会员已到期！";
				$data['url']='index.php?c=rating';
			}
			if($statics['addltjobnum']==2){ 
				if($this->config['integral_lt_job']!='0'){
					$data['msg']="您的套餐已用完！";
					$data['url']='index.php?c=rating';
				}else{
					$this->obj->DB_update_all("company_statis","`lt_job_num` = '1'","`uid`='".$this->uid."'");
				}
			}
		}
		$this->rightinfo();

		$msg=array();
		$isallow_addjob="1";
		if($this->config['com_enforce_emailcert']=="1"){
			if($company['email_status']!="1"){
				$isallow_addjob="0";
				$msg[]="邮箱认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_mobilecert']=="1"){
			if($company['moblie_status']!="1"){
				$data['msg']="请先完成手机认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_licensecert']=="1"){
			if($company['yyzz_status']!="1"){
				$data['msg']="请先完成营业执照认证";
				$data['url']='index.php?c=set';
			}
		}
		if($this->config['com_enforce_setposition']=="1"){
			if(empty($company['x'])||empty($company['y'])){
				$isallow_addjob="0";
				$msg[]="设置企业地图";
				$data['url']="index.php?c=map";
			}
		}
		if($isallow_addjob=="0"){
			$data['msg']="请先完成".implode(",",$msg)."！";
		}else if($_GET['id']){
			$row=$this->obj->DB_select_once("lt_job","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
			$arr_data1=$arr_data['sex'][$row['sex']];
			$this->yunset("arr_data1",$arr_data1);
			if($row['id']){
			
				if($row['constitute']!=""){
					$row['constitutev'] = $row['constitute'];
					$row['constitute']=@explode(",",$row['constitute']);
				}
				if($row['welfare']!=""){
					$row['welfarev'] = $row['welfare'];
					$row['welfare']=@explode(",",$row['welfare']);
				}
				if($row['language']!=""){
					$row['languagev'] = $row['language'];
					$row['language']=@explode(",",$row['language']);
				}
			
				if($row['job']){
					$job=@explode(",",$row['job']);
					foreach ($job as $v){
						$jobname[]=$CacheList['ltjob_name'][$v];
					}
				}
				$jobname=@implode(",",$jobname);
				$this->yunset("jobname",$jobname);
				if($row['qw_hy']){
					$hy=@explode(",",$row['qw_hy']);
					foreach ($hy as $v){
						$hyname[]=$CacheList['lthy_name'][$v];
					}
				}
				$hyname=@implode(",",$hyname);
				$this->yunset("hyname",$hyname);
				$row['days']= ceil(($row['edate']-$row['sdate'])/86400);
				
				$row['job_desc_t'] = strip_tags($row['job_desc']);
				$row['eligible_t'] = strip_tags($row['eligible']);
				$row['other_t'] = strip_tags($row['other']);
				
				$this->yunset("row",$row);
			}else{
				$data['msg']='职位不存在！';
				$data['url']='index.php?c=job&s=1';
			}
		}
		if($_POST['submit']){
			$_POST=$this->post_trim($_POST);
			$id=(int)$_POST['id'];
			$_POST['desc'] = str_replace("&amp;","&",html_entity_decode($_POST['desc'],ENT_QUOTES));
			$data1['com_name']=$company['name'];
			$data1['pr']=$company['pr'];
			$data1['hy']=$company['hy'];
			$data1['mun']=$company['mun'];
			$data1['desc']=$company['content'];
			$data1['did'] = $company['did'];
			$data1['usertype']=2;
			$data1['job_name']=$_POST['job_name'];
			$data1['department']=$_POST['department'];
			$data1['edate']=strtotime($_POST['edate']);
			$data1['report']=$_POST['report'];
			$data1['jobone']=$_POST['jobone'];
			$data1['jobtwo']=$_POST['jobtwo'];
			$data1['provinceid']=$_POST['provinceid'];
			$data1['cityid']=$_POST['cityid'];
			$data1['three_cityid']=$_POST['three_cityid'];
			$data1['minsalary']=$_POST['minsalary'];
			$data1['maxsalary']=$_POST['maxsalary'];
			if(!empty($_POST['constitute'])){
				$_POST['constitute'] = pylode(",",$_POST['constitute']);
			}else{
				$_POST['constitute'] = "";
			}
			if(!empty($_POST['welfare'])){
				$_POST['welfare'] = pylode(",",$_POST['welfare']);
			}else{
				$_POST['welfare'] = "";
			}
			if(!empty($_POST['language'])){
				$_POST['language'] = pylode(",",$_POST['language']);
			}else{
				$_POST['language'] = "";
			}
			$data1['constitute']=$_POST['constitute'];
			$data1['welfare']=$_POST['welfare'];
			$data1['job_desc']=$_POST['job_desc'];
			$data1['age']=$_POST['age'];
			$data1['sex']=$_POST['sex'];
			$data1['exp']=$_POST['exp'];
			$data1['edu']=$_POST['edu'];
			$data1['language']=$_POST['language'];
			$data1['eligible']=$_POST['eligible'];
			$data1['rebates']=$_POST['rebates'];
			$data1['other']=$_POST['other'];
			$data1['lastupdate']=time();
			$data1['status']=$this->config['lt_job_status'];
			
			
			if($data1['status'] != 0 && $this->usertype == 2){
				$member = $this->obj->DB_select_once("member", "`uid`='{$this->uid}'", "`status`");
				if($member['status'] != 1){
					$data1['status'] = 0;
				}
			}

			if($_POST['id']){
				$job=$this->obj->DB_select_once("lt_job","`id`='".$_POST['id']."' and `uid`='".$this->uid."'","`status`");
				 
				$where['uid']=$this->uid;
				$where['id']=$_POST['id'];
				$id=$this->obj->update_once("lt_job",$data1,$where);
				if($id){
					$this->obj->member_log("更新猎头职位",1,2);
					$data['msg']='修改职位成功！';
					$data['url']='index.php?c=lt_job';
				}else{
					$data['msg']='修改职位失败！';
				}
			}else{
				$data1['uid']=$this->uid;
				$data1['did']=$this->userdid;
				$data1['msg']=$this->get_com(4);

				$id=$this->obj->insert_into("lt_job",$data1);
				if($id){
					$state_content = "新发布了猎头职位 <a href=\"".$this->config['sy_weburl']."/lietou/index.php?c=jobshow&id=".$id."\" target=\"_blank\">".$_POST['job_name']."</a>。";
					$state['uid']=$this->uid;
					$state['content']=$state_content;
					$state['ctime']=time();
					$state['type']=2;
					$this->obj->insert_into("friend_state",$state);
					$this->obj->member_log("发布猎头职位",1,1);
					$data['msg']='发布职位成功！';
					$data['url']='index.php?c=lt_job';
				}else{
					$data['msg']='发布职位失败！';
				}
			}
			echo json_encode($data);die;
		}
		$this->yunset("layer",$data);
		$this->yunset('header_title',"发布猎头职位");
		$this->get_user();
		$this->waptpl('lt_jobadd');
	}
	function lt_job_action(){
		$this->rightinfo();
		$urlarr=array("c"=>"lt_job","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$this->get_page("lt_job","`uid`='".$this->uid."'",$pageurl,"10");
		$this->company_satic();
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"猎头职位管理");
		$this->waptpl('lt_job');
	}
	function ltjobdel_action(){
		if($_GET['id']){
			$del=(int)$_GET['id'];
			$did=$this->obj->DB_delete_all("lt_job","`uid`='".$this->uid."' and `id` in (".$del.")","");
			$this->obj->DB_delete_all("fav_job","`job_id` in (".$del.")","");
			$this->obj->DB_delete_all("rebates","`job_id` in (".$del.")","");
			$this->obj->DB_delete_all("userid_job","`job_id` in (".$del.")","");
			if($did){
				$this->obj->member_log("删除猎头职位",1,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function ltjobset_action(){
		if($_GET['id']){
			$where['id']=(int)$_GET['id'];
			$where['uid']=$this->uid;
			$did=$this->obj->update_once("lt_job",array("zp_status"=>(int)$_GET['status']),$where);
			if($did){
				$this->obj->member_log("修改猎头职位招聘状态",1,2);
				$this->waplayer_msg('操作成功！');
			}else{
				$this->waplayer_msg('操作失败！');
			}
		}
		
		if($_POST){
			if(!isset($_POST['ltjobid'])){
				exit;
			}
			$ltjobid = $_POST['ltjobid'];
			$statis = $this->company_satic();
			$msg = '';

			
			$companyM = $this->MODEL('company');
			$result = $companyM->comVipDayActionCheck('refreshltjob',$this->uid);
			if($result['status']!=1){
			    echo json_encode($result);
			    exit;
			}
			
			$M=$this->MODEL('comtc');
			$return = $M->refresh_ltjob($_POST);
			if($return['status']==1){
				
				$data['msg']=$return['msg'];
				$data['error']=1;
				echo json_encode($data);
				exit;
			}else if($return['status']==2){
				
				$data['msg']=$return['msg'];
				$data['error']=2;
				echo json_encode($data);
				exit;
			}else{
				
				if($return['url']){
					$data['url']=$return['url'];
				}
				$data['msg']=$return['msg'];
				$data['error']=3;
				echo json_encode($data);
				exit;
			}
			 

			$data['msg'] = $msg;
			echo json_encode($data);
			exit;

		}
	}

	function ajax_refresh_job_action()
	{
		if(!isset($_POST['jobid'])){
			exit;
		}

		$jobid = $_POST['jobid'];
		
		$statis = $this->company_satic();

		$msg = '';
		
		
		$companyM = $this->MODEL('company');

		$result = $companyM->comVipDayActionCheck('refreshjob',$this->uid);
		if($result['status']!=1){
		    echo json_encode($result);
		    exit;
		}
		 
 		$M=$this->MODEL('comtc');
 		
 		$return = $M->refresh_job($_POST);
 	
 		if($return['status']==1){
			
			$data['msg']=$return['msg']." !";
			$data['error']=1;
			echo json_encode($data);
			exit;
		}else if($return['status']==2){
			
			$data['msg']=$return['msg']." !";
			$data['error']=2;
			echo json_encode($data);
			exit;
		}else{
			
			if($return['url']){
				$data['url'] = $return['url'];
			}
			$data['msg']=$return['msg'];
			$data['error']=3;
			echo json_encode($data);
 			exit;
		}
		$data['msg'] = $msg;
		echo json_encode($data);
		exit;
	}
    
    function ajax_refresh_part_action(){
		if(!isset($_POST['partid'])){
			exit;
		}
		$partid = $_POST['partid'];
		$statis = $this->company_satic();
		
		$companyM = $this->MODEL('company');
		$result = $companyM->comVipDayActionCheck('refreshpart',$this->uid);
		if($result['status']!=1){
			echo json_encode($result);
			exit;
		}
		 
 		$M=$this->MODEL('comtc');
 		$return = $M->refresh_part($_POST);
 		
 		if($return['status']==1){
			
			$data['msg']=$return['msg']." !";
			$data['error']=1;
			echo json_encode($data);
			exit;
		}else if($return['status']==2){
			
			$data['msg']=$return['msg']." !";
			$data['error']=2;
			echo json_encode($data);
			exit;
		}else{
			
			if($return['url']){
				$data['url'] = $return['url'];
			}
			$data['msg']=$return['msg'];
			$data['error']=3;
			echo json_encode($data);
 			exit;
		}
		echo json_encode($data);
		exit;
	}
	
	function rewardjob(){
	
		$urlarr=array("c"=>"jobpack",'t'=>'r',"page"=>"{{page}}");
		$where="`uid`='".$this->uid."' ";
		
		$pageurl=Url('wap',$urlarr,'member');

		$rows=$this->get_page("company_job_reward",$where,$pageurl,'10');
		
		if(is_array($rows) && !empty($rows)){
			$jobids=array();
			foreach($rows as $v){
				$jobids[]=$v['jobid'];
			}
			$joblist = $this->obj->DB_select_all("company_job","`uid`='".$this->uid."' AND `id` IN (".@implode(',',$jobids).")");

			
			$sqNum = $this->obj->DB_select_all("company_job_rewardlist","`jobid` IN (".@implode(',',$jobids).") group by jobid","count(*) as num,jobid");
			
			foreach($rows as $k=>$v){
				
				foreach($joblist as $val){
					if($v['jobid']==$val['id']){
						$rows[$k]['name']=$val['name'];
						$rows[$k]['status']=$val['status'];
						$rows[$k]['lastupdate']=$val['lastupdate'];
					}
				}
				foreach($sqNum as $val){
					if($v['jobid']==$val['jobid']){
						$rows[$k]['sqnum']=$val['num'];
						
					}
				}
				$rows[$k]['sqnum'] = $rows[$k]['sqnum']?$rows[$k]['sqnum']:0;
			}
		}
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
 		$this->yunset("backurl",$backurl);
 		$this->yunset("header_title","赏金推广职位");
 		$this->yunset("rows",$rows);
		$this->waptpl('jobrewardlist');
	}
	
	function rewardlog_action(){	

		$urlarr=array("c"=>"jobpack",'c'=>'rewardlog',"page"=>"{{page}}");
		$where="`comid`='".$this->uid."' ";
		if($_GET['jobid']){
			$where.=" AND `jobid`='".(int)$_GET['jobid']."'";
			$urlarr['jobid']=$_GET['jobid'];
		}
		
		$pageurl=Url('wap',$urlarr,'member');
 
		$rows=$this->get_page("company_job_rewardlist",$where." order by datetime DESC",$pageurl,'10');
		
		if(is_array($rows) && !empty($rows)){
			$jobids=array();
			foreach($rows as $v){
				$jobids[]=$v['jobid'];
				if($v['usertype']=='3'){
					$lteid[]=$v['eid'];
				}else{
					$eid[]=$v['eid'];
				}
				$rewardid[] = $v['id'];
			}
			$joblist = $this->obj->DB_select_all("company_job","`uid`='".$this->uid."' AND `id` IN (".@implode(',',$jobids).")");
			
			include PLUS_PATH."/user.cache.php";
			include PLUS_PATH."/job.cache.php";
			if(!empty($eid)){
				$ulist = $this->obj->DB_select_all("resume_expect","`id` IN (".@implode(',',$eid).")");

			}
			if(!empty($lteid)){
				$ltulist = $this->obj->DB_select_all("lt_talent","`id` IN (".@implode(',',$lteid).")");

			}
			
			$M			=	$this->MODEL('pack');
			

			$log = $this->obj->DB_select_all("company_job_rewardlog","`rewardid` IN (".@implode(',',$rewardid).") ORDER BY id ASC");
			if(is_array($log)){
				foreach($log as $value){
					$logList[$value['rewardid']][] = $value;
					
				}
			}
			foreach($rows as $k=>$v){
				
				
				
					$rows[$k]['log'] = $M->getStatusInfo($v['id'],2,$v['status'],$logList[$v['id']]);
				
				
				foreach($joblist as $val){
					if($v['jobid']==$val['id']){
						$rows[$k]['name']=$val['name'];
					}
				}
				if(is_array($ulist)){
					foreach($ulist as $val){
						if($v['eid']==$val['id']){
							$rows[$k]['uname']=mb_substr($val['uname'],0,1,'utf-8').'**';
							$rows[$k]['edu']=$userclass_name[$val['edu']];
							$rows[$k]['exp']=$userclass_name[$val['exp']];
							if($val['job_classid']){
								$class = @explode(',',$val['job_classid']);
								foreach($class as $v){
									$classname[] = $job_name[$v];
								}
								$rows[$k]['jobclass']=@implode(',',$classname);
								unset($classname);
							}
						}
					}
				}
				if(is_array($ltulist)){
					foreach($ltulist as $val){
						if($v['eid']==$val['id']){
							$rows[$k]['uname']=mb_substr($val['name'],0,1,'utf-8').'**';
							$rows[$k]['edu']=$userclass_name[$val['edu']];
							$rows[$k]['exp']=$userclass_name[$val['exp']];
							
							$rows[$k]['jobclass']=$val['jobname'];
								
						}
					}
				}
				
			}
		}
		$this->yunset('header_title',"应聘悬赏简历");
		$this->yunset("rows",$rows);
		$this->waptpl('jobrewardlog');
	}
	function rewardpay_action(){
		
		

		if($_GET['id']){
			
			if($this->config['wxpay']=='1'){
				$paytype['wxpay']='1';
			}
			if($this->config['alipay']=='1' &&  $this->config['alipaytype']=='1'){
				$paytype['alipay']='1';
			}
			if($paytype){
				$this->yunset("paytype",$paytype);
			}
			$rewardJob = $this->obj->DB_select_once("company_job_rewardlist","`comid`='".$this->uid."' AND `id`='".$_GET['id']."'");

			$this->yunset("rewardJob",$rewardJob);
		}
		$this->yunset('header_title',"赏金支付");
	
		$this->waptpl('rewardpay');
	}
	function logstatus_action(){
		if($_POST){
				
			 $M			=	$this->MODEL('pack');
			 $return	=  $M->logStatus((int)$_POST['rewardid'],(int)$_POST['status'],$this->uid,'2',$_POST);
				
			 if($return['error']==''){
				
				 echo json_encode(array('error'=>'ok'));
					
			 }else{
				 
				 
				 echo json_encode(array('error'=>$return['error']));
			 }
		}

	
	}
	
	function lookresume_action(){
	
		if($_GET['id']){

			$M			=	$this->MODEL('pack');
			$reward		= $M->getReward((int)$_GET['id'],$this->uid);
			
			if(empty($reward)){

				$this->ACT_msg('index.php?c=jobpack&t=r', '未找到相关数据！',8);

			}elseif($reward['status']=='0'){

				$this->ACT_msg('index.php?c=jobpack&act=rewardlog&jobid='.$reward['jobid'], '请先支付职位赏金！',8);
			
			}else{
				
				if($reward['usertype']=='3'){
					$talentM = $this->MODEL('talent');
					$Info = $talentM->getTalent($reward['uid'],$reward['eid'],'1');
 					
				}else{
					$resumeM=$this->MODEL('resume');

					$Info = $resumeM->resume_select($reward['eid']);
					include(CONFIG_PATH."db.data.php");
					$Info['sex']=$arr_data['sex'][$Info['sex']];
				}
				
				$this->yunset(array("resumestyle"=>$this->config['sy_weburl']."/app/template/resume"));  
				$this->yunset("Info",$Info);
 				$this->yunset("reward",$reward);
			}
			$this->yunset('header_title',"简历详情");
			$this->waptpl('lookresume');
		}
	}
	function rewardinvite_action(){
		if($_GET['rewardid']){
			
			$reward = $this->obj->DB_select_once("company_job_rewardlist","`comid`='".$this->uid."' AND `id`='".(int)$_GET['rewardid']."'");
			$company = $this->obj->DB_select_once("company","`uid`='".$this->uid."'","`address`,`linktel`,`linkphone`,`linkman`");

			if($reward['jobid']){
				$job = $this->obj->DB_select_once("company_job","`id`='".$reward['jobid']."'");
				if(is_array($job)){
					$job_link=$this->obj->DB_select_once("company_job_link","`uid`='".$this->uid."' and  `jobid`='".$_GET['jobid']."'");

					if($job['is_link']=='1'){
						if($job['link_type']=='1'){
							$job['link_man'] = $company['linkman'];
							$job['link_moblie'] = $company['linktel']?$company['linktel']:$company['linkphone'];
						}else{
							$job['link_man'] = $job_link['link_man'];
							$job['link_moblie'] = $job_link['link_moblie'];
						}
					}else if($job['is_link']=='0'){
						$job['link_man'] = "";
						$job['link_moblie'] = "";
					}	
					$job['address'] = $company['address'];
				}
				$this->yunset("job",$job);
			}
			if($reward['eid'] && $reward['uid']){
				include(PLUS_PATH."job.cache.php");
				$resume=$this->obj->DB_select_once("resume","`uid` ='".$reward['uid']."' and `r_status`<>'2'","`name`,`photo`");
				$expect=$this->obj->DB_select_once("resume_expect","`id` = '".$reward['eid']."'","`job_classid`");
				$jobids=@explode(',',$expect['job_classid']);
				foreach($jobids as $key=>$value){
					if($value){
						$jobname[]=$job_name[$value];
					}
				}
 				$resume['jobname']=$jobname;
				$this->yunset("resume",$resume);
			}
			$this->yunset("reward",$reward);
		}
		
        $this->yunset('header_title',"邀请面试");
		$this->waptpl('rewardinvite');
	}
	function loglist_action(){
		
		$userM  = $this->MODEL('userinfo');
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>2));
		
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("company_job_sharelog","`uid`='".$this->uid."' order by time desc",$pageurl,"10");

		$this->yunset("rows",$rows);
		$statis['freeze'] = sprintf("%.2f", $statis['freeze']);
		$this->yunset("statis",$statis);
		$this->yunset('header_title',"赏金收益明细");
		$this->waptpl('loglist');
	}
	function change_action(){
		$this->yunset('header_title',"赏金转换积分");
		$userM=$this->MODEL('userinfo');
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>2));
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
				$nid=$this->obj->DB_update_all("company_statis","`packpay`=`packpay`-'".$changeprice."'","`uid`='".$this->uid."'");
				if($nid){
					$integral->company_invtal($this->uid,$changeintegral,true,"赏金转换积分",true,2,'integral',2);
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
 		$this->waptpl('change');
	}
	function changelist_action(){
		$this->yunset('header_title',"赏金转换积分明细");
		$urlarr["c"]="changelist";
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$where="`com_id`='".$this->uid."'";
		$where.=" and `pay_remark` LIKE '%转换积分%' order by pay_time desc";
		$rows=$this->get_page("company_pay",$where,$pageurl,"10");
		$this->yunset("rows",$rows);
		$userM=$this->MODEL('userinfo');
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>2));
		$this->yunset("statis",$statis);
		$this->waptpl('changelist');
	}
	
	function withdraw_action(){
		
		$this->yunset('header_title',"提现");
		if($_POST){
			$M = $this->MODEL('pack');
			$return	= $M->withDraw($this->uid,$this->usertype,$_POST['price'],$_POST['real_name']);
			if($return==''){
				
				$data['msg']='提现成功，请关注微信账户提醒！';
				$data['url']='index.php?c=withdrawlist';
				$this->yunset("layer",$data);
			 }else{
				 
				 $data['msg']=$return;
				
				$this->yunset("layer",$data);
			 }
		}else{
			$userM  = $this->MODEL('userinfo');
			$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>2));
			$this->yunset("statis",$statis);
		}
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
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>2));

		$this->yunset("statis",$statis);
		$this->yunset("rows",$rows);
		$this->yunset('header_title',"提现明细");
		$this->waptpl('withdrawlist');
	}
	

	function addreward_action(){
	
		if($_GET['jobid']){
			
			if($this->config['wxpay']=='1'){
				$paytype['wxpay']='1';
			}
			if($this->config['alipay']=='1' &&  $this->config['alipaytype']=='1'){
				$paytype['alipay']='1';
			}
			
			if($paytype){
				$this->yunset("paytype",$paytype);
			}
			
		}
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职位推广");
		$this->waptpl('addreward');
	}
	function addrewardjob_action(){
	
		if($_POST){
				
			$M = $this->MODEL('pack');
			$return = $M->rewardJob($_POST);
	
			if($return['error']=='ok'){
				
			    $this->obj->member_log('悬赏职位(ID:'.$_POST['jobid'].')发布成功',1,1);
				$data['msg']='悬赏职位发布成功';
				$data['url']='index.php?c=job';
			}else{
	
				
				$data['msg']=$return['error'];
				$data['url']=$_SERVER['HTTP_REFERER'];
			}
		}else{
			$data['msg']="参数错误，请重试！";
			$data['url']=$_SERVER['HTTP_REFERER'];
		}
		$this->yunset('header_title',"职位推广");
		$this->yunset("layer",$data);
		$this->waptpl('addreward');
	}

    function special_action(){
        $urlarr=array("c"=>"special","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
        
		$rows=$this->get_page("special_com","`uid`='".$this->uid."' ORDER BY `time` DESC",$pageurl,"10");
        if($rows&&is_array($rows)){
			$uid=array();
			foreach($rows as $val){
				$sid[]=$val['sid'];
			}
			$special=$this->obj->DB_select_all("special","`id` in(".pylode(',',$sid).")","id,title,intro");
			foreach($rows as $key=>$val){
				foreach($special as $v){
					if($val['sid']==$v['id']){
						$rows[$key]['title']=$v['title'];
						$rows[$key]['intro']=$v['intro'];
					}
				}
			}
		}
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset("header_title","专题招聘");
		$this->yunset("rows",$rows);
        $this->waptpl('special');
    }
    function delspecial_action(){
        $IntegralM=$this->MODEL('integral');
		$id=$this->obj->DB_select_once("special_com","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."' and `status`=0","uid,integral");
		if($id&&$id['integral']>0){
			$IntegralM->company_invtal($id['uid'],$id['integral'],true,"取消专题招聘报名，退还".$this->config['integral_pricename'],true,2,'integral');
		}
		$delid=$this->obj->DB_delete_all("special_com","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'"," ");
		if($delid){
			$this->obj->member_log("删除专题报名",14,3);
			$this->layer_msg('删除成功！',9,0,$_SERVER['HTTP_REFERER']);
		}else{
			$this->layer_msg('删除失败！',8,0,$_SERVER['HTTP_REFERER']);
		}
	}
	function zhaopinhui_action(){
		$urlarr=array("c"=>"zhaopinhui","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("zhaopinhui_com","`uid`='".$this->uid."' ORDER BY `ctime` DESC",$pageurl,"10");
		
		if(is_array($rows)){
			foreach($rows as $key=>$v){
				$zphid[]=$v['zid'];
				$jobids[]=$v['jobid'];
			}
			$jobids=@implode(',', $jobids);
			$jobid=array_unique(@explode(',', $jobids));
			
			$zhaopinhui=$this->obj->DB_select_all("zhaopinhui","`id` in (".pylode(',',$zphid).")","`id`,`title`,`address`,`starttime`,`endtime`");
			$job=$this->obj->DB_select_all("company_job","`id` in (".pylode(',',$jobid).")","`id`,`name`");
			$space=$this->obj->DB_select_all("zhaopinhui_space");
			$spaces=array();
			foreach($space as $val){
				$spacename[$val['id']]=$val['name'];
			}
			$jobs=array();
			foreach($rows as $k=>$v){
				foreach($zhaopinhui as $val){
					if($v['zid']==$val['id']){
						$rows[$k]['title']=$val['title'];
						$rows[$k]['address']=$val['address'];
						$rows[$k]['starttime']=$val['starttime'];
						$rows[$k]['endtime']=$val['endtime'];
					}
				}
				$rows[$k]['sidname']=$spacename[$v['sid']];
				$rows[$k]['bidname']=$spacename[$v['bid']];
				$rows[$k]['cidname']=$spacename[$v['cid']];
				
				$jobs=@explode(',', $v['jobid']);
				$jobname=array();
				if($jobs){
					foreach($job as $val){
						foreach ($jobs as $vv){
							if($vv==$val['id']){
								$jobname[]=$val['name'];
							}
						}
						$rows[$k]['jobname']=@implode(',', $jobname);
					}
				}
			}
		}
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset("rows",$rows);
		$this->yunset("header_title","招聘会记录");
		$this->waptpl('zhaopinhui');
	}
	function delzph_action(){
		$row=$this->obj->DB_select_once("zhaopinhui_com","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'","`price`,`status`");
		$delid=$this->obj->DB_delete_all("zhaopinhui_com","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'"," ");
		if($delid){
			if($row['status']==0 && $row['price']>0){
			    $IntegralM=$this->MODEL('integral');
				$IntegralM->company_invtal($this->uid,$row['price'],true,"退出招聘会",true,2,'integral');
			}
			$this->obj->member_log("退出招聘会",14,3);
			$this->layer_msg('退出成功！',9,0,$_SERVER['HTTP_REFERER']);
		}else{
			$this->layer_msg('退出失败！',8,0,$_SERVER['HTTP_REFERER']);
		}
	}
	function xjh_action(){
		$urlarr=array("c"=>"xjh","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`uid`='".$this->uid."'";
		$rows=$this->get_page("school_xjh",$where,$pageurl,"10");
		if($rows&&is_array($rows)){
			foreach($rows as $val){
				$uids[]=$val['uid'];
				$sids[]=$val['schoolid'];
				$xjhids[]=$val['id'];
			}
			$company=$this->obj->DB_select_all("company","`uid` in(".pylode(',',$uids).")","`uid`,`name`");
			$academy=$this->obj->DB_select_all('school_academy',"`id` in(".pylode(',',$sids).")",'id,schoolname');
			$atn=$this->obj->DB_select_all('atn',"`xjhid` in(".pylode(',',$xjhids).") group by xjhid",'`xjhid`,count(xjhid) as xjhnum');
			foreach($rows as $key=>$val){
				foreach($company as $v){
					if($val['uid']==$v['uid']){
						$rows[$key]['com_name']=$v['name'];
					}
				}
				foreach($academy as $v){
					if($val['schoolid']==$v['id']){
						$rows[$key]['sch_name']=$v['schoolname'];
					}
				}
				foreach($atn as $v){
					if($val['id']==$v['xjhid']){
						$rows[$key]['atnnum']=$v['xjhnum'];
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `type`='3'","`id`,`status`,`statusbody`");
		$this->yunset("cert",$cert);
		$this->yunset($this->MODEL('cache')->GetCache(array('city')));
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);	
		$this->rightinfo();
		$this->get_user();
		$this->yunset('header_title',"宣讲会");
		$this->waptpl('xjh');
	}
	function delxjh_action(){
		$delid=$this->obj->DB_delete_all("school_xjh","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'"," ");
		if($delid){
			$this->layer_msg('删除成功！',9,0,$_SERVER['HTTP_REFERER']);
		}else{
			$this->layer_msg('删除失败！',8,0,$_SERVER['HTTP_REFERER']);
		}
	}
	function set_action(){
		$company = $this->obj->DB_select_once("company","`uid`='".$this->uid."'");
		if($company['logo']&&$company['logo_status']!='0'){
			$company['logo']=null;
		}
		$this->yunset("company",$company);
		
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='3'");
		$this->yunset("cert",$cert);
		
		$info = $this->obj->DB_select_once("member","`uid`='".$this->uid."'","`restname`");
		if($info['restname']=="0"){
			$this->yunset("setname",1);
		}
		
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"账户设置");
		$this->waptpl('set');
	}
	
	function sysnews_action(){	
		
		$userid_job=$this->obj->DB_select_once("userid_job","`com_id`='".$this->uid."' and `is_browse`='1' order by datetime desc","`job_name`,`uid`,`datetime`");
 		$resume=$this->obj->DB_select_once("resume","`uid`='".$userid_job['uid']."'","`name`");
 		$userid_job['name'] = $resume['name'];
 		$this->yunset('userid',$userid_job);
 		$userid_jobnum=$this->obj->DB_select_num("userid_job","`com_id`='".$this->uid."'and `is_browse`='1'");
 		$this->yunset('userid_jobnum',$userid_jobnum);
		
		$sxrows=$this->obj->DB_select_once("sysmsg","`fa_uid`='".$this->uid."' order by ctime desc");
		$this->yunset("sxrows",$sxrows);
		$sxnum=$this->obj->DB_select_num("sysmsg","`fa_uid`='".$this->uid."'and `remind_status`='0'");
		$this->yunset('sxnum',$sxnum);
 		
 		
	    $jobrows=$this->obj->DB_select_once("msg","`job_uid`='".$this->uid."' and `del_status`<>'1' order by datetime desc");
		$this->yunset('jobrows',$jobrows);
		
		$jobnum=$this->obj->DB_select_num("msg","`job_uid`='".$this->uid."'and `reply`=''");
		$this->yunset('jobnum',$jobnum);
		
		
		$company_msg=$this->obj->DB_select_once("company_msg","`cuid`='".$this->uid."'order by ctime desc","`uid`,`ctime`");
		if($company_msg){
			$com_msgnum=$this->obj->DB_select_num("company_msg","`cuid`='".$this->uid."'and `reply`=''");
			$resume=$this->obj->DB_select_once("resume","`uid`='".$company_msg['uid']."'","`name`");
			$company_msg['name'] = $resume['name'];
			$this->yunset('company_msg',$company_msg);
			$this->yunset('com_msgnum',$com_msgnum);
		}
		
		$jobpacknum = $this->obj->DB_select_num('company_job_rewardlist', "`comid`=".$this->uid." and `status` = 0");
		$jobpack = $this->obj->DB_select_once('company_job_rewardlist', "`comid`=".$this->uid." and `status` = 0 order by datetime desc","uid,jobid,datetime");
		if($jobpack){
			$job=$this->obj->DB_select_once('company_job', "`uid`=".$this->uid." and `id`='".$jobpack['jobid']."'","name");
			$resume=$this->obj->DB_select_once('resume', "`uid`='".$jobpack['uid']."'","name");
			$jobpack['job_name']=$job['name'];
			$jobpack['username']=$resume['name'];
		}
		$this->yunset('jobpacknum',$jobpacknum);
		$this->yunset('jobpack',$jobpack);
        $this->yunset('header_title',"系统消息");
		
    	$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('sysnews');
		
		
		
	}
	
	function msg_action(){
		$urlarr=array("c"=>"msg","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("msg","`job_uid`='".$this->uid."' and `del_status`<>'1' order by datetime desc",$pageurl,"15");
		if(is_array($rows)&&$rows){
			foreach($rows as $key=>$val){
				$rows[$key]['content']=strip_tags(trim($val['content']));
				$uid[]=$val['uid'];
			}
			$resume=$this->obj->DB_select_all("resume_expect","`uid` in (".@implode(",",$uid).")","`id`,`uid`");
		    if(is_array($resume)){
		    	foreach($rows as $k=>$v){		    		
		    		foreach($resume as $val){	    			
		    			if($v['uid']==$val['uid']){		    				
		    				$rows[$k]['did']=$val['id'];			    					    				
		    			}
		    		}
		    	}
		    }		
		}		
		$this->obj->DB_update_all("msg","`com_remind_status`='1'","`job_uid`='".$this->uid."' and `com_remind_status`='0'");
		        
        if($_POST['submit']){
			if($_POST['reply']==""){
				$this->waptpl('msg');
			}else{
				$data['reply']=$_POST['reply'];
				$data['reply_time']=time();
				$data['user_remind_status']='0';
				$where['id']=(int)$_POST['id'];
				$where['job_uid']=$this->uid;
				$nid=$this->obj->update_once("msg",$data,$where);	 			
	 			if($nid){
	 				$this->obj->member_log("回复企业评论",18,1);
	 				$data['msg']='回复成功';
	 				$data['url']='index.php?c=msg';
	 			}else{
	 				$data['msg']='添加失败';
	 			}
 				$this->yunset("layer",$data);
 				$this->waptpl('msg');
			}
		} 
		$this->yunset("rows",$rows);
        $backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"求职咨询");
        $this->waptpl('msg');
	}
	
	function delmsg_action(){
	    if($_GET['id']){
			$nid=$this->obj->DB_delete_all("msg","`id`='".$_GET['id']."' and `job_uid`='".$this->uid."'");
 			if($nid){
 				$this->obj->member_log("删除求职咨询",18,3);
 				$this->layer_msg('删除成功!');
 			}else{
 				$this->layer_msg('删除失败！');
 			}
		}
	}
    
    function sxnews_action(){
    	$urlarr=array("c"=>"sxnews","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows = $this->get_page("sysmsg","`fa_uid`='".$this->uid."' order by id desc",$pageurl,"15");
		if(is_array($rows)){
			$patten = array("\r\n", "\n", "\r");
			foreach($rows as $key=>$value){
			
				$rows[$key]['content_all'] = str_replace($patten, "<br/>", $value['content']);
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"系统消息");
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
		if ($_GET['id']){
            $nid=$this->obj->DB_delete_all("sysmsg","`id`='".$_GET['id']."' and `fa_uid`='".$this->uid."'");
 			if($nid){
 				$this->obj->member_log("删除系统消息",18,3);
 				$this->layer_msg('删除成功！');
 			}else{
 				$this->layer_msg('删除失败！');
 			}
		}
	}
	
	function attention_me_action(){
	    
	    
		$whereAtn = "`sc_uid` = '".$this->uid ."'";
		$users = $this->obj->DB_select_all("atn",$whereAtn);
		
				
		if(is_array($users)){
			foreach($users as $v){
				$uids[] = $v['uid'];
			}
		}
		
		
		$whereResume = "`uid` in (".pylode(',',$uids) .") ";
		
		$defineJobs = $this->obj->DB_select_all("resume",$whereResume," `uid`,`name`,`def_job`,`birthday`");
		
		
		if(is_array($defineJobs)){
			foreach($defineJobs as $v){
				$defineJobsId[] = $v['def_job'];
			}
		}
 		 
		$urlarr=array("c"=>"attention_me","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		
		
		$whereResumeExpect = " `id` in (".pylode(',',$defineJobsId) .") ";
		
 		$resume = $this->get_page("resume_expect",$whereResumeExpect,$pageurl,"5","`id`,`job_classid`,`exp`,`edu`,`minsalary`,`maxsalary`,`uid`");
 		
		
		if(is_array($resume)){
			foreach($resume as $k => $v){
				foreach($users as $u){
					if($v['uid'] == $u["uid"]){
						$resume[$k]['time'] = $u["time"];
						break;
					}
				}
				foreach($defineJobs as $d){
					if($v['uid'] == $d['uid']){
						$resume[$k]['username'] = $d['name'];
						$resume[$k]['birthday'] = $d['birthday'];
						break;
					}
				}
			}
		}
		
		
		$userid_msg=$this->obj->DB_select_all("userid_msg","`fid`='".$this->uid."' and `uid` in (".pylode(",",$uids).")","uid");
		
		if(is_array($resume) && !empty($resume)){
			include(PLUS_PATH."user.cache.php");
			include(PLUS_PATH."job.cache.php");
			foreach($resume as $key=>$val){
				
				$resume[$key]['exp']=$userclass_name[$val['exp']];
				$resume[$key]['edu']=$userclass_name[$val['edu']];
				$resume[$key]['minsalary']=$val['minsalary'];
				$resume[$key]['maxsalary']=$val['maxsalary'];
				if($val['job_classid']!="")
				{
					$job_classid=@explode(",",$val['job_classid']);
					$resume[$key]['jobname']=$job_name[$job_classid[0]];
				}
				
				foreach($userid_msg as $va)
				{
					if($val['uid']==$va['uid'])
					{
						$resume[$key]['userid_msg']=1;
					}
				}
			}
		}
		
		$JobM=$this->MODEL("job");
		$company_job=$JobM->GetComjobList(array("uid"=>$this->uid,"state"=>1," `r_status`<>'2' and `status`<>'1'"),array("field"=>"`name`,`id`"));
		$this->yunset("company_job",$company_job);
		
		$this->yunset('rows',$resume);
		
		$age=date("Y",time());
		$time=date("Y",0);
		
		$this->yunset("age",$age);
		$this->yunset("time",$time);
	    $backurl=Url('wap',array('c'=>'resumecolumn'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"关注我的人才");

	    $this->waptpl('attention_me');
	}
	
	function atnmedel_action(){
		if($_GET['id']){
			$resume = $this->obj->DB_select_once("resume_expect","`id`='".$_GET['id']."'","`uid`");
 			
			$nid=$this->obj->DB_delete_all("atn","`uid`='".$resume['uid']."' and `sc_uid`='".$this->uid."'");
			
			if($nid){
			    $this->obj->member_log("删除关注我的人才（UID:".$resume['uid']."）",5,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
        	
	function give_rebates_action(){
	    $rows=$this->get_page("rebates","`job_uid`='".$this->uid."' order by id desc");
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$uid[]=$v['uid'];
				$id[]=$v['id'];
			}
			$uid=pylode(",",$uid);
			$user=$this->obj->DB_select_all("member","`uid` in (".$uid.")","`uid`,`username`");
			$temporary=$this->obj->DB_select_all("temporary_resume","`rid` in (".pylode(",",$id).")","`rid`,`email`");
			foreach($rows as $k=>$v){
				foreach($user as $val){
					if($v['uid']==$val['uid']){
						$rows[$k]['username']=$val['username'];
					}
				}
				foreach($temporary as $val){
					if($v['id']==$val['rid']){
						$rows[$k]['email']=$val['email'];
					}
				}
			}
		}
		$this->yunset("rows",$rows);
	    $backurl=Url('wap',array('c'=>'resumecolumn'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"推荐给我的人才");
	    $this->waptpl('give_rebates');
	}
	function my_rebates_action(){
		$rows = $this->obj->DB_select_all("rebates","`uid`='".$this->uid."' order by id desc");	
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$uids[]=$v['job_id'];
			}
			$job=$this->obj->DB_select_all("lt_job","`id` in(".pylode(',',$uids).")","`id`,`job_name`,`com_name`,`rebates`,`usertype`");
			foreach($rows as $k=>$v){
				foreach($job as $val){
					if($v['job_id']==$val['id']){
						$rows[$k]['job_name']=$val['job_name'];
						$rows[$k]['com_name']=$val['com_name'];
						$rows[$k]['rebates']=$val['rebates'];
						$rows[$k]['gsid']=$val['uid'];
						if($val['usertype']==2){
							$rows[$k]['type']=2;
						}else{
							$rows[$k]['type']=3;
						}
					}
				}
			}
		}
		$this->yunset('rows',$rows);
	    $backurl=Url('wap',array('c'=>'resumecolumn'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"我推荐的悬赏");

	    $this->waptpl('my_rebates');
	}
	function delrebate_action(){
		if($_GET['id']){
			$del=(int)$_GET['id'];
			$this->obj->DB_delete_all("temporary_resume","`rid`='".$del."'","");
			if($_GET['type']==1){
				$nid=$this->obj->DB_delete_all("rebates","`job_uid`='".$this->uid."' and `id`='".$del."'","");
			}else{
				$nid=$this->obj->DB_delete_all("rebates","`uid`='".$this->uid."' and `id`='".$del."'","");	
			}
			if($nid){
				if($_GET['type']==1){
					$this->obj->member_log("删除推荐给我的人才",25,3);
				}else{
					$this->obj->member_log("删除我推荐的悬赏",25,3);
				}
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	function rebateshow_action(){
		$this->yunset('headertitle',"悬赏详情");
		$this->get_user();
		if(!intval($_GET['type'])){
			$this->obj->DB_update_all("rebates","`status`='1'","`id`='".intval($_GET['id'])."'");
		}
		include(PLUS_PATH."user.cache.php");
		include PLUS_PATH."/job.cache.php";
		include PLUS_PATH."/industry.cache.php";
		include PLUS_PATH."/city.cache.php";
		include(CONFIG_PATH."db.data.php");
		
		$resume=$this->obj->DB_select_once("temporary_resume","`rid`='".intval($_GET['id'])."'","uname,sex,edu,exp,birthday,telphone,email,hy,job_classid,provinceid,cityid,three_cityid,minsalary,maxsalary,type,report");
		
		$rebate=$this->obj->DB_select_once("rebates","`id`='".intval($_GET['id'])."'","content");
		
		$data['uname']=$resume['uname'];
		$data['sex']=$arr_data['sex'][$resume['sex']];
		$data['birthday']=$resume['birthday'];
		$data['edu']=$userclass_name[$resume['edu']];
		$data['exp']=$userclass_name[$resume['exp']];
		$data['telphone']=$resume['telphone'];
		
		if ($resume['email']){
			$data['email']=$resume['email'];
		}else{
			$data['email']="无";
		}
		
		$data['hy']=$industry_name[$resume['hy']];
		if($resume['job_classid']){
			$jobids=@explode(',',$resume['job_classid']);
			foreach($jobids as $val){
				$jobname[]=$job_name[$val];
			}
			$jobname=@implode('、',$jobname);
		}
		$data['job_classid']=$jobname;
		if($city_name[$resume['three_cityid']]){
			$city=$city_name[$resume['provinceid']].'-'.$city_name[$resume['cityid']].'-'.$city_name[$resume['three_cityid']];
		}elseif($city_name[$resume['cityid']]){
			$city=$city_name[$resume['provinceid']].'-'.$city_name[$resume['cityid']];
		}elseif($city_name[$resume['provinceid']]){
			$city=$city_name[$resume['provinceid']];
		}
		
		$data['city']=$city;
		if($resume['minsalary']&&$resume['maxsalary']){
			$salary='￥'.$resume['minsalary'].'-'.$resume['maxsalary'].'万元/年';
		}else if($resume['minsalary']){
			$salary='￥'.$resume['minsalary'].'万元/年以上';
		}else{
			$salary='面议';
		}
		$data['salary']=$salary;
		$data['type']=$userclass_name[$resume['type']];
		$data['report']=$userclass_name[$resume['report']];
		$data['content']=$rebate['content'];
		
		$this->yunset("row",$data);
		
		
		
		$this->waptpl('rebates_info');
	}
	function save_give_rebates_action(){
		if($_POST){
			$data['reply']=$_POST['reply'];
			$data['reply_time']=time();
			$data['status']=1;
			$where['id']=(int)$_POST['id'];
			$where['job_uid']=$this->uid;
			$this->obj->update_once("rebates",$data,$where);
			$this->obj->member_log("回复推荐给我的返利",18,1);
			echo 1;die;
		}
	}
	function rebates_set_action(){
		if($_POST['id']){
			$where['id']=(int)$_POST['id'];
			$where['job_uid']=$this->uid;
			$nid=$this->obj->update_once("rebates",array("status"=>(int)$_POST['status']),$where);
			echo 1;die;
		}
	}
	
	function coupon_list_action(){
		$urlarr=array("c"=>"coupon_list","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("coupon_list","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
		$this->yunset('rows',$rows);
	    
	    
	    $this->yunset('header_title',"优惠卡券");
	    
	    $this->waptpl('coupon_list');
	}
	

	function searchcom_action(){
		$company=$this->obj->DB_select_all("company","`name` like '%".$this->stringfilter(trim($_POST['name']))."%' ","`uid`,`name`");
		
		if($company&&is_array($company)){
			$html="";
			foreach($company as $val){
				$html.="<div class='mui-input-row mui-radio mui-left'>
						<label>".$val['name']."</label>
						<input name='cuid' type='radio' value='".$val['uid']."'>
					</div>";
				
			}
		}else{
			$html=1;
		}
		echo $html;die;
	}
	
	function handsel_action(){
		$coupon=intval($_POST['coupon']);
		$cuid=intval($_POST['cuid']);
 		
 		if($cuid==''){
			$this->waplayer_msg('请选择要赠送的企业！');
 		}  
		$row=$this->obj->DB_select_once("coupon_list","`uid`='".$this->uid."' and `id`='".$coupon."' and `status`='1' and `validity`>'".time()."'");
		 
		if($row['id']){
			$nid=$this->obj->DB_update_all("coupon_list","`uid`='".$cuid."',`source`='".$this->uid."'","`uid`='".$this->uid."' and `id`='".intval($row['id'])."'");
			$nid?$this->waplayer_msg("赠送成功！"):$this->waplayer_msg("赠送失败！");
		}else{
			$this->waplayer_msg("非法操作！");
		}
	}
	
	function delcoupon_action(){
		if($_GET['id']){
			$nid=$this->obj->DB_delete_all("coupon_list","`uid`='".$this->uid."' and `id`='".$_GET['id']."'and `status` in('2','3')");
			if($nid){
				$this->obj->member_log("删除优惠券",24,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	
	function invoice_action(){
	    $urlarr=array("c"=>"invoice","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`uid`='".$this->uid."'  order by addtime desc";
		$rows=$this->get_page("invoice_record",$where,$pageurl,"10");		
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$orderId[] = $v['order_id'];
			}
			$orderId = pylode(',',$orderId);
			$order = $this->obj->DB_select_all("company_order","`order_id` in (".$orderId.")","`order_id`,`order_price`");
			
			foreach($rows as $k=>$v){
				foreach($order as $val){
					if((int)$v['order_id']==(int)$val['order_id'] && $v['price']==""){
						$rows[$k]['price']=$val['order_price'];
					}
				}
			}
		}		
		$this->yunset("rows",$rows);
	    $backurl=Url('wap',array('c'=>'finance'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"我的发票");
	    $this->waptpl('invoice');
	}
	
	function sqinvoice_action(){
		
	    include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"sqinvoice","page"=>"{{page}}");
		
		$pageurl=Url('wap',$urlarr,'member');
		$where="`uid`='".$this->uid."' and `order_state`='2' and `is_invoice`='0'";
		if($this->config['sy_com_invoice_money']){
			$where.=" and `order_price`>".$this->config['sy_com_invoice_money']." order by order_time desc";
		}else{
			$where.=" and `order_price`>0 order by order_time desc";
		}
		$rows=$this->get_page("company_order",$where,$pageurl,"10");
		if($rows&&is_array($rows)&&$this->config['sy_com_invoice']=='1'){
			$last_days=strtotime("-7 day");
			foreach($rows as $key=>$val){
				if($val['order_time']>=$last_days && $val['order_remark']!="使用充值卡"){
					$rows[$key]['invoice']='1';
				}
			}
			$this->yunset("rows",$rows);
		}
		
		$invoice = $this->obj->DB_select_once("invoice_info","`uid`='".$this->uid."'");
		$this->yunset("invoice",$invoice);
		
		if($_POST){
			
			if(!$invoice){
				$data['msg']='请先完善发票信息！';
				$data['url']='index.php?c=invoice_info';
			}elseif($_POST['order_price']<$this->config['sy_com_invoice_money']){
				$data['msg']='超过'.$this->config['sy_com_invoice_money'].'元才能申请发票';
				$data['url']='index.php?c=invoice_info';
			}else{
				$value="`order_id`='".$_POST['order_id']."',";
				$value.="`price`='".$_POST['order_price']."',";
				$value.="`uid`='".$this->uid."',";
				$value.="`did`='".$this->userdid."',";
				
				$value.="`title`='".trim($invoice['invoicetitle'])."',";
				$value.="`type`='".trim($invoice['invoicetype'])."',";
				$value.="`invoice_id`='".trim($invoice['registerno'])."',";
				
				if($invoice['invoicetype']=='2'){
					$value.="`bankno`='".trim($invoice['bankno'])."',";
					$value.="`bank`='".trim($invoice['bank'])."',";
					$value.="`opaddress`='".trim($invoice['opaddress'])."',";
					$value.="`opphone`='".trim($invoice['opphone'])."',";
				}
				
				$value.="`style`='".trim($invoice['invoicestyle'])."',";
				$value.="`link_man`='".trim($invoice['linkman'])."',";
				
				if($invoice['invoicestyle']=='1'){
					$value.="`link_moblie`='".trim($invoice['phone'])."',";
					$value.="`address`='".trim($invoice['street'])."',";
				}elseif($invoice['invoicestyle']=='2'){
					$value.="`email`='".trim($invoice['email'])."',";
				}
				$value.="`status`='0',";
				$value.="`addtime`='".time()."'";
				
				$nid=$this->obj->DB_insert_once("invoice_record",$value);
				
				if($nid){
					$this->obj->DB_update_all("company_order","`is_invoice`='1'","`order_id` ='".$_POST['order_id']."' and `uid`='".$this->uid."'");
					$data['msg']="申请成功";
					$data['url']='index.php?c=invoice';
				}else{
					$data['msg']="申请失败";
					$data['url']='index.php?c=sqinvoice';
				}
				
				echo json_encode($data);die;
			}
		}
		
	    $backurl=Url('wap',array('c'=>'finance'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"发票索取");
	    $this->waptpl('invoice_apply');
	}
	
	function invoice_info_action(){
		$rows=$this->obj->DB_select_once("invoice_info","`uid`='".$this->uid."'");
 		$this->yunset("rows",$rows);
 		
 		if($_POST['submit']){
 			
			$_POST=$this->post_trim($_POST);
			
			$id = intval($_POST['id']);
			
 			if($_POST['invoicetitle']==""){
				$data['msg']='发票抬头不能为空！';
			}elseif($_POST['registerno']==""){
				$data['msg']='企业登记税号不能为空！';
			}elseif($_POST['invoicetype']==""){
				$data['msg']='请选择发票类型！';
			}elseif($_POST['invoicetype']=="2"){
				
				if($_POST['bank']==""){
					$data['msg']='请填写开户银行！';
				}elseif($_POST['bankno']==""){
					$data['msg']='请填写开户账号！';
				}elseif($_POST['opaddress']==""){
					$data['msg']='请填写企业注册所在地！';
				}elseif($_POST['opphone']==""){
					$data['msg']='请填写企业注册固话！';
				}
								
			}elseif($_POST['invoicestyle']==""){
				$data['msg']='请选择开票性质！';
			}elseif($_POST['linkman']==""){
				$data['msg']='收件人不能为空！';
			}elseif($_POST['invoicestyle']=="1"){
				if($_POST['street']==""){
					$data['msg']='邮寄地址不能为空！';
				}elseif($_POST['phone']==""){
					$data['msg']='联系手机不能为空！';
				}
			}elseif($_POST['invoicestyle']=="2"){
				if($_POST['email']==""){
					$data['msg']='电子邮箱不能为空！';
				}
			}
			 
			if($data['msg']==''){
				unset($_POST['submit']);
				
				if(!$id){
					$_POST['uid'] = $this->uid;
					$nid=$this->obj->insert_into("invoice_info",$_POST);
			   		$nid?$data['msg']="添加成功":$data['msg']="添加失败";
					
				}else{
					$where['id']=$id;
					$where['uid']=$this->uid;
			   		$nid=$this->obj->update_once("invoice_info",$_POST,$where);
			   		$nid?$data['msg']="更新成功":$data['msg']="更新失败";
				}
				$data['url']='index.php?c=invoice_info';
			}
			echo json_encode($data);die;
		}
 		
	    $backurl=Url('wap',array('c'=>'finance'),'member');
	    $this->yunset('backurl',$backurl);
		$this->yunset('header_title',"发票信息");
	    $this->waptpl('invoice_info');
	}
	
	
	
	function finance_action(){	    
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
		$this->yunset('header_title',"财务管理");
		$this->waptpl('finance');
	}
	function integral_action(){
		$baseInfo			= false;	
		$logo				= false;	
		$signin			    = false;	
		$emailChecked		= false;	
		$phoneChecked		= false;	
		$pay_remark         =false;
		$question        	=false;		
		$answer       		=false;		
		$answerpl           =false;		
		
		$map				= false;	
		$banner				= false;	
		$yyzz				= false;	
		
		$row = $this->obj->DB_select_once("company",'`uid` = '.$this->uid,
			"`name`,`hy`,
			`logo`,`email_status`,`moblie_status`,
			`x`,`y`,
			`firmpic`,
			`yyzz_status`");
		$ban= $this->obj->DB_select_once("banner","`uid`='".$this->uid."'","`pic`");
		$row['firmpic']=$ban['pic'];
		if(is_array($row) && !empty($row)){
			if($row['name'] != '' && $row['hy'] != '' )
				$baseInfo = true;
			
			if($row['logo'] != '') $logo = true;
			if($row['email_status'] != 0) $emailChecked = true;
			if($row['moblie_status'] != 0) $phoneChecked = true;
			if($row['x'] != 0 && $row['y'] != 0) $map = true;
			if($row['firmpic'] != '') $banner = true;
			if($row['yyzz_status'] != 0) $yyzz = true;
			
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
		$statusList = array(
			'baseInfo'		=>$baseInfo,
			'logo'			=>$logo,
		    'signin'		=>$signin,
			'emailChecked'	=>$emailChecked,
			'phoneChecked'	=>$phoneChecked,
			'question'	    =>$question,
			'answer'	    =>$answer,
			'answerpl'	    =>$answerpl,
			'map'			=> $map,	
			'banner'		=> $banner,	
			'yyzz'			=> $yyzz	
		);
		$this->yunset("statusList",$statusList);
		$statis=$this->obj->DB_select_once("company_statis","`uid`='".$this->uid."'","`integral`");
		$this->yunset("statis",$statis);
		if($_GET['type']){
			$backurl=Url('wap',array('c'=>'finance'),'member');
		}else{
			$backurl=Url('wap',array(),'member');
		}
		
		$reg_url = Url('wap',array('c'=>'register','uid'=>$this->uid));
		$this->yunset('reg_url', $reg_url);
		
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"积分管理");
		$this->waptpl('integral');
	}
	
	function resumecolumn_action(){
		
		
		$sqnum=$this->obj->DB_select_num("userid_job","`com_id`='".$this->uid."'");
		$this->yunset('sqnum',$sqnum);
		
		$userid_jobnum=$this->obj->DB_select_num("userid_job","`com_id`='".$this->uid."'and `is_browse`='1'");
 		$this->yunset('userid_jobnum',$userid_jobnum);
 		
		
		
		$userid_msgnum=$this->obj->DB_select_num("userid_msg","`fid`='".$this->uid."'");
 		$this->yunset("invitenum",$userid_msgnum);
		
		
	    $looknum=$this->obj->DB_select_num("look_resume","`com_id`='".$this->uid."'and `com_status`='0'");
	    $this->yunset("looknum",$looknum);
	    
	    
	    $talentnum=$this->obj->DB_select_num("talent_pool","`cuid`='".$this->uid."'");
	    $this->yunset("talentnum",$talentnum);
	    
	    
	    $downnum=$this->obj->DB_select_num("down_resume","`comid`='".$this->uid."'");
	    $this->yunset("downnum",$downnum);
	    
	    
	    $atnnum=$this->obj->DB_select_num("atn","`sc_uid`='".$this->uid."'");
 	    $this->yunset("atnnum",$atnnum);
	    
	    
	    $lookjobnum=$this->obj->DB_select_num("look_job","`com_id`='".$this->uid."' and `com_status`='0'");
	    $this->yunset("lookjobnum",$lookjobnum);
	    
	    
	    $rebatesnum=$this->obj->DB_select_num("rebates","`uid`='".$this->uid."'");
	    $this->yunset("rebatesnum",$rebatesnum);
	   
	   
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"简历管理");

		$this->waptpl('resumecolumn');
	}
	
    function jobcolumn_action(){
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);
		$this->rightinfo();
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
 		$this->yunset("header_title","职位管理");
		$this->get_user();
		$this->company_satic();
		$this->waptpl('jobcolumn');
	}
	
	function integral_reduce_action(){
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->get_user();
		$this->yunset('header_title',"消费规则");
		$this->waptpl('integral_reduce');
	}
	
	function xjhadd_action(){
		
		if($_POST['submit']){
			if($_POST['provinceid']==''){
				$data['msg']='请选择宣讲省份';
			}elseif($_POST['cityid']==''){
				$data['msg']='请选择宣讲城市';
			}elseif($_POST['schoolid']==''){
				$data['msg']='请选择宣讲学校';
			}elseif($_POST['address']==''){
				$data['msg']='请选择详细地点';
			}elseif($_POST['datetime']==''){
				$data['msg']='请选择宣讲日期';
			}elseif($_POST['stime']==''){
				$data['msg']='请选择宣讲开始时间';
			}elseif($_POST['etime']==''){
				$data['msg']='请选择宣讲结束时间';
			}
			$sdate=strtotime($_POST['datetime'].' '.$_POST['stime']);
			$edate=strtotime($_POST['datetime'].' '.$_POST['etime']);
			$kdate=time();
			if($sdate<$kdate){
				$data['msg']='宣讲日期不能小于当前日期';
			}
			if($sdate>$edate){
				$data['msg']='开始时间不能小于当前时间';
			}
			if($data['msg']==''){
				$data['provinceid']=$_POST['provinceid'];
				$data['cityid']=$_POST['cityid'];
				$data['schoolid']=$_POST['schoolid'];
				$data['address']=$_POST['address'];
				$data['stime']=$sdate;
				$data['etime']=$edate;
				$data['uid']=$this->uid;
				if($_POST['id']){
					$data['status']='0';
					$where['id']=$_POST['id'];
					unset($_POST['id']);
					$nid=$this->obj->update_once('school_xjh',$data,$where);
					$msg='修改';
				}else{
					$data['ctime']=time();
					$nid=$this->obj->insert_into('school_xjh',$data);
					$msg='添加';
				}
				if($nid){
					$data['msg']=$msg.'成功';
					$data['url']='index.php?c=xjh';
				}else{
					$data['msg']=$msg.'失败';
					$data['url']=$_SERVER['HTTP_REFERER'];
				}
			}
			echo json_encode($data);die;
		}
		$this->yunset($this->MODEL('cache')->GetCache(array('city')));
		$school=$this->obj->DB_select_all('school_academy','1','id,schoolname,cityid');
		$this->yunset("school",$school);
		if($_GET['id']){
			$row=$this->obj->DB_select_once('school_xjh',"`id`='".$_GET['id']."'");
			$this->yunset("row",$row);
		}
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"新增宣讲会");
		$this->waptpl('xjhadd');
	}
	
	function banner_action(){
		
		if($_POST['submit']){
			if($_POST['preview']){
				
				$UploadM =$this->MODEL('upload');
				$upload  =$UploadM->Upload_pic(APP_PATH."/data/upload/company/",false);
				
				$pic     =$upload->imageBase($_POST['preview']);
				
				$picmsg  = $UploadM->picmsg($pic,$_SERVER['HTTP_REFERER']);
				if($picmsg['status']==$pic){
					$data['msg']=$picmsg['msg'];
 				}else{
					
					$photo=str_replace(APP_PATH."/data/upload/company/","./data/upload/company/",$pic);
					$datap['uid']=$this->uid;
					$datap['pic']=$photo;
				}

			}
			
			if($data['msg']==""){
				$row=$this->obj->DB_select_once("banner","`uid`='".$this->uid."'");
				
				if($row['id']){
					if($row['pic']){
						unlink_pic(APP_PATH.$row['pic']);
					}
					$nid=$this->obj->update_once("banner",$datap,array('id'=>$row['id']));
				}else{
					$nid=$this->obj->insert_into("banner",$datap);
				}
				
				if($nid){
					$this->obj->member_log("上传企业横幅",16,1);
							
					if(!$row['id']){
						$IntegralM=$this->MODEL('integral');
						$IntegralM->get_integral_action($this->uid,"integral_banner","上传企业横幅");
					}
							
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
		
		$banner=$this->obj->DB_select_once("banner","`uid`='".$this->uid."'");
		
		if($banner['pic'] && file_exists(str_replace('./',APP_PATH,$banner['pic']))){
			$banner['pic']=str_replace('./',$this->config['sy_weburl'].'/',$banner['pic']);
		}else{
			$banner['pic']='';
		}
		
		$this->yunset("banner",$banner);
		$this->yunset("layer",$data);
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset("backurl",$backurl);
		$this->yunset('header_title',"企业横幅");
		$this->waptpl('banner');
	}
	
	
	
	function usecard_action(){
		if($_POST['submit']){
			$info=$this->obj->DB_select_once("prepaid_card","`card`='".$_POST['card']."' and `password`='".$_POST['password']."'");
			if($_POST['card']==''){
				$data['msg']='请填写卡号！';
			}elseif($_POST['password']==''){
				$data['msg']='请填写密码！';
			}elseif(empty($info)){
				$data['msg']='卡号或密码错误！';
			}elseif($info['uid']>0){
				$data['msg']='该充值卡已使用！';
			}elseif($info['type']=="2"){
				$data['msg']='该充值卡不可用！';
			}elseif($info['stime']>time()){
				$data['msg']='该充值卡还未到使用时间！';
			}elseif($info['etime']<time()){
				$data['msg']='该充值卡已过期！';
			}
			if ($data['msg']==''){
				$dingdan=mktime().rand(10000,99999);
				$integral=$info['quota']*$this->config['integral_proportion'];
				$data['order_id']=$dingdan;
				$data['order_price']=$info['quota'];
				$data['order_time']=mktime();
				$data['order_state']="2";
				$data['order_remark']="使用充值卡";
				$data['uid']=$this->uid;
				$data['did']=$this->userdid;
				$data['integral']=$integral;
				$data['type']='2';
				$nid=$this->obj->insert_into("company_order",$data);
				if($nid){
					$this->obj->DB_update_all("prepaid_card","`uid`='".$this->uid."',`username`='".$this->username."',`utime`='".time()."'","`id`='".$info['id']."'");
					$this->MODEL('integral')->company_invtal($this->uid,$integral,true,"充值卡充值",true,$pay_state=2,"integral");
					$data['msg']='充值卡使用成功！';
					$data['url']="index.php?c=finance";
				}else{
					$data['msg']='充值卡使用失败！';
					$data['url']=$_SERVER['HTTP_REFERER'];
				}
			}
			$this->yunset("layer",$data);
		}
		$backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"充值卡充值");
		$this->waptpl('usecard');
	}
	
	function show_action(){
		$urlarr['c']="show";
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows = $this->get_page("company_show","`uid`='".$this->uid."' order by sort desc",$pageurl,"12","`title`,`id`,`picurl`");
		
		if($rows&&is_array($rows)){
			foreach($rows as $k=>$v){
				$rows[$k]['picurl']=str_replace('./','/',$v['picurl']);
			}
		}
		$this->yunset("rows",$rows);
		$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`name`");
		$this->yunset("company",$company);
		
		$this->yunset("js_def",2);
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"企业环境");
		$this->waptpl('show');
	}
	
	function del_action(){
		if($_POST['id']==""){
			$data=3;
		}else{
			$row=$this->obj->DB_select_once("company_show","`id`='".(int)$_POST['id']."' and `uid`='".$this->uid."'","`picurl`");
			if(is_array($row)){
				unlink_pic(".".$row['picurl']);
				$oid=$this->obj->DB_delete_all("company_show","`id`='".(int)$_POST['id']."' and `uid`='".$this->uid."'");
			}
			if($oid){
				$this->obj->member_log("删除企业环境展示",16,3);
				$data=1;
			}else{
				$data=2;
			}
		}
		echo json_encode($data);die;
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
 				}else{
 					$photo=str_replace(APP_PATH."/data/upload/show/","./data/upload/show/",$pic);
 					
 					$picurl=$photo;
				}
			}
			
			if($data['msg']==""){
				$datashow=array(
					'title'=>$_POST['title'],
					'uid'=>$this->uid,
					'ctime'=>time()
				);
				$companyM = $this->MODEL('company');
				if($_POST['id']){
					$row=$this->obj->DB_select_once("company_show","`id`='".$_GET['id']."' and `uid`='".$this->uid."'");
					if(!$picurl){
						$datashow['picurl']=$row['picurl'];
					}elseif($picurl!=$row['picurl']){
						if($row['picurl']){
							unlink_pic(APP_PATH.$row['picurl']);
						}
						$datashow['picurl']=$picurl;
					}
					$nid = $companyM->UpdateShow($datashow,array('id'=>intval($_POST['id']),'uid'=>$this->uid));
					if($nid){
						$data['msg']='更新成功！';
						$data['url']='index.php?c=show';
					}else{
						$data['msg']='更新失败！';
						$data['url']='index.php?c=show';
					}
				}else{
					if(!$picurl){
						$data['msg']='请上传企业环境！';
					}else{
						$datashow['picurl']=$picurl;
						$id = $companyM->AddCompanyShow($datashow);
						if($id){
							$data['msg']='上传成功！';
							$data['url']='index.php?c=show';
						}else{
							$data['msg']='上传失败！';
							$data['url']='index.php?c=show';
						}
					}
				}
			}else{
				$data['msg']=$data['msg'];
				$data['url']='index.php?c=show';
			}
		}else{
			if($_GET['id']){
				$row=$this->obj->DB_select_once("company_show","`id`='".$_GET['id']."' and `uid`='".$this->uid."'");
				$row['picurl']=str_replace('./','/',$row['picurl']);
				$this->yunset("row",$row);
			}
		}
		$company=$this->obj->DB_select_once("company","`uid`='".$this->uid."'","`name`");
		$this->yunset("company",$company);
		
		$this->yunset("layer",$data);
		$backurl=Url('wap',array('c'=>'show'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"企业环境");
		$this->get_user();
		$this->waptpl('addshow');
	}
	 
	function pl_action(){
		$urlarr=array("c"=>"pl","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("company_msg","`cuid`='".$this->uid."' AND `status`='1'  order by reply_time desc",$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$uid[]=$v['uid'];
			}
			$uid=pylode(",",$uid);
			$user=$this->obj->DB_select_all("resume","`uid` in ($uid)","`uid`,`name`");
			foreach($rows as $k=>$v){
				foreach($user as $val){
					if($v['uid']==$val['uid']){
						$rows[$k]['name']=$val['name']; 
					}
				}
			}
		}
		
		if($_POST['submit']){
			if($_POST['reply']==""){
				$this->waptpl('pl');
			}else{
				$data['reply']=$_POST['reply'];
				$data['reply_time']=time();
				$where['id']=(int)$_POST['id'];
				$where['cuid']=$this->uid;
				$nid=$this->obj->update_once("company_msg",$data,$where);	 			
	 			if($nid){
	 				$this->obj->member_log("回复面试评价",18,1);
	 				$data['msg']='回复成功';
	 				$data['url']='index.php?c=pl';
	 			}else{
	 				$data['msg']='回复失败';
	 			}
 				$this->yunset("layer",$data);
 				$this->waptpl('pl');
			}
		} 
		
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'sysnews'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"面试评价");
		$this->waptpl('pl');
	} 

	function userLog_action(){
		if($_POST){	
			$_POST['uid'] = $this->uid;
			$_POST['usertype'] = $this->usertype;
			$LogM = $this->MODEL('log');
			 
			if($_COOKIE["delay"]==""){
				$nid = $LogM->addUserLog($_POST);
				$this->cookie->SetCookie("delay",$nid,time() + 60);
				echo $nid;die;
			}else{
				$ul = $this->obj->DB_select_once("user_log","`id`='".$_COOKIE["delay"]."'","`second`,`opera`");
				
				if($ul['opera']==$_POST['opera']){
					
					$data['id'] = $_COOKIE["delay"];
					$data['orderid'] = $_POST["orderid"];
					$data['second'] = $_POST['second']+$ul['second'];
					$LogM->updateUserLog($data);
					echo $_COOKIE["delay"];die;

				}else{

					$nid = $LogM->addUserLog($_POST);
					$this->cookie->SetCookie("delay",$nid,time() + 60);
					echo $nid;die;

				}
			}
 		}
	} 

	function gxUserLog_action(){
		if($_POST){
			if($_COOKIE["delay"]!=""){
				$ul = $this->obj->DB_select_once("user_log","`id`='".$_COOKIE["delay"]."'","`second`");
				$_POST['second']=$_POST['second']+$ul['second'];
			}
			$LogM = $this->MODEL('log');
			$_POST['uid'] = $this->uid;
			$_POST['usertype'] = $this->usertype;
			$LogM->updateUserLog($_POST);
		}
	}
}
	
?>