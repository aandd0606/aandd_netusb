<?php
session_start();
//系統基本資料
define("_WEB_ROOT_URL","http://{$_SERVER['SERVER_NAME']}/netusb/");
define("_WEB_ROOT_PATH","{$_SERVER['DOCUMENT_ROOT']}/netusb/");

//系統變數
$title="雲端隨身碟";
$page_menu=array(
    "首頁"=>"index.php",
    "雲端隨身碟"=>"netusb.php"
    );
$userSta_arr=array('ok'=>'啟用','no'=>'禁用');
$tblUser="user";

//$cover_path=_WEB_ROOT_PATH."cover/";
//$cover_url=_WEB_ROOT_URL."cover/";
//$img_url=_WEB_ROOT_URL."img/";
//資料庫連線
$db_id="netusb";//資料庫使用者//
$db_passwd="netusb123456";//資料庫使用者密碼//
$db_name="netusb";//資料庫名稱//
//動態產生導覽列
$top_nav=dy_nav($page_menu);

//連入資料庫
$link=mysqli_connect('localhost',$db_id,$db_passwd,$db_name) or die_content(mysqli_connect_errno().mysqli_connect_error()."資料庫無法連線");
mysqli_query($link,"SET NAMES 'utf8'");


//自定輸出錯誤訊息
function die_content($content=""){
    $main="
		<!DOCTYPE html>
		<html lang='zh-Hant-tw'>
		<head>
		<meta charset='utf-8'>
		<title>輸出錯誤訊息</title>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>
		<meta name='description' content='輸出錯誤訊息'>
		<meta name='author' content='aandd'>
		<!--引入JQuery CDN-->
		<script src='https://code.jquery.com/jquery-2.1.4.min.js'></script>
		<!--引入Bootstrap 3 CDN---->
		<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'>
		<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css'>
		<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
		</head>
		<body>
		<!--放入網頁主體-->
		<div class='container'>
		  <!-- 主要內容欄位開始 -->
		  <div class='row'>
			<div class='col-md-12 col-sm-12'>
				<div class='jumbotron'>
				  <h1>輸出錯誤訊息</h1>
				  <p>{$content}</p>
				</div>
			</div>
		  </div>
		  <!-- 主要內容欄位結束 -->
		</div> 
		<!-- 主要內容欄位結束 -->
		</body>
		</html>
	";
    die($main);
}

//產生動態導覽列
function dy_nav($page_menu=array()){
    global $title;
    $main="
    <!-- Fixed navbar -->
    <nav class='navbar navbar-default navbar-fixed-top'>
      <div class='container'>
        <div class='navbar-header'>
          <button type='button' class='navbar-toggle collapsed' data-toggle='collapse' data-target='#navbar' aria-expanded='false' aria-controls='navbar'>
            <span class='sr-only'>Toggle navigation</span>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
          </button>
          <a class='navbar-brand' href='#'>{$title}</a>
        </div>
        <div id='navbar' class='navbar-collapse collapse'>
          <ul class='nav navbar-nav'>";
		  //$file_name=basename($_SERVER['PHP_SELF']);
			$file_name=basename($_SERVER['REQUEST_URI']);
			foreach($page_menu as $i=>$v){
				$class=($file_name==$v)?"class='active'":"";
				$main.="<li {$class}><a href='{$v}'>{$i}</a></li>";
			}
          $main.="</ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>	
	
	
	";
	
	
	
	
    return $main;
}

function bootstrap($content="",$js_link="",$css_link="",$js_fun=""){
    global $top_nav,$title;
	$main="
	<!DOCTYPE html>
	<html lang='zh-Hant-tw'>
	<head>
	<meta charset='utf-8'>
	<title>{$title}</title>
	 <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='description' content='{$title}'>
        <meta name='author' content='aandd'>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'>
		<link href='https://maxcdn.bootstrapcdn.com/bootswatch/3.3.5/cerulean/bootstrap.min.css' rel='stylesheet'>	
		<script src='https://code.jquery.com/jquery-2.1.4.min.js'></script>
		<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
        <style type='text/css'>
          body {
            padding-top: 60px;
            padding-bottom: 20px;
          }
        </style>
        <!--引入額外的css檔案以及js檔案開始-->
        {$js_link}
        {$css_link}
        <!--引入額外的css檔案以及js檔案結束-->
        <!--jquery語法開始-->
        {$js_fun}
        <!--jquery語法結束-->
        </head>
        <body>
	<!--放入網頁主體-->
	{$top_nav}
	<div class='container'>
	  <!-- 主要內容欄位開始 -->
	  {$content}
	  <!-- 主要內容欄位結束 -->
	</div> 
	<!-- 主要內容欄位結束 -->
	</body>
	</html>
	
	";

    return $main;
}
?>