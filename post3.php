<?php

/**
 * ��Ӱ��� 
 * ============================================================================
 * * ��Ȩ���� 2009-2014 ��Ӱ���������������Ȩ����
 * ��վ��ַ: http://www.ddecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: Ozil <admin@ddecshop.com>
 * ��Ȩ����֧��: 1838225378@qq.com
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