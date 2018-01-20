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

	<link href="/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
		DIMAUB: "/",
	    ROOT: "/",
	    WEB_ROOT: "/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/public/js/jquery.js"></script>
    <script src="/public/js/wind.js"></script>
    <script src="/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
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
<!-- <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=E4805d16520de693a3fe707cdc962045"></script> -->
<link rel="stylesheet" href="http://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.css">
<style type="text/css">
.form-required{
	margin-right: 10px;
	line-height: 36px;
	color: red;
}
</style>
</head>
<body>
	<div class="wrap">
		<ul class="nav nav-tabs">
			<li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站列表</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站服务范围</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站配送统计</a></li>
			<li><a href="<?php echo U('Admin/Station/C_stationAdd'); ?>">新建水站</a></li>
			<li class="active"><a href="window.location.reload();return false;">编辑水站</a></li>
		</ul>
		<form method="post" class="form-horizontal js-ajax-form" action="<?php echo U('Admin/Station/C_stationEdit'); ?>">
			<fieldset>
				<!-- <div class="control-group">
					<label class="control-label"><span class="form-required">*</span>水站名称:</label>
					<div class="controls">
						<input type="text" name="name" require="require" tips="水站名称">
						
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">水站电话:</label>
					<div class="controls">
						<input type="text" name="tel" tips="水站电话"> -->
						<!-- <span class="form-required">*</span> -->
				<!-- 	</div>
				</div> -->
			<!-- 	<div class="control-group">
					<label class="control-label"><span class="form-required">*</span>负责人:</label>
					<div class="controls">
						<input type="text" name="recharger" require="require" tips="水站负责人">
					</div>
				</div>	 -->
				<input type="text" name="sid" value="<?php echo $station['id']; ?>" style="display: none;">
				<div class="control-group">
					<label class="control-label"><span class="form-required">*</span>水站名称:</label>
					<div class="controls">
						<input type="text" name="name" require="require" tips="水站名称" value="<?php echo $station['name']; ?>">
					</div>
				</div><!-- 
				<div class="control-group">
					<label class="control-label">负责人电话:</label>
					<div class="controls">
						<input type="text" name="recharger_tel" tips="水站负责人电话"> -->
						<!-- <span class="form-required">*</span> -->
				<!-- 	</div>
				</div> -->
				<div class="control-group">
					<label class="control-label"><span class="form-required">*</span>电话:</label>
					<div class="controls">
                        <input type="text" name="tel" require="require" tips="水站负责人电话" value="<?php echo $station['tel']; ?>">
						<span class="form-required">*</span>
					</div>
				</div>
                <div class="control-group">
                    <label class="control-label"><span class="form-required">*</span>备注:</label>
                    <div class="controls">
                        <textarea style="width:400px; height:60px;resize: none;" type="text" name="desc" require="require" tips="水站备注"><?php echo $station['desc']; ?></textarea>
                        <span class="form-required">*</span>
                    </div>
                </div>
				<div class="control-group">
					<label class="control-label"><span class="form-required">*</span>水站地址:</label>
					<div class="controls">
						<div id="address-map" style="width: 1000px;height:600px;"></div>
						<input type="text" id="address" name="address" require="require" tips="水站地址" style="width: 485px;" value="<?php echo $station['address']; ?>">
                        <input type="text" id="address-gps" name="address_gps" require="require" tips="水站地址" style="display: none;" value="<?php echo $station['gps']; ?>">
						<input type="text" id="range" name="range" require="require" tips="水站服务范围不能为空" style="display: none;" value="<?php echo $station['range']; ?>">
					</div>
				</div>
			</fieldset>
			<div class="form-actions">
				<button type="submit" class="btn btn-primary js-ajax-submit">保存</button>
				<a class="btn" href="<?php echo U('Admin/Station/C_stationList'); ?>">返回</a>
			</div>
		</form>
	</div>
	<script src="/public/js/common.js"></script>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=B1fe242ae7bea1edfd0a8d3b39d291eb"></script>  
	<script type="text/javascript" src="http://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
</body>
<script type="text/javascript"> 
$(function(){
    var map = new BMap.Map("address-map");  
    map.centerAndZoom(new BMap.Point(116.404, 39.915), 11);
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();

    // map.addEventListener("click", function(e){  
    //     map.clearOverlays();  
    //     var point = new BMap.Point(e.point.lng,e.point.lat);  
    //     var marker = new BMap.Marker(point);
    //     map.addOverlay(marker);  
    //     getAddr(e.point.lng,e.point.lat);
    // });  

    var geoc = new BMap.Geocoder();  
    function getAddr(lng, lat){  
        var point = new BMap.Point(lng, lat);  
        geoc.getLocation(point, function(rs){ 
            console.log(rs);
            if(rs.address){
            	var address = rs.address;
    			var addressGPS = rs.point.lng+','+rs.point.lat;
            }else{
            	map.clearOverlays();
            	alert('您选择的地点无法获取地址信息，请更换.');
            	var address = '';
            	var addressGPS = '';
            }
        	$('#address').val(address);
        	$('#address-gps').val(addressGPS);
        });  
    }

    var _gps = '<?php echo $station["gps"]; ?>',
    	__range = JSON.parse('<?php echo $station["range"]; ?>');
    	console.log(_gps);
    	console.log(__range);
    _gps = _gps.split(',');
    map.addOverlay(new BMap.Marker(new BMap.Point(_gps[0], _gps[1])));

    for(var i=0; i<__range.length; ++i){
    	var __points = [];
    	for(var j=0; j<__range[i].length; ++j){
    		__points.push(new BMap.Point(__range[i][j]['lng'], __range[i][j]['lat']));
    	}
    	var polygon = new BMap.Polygon(__points);
    	map.addOverlay(polygon);
    }

    var rangePolygon = [], stationPoint = false;
	
    var styleOptions = {
        strokeColor:"red",    //边线颜色。
        fillColor:"red",      //填充颜色。当参数为空时，圆形将没有填充效果。
        strokeWeight: 3,       //边线的宽度，以像素为单位。
        strokeOpacity: 0.8,	   //边线透明度，取值范围0 - 1。
        fillOpacity: 0.6,      //填充的透明度，取值范围0 - 1。
        strokeStyle: 'solid' //边线的样式，solid或dashed。
    }
    //实例化鼠标绘制工具
    var drawingManager = new BMapLib.DrawingManager(map, {
        isOpen: false, //是否开启绘制模式
        enableDrawingTool: true, //是否显示工具栏
        drawingToolOptions: {
            anchor: BMAP_ANCHOR_TOP_RIGHT, //位置
            // offset: new BMap.Size(5, 5), //偏离值
            drawingTypes : [BMAP_DRAWING_MARKER, BMAP_DRAWING_CIRCLE]
        },
        circleOptions: styleOptions, //圆的样式
        polylineOptions: styleOptions, //线的样式
        polygonOptions: styleOptions, //多边形的样式
        rectangleOptions: styleOptions //矩形的样式
    });  
	 //添加鼠标绘制工具监听事件，用于获取绘制结果
    drawingManager.addEventListener('overlaycomplete', function(e){
        if(e.drawingMode == 'marker'){
            if(stationPoint){
                map.removeOverlay(stationPoint);
            }
            stationPoint = e.overlay;
            getAddr(e.overlay.point.lng, e.overlay.point.lat);
        }else if(e.drawingMode == 'rectangle' || e.drawingMode == 'polygon'){
            rangePolygon.push(e.overlay.po);
            $('#range').val(JSON.stringify(rangePolygon));
        }else{
            alert('请使用多边形或矩形描述水站的服务范围。');
            map.removeOverlay(e.overlay);
            return false;
        }
    });

    // 表单非空验证
    $('button[type="submit"]').click(function(){
    	var formNoEmptyLength = $('form.js-ajax-form *[require="require"]').length;
    	for(var i=0; i<formNoEmptyLength; ++i){
    		var value = $('form.js-ajax-form *[require="require"]').eq(i).val();
    		if(value.trim().length == 0){
    			var tips = $('form.js-ajax-form *[require="require"]').eq(i).attr('tips');
    			alert(tips+'不能为空！');
    			return false;
    		}
    	}	
    });
});
</script>  
</html>