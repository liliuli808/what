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
            <li class="active"><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站服务范围</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationList'); ?>">水站配送统计</a></li>
            <li><a href="<?php echo U('Admin/Station/C_stationAdd'); ?>">新建水站</a></li>
        </ul>
		<div id="map" style="width: 95%;height: 90%;position: absolute;padding: 0;margin:0;border:0;"></div>
	</div>
	<script src="/htdocs/public/js/common.js"></script>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=B1fe242ae7bea1edfd0a8d3b39d291eb"></script>  
	<script type="text/javascript" src="http://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
</body>
<script type="text/javascript"> 
$(function(){
    var map = new BMap.Map("map");  
    map.centerAndZoom(new BMap.Point(116.404, 39.915), 13);
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();


    var stations = <?php echo $stations; ?>;

    if(stations.length){
        for(var i=0; i<stations.length; ++i){
            addStation(stations[i]);
        }
    }

    function addStation(station){
        var _range = JSON.parse(station['range']),
            gps = station['gps'],
            name = station['name'],
            tel = station['tel'];

        if(_range.length){
            // add polygon
            for(var j=0; j<_range.length; ++j){
                var points = [];
                for(var k=0; k<_range[j].length; ++k){
                    points.push(new BMap.Point(_range[j][k]['lng'], _range[j][k]['lat']));
                }
                var polygon = new BMap.Polygon(points, {strokeColor:"orangered", strokeWeight:2, strokeOpacity:0.8, fillColor:"orange"});
                map.addOverlay(polygon);
            }

            // add marker
            var _gps = gps.split(',');
            var marker = new BMap.Marker(new BMap.Point(_gps[0], _gps[1]));
            map.addOverlay(marker);

            // add label
            var opts = {
                position : new BMap.Point(_gps[0], _gps[1]),
                offset   : new BMap.Size(10, -30)
            };
            var label = new BMap.Label('店名：'+name+'<br/>电话：'+tel, opts);  // 创建文本标注对象
            label.setStyle({
                color : "black",
                fontSize : "12px",
                padding : '10px',
                width: '120px',
                lineHeight : "20px",
                fontFamily:"微软雅黑",
                fontWeight:'bolder',
                border : '1px solid gray'
            });
            map.addOverlay(label);
        }
    }

});
</script>  
</html>