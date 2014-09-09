<?php

/**
 * ��Ӱ��� 
 * ============================================================================
 * * ��Ȩ���� 2009-2014 ��Ӱ���������������Ȩ��
 * ��վ��ַ: http://www.ddecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: Ozil <admin@ddecshop.com>
 * ��Ȩ����֧��: 1838225378@qq.com
 * $Createtime: 2014-09-09 00:17
 */
print_r($_POST);

//user register
require '../source/class/class_core.php';
$newusername = trim($_POST['username']);
$newpassword = trim($_POST['userid']);
$newemail = $newpassword . '@youjwu.com';



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

//thread post




































