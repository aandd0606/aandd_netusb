<?php
//輸入陣列產生單選表單
function array_to_radio($arr=array(),$use_v=false,$name="default",$default_val="",$validate=false){
	if(empty($arr))return;
	$opt="";
	foreach($arr as $i=>$v){
		$val=($use_v)?$v:$i;
		$checked=($val==$default_val)?"checked='checked'":"";
		$validate_check=($validate)?"class='required'":"";
		$opt.="<input type='radio' name='{$name}' id='{$val}' value='{$val}' $validate_check $checked><label for='{$val}' style='margin-right:15px;'> $v</label>";
	}
	return $opt;
}

//取出列表的陣列值
//取得資料庫一筆資料的資料陣列
function get_list_data_arr_from_sn($link="",$table_name="",$sn_name="",$sn=""){
	$data_list=array();
    if(empty($sn_name)){
        $sql="select * from `{$table_name}`";
    }else{
        $sql="select * from `{$table_name}` where {$sn_name}='{$sn}'";
    }
	$result=mysql_query($sql,$link) or die_content("取得{$table_name}資料失敗".mysql_error());

	if(empty($sn_name)){
        while($data=mysql_fetch_assoc($result)){
            $data_list[]=$data;
        }
        return $data_list;
    }else{
        $data=mysql_fetch_assoc($result);
        return $data;
    }
}
?>