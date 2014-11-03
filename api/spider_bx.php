<?php

//config

//user register
require '../source/class/class_core.php';
require '../data/img.inc.php';
C::app()->init();
if(!isset($_POST['username']) || !isset($_POST['userid'])){
    exit;
}

$newusername = trim($_POST['username']);
if(strlen($newusername)<3) exit();
$newpassword = trim($_POST['userid']);
$newemail = $newpassword . '@youjuwu.com';
$user = C::t('common_member')->fetch_by_username($newusername);


if(empty($user)){
    loaducenter();
    $uid = uc_user_register(addslashes($newusername), $newpassword, $newemail);
    if($uid <= 0) {
        exit();
    }
    $group = C::t('common_usergroup')->fetch($_POST['newgroupid']);
    $newadminid = in_array($group['radminid'], array(1, 2, 3)) ? $group['radminid'] : ($group['type'] == 'special' ? -1 : 0);
    if($group['radminid'] == 1) {
        cpmsg('members_add_admin_none', '', 'error');
    }
    if(in_array($group['groupid'], array(5, 6, 7))) {
        cpmsg('members_add_ban_all_none', '', 'error');
    }
    $profile = $verifyarr = array();
    loadcache('fields_register');
    $init_arr = explode(',', $_G['setting']['initcredits']);
    $password = md5(random(10));
    C::t('common_member')->insert($uid, $newusername, $password, $newemail, 'Manual Acting', $_POST['newgroupid'], $init_arr, $newadminid);
    //user avatar
    if(isset($_POST['userface']) && $_POST['userface']!='http://s.baixing.net/img/style/avatar_150.jpg'){
        $imgurl = trim($_POST['userface']);
        $dir = saveimg($imgurl,'user');
        uploadavatar($dir,$uid);
    }
}else{
    $newusername = $user['username'];
    $uid = $user['uid'];
}






//thread post
require_once libfile('class/credit');
require_once libfile('function/post');
require_once libfile('function/editor');
$fid = $_POST['fid'];
$author = $newusername;
$authorid = $uid;
$dateline=transtime($_POST['posttime']);
$lastpost = $dateline;
$publishdate = $dateline;
$subject = trim($_POST['subject']);
$message = $_POST['message'];
$message = html2bbcode($message);
$lastposter = $author;
$status = '32';
$icon = '-1';

$sex=(isset($_POST['sex']) && !empty($_POST['sex']))?$_POST['sex']:'';
$sex='女生'?0:1;
$age=(isset($_POST['age']) && !empty($_POST['age']))?$_POST['age']:'';
$qq=(isset($_POST['qq']) && !empty($_POST['qq']))?$_POST['qq']:'';
$mob='';
$mob1=(isset($_POST['mob1']) && !empty($_POST['mob1']))?$_POST['mob1']:'';
$mob2=(isset($_POST['mob2']) && !empty($_POST['mob2']))?$_POST['mob2']:'';
if(strlen($mob2)==4 && preg_match('/^13|15[.*]$/',$mob1,$return) && strpos($mob1,'****')){
    $mob=str_replace('****','',$mob1).$mob2;
}


if(C::t('forum_thread')->fetch_all_by_authorid_and_subject($authorid, $subject)) exit();

$newthread = array(
    'fid' => $fid,
    'author' => $author,
    'authorid' => $authorid,
    'dateline' => $dateline,
    'lastpost' => $lastpost,
    'lastposter' => $lastposter,
    'subject' => $subject,
    'status' => $status,
    'icon' => $icon,
);
$tid = C::t('forum_thread')->insert($newthread, true);
$first = '1';
$useip = '127.0.0.1';
$pid = insertpost(array(
    'fid' => $fid,
    'tid' => $tid,
    'first' => $first,
    'author' => $author,
    'authorid' => $authorid,
    'subject' => $subject,
    'dateline' => $dateline,
    'message' => $message,
    'useip' => $useip,
    'sex' => !empty($sex)?$sex:0,
    'age' => !empty($age)?$age:'',
    'qq' => !empty($qq)?$qq:'',
    'mob' => !empty($mob)?$mob:'',
));
updatemembercount($uid, array('extcredits2' => 2, 'posts' => 1, 'threads' =>1));
updatemoderate('tid', $tid);
C::t('forum_forum')->update_forum_counter($fid, 1, 1, 1);
//attachment upload
if(!empty($_POST['attachment'])){
    $attachment=explode('###',$_POST['attachment']);
    foreach($attachment as $r){
        $dir = saveimg($r);
        if($dir){
            $aid = uploadimg($dir);
            $attachnew[$aid]['description']='';
        }
    }
}

$modnewthreads = false;
updateattach($modnewthreads, $tid, $pid, $attachnew);
//sleep(1);


function insertpost($data) {
    if(isset($data['tid'])) {
        $thread = C::t('forum_thread')->fetch($data['tid']);
        $tableid = $thread['posttableid'];
    } else {
        $tableid = $data['tid'] = 0;
    }
    $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
    $data = array_merge($data, array('pid' => $pid));
    C::t('forum_post')->insert($tableid, $data);
    if($pid % 1024 == 0) {
        C::t('forum_post_tableid')->delete_by_lesspid($pid);
    }
    savecache('max_post_id', $pid);
    return $pid;
}
function saveimg($imgurl,$type='forum'){
    $filename = basename($imgurl);
    $dir=$type=='user'?USERIMG.$filename:FORUMIMG.$filename;
    $dir=str_replace('.jpg_bi','.jpg',$dir);
    $dir=str_replace('.jpg_sq','.jpg',$dir);
    getimg($imgurl,$dir);
    return $dir;
}
function getimg($url = "", $filename = ""){
    $hander = curl_init();
    $fp = fopen($filename,'wb');
    curl_setopt($hander,CURLOPT_URL,$url);
    curl_setopt($hander,CURLOPT_FILE,$fp);
    curl_setopt($hander,CURLOPT_HEADER,0);
    curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($hander,CURLOPT_TIMEOUT,60);
    curl_setopt($hander, CURLINFO_HEADER_OUT, true);
    curl_exec($hander);
    curl_close($hander);
    fclose($fp);
    return true;
}
function uploadimg($dir){
    $url = 'http://www.youjuwu.com/upload.php';
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('Filedata'=>'@'.$dir));
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res,true);
}
function uploadavatar($dir,$uid){
    $url = 'http://www.youjuwu.com/uc_server/index.php?m=user&a=uploadavatar2';
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('Filedata'=>'@'.$dir,'uid'=>$uid));
    curl_exec($ch);
    curl_close($ch);
}
function transtime($time){
    if(!$time){
        return;
    }else{
        preg_match('/(\d+)年(\d+)月(\d+)日 (\d+):(\d+)/',$time,$t);
        return  mktime($t[4],$t[5],0,$t[2],$t[3],$t[1]);
    }
}






























