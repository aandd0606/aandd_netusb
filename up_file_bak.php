<?php
//設定模組目錄名稱
define("_FILEDIR","files_center");
//die("upfile".$_SESSION['user_sn']);


//  ------------------------------------------------------------------------ //
// 本模組由 tad 製作
// 製作日期：2008-12-15
// $Id:$
// ------------------------------------------------------------------------- //

/*
1.建立資料表：
CREATE TABLE `files_center` (
  `files_sn` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '檔案流水號',
  `col_name` varchar(255) NOT NULL COMMENT '欄位名稱',
  `col_sn` smallint(5) unsigned NOT NULL COMMENT '欄位編號',
  `sort` smallint(5) unsigned NOT NULL COMMENT '排序',
  `kind` enum('img','file') NOT NULL COMMENT '檔案種類',
  `file_name` varchar(255) NOT NULL COMMENT '檔案名稱',
  `file_type` varchar(255) NOT NULL COMMENT '檔案類型',
  `file_size` int(10) unsigned NOT NULL COMMENT '檔案大小',
  `description` text NOT NULL COMMENT '檔案說明',
  `counter` mediumint(8) unsigned NOT NULL COMMENT '下載人次',
  PRIMARY KEY (`files_sn`)
) ENGINE=MyISAM COMMENT='檔案資料表';

2.在用到上傳的檔案，加入此行：
include_once "up_file.php";

3.在上傳的表單加入底下屬性：
enctype='multipart/form-data'
 
4.在上傳的表單加入底下引入相關檔案的語法：
<script src="upload/jquery-1.3.2.min.js"></script>
<script src="upload/jquery.MultiFile.js"></script>

5.在上傳的表單加入該上傳欄位：
<input type='file' name='upfile[]' class='multi' maxlength=1>".list_del_file($col_name,$col_sn)."
或
<input type='file' name='upfile[]' class='multi' maxlength=1><?php list_del_file($col_name,$col_sn);

6.在儲存或更新的動作中加入該上傳函數：
upload_file($col_name,$col_sn);

7.顯示所有圖片：  //欄位,編號,是否縮圖,顯示模式filename、num,顯示描述,顯示下載次數
show_files($col_name,$col_sn,true,false,false,false);

8.刪除資料時，在刪除程式中，最後加入刪除檔案函數
del_files($files_sn,$col_name,$col_sn);

種類：img,file

*/

define("_FC_THUMB_SMALL_WIDTH",250);


// //檔案中心實體位置
// define("_FILES_CENTER_DIR",_FILEDIR."/files");
// define("_FILES_CENTER_URL",_FILEDIR."/files");
// //檔案中心圖片實體位置
// define("_FILES_CENTER_IMAGE_DIR",_FILEDIR."/images");
// define("_FILES_CENTER_IMAGE_URL",_FILEDIR."/images");
// //檔案中心縮圖實體位置
// define("_FILES_CENTER_THUMB_DIR",_FILEDIR."/_thumbs");
// define("_FILES_CENTER_THUMB_URL",_FILEDIR."/_thumbs");


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


//上傳圖檔，$col_name=對應欄位名稱,$col_sn=對應欄位編號,$種類：img,file,$sort=圖片排序,$files_sn="更新編號"
function upload_file($col_name="",$col_sn="",$files_sn="",$sort=""){
	global $link;
	//引入上傳物件
  include_once "class/class.upload.php/src/class.upload.php";

	//取消上傳時間限制
  set_time_limit(0);
  //設置上傳大小
  ini_set('memory_limit', '80M');
  
  //刪除勾選檔案
  if(!empty($_POST['del_file'])){
    foreach($_POST['del_file'] as $del_files_sn){
			del_files($del_files_sn);
		}
	}
	  
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
			$sort=auto_sort($col_name,$col_sn);
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
	      $file_handle->file_new_name_body   = "{$col_name}_{$col_sn}_{$sort}";
	      $path=($kind=="img")?_FILES_CENTER_IMAGE_DIR:_FILES_CENTER_DIR;
	      $file_handle->process($path);
	      $file_handle->auto_create_dir = true;

	      //若是圖片才製作小縮圖
	      if($kind=="img"){
		      $file_handle->file_safe_name = false;
		      $file_handle->file_overwrite = true;
		      $file_handle->file_new_name_body   = "{$col_name}_{$col_sn}_{$sort}";

		      $file_handle->image_resize         = true;
		      $file_handle->image_x              = _FC_THUMB_SMALL_WIDTH;
		      $file_handle->image_ratio_y         = true;

		      $file_handle->process(_FILES_CENTER_THUMB_DIR);
		      $file_handle->auto_create_dir = true;
				}

				//上傳檔案
	      if ($file_handle->processed) {
	          $file_handle->clean();
	          $file_name="{$col_name}_{$col_sn}_{$sort}.{$ext}";

	          if(empty($files_sn)){
	  	        $sql = "insert into files_center (`col_name`,`col_sn`,`sort`,`kind`,`file_name`,`file_type`,`file_size`,`description`) values('$col_name','$col_sn','$sort','{$kind}','{$file_name}','{$file['type']}','{$file['size']}','{$file['name']}')";
	  			mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
				
	          }else{
	            $sql = "replace into files_center (`files_sn`,`col_name`,`col_sn`,`sort`,`kind`,`file_name`,`file_type`,`file_size`,`description`) values('{$files_sn}','$col_name','$col_sn','$sort','{$kind}','{$file_name}','{$file['type']}','{$file['size']}','{$file['name']}')";
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
function del_files($files_sn="",$col_name="",$col_sn="",$sort=""){
	global $link;
	if(!empty($files_sn)){
		$del_what="`files_sn`='{$files_sn}'";
	}elseif(!empty($col_name) and !empty($col_sn)){
	  $and_sort=(empty($sort))?"":"and `sort`='{$sort}'";
		$del_what="`col_name`='{$col_name}' and `col_sn`='{$col_sn}' $and_sort";
	}
	
	$sql = "select * from files_center where $del_what";
 	//die($sql);
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

//取得檔案 $kind=images（大圖）,thumb（小圖），$mode=link（完整連結）or array（路徑陣列）
function get_file($col_name="",$col_sn="",$sort=""){
	global $link;
	$and_sort=(!empty($sort))?" and `sort`='{$sort}'":"";
	$sql = "select * from files_center where `col_name`='{$col_name}' and `col_sn`='{$col_sn}' $and_sort order by sort";

 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
	  //以下會產生這些變數： $files_sn, $col_name, $col_sn, $sort, $kind, $file_name, $file_type, $file_size, $description
    foreach($data as $k=>$v){
      $$k=$v;
    }
   
    $files[$files_sn]['kind']=$kind;
    $files[$files_sn]['sort']=$sort;
    $files[$files_sn]['file_name']=$file_name;
    $files[$files_sn]['file_type']=$file_type;
    $files[$files_sn]['file_size']=$file_size;
    $files[$files_sn]['counter']=$counter;
    $files[$files_sn]['description']=$description;
    
    if($kind=="img"){
      $pic_name=(file_exists(_FILES_CENTER_IMAGE_DIR."/{$file_name}"))?_FILES_CENTER_IMAGE_URL."/{$file_name}":"upload/no_thumb.gif";
			$thumb_pic=(file_exists(_FILES_CENTER_THUMB_DIR."/{$file_name}"))?_FILES_CENTER_THUMB_URL."/{$file_name}":"upload/no_thumb.gif";
			
			
			$files[$files_sn]['link']="<a href='{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn' title='{$description}' rel='lytebox'><img src='{$pic_name}' alt='{$description}' title='{$description}' rel='lytebox'></a>";
			$files[$files_sn]['path']=$pic_name;
			$files[$files_sn]['url']="<a href='{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn' title='{$description}' target='_blank'>{$description}</a>";

			
			
			$files[$files_sn]['tb_link']="<a href='{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn' title='{$description}' rel='lytebox'><img src='$thumb_pic' alt='{$description}' title='{$description}'></a>";
			$files[$files_sn]['tb_path']=$thumb_pic;
			$files[$files_sn]['tb_url']="<a href='{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn' title='{$description}' rel='lytebox'>{$description}</a>";
		}else{
			$files[$files_sn]['link']="<a href='{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn'>{$description}</a>";
			$files[$files_sn]['path']="{$_SERVER['PHP_SELF']}?fop=dl&files_sn=$files_sn";
		}
	}
	return $files;
}

// //列出可刪除檔案
// function list_del_file($col_name="",$col_sn=""){
	// global $link;
  // $files="<div>選擇欲刪除檔案：<br>";

	// $sql = "select * from files_center where `col_name`='{$col_name}' and `col_sn`='{$col_sn}' order by sort";

 	// $result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	// while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
	  // //以下會產生這些變數： $files_sn, $col_name, $col_sn, $sort, $kind, $file_name, $file_type, $file_size, $description
    // foreach($data as $k=>$v){
      // $$k=$v;
    // }

    // $files.="<input type='checkbox' name='del_file[]' value='{$files_sn}'> $description<br>";
	// }
	// $files.="</div>";
	// return $files;
// }

//列出可刪除檔案
function list_file($col_name="",$col_sn=""){
	global $link;
	$files="";
	$qrcode="";
	$sql = "select * from files_center where `col_name`='{$col_name}' and `col_sn`='{$col_sn}' order by sort";

 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	while($data=mysqli_fetch_array($result,MYSQLI_ASSOC)){
	  //以下會產生這些變數： $files_sn, $col_name, $col_sn, $sort, $kind, $file_name, $file_type, $file_size, $description
    foreach($data as $k=>$v){
      $$k=$v;
    }
	$ext = end(explode('.', $description));
	$ahref="{$_SERVER['PHP_SELF']}?op=download&files_sn={$files_sn}";
	$files.="
		<div class='col-xs-6 col-md-3'>
		<input type='checkbox' name='del_file[]' value='{$files_sn}'>
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

// //取得附檔或附圖$show_mode=filename、num
// function show_files($col_name="",$col_sn="",$thumb=true,$show_mode="",$show_description=false,$show_dl=false){
	// global $link;
	// if($show_mode==""){
		// $all_files="<script type='text/javascript' language='javascript' src='upload/lytebox/lytebox.js'></script>
	// <link rel='stylesheet' href='upload/lytebox/lytebox.css' type='text/css' media='screen' />";
	// }else{
    // $all_files="";
	// }
	// $file_arr="";
	// $file_arr=get_file($col_name,$col_sn);
	// if(empty($file_arr))return;

	// if($file_arr){
	  // $i=1;
		// foreach($file_arr as $files_sn => $file_info){

			// if($show_mode=="filename"){
			  // if($file_info['kind']=="file"){
					// $all_files.="<div>({$i}) {$file_info['link']}</div>";
				// }else{
					// $all_files.="<div>({$i}) {$file_info['url']}</div>";
				// }
			// }else{
			  // if($file_info['kind']=="file"){
     			// $linkto=$file_info['path'];
					// $description=$file_info['description'];
					// $thumb_pic="upload/downloads.png";
					// $rel="";
				// }else{
					// $linkto=$file_info['path'];
					// $description=$file_info['description'];
					// $thumb_pic=($thumb)?$file_info['tb_path']:$file_info['path'];
					// $rel="rel='lyteshow[{$col_name}_{$course_sn}]' title='{$description}'";
				// }
				
				// //描述顯示
				// $show_description_txt=($show_description)?"<div style='height:40px;font-size:11px;font-weight:normal;overflow:hidden;text-align:center;'><a href='{$linkto}' $rel style='color:#404040;font-size:11px;font-weight:normal;line-height:1;text-decoration:none;'>{$description}</a></div>":"";
				
				
				// //下載次數顯示
				// $show_dl_txt=($show_dl)?"<img src='upload/dl_times.gif' alt='download counter' title='download counter' align='absmiddle' hspace=4 border=0>: {$file_info['counter']}":"";
				
				// $width=($thumb)?110:400;
				// $pic_height=($thumb)?90:300;
				// $height=($thumb)?100:320;
				// $height+=($show_description)?30:0;
				
				// $all_files.="<div style='border:0px solid gray;width:{$width}px;height:{$height}px;float:left;display:inline;margin:2px;'>
					// <a href='{$linkto}' $rel>
					// <div align='center' style=\"border:1px solid #CFCFCF;width:{$width}px;height:{$pic_height}px;overflow:hidden;margin:2px auto;background-image:url('{$thumb_pic}');background-repeat: no-repeat;background-position: center center;cursor:pointer;\">
					// $show_dl_txt
					// </div>
				// </a>
				// $show_description_txt
				// </div>";
			// }

      // $i++;
		// }
	// }else{
    // $all_files="";
	// }
	// $all_files.="<div style='clear:both;'></div>";
	// return $all_files;
// }

//取得單一檔案資料
function get_one_file($files_sn=""){
	global $link;
	$sql = "select * from files_center where `files_sn`='{$files_sn}'";

 	$result=mysqli_query($link, $sql) or die_content(mysqli_errno($link).mysqli_error($link)."查詢資料失敗");
 	$all=mysqli_fetch_array($result,MYSQLI_ASSOC);
 	return $all;
}

//自動編號
function auto_sort($col_name="",$col_sn=""){
	global $link;

	$sql = "select max(sort) from files_center where `col_name`='{$col_name}' and `col_sn`='{$col_sn}'";

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

	if($file['kind']=="img"){
		header("location:"._FILES_CENTER_IMAGE_URL."/{$file['file_name']}");
	}else{
		header("location:"._FILES_CENTER_URL."/{$file['file_name']}");
	}
}

if(@$_GET['fop']=="dl"){
  add_file_counter($_GET['files_sn']);
}

?>
