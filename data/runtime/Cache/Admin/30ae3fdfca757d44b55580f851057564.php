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
			<li class="active"><a href="<?php echo U('Admin/Wechat/C_funsList'); ?>">客户列表</a></li>
			<li><a href="<?php echo U('Admin/Wechat/C_funsAddressList'); ?>">地址列表</a></li>
			<li><a href="<?php echo U('Admin/Wechat/C_funsTicketList'); ?>">水票列表</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Wechat/C_funsList'); ?>">
			分类： 
			<select class="select_2" name="subscribe" style="width:120px;">
				<option value="-1" <?php if(!isset($where['subscribe']) && $where == -1) echo 'selected="selected"';?>>所有</option>
				<option value="1" <?php if(isset($where['subscribe']) && $where['subscribe'] == 1) echo 'selected="selected"';?>>关注</option>
				<option value="0" <?php if(isset($where['subscribe']) && $where['subscribe'] == 0) echo 'selected="selected"';?>>未关注</option>
			</select> &nbsp;&nbsp;
			<?php if($_isAdmin === true): ?>服务号： 
			<select class="select_2" name="config" style="width:120px;">
				<option value="-1">所有</option>
				<?php if(is_array($wechatconfig)): foreach($wechatconfig as $key=>$vo): ?><option value="<?php echo ($vo['id']); ?>" <?php if(isset($where['config_id']) && $where['config_id'] == $vo['id']) echo 'selected="selected"';?>><?php echo ($vo['wechat_name']); ?></option><?php endforeach; endif; ?>
			</select> &nbsp;&nbsp;<?php endif; ?>
			关键字： 
			<input type="text" name="keyword" style="width: 200px;" value="<?php echo $_POST['keyword']; ?>" placeholder="请输入微信昵称...">
			<input type="submit" class="btn btn-primary" value="搜索">

		</form>
		<!-- <form style="display: none;" id="excel-import" method="post" action="<?php echo U('Admin/Goods/importFromExcel'); ?>" enctype="multipart/form-data">
			<input type="file" name="file" style="display: none;" />
			<input style="float: right;margin-top: -70px;margin-right: 20px;" id="import" type="submit" class="btn btn-primary" value="Excel导入">
		</form> -->
		<form class="js-ajax-form" action="<?php echo U('Admin/Carousel/C_saveCarouselListOrder'); ?>" method="post" novalidate="novalidate">
			<!-- <div class="table-actions">
				<button class="btn btn-primary btn-small js-ajax-submit" type="submit">排序</button>
			</div> -->
			<table class="table table-hover table-bordered">
				<thead>
					<tr>
						<th width="50">ID</th>
						<?php if($_isAdmin === true): ?><th width="">服务号</th><?php endif; ?>
						<!-- <th width="80">头像</th>
						<th style="text-align:center;">昵称</th> -->
						<th>账号信息</th>
						<th style="text-align:center;">openid</th>
						<th style="text-align:center;">是否关注</th>
						<th style="text-align:center;">关注时间</th>
						<th style="text-align:center;">性别</th>
						<th style="text-align:center;">默认收货地址</th>
						<th style="text-align:center;">水桶个数</th>
						<th width="120">操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($data['data'] as $k => $v) { ?>
						<tr>
							
							<td><?php echo $v['id']; ?></td>
							<?php if($_isAdmin === true): ?><td><?php echo $v['wechat_name']; ?></td><?php endif; ?>
							<!-- <td><img src="<?php echo $v['headimgurl']; ?>" style="width:80px;"></td>
							<td style="color:#1abc9c;font-weight:bold;text-align:center;"><?php echo $v['nickname']; ?></td> -->
							<td style="width: 160px;">
								<img src="<?php echo $v['headimgurl']; ?>" style="width:40px;height: 40px;margin-right: 10px;border-radius: 100%;"><?php echo $v['nickname']; ?>
							</td>
							<td style="color:#1abc9c;font-weight:bold;width:230px;"><?php echo $v['openid']; ?></td>
							<td style="text-align:center;">
								<?php
 if($v['subscribe'] == 0) echo '未关注'; if($v['subscribe'] == 1) echo '关注'; ?>
							</td>
							<td style="text-align:center;"><?php echo date('Y-m-d H:i:s', $v['subscribe_time']); ?></td>
							<td style="text-align:center;">
								<?php
 if($v['sex'] == 1) echo '<span style="color:blue">男</span>'; if($v['sex'] == 2) echo '<span style="color:red">女</span>'; if($v['sex'] == 0) echo '未设置'; ?>
							</td>
							<td style="text-align:center;"><?php if(!empty($v['default_address'])): echo ($v['default_address']); else: ?>未设置<?php endif; ?></td>
							<td style="text-align:center;font-weight:bold;"><?php echo $v['bucket']; ?></td>
							<td style="text-align:center;">
								<a href="<?php echo U('Admin/Wechat/C_funsAddressList', array('openid'=>$v['openid'])); ?>">地址列表</a> | 
								<a href="<?php echo U('Admin/Wechat/C_funsTicketList', array('openid'=>$v['openid'])); ?>">水票列表</a> | 
								<a href="<?php echo U('Admin/Order/C_orderList', array('openid'=>$v['openid'])); ?>">订单列表</a> | 
								<a href="<?php echo U('Admin/Wechat/C_SinglePriced', array('openid'=>$v['openid'], 'config_id'=>$v['config_id'])); ?>">单独定价</a> | 
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>
		<div class="pagination"><?php echo $data['show']; ?></div>
	</div>
	<script src="/htdocs/public/js/common.js"></script>
</body>
<script type="text/javascript">
$(function(){
	var imported = false;
	$('#import').click(function(){
		if(imported === false){
			$('input[type="file"]').trigger('click');
			imported = true;
			return false;
		}else{
			imported = false;
		}
	});
});
</script>
</html>