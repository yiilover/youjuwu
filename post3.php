<?php

/**
 * 乐影软件 
 * ============================================================================
 * * 版权所有 2009-2014 乐影软件，并保留所有权利。
 * 网站地址: http://www.ddecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Ozil <admin@ddecshop.com>
 * 授权技术支持: 1838225378@qq.com
 * $Createtime: 2014-09-15 22:33
 */
if($_POST){

    $attach = $_FILES['Filedata'];
    $source = $attach['tmp_name'];
    $target = 'd:/data/new.jpg';
    @move_uploaded_file($source,$target);
    echo json_encode($_FILES);
}else{

    $attach = uploadimg('D:/data/img3/p19904754.jpg');
    $source = $attach['tmp_name'];
    echo $source;
    $target = 'd:/data/new.jpg';
    if(!file_exists($source)){
        echo 'tempfile not exist!';
    }
    if(@move_uploaded_file($source, $target)){
        echo 'upload success!';
    }else{
        echo 'upload failed!';
    }
}


function uploadimg($dir){
    $url = 'http://www.youjuwu.com/post.php';
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('Filedata'=>"@".$dir));
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response,true);
    return $result['Filedata'];
}