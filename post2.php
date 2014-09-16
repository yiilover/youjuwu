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
    分类csv文件</td>
                <td><input name="Filedata" type="file" size="10"><input name="submit" type="submit" id="submit" value="" class="button" /></td>
            </tr>
        </table>
    </form>