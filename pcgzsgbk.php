<?php

define('PW', 'pcgzs');
if($_REQUEST['pw'] !== PW){
	exit('密码错误');
}
error_reporting(E_ERROR);
define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');
require './source/class/class_core.php';
require './source/function/function_forum.php';
C::app()->init();

/****************配置信息华丽的分割线***************/
$config = array
(
	
	'使用签名'			=>			1,							
	'注册会员邮件后缀'	=>			'126.com|||163.com',		
	'数据库用户'			=>			0,							
	'回复用户名'			=>			'回复用户1|||回复用户2|||回复用户3|||回复用户4|||回复用户5|||回复用户6',
	'用户密码参数'		=>			'jksdjfs',					
	'回复间隔'			=>			array( 30 , 60 ),			
	'回复用户签名'		=>			'签名1|||签名2|||签名3|||签名4|||签名5|||签名6|||签名7',
	'分隔符'				=>			'|||',
	'附件权限组'			=>			'',
	'附件价格'			=>			'',
	'附件隐藏'			=>			'1',
	'fname'				=>			array(						
										'板块1'=>'1',
										'板块2'=>'2',
									),
	'typename'			=>			array(						
										'分类1'=>'1',
										'分类2'=>'2',
									),
	'sortname'			=>			array(						
										
									),
	'ucenter密码'		=>			'123456',																		
						
);
/****************配置信息华丽的分割线***************/
//SELECT uid,username FROM `pre_common_member` AS t1 JOIN (SELECT ROUND(RAND() * (SELECT MAX(uid) FROM `pre_common_member`)) AS uid) AS t2 WHERE t1.uid >= t2.uid ORDER BY t1.uid ASC LIMIT 1;
$j = new jiekou();
if(empty($_POST)){
	$j->makeCat(getgpc('type'));
}else{
	define('NOROBOT', TRUE);
	$j->checkAndInitData();
	runhooks();
	set_rssauth();
	loadforum();
	require_once libfile('function/misc');
	require_once libfile('function/member');
	loaducenter();
	$j->logonUser();
	$j->newthread();
	$j->reply();
	exit('发布成功');
}
class jiekou
{
	public $replys 	= array();
	public $file	= array();
	/**
	 * 生成栏目列表
	 */
	public function makeCat($type = 'forum')
	{
		function maketree($ar,$id,$pre)
		{
			$ids='';
			foreach($ar as $k=>$v){
				$pid=$v['fud'];
				$cname=$v['name'];
				$cid=$v['fid'];
				if($pid==$id)
				{
					$ids.="<option value='$cid'>{$pre}{$cname}</option>";
					foreach($ar as $kk=>$vv)
					{
						$pp=$vv['pid'];
						if($pp==$cid)
						{
							$ids.=maketree($ar,$cid,$pre."&nbsp;&nbsp;");
							break;
						}
					}
				}
			}
			return $ids;
		}
		if($type == 'group'){
			$cates = DB::fetch_all("SELECT * FROM ".DB::table('forum_forum')." WHERE status='3' AND type = 'sub'");
		}else{
			$cates = C::t('forum_forum')->fetch_all_valid_forum();
		}
		echo "<select name='list'>";
		echo maketree($cates,0,'');
		echo '</select>';
		exit;
	}
	
	/**
	 * 检测以及初始化数据
	 */
	public function checkAndInitData()
	{
		global $_G , $config;
		$fid = intval(getgpc('fid'));
		$sortid = intval(getgpc('sortid'));
		$typeid = intval(getgpc('typeid'));
		$special = intval(getgpc('special'));
		if(empty($sortid) && !empty($_POST['sortname'])){
			$_POST['sortid'] = intval($config['sortname'][trim($_POST['sortname'])]);
		}
		if(empty($typeid) && !empty($_POST['typename'])){
			$_POST['typeid'] = intval($config['typename'][trim($_POST['typename'])]);
		}
		if(empty($fid) && !empty($_GET['fname'])){
			$_GET['fid'] = intval($config['fname'][trim($_GET['fname'])]);
		}
		if(empty($_GET['fid']) || empty($_GET['subject']) || empty($_GET['message']) || empty($_GET['username'])){
			exit('错误缺少参数');
		}else{
			$this->subject = trim($_POST['subject']);
			$messageArr = explode($config['分隔符'] , trim($_POST['message']));
			$usernameArr = explode($config['分隔符'] , trim($_POST['username']));
			
			$this->message = array_shift($messageArr);
			$this->username = array_shift($usernameArr);
			
			isset($_POST['price']) && $this->price = intval(trim($_POST['price']));
			isset($_POST['readperm']) && $this->readperm = intval($_POST['readperm']);
		}
		if(!empty($_POST['publishdate'])){
			$publish_date_arr = explode($config['分隔符'] , trim($_POST['publishdate']));
			$publish_date_tmp = array_shift($publish_date_arr);
			$publish_date = strtotime(trim($publish_date_tmp)); 
			if($publish_date > 0){
				$this->publish_date = $publish_date;
			}else{
				$this->publish_date = $_G['timestamp'];
			}
		}else{
			$this->publish_date = $_G['timestamp'];
		}
		
		if(!empty($_POST['avatar'])){
			$avatar_tmp_arr = explode($config['分隔符'] , trim($_POST['avatar']));
			foreach($avatar_tmp_arr as $k => $v){
				$this->avatar[] = array('username' => $v , 'file'=>$_FILES['avatar'.$k]);
			}
		}
		if(!empty($_FILES)){
			$i = 0;
			while (isset($_FILES['attach'.$i])){
				$this->attach[] = $_FILES['attach'.$i++];
			}
			//unset($_FILES);
			//$_FILES=null;
		}
		
		$this->save_date = $this->publish_date;
		if(!empty($messageArr)){
			if(!empty($usernameArr)){
				$reply_user = $usernameArr;
				$is_username_random = false;
			}elseif(!empty($config['回复用户名'])){
				$is_username_random = true;
				$reply_user = explode('|||', $config['回复用户名']);
			}else{
				$reply_user = $this->username;
			}
			if(!empty($publish_date_arr)){
				$reply_times = $publish_date_arr;
			}
			//签名
			if(!empty($_POST['signature'])){
				$reply_sig = explode($config['分隔符'], trim($_POST['signature']));
				$this->sightml = array_shift($reply_sig);
				$is_signature_random = false;
			//随机用户签名
			}elseif(!empty($config['回复用户签名'])){
				$is_signature_random = true;
				$reply_sig = explode('|||', $config['回复用户签名']);
				$this->sightml = array_shift($reply_sig);
			}
			foreach($messageArr as $k => $v)
			{
				$reply_tmp 	= (!$is_username_random && isset($reply_user[$k])) ? $reply_user[$k] : $reply_user[rand(0,count($reply_user)-1)];
				$sig_tmp 	= (!$is_signature_random && isset($reply_sig[$k])) ? $reply_sig[$k] : (empty($reply_sig) ? '' : $reply_sig[rand(0,count($reply_sig)-1)]);
				if(isset($reply_times[$k])){
					$reply_time_tmp = strtotime($reply_times[$k]);
					if($reply_time_tmp > 0){
						 $this->save_date = $reply_time_tmp;
					}else{
						$this->save_date = $reply_time_tmp = $this->save_date + rand($config['回复间隔'][0] , $config['回复间隔'][1]);
					}
				}else{
					$this->save_date = $reply_time_tmp = $this->save_date + rand($config['回复间隔'][0] , $config['回复间隔'][1]);
				}
				$this->replys[$k] = array(
						'message' 		=>	$v , 
						'username'		=>	$reply_tmp ,
						'publishdate' 	=>	$reply_time_tmp ,
						'signature'		=>	$sig_tmp
					);
			}
		}
	}
	
	/**
	 * 得到随机ip
	 */ 
	private function randomip()
	{
		return rand(0,255).'.'.rand(0,255).'.'.rand(0,255).'.'.rand(0,255);
	}
	
	public function logonUser($username = '')
	{
		empty($username) && $username = $this->username;
		global $_G , $config;
		$user = uc_get_user($username);
		$uid = $user[0];
		if($user && !C::t('common_member')->fetch_uid_by_username($username) && !C::t('common_member_archive')->fetch_uid_by_username($username)) {
			exit("错误--ucenter和论坛用户名不一致");
		}elseif($user){
			$member = getuserbyuid($user[0]);
			setloginstatus($member , 0);
		}else{
			$md5 = md5($username.$config['用户密码参数']);
			$password =md5("123456");
			if(empty($config['注册会员邮件后缀'])){
				$mail_ext = '@126.com';
			}else{
				$mail_arr = explode('|||', $config['注册会员邮件后缀']);
				$mail_ext = '@'.$mail_arr[rand(0 , count($mail_arr)-1)];
			}
			$email = substr($md5, 0 , 6).$mail_ext ;
			$ip = $this->randomip();
			$uid = uc_user_register(addslashes($username), $password , $email , '', '', $ip);
			if($uid == -1) {
				exit('错误profile_username_illegal'.addslashes($username));
			} elseif($uid == -2) {
				exit('错误profile_username_protect');
			} elseif($uid == -3) {
				exit('错误profile_username_duplicate');
			} elseif($uid == -4) {
				exit('错误profile_email_illegal');
			} elseif($uid == -5) {
				exit('错误profile_email_domain_illegal');
			} elseif($uid == -6) {
				exit('错误profile_email_duplicate');
			} elseif($uid <= 0) {
				exit('错误undefined_action');
			}
			if(getuserbyuid($uid, 1)) {
				uc_user_delete($uid);
				exit('错误profile_uid_duplicate');
			}
			$groupinfo = array('groupid' => $_G['setting']['newusergroupid']);
			$setregip = null;
			if($_G['setting']['regfloodctrl']) {
				$regip = C::t('common_regip')->fetch_by_ip_dateline($ip, $_G['timestamp']-86400);
				if($regip) {
					if($$regip['count'] >= $_G['setting']['regfloodctrl']) {
						exit('错误ip注册数超过'.$_G['setting']['regfloodctrl']);
					} else {
						$setregip = 1;
					}
				} else {
					$setregip = 2;
				}
			}
			if($setregip !== null) {
				if($setregip == 1) {
					C::t('common_regip')->update_count_by_ip($ip);
				} else {
					C::t('common_regip')->insert(array('ip' => $ip, 'count' => 1, 'dateline' => $_G['timestamp']));
				}
			}
			$init_arr = array('credits' => explode(',', $_G['setting']['initcredits']), 'profile'=>array());
			C::t('common_member')->insert($uid, $username, md5($password), $email, $ip, $groupinfo['groupid'], $init_arr);
			if(!empty($this->sightml)){
				C::t('common_member_field_forum')->update($uid, array('sightml' => $this->sightml));
			}
			
			//上传头像
			if(!empty($this->avatar) && function_exists('fsockopen')){
				foreach($this->avatar as $k => $v){
					if(strpos($v['username'] , $username) !== false && is_uploaded_file($v['file']['tmp_name'])){
						$img_file = $v['file'];
						
					}
				}
				if(!empty($img_file)){
					$URL = UC_API.'/ucenter_avatar.php';
					$fp1 = fopen($img_file['tmp_name'], 'rb');
					$avatar = fread($fp1, filesize($img_file['tmp_name'])); //二进制数据
					$post_data['avatar']	=	base64_encode($avatar);
					$post_data['name']		=	$img_file['name'];
					$post_data['uid']		=	$uid;
					$post_data['pw']		=	$config['ucenter密码'];
					$referrer="";
					$URL_Info=parse_url($URL);
					if($referrer==""){
						$referrer=$_SERVER["SCRIPT_URI"];
					}
					foreach ($post_data as $key=>$value){
						$values[]="$key=".urlencode($value);
					}
					$data_string=implode('&',$values);
	
					if (!isset($URL_Info["port"])) {
						$URL_Info["port"]=80;
						$request.="POST ".$URL_Info["path"]." HTTP/1.1\n";
						$request.="Host: ".$URL_Info["host"]."\n";
						$request.="Referer: $referrer\n";
						$request.="Content-type: application/x-www-form-urlencoded\n";
						$request.="Content-length: ".strlen($data_string)."\n";
						$request.="Connection: close\n";
						$request.="\n";
						$request.=$data_string."\n";
					}
					$fp = fsockopen($URL_Info["host"], $URL_Info["port"]);
					fputs($fp, $request);
					while(!feof($fp)) {
						$result .= fgets($fp, 10240);
					}
					fclose($fp);
					unset($this->avatar[$k]);
				}
			}
			
			require_once libfile('cache/userstats', 'function');
			build_cache_userstats();
			setloginstatus(array(
				'uid' => $uid,
				'username' => $username,
				'password' => $password,
				'groupid' => $groupinfo['groupid'],
			), 0);
			include_once libfile('function/stat');
			updatestat('register');
		}

		
	}
	
	private function checkGroup()
	{
		global $_G;
		if($_G['forum']['status'] == 3) {
			if(!helper_access::check_module('group')) {
				exit('错误group_status_off');
			}
			require_once libfile('function/group');
			$status = groupperm($_G['forum'], $_G['uid'], 'post');
			if($status == -1) {
				exit('错误forum_not_group');
			} elseif($status == 1) {
				exit('错误forum_group_status_off');
			} elseif($status == 2) {
				exit('错误forum_group_noallowed');
			} elseif($status == 3) {
				exit('错误forum_group_moderated');
			} elseif($status == 4) {
				if($_G['uid']) {
					exit('错误forum_group_not_groupmember');
				} else {
					exit('错误forum_group_not_groupmember_guest');
				}
			} elseif($status == 5) {
				exit('错误forum_group_moderated');
			}
		}
		if(($_G['forum']['simple'] & 1) || $_G['forum']['redirect']) {
			exit('错误forum_disablepost');
		}
	}
	
	private function upload($publishdate)
	{
		global $_G;
		empty($publishdate) && $publishdate = $_G['timestamp'];
		$upload = new discuz_upload();
		$upload->init($_FILES['Filedata'], 'forum');
		//$this->attach = &$upload->attach;

		if($upload->error()) {
			exit('错误上传错误');
		}
		if($_G['group']['attachextensions'] && (!preg_match("/(^|\s|,)".preg_quote($upload->attach['ext'], '/')."($|\s|,)/i", $_G['group']['attachextensions']) || !$upload->attach['ext'])) {
			exit('错误不支持的附近格式'.$upload->attach['ext']);
		}

		if(empty($upload->attach['size']) || $_G['group']['maxattachsize'] && $upload->attach['size'] > $_G['group']['maxattachsize']) {
			exit('错误附近大小不对'.$upload->attach['size'].'最大'.$_G['group']['maxattachsize']);
		}

		loadcache('attachtype');
		if($_G['fid'] && isset($_G['cache']['attachtype'][$_G['fid']][$upload->attach['ext']])) {
			$maxsize = $_G['cache']['attachtype'][$_G['fid']][$upload->attach['ext']];
		} elseif(isset($_G['cache']['attachtype'][0][$upload->attach['ext']])) {
			$maxsize = $_G['cache']['attachtype'][0][$upload->attach['ext']];
		}
		if(isset($maxsize)) {
			if(!$maxsize) {
				exit(4);
			} elseif($upload->attach['size'] > $maxsize) {
				exit(5);
			}
		}

		updatemembercount($_G['uid'], array('todayattachs' => 1, 'todayattachsize' => $upload->attach['size']));
		$upload->save();
		if($upload->error() == -103) {
			exit(8);
		} elseif($upload->error()) {
			exit(9);
		}
		$thumb = $remote = $width = 0;
		if($upload->attach['isimage']) {
			if($_G['setting']['showexif']) {
				require_once libfile('function/attachment');
				$exif = getattachexif(0, $upload->attach['target']);
			}
			if($_G['setting']['thumbsource'] || $_G['setting']['thumbstatus']) {
				require_once libfile('class/image');
				$image = new image;
			}
			if($_G['setting']['thumbsource'] && $_G['setting']['sourcewidth'] && $_G['setting']['sourceheight']) {
				$thumb = $image->Thumb($upload->attach['target'], '', $_G['setting']['sourcewidth'], $_G['setting']['sourceheight'], 1, 1) ? 1 : 0;
				$width = $image->imginfo['width'];
				$upload->attach['size'] = $image->imginfo['size'];
			}
			if($_G['setting']['thumbstatus']) {
				$thumb = $image->Thumb($upload->attach['target'], '', $_G['setting']['thumbwidth'], $_G['setting']['thumbheight'], $_G['setting']['thumbstatus'], 0) ? 1 : 0;
				$width = $image->imginfo['width'];
			}
			if($_G['setting']['thumbsource'] || !$_G['setting']['thumbstatus']) {
				list($width) = @getimagesize($upload->attach['target']);
			}
		}
		$aid = getattachnewaid($_G['uid']);
		$insert = array(
			'aid' => $aid,
			'dateline' => $publishdate,
			'filename' => censor($upload->attach['name']),
			'filesize' => $upload->attach['size'],
			'attachment' => $upload->attach['attachment'],
			'isimage' => $upload->attach['isimage'],
			'uid' => $_G['uid'],
			'thumb' => $thumb,
			'remote' => $remote,
			'width' => $width,
		);
		C::t('forum_attachment_unused')->insert($insert);
		if($upload->attach['isimage'] && $_G['setting']['showexif']) {
			C::t('forum_attachment_exif')->insert($aid, $exif);
		}
		return array('aid'=>$aid , 'name' => $upload->attach['name'] , 'isimage' => $upload->attach['isimage']);
	}
	
	private function setUpdate(&$attach , $message)
	{
		if(empty($attach)){
			return $message;
		}
		$uploadFile = array();
		foreach($attach as $k => $v){
			if(!empty($v) && $v['error'] == 0 && strpos($message , $v['name']) !== false){
				$_FILES['Filedata'] = $v;
				//$_FILES['Filedata']['name'] = addslashes(diconv(urldecode($_FILES['Filedata']['name']), 'UTF-8'));
				$_FILES['Filedata']['name'] = addslashes(urldecode($_FILES['Filedata']['name']));
				$_FILES['Filedata']['type'] = strrchr($_FILES['Filedata']['name'], '.');
				$uploadFile[] = $this->upload($this->publishdate);
				unset($attach[$k]);
			}
		}
		if(!empty($uploadFile)){
			foreach($uploadFile as $k => $v){
				$have = false;
				if($v['isimage'] == '1'){
					//正则替换，
					if(preg_match("/<img[^<>]+".$v['name']."[^<>]+>/iUs", $message , $match)){
						$have = true;
						if($config['附件隐藏']){
							$message = str_replace($match[0], '[hide][attach]'.$v['aid'].'[/attach][/hide]', $message);
						}else{
							$message = str_replace($match[0], '[attach]'.$v['aid'].'[/attach]', $message);
						}
					}
				}else{
					if(preg_match("/<a[^<>]*>".$v['name']."</a>/iUs", $message , $match)){
						$have = true;
						if($config['附件隐藏']){
							$message = str_replace($match[0], '[hide][attach]'.$v['aid'].'[/attach][/hide]', $message);
						}else{
							$message = str_replace($match[0], '[attach]'.$v['aid'].'[/attach]', $message);
						}
					}
				}
				
				if(!empty($config['附件权限组'])){
					$readpermArr = explode('|||', $config['附件权限组']);
					$readperm = $readpermArr[rand(0 , count($readpermArr)-1)];
				}else{
					$readperm = 0;
				}
				if(!empty($config['附件价格'])){
					$priceArr = explode('|||', $config['附件价格']);
					$price = $readpermArr[rand(0 , count($priceArr)-1)];
				}else{
					$price = 0;
				}
				$_GET['attachnew'][$v['aid']]['description'] = $v['name'];
				$_GET['attachnew'][$v['aid']]['readperm'] = $readperm;
				$_GET['attachnew'][$v['aid']]['price'] = $price;//$config['附件价格'];
			}
		}
		return $message;
	}
	
	public function newthread()
	{
		global $_G,$config;
		require_once libfile('class/credit');
		require_once libfile('function/post');
		
		$pid = intval(getgpc('pid'));
		$sortid = intval(getgpc('sortid'));
		$typeid = intval(getgpc('typeid'));
		
		$postinfo = array('subject' => '');
		$thread = array('readperm' => '', 'pricedisplay' => '', 'hiddenreplies' => '');
		
		$_G['forum_dtype'] = $_G['forum_checkoption'] = $_G['forum_optionlist'] = $tagarray = $_G['forum_typetemplate'] = array();
		
		if($sortid) {
			require_once libfile('post/threadsorts', 'include');
		}
		$this->checkGroup();
		//require_once libfile('function/discuzcode');
		
		$space = array();
		space_merge($space, 'field_home');
		
		formulaperm($_G['forum']['formulaperm']);
		
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
		
		$extra =  '';
		
		$subject = dhtmlspecialchars(censor(trim($this->subject)));
		$subject = !empty($subject) ? str_replace("\t", ' ', $subject) : $subject;
		$message = $this->setUpdate($this->attach , censor($this->message));
		$polloptions = isset($polloptions) ? censor(trim($polloptions)) : '';
		$readperm = isset($this->readperm) ? $this->readperm : 0;
		$price = isset($this->price) ? $this->price : 0;
		
		if(empty($bbcodeoff) && !$_G['group']['allowhidecode'] && !empty($message) && preg_match("/\[hide=?\d*\].*?\[\/hide\]/is", preg_replace("/(\[code\](.+?)\[\/code\])/is", ' ', $message))) {
			exit('错误post_hide_nopermission');
		}
		
		$modnewthreads = $modnewreplies = 0;
		$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';
		
		$seccodecheck = ($_G['setting']['seccodestatus'] & 4) && (!$_G['setting']['seccodedata']['minposts'] || getuserprofile('posts') < $_G['setting']['seccodedata']['minposts']);
		$secqaacheck = $_G['setting']['secqaa']['status'] & 2 && (!$_G['setting']['secqaa']['minposts'] || getuserprofile('posts') < $_G['setting']['secqaa']['minposts']);
		
		$_G['group']['allowpostpoll'] = $_G['group']['allowpost'] && $_G['group']['allowpostpoll'] && ($_G['forum']['allowpostspecial'] & 1);
		$_G['group']['allowposttrade'] = $_G['group']['allowpost'] && $_G['group']['allowposttrade'] && ($_G['forum']['allowpostspecial'] & 2);
		$_G['group']['allowpostreward'] = $_G['group']['allowpost'] && $_G['group']['allowpostreward'] && ($_G['forum']['allowpostspecial'] & 4);
		$_G['group']['allowpostactivity'] = $_G['group']['allowpost'] && $_G['group']['allowpostactivity'] && ($_G['forum']['allowpostspecial'] & 8);
		$_G['group']['allowpostdebate'] = $_G['group']['allowpost'] && $_G['group']['allowpostdebate'] && ($_G['forum']['allowpostspecial'] & 16);
		$_G['forum']['threadplugin'] = dunserialize($_G['forum']['threadplugin']);
		

		$_G['group']['allowanonymous'] = $_G['forum']['allowanonymous'] || $_G['group']['allowanonymous'] ? 1 : 0;
		
		if($_G['forum']['allowspecialonly']) {
			exit('错误group_nopermission');
		}
		
		$policykey = 'post';
		$postcredits = $_G['forum'][$policykey.'credits'] ? $_G['forum'][$policykey.'credits'] : $_G['setting']['creditspolicy'][$policykey];
		
		
		$albumlist = array();
		if(helper_access::check_module('album') && $_G['group']['allowupload'] && $_G['uid']) {
			$query = C::t('home_album')->fetch_all_by_uid($_G['uid'], 'updatetime');
			foreach($query as $value) {
				if($value['picnum']) {
					$albumlist[] = $value;
				}
			}
		}
		check_allow_action('allowpost');
		
		if(helper_access::check_module('album') && $_G['group']['allowupload'] && $_G['setting']['albumcategorystat'] && !empty($_G['cache']['albumcategory'])) {
			require_once libfile('function/portalcp');
		}
		loadcache('groupreadaccess');
		
		if(empty($_G['forum']['fid']) || $_G['forum']['type'] == 'group') {
			exit('错误板块id不正确');
		}
		
		if(!$_G['uid'] && !((!$_G['forum']['postperm'] && $_G['group']['allowpost']) || ($_G['forum']['postperm'] && forumperm($_G['forum']['postperm'])))) {
			exit('错误postperm_login_nopermission');
		} elseif(empty($_G['forum']['allowpost'])) {
			if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
				exit('错误postperm_none_nopermission');
			} elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
				showmessagenoperm('postperm', $_G['fid'], $_G['forum']['formulaperm']);
			}
		} elseif($_G['forum']['allowpost'] == -1) {
			exit('错误post_forum_newthread_nopermission');
		}
		
		if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
			exit('错误postperm_login_nopermission');
		}
		
		checklowerlimit('post', 0, 1, $_G['forum']['fid']);
		
		/*检测长度
		if($post_invalid = checkpost($subject, $message, ($special || $sortid))) {
			exit('错误'.$post_invalid);
		}
		*/
		
		$_GET['save'] = $_G['uid'] ? $_GET['save'] : 0;
		if ($this->publish_date  > $_G['timestamp']) {
			$_GET['save'] = 1;
		}
		$publishdate = $this->publish_date;
		
		$typeid = isset($typeid) && isset($_G['forum']['threadtypes']['types'][$typeid]) && (empty($_G['forum']['threadtypes']['moderators'][$typeid]) || $_G['forum']['ismoderator']) ? $typeid : 0;
		$displayorder = ($_G['forum']['ismoderator'] && $_G['group']['allowstickthread'] && !empty($_GET['sticktopic'])) ? 1 : (empty($_GET['save']) ? 0 : -4);
		if($displayorder == -4) {
			$_GET['addfeed'] = 0;
		}
		$digest = $_G['forum']['ismoderator'] && $_G['group']['allowdigestthread'] && !empty($_GET['addtodigest']) ? 1 : 0;
		$readperm = $_G['group']['allowsetreadperm'] ? $readperm : 0;
		$isanonymous = 0;
		$price = intval($price);
		$price = $_G['group']['maxprice'] && !$special ? ($price <= $_G['group']['maxprice'] ? $price : $_G['group']['maxprice']) : 0;
	
		if(!$typeid && $_G['forum']['threadtypes']['required'] && !$special) {
			exit('错误该板块必须要主题分类typeid');
		}
	
		if(!$sortid && $_G['forum']['threadsorts']['required'] && !$special) {
			exit('错误该板块必须要分类信息sortid');
		}
	
		if($price > 0 && floor($price * (1 - $_G['setting']['creditstax'])) == 0) {
			exit('错误post_net_price_iszero');
		}
	
		$typeexpiration = intval($_GET['typeexpiration']);
	
		if($_G['forum']['threadsorts']['expiration'][$typeid] && !$typeexpiration) {
			exit('错误threadtype_expiration_invalid');//);
		}
	
		$_G['forum_optiondata'] = array();
		if($_G['forum']['threadsorts']['types'][$sortid] && !$_G['forum']['allowspecialonly']) {
			$_G['forum_optiondata'] = threadsort_validator($_GET['typeoption'], $pid);
		}
	
		$author =  $_G['username'] ;
	
		$moderated = $digest || $displayorder > 0 ? 1 : 0;
	
		$thread['status'] = 0;
		$isgroup = $_G['forum']['status'] == 3 ? 1 : 0;
	
		//回复奖励
		if($_G['group']['allowreplycredit']) {
			$_GET['replycredit_extcredits'] = intval($_GET['replycredit_extcredits']);
			$_GET['replycredit_times'] = intval($_GET['replycredit_times']);
			$_GET['replycredit_membertimes'] = intval($_GET['replycredit_membertimes']);
			$_GET['replycredit_random'] = intval($_GET['replycredit_random']);
	
			$_GET['replycredit_random'] = $_GET['replycredit_random'] < 0 || $_GET['replycredit_random'] > 99 ? 0 : $_GET['replycredit_random'] ;
			$replycredit = $replycredit_real = 0;
			if($_GET['replycredit_extcredits'] > 0 && $_GET['replycredit_times'] > 0) {
				$replycredit_real = ceil(($_GET['replycredit_extcredits'] * $_GET['replycredit_times']) + ($_GET['replycredit_extcredits'] * $_GET['replycredit_times'] *  $_G['setting']['creditstax']));
				if($replycredit_real > getuserprofile('extcredits'.$_G['setting']['creditstransextra'][10])) {
					exit('错误replycredit_morethan_self');
				} else {
					$replycredit = ceil($_GET['replycredit_extcredits'] * $_GET['replycredit_times']);
				}
			}
		}
	
		$newthread = array(
			'fid' => $_G['fid'],
			'posttableid' => 0,
			'readperm' => $readperm,
			'price' => $price,
			'typeid' => $typeid,
			'sortid' => $sortid,
			'author' => $author,
			'authorid' => $_G['uid'],
			'subject' => $subject,
			'dateline' => $publishdate,
			'lastpost' => $publishdate,
			'lastposter' => $author,
			'displayorder' => $displayorder,
			'digest' => $digest,
			'special' => $special,
			'attachment' => 0,
			'moderated' => $moderated,
			'status' => $thread['status'],
			'isgroup' => $isgroup,
			'replycredit' => $replycredit,
			'closed' => $closed ? 1 : 0
		);
		$tid = C::t('forum_thread')->insert($newthread, true);
		useractionlog($_G['uid'], 'tid');
	
		if(!getuserprofile('threads') && $_G['setting']['newbie']) {
			C::t('forum_thread')->update($tid, array('icon' => $_G['setting']['newbie']));
		}
		//计划发布
		if ($publishdate != $_G['timestamp']) {
			loadcache('cronpublish');
			$cron_publish_ids = dunserialize($_G['cache']['cronpublish']);
			$cron_publish_ids[$tid] = $tid;
			$cron_publish_ids = serialize($cron_publish_ids);
			savecache('cronpublish', $cron_publish_ids);
		}
	
		
		C::t('common_member_field_home')->update($_G['uid'], array('recentnote'=>$subject));
		
		if($moderated) {
			updatemodlog($tid, ($displayorder > 0 ? 'STK' : 'DIG'));
			updatemodworks(($displayorder > 0 ? 'STK' : 'DIG'), 1);
		}
	
		if($_G['forum']['threadsorts']['types'][$sortid] && !empty($_G['forum_optiondata']) && is_array($_G['forum_optiondata'])) {
			$filedname = $valuelist = $separator = '';
			foreach($_G['forum_optiondata'] as $optionid => $value) {
				if($value) {
					$filedname .= $separator.$_G['forum_optionlist'][$optionid]['identifier'];
					$valuelist .= $separator."'".daddslashes($value)."'";
					$separator = ' ,';
				}
	
				if($_G['forum_optionlist'][$optionid]['type'] == 'image') {
					$identifier = $_G['forum_optionlist'][$optionid]['identifier'];
					$sortaids[] = intval($_GET['typeoption'][$identifier]['aid']);
				}
	
				C::t('forum_typeoptionvar')->insert(array(
					'sortid' => $sortid,
					'tid' => $tid,
					'fid' => $_G['fid'],
					'optionid' => $optionid,
					'value' => censor($value),
					'expiration' => ($typeexpiration ? $publishdate + $typeexpiration : 0),
				));
			}
	
			if($filedname && $valuelist) {
				C::t('forum_optionvalue')->insert($sortid, "($filedname, tid, fid) VALUES ($valuelist, '$tid', '$_G[fid]')");
			}
		}
		if($_G['group']['allowat']) {
			$atlist = $atlist_tmp = array();
			preg_match_all("/@([^\r\n]*?)\s/i", $message.' ', $atlist_tmp);
			$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $_G['group']['allowat']);
			if(!empty($atlist_tmp)) {
				if(empty($_G['setting']['at_anyone'])) {
					foreach(C::t('home_follow')->fetch_all_by_uid_fusername($_G['uid'], $atlist_tmp) as $row) {
						$atlist[$row['followuid']] = $row['fusername'];
					}
					if(count($atlist) < $_G['group']['allowat']) {
						$query = C::t('home_friend')->fetch_all_by_uid_username($_G['uid'], $atlist_tmp);
						foreach($query as $row) {
							$atlist[$row['fuid']] = $row['fusername'];
						}
					}
				} else {
					foreach(C::t('common_member')->fetch_all_by_username($atlist_tmp) as $row) {
						$atlist[$row['uid']] = $row['username'];
					}
				}
			}
			if($atlist) {
				foreach($atlist as $atuid => $atusername) {
					$atsearch[] = "/@$atusername /i";
					$atreplace[] = "[url=home.php?mod=space&uid=$atuid]@{$atusername}[/url] ";
				}
				$message = preg_replace($atsearch, $atreplace, $message.' ', 1);
			}
		}
	
		$bbcodeoff = checkbbcodes($message, 0);
		$smileyoff = checksmilies($message, 0);
		$parseurloff = !empty($_GET['parseurloff']);
		$htmlon = strpos($message , '<') !==false ? 1 : 0;
		$usesig = $config['使用签名'];
		$class_tag = new tag();
		$tagstr = $class_tag->add_tag($_GET['tags'], $tid, 'tid');
	
		if($_G['group']['allowreplycredit']) {
			if($replycredit > 0 && $replycredit_real > 0) {
				updatemembercount($_G['uid'], array('extcredits'.$_G['setting']['creditstransextra'][10] => -$replycredit_real), 1, 'RCT', $tid);
				$insertdata = array(
						'tid' => $tid,
						'extcredits' => $_GET['replycredit_extcredits'],
						'extcreditstype' => $_G['setting']['creditstransextra'][10],
						'times' => $_GET['replycredit_times'],
						'membertimes' => $_GET['replycredit_membertimes'],
						'random' => $_GET['replycredit_random']
					);
				C::t('forum_replycredit')->insert($insertdata);
			}
		}
		$pinvisible = $modnewthreads ? -2 : (empty($_GET['save']) ? 0 : -3);

		$pid = insertpost(array(
			'fid' => $_G['fid'],
			'tid' => $tid,
			'first' => '1',
			'author' => $_G['username'],
			'authorid' => $_G['uid'],
			'subject' => $subject,
			'dateline' => $publishdate,
			'message' => $message,
			'useip' => $_G['clientip'],
			'invisible' => $pinvisible,
			'anonymous' => $isanonymous,
			'usesig' => $usesig,
			'htmlon' => $htmlon,
			'bbcodeoff' => $bbcodeoff,
			'smileyoff' => $smileyoff,
			'parseurloff' => $parseurloff,
			'attachment' => '0',
			'tags' => $tagstr,
			'replycredit' => 0,
			'status' => 0
		));
		if($_G['group']['allowat'] && $atlist) {
			foreach($atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', array('from_id' => $tid, 'from_idtype' => 'thread', 'buyerid' => $_G['uid'], 'buyer' => $_G['username'], 'tid' => $tid, 'subject' => $subject, 'pid' => $pid, 'message' => messagecutstr($message, 150)));
			}
			set_atlist_cookie(array_keys($atlist));
		}
		$threadimageaid = 0;
		$threadimage = array();

		if($_G['forum']['threadsorts']['types'][$sortid] && !empty($_G['forum_optiondata']) && is_array($_G['forum_optiondata']) && $sortaids) {
			foreach($sortaids as $sortaid) {
				convertunusedattach($sortaid, $tid, $pid);
			}
		}
	
		if(($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) && ($_GET['attachnew'] || $sortid || !empty($_GET['activityaid']))) {
			updateattach($displayorder == -4 || $modnewthreads, $tid, $pid, $_GET['attachnew']);
			if(!$threadimageaid) {
				$threadimage = C::t('forum_attachment_n')->fetch_max_image('tid:'.$tid, 'tid', $tid);
				$threadimageaid = $threadimage['aid'];
			}
		}
	
		$values = array('fid' => $_G['fid'], 'tid' => $tid, 'pid' => $pid, 'coverimg' => '');
		$param = array();
		if($_G['forum']['picstyle']) {
			if(!setthreadcover($pid, 0, $threadimageaid)) {
				preg_match_all("/(\[img\]|\[img=\d{1,4}[x|\,]\d{1,4}\])\s*([^\[\<\r\n]+?)\s*\[\/img\]/is", $message, $imglist, PREG_SET_ORDER);
				$values['coverimg'] = "<p id=\"showsetcover\">".lang('message', 'post_newthread_set_cover')."<span id=\"setcoverwait\"></span></p><script>if($('forward_a')){\$('forward_a').style.display='none';setTimeout(\"$('forward_a').style.display=''\", 5000);};ajaxget('forum.php?mod=ajax&action=setthreadcover&tid=$tid&pid=$pid&fid=$_G[fid]&imgurl={$imglist[0][2]}&newthread=1', 'showsetcover', 'setcoverwait')</script>";
				$param['clean_msgforward'] = 1;
				$param['timeout'] = $param['refreshtime'] = 15;
			}
		}
	
		if($threadimageaid) {
			if(!$threadimage) {
				$threadimage = C::t('forum_attachment_n')->fetch('tid:'.$tid, $threadimageaid);
			}
			$threadimage = daddslashes($threadimage);
			C::t('forum_threadimage')->insert(array(
				'tid' => $tid,
				'attachment' => $threadimage['attachment'],
				'remote' => $threadimage['remote'],
			));
		}
	
		$statarr = array(0 => 'thread', 1 => 'poll', 2 => 'trade', 3 => 'reward', 4 => 'activity', 5 => 'debate', 127 => 'thread');
		include_once libfile('function/stat');
		updatestat($isgroup ? 'groupthread' : 'thread');
	
		dsetcookie('clearUserdata', 'forum');
	
		if($modnewthreads) {
			updatemoderate('tid', $tid);
			C::t('forum_forum')->update_forum_counter($_G['fid'], 0, 0, 1);
			manage_addnotify('verifythread');
			exit('错误post_newthread_mod_succeed');
		} else {
			if($displayorder != -4) {
				if($digest) {
					updatepostcredits('+',  $_G['uid'], 'digest', $_G['fid']);
				}
				updatepostcredits('+',  $_G['uid'], 'post', $_G['fid']);
				if($isgroup) {
					C::t('forum_groupuser')->update_counter_for_user($_G['uid'], $_G['fid'], 1);
				}
	
				$subject = str_replace("\t", ' ', $subject);
				$lastpost = "$tid\t".$subject."\t{$this->publishdate}\t$author";
				C::t('forum_forum')->update($_G['fid'], array('lastpost' => $lastpost));
				C::t('forum_forum')->update_forum_counter($_G['fid'], 1, 1, 1);
				if($_G['forum']['type'] == 'sub') {
					C::t('forum_forum')->update($_G['forum']['fup'], array('lastpost' => $lastpost));
				}
			}

			if($_G['forum']['status'] == 3) {
				C::t('forum_forumfield')->update($_G['fid'], array('lastupdate' => TIMESTAMP));
				require_once libfile('function/grouplog');
				updategroupcreditlog($_G['fid'], $_G['uid']);
			}
		}
		$_GET['tid'] = $tid;
		$_GET['attachnew'] = null;
		loadforum();
	}
	
	public function doreply($username , $message , $publishdate)
	{
		global $_G,$config;
		//cknewuser();
		$pid = intval(getgpc('pid'));
		$sortid = intval(getgpc('sortid'));
		$typeid = intval(getgpc('typeid'));
		$special = intval(getgpc('special'));
		
		$postinfo = array('subject' => '');
		$thread = array('readperm' => '', 'pricedisplay' => '', 'hiddenreplies' => '');
		
		$_G['forum_dtype'] = $_G['forum_checkoption'] = $_G['forum_optionlist'] = $tagarray = $_G['forum_typetemplate'] = array();
		
		if($sortid) {
			require_once libfile('post/threadsorts', 'include');
		}
		$this->checkGroup();
		require_once libfile('function/discuzcode');
		
		$space = array();
		space_merge($space, 'field_home');
		
		if(!empty($_GET['cedit'])) {
			unset($_G['inajax'], $_GET['infloat'], $_GET['ajaxtarget'], $_GET['handlekey']);
		}
		
		$thread = C::t('forum_thread')->fetch($_G['tid']);
		/*待发帖子
		if(!$_G['forum_auditstatuson'] && !($thread['displayorder']>=0 || (in_array($thread['displayorder'], array(-4, -2)) && $thread['authorid']==$_G['uid']))) {
			$thread = array();
		}
		*/
		if(!empty($thread)) {
			if($thread['readperm'] && $thread['readperm'] > $_G['group']['readaccess'] && !$_G['forum']['ismoderator'] && $thread['authorid'] != $_G['uid']) {
				exit('错误thread_nopermission');
			}
			$_G['fid'] = $thread['fid'];
			$thread['special'] > 0 && exit('错误只能发布普通类型');
	
		} else {
			exit('错误thread_nonexistence');
		}
	
		if($thread['closed'] == 1 && !$_G['forum']['ismoderator']) {
			exit('错误post_thread_closed');
		}
		formulaperm($_G['forum']['formulaperm']);
		
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
		
		$extra = '';
		
		$subject = isset($subject) ? dhtmlspecialchars(censor(trim($subject))) : '';
		$subject = !empty($subject) ? str_replace("\t", ' ', $subject) : $subject;
		$message = isset($message) ? censor($message) : '';
		if(empty($bbcodeoff) && !$_G['group']['allowhidecode'] && !empty($message) && preg_match("/\[hide=?\d*\].*?\[\/hide\]/is", preg_replace("/(\[code\](.+?)\[\/code\])/is", ' ', $message))) {
			exit('错误post_hide_nopermission');//);
		}
		
		$modnewthreads = $modnewreplies = 0;
		
		$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';
		
		$seccodecheck = ($_G['setting']['seccodestatus'] & 4) && (!$_G['setting']['seccodedata']['minposts'] || getuserprofile('posts') < $_G['setting']['seccodedata']['minposts']);
		$secqaacheck = $_G['setting']['secqaa']['status'] & 2 && (!$_G['setting']['secqaa']['minposts'] || getuserprofile('posts') < $_G['setting']['secqaa']['minposts']);
		
		$_G['group']['allowpostpoll'] = $_G['group']['allowpost'] && $_G['group']['allowpostpoll'] && ($_G['forum']['allowpostspecial'] & 1);
		$_G['group']['allowposttrade'] = $_G['group']['allowpost'] && $_G['group']['allowposttrade'] && ($_G['forum']['allowpostspecial'] & 2);
		$_G['group']['allowpostreward'] = $_G['group']['allowpost'] && $_G['group']['allowpostreward'] && ($_G['forum']['allowpostspecial'] & 4);
		$_G['group']['allowpostactivity'] = $_G['group']['allowpost'] && $_G['group']['allowpostactivity'] && ($_G['forum']['allowpostspecial'] & 8);
		$_G['group']['allowpostdebate'] = $_G['group']['allowpost'] && $_G['group']['allowpostdebate'] && ($_G['forum']['allowpostspecial'] & 16);
		$_G['forum']['threadplugin'] = dunserialize($_G['forum']['threadplugin']);

		$_G['group']['allowanonymous'] = $_G['forum']['allowanonymous'] || $_G['group']['allowanonymous'] ? 1 : 0;
		
		if($_GET['action'] == 'newthread' && $_G['forum']['allowspecialonly']) {
			exit('错误group_nopermission');
		}
		$policykey = 'reply';
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
		
		check_allow_action('allowreply');
		
		if(helper_access::check_module('album') && $_G['group']['allowupload'] && $_G['setting']['albumcategorystat'] && !empty($_G['cache']['albumcategory'])) {
			require_once libfile('function/portalcp');
		}
				
		require_once libfile('function/forumlist');
		
		$isfirstpost = 0;
		$showthreadsorts = 0;
		$quotemessage = '';
		
		if(!$_G['uid'] && !((!$_G['forum']['replyperm'] && $_G['group']['allowreply']) || ($_G['forum']['replyperm'] && forumperm($_G['forum']['replyperm'])))) {
			exit('错误replyperm_login_nopermission');//, NULL, array(), array('login' => 1));
		} elseif(empty($_G['forum']['allowreply'])) {
			if(!$_G['forum']['replyperm'] && !$_G['group']['allowreply']) {
				exit('错误replyperm_none_nopermission');//, NULL, array(), array('login' => 1));
			} elseif($_G['forum']['replyperm'] && !forumperm($_G['forum']['replyperm'])) {
				showmessagenoperm('replyperm', $_G['forum']['fid']);
			}
		} elseif($_G['forum']['allowreply'] == -1) {
			exit('错误post_forum_newreply_nopermission');//, NULL);
		}
		
		if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
			exit('错误replyperm_login_nopermission');//, NULL, array(), array('login' => 1));
		}
		
		if(empty($thread)) {
			exit('错误thread_nonexistence');//);
		} elseif($thread['price'] > 0  && !$_G['uid']) {
			exit('错误group_nopermission');//, NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
		}
		
		checklowerlimit('reply', 0, 1, $_G['forum']['fid']);
		
		if(getstatus($thread['status'], 3)) {
			$rushinfo = C::t('forum_threadrush')->fetch($_G['tid']);
			if($rushinfo['creditlimit'] != -996) {
				$checkcreditsvalue = $_G['setting']['creditstransextra'][11] ? getuserprofile('extcredits'.$_G['setting']['creditstransextra'][11]) : $_G['member']['credits'];
				if($checkcreditsvalue < $rushinfo['creditlimit']) {
					$creditlimit_title = $_G['setting']['creditstransextra'][11] ? $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][11]]['title'] : lang('forum/misc', 'credit_total');
					exit('错误post_rushreply_creditlimit');//, '', array('creditlimit_title' => $creditlimit_title, 'creditlimit' => $rushinfo['creditlimit']));
				}
			}
		}
		
		if($thread['closed'] && !$_G['forum']['ismoderator'] && !$thread['isgroup']) {
			exit('错误post_thread_closed');//);
		} elseif(!$thread['isgroup'] && $post_autoclose = checkautoclose($thread)) {
			exit($post_autoclose);
		} if(trim($subject) == '' && trim($message) == '') {
			exit('错误post_sm_isnull2');//);
		}/*检测长度
		 elseif($post_invalid = checkpost($subject, $message, $special == 2 && $_G['group']['allowposttrade'])) {
			exit('错误'.$post_invalid);//, '', array('minpostsize' => $_G['setting']['minpostsize'], 'maxpostsize' => $_G['setting']['maxpostsize']));
		}*/
	
		$attentionon = empty($_GET['attention_add']) ? 0 : 1;
		$attentionoff = empty($attention_remove) ? 0 : 1;
		$heatthreadset = update_threadpartake($_G['tid'], true);
		$message = $this->setUpdate($this->attach , $message);
		$bbcodeoff = checkbbcodes($message, 0);
		$smileyoff = checksmilies($message, 0);
		$parseurloff = !empty($_GET['parseurloff']);
		$htmlon = strpos($message , '<') !== false ? 1 : 0 ;//$_G['group']['allowhtml'] && !empty($_GET['htmlon']) ? 1 : 0;
		$usesig = $config['使用签名'];
	
		$isanonymous = 0;
		$author = $_G['username'] ;
	
		if($thread['displayorder'] == -4) {
			$modnewreplies = 0;
		}
		$pinvisible = $modnewreplies ? -2 : ($thread['displayorder'] == -4 ? -3 : 0);
		$postcomment = in_array(2, $_G['setting']['allowpostcomment']) && $_G['group']['allowcommentreply'] && !$pinvisible && !empty($_GET['reppid']) && ($nauthorid != $_G['uid'] || $_G['setting']['commentpostself']) ? messagecutstr($message, 200, ' ') : '';
	
		$pid = insertpost(array(
			'fid' => $_G['fid'],
			'tid' => $_G['tid'],
			'first' => '0',
			'author' => $_G['username'],
			'authorid' => $_G['uid'],
			'subject' => $subject,
			'dateline' => $publishdate,
			'message' => $message,
			'useip' => $this->randomip(),
			'invisible' => $pinvisible,
			'anonymous' => $isanonymous,
			'usesig' => $usesig,
			'htmlon' => $htmlon,
			'bbcodeoff' => $bbcodeoff,
			'smileyoff' => $smileyoff,
			'parseurloff' => $parseurloff,
			'attachment' => '0',
			'status' => 0,
		));
		if($_G['group']['allowat'] && $atlist) {
			foreach($atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', array('from_id' => $_G['tid'], 'from_idtype' => 'thread', 'buyerid' => $_G['uid'], 'buyer' => $_G['username'], 'tid' => $_G['tid'], 'subject' => $thread['subject'], 'pid' => $pid, 'message' => messagecutstr($message, 150)));
			}
			set_atlist_cookie(array_keys($atlist));
		}
		$updatethreaddata = $heatthreadset ? $heatthreadset : array();
		$postionid = C::t('forum_post')->fetch_maxposition_by_tid($thread['posttableid'], $_G['tid']);
		$updatethreaddata[] = DB::field('maxposition', $postionid);
		if(getstatus($thread['status'], 3) && $postionid) {
			$rushstopfloor = $rushinfo['stopfloor'];
			if($rushstopfloor > 0 && $thread['closed'] == 0 && $postionid >= $rushstopfloor) {
				$updatethreaddata[] = 'closed=1';
			}
		}
		useractionlog($_G['uid'], 'pid');
	
		$nauthorid = 0;
	
		if($thread['authorid'] != $_G['uid'] && getstatus($thread['status'], 6) && empty($_GET['noticeauthor']) && !$isanonymous && !$modnewreplies) {
			$thapost = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid'], 0);
			notification_add($thapost['authorid'], 'post', 'reppost_noticeauthor', array(
				'tid' => $thread['tid'],
				'subject' => $thread['subject'],
				'fid' => $_G['fid'],
				'pid' => $pid,
				'from_id' => $thread['tid'],
				'from_idtype' => 'post',
			));
		}
	
		if($thread['replycredit'] > 0 && !$modnewreplies && $thread['authorid'] != $_G['uid'] && $_G['uid']) {
	
			$replycredit_rule = C::t('forum_replycredit')->fetch($_G['tid']);
			if(!empty($replycredit_rule['times'])) {
				$have_replycredit = C::t('common_credit_log')->count_by_uid_operation_relatedid($_G['uid'], 'RCA', $_G['tid']);
				if($replycredit_rule['membertimes'] - $have_replycredit > 0 && $thread['replycredit'] - $replycredit_rule['extcredits'] >= 0) {
					$replycredit_rule['extcreditstype'] = $replycredit_rule['extcreditstype'] ? $replycredit_rule['extcreditstype'] : $_G['setting']['creditstransextra'][10];
					if($replycredit_rule['random'] > 0) {
						$rand = rand(1, 100);
						$rand_replycredit = $rand <= $replycredit_rule['random'] ? true : false ;
					} else {
						$rand_replycredit = true;
					}
					if($rand_replycredit) {
						updatemembercount($_G['uid'], array($replycredit_rule['extcreditstype'] => $replycredit_rule['extcredits']), 1, 'RCA', $_G[tid]);
						C::t('forum_post')->update('tid:'.$_G['tid'], $pid, array('replycredit' => $replycredit_rule['extcredits']));
						$updatethreaddata[] = DB::field('replycredit', $thread['replycredit'] - $replycredit_rule['extcredits']);
					}
				}
			}
		}
		($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) && ($_GET['attachnew'] || $special == 2 && $_GET['tradeaid']) && updateattach($thread['displayorder'] == -4 || $modnewreplies, $_G['tid'], $pid, $_GET['attachnew']);
	
		$_G['forum']['threadcaches'] && deletethreadcaches($_G['tid']);
	
		include_once libfile('function/stat');
		updatestat($thread['isgroup'] ? 'grouppost' : 'post');
	
		$param = array('fid' => $_G['fid'], 'tid' => $_G['tid'], 'pid' => $pid, 'from' => $_GET['from'], 'sechash' => !empty($_GET['sechash']) ? $_GET['sechash'] : '');
		if($feedid) {
			$param['feedid'] = $feedid;
		}
		dsetcookie('clearUserdata', 'forum');
	
		if($modnewreplies) {
			updatemoderate('pid', $pid);
			unset($param['pid']);
			if($updatethreaddata) {
				C::t('forum_thread')->update($_G['tid'], $updatethreaddata, false, false, 0, true);
			}
			C::t('forum_forum')->update_forum_counter($_G['fid'], 0, 0, 1, 1);
			manage_addnotify('verifypost');
		} else {
			$fieldarr = array(
				'lastposter' => array($author),
				'replies' => 1
			);
			if($thread['lastpost'] < $publishdate) {
				$fieldarr['lastpost'] = array($publishdate);
			}
			$row = C::t('forum_threadaddviews')->fetch($_G['tid']);
			if(!empty($row)) {
				C::t('forum_threadaddviews')->update($_G['tid'], array('addviews' => 0));
				$fieldarr['views'] = $row['addviews'];
			}
			$updatethreaddata = array_merge($updatethreaddata, C::t('forum_thread')->increase($_G['tid'], $fieldarr, false, 0, true));
			if($thread['displayorder'] != -4) {
				updatepostcredits('+', $_G['uid'], 'reply', $_G['fid']);
				if($_G['forum']['status'] == 3) {
					if($_G['forum']['closed'] > 1) {
						C::t('forum_thread')->increase($_G['forum']['closed'], $fieldarr, true);
					}
					C::t('forum_groupuser')->update_counter_for_user($_G['uid'], $_G['fid'], 0, 1);
					C::t('forum_forumfield')->update($_G['fid'], array('lastupdate' => $publishdate));
					require_once libfile('function/grouplog');
					updategroupcreditlog($_G['fid'], $_G['uid']);
				}
	
				$lastpost = "$thread[tid]\t$thread[subject]\t$publishdate\t$author";
				C::t('forum_forum')->update($_G['fid'], array('lastpost' => $lastpost));
				C::t('forum_forum')->update_forum_counter($_G['fid'], 0, 1, 1);
				if($_G['forum']['type'] == 'sub') {
					C::t('forum_forum')->update($_G['forum']['fup'], array('lastpost' => $lastpost));
				}
			}
			if($updatethreaddata) {
				C::t('forum_thread')->update($_G['tid'], $updatethreaddata, false, false, 0, true);
			}
		}
	}
	
	public function reply()
	{
		global $_G,$config;
		if(empty($this->replys)){
			return false;
		}else{
			require_once libfile('class/credit');
			require_once libfile('function/post');
			foreach($this->replys as $v){
				if(!empty($config['数据库用户'])){
					$max = C::t('common_member')->max_uid();
					$randuid = rand(1, $max);
					$username_info = DB::fetch_first("SELECT uid,username FROM `".DB::table('common_member')."` where uid >= {$randuid} order by uid ASC LIMIT 1" );
					$username = $username_info['username'];
				}else{
					$username = $v['username'];
				}
				$this->logonUser($username);
				empty($v['signature']) ||	C::t('common_member_field_forum')->update($_G['uid'], array('sightml' => $v['signature']));
				$this->doreply($username , $v['message'] , $v['publishdate']);
			}
		}
	}
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

