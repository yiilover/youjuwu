<?php

//config

//user register
require '../source/class/class_core.php';
require '../data/img.inc.php';
C::app()->init();
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
    if($_POST['userface']!='http://img3.douban.com/icon/user_normal.jpg'){
        $filename = basename($_POST['userface']);
        preg_match("/u([0-9]*?)-/",$filename,$return);
        $avatarnum = $return[1];
        $imgurl = 'http://img3.douban.com/icon/ul'.$avatarnum.'.jpg';
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
$dateline = strtotime($_POST['posttime']);
$lastpost = $dateline;
$publishdate = $dateline;
$subject = $_POST['fullsubject']?$_POST['fullsubject']:$_POST['subject'];
$message = explode("#SLICE#",$_POST['message']);
$message = $message[0];
$message = html2bbcode($message);
$lastposter = $author;
$status = '32';
$icon = '-1';

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
));
updatemembercount($uid, array('extcredits2' => 2, 'posts' => 1, 'threads' =>1));
updatemoderate('tid', $tid);
C::t('forum_forum')->update_forum_counter($fid, 1, 1, 1);
//attachment upload
if(!empty($_POST['attachment'])){
    preg_match_all("/src=\"(.*?)\"/",$_POST['attachment'],$templist);
    foreach($templist[1] as $r){
        $dir = saveimg($r);
        if($dir){
            $aid = uploadimg($dir);
            $attachnew[$aid]['description']='';
        }
    }
}

$modnewthreads = false;
updateattach($modnewthreads, $tid, $pid, $attachnew);
sleep(1);


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































