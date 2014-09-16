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
 * $Createtime: 2014-09-15 16:17
 */
//print_r($_FILES['Filedata']);

//$file = 'D:\images/5587200.gif'; //要上传的文件
//$url  = 'http://www.youjuwu.com/test.php';//target url
//
//$fields['f'] = '@'.$file;
//
//$ch = curl_init();
//
//curl_setopt($ch, CURLOPT_URL, $url );
//curl_setopt($ch, CURLOPT_POST, 1 );
//curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
//
//curl_exec( $ch );
//
//if ($error = curl_error($ch) ) {
//    die($error);
//}
//curl_close($ch);
//print_r($_POST);



$ch=curl_init('http://www.youjuwu.com/post.php');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// localhost:8888是fiddler的代理，设置此选项用于让fiddler抓获post的请求

//curl_setopt($ch, CURLOPT_PROXY, 'localhost:8888');
//下面这一句必须注释，不然Fiddler抓不到Post的http请求
//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    //1.文件路径之前必须要加@
    //2.文件路径带中文就会失败，例如'img_1'=>'@C:\Documents and Settings\Administrator\桌面\Android壁纸\androids.gif'
    array('uname'=>'wqfghgfh','img_1'=>'@D:\images/5587200.gif')
);
//echo 8;die;
$data=curl_exec($ch);
curl_close($ch);

echo $data;

?>

<!--<form action="test.php" method="post" enctype="multipart/form-data" name="theForm" >-->
<!--        <table cellspacing="1" cellpadding="3" width="100%">-->
<!--            <tr>-->
<!--                <td class="label">-->
<!--    分类csv文件</td>-->
<!--                <td><input name="Filedata" type="file" size="10"><input name="submit" type="submit" id="submit" value="" class="button" /></td>-->
<!--            </tr>-->
<!--        </table>-->
<!--    </form>-->