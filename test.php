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


$time='2014��10��18�� 20:57';
echo $str = trantime($time);
echo '<br>';
$date = date('Y-m-d H:i:s',$str);
echo $str2=strtotime($date);
function trantime($time){
    if(!$time){
        return;
    }else{
        preg_match('/(\d+)��(\d+)��(\d+)�� (\d+):(\d+)/',$time,$t);
        $unixtimestamp = mktime($t[4], $t[5], 0, $t[2], $t[3], $t[1]);
        return $unixtimestamp;
    }
}



?>
