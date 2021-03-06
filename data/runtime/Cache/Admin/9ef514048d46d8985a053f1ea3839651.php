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
			<li class="active"><a href="<?php echo U('Admin/Order/C_orderList'); ?>">订单列表</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Order/C_orderList'); ?>">
			订单状态： 
			<select class="select_2" name="status" style="width:120px;">
				<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CREATED; ?>" <?php if(isset($where['status']) && $where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CREATED) echo 'selected="selected"';?>>下单成功</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_STATION_ACCEPT; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_STATION_ACCEPT) echo 'selected="selected"';?>>已接单</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_DELIVERING; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_DELIVERING) echo 'selected="selected"';?>>配送中</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_FINISHED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_FINISHED) echo 'selected="selected"';?>>已完成</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CANCELED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CANCELED) echo 'selected="selected"';?>>已取消</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CLOSED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CLOSED) echo 'selected="selected"';?>>已关闭</option>
			</select> &nbsp;&nbsp;
			<?php if($_isAdmin === true): ?>服务号： 
			<select class="select_2" name="config" style="width:120px;">
				<option value="-1">所有</option>
				<?php if(is_array($wechatconfig)): foreach($wechatconfig as $key=>$vo): ?><option value="<?php echo ($vo['id']); ?>" <?php if(isset($where['config']) && $where['config'] == $vo['id']) echo 'selected="selected"';?>><?php echo ($vo['wechat_name']); ?></option><?php endforeach; endif; ?>
			</select> &nbsp;&nbsp;<?php endif; ?>
			支付方式： 
			<select class="select_2" name="pay_type" style="width:120px;">
				<option value="-1" <?php if($where['pay_type'] == -1) echo 'selected="selected"';?>>所有</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_CASH; ?>" <?php if(isset($where['pay_type']) && $where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_CASH) echo 'selected="selected"';?>>现金支付</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_WECHAT; ?>" <?php if($where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_WECHAT) echo 'selected="selected"';?>>微信支付</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_TICKET; ?>" <?php if($where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_TICKET) echo 'selected="selected"';?>>水票支付</option>
			</select> &nbsp;&nbsp;
			支付状态：
			<select class="select_2" name="pay_status" style="width:120px;">
				<option value="-1" <?php if($where['pay_status'] == -1) echo 'selected="selected"';?>>所有</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_NOPAY; ?>" <?php if(isset($where['pay_status']) && $where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_NOPAY) echo 'selected="selected"';?>>未支付</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_SUCCESS; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_SUCCESS) echo 'selected="selected"';?>>支付成功</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_FAILED; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_FAILED) echo 'selected="selected"';?>>支付失败</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKING; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKING) echo 'selected="selected"';?>>退款中</option>
				<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKED; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKED) echo 'selected="selected"';?>>已退款</option>
			</select> &nbsp;&nbsp;
			时间：
			<input type="text" name="start_time" class="js-date date" value="<?php echo $where['start_time']; ?>" style="width: 100px;text-align:center;" autocomplete="off">-
			<input type="text" class="js-date date" name="end_time" value="<?php echo $where['end_time']; ?>" style="width: 100px;text-align:center;" autocomplete="off"> &nbsp; &nbsp;
			关键字： 
			<input type="text" name="keyword" style="width: 200px;" value="<?php echo $where['keyword']; ?>" placeholder="请输入订单编号...">
			<input type="submit" class="btn btn-primary" value="搜索">
		</form>
		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<th>ID</th>
					<?php if($_isAdmin === true): ?><th style="text-align:center;">服务号</th><?php endif; ?>
					<th style="text-align:center;">下单人</th>
					<th style="text-align:center;">订单编号</th>
					<th style="text-align:center;">总价</th>
					<!-- <th style="text-align:right;">优惠券</th> -->
					<!-- <th style="text-align:center;">发票信息</th> -->
					<th style="text-align:center;">下单时间</th>
					<th style="text-align:center;">订单状态</th>
					<th style="text-align:center;">支付方式</th>
					<th style="text-align:center;">支付状态</th>
					<th style="text-align:center;">操作</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data['data'] as $k => $v) { ?>
					<tr>
						<td style="width:40px;text-align:center;">
							<?php echo $v['id']; ?>
						</td>
						<?php if($_isAdmin === true): ?><td style="text-align:center;">
							<?php echo $v['wechat_name']; ?>
						</td><?php endif; ?>
						<td style="text-align:center;" title="<?php echo $v['name']; ?>">
							<?php echo $v['name'];?>
							<?php echo base64_decode($v['nickname']); ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['order_sn']; ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['order_price']; ?>
						</td>
						<!-- <td style="text-align:right;"><?php echo $v['coupon_money']; ?></td> -->
						<!-- <td style="text-align:center;">
						<?php  if($v['invoice_type'] == 0){ echo '无'; }else{ echo $v['invoice_type'] == 1 ? '[普通]' : '[专用]'; echo $v['invoice_name']; echo '-'.$v['order_total'].'元'; } ?>
						</td> -->
						<td style="text-align:center;">
							<?php echo date('Y-m-d H:i:s', $v['create_time']); ?>
						</td>
						<td style="text-align:center;">
						<?php
 if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CREATED){ echo '下单成功'; }else if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_STATION_ACCEPT){ echo '已接单'; }else if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_DELIVERING){ echo '配送中'; }else if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_FINISHED){ echo '已完成'; }else if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CANCELED){ echo '已取消'; }else if($v['order_status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CLOSED){ echo '已关闭'; } ?>
						</td>
						<td style="text-align:center;">
						<?php
 if($v['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_CASH){ echo '现金支付'; }else if($v['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_WECHAT){ echo '微信支付'; }else if($v['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_TICKET){ echo '水票支付'; } ?>
						</td>
						<td style="text-align:center;">
							<?php
 if($v['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_NOPAY){ echo '未支付'; }else if($v['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_SUCCESS){ echo '支付成功'; }else if($v['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_FAILED){ echo '支付失败'; }else if($v['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKING){ echo '退款中'; }else if($v['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKED){ echo '已退款'; } ?>
						</td>
						<td style="text-align:center;">
							<a href="<?php echo U('Admin/Order/orderDetail', array('oid'=>$v['id'])); ?>">订单详情</a>
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