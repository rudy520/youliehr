function layer_del(msg,url){ 
	if(msg==''){ 
		layerload('执行中，请稍候...');
		$.get(url,function(data){ 
			layer.closeAll();
			var data=eval('('+data+')');
			if(data.url=='1'){ 
				layermsg(data.msg,Number(data.tm),function(){location.reload();});return false;
			}else{
				layermsg(data.msg,Number(data.tm),function(){location.href=data.url;});return false;
			}
		});
	}else{
		layer.open({
			content: msg,
			btn: ['确认', '取消'],
			shadeClose: false,
			yes: function(){
				layer.closeAll();
				layerload('执行中，请稍候...');
				$.get(url,function(data){
					layer.closeAll();
					var data=eval('('+data+')');
					var lasturl=$("#lasturl").val();
					if(data.url=='1'){ 
						layermsg(data.msg,Number(data.tm),function(){location.reload();});return false;
					}else{
						if(lasturl){
							layermsg(data.msg,Number(data.tm),function(){location.href=lasturl;});return false;
						}else{
							layermsg(data.msg,Number(data.tm),function(){location.href=data.url;});return false;
						}
					}
				});
			} 
		}); 
	}
}
function islayer(){
	if($.trim($("#layermsg").val())){
		var msg=$.trim($("#layermsg").val());
		var url=$.trim($("#layerurl").val());
        if(msg){
		    if(url){
			    layermsg(msg,2,function(){location.href=url;});
		    }else{
			    layermsg(msg);
		    } 
	    }
	} 
}
function layermsg(content,time,end){ 
	layer.open({
		content: content, 
		time: time === undefined ? 2 : time,
		end: end
	});
	return false;
}
function layeralert(title,content,time,end){ 
	layer.open({
		title: [title,'background-color:#0099CC; color:#fff;'],
		content: content, 
		time: time === undefined ? 2 : time,
		end:end===undefined?'':function(){location.href = end;}
	});
}
function layerload(msg){
	layer.open({
		type: 2,
		content: msg
	});
};
function layeropen(div,title){
	var content=$("#"+div).html();
	if(content){
		$("#"+div).html('');
		layer.open({ 
			title: title,
			type:1,
			content: content,
			end:function(){
				$("#"+div).html(content);
			}
		});
	}
}
function showDetail(url){
	window.location.href =url;
}
function statusdiv(id,title){
	$("#status"+id).attr('checked',true);
	layeropen("status_div",title); 
}
function lockdiv(id,title){
	$("#status"+id).attr('checked',true);
	layeropen("lock_div",title); 
}
function checkStatus(id,url){
	var status=$("input[name='status']:checked").val();
	var statusbody=$.trim($("textarea[name='statusbody']").val());
	var pytoken=$("#pytoken").val();
	var lasturl=$("#lasturl").val();
	if(!status){
		layermsg('请选择操作');return false;
	}
	var layerIndex=layer.open({
		type: 2,
		content: '执行中'
	});
	$.post(url,{id:id,status:status,statusbody:statusbody,pytoken:pytoken,lasturl:lasturl},function(data){
		layer.closeAll();
		var data=JSON.parse(data);
		if(data.url=='1'){
			layermsg(data.msg,Number(data.tm),function(){location.reload();});return false;
		}else if(url==''){
			layermsg(data.msg);return false;
		}else{
			layermsg(data.msg,Number(data.tm),function(){location.href=data.url;});return false;
		} 
	});
}
function uprating(id){
	var pytoken=$("#pytoken").val();
	if(id){
		$.post("index.php?c=admin_company_rating&a=getrating",{id:id,pytoken:pytoken},function(data){
			if(data){
				var dataJson = eval("(" + data + ")"); 
				$('#lt_job_num').val(dataJson.lt_job_num);
				$('#lt_down_resume').val(dataJson.lt_resume);
				$('#lt_editjob_num').val(dataJson.lt_editjob_num);
				$('#lt_breakjob_num').val(dataJson.lt_breakjob_num);
				$('#job_num').val(dataJson.job_num);
				$('#down_resume').val(dataJson.resume);
				$('#editjob_num').val(dataJson.editjob_num);
				$('#invite_resume').val(dataJson.interview);
				$('#breakjob_num').val(dataJson.breakjob_num);
				$('#part_num').val(dataJson.part_num);
				$('#editpart_num').val(dataJson.editpart_num);
				$('#breakpart_num').val(dataJson.breakpart_num);
				$('#zph_num').val(dataJson.zph_num);
				$('#vipetime').val(dataJson.vipetime);
				$('#oldetime').val(dataJson.oldetime);
				$('#rating_type').val(dataJson.type);
				var ratingname = $("#ratingid").find("option:selected").text();
				$('#rating_name').val(ratingname);
			}
		});
	}
}
function resetpw(uname,uid){
	var pytoken = $('#pytoken').val();
	layer.open({
			content: '确定要重置密码吗？',
			btn: ['确认', '取消'],
			shadeClose: false,
			yes: function(){
				layer.closeAll();
				layerload('执行中，请稍候...');
				$.get("index.php?c=user_member&a=reset_pw&uid="+uid+"&pytoken="+pytoken,function(data){
					layer.closeAll();
					layeralert('密码重置',"用户："+uname+" 密码已经重置为123456！",'10');
				});
			} 
		}); 
}
function isjsMobile(obj) {
    var reg= /^[1][3456789]\d{9}$/;   
	
    if (obj.length != 11) return false;
    else if (!reg.test(obj)) return false;
    else if (isNaN(obj)) return false;
    else return true;
}



$(document).ready(function(){	
	$(".wapadmin_select").change(function(){
		var wapadmin_select=$(this).val();
		var lid=$(this).attr("lid");
		if(wapadmin_select==""){
			$("#"+lid+" option").remove()
			$("<option value='0'>请选择城市</option>").appendTo("#"+lid);
			lid2=$("#"+lid).attr("lid");
			if(lid2){
				$("#"+lid2+" option").remove();
				$("<option value='0'>请选择城市</option>").appendTo("#"+lid2);
				$("#"+lid2).hide();
			}
		}
		 
		$.post("index.php?c=admin_resume&a=ajax&", {"str":wapadmin_select},function(data) {
			if(lid!="" && data!=""){
				$('#'+lid+' option').remove();
				$(data).appendTo("#"+lid);
				city_type(lid); 
			}
		})
	})	
})
 
function city_type(id){
	var id;
	var wapadmin_select=$("#"+id).val();
	var lid=$("#"+id).attr("lid");
	$.post("index.php?c=admin_resume&a=ajax&", {"str":wapadmin_select},function(data) {
		if(lid!=""){
			if(lid!="three_cityid" && lid!="three_city" && data!=""){
				$('#'+lid+' option').remove();
				$(data).appendTo("#"+lid);
			}else{
				if(data!=""){
					$('#'+lid+' option').remove();
					$(data).appendTo("#"+lid);
					$('#'+lid).show();
				}else{
					$('#'+lid+' option').remove();
					$("<option value='0'>请选择城市</option").appendTo("#"+lid);
					$('#'+lid).hide();
				}
			}
		}
	})
}
