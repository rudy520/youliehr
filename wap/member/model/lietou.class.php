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
class lietou_controller extends wap_controller{
	function waptpl($tpname){
		$this->yuntpl(array('wap/member/lietou/'.$tpname));
	}
	function user_shell(){
		$userinfo=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		if($userinfo['realname']==""){			
			$data['msg']='请先完善基本资料！';
		    $data['url']='index.php?c=info';
			$this->yunset("layer",$data);	
		}
	}
	function index_action(){
		$user=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		if($user['photo_big'] == ""){
			$user['photo_big'] = $this->config['sy_weburl']."/".$this->config['sy_lt_icon'];
		}else{
			$user['photo_big'] = str_replace("./",$this->config['sy_weburl']."/",$user['photo_big']);
		}
		$this->yunset("user",$user);
		
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
		$this->seo("ltindex");
		$this->user_shell();
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
	
	function info_action(){
		$CacheList=$this->MODEL('cache')->GetCache(array('lt','lthy','ltjob','city'));
		$this->yunset($CacheList);
		$row=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		if(!$row['photo_big'] || !file_exists(str_replace('./',APP_PATH,$row['photo_big']))){
			$row['photo_big']=$this->config['sy_weburl']."/".$this->config['sy_lt_icon'];
		}else{
			$row['photo_big']=str_replace("./",$this->config['sy_weburl']."/",$row['photo_big']);
		}
		if($row['job']){
			$job=@explode(",",$row['job']);
			foreach ($job as $v){
				$jobname[]=$CacheList['ltjob_name'][$v];
			}
		}
		$jobname=@implode(",",$jobname);
		$this->yunset("jobname",$jobname);
		if($row['hy']){
			$hy=@explode(",",$row['hy']);
			foreach ($hy as $v){
				$hyname[]=$CacheList['lthy_name'][$v];
			}
		}
		$hyname=@implode(",",$hyname);
		$this->yunset("hyname",$hyname);
		$this->yunset("row",$row);
		if($_POST['submit']){
			$_POST=$this->post_trim($_POST);
			unset($_POST['submit']);
			if($_POST['realname']==''){
				$data['msg']='请输入真实姓名！';
			}elseif($_POST['com_name']==''){
				$data['msg']='请输入所在公司！';
			}elseif($_POST['phone']==''){
				$data['msg']='请输入公司座机！';
			}elseif($_POST['phone']&&CheckTell($_POST['phone'])==false){
				$data['msg']='公司座机格式错误！';
			}elseif($_POST['email']&&CheckRegEmail($_POST['email'])==false){
				$data['msg']='联系邮箱格式错误！';
			}elseif($_POST['moblie']==''){
				$data['msg']='请输入手机号码！';
			}elseif($_POST['moblie']&&CheckMoblie($_POST['moblie'])==false){
				$data['msg']='手机号码格式错误！';
			}elseif($_POST['cityid']==''){
				$data['msg']='请输入所在公司！';
			}elseif($_POST['exp']==''){
				$data['msg']='请选择工作经验！';
			}elseif($_POST['title']==''){
				$data['msg']='请选择目前头衔！';
			}elseif($_POST['qw_hy']==''){
				$data['msg']='请选择擅长行业！';
			}elseif($_POST['job']==''){
				$data['msg']='请选择擅长职位！';
			}elseif($_POST['content']==''){
				$data['msg']='请输入顾问介绍！';
			}else{
				$where['uid']=$this->uid;
				$_POST['job'] = pylode(",",$_POST['job']);
				$_POST['hy'] = pylode(",",$_POST['qw_hy']);
				$row=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
				$Member=$this->MODEL("userinfo");
				if($row['moblie_status']==1){
					unset($_POST['moblie']);
				}else{
					$moblieNum = $Member->GetMemberNum(array("moblie"=>$_POST['moblie'],"`uid`<>'".$this->uid."'"));
					if($_POST['moblie']==''){
						$data['msg']='手机号码不能为空！';
					}elseif(!CheckMoblie($_POST['moblie'])){
						$data['msg']='手机号码格式错误！';
					}elseif($moblieNum>0){
						$data['msg']='手机号码已存在！';
					}else{
						$data1['moblie']=$_POST['moblie'];
					}
				}
				if($row['email_status']==1){
					unset($_POST['email']);
				}else{
					$emailNum = $Member->GetMemberNum(array("email"=>$_POST['email'],"`uid`<>'".$this->uid."'"));
					if($_POST['email']&&CheckRegEmail($_POST['email'])==false){
						$data['msg']='联系邮箱格式错误！';
					}elseif($_POST['email']&&$emailNum>0){
						$data['msg']='联系邮箱已存在！';
					}else{
						$data1['email']=$_POST['email'];
					}
				}
				$this->obj->DB_update_all("lt_job","`com_name`='".$_POST['com_name']."'","`uid`=".$this->uid." ");
				$id=$this->obj->update_once("lt_info",$_POST,$where);
				if($id){
					if(!empty($data1)){
						$this->obj->update_once("member",$data1,array("uid"=>$this->uid));
					}
					$this->obj->member_log("修改基本信息",7);
					
				
					if($row['com_name']==""){
						$this->MODEL('integral')->get_integral_action($this->uid,"integral_userinfo","完善基本资料");
					}
					$data['msg']='更新成功！';
					$data['url']='index.php';
				}else{
					$data['msg']='更新失败！';
				}
			}
			echo json_encode($data);die;
			
		}
		
		
		$this->yunset('header_title',"基本资料");
		$this->waptpl('info');
	}

	
	function uppic_action(){
		if($_POST['submit']){
			$pic=$this->wap_up_pic($_POST['uimage'],'lietou');
			if($pic['errormsg']){echo 2;die;}
			if($pic['re']){
				$ltInfo=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'","`photo`,`photo_big`");
				if($ltInfo['photo']){
					unlink_pic(APP_PATH.$ltInfo['photo']);
				}else{
					$this->MODEL('integral')->get_integral_action($this->uid,"integral_avatar","上传头像");
				}
				if($ltInfo['photo_big']){
					unlink_pic(APP_PATH.$ltInfo['photo_big']);
				}
				$photo="./data/upload/lietou/".date('Ymd')."/".$pic['new_file'];
				$ref=$this->obj->DB_update_all("lt_info","`photo_big`='".$photo."',`photo`='".$photo."'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("answer","`pic`='".$photo."'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("question","`pic`='".$photo."'","`uid`='".$this->uid."'");
				if($ref){$this->obj->member_log("上传猎头头像",16,1);echo 1;die;}else{echo 2;die;}
			}else{
				unlink_pic(APP_PATH."data/upload/lietou/".date('Ymd')."/".$pic['new_file']);
				echo 2;die;
			}
		}else{
			$row = $this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		    if(!$row['photo_big'] || !file_exists(str_replace('./',APP_PATH,$row['photo_big']))){
			    $row['photo_big']=$this->config['sy_weburl']."/".$this->config['sy_lt_icon'];
			}else{
			    $row['photo_big']=str_replace("./",$this->config['sy_weburl']."/",$row['photo_big']);
			}
			$this->yunset("row",$row);
		}
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"上传头像");
		$this->waptpl('uppic');
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
		if($paytype){
			if($_POST['usertype']=='price'){
				$id=(int)$_POST['id'];
				if ($id){
					$rows=$this->obj->DB_select_once("company_rating","`service_price`<>'' and `service_time`>'0' and `id`='".$id."' and `display`='1' and `category`=2 order by sort desc","name,time_start,time_end,service_price,yh_price,coupon,id");
					if ($row['time_start']<time() && $rows['time_end']>time()){
						if ($rows['coupon']>0){
							$coupon=$this->obj->DB_select_once("coupon","`id`='".$rows['coupon']."'");
							$this->yunset("coupon",$coupon);
						}
					}
				}
				
				
				$this->yunset("rows",$rows);
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
			$this->yunset("paytype",$paytype);
		}else{
			$data['msg']="暂未开通手机支付，请移步至电脑端充值！";
			$data['url']=$_SERVER['HTTP_REFERER'];
			$this->yunset("layer",$data);
				
		}
		$nopayorder=$this->obj->DB_select_num("company_order","`uid`=".$this->uid." and `order_state`=1");
		$this->yunset('nopayorder',$nopayorder);
		$this->yunset($this->MODEL('cache')->GetCache(array('integralclass')));
		
		
		$this->user_shell();
		$this->yunset('header_title',"充值积分");
		$this->waptpl('pay');
	}
	function dingdan_action(){
		$data['msg']="参数不正确，请正确填写！";
		$data['url']=$_SERVER['HTTP_REFERER'];
		
		if($_POST['price']){
			
			$statis = $this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'","`integral`");
			
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
						$value=$ratingM->ltrating_info($_POST['comvip'],$this->uid);

						$status=$this->obj->DB_update_all("lt_statis",$value,"`uid`='".$this->uid."'");
						
						$this->obj->DB_update_all("lt_job","`rating`='".$_POST['comvip']."'","`uid`='".$this->uid."'");

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
			$data['rating']=$_POST['comvip'];
			$data['integral']=$_POST['price_int'];
			$id=$this->obj->insert_into("company_order",$data);
			if($id){
				
				$this->obj->DB_update_all("coupon_list","`status`='2',`xf_time`='".time()."'","`id`='".$coupon['id']."'");
				
				if($_POST['comvip']){
					$this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买会员",true,2,'integral',27);
					$this->obj->member_log("购买会员,订单ID".$dingdan,88);
				}else if($_POST['price_int']){
					$this->obj->member_log("积分充值,订单ID".$dingdan,88);
				}
				$_POST['dingdan']=$dingdan;
				$_POST['dingdanname']=$dingdan;
				$_POST['alimoney']=$price;
				$data['msg']="下单成功，请付款！";
				
				if($_POST['paytype']=='alipay'){
					$url=$this->config['sy_weburl'].'/api/wapalipay/alipayto.php?dingdan='.$dingdan.'&dingdanname='.$dingdanname.'&alimoney='.$price;
					header('Location: '.$url);exit();
				}elseif($_POST['paytype']=='wxpay'){
					$url='index.php?c=wxpay&id='.$id;
					header('Location: '.$url);exit();
				}
			}else{
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
		$this->user_shell();
		$this->waptpl('pay');
	}
	
	function wxpay_action(){
		if($_GET['id']){
			$id = (int)$_GET['id'];
			$order = $this->obj->DB_select_once("company_order","`uid`='".$this->uid."' AND `id`='".$id."'");
			if(!empty($order)){
				require_once(LIB_PATH.'wxOrder.function.php');
				$jsApiParameters = wxWapOrder(array('body'=>'充值','id'=>$order['order_id'],'url'=>$this->config['sy_weburl'],'total_fee'=>$order['order_price']));
				if($jsApiParameters){
					$this->yunset('jsApiParameters',$jsApiParameters);
				}else{
					$data['msg']="参数不正确，请重新支付！";
					$data['url']='index.php?c=paylog';
					$this->yunset("layer",$data);
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
			$this->user_shell();
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
							$price = $ratinginfo['yh_price'];
						}else{
							$price = $ratinginfo['service_price'];
						}
						
						$data['type']='1';
	
					}elseif($_POST['comservice']){
						$id=(int)$_POST['comservice'];
						$dkjf=(int)$_POST['dkjf'];
						$price=$_POST['dkprice'];
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
						if($_POST['comvip']){
							$this->MODEL('integral')->company_invtal($this->uid,$dkjf,false,"购买会员",true,2,'integral',11);
							$this->obj->member_log("购买会员,订单ID".$dingdan,88);
						}else if($_POST['price_int']){
							$this->obj->member_log("积分充值,订单ID".$dingdan,88);
						}
						
						$data['msg']="操作成功，请等待管理员审核！";
						$data['url']="index.php?c=paylog";
						$this->yunset("layer",$data);
					}else{
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
		$this->waptpl('payment');
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
		
		
		
		
		if($paytype){
			
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
			
 			$this->yunset("paytype",$paytype);
 			
		}else{
			$data['msg']="暂未开通手机支付，请移步至电脑端充值！";
			$data['url']=$_SERVER['HTTP_REFERER'];
			$this->yunset("layer",$data);
		}
		
		
		$this->yunset('header_title',"订单确认");
		
		$this->waptpl('payment');
	}
	
	function passwd_action(){
		$this->rightinfo();
		 
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"安全密码");
		$this->waptpl('passwd');
	}
	
	function job_action(){
		
		$jobM = $this->MODEL('lietou');
		$rows = $jobM->GetLietoujobList(array('uid'=>$this->uid),array('orderby'=>'`lastupdate`','desc'=>'desc'));
        if(is_array($rows)){
			foreach ($rows as $k=>$v){
				if($v['minsalary']>0){
					if($v['maxsalary']>0){
						$rows[$k]['msalary']='￥'.floatval($v['minsalary']).'-'.floatval($v['maxsalary']).'万';
					}else{
						$rows[$k]['msalary']='￥'.floatval($v['minsalary']).'万以上';
					}
				}else{
					$rows[$k]['msalary']='面议';
				}
			}
		}
		$zp=$sh=$xj=0;
		if(is_array($rows)){
		    foreach($rows as $value){
		        if($value['status']==1 && $value['zp_status']==0){
		            $zp +=1;
		        }
		        if($value['status']!='1'){
		            $sh +=1;
		        }
		        if($value['zp_status']=='1'){
		            $xj +=1;
		        }
		    }
		}
		$this->yunset(array('zp'=>$zp,'sh'=>$sh,'xj'=>$xj));
		$this->yunset("rows",$rows);
		$this->lt_satic();
		$CacheList=$this->MODEL('cache')->GetCache(array('lt','city'));
		$this->yunset($CacheList);
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"职位管理");
		$this->waptpl('job');
	}
	
	function jobdel_action(){
		if($_GET['id']){
			$del=(int)$_GET['id'];
			$did=$this->obj->DB_delete_all("lt_job","`uid`='".$this->uid."' and `id` in (".$del.")","");
			$this->obj->DB_delete_all("fav_job","`job_id` in (".$del.")","");
			$this->obj->DB_delete_all("rebates","`job_id` in (".$del.")","");
			$this->obj->DB_delete_all("userid_job","`job_id` in (".$del.")","");
			if($did){
				$this->obj->member_log("删除猎头职位",10,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
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
		
		$jobid=intval($_GET['id']);
		$server=intval($_GET['server']);
		
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);

		$info=$this->obj->DB_select_once("lt_job","`uid`='".$this->uid."' and `id`='".$jobid."'","`id`");
		$this->yunset("info",$info);

		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		
		$server=intval($_GET['server']);
		switch($server){
			case 1:$header_title="刷新职位";
			break;
			case 2:$header_title="简历下载";
			break;
			case 3:$header_title="发布职位";
			break;
		}
		$this->yunset('header_title',$header_title);
		$this->user_shell();
		$this->waptpl('getserver');
	}

	function getOrder_action(){
		if($_POST){
       		$M=$this->MODEL('compay');
			if ($_POST['server']=='refresh_job'){
				$return = $M->buyLtJobRefresh($_POST);
				$msg="购买刷新猎头职位";
			} else if ($_POST['server']=='issue_job'){
				$return = $M->buyLtIssueJob($_POST);
				$msg="购买发布猎头职位";
			} else if ($_POST['server']=='downresume'){
				$return = $M->buyLtDownresume($_POST);
				$msg="购买简历下载";
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
		$this->user_shell();
		$this->waptpl('pay');
	}


	function dkzf_action(){
  		if($_POST){
   			$M=$this->MODEL('jfdk');
			
			if ($_POST['jobid']){
				$return = $M->buyLtJobRefresh($_POST);
			} else if ($_POST['issuejob']){
				$return = $M->buyLtIssueJob($_POST);
			} else if ($_POST['eid']){
				$return = $M->buyLtDownresume($_POST);
			} elseif($_POST['id']){
				$return = $M->buyVip($_POST);
			}
			
			
			if($return['status']==1){
				
				echo json_encode(array('error'=>0,'msg'=>$return['msg']));
			}else{
				
				echo json_encode(array('error'=>1,'msg'=>$return['error'],'url'=>$return['url']));
			}
		}else{
			echo json_encode(array('error'=>1,'msg'=>'参数错误，请重试！'));
		}
	}
	
	function jobset_action(){
		if($_GET['id']){
			$where['id']=(int)$_GET['id'];
			$where['uid']=$this->uid;
			$did=$this->obj->update_once("lt_job",array("zp_status"=>(int)$_GET['status']),$where);
			if($did){
				$this->obj->member_log("设置猎头职位招聘状态",10,2);
				$this->waplayer_msg('操作成功！');
			}else{
				$this->waplayer_msg('操作失败！');
			}
		}
		 
	}

 	function ajax_refresh_job_action() {
		
		if(!isset($_POST['jobid'])){
			exit;
		}

		$jobid = $_POST['jobid'];
		
		$statis = $this->lt_satic();

		$msg = '';
		
 		$M=$this->MODEL('comtc');
 		$return = $M->ltRefreshJob($_POST);
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

	
	function lt_satic(){
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");

		if($statis['rating']){
			$rating=$this->obj->DB_select_once("company_rating","`id`='".$statis['rating']."' and `category`='2'");
		}
		
		if($statis['vip_etime'] < time()){
			
			if($statis['vip_etime'] > 1){
			
				$nums=0;
			
			}else if($statis['vip_etime'] < '1' && $statis['rating']!="0"){
			
				$nums=1;
			
			}else{
			
				$nums=0;
			
			} 

			if($nums==0){
				if($this->config['com_vip_done']=='0'){

					$data['lt_job_num']=$data['lt_breakjob_num']=$data['lt_down_resume']='0';
					$data['oldrating_name']=$statis['rating_name'];
					$statis['rating_name']=$data['rating_name']="过期会员";
					
					$statis['rating_type']=$statis['rating']=$data['rating_type']=$data['rating']="0"; 
  					
					$where['uid']=$this->uid;
					
					$this->obj->update_once("lt_statis",$data,$where);
					
				}elseif ($this->config['com_vip_done']=='1'){
					
					$ratingM = $this->MODEL('rating');
					
					$rat_value=$ratingM->ltrating_info();
					
					$this->obj->DB_update_all("lt_statis",$rat_value,"`uid`='".$this->uid."'");
				}
			}
		}
		
		if($statis['vip_etime']>time() || $statis['vip_etime']==0){
 			if($statis['rating_type']=="2"){
				$addltjobnum='1';
			}elseif($statis['rating_type']=='1'){
 				if($statis['lt_job_num'] > 0){
					$addltjobnum='1';
				}else{
					$addltjobnum='2';
				}
   		}else{
				$addltjobnum='0';
			}
		}else{
  			$addltjobnum='0';
 		}
		
		$statis['integral_format']=number_format($statis['integral']);
		$statis['addltjobnum']=$addltjobnum;
		$this->yunset("addltjobnum",$addltjobnum);
        
		$this->yunset("statis",$statis);
		return $statis;
	}
	 
 	function get_com($type){
		$statis=$this->lt_satic();
		if($statis['rating_type']&&$statis['rating']){
			$data=array();
			if($type==1){
				if($statis['rating_type']=='1' && $statis['lt_job_num']>0 && ($statis['vip_etime']<1 || $statis['vip_etime']>=time())){
					$data="`lt_job_num`=`lt_job_num`-1";
				}elseif($statis['rating_type']=='2' && ($statis['vip_etime']>time() || $statis['vip_etime']=='0')){
					$value=null;
				}else{
					return "会员套餐已用完!";
				} 
			}elseif($type==3){
				if($statis['rating_type']=='1' && $statis['lt_breakjob_num']>0 && ($statis['vip_etime']<1 || $statis['vip_etime']>=time())){
					$data="`lt_breakjob_num`=`lt_breakjob_num`-1";
				}elseif($statis['rating_type']=='2' && ($statis['vip_etime']>time() || $statis['vip_etime']=='0')){
					$value=null;
				}else{
					return "会员套餐已用完!";
				} 
				
			}
			if($data){
				$this->obj->DB_update_all("lt_statis",$data,"`uid`='".$this->uid."'");
			}
		}else{
			return "会员已到期！";
		}
	}

	function jobadd_action(){
		include(CONFIG_PATH."db.data.php");		
		$this->yunset("arr_data",$arr_data);
		$CacheList=$this->MODEL('cache')->GetCache(array('lt','lthy','ltjob','city','com','hy'));
		$rows=$this->obj->DB_select_all("company_cert","`uid`='".$this->uid."' group by type order by id desc");
		foreach($rows as $v){
			$row[$v["type"]]=$v;
		}
		$info=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'","`com_name`,`email_status`,`moblie_status`,`yyzz_status`");
		
		if($info['com_name']==''){
			$data['msg']="请先完善基本资料！";
			$data['url']='index.php?c=info';
			$this->yunset("layer",$data);
		}
		$this->rightinfo();
		
		$statics = $this->lt_satic();
		if(!$_GET['id']){
			if($statics['addltjobnum']==0){ 
				$data['msg']="您的会员已到期！";
				$data['url']='index.php?c=rating';
				$this->yunset("layer",$data);
			}
			if($statics['addltjobnum']==2){ 
				if($this->config['integral_lt_job']!='0'){
					$data['msg']="您的套餐已用完！";
					$data['url']='index.php?c=rating';
					$this->yunset("layer",$data);
				}else{
					$this->obj->DB_update_all("company_statis","`lt_job_num` = '1'","`uid`='".$this->uid."'");
				}
			}
		}

		$msg=array();
		$isallow_addjob="1";
		if($this->config['lt_enforce_emailcert']=="1"){
			if($row['1']['status']!="1"){
				$isallow_addjob="0";
				$msg[]="邮箱认证";
			}
		}
		if($this->config['lt_enforce_mobilecert']=="1"){
			if($row['2']['status']!="1"){
				$isallow_addjob="0";
				$msg[]="手机认证";
			}
		}
		if($this->config['lt_enforce_licensecert']=="1"){
			if($row['4']['status']!="1"){
				$isallow_addjob="0";
				$msg[]="职业资格认证";
			}
		}
		$data['url']='index.php?c=set';
		if($isallow_addjob=="0"){
			$data['msg']="请先完成".implode(",",$msg)."！";
			$this->yunset("layer",$data);
		}
		if($_GET['id']){
			$row=$this->obj->DB_select_once("lt_job","`id`='".(int)$_GET['id']."' and `uid`='".$this->uid."'");
			$arr_data1=$arr_data['sex'][$row['sex']];		
		    $this->yunset("arr_data1",$arr_data1);
			if($row['id']){
				$row['constitutev']=$row['constitute'];
				if($row['constitute']!=""){
					$row['constitute']=@explode(",",$row['constitute']);
				}
				$row['welfarev']=$row['welfare'];
				if($row['welfare']!=""){
					$row['welfare']=@explode(",",$row['welfare']);
				}
				$row['languagev']=$row['language'];
				if($row['language']!=""){
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
				$row['job_desc_t'] = strip_tags($row['job_desc']);
				$row['eligible_t'] = strip_tags($row['eligible']);
				$row['other_t'] = strip_tags($row['other']);
				 
				$hyname=@implode(",",$hyname);
				$this->yunset("hyname",$hyname);
				$row['days']= ceil(($row['edate']-$row['sdate'])/86400);
				$this->yunset("row",$row);
			}else{
				$data['msg']='职位不存在！';
				$data['url']='index.php?c=job&s=1';
				$this->yunset("layer",$data);
			}
		}
		if($_POST['submit']){
			$_POST=$this->post_trim($_POST);
			$id=(int)$_POST['id'];
			$info=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'","`integral`");
			$_POST['desc'] = str_replace("&amp;","&",html_entity_decode($_POST['desc'],ENT_QUOTES));
			$data1['com_name']=$_POST['com_name'];
 			$data1['pr']=$_POST['pr'];
			$data1['hy']=$_POST['hy'];
			$data1['mun']=$_POST['mun'];
			$data1['desc']=$_POST['desc'];
			$data1['job_name']=$_POST['job_name'];
			$data1['department']=$_POST['department'];
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
				$_POST['lang'] = "";
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
			if($_POST['id']){
				$job=$this->obj->DB_select_once("lt_job","`id`='".$_POST['id']."' and `uid`='".$this->uid."'","`status`");
				$where['uid']=$this->uid;
				$where['id']=$_POST['id'];
				$id=$this->obj->update_once("lt_job",$data1,$where);
				if($id){
					$this->obj->member_log("更新猎头职位",10,2);
					$data['msg']='修改职位成功！';
					if($this->config['lt_job_status']=='1'){
						$data['url']='index.php?c=job&s=1';
					}else{
						$data['url']='index.php?c=job&s=0';
					}
				}else{
					$data['msg']='修改职位失败！';
				}
			}else{
				$data1['uid']=$this->uid;
				$data1['did']=$this->userdid;
				$data1['msg']=$this->get_com(1);
				$id=$this->obj->insert_into("lt_job",$data1);
				if($id){
					$state_content = "新发布了猎头职位 <a href=\"".$this->config['sy_weburl']."/lietou/index.php?c=jobshow&id=".$id."\" target=\"_blank\">".$_POST['job_name']."</a>。";
					$state['uid']=$this->uid;
					$state['content']=$state_content;
					$state['ctime']=time();
					$state['type']=2;
					$this->obj->insert_into("friend_state",$state);
					$this->obj->member_log("发布猎头职位",10,1);
					$data['msg']='发布职位成功！';
					if($this->config['lt_job_status']=='1'){
						$data['url']='index.php?c=job&s=1';
					}else{
						$data['url']='index.php?c=job&s=0';
					}
				}else{
					$data['msg']='发布职位失败！';
					
				}
			}
			echo json_encode($data);die;
		}
		
 		$this->yunset("today",date("Y-m-d"));
		$this->yunset($CacheList);
		$this->user_shell();
		$this->yunset('header_title',"职位发布");
		$this->waptpl('jobadd');
	}
	
	function binding_action(){
		if($_POST['moblie']){
			$row=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `check`='".$_POST['moblie']."'");
			if(!empty($row)){
				if($row['check2']!=$_POST['code']){
					echo 3;die;
				}
				
				$this->obj->DB_update_all("member","`moblie`=''","`moblie`='".$row['check']."'");
				$this->obj->DB_update_all("resume","`moblie_status`='0',`telphone`=''","`telphone`='".$row['check']."'");
				$this->obj->DB_update_all("company","`moblie_status`='0',`moblie`=''","`linktel`='".$row['check']."'");
				$this->obj->DB_update_all("lt_info","`moblie_status`='0',`moblie`=''","`moblie`='".$row['check']."'");
				$this->obj->DB_update_all("px_train","`moblie_status`='0',`linktel`=''","`linktel`='".$row['check']."'");
				
				$this->obj->DB_update_all("member","`moblie`='".$row['check']."'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("lt_info","`moblie`='".$row['check']."',`moblie_status`='1'","`uid`='".$this->uid."'");
				$this->obj->DB_update_all("company_cert","`status`='1'","`uid`='".$this->uid."' and `check2`='".$_POST['code']."'");
				$this->obj->member_log("手机绑定",13,1);
				$pay=$this->obj->DB_select_once("company_pay","`pay_remark`='手机绑定' and `com_id`='".$this->uid."'");
				if(empty($pay)){
					$this->MODEL('integral')->get_integral_action($this->uid,"integral_mobliecert","手机绑定");
				}
				echo 1;die;
			}else{
				echo 2;die;
			}
		}
		if($_GET['type']){
			if($_GET['type']=="moblie"){
				$this->obj->DB_update_all("lt_info","`moblie_status`='0'","`uid`='".$this->uid."'");
			}
			if($_GET['type']=="email"){
				$this->obj->DB_update_all("lt_info","`email_status`='0'","`uid`='".$this->uid."'");
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
		$lt=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		$this->yunset("lt",$lt);
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='4'");
		$this->yunset("cert",$cert);
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"社交账号绑定");
		$this->waptpl('binding');
	}
	
	function bindingbox_action(){
		$member=$this->obj->DB_select_once("member","`uid`='".$this->uid."'");
		$this->yunset("member",$member);
		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		
		if($_GET['type']=="email"){
			$this->yunset('header_title',"邮箱绑定");
		}else if($_GET['type']=="moblie"){
			$this->yunset('header_title',"手机认证");
		}
		$this->waptpl('bindingbox');
	}
	
	function ltcert_action(){
		if($_POST['submit']){
			$row=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='4'");
            if(!$_POST['preview']){
				$data['msg']='请上传职业资格证书！';
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
				if($this->config['lt_cert_status']=="1"){
					$sql['status']=0;
					$this->obj->DB_update_all("lt_info","`yyzz_status`='0'","`uid`='".$this->uid."'");
				}else{
					$sql['status']=1;
					$this->obj->DB_update_all("lt_info","`yyzz_status`='1'","`uid`='".$this->uid."'");
				}
				$sql['step']=1;
				$sql['check']=$photo;
				$sql['check2']="4";
				$sql['ctime']=mktime();
				$company=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='4'","`check`");
				if(is_array($company)){
					unlink_pic(APP_PATH.$company['check']);
					$where['uid']=$this->uid;
					$where['type']='4';
					$this->obj->update_once("company_cert",$sql,$where);
					$this->obj->member_log("更新职业资格证书",13,2);
				}else{
					$sql['uid']=$this->uid;
					$sql['did']=$this->userdid;
					$sql['type']='4';
					$this->obj->insert_into("company_cert",$sql);
					$this->obj->member_log("上传职业资格证书",13,1);
					if($this->config['lt_cert_status']=="0"){
						$uid=$this->uid;
						$ulen=9-strlen($uid);
						for($a=1;$a<$ulen;$a++){
							$uid="0".$uid;
						}
						$data['rzid']="YLT".$uid;
						$this->obj->update_once("lt_info",$data,array("uid"=>$uid));
						$this->MODEL('integral')->get_integral_action($this->uid,"integral_ltcert","猎头执照认证");
					}
				}
				$data['msg']='上传职业资格证书成功！';
				$data['url']='index.php?c=ltcert';
			}else{
				$data['msg']=$data['msg'];
				$data['url']='index.php?c=ltcert';
			}
			$this->yunset("layer",$data);
		}
		$company=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and `type`='4'","`check`,`status`,`statusbody`");
		if($cert['check']){
		    $cert['old_check']=str_replace('./data','/data',$cert['check']);
		}
		$this->yunset("company",$company);
		$this->yunset("cert",$cert);
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"资格证书");
		$this->waptpl('ltcert');
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
			$data['url']='index.php?c=binding';
			$this->yunset("layer",$data);
		}
		$this->rightinfo();
		$backurl=Url('wap',array('c'=>'set'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"修改用户名");
		$this->waptpl('setname');
	}


	
	function look_resume_action(){
		$where="a.`com_id`='".$this->uid."' and a.`resume_id`=b.`id`";
		$this->resume("look_resume",$where);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"浏览的简历");
		$this->waptpl('look_resume');
	}
	function lookdel_action(){
		if($_GET['del']){
			$delid=(int)$_GET['del'];
			$nid=$this->obj->DB_delete_all("look_resume","`com_id`='".$this->uid."' and `resume_id` in (".$delid.")","");
			if($nid){
				$this->obj->member_log("删除浏览过的简历",26,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
	
	function down_resume_action(){
		$where="a.`comid`='".$this->uid."' and a.`eid`=b.`id` and b.`height_status`='2'";
		$this->resume("down_resume",$where);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"下载的简历");
		$this->waptpl('down_resume');
	}
	function downdel_action(){
		if($_GET['del']){
 			$delid=(int)$_GET['del'];
			$nid=$this->obj->DB_delete_all("down_resume","`comid`='".$this->uid."' and `eid` in (".$delid.")","");
 			if($nid){
 				$this->obj->member_log("删除下载的简历",3,3);
 				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
 		}
	}
	
	function yp_resume_action(){
		$where="a.`com_id`='".$this->uid."' and a.`eid`=b.`id`";
		$this->resume("userid_job",$where);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"应聘简历");
		$this->waptpl('yp_resume');
	}
	function ypdel_action(){
		if($_GET['del']){
 			$delid=(int)$_GET['del'];
			$nid=$this->obj->DB_delete_all("userid_job","`com_id`='".$this->uid."' and `eid` in (".$delid.")","");
 			if($nid){
 				$this->obj->member_log("删除应聘来的简历",6,3);
 				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
 		}
	}
	
	function entrust_resume_action(){
		$where="a.`lt_uid`='".$this->uid."' and a.`uid`=b.`uid`";
		$delwhere="`lt_uid`='".$this->uid."' and `id`='".(int)$_GET['del']."'";
		$this->resume("entrust",$where,$delwhere,"委托来的简历");
		
		$this->obj->DB_update_all("entrust","`remind_status`='1'","`lt_uid`='".$this->uid."' and `remind_status`='0'");
		
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"委托简历");
		$this->waptpl('entrust_resume');
	}
	function entrustdel_action(){
		if($_GET['del']){
			$delid=(int)$_GET['del'];	
			$nid=$this->obj->DB_delete_all("entrust","`lt_uid`='".$this->uid."' and `uid` in (".$delid.")","");
			if($nid){
				$this->obj->member_log("删除委托的简历",6,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
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
		
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'","integral");
		$statis[integral]=number_format($statis[integral]);
		$this->yunset("statis",$statis);
		$this->yunset('rows',$rows);
		$backurl=Url('wap',array('c'=>'integral','type'=>1),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->yunset('header_title',"兑换记录");
		$this->waptpl('reward_list');
	}
	
	function rewarddel_action(){
		if($this->usertype!='3' || $this->uid==''){
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
	
	function rating_action(){
		$lt=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		$this->yunset("lt",$lt);
		
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
		
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($this->config['com_vip_type'] == 2){
			$where = '`type` = 1 ';
		}else if($this->config['com_vip_type'] == 1){
			$where = '`type` = 2';
		}else{
			
			$where = '`type` = 1 ';
		}
		
		$row=$this->obj->DB_select_once("company_rating","`category`='2' and `service_price` > 0 and `display`='1' and `type`='1' order by `type` asc,`sort` desc");
		$this->yunset("row",$row);
		
		$rows=$this->obj->DB_select_all("company_rating","`category`='2' and `service_price` > 0 and `display`='1' and `type`='1' order by `type` asc,`sort` desc");
		if (is_array($rows)&&$rows){
			foreach ($rows as $v){
				$couponid[]=$v['coupon'];
			}
			if(empty($coupon)){
				$coupon=$this->obj->DB_select_all("coupon","`id` in (".@implode(",",$couponid).")","`id`,`name`");
			}
			if (is_array($coupon)){
				foreach ($rows as $k=>$v){
					foreach ($coupon as $val){
						if ($v['coupon']==$val['id']){
							$rows[$k]['couponnmae']=$val['name'];
						}
					}
				}
			}
		}
		if($rows&&is_array($rows)){
			foreach ($rows as $k=>$v){
				$rname=array();
				if($v['lt_job_num']>0){$rname[]='猎头发布职位数:'.$v['lt_job_num'].'份';}
				if($v['lt_breakjob_num']>0){$rname[]='猎头刷新职位数:'.$v['lt_breakjob_num'].'份';}
				if($v['lt_resume']>0){$rname[]='猎头下载简历数:'.$v['lt_resume'].'份';}
				$rows[$k]['rname']=@implode('+',$rname);
			}
		}
		$this->yunset("rows",$rows);
		$this->yunset("js_def",4);
		
		
		
		
		$this->user_shell();
		$this->yunset('header_title',"会员套餐");
		if($this->config['com_vip_type'] == 2 || $this->config['com_vip_type'] == 0){
			$this->waptpl('lietou_rating');
		}else if($this->config['com_vip_type'] == 1){
			$this->waptpl('lietou_time');
		}
	}
	
	
	function time_action(){
		$lt=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		$this->yunset("lt",$lt);
		
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
		
		$banks=$this->obj->DB_select_all("bank");
		$this->yunset("banks",$banks);
		
		$coupons=$this->obj->DB_select_all("coupon_list","`uid`='".$this->uid."' and `validity`>'".time()."' and `status`='1'");
		$this->yunset("coupons",$coupons);
		
		if($this->config['com_vip_type'] == 2){
			$where = '`type` = 1 ';
		}else if($this->config['com_vip_type'] == 1){
			$where = '`type` = 2';
		}else{
			
			$where = '`type` = 1 ';
		}
		
		$row=$this->obj->DB_select_once("company_rating","`category`='2' and `display`='1' and `type`='2' order by `type` asc,`sort` desc");
		$this->yunset("row",$row);
		
		$rows=$this->obj->DB_select_all("company_rating","`category`='2' and `display`='1'and `type`='2' order by `type` asc,`sort` desc");
		
		if (is_array($rows) && $rows){
			foreach ($rows as $v){
				$couponid[]=$v['coupon'];
			}
			if(empty($coupon)){
				$coupon=$this->obj->DB_select_all("coupon","`id` in (".@implode(",",$couponid).")","`id`,`name`");
			}
			if (is_array($coupon)){
				foreach ($rows as $k=>$v){
					foreach ($coupon as $val){
						if ($v['coupon']==$val['id']){
							$rows[$k]['couponnmae']=$val['name'];
						}
					}
				}
			}
		}
		
		if ($rows&&is_array($rows)){
			foreach ($rows as $k=>$v){
				$rname=array();
				if($v['lt_job_num']>0){$rname[]='猎头发布职位数:'.$v['lt_job_num'].'份';}
 				if($v['lt_breakjob_num']>0){$rname[]='猎头刷新职位数:'.$v['lt_breakjob_num'].'份';}
				if($v['lt_resume']>0){$rname[]='猎头下载简历数:'.$v['lt_resume'].'份';}
				$rows[$k]['rname']=@implode('+',$rname);
			}
		}
		
		$this->yunset("rows",$rows);
		


		$this->yunset("js_def",4);
		$this->user_shell();
		$this->yunset('header_title',"会员套餐");
		$this->waptpl('lietou_time');
	}
	
	function mypay_action(){
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$statis['integral_format']=number_format($statis['integral']);
		$this->yunset("statis",$statis);
		$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->user_shell();
		$this->waptpl('mypay');
	}
	function give_rebates_action(){
		$urlarr=array("c"=>"give_rebates","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("rebates","`job_uid`='".$this->uid."' order by id desc",$pageurl,"10");
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
		$this->yunset('header_title',"推荐给我的人");
		$this->user_shell();
		$this->yunset('header_title',"推荐给我的人才");
		$this->waptpl('give_rebates');
	}
	function save_give_rebates_action(){
		if($_POST){
			$data['reply']=$_POST['reply'];
			$data['reply_time']=time();
			$data['status']=1;
			$where['id']=(int)$_POST['id'];
			$where['job_uid']=$this->uid;
			$this->obj->update_once("rebates",$data,$where);
			$this->obj->member_log("回复推荐给我的返利",18,2);
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
	function my_rebates_action(){
		$urlarr=array("c"=>"my_rebates","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("rebates","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
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
						if($val['usertype']==2){
							$rows[$k]['type']=2;
						}else{
							$rows[$k]['type']=3;
						}
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'resumecolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"我推荐的悬赏");
		
		$this->user_shell();
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
		if(intval($_GET['id'])){
			if(!intval($_GET['type'])){
				$this->obj->DB_update_all("rebates","`status`='1'","`id`='".intval($_GET['id'])."'");
			}
			include(CONFIG_PATH."db.data.php");
			$CacheList=$this->MODEL('cache')->GetCache(array('user','hy','job','city'));
			$rebate=$this->obj->DB_select_once("rebates","`id`='".intval($_GET['id'])."'");
			$resume=$this->obj->DB_select_once("temporary_resume","`rid`='".intval($_GET['id'])."'");
			$resume['sex']=$arr_data['sex'][$resume['sex']];
			if($resume['job_classid']){
				$jobids=@explode(',',$resume['job_classid']);
				foreach($jobids as $val){
					$jobname[]=$CacheList['job_name'][$val];
				}
				$resume['jobname']=@implode('、',$jobname);
			}
			if($CacheList['city_name'][$resume['three_cityid']]){
				$resume['city']=$CacheList['city_name'][$resume['provinceid']].'-'.$CacheList['city_name'][$resume['cityid']].'-'.$CacheList['city_name'][$resume['three_cityid']];
			}elseif($CacheList['city_name'][$resume['cityid']]){
				$resume['city']=$CacheList['city_name'][$resume['provinceid']].'-'.$CacheList['city_name'][$resume['cityid']];
			}elseif($CacheList['city_name'][$resume['provinceid']]){
				$resume['city']=$CacheList['city_name'][$resume['provinceid']];
			}
			
			if($resume['minsalary']){
				if($resume['maxsalary']){
					$resume['rsalary']='￥'.$resume['minsalary'].'-'.$resume['maxsalary'].'万/年';
				}else{
					$resume['rsalary']='￥'.$resume['minsalary'].'万/年以上';
				}
			}else{
				$resume['rsalary']='面议';
			}
		}
		$this->yunset($CacheList);
		$this->yunset("rebate",$rebate);
		$this->yunset("resume",$resume);
		if($_GET['type']){
			$backurl=Url('wap',array('c'=>'my_rebates'),'member');
		}else{
			$backurl=Url('wap',array('c'=>'give_rebates'),'member');
		}
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"人才信息");
		$this->user_shell();
		$this->waptpl('rebateshow');
	}
	function paylog_action(){
		include(CONFIG_PATH."db.data.php");
		$this->yunset("arr_data",$arr_data);
		$urlarr=array("c"=>"paylog","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`uid`='".$this->uid."' and `order_price`> 0  order by order_time desc";
		
		$rows=$this->get_page("company_order",$where,$pageurl,"10");
		
		if($rows&&is_array($rows)){
			foreach($rows as $key=>$val){
				$rows[$key]['sname']=$arr_data['paystate'][$val['order_state']];
				$rows[$key]['type']=$arr_data['pay'][$val['order_type']];
			}
		}
		$this->yunset("rows",$rows);
		$this->user_shell();
		$this->yunset('header_title',"订单管理");
		$this->waptpl('paylog');
	}
	function delpaylog_action(){
		if($this->usertype!='3' || $this->uid==''){
			$this->waplayer_msg('登录超时！');
		}else{
			$oid=$this->obj->DB_select_once("company_order","`uid`='".$this->uid."' and `id`='".(int)$_GET['id']."' and `order_state`='1'");
			if(empty($oid)){
				$this->waplayer_msg('订单不存在！');
			}else{
				$this->obj->DB_delete_all("company_order","`id`='".$oid['id']."' and `uid`='".$this->uid."'");
				$this->obj->DB_delete_all("invoice_record","`oid`='".$oid['id']."'  and `uid`='".$this->uid."'");
				$this->waplayer_msg('取消成功！');
			}
		}
	}
	function consume_action(){
		$urlarr=array("c"=>"consume","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$where="`com_id`='".$this->uid."' order by pay_time desc";
		$rows = $this->get_page("company_pay",$where,$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $k=>$v){
				$rows[$k]['pay_time']=date("Y-m-d H:i:s",$v['pay_time']);
				$rows[$k]['order_price']=str_replace(".00","",$rows[$k]['order_price']);
			}
		}
		
		$this->yunset("rows",$rows);
		$this->user_shell();
		$this->yunset('header_title',"财务明细");
		$this->waptpl('consume');
	}
	
	function integral_reduce_action(){
		$backurl=Url('wap',array('c'=>'integral'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"消费规则");
		$this->waptpl('integral_reduce');
	}
	function loglist_action(){
		
		$userM  = $this->MODEL('userinfo');
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>3));
		
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("company_job_sharelog","`uid`='".$this->uid."' order by time desc",$pageurl,"10");

		$this->yunset("rows",$rows);
		$statis['packpay'] = sprintf("%.2f", $statis['packpay']);
		$statis['freeze'] = sprintf("%.2f", $statis['freeze']);
		$this->yunset("statis",$statis);
		$this->yunset('header_title',"赏金收益明细");
		$backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		$this->waptpl('loglist');
	}
	function change_action(){
		$this->yunset('header_title',"赏金转换积分");
		$userM=$this->MODEL('userinfo');
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>3));
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
				$nid=$this->obj->DB_update_all("lt_statis","`packpay`=`packpay`-'".$changeprice."'","`uid`='".$this->uid."'");
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
		$backurl=Url('wap',array('c'=>'loglist'),'member');
		$this->yunset('backurl',$backurl);
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
		$statis=$userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>3));
		$this->yunset("statis",$statis);
		$this->waptpl('changelist');
	}
	
	function withdraw_action(){
		
		$this->yunset('header_title',"提现");
		if($_POST){

			$M			=	$this->MODEL('pack');
			
			 $return	=  $M->withDraw($this->uid,$this->usertype,$_POST['price'],$_POST['real_name']);
				
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
			$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>3));
			$statis['packpay'] = sprintf("%.2f", $statis['packpay']);
			$statis['freeze'] = sprintf("%.2f", $statis['freeze']);
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
		$statis = $userM->GetUserstatisOne(array('uid'=>$this->uid),array('usertype'=>3));

		$this->yunset("statis",$statis);
		$this->yunset("rows",$rows);
		$this->yunset('header_title',"提现明细");
		$this->waptpl('withdrawlist');
	}
	function rewardlog_action(){	

		$urlarr=array('c'=>'rewardlog',"page"=>"{{page}}");
		$where="`uid`='".$this->uid."' ";
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
			$joblist = $this->obj->DB_select_all("company_job","`id` IN (".@implode(',',$jobids).")");
			
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
				
				
				
					$rows[$k]['log'] = $M->getStatusInfo($v['id'],1,$v['status'],$logList[$v['id']]);
				
				
				foreach($joblist as $val){
					if($v['jobid']==$val['id']){
						$rows[$k]['name']=$val['name'];
					}
				}
				if(is_array($ulist)){
					foreach($ulist as $val){
						if($v['eid']==$val['id']){
							$rows[$k]['uname']=$val['uname'];
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
		
		$this->yunset("rows",$rows);
		$backurl=Url('wap',array('c'=>'jobcolumn'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"赏金职位");
		$this->waptpl('jobrewardlog');
	}
	
	function logstatus_action(){
		if($_POST){
				
			 $M			=	$this->MODEL('pack');
			 $return	=  $M->logStatus((int)$_POST['rewardid'],(int)$_POST['status'],$this->uid,'1',$_POST);
				
			 if($return['error']==''){
				
				 echo json_encode(array('error'=>'ok'));
					
			 }else{
				 
				 
				 echo json_encode(array('error'=>$return['error']));
			 }
		}

	
	}
	function arb_action(){
		$this->yunset('headertitle',"申请仲裁");
		if($_POST){

			if(!$_POST['rewardid']){
				$this->ACT_layer_msg("请选择需要仲裁的赏单！",8,$_SERVER['HTTP_REFERER']);
			}
			if(!$_POST['content']){
				$this->ACT_layer_msg("请填写仲裁原因！",8,$_SERVER['HTTP_REFERER']);
			}else{
				$data['content'] = $_POST['content'];
			}

			
			if (is_uploaded_file($_FILES['arbpic']['tmp_name'])) {
				$UploadM=$this->MODEL('upload');
				$upload=$UploadM->Upload_pic("../data/upload/pack/".$this->uid.'/',false);
				$arbpic=$upload->picture($_FILES['arbpic']);
				
				$picmsg=$UploadM->picmsg($arbpic,$_SERVER['HTTP_REFERER']);
				if($picmsg['status'] == $arbpic){
					$this->ACT_layer_msg($picmsg['msg'],8);
				}
				$arbpic = str_replace("../data/","./data/",$arbpic);
				$data['arbpic'] = $arbpic;
			}
			
			 $M			=	$this->MODEL('pack');

			 $return	=  $M->logStatus((int)$_POST['rewardid'],26,$this->uid,'1',$data);
				
			 if($return['error']==''){
				
				$data['msg']='仲裁提交成功！';
				$data['url']='index.php?c=rewardlog';
			
				$this->yunset("layer",$data);
					
			 }else{
				 
				 $data['msg']=$return['error'];
				$data['url']='index.php?c=rewardlog';
			
				$this->yunset("layer",$data);
			 }
		}elseif($_GET['rewardid']){
		
			
			
		}
	
		$this->waptpl('jobrewardarb');
	}

	function talent_action(){
		
		$urlarr=array("c"=>"talent","page"=>"{{page}}");
		$pageurl=Url('member',$urlarr);
		$rows=$this->get_page("lt_talent","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
		
		if(is_array($rows)){
			foreach($rows as $key=>$value){
				$id[] = $value['id'];
			}
			
			$rewardList = $this->obj->DB_select_all('company_job_rewardlist',"`eid` IN (".pylode(',',$id).") AND `status` NOT IN ('18','19','20','21','23','26','27','28','29')");
			if(is_array($rewardList)){ 
				foreach($rewardList as $key=>$value){
					$rewardStatusId[] = $value['eid'];
				}
				foreach($rows as $key=>$value){
					if(in_array($value['id'],$rewardStatusId)){
						$rows[$key]['rewardstatus'] = '1';
					}
				}
			}
			
		}
		$this->yunset("rows",$rows);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','user')));
		$this->yunset('header_title',"简历库");
		$this->waptpl('talent');
	}

	function talentexpect_action(){
		
		$talentM = $this->MODEL('talent');

		if($_GET['id']){
			$expectInfo = $talentM->getTalent($this->uid,$_GET['id']);
			
			$this->yunset("resume",$expectInfo);
			
		}
		$this->yunset($this->MODEL('cache')->GetCache(array('city','user','hy')));
		
		include(CONFIG_PATH."db.data.php");
		unset($arr_data['sex'][3]);
		$this->yunset("arr_data",$arr_data);
		$this->yunset('header_title',"充实简历库");
		$this->waptpl('talent_expect');
		
	}
	function savetalentexpect_action(){

		if($_POST){
			$talentM = $this->MODEL('talent');
			$return  = $talentM->addTalent($_POST);
			echo json_encode($return);
		}
	
	}
	function talentdel_action(){
		if($_GET['id']){
			$del=(int)$_GET['id'];
			$this->obj->DB_delete_all("temporary_resume","`rid`='".$del."'","");
			$nid=$this->obj->DB_delete_all("rebates","`id`='".$del."' and `uid`='".$this->uid."'","");
			$this->obj->member_log("删除我推荐的人才",25,3);
			$nid?$this->layer_msg('删除成功！',9,0,"index.php?c=my_rebates"):$this->layer_msg('删除失败！',8,0,"index.php?c=my_rebates");
		}
	}

	function telstatus_action(){

		$talentM = $this->MODEL('talent');

		if($_GET['id']){

			$Info = $talentM->getTalent($this->uid,$_GET['id']);
			$this->yunset("Info",$Info);
			$this->yunset('header_title',"简历库授权认证");
			$this->waptpl('telstatus');
		}elseif($_POST['id'] && $_POST['linktel'] && $_POST['code']){

			$return  = $talentM->telStatus($_POST['id'],$_POST['linktel'],$_POST['code']);
			if($return['error']=='1'){

				$this->obj->member_log("简历库授权认证",13,1);
			}
			echo json_encode($return);
		}
	}
	
	
	function talentreward_action(){
		
		
		$packM = $this->MODEL('pack');
		$job = $packM->getRewardJob((int)$_GET['jobid'],'1');
		$job['money']=floatval($job['money']);
		$job['sqmoney']=floatval($job['sqmoney']);
		$job['offermoney']=floatval($job['offermoney']);
		$job['invitemoney']=floatval($job['invitemoney']);
		$this->yunset('job',$job);
		
		$urlarr=array("c"=>"talent","page"=>"{{page}}");
		$pageurl=Url('member',$urlarr);
		$rows=$this->get_page("lt_talent","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
		
		if(is_array($rows)){
			foreach($rows as $key=>$value){
				$id[] = $value['id'];
			}
			
			$rewardList = $this->obj->DB_select_all('company_job_rewardlist',"`eid` IN (".pylode(',',$id).") AND `status` NOT IN ('18','19','20','21','23','26','27','28','29')");
			if(is_array($rewardList)){ 
				foreach($rewardList as $key=>$value){
					$rewardStatusId[] = $value['eid'];
				}
				foreach($rows as $key=>$value){
					if(in_array($value['id'],$rewardStatusId)){
						$rows[$key]['rewardstatus'] = '1';
					}
				}
			}
		}
		$this->yunset("rows",$rows);
		
		$CacheM = $this->MODEL('cache');
		$CacheList=$CacheM->GetCache(array('com'));
        $this->yunset($CacheList);
		$this->yunset($this->MODEL('cache')->GetCache(array('city','user')));
		$this->yunset('header_title',"赏金投递");
		$this->waptpl('talentreward');
	}
	
	function talentsqjob_action(){	
		
		
		$packM = $this->MODEL('pack');
	
		$return  = $packM->sqRewardJob($_POST['jobid'],$this->uid,$this->usertype,$_POST['eid']);
		
		echo json_encode($return);
	}
	 
	function sysnews_action(){	
		
		$userid_job=$this->obj->DB_select_once("userid_job","`com_id`='".$this->uid."' and `is_browse`='1' order by datetime desc","`job_name`,`uid`,`datetime`");
 		$resume=$this->obj->DB_select_once("resume","`uid`='".$userid_job['uid']."'","`name`");
 		$userid_job['name'] = $resume['name'];
 		$this->yunset('userid',$userid_job);
 		$userid_jobnum=$this->obj->DB_select_num("userid_job","`com_id`='".$this->uid."'and `is_browse`='1'");
 		$this->yunset('userid_jobnum',$userid_jobnum);
		
		$jobrows=$this->obj->DB_select_once("msg","`job_uid`='".$this->uid."' and `del_status`<>'1' order by datetime desc");
		$this->yunset('jobrows',$jobrows);
		$jobnum=$this->obj->DB_select_num("msg","`job_uid`='".$this->uid."' and `com_remind_status`='0'");
		$this->yunset('jobnum',$jobnum);
		
		$entrust=$this->obj->DB_select_once("entrust","`lt_uid`='".$this->uid."'order by datetime desc","`uid`,`datetime`");
		$resume=$this->obj->DB_select_once("resume","`uid`='".$entrust['uid']."'","`name`");
		$entrust['name'] = $resume['name'];
		$this->yunset('entrust',$entrust);
		$entrustnum=$this->obj->DB_select_num("entrust","`lt_uid`='".$this->uid."' and `remind_status`='0'");
		$this->yunset('entrustnum',$entrustnum);
		
		$sxrows=$this->obj->DB_select_once("sysmsg","`fa_uid`='".$this->uid."' order by ctime desc");
		$this->yunset("sxrows",$sxrows);
		$sxnum=$this->obj->DB_select_num("sysmsg","`fa_uid`='".$this->uid."'and `remind_status`='0'");
		$this->yunset('sxnum',$sxnum);
		
    	$backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"系统消息");
		$this->waptpl('sysnews');
	}
	
	function msg_action(){
		$urlarr=array("c"=>"msg","page"=>"{{page}}");
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("msg","`job_uid`='".$this->uid."' order by id desc",$pageurl,"10");
		if(is_array($rows)){
			foreach($rows as $v){
				$uid[]=$v['uid'];			
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
			$nid = $this->obj->DB_delete_all("msg","`id` = '".$_GET['id']."' and `job_uid`='".$this->uid."'");
			if($nid){				
				$this->obj->member_log("删除求职咨询",18,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}		
	}
	
	function jobcolumn_action(){
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("sysmsg","`fa_uid`='".$this->uid."' order by `id` desc",$pageurl,"13");
        $backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"职位管理");
		$this->waptpl('jobcolumn');
	}
	
	function set_action(){
		$lt=$this->obj->DB_select_once("lt_info","`uid`='".$this->uid."'");
		$this->yunset("lt",$lt);
		$cert=$this->obj->DB_select_once("company_cert","`uid`='".$this->uid."' and type='4'");
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
	
	function finance_action(){
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
        $backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"财务管理");
		$this->waptpl('finance');
	}
	
	function coupon_list_action(){
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("coupon_list","`uid`='".$this->uid."' order by id desc",$pageurl,"10");
		$this->yunset('rows',$rows);
		$backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"优惠卡券");
		$this->waptpl('coupon_list');
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
		
	function integral_action(){
		$signin			    = false;	
		$baseInfo			= false;	
		$photo				= false;	
		$emailChecked		= false;	
		$phoneChecked		= false;	
		$pay_remark         =false;
		$question        	=false;		
		$answer       		=false;		
		$answerpl           =false;		
		
		$yyzz				= false;	
		
		$row = $this->obj->DB_select_once("lt_info",'`uid` = '.$this->uid,
			"`realname`,`com_name`,
			`photo`,`email_status`,`moblie_status`,
			`yyzz_status`");
		
		if(is_array($row) && !empty($row)){
			if($row['realname'] != '' && $row['com_name'] != '' )
				$baseInfo = true;
			
			if($row['photo'] != '') $photo = true;
			if($row['email_status'] != 0) $emailChecked = true;
			if($row['moblie_status'] != 0) $phoneChecked = true;
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
			'signin'		=>$signin,
			'photo'			=>$photo,
			'emailChecked'	=>$emailChecked,
			'phoneChecked'	=>$phoneChecked,
			'pay_remark'	=>$pay_remark,
			'question'	    =>$question,
			'answer'	    =>$answer,
			'answerpl'	    =>$answerpl,
			'yyzz'			=> $yyzz	
		);
		$this->yunset("statusList",$statusList);
        
        $statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'","`integral`");
		$this->yunset("statis",$statis);
		
		$reg_url = Url('wap',array('c'=>'register','uid'=>$this->uid));
		$this->yunset('reg_url', $reg_url);
		
        $backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"积分管理");
		$this->waptpl('integral');
	}
	
	function com_action(){
		$statis=$this->obj->DB_select_once("lt_statis","`uid`='".$this->uid."'");
		$this->yunset("statis",$statis);
		$backurl=Url('wap',array('c'=>'finance'),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"我的会员");
		$this->waptpl('com');
	}
	
	
	function resumecolumn_action(){
		
		$yp_resume=$this->obj->DB_select_num("userid_job","`com_id`='".$this->uid."'");
 		$this->yunset("yp_resume",$yp_resume);
		
		
		$entrust_resume=$this->obj->DB_select_num("entrust","`lt_uid`='".$this->uid."'");
 		$this->yunset("entrust_resume",$entrust_resume);
		
		$entrust_resumeno=$this->obj->DB_select_num("entrust","`lt_uid`='".$this->uid."' and `remind_status`='0'");
 		$this->yunset("entrust_resumeno",$entrust_resumeno);
		
		$down_resume=$this->obj->DB_select_num("down_resume","`comid`='".$this->uid."'");
 		$this->yunset("down_resume",$down_resume);
		
		$look_resume=$this->obj->DB_select_num("look_resume","`com_id`='".$this->uid."'");
 		$this->yunset("look_resume",$look_resume);
		
		$talent=$this->obj->DB_select_num("lt_talent","`uid`='".$this->uid."'");
 		$this->yunset("talent",$talent);
		
		$my_rebates=$this->obj->DB_select_num("rebates","`uid`='".$this->uid."'");
 		$this->yunset("my_rebates",$my_rebates);
		
		$my_rebatesno=$this->obj->DB_select_num("rebates","`uid`='".$this->uid."' and `status`<>0");
 		$this->yunset("my_rebatesno",$my_rebatesno);
		
		$give_rebates=$this->obj->DB_select_num("rebates","`job_uid`='".$this->uid."'");
 		$this->yunset("give_rebates",$give_rebates);
		
		$give_rebatesno=$this->obj->DB_select_num("rebates","`job_uid`='".$this->uid."' and `status`=0");
 		$this->yunset("c",$give_rebatesno);
		
        $backurl=Url('wap',array(),'member');
		$this->yunset('backurl',$backurl);
		$this->yunset('header_title',"简历管理");
		$this->waptpl('resumecolumn');
	}
	
	
	function sxnews_action(){
		$urlarr['c']=$_GET['c'];
		$urlarr["page"]="{{page}}";
		$pageurl=Url('wap',$urlarr,'member');
		$rows=$this->get_page("sysmsg","`fa_uid`='".$this->uid."' order by `id` desc",$pageurl,"13");
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
		if($_GET['id']){
			$nid = $this->obj->DB_delete_all("sysmsg","`id` = '".$_GET['id']."' and `fa_uid`='".$this->uid."'");
			if($nid){
				$this->obj->member_log("删除系统消息",18,3);
				$this->waplayer_msg('删除成功！');
			}else{
				$this->waplayer_msg('删除失败！');
			}
		}
	}
}
?>