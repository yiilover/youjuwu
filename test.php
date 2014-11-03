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
//$_POST['mob1']='1332613****';
//$_POST['mob2']='3557';
//$mob='';
//$mob1=(isset($_POST['mob1']) && !empty($_POST['mob1']))?$_POST['mob1']:'';
//$mob2=(isset($_POST['mob2']) && !empty($_POST['mob2']))?$_POST['mob2']:'';
//if(strlen($mob2)==4 && preg_match('/^13|15[.*]$/',$mob1,$return) && strpos($mob1,'****')){
//    $mob=str_replace('****','',$mob1).$mob2;
//}
//echo 'mob='.$mob;
//$_POST['attachment']='http://img5.baixing.net/19992abf65a65b9faafd48b5cbed987b.jpg_bi';
//$attachment=explode('###',$_POST['attachment']);
//print_r($attachment);


$time='2014年10月18日 20:57';
echo $str = trantime($time);
echo '<br>';
$date = date('Y-m-d H:i:s',$str);
echo $str2=strtotime($date);
function trantime($time){
    if(!$time){
        return;
    }else{
        preg_match('/(\d+)年(\d+)月(\d+)日 (\d+):(\d+)/',$time,$t);
        $unixtimestamp = mktime($t[4], $t[5], 0, $t[2], $t[3], $t[1]);
        return $unixtimestamp;
    }
}



?>
