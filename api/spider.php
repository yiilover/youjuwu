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
 * $Createtime: 2014-09-09 00:17
 */
print_r($_POST);

//检查用户是否存在

$newusername = trim($_GET['newusername']);
$newpassword = trim($_GET['newpassword']);
$newemail = strtolower(trim($_GET['newemail']));

if(!$newusername || !isset($_GET['confirmed']) && !$newpassword || !isset($_GET['confirmed']) && !$newemail) {
    cpmsg('members_add_invalid', '', 'error');
}

if(C::t('common_member')->fetch_uid_by_username($newusername) || C::t('common_member_archive')->fetch_uid_by_username($newusername)) {
    cpmsg('members_add_username_duplicate', '', 'error');
}

loaducenter();

$uid = uc_user_register(addslashes($newusername), $newpassword, $newemail);
if($uid <= 0) {
    if($uid == -1) {
        cpmsg('members_add_illegal', '', 'error');
    } elseif($uid == -2) {
        cpmsg('members_username_protect', '', 'error');
    } elseif($uid == -3) {
        if(empty($_GET['confirmed'])) {
            cpmsg('members_add_username_activation', 'action=members&operation=add&addsubmit=yes&newgroupid='.$_GET['newgroupid'].'&newusername='.rawurlencode($newusername), 'form');
        } else {
            list($uid,, $newemail) = uc_get_user(addslashes($newusername));
        }
    } elseif($uid == -4) {
        cpmsg('members_email_illegal', '', 'error');
    } elseif($uid == -5) {
        cpmsg('members_email_domain_illegal', '', 'error');
    } elseif($uid == -6) {
        cpmsg('members_email_duplicate', '', 'error');
    }
}

$group = C::t('common_usergroup')->fetch($_GET['newgroupid']);
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
C::t('common_member')->insert($uid, $newusername, $password, $newemail, 'Manual Acting', $_GET['newgroupid'], $init_arr, $newadminid);
if($_GET['emailnotify']) {
    if(!function_exists('sendmail')) {
        include libfile('function/mail');
    }
    $add_member_subject = lang('email', 'add_member_subject');
    $add_member_message = lang('email', 'add_member_message', array(
        'newusername' => $newusername,
        'bbname' => $_G['setting']['bbname'],
        'adminusername' => $_G['member']['username'],
        'siteurl' => $_G['siteurl'],
        'newpassword' => $newpassword,
    ));
    if(!sendmail("$newusername <$newemail>", $add_member_subject, $add_member_message)) {
        runlog('sendmail', "$newemail sendmail failed.");
    }
}

updatecache('setting');
cpmsg('members_add_succeed', '', 'succeed', array('username' => $newusername, 'uid' => $uid));