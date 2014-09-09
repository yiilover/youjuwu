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


//user register
require '../source/class/class_core.php';
$discuz = C::app();
$discuz->init();


$newusername = trim($_POST['username']);
$newpassword = trim($_POST['userid']);
$newemail = $newpassword . '@youjuwu.com';



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
        if(empty($_POST['confirmed'])) {
            cpmsg('members_add_username_activation', 'action=members&operation=add&addsubmit=yes&newgroupid='.$_POST['newgroupid'].'&newusername='.rawurlencode($newusername), 'form');
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

//user avator

//thread post
require_once libfile('class/credit');
require_once libfile('function/post');

//post data
$_POST['action']='newthread';

$pid = intval(getgpc('pid'));
$sortid = intval(getgpc('sortid'));
$typeid = intval(getgpc('typeid'));
$special = intval(getgpc('special'));

parse_str($_POST['extra'], $_POST['extra']);
$_POST['extra'] = http_build_query($_POST['extra']);

$postinfo = array('subject' => '');
$thread = array('readperm' => '', 'pricedisplay' => '', 'hiddenreplies' => '');

$_G['forum_dtype'] = $_G['forum_checkoption'] = $_G['forum_optionlist'] = $tagarray = $_G['forum_typetemplate'] = array();






require_once libfile('function/discuzcode');

$space = array();
space_merge($space, 'field_home');


$addfeedcheck = !empty($space['privacy']['feed']['newthread']) ? 'checked="checked"': '';

$navigation = $navtitle = '';





if($_G['forum']['status'] == 3) {
    $returnurl = 'forum.php?mod=forumdisplay&fid='.$_G['fid'].(!empty($_POST['extra']) ? '&action=list&'.preg_replace("/^(&)*/", '', $_POST['extra']) : '').'#groupnav';
    $nav = get_groupnav($_G['forum']);
    $navigation = ' <em>&rsaquo;</em> <a href="group.php">'.$_G['setting']['navs'][3]['navname'].'</a> '.$nav['nav'];
} else {
    loadcache('forums');
    $returnurl = 'forum.php?mod=forumdisplay&fid='.$_G['fid'].(!empty($_POST['extra']) ? '&'.preg_replace("/^(&)*/", '', $_POST['extra']) : '');
    $navigation = ' <em>&rsaquo;</em> <a href="forum.php">'.$_G['setting']['navs'][2]['navname'].'</a>';

    if($_G['forum']['type'] == 'sub') {
        $fup = $_G['cache']['forums'][$_G['forum']['fup']]['fup'];
        $t_link = $_G['cache']['forums'][$fup]['type'] == 'group' ? 'forum.php?gid='.$fup : 'forum.php?mod=forumdisplay&fid='.$fup;
        $navigation .= ' <em>&rsaquo;</em> <a href="'.$t_link.'">'.($_G['cache']['forums'][$fup]['name']).'</a>';
    }

    if($_G['forum']['fup']) {
        $fup = $_G['forum']['fup'];
        $t_link = $_G['cache']['forums'][$fup]['type'] == 'group' ? 'forum.php?gid='.$fup : 'forum.php?mod=forumdisplay&fid='.$fup;
        $navigation .= ' <em>&rsaquo;</em> <a href="'.$t_link.'">'.($_G['cache']['forums'][$fup]['name']).'</a>';
    }

    $t_link = 'forum.php?mod=forumdisplay&fid='.$_G['fid'].($_POST['extra'] && !IS_ROBOT ? '&'.$_POST['extra'] : '');
    $navigation .= ' <em>&rsaquo;</em> <a href="'.$t_link.'">'.($_G['forum']['name']).'</a>';

    unset($t_link, $t_name);
}

periodscheck('postbanperiods');

if($_G['forum']['password'] && $_G['forum']['password'] != $_G['cookie']['fidpw'.$_G['fid']]) {
    showmessage('forum_passwd', "forum.php?mod=forumdisplay&fid=$_G[fid]");
}

if(empty($_G['forum']['allowview'])) {
    if(!$_G['forum']['viewperm'] && !$_G['group']['readaccess']) {
        showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
    } elseif($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm'])) {
        showmessagenoperm('viewperm', $_G['fid']);
    }
} elseif($_G['forum']['allowview'] == -1) {
    showmessage('forum_access_view_disallow');
}

formulaperm($_G['forum']['formulaperm']);

if(!$_G['adminid'] && $_G['setting']['newbiespan'] && (!getuserprofile('lastpost') || TIMESTAMP - getuserprofile('lastpost') < $_G['setting']['newbiespan'] * 60) && TIMESTAMP - $_G['member']['regdate'] < $_G['setting']['newbiespan'] * 60) {
    showmessage('post_newbie_span', '', array('newbiespan' => $_G['setting']['newbiespan']));
}

$special = $special > 0 && $special < 7 || $special == 127 ? intval($special) : 0;

$_G['forum']['allowpostattach'] = isset($_G['forum']['allowpostattach']) ? $_G['forum']['allowpostattach'] : '';
$_G['group']['allowpostattach'] = $_G['forum']['allowpostattach'] != -1 && ($_G['forum']['allowpostattach'] == 1 || (!$_G['forum']['postattachperm'] && $_G['group']['allowpostattach']) || ($_G['forum']['postattachperm'] && forumperm($_G['forum']['postattachperm'])));
$_G['forum']['allowpostimage'] = isset($_G['forum']['allowpostimage']) ? $_G['forum']['allowpostimage'] : '';
$_G['group']['allowpostimage'] = $_G['forum']['allowpostimage'] != -1 && ($_G['forum']['allowpostimage'] == 1 || (!$_G['forum']['postimageperm'] && $_G['group']['allowpostimage']) || ($_G['forum']['postimageperm'] && forumperm($_G['forum']['postimageperm'])));
$_G['group']['attachextensions'] = $_G['forum']['attachextensions'] ? $_G['forum']['attachextensions'] : $_G['group']['attachextensions'];
require_once libfile('function/upload');
$swfconfig = getuploadconfig($_G['uid'], $_G['fid']);
$imgexts = str_replace(array(';', '*.'), array(', ', ''), $swfconfig['imageexts']['ext']);
$allowuploadnum = $allowuploadtoday = TRUE;
if($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) {
    if($_G['group']['maxattachnum']) {
        $allowuploadnum = $_G['group']['maxattachnum'] - getuserprofile('todayattachs');
        $allowuploadnum = $allowuploadnum < 0 ? 0 : $allowuploadnum;
        if(!$allowuploadnum) {
            $allowuploadtoday = false;
        }
    }
    if($_G['group']['maxsizeperday']) {
        $allowuploadsize = $_G['group']['maxsizeperday'] - getuserprofile('todayattachsize');
        $allowuploadsize = $allowuploadsize < 0 ? 0 : $allowuploadsize;
        if(!$allowuploadsize) {
            $allowuploadtoday = false;
        }
        $allowuploadsize = $allowuploadsize / 1048576 >= 1 ? round(($allowuploadsize / 1048576), 1).'MB' : round(($allowuploadsize / 1024)).'KB';
    }
}
$allowpostimg = $_G['group']['allowpostimage'] && $imgexts;
$enctype = ($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) ? 'enctype="multipart/form-data"' : '';
$maxattachsize_mb = $_G['group']['maxattachsize'] / 1048576 >= 1 ? round(($_G['group']['maxattachsize'] / 1048576), 1).'MB' : round(($_G['group']['maxattachsize'] / 1024)).'KB';

$_G['group']['maxprice'] = isset($_G['setting']['extcredits'][$_G['setting']['creditstrans']]) ? $_G['group']['maxprice'] : 0;

$extra = !empty($_POST['extra']) ? rawurlencode($_POST['extra']) : '';
$notifycheck = empty($emailnotify) ? '' : 'checked="checked"';
$stickcheck = empty($sticktopic) ? '' : 'checked="checked"';
$digestcheck = empty($addtodigest) ? '' : 'checked="checked"';

$subject = isset($_POST['subject']) ? dhtmlspecialchars(censor(trim($_POST['subject']))) : '';
$subject = !empty($subject) ? str_replace("\t", ' ', $subject) : $subject;
$message = isset($_POST['message']) ? censor($_POST['message']) : '';
$polloptions = isset($polloptions) ? censor(trim($polloptions)) : '';
$readperm = isset($_POST['readperm']) ? intval($_POST['readperm']) : 0;
$price = isset($_POST['price']) ? intval($_POST['price']) : 0;

if(empty($bbcodeoff) && !$_G['group']['allowhidecode'] && !empty($message) && preg_match("/\[hide=?\d*\].*?\[\/hide\]/is", preg_replace("/(\[code\](.+?)\[\/code\])/is", ' ', $message))) {
    showmessage('post_hide_nopermission');
}


$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';

list($seccodecheck, $secqaacheck) = seccheck('post', $_POST['action']);

$_G['group']['allowpostpoll'] = $_G['group']['allowpost'] && $_G['group']['allowpostpoll'] && ($_G['forum']['allowpostspecial'] & 1);
$_G['group']['allowposttrade'] = $_G['group']['allowpost'] && $_G['group']['allowposttrade'] && ($_G['forum']['allowpostspecial'] & 2);
$_G['group']['allowpostreward'] = $_G['group']['allowpost'] && $_G['group']['allowpostreward'] && ($_G['forum']['allowpostspecial'] & 4);
$_G['group']['allowpostactivity'] = $_G['group']['allowpost'] && $_G['group']['allowpostactivity'] && ($_G['forum']['allowpostspecial'] & 8);
$_G['group']['allowpostdebate'] = $_G['group']['allowpost'] && $_G['group']['allowpostdebate'] && ($_G['forum']['allowpostspecial'] & 16);
$usesigcheck = $_G['uid'] && $_G['group']['maxsigsize'] ? 'checked="checked"' : '';
$ordertypecheck = !empty($thread['tid']) && getstatus($thread['status'], 4) ? 'checked="checked"' : '';
$imgcontentcheck = !empty($thread['tid']) && getstatus($thread['status'], 15) ? 'checked="checked"' : '';
$specialextra = !empty($_POST['specialextra']) ? $_POST['specialextra'] : '';
$_G['forum']['threadplugin'] = dunserialize($_G['forum']['threadplugin']);

if($specialextra && $_G['group']['allowpost'] && $_G['setting']['threadplugins'] &&
    (!array_key_exists($specialextra, $_G['setting']['threadplugins']) ||
        !@in_array($specialextra, is_array($_G['forum']['threadplugin']) ? $_G['forum']['threadplugin'] : dunserialize($_G['forum']['threadplugin'])) ||
            !@in_array($specialextra, $_G['group']['allowthreadplugin']))) {
    $specialextra = '';
}
if($special == 3 && !isset($_G['setting']['extcredits'][$_G['setting']['creditstrans']])) {
    showmessage('reward_credits_closed');
}
$_G['group']['allowanonymous'] = $_G['forum']['allowanonymous'] || $_G['group']['allowanonymous'] ? 1 : 0;

if($_POST['action'] == 'newthread' && $_G['forum']['allowspecialonly'] && !$special) {
    if($_G['group']['allowpostpoll']) {
        $special = 1;
    } elseif($_G['group']['allowposttrade']) {
        $special = 2;
    } elseif($_G['group']['allowpostreward']) {
        $special = 3;
    } elseif($_G['group']['allowpostactivity']) {
        $special = 4;
    } elseif($_G['group']['allowpostdebate']) {
        $special = 5;
    } elseif($_G['group']['allowpost'] && $_G['setting']['threadplugins'] && $_G['group']['allowthreadplugin']) {
        if(empty($_POST['specialextra'])) {
            foreach($_G['forum']['threadplugin'] as $tpid) {
                if(array_key_exists($tpid, $_G['setting']['threadplugins']) && @in_array($tpid, $_G['group']['allowthreadplugin'])){
                    $specialextra=$tpid;
                    break;
                }
            }
        }
        $threadpluginary = array_intersect($_G['forum']['threadplugin'], $_G['group']['allowthreadplugin']);
        $specialextra = in_array($specialextra, $threadpluginary) ? $specialextra : '';
    }

    if(!$special && !$specialextra) {
        showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
    }
}

if(!$sortid && !$specialextra) {
    $postspecialcheck[$special] = ' class="a"';
}

$editorid = 'e';
$_G['setting']['editoroptions'] = str_pad(decbin($_G['setting']['editoroptions']), 3, 0, STR_PAD_LEFT);
$editormode = $_G['setting']['editoroptions']{0};
$allowswitcheditor = $_G['setting']['editoroptions']{1};
$editor = array(
    'editormode' => $editormode,
    'allowswitcheditor' => $allowswitcheditor,
    'allowhtml' => $_G['forum']['allowhtml'],
    'allowsmilies' => $_G['forum']['allowsmilies'],
    'allowbbcode' => $_G['forum']['allowbbcode'],
    'allowimgcode' => $_G['forum']['allowimgcode'],
    'allowresize' => 1,
    'allowchecklength' => 1,
    'allowtopicreset' => 1,
    'textarea' => 'message',
    'simplemode' => !isset($_G['cookie']['editormode_'.$editorid]) ? !$_G['setting']['editoroptions']{2} : $_G['cookie']['editormode_'.$editorid],
);
if($specialextra) {
    $special = 127;
}

if($_POST['action'] == 'newthread') {
    $policykey = 'post';
} elseif($_POST['action'] == 'reply') {
    $policykey = 'reply';
} else {
    $policykey = '';
}
if($policykey) {
    $postcredits = $_G['forum'][$policykey.'credits'] ? $_G['forum'][$policykey.'credits'] : $_G['setting']['creditspolicy'][$policykey];
}

$albumlist = array();
if(helper_access::check_module('album') && $_G['group']['allowupload'] && $_G['uid']) {
    $query = C::t('home_album')->fetch_all_by_uid($_G['uid'], 'updatetime');
    foreach($query as $value) {
        if($value['picnum']) {
            $albumlist[] = $value;
        }
    }
}

$posturl = "action=$_POST[action]&fid=$_G[fid]".
    (!empty($_G['tid']) ? "&tid=$_G[tid]" : '').
    (!empty($pid) ? "&pid=$pid" : '').
    (!empty($special) ? "&special=$special" : '').
    (!empty($sortid) ? "&sortid=$sortid" : '').
    (!empty($typeid) ? "&typeid=$typeid" : '').
    (!empty($_POST['firstpid']) ? "&firstpid=$firstpid" : '').
    (!empty($_POST['addtrade']) ? "&addtrade=$addtrade" : '');

if($_POST['action'] == 'reply') {
    check_allow_action('allowreply');
} else {
    check_allow_action('allowpost');
}

if($special == 4) {
    $_G['setting']['activityfield'] = $_G['setting']['activityfield'] ? dunserialize($_G['setting']['activityfield']) : array();
}
if(helper_access::check_module('album') && $_G['group']['allowupload'] && $_G['setting']['albumcategorystat'] && !empty($_G['cache']['albumcategory'])) {
    require_once libfile('function/portalcp');
}
$navtitle = lang('core', 'title_'.$_POST['action'].'_post');

if($_POST['action'] == 'newthread' || $_POST['action'] == 'newtrade') {
    loadcache('groupreadaccess');
    $navtitle .= ' - '.$_G['forum']['name'];
    require_once libfile('post/newthread', 'include');
} elseif($_POST['action'] == 'reply') {
    $navtitle .= ' - '.$thread['subject'].' - '.$_G['forum']['name'];
    require_once libfile('post/newreply', 'include');
} elseif($_POST['action'] == 'edit') {
    loadcache('groupreadaccess');
    $navtitle .= ' - '.$thread['subject'].' - '.$_G['forum']['name'];
    require_once libfile('post/editpost', 'include');
}

function check_allow_action($action = 'allowpost') {
    global $_G;
    if(isset($_G['forum'][$action]) && $_G['forum'][$action] == -1) {
        showmessage('forum_access_disallow');
    }
}
function recent_use_tag() {
    $tagarray = $stringarray = array();
    $string = '';
    $i = 0;
    $query = C::t('common_tagitem')->select(0, 0, 'tid', 'itemid', 'DESC', 10);
    foreach($query as $result) {
        if($i > 4) {
            break;
        }
        if($tagarray[$result['tagid']] == '') {
            $i++;
        }
        $tagarray[$result['tagid']] = 1;
    }
    if($tagarray) {
        $query = C::t('common_tag')->fetch_all(array_keys($tagarray));
        foreach($query as $result) {
            $tagarray[$result[tagid]] = $result['tagname'];
        }
    }
    return $tagarray;
}



































