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
 * $Createtime: 2014-09-15 16:17
 */
//print_r($_FILES['Filedata']);

//$file = 'D:\images/5587200.gif'; //Ҫ�ϴ����ļ�
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
// localhost:8888��fiddler�Ĵ������ô�ѡ��������fiddlerץ��post������

//curl_setopt($ch, CURLOPT_PROXY, 'localhost:8888');
//������һ�����ע�ͣ���ȻFiddlerץ����Post��http����
//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    //1.�ļ�·��֮ǰ����Ҫ��@
    //2.�ļ�·�������ľͻ�ʧ�ܣ�����'img_1'=>'@C:\Documents and Settings\Administrator\����\Android��ֽ\androids.gif'
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
<!--    ����csv�ļ�</td>-->
<!--                <td><input name="Filedata" type="file" size="10"><input name="submit" type="submit" id="submit" value="" class="button" /></td>-->
<!--            </tr>-->
<!--        </table>-->
<!--    </form>-->