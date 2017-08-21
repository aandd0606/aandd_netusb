<?php
//設定模組目錄名稱
define("_FILEDIR","files_center");
define("_FC_THUMB_SMALL_WIDTH",250);
if(isset($_SESSION['user_sn'])){
	//檔案中心實體位置
	define("_FILES_CENTER_DIR",_FILEDIR."/files/".$_SESSION['user_sn']);
	define("_FILES_CENTER_URL",_FILEDIR."/files/".$_SESSION['user_sn']);
	//檔案中心圖片實體位置
	define("_FILES_CENTER_IMAGE_DIR",_FILEDIR."/images/".$_SESSION['user_sn']);
	define("_FILES_CENTER_IMAGE_URL",_FILEDIR."/images/".$_SESSION['user_sn']);
	//檔案中心縮圖實體位置
	define("_FILES_CENTER_THUMB_DIR",_FILEDIR."/_thumbs/".$_SESSION['user_sn']);
	define("_FILES_CENTER_THUMB_URL",_FILEDIR."/_thumbs/".$_SESSION['user_sn']);
}

//上傳圖檔，$cate_name=對應欄位名稱,$cate_sn=對應欄位編號,$種類：img,file,$sort=圖片排序,$files_sn="更新編號"
function upload_file($cate_name="",$cate_sn="",$files_sn="",$sort=""){
	global $link;
	//引入上傳物件
	include_once "class/class.upload.php/src/class.upload.php";
	//取消上傳時間限制
	set_time_limit(0);
	//設置上傳大小
	ini_set('memory_limit', '100M');
	  
	$files = array();
	foreach ($_FILES['upfile'] as $k => $l) {
		foreach ($l as $i => $v) {
			if (!array_key_exists($i, $files)){
				$files[$i] = array();
			}
			$files[$i][$k] = $v;
		}
	}
  
  foreach ($files as $file) {
		//先刪除舊檔
		if(!empty($files_sn)){
	  	del_files($files_sn);
	  }
	  //自動排序
	  if(empty($sort)){
			$sort=auto_sort($cate_name,$cate_sn);
		}
		//取得檔案
	  $file_handle = new upload($file,"zh_TW");
	  if ($file_handle->uploaded) {
	      //取得副檔名
	      $ext=strtolower($file_handle->file_src_name_ext);
	      //判斷檔案種類
	      if($ext=="jpg" or $ext=="jpeg" or $ext=="png" or $ext=="gif"){
					$kind="img";
				}else{
					$kind="file";
				}
	      
	      $file_handle->file_safe_name = false;
	      $file_handle->file_overwrite = true;
	      $file_handle->file_new_name_body   = "{$cate_name}_{$cate_sn}_{$sort}";
	      $path=($kind=="img")?_FILES_CENTER_IMAGE_DIR:_FILES_CENTER_DIR;
	      $file_handle->process($path);
	      $file_handle->auto_create_dir = true;

	      //若是圖片才製作小縮圖
	      if($kind=="img"){
		      $file_handle->file_safe_name = false;
		      $file_handle->file_overwrite = true;
		      $file_handle->file_new_name_body   = "{$cate_name}_{$cate_sn}_{$sort}";

		      $file_handle->image_resize         = true;
		      $file_handle->image_x              = _FC_THUMB_SMALL_WIDTH;
		      $file_handle->image_ratio_y         = true;

		      $file_handle->process(_FILES_CENTER_THUMB_DIR);
		      $file_handle->auto_create_dir = true;
				}

				//上傳檔案
	      if ($file_handle->processed) {
	          $file_handle->clean();
	          $file_name="{$cate_name}_{$cate_sn}_{$sort}.{$ext}";

	          if(empty($files_sn)){
	  	        $sql = "insert into files_center (`cate_name`,`cate_sn`,`sort`,`kind`,`file_name`,`file_type`,`file_size`,`description`) values('$cate_name','$cate_sn','$sort','{$kind}','{$file_name}','{$file['type']}','{$file['size']}','{$file['name']}')";
	  			mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
				
	          }else{
	            $sql = "replace into files_center (`files_sn`,`cate_name`,`cate_sn`,`sort`,`kind`,`file_name`,`file_type`,`file_size`,`description`) values('{$files_sn}','$cate_name','$cate_sn','$sort','{$kind}','{$file_name}','{$file['type']}','{$file['size']}','{$file['name']}')";
	  			mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
	          }
	      } else {
						die("Error:".$file_handle->error);
	      }
	  }
	  $sort="";
  }
}

//刪除實體檔案
function del_files($files_sn="",$cate_name="",$cate_sn="",$sort=""){
	global $link;
	if(!empty($files_sn)){
		$del_what="`files_sn`='{$files_sn}'";
	}elseif(!empty($cate_name) and !empty($cate_sn)){
	  $and_sort=(empty($sort))?"":"and `sort`='{$sort}'";
		$del_what="`cate_name`='{$cate_name}' and `cate_sn`='{$cate_sn}' $and_sort";
	}
	$sql = "select * from files_center where $del_what";
	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
		foreach($data as $k=>$v){
			$$k=$v;
		}
 	  $del_sql = "delete  from files_center where files_sn='{$files_sn}'";
		//die($del_sql);
	  
 		mysqli_query($link, $del_sql) or die_content(mysqli_errno($link).mysqli_error($link)."刪除資料失敗");
 	
		if($kind=="img"){
			unlink(_FILES_CENTER_IMAGE_DIR."/$file_name");
			unlink(_FILES_CENTER_THUMB_DIR."/$file_name");
		}else{
			unlink(_FILES_CENTER_DIR."/$file_name");
		}
	}
}

//列出可刪除檔案
function list_file($cate_name="",$cate_sn=""){
	global $link;
	$files="";
	$qrcode="";
	$sql = "select * from files_center where `cate_name`='{$cate_name}' and `cate_sn`='{$cate_sn}' order by sort";
 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
	  //以下會產生這些變數： $files_sn, $cate_name, $cate_sn, $sort, $kind, $file_name, $file_type, $file_size, $description
    foreach($data as $k=>$v){
      $$k=$v;
    }
	$ext = end(explode('.', $description));
	$ahref="{$_SERVER['PHP_SELF']}?op=download&files_sn={$files_sn}";
	$files.="
		<div class='col-xs-6 col-md-3'>
		<input type='checkbox' name='listfile[]' value='{$files_sn}'>
		<img src='img/Free-file-icons-master/48px/{$ext}.png'><br>
		{$description}<br>
		<a href='{$_SERVER['PHP_SELF']}?op=del_files&files_sn={$files_sn}'><span class='glyphicon glyphicon-remove' aria-hidden='true' title='刪除檔案'></span></a>
		<a href='{$ahref}' target='_blank'><span class='glyphicon glyphicon-download-alt' aria-hidden='true' title='下載檔案'></span></a>
		<a href='{$_SERVER['PHP_SELF']}?op=shoowQrcode&files_sn={$files_sn}' target='_blank'><span class='glyphicon glyphicon-qrcode' aria-hidden='true' title='檢視QRCODE'></span></a>
		</div>
		";
	}
	$files.="";
	return $files;
}

function downloadFile($files_sn=""){
	add_file_counter($files_sn);
	$fileData=get_one_file($files_sn);
	foreach($fileData as $k=>$v){
		$$k=$v;
	}
	//die($description);
	header('Pragma: public');
	header('Expires: 0');
	header('Last-Modified: ' . gmdate('D, d M Y H:i ') . ' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private', false);
	header('Content-Type: application/octet-stream');
	//header('Content-type:application/force-download'); //告訴瀏覽器 為下載 
	header('Content-Transfer-Encoding: Binary'); //編碼方式
	header('Content-Disposition:attachment;filename='.$description); //檔名
	ob_clean();
    flush();
	@readfile(_FILES_CENTER_DIR."/{$file_name}");
	exit;
}

//取得單一檔案資料
function get_one_file($files_sn=""){
	global $link;
	$sql = "select * from files_center where `files_sn`='{$files_sn}'";
 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	$all=mysqli_fetch_array($result,MYSQLI_ASSOC);
 	return $all;
}

//自動編號
function auto_sort($cate_name="",$cate_sn=""){
	global $link;
	$sql = "select max(sort) from files_center where `cate_name`='{$cate_name}' and `cate_sn`='{$cate_sn}'";
 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	list($max)=mysqli_fetch_array($result,MYSQLI_NUM);
	return ++$max;
}

//下載並新增計數器
function add_file_counter($files_sn=""){
	global $link;
	$file=get_one_file($files_sn);
	$sql = "update files_center set `counter`=`counter`+1 where `files_sn`='{$files_sn}'";
 	mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
}

if(@$_GET['fop']=="dl"){
  add_file_counter($_GET['files_sn']);
}
?>
