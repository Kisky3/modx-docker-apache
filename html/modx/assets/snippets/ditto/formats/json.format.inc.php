<?php
/*
 * Title: JSON
 * Purpose:
 *  	Collection of parameters, functions, and classes that expand
 *  	Ditto's output capabilities to include JSON
*/

if(!defined('MODX_BASE_PATH') || strpos(str_replace('\\','/',__FILE__), MODX_BASE_PATH)!==0) exit;
$modx->documentObject['contentType'] = 'application/json';
// set json placeholders
$json_placeholders['json_lang'] = (isset($abbrLanguage))? $abbrLanguage : $_lang['abbr_lang'];
/*
	Param: abbrLanguage

	Purpose:
	Language for the JSON document

	Options:
	Any valid 2 character language abbreviation

	Default:
	[LANG]

	Related:
	- <language>
*/
$json_placeholders['json_copyright'] = isset($copyright) ? $copyright: $_lang['default_copyright'];
/*
	Param: copyright

	Purpose:
	Copyright message to embed in the JSON document

	Options:
	Any text

	Default:
	[LANG]
*/
$json_placeholders['json_link'] = $modx->config['site_url']."[~".$modx->documentObject['id']."~]";
$json_placeholders['json_ttl'] = isset($ttl) ? intval($ttl):120;
/*
	Param: ttl

	Purpose:
	Time to live for the JSON feed

	Options:
	Any integer greater than 1

	Default:
	120
*/
$json_placeholders['json_op'] = (!empty($_REQUEST[$dittoID.'jsonp']) ? $_REQUEST[$dittoID.'jsonp'] : '');

// set tpl JSON placeholders
$placeholders['*'] = "json_parameters";
if(!function_exists("json_parameters")) { 
	function json_parameters($placeholders) {
		global $modx;
		$jsonArr = array();
		foreach ($placeholders as $name=>$value) {
			$value = addslashes(htmlspecialchars($value,ENT_QUOTES, $modx->config['modx_charset']));
			if($name=='date' && !preg_match('@^[0-9]+$@',$value))
				$value = $modx->getUnixtimeFromDateString($value);
			$value = str_replace(array("\r\n","\n", "\r"), '\n', $value);
			$jsonArr["json_{$name}"] = str_replace("\t", '\t', $value);
		}
		$placeholders = array_merge($jsonArr,$placeholders);
		return $placeholders;
	}
}
// ---------------------------------------------------
// JSON Templates
// ---------------------------------------------------

$json_header = '
[+json_op+]{
 "title":"[*pagetitle:addslashes*]",
 "link":"[+json_link+]",
 "description":"[*description:addslashes:nl2lf*]",
 "language":"[+json_lang+]",
 "copyright":"[+json_copyright+]",
 "ttl":"[+json_ttl+]",
 "entries":[
';
	// not heredoc because { cannont be used as a char in heredoc

$json_tpl = <<<TPL
	{
		"title":"[+json_pagetitle+]",
		"link":"[(site_url)][~[+id+]~]",
		"date":"[+json_date+]",
		"guid":"[(site_url)][~[+id+]~]",
		"author":"[+json_author+]",
		"description":"[+json_description+]",
		"introtext":"[+json_introtext+]"
	},

TPL;

$json_tpl_last = <<<TPL
	{
		"title":"[+json_pagetitle+]",
		"link":"[(site_url)][~[+id+]~]",
		"date":"[+json_date+]",
		"guid":"[(site_url)][~[+id+]~]",
		"author":"[+json_author+]",
		"description":"[+json_description+]",
		"introtext":"[+json_introtext+]"
	}
TPL;

$json_footer = <<<TPL

]
}
TPL;

// ---------------------------------------------------
// Pass JSON Templates To Snippet
// ---------------------------------------------------

$header = isset($header) ? $header : template::replace($json_placeholders,$json_header);

$tpl = isset($tpl) ? $tpl : "@CODE:{$json_tpl}";

$tplLast = isset($tplLast) ? $tplLast : "@CODE:{$json_tpl_last}";

$footer = isset($footer) ? $footer : $json_footer;

// set emptytext
$noResults = '      ';
