<?php
require_once 'setup.php';
require_once 'up_file.php';
require_once 'class/google/Google_Client.php';
require_once 'class/google/contrib/Google_Oauth2Service.php';

//-------------------設定區-----------------------//
$op=(empty($_REQUEST['op']))?"":$_REQUEST['op'];
$cate_psn=(empty($_REQUEST['cate_psn']))?"":$_REQUEST['cate_psn'];
$cate_sn=(empty($_REQUEST['cate_sn']))?"1":$_REQUEST['cate_sn'];
$files_sn=(empty($_REQUEST['files_sn']))?"":$_REQUEST['files_sn'];

//---------------流程控制區----------------------//
switch($op){
	//壓縮下載
	case "zip":
	zip($_POST['listfile']);
	break;
	
	
	//檢視檔案QRCODE
	case "shoowQrcode":
	 $content=bootstrap(page(shoowQrcode($files_sn)));
	break;
	
	//檔案刪除
	case "del_files":
	del_files($files_sn);
	$cate_psn=getFileCatesn($files_sn);
	//die("location:{$_SERVER['PHP_SELF']}?cate_sn={$cate_psn}");
	header("location:{$_SERVER['PHP_SELF']}?cate_sn={$cate_psn}");
	break;
	
	
	//檔案下載
	case "download":
	downloadFile($files_sn);
	break;	
	
	//新增檔案
	case "addFile":
	upload_file($_SESSION['user_sn'],$cate_psn);
	header("location:{$_SERVER['PHP_SELF']}?cate_sn={$cate_psn}");
	break;
	
	//新增目錄名稱
	case "addCate":
	addCate($cate_psn);
	header("location:{$_SERVER['PHP_SELF']}?cate_sn={$cate_psn}");
	break;
	
	

	

	
	
    default:
    $content=bootstrap(page(addCateForm($cate_sn)));
}
//------------------輸出區----------------------//
echo $content;
//----------------------函數區-------------------------//
function page($r_content=""){
	global $link;
	$main="
	<div class='row'>
		<div class='col-md-3'>".google_login()."</div>
		<div class='col-md-9'>{$r_content}</div>
	</div>
	";
	return $main;
}

function google_login(){
	global $link;
	$client = new Google_Client();
	$client->setApplicationName("網路隨身碟");
	$oauth2 = new Google_Oauth2Service($client);

	//通過Google的使用者任認證之後開始網址轉頁
	if (isset($_GET['code'])) {
	  $client->authenticate($_GET['code']);
	  $_SESSION['token'] = $client->getAccessToken();
	  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	  //參考http://www.w3school.com.cn/php/php_ref_filter.asp，快速的檢驗使用者輸入的資料
	  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
	  return;
	}
	//將google認證Google_Oauth2Service物件中的Token設定為$_SESSION['token']
	if (isset($_SESSION['token'])) {
	 $client->setAccessToken($_SESSION['token']);
	}
	//接收到登出的訊息之後清除$_SESSION['token']也清除Google_Oauth2Service物件的Token
	if (isset($_REQUEST['logout'])) {
	  unset($_SESSION['token']);
	  unset($_SESSION['user_sn']);
	  $client->revokeToken();
	}
	//取得google登入後的使用者資料
	//有id、email、verified_email、name、given_name、family_name、link、picture、gender、locale等資料
	if ($client->getAccessToken()) {
		$user = $oauth2->userinfo->get();

		$email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
		$name = filter_var($user['name'], FILTER_SANITIZE_STRING);
		$img = filter_var($user['picture'], FILTER_VALIDATE_URL);
	  
		//檢查是否為本站會員
		//die(var_dump($link));
		$sql="select * from `user` where `mail` = '{$email}'";
		$result = mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
		if (mysqli_num_rows($result) > 0){
			//是本站會員進行SESSION設定
			$sql="UPDATE `user` set `times` = times+1 WHERE `mail` = '{$email}'";
			mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link).$sql."增加次數失敗");
			//取得使用者的user_sn，並記錄在SESSION中
			$sql="SELECT * FROM `user` WHERE `mail` = '{$email}'";
			//die($sql);
			$result = mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
			$data=mysqli_fetch_array($result,MYSQLI_ASSOC);
			//die(var_dump($data));
			$_SESSION['user_sn']=$data['user_sn'];
		}else{
			//非本站會員記入資料庫，並進行SESSION設定
			//print "<h1>非本站會員記入資料庫，並進行SESSION設定</h1>";
			$sql="INSERT INTO `user` (`user_sn`, `name`, `mail`, `times`) VALUES (NULL, '{$name}', '{$email}', '1');";
			mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link).$sql."新增資料失敗");
			header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
			return;
		}
	  
		$personMarkup = "
		<div class='alert alert-success' role='alert'>
			<p>歡迎{$name}使用本系統</p>
			<p>{$email}</p>
			<div><img src='{$img}?sz=50'></div>
		</div>
		";
		//$personMarkup .= var_dump($user);

		$_SESSION['token'] = $client->getAccessToken();
	} else {
		$authUrl = $client->createAuthUrl();
	}
	//輸出資料
	$main="";
	if(isset($personMarkup)){
		$main.=$personMarkup; //Print user Information 
	} 
	if(isset($authUrl)) {
			$main.="<a class='login' href='{$authUrl}'><img src='img/sign-in-button.png' width=200px></a>";
	  } else {
			$main.="<a class='btn btn-danger col-md-12' href='?logout'>登出</a>";
	  }
	return $main;
}

function addCateForm($cate_psn=""){
	global $link;
	if(empty($cate_psn)){
		$cate_psn="1";
	}
	$op="
	<input type='hidden' name='op' value='addCate'>
	<input type='hidden' name='cate_psn' value='{$cate_psn}'>
	<input type='submit' value='新增目錄' id='submit' class='btn btn-warning'>
	";
	$op2="
	<input type='hidden' name='op' value='addFile'>
	<input type='hidden' name='cate_psn' value='{$cate_psn}'>
	<input type='submit' value='新增檔案' id='submit' class='btn btn-danger'>
	";	
	$main="<form action='{$_SERVER['PHP_SELF']}' method='post'>
	";
	$main.=list_cate();
	$main.="
		<input type='hidden' name='op' value='zip'>
		<input type='submit' value='壓縮下載' id='submit' class='btn btn-success'>
		</form>";
	$main.="
	<div class='row'>
	<div class='col-xs-6 col-md-6'>
	<form action='{$_SERVER['PHP_SELF']}' method='post'>
	<div class='form-group'>
	<label for='exampleInputEmail1'>輸入要新增目錄名稱</label>
	<input class='form-control' id='exampleInputEmail1' placeholder='新增目錄名稱' name='cate_name'>
	</div>
	{$op}
	</form>
	</div>
	<div class='col-xs-6 col-md-6'>
	<form action='{$_SERVER['PHP_SELF']}' method='post' enctype='multipart/form-data'>
	<div class='form-group'>
	<label for='exampleInputFile'>上傳檔案</label>
	<input type='file' name='upfile[]' id='exampleInputFile' multiple>
	</div>
	{$op2}
	</form>
	</div>
	</div>
	";
	if(!isset($_SESSION['user_sn'])){
		$main="<h1>請登入</h1>";
	}
	return $main;
}

function addCate($cate_psn=""){
	global $link;
	$cate_name=filter_var($_POST['cate_name'], FILTER_SANITIZE_STRING);
	$sql="INSERT INTO `cate` (`user_sn`, `cate_psn`, `cate_name`) VALUES ('{$_SESSION['user_sn']}','{$cate_psn}','{$cate_name}')";
	//die($sql);
	mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link).$sql."新增資料失敗");	
}

function list_cate(){
	global $link,$cate_sn,$cate_psn;
	$main="";
	$main.="
	<ol class='breadcrumb'>
	<li><a href='{$_SERVER['PHP_SELF']}'><span class='glyphicon glyphicon glyphicon-home' aria-hidden='true'></span> HOME</a></li>
	";
	$main.=get_breadcrumb($cate_sn);
	$main.="</ol>";
	if(empty($_SESSION['user_sn'])) return;
	$main.="
		<div class='jumbotron'>
		<div class='container'>
		<div class='row'>
	";
	$main.=show_cate($_SESSION['user_sn'],$cate_sn);
	$main.=list_file($_SESSION['user_sn'],$cate_sn);
	$main.="</div></div></div>";
	return $main;
}

function show_cate($user_sn="",$cate_psn=""){
	global $link;
	$main="";
	if(isset($user_sn)){
		
		if(empty($cate_psn)){
			$sql="select * from `cate` where `user_sn` = '{$_SESSION['user_sn']}' AND `cate_psn` = '1' order by cate_name DESC";
		}else{
			$sql="select * from `cate` where `user_sn` = '{$_SESSION['user_sn']}' AND `cate_psn` = '{$cate_psn}' order by cate_name DESC";
		}
		
		$result = mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
		while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
			foreach($data as $k=>$v){
				$$k=$v;
			}
			$main.="
				<div class='col-xs-6 col-md-3'>
				<a href='{$_SERVER['PHP_SELF']}?cate_sn={$cate_sn}'>
				<img src='img/folder.png'><br>
				  {$cate_name}
				  </a><br>
				 </div>";
		}	
	}else{
		$main="{$user_sn}";
	}
	return $main;

}

function get_breadcrumb($cate_sn=""){
	global $link;
	if(empty($_SESSION['user_sn'])) return;
	$main="";
	$sql="select * from `cate` where `user_sn` = '{$_SESSION['user_sn']}' AND `cate_sn` = '{$cate_sn}'";
	$result = mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
	while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
		foreach($data as $k=>$v){
			$$k=$v;
		}
		$main.=get_breadcrumb($cate_psn)."<li><a href='{$_SERVER['PHP_SELF']}?cate_sn={$cate_sn}'>{$cate_name}</a></li>";
	
	}
	return $main;
}
//從files_sn取得cate_sn
function getFileCatesn($files_sn=""){
	global $link;
	$data=get_one_file($files_sn);
	return $data['col_sn'];
}

function human_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

//顯示QRCODE
function shoowQrcode($files_sn=""){
	global $link;
	$filedata=get_one_file($files_sn);
	$human_file_size=human_filesize($filedata['file_size']);
	$filedes="
		<div class='table-responsive'>
		<table class='table table-striped table-bordered'>
		<tr><td>原始檔案名稱</td><td>{$filedata['description']}</td></tr>
		<tr><td>檔案名稱</td><td>{$filedata['file_name']}</td></tr>
		<tr><td>檔案類型</td><td>{$filedata['file_type']}</td></tr>
		<tr><td>檔案大小</td><td>{$human_file_size}</td></tr>

	";

	$data="{$_SERVER['SERVER_NAME']}/netusb/index.php?op=download&files_sn={$files_sn}";
	//set it to writable location, a place for temp generated PNG files
	$PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;
	
	//html PNG location prefix
    $PNG_WEB_DIR = 'temp/';

    include "class/phpqrcode/qrlib.php";    
    
    //ofcourse we need rights to create temp dir
    if (!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);
    
    $filename = $PNG_TEMP_DIR."{$files_sn}.png";
    //processing form input
    //remember to sanitize user input in real-life solution !!!
    $errorCorrectionLevel = 'L';
    if (isset($_REQUEST['level']) && in_array($_REQUEST['level'], array('L','M','Q','H')))
        $errorCorrectionLevel = $_REQUEST['level'];    

    $matrixPointSize = 6;
    if (isset($_REQUEST['size']))
        $matrixPointSize = min(max((int)$_REQUEST['size'], 1), 10);

    if (isset($data)) { 
    
        //it's very important!
        if (trim($data) == '')
            die('data cannot be empty! <a href="?">back</a>');
            
        // user data
        $filename = $PNG_TEMP_DIR.'test'.md5($data.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
        QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
        
    } else {    
    
        //default data
        echo 'You can provide data in GET parameter: <a href="?data=like_that">like that</a><hr/>';    
        QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
        
    }    
	$main="{$filedes}";
	$main.='
		<tr><td>QRcode圖檔</td><td>
		<img src="'.$PNG_WEB_DIR.basename($filename).'" />
		</table>
		</div>';
	return $main;
}

function zip($listfile=array()){
	global $link;
	require_once('class/pclzip-2-8-2/pclzip.lib.php');
	$fname="{$_SESSION['user_sn']}allfile.zip";

	$archive = new PclZip("{$_SESSION['user_sn']}allfile.zip");
	
	foreach($listfile as $k => $v){
		$data=get_one_file($v);
		if($data['kind']=="img"){
			$archive->add(_FILES_CENTER_IMAGE_DIR."/{$data['file_name']}",PCLZIP_OPT_REMOVE_ALL_PATH,PCLZIP_CB_PRE_ADD, 'myPreAddCallBack');
		}else{
			$archive->add(_FILES_CENTER_DIR."/{$data['file_name']}",PCLZIP_OPT_REMOVE_ALL_PATH,PCLZIP_CB_PRE_ADD, 'myPreAddCallBack');
		}
		
	}
	header('Pragma: public');
	header('Expires: 0');
	header('Last-Modified: ' . gmdate('D, d M Y H:i ') . ' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private', false);
	header('Content-Type: application/octet-stream');
	//header('Content-type:application/force-download'); //告訴瀏覽器 為下載 
	header('Content-Transfer-Encoding: Binary'); //編碼方式
	header('Content-Disposition:attachment;filename='.$fname); //檔名
	ob_clean();
    flush();
	@readfile($fname);
	if(file_exists($fname)){
		unlink($fname);
	}
	exit;


}

function myPreAddCallBack($p_event, &$p_header){
    global $link;
    // pclzip.lib.php壓縮前的回呼函數，更改檔名。
    $str_arr=explode("/",$p_header['stored_filename']);
    $file_name=array_pop($str_arr);
    $file_element=explode("_",$file_name);
	//die(var_dump($file_element));
    $cate_name=$file_element[0];
    $cate_sn=$file_element[1];
    $ext_arr=explode(".",$file_element[2]);
    $sort=$ext_arr[0];
    $ext=$ext_arr[1];
	
    $sql="select description from `files_center` where cate_name='{$cate_name}' AND cate_sn='{$cate_sn}' AND sort='{$sort}'";
    $result = mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
    list($description)=mysqli_fetch_array($result);
    $description=iconv("utf-8","big5",$description);
    $p_header['stored_filename']=$description;
    return 1;
}
?>