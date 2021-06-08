<?php

function get_only_digits($str_value, $int_len = 0) {
	$str_digits = "0123456789";
	$str_ret = ""; 

    for($int_i1 =0; $int_i1<strlen($str_value); $int_i1++) {
        if (strpos("0123456789", $str_value[$int_i1]) !== false) {
            $str_ret .= $str_value[$int_i1];
        }
    }

    if (($int_len != 0) && ($int_len > strlen($str_ret))) {
        while (strlen($str_ret) < $int_len) {
            $str_ret = "0" . $str_ret;
        }
    }

	return $str_ret;
}


function format_date($str_date) {
    $arr_date = explode("-", $str_date);
    return  $arr_date[2] . "/" . $arr_date[1]. "/" . $arr_date[0]; 
}

function format_value($str_value) {
    $int_len = strlen($str_value);
    $int_i = 0;

    while ($int_i < $int_len) {
        if ( $str_value[$int_i] != "0") {
           break;
        }
        $int_i++;
    }

    if ($int_i == $int_len) {
        return "R$ 0,00";
    }

    $str_ret = substr($str_value, $int_i);
    $int_len = strlen($str_ret);

    if ($int_len == 1) {
        return "R$ 0,0" . $str_ret;
    }
    if ($int_len == 2) {
        return "R$ 0," . $str_ret;
    }
    
    $str_ret = substr($str_ret, 0, $int_len - 2) . "," . substr($str_ret, $int_len - 2);

    return "R$ " . $str_ret;
}

function create_text_file($str_file_name, $str_content) {
    
    $str_os = strtolower(php_uname("s"));
    $str_mode = "w";

    if ( str_contains($str_os, "windows") == false) {
        $str_mode .= "t";
    }

    //$obj_file = fopen("../../output/" . $str_file_name, $str_mode);
    $obj_file = fopen("./output/" . $str_file_name, $str_mode);
    fwrite($obj_file, $str_content);
    fclose($obj_file);

}
?>