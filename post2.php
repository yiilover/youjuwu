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
 * $Createtime: 2014-09-15 22:02
 */
if($_POST){
    print_r($_FILES);
    $source = $_FILES['Filedata']['tmp_name'];

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

?>



<form action="post2.php" method="post" enctype="multipart/form-data" name="theForm" >
        <table cellspacing="1" cellpadding="3" width="100%">
            <tr>
                <td class="label">
    ����csv�ļ�</td>
                <td><input name="Filedata" type="file" size="10"><input name="submit" type="submit" id="submit" value="" class="button" /></td>
            </tr>
        </table>
    </form>