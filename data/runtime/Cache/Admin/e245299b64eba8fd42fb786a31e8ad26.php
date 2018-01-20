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
			<li class="active"><a href="<?php echo U('Admin/Goods/C_goodsList'); ?>">商品列表</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_addGoods'); ?>">新建商品</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_goodsCatesList'); ?>">商品分类</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_createGoodsCate'); ?>">新建商品分类</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_goodsStrategyList'); ?>">商品组合列表</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_addGoodsStrategyPackage'); ?>">新建商品套餐</a></li>
			<li><a href="<?php echo U('Admin/Goods/C_goodsWarehouse'); ?>">商品库</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Goods/C_goodsList'); ?>">
			分类： 
			<select class="select_2" name="status">
				<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>
				<option value="0" <?php if(isset($where['status']) && $where['status'] == 0) echo 'selected="selected"';?>>在售</option>
				<option value="1" <?php if($where['status'] == 1) echo 'selected="selected"';?>>下架</option>
			</select> &nbsp;&nbsp;
			关键字： 
			<input type="text" name="keyword" style="width: 200px;" value="<?php echo $where['keyword']; ?>" placeholder="请输入商品名称，分类名称...">
			<input type="submit" class="btn btn-primary" value="搜索">

		</form>
		<!-- <form style="display: none;" id="excel-import" method="post" action="<?php echo U('Admin/Goods/importFromExcel'); ?>" enctype="multipart/form-data">
			<input type="file" name="file" style="display: none;" />
			<input style="float: right;margin-top: -70px;margin-right: 20px;" id="import" type="submit" class="btn btn-primary" value="Excel导入">
		</form> -->
		<form class="js-ajax-form" action="<?php echo U('Admin/Goods/C_saveGoodsListOrder'); ?>" method="post" novalidate="novalidate">
			<div class="table-actions">
				<button class="btn btn-primary btn-small js-ajax-submit" type="submit">排序</button>
			</div>
			<table class="table table-hover table-bordered">
				<thead>
					<tr>
						<th width="50">排序</th>
						<th width="50">ID</th>
						<th width="80">图片</th>
						<th>类型</th>
						<th>名称</th>
						<th style="text-align:center;">价格</th>
						<th style="text-align:center;">规格</th>
						<th>创建时间</th>
						<th style="text-align:center;">状态</th>
						<th width="120">操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($data['data'] as $k => $v) { ?>
						<tr>
							<td style="padding-left:20px;">
								<input name="listorders[<?php echo $v['id']; ?>]" type="text" size="3" value="<?php echo $v['list_order']; ?>" class="input input-order valid" style="width:50px;" aria-invalid="false">
							</td>
							<td><?php echo $v['id']; ?></td>
							<td><img src="/htdocs<?php echo $v['img']; ?>" style="width:80px;"></td>
							<td><?php echo $v['cate_name']; ?></td>
							<td style="color:#1abc9c;font-weight:bold;"><?php echo $v['name']; ?></td>
							<td style="text-align:center;">
								<?php echo $v['price']; ?> 元
							</td>
							<td style="text-align:center;"><?php echo $v['standard'].' / '.$v['unit']; ?></td>
							<td><?php echo $v['create_time']; ?></td>
							<td style="text-align:center;">
								<?php
 if($v['status'] == 0) echo '在售'; if($v['status'] == 1) echo '下架'; ?>
							</td>
							<td>
								<a href="<?php echo U('Admin/Goods/C_editGoods', array('gid'=>$v['id'])); ?>">编辑</a>
								<?php
 if(1 == $v['status']){ echo ' | <a class="js-ajax-dialog-btn" data-msg="您确定要上架此商品么？" href="'.U('Admin/Goods/C_openGoods', array('gid'=>$v['id'])).'">上架</a>'; } ?>
								<?php
 if(0 == $v['status']){ echo ' | <a class="js-ajax-dialog-btn" data-msg="您确定要下架此商品么？" href="'.U('Admin/Goods/C_closeGoods', array('gid'=>$v['id'])).'">下架</a>'; } ?>
								<?php if( $v['is_allowticket'] == 1): ?>| <a href="<?php echo U('Admin/Goods/C_addGoodsStrategyTicket', array('goods_id'=>$v['id'])); ?>">添加水票</a><?php endif; ?>
								<!-- 
								<?php
 if(1 == $v['is_recommended']){ echo ' |<a class="js-ajax-dialog-btn" data-msg="您确定要取消推荐此商品么？" href="'.U('Admin/Goods/C_unrecommendGoods', array('gid'=>$v['id'])).'">取消推荐</a>'; } ?>
								<?php
 if(0 == $v['is_recommended']){ echo ' |<a class="js-ajax-dialog-btn" data-msg="您确定要推荐此商品至首页么？" href="'.U('Admin/Goods/C_recommendGoods', array('gid'=>$v['id'])).'">推荐</a>'; } ?>
								 -->
								 | <a class="js-ajax-dialog-btn" data-msg="您确定要上架此商品么？" href="<?php echo U('Admin/Goods/C_deleteGoods', array('gid'=>$v['id'])); ?>">删除</a>
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