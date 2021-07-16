<?php 
 return array (
  'Ditto' => 'return @require MODX_BASE_PATH.\'assets/snippets/ditto/snippet.ditto.php\';
',
  'DittoProps' => '',
  'eForm' => '# eForm 1.4.4.7 - Electronic Form Snippet
# Original created by Raymond Irving 15-Dec-2004.
# Version 1.3+ extended by Jelle Jager (TobyL) September 2006
# -----------------------------------------------------
# Captcha image support - thanks to Djamoer
# Multi checkbox, radio, select support - thanks to Djamoer
# Form Parser and extened validation - by Jelle Jager
#

# Set Snippet Paths
$snip_dir = isset($snip_dir) ? $snip_dir : \'eform\';
$snipPath = "{$modx->config[\'base_path\']}assets/snippets/{$snip_dir}/";

# check if inside manager
if ($modx->isBackend()) return \'\'; // don\'t go any further when inside manager

# Start processing

$version = \'1.4.4.7\';
include_once ("{$snipPath}eform.inc.php");

$output = eForm($modx,$params);

# Return
return $output;',
  'eFormProps' => '&sendAsText=テキストで送る;string;1',
  'TopicPath' => 'include_once($modx->config[\'base_path\'] . \'assets/snippets/topicpath/topicpath.class.inc.php\');
$topicpath = new TopicPath();
return $topicpath->getTopicPath();
',
  'TopicPathProps' => '&theme=Theme;list;string,list;string',
  'Wayfinder' => 'return require MODX_BASE_PATH.\'assets/snippets/wayfinder/snippet.wayfinder.php\';
',
  'WayfinderProps' => '',
  'WebLogin' => '# Created By Raymond Irving 2004
#::::::::::::::::::::::::::::::::::::::::
# Params:	
#
#	&loginhomeid 	- (Optional)
#		redirects the user to first authorized page in the list.
#		If no id was specified then the login home page id or 
#		the current document id will be used
#
#	&logouthomeid 	- (Optional)
#		document id to load when user logs out	
#
#	&pwdreqid 	- (Optional)
#		document id to load after the user has submited
#		a request for a new password
#
#	&pwdactid 	- (Optional)
#		document id to load when the after the user has activated
#		their new password
#
#	&logintext		- (Optional) 
#		Text to be displayed inside login button (for built-in form)
#
#	&logouttext 	- (Optional)
#		Text to be displayed inside logout link (for built-in form)
#	
#	&tpl			- (Optional)
#		Chunk name or document id to as a template
#	
#	Note: Templats design:
#			section 1: login template
#			section 2: logout template 
#			section 3: password reminder template 
#
#			See weblogin.tpl for more information
#
# Examples:
#
#	[!WebLogin? &loginhomeid=`8` &logouthomeid=`1`!] 
#
#	[!WebLogin? &loginhomeid=`8,18,7,5` &tpl=`Login`!]

# Set Snippet Paths 
$snipPath = $modx->config[\'base_path\'] . "assets/snippets/";

# check if inside manager
if ($m = $modx->isBackend()) {
	return \'\'; // don\'t go any further when inside manager
}

# deprecated params - only for backward compatibility
if(isset($loginid))  $loginhomeid=$loginid;
if(isset($logoutid)) $logouthomeid = $logoutid;
if(isset($template)) $tpl = $template;

# Snippet customize settings
$liHomeId   = isset($loginhomeid) ? explode(\',\',$loginhomeid):array($modx->config[\'login_home\'],$modx->documentIdentifier);
$loHomeId   = isset($logouthomeid)? $logouthomeid:$modx->documentIdentifier;
$pwdReqId   = isset($pwdreqid)    ? $pwdreqid:0;
$pwdActId   = isset($pwdactid)    ? $pwdactid:0;
$loginText  = isset($logintext)   ? $logintext:\'Login\';
$logoutText = isset($logouttext)  ? $logouttext:\'Logout\';
$tpl        = isset($tpl)         ? $tpl:\'\';

# System settings
$webLoginMode  = isset($_REQUEST[\'webloginmode\'])? $_REQUEST[\'webloginmode\']: \'\';
$isLogOut      = $webLoginMode==\'lo\' ? 1:0;
$isPWDActivate = $webLoginMode==\'actp\' ? 1:0;
$isPostBack    = count($_POST) && (isset($_POST[\'cmdweblogin\']) || isset($_POST[\'cmdweblogin_x\']));
$txtPwdRem     = isset($_REQUEST[\'txtpwdrem\'])? $_REQUEST[\'txtpwdrem\']: 0;
$isPWDReminder = $isPostBack && $txtPwdRem==\'1\' ? 1:0;

$site_id = isset($site_id) ? $site_id: \'\';
$cookieKey = substr(md5("{$site_id}Web-User"),0,15);

# Start processing
include_once("{$snipPath}weblogin/weblogin.common.inc.php");
include_once("{$snipPath}weblogin/crypt.class.inc.php");

if ($isPWDActivate || $isPWDReminder || $isLogOut || $isPostBack) {
	# include the logger class
	include_once(MODX_CORE_PATH . \'log.class.inc.php\');
	include_once("{$snipPath}weblogin/weblogin.processor.inc.php");
}

include_once("{$snipPath}weblogin/weblogin.inc.php");

# Return
return $output;
',
  'WebLoginProps' => '&loginhomeid=Login Home Id;string; &logouthomeid=Logout Home Id;string; &logintext=Login Button Text;string; &logouttext=Logout Button Text;string; &tpl=Template;string;',
);