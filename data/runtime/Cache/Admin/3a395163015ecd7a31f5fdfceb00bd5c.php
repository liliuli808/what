<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<!-- Set render engine for 360 browser -->
	<meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->

	<link href="/htdocs/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/htdocs/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/htdocs/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/htdocs/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/htdocs/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
		DIMAUB: "/htdocs/",
	    ROOT: "/htdocs/",
	    WEB_ROOT: "/htdocs/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/htdocs/public/js/jquery.js"></script>
    <script src="/htdocs/public/js/wind.js"></script>
    <script src="/htdocs/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
    <script>
    	$(function(){
    		$("[data-toggle='tooltip']").tooltip();
    	});
    </script>
<?php if(APP_DEBUG): ?><style>
		#think_page_trace_open{
			z-index:9999;
		}
	</style><?php endif; ?>
<style type="text/css">
.pagination{float: right;margin-right: 20px;}
.pagination a, .pagination span{padding: 3px 10px;margin-left: 3px;border-radius: 3px;}
.pagination a{background-color: #dadada;border: 1px solid #d1d1d1;color: black;text-decoration: none;}
.pagination span{background-color: orangered;border: 1px solid orangered;color: white;cursor: default;}
</style>
</head>
<body>
	<div class="wrap js-check-wrap">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站列表</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站服务范围</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站配送统计</a></li>
			<li><a href="<?php echo U('Admin/Station/C_stationAdd'); ?>">新建水站</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Station/C_stationList'); ?>">
			分类： 
			<select class="select_2" name="status" style="width: 120px;">
				<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>
				<!-- <option value="0" <?php if(isset($where['status']) && $where['status'] == 0) echo 'selected="selected"';?>>未绑定</option> -->
				<option value="0" <?php if($where['status'] == 0) echo 'selected="selected"';?>>启用</option>
				<option value="1" <?php if($where['status'] == 1) echo 'selected="selected"';?>>禁用</option>
			</select> &nbsp;&nbsp;
			<?php if($_isAdmin){ ?>
			服务号： 
			<select class="select_2" name="config" style="width:120px;">
				<option value="-1">所有</option>
				<?php if(is_array($wechatconfig)): foreach($wechatconfig as $key=>$vo): ?><option value="<?php echo ($vo['id']); ?>" <?php if(isset($where['config']) && $where['config'] == $vo['id']) echo 'selected="selected"';?>><?php echo ($vo['wechat_name']); ?></option><?php endforeach; endif; ?>
			</select> &nbsp;&nbsp;
			<?php } ?>
			关键字： 
			<input type="text" name="keyword" style="width: 200px;" value="<?php echo $where['keyword']; ?>" placeholder="水站名称，负责人，电话...">
			<input type="submit" class="btn btn-primary" value="搜索">
		</form>
		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<th width="50">ID</th>
					<!-- <th width="80" style="text-align:center;">水站名称</th>
					<th style="text-align:center;">水站电话</th>
					<th style="text-align:center;">负责人</th>
					<th style="text-align:center;">负责人电话</th>
					<th style="text-align:center;">地址</th>
					<th style="text-align:center;">绑定码</th>
					<th style="text-align:center;">创建时间</th>
					<th style="text-align:center;">状态</th>
					<th width="120" style="text-align:left;">操作</th> -->
				<?php if($_isAdmin){ ?>
					<th style="text-align:left;">服务号</th>
				<?php } ?>
					<th style="text-align:left;">姓名</th>
					<th style="text-align:left;">电话</th>
					<th style="text-align:left;">地址</th>
					<th style="text-align:left;">绑定码</th>
					<th style="text-align:left;">创建时间</th>
					<th style="text-align:left;">关注二维码</th>
					<th style="text-align:left;">状态</th>
					<th width="120" style="text-align:left;">操作</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data['data'] as $k => $v) { ?>
					<tr>
						<td><?php echo $v['id']; ?></td>
						<!-- 	<td><?php echo $v['name']; ?></td>
						<td><?php echo $v['tel']; ?></td>
						<td><?php echo $v['recharger']; ?></td>
						<td><?php echo $v['recharger_tel']; ?></td>
						<td><?php echo $v['address']; ?></td>
						<td><?php echo $v['bind_code']; ?></td>
						<td><?php echo date('Y-m-d H:i:s', $v['create_time']); ?></td>
						<td style="text-align:center;">
							<?php
 if($v['status'] == 0) echo '未绑定'; if($v['status'] == 1) echo '启用'; if($v['status'] == 2) echo '禁用'; ?>
						</td>
						<td>
							<a href="<?php echo U('Admin/Station/stationEdit', array('sid'=>$v['id'])); ?>">编辑</a>
							<a href="<?php echo U('Admin/Station/stationGoodsPrice', array('sid'=>$v['id'])); ?>">设定代理价格</a>
						</td> -->

					<?php if($_isAdmin){ ?>
						<td><?php if(!empty($v['wechat_name'])): echo $v['wechat_name']; else: ?>Admin<?php endif; ?></td>
					<?php } ?>
						<td style="text-align:center;"><?php echo $v['name']; ?></td>
						<td><?php echo $v['tel']; ?></td>
						<td><?php echo $v['address']; ?></td>
						<td><?php echo $v['bind_code']; ?></td>
						<td style="text-align:center;"><?php echo date('Y-m-d H:i:s', $v['create_time']); ?></td>
						<td><img src="<?php echo $v['qr_url']; ?>" style="width: 40px;height: 40px;"></td>
						<td style="text-align:left;">
							<?php
 if($v['status'] == 0) echo '启用'; if($v['status'] == 1) echo '禁用'; ?>
						</td>
						<td>
							<a href="<?php echo U('Admin/Station/C_stationEdit', array('sid'=>$v['id'])); ?>">编辑</a>
							 | <a target="_blank" href="<?php echo $v['qr_url']; ?>">下载二维码</a>
							<?php
 if(1 == $v['status']){ echo ' | <a class="js-ajax-dialog-btn" data-msg="您确定要启用此水站么？" href="'.U('Admin/Station/C_autoSaveStatus', array('uid'=>$v['id'],'status'=>$v['status'])).'">启用</a>'; } ?>
							<?php
 if(0 == $v['status']){ echo ' | <a class="js-ajax-dialog-btn" data-msg="您确定要禁用此水站么？" href="'.U('Admin/Station/C_autoSaveStatus', array('uid'=>$v['id'],'status'=>$v['status'])).'">禁用</a>'; } ?>
							 | <a class="js-ajax-dialog-btn" data-msg="您确定要删除此水站么？" href="<?php echo U('Admin/Station/C_stationDel', array('uid'=>$v['id'])); ?>">删除</a>
							<!-- <a href="javascript:void(0);" class="del" uid="<?php echo $v['id'];?>">删除</a> -->
							<!-- <a href="javascript:void(0);" class="status_edit" uid="<?php echo $v['id'];?>" status="<?php echo $v['status'];?>">
							<?php
 if($v['status'] == 0) echo '禁用'; if($v['status'] == 1) echo '启用'; ?>
							</a> -->
							<!-- <a href="<?php echo U('Admin/Station/stationGoodsPrice', array('sid'=>$v['id'])); ?>">设定代理价格</a> -->
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="pagination"><?php echo $data['show']; ?></div>
	</div>
	<script src="/htdocs/public/js/common.js"></script>
</body>
</html>
<script type="text/javascript">
	// $('.status_edit').on('click',function(){
		
	// 		var textStatus = $(this).parent().prev();
	// 		var ck =$(this);
	// 		var status = $(this).attr('status');//当前的状态
	// 		var id = $(this).attr('uid');
	// 		//alert(id);
	// 		//Object {info: "修改成功！", status: 1, referer: "", state: "success"}
	// 		$.ajax({
	// 			type:'get',
	// 			url:"<?php echo U('Admin/Station/autoSaveStatus');?>",
	// 			data:"uid="+id+"&status="+status,
	// 			success:function(data){
	// 				//data.status  修改完成后的状态
	// 				ck.attr('status',data.status);
	// 				if (data.status==0) 
	// 				{
	// 					textStatus.html('启用');
	// 					ck.html('禁用');
	// 				}
	// 				if (data.status==1) 
	// 				{
	// 					textStatus.html('禁用');
	// 					ck.html('启用');
	// 				}
	// 			}
	// 		});
		
	// });
	// $('.del').on('click',function(){
		
	// 		var myself = $(this).parent().parent();
	// 		var id = $(this).attr('uid');
	// 		$.ajax({
	// 			type:'get',
	// 			url:"<?php echo U('Admin/Station/stationDel');?>",
	// 			data:"uid="+id,
	// 			success:function(data){
	// 				//data.status  修改完成后的状态
	// 				console.log(data.status);
	// 				if(data.status==0)
	// 				{
	// 					myself.remove();
	// 				}
	// 			}
	// 		});
		
	// });
</script>