<?php
if(!defined('MODX_BASE_PATH') || strpos(str_replace('\\','/',__FILE__), MODX_BASE_PATH)!==0) exit;
$c = &$this->contentTypes;
$e = &$this->pluginEvent;
$e['OnBeforeDocFormSave'] = array('ManagerManager');
$e['OnBeforeManagerLogin'] = array('Forgot Manager Login');
$e['OnDocFormPrerender'] = array('ManagerManager','Quick Manager+');
$e['OnDocFormRender'] = array('ManagerManager');
$e['OnDocFormSave'] = array('Quick Manager+');
$e['OnInterfaceSettingsRender'] = array('TinyMCE Rich Text Editor');
$e['OnManagerAuthentication'] = array('Forgot Manager Login');
$e['OnManagerChangePassword'] = array('Forgot Manager Login');
$e['OnManagerLoginFormPrerender'] = array('Forgot Manager Login');
$e['OnManagerLoginFormRender'] = array('Forgot Manager Login');
$e['OnManagerLogout'] = array('Quick Manager+');
$e['OnManagerMainFrameHeaderHTMLBlock'] = array('ManagerManager');
$e['OnParseDocument'] = array('Quick Manager+');
$e['OnPluginFormRender'] = array('ManagerManager');
$e['OnRichTextEditorInit'] = array('TinyMCE Rich Text Editor');
$e['OnRichTextEditorRegister'] = array('TinyMCE Rich Text Editor');
$e['OnWebPagePrerender'] = array('Quick Manager+');

