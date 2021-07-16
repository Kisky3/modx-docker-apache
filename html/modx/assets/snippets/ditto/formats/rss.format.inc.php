<?php

/*
 * Title: RSS
 * Purpose:
 *  	Collection of parameters, functions, and classes that expand
 *  	Ditto's output capabilities to include RSS
*/

if(!defined('MODX_BASE_PATH') || strpos(str_replace('\\','/',__FILE__), MODX_BASE_PATH)!==0) exit;

$modx->documentObject['contentType'] = 'application/rss+xml';

// set placeholders
$rss_placeholders['rss_copyright'] = isset($copyright) ? $copyright: $_lang['default_copyright'];
/*
	Param: copyright

	Purpose:
	Copyright message to embed in the RSS feed

	Options:
	Any text

	Default:
	[LANG]
*/
$rss_placeholders['rss_lang'] = (isset($abbrLanguage))? $abbrLanguage : $_lang['abbr_lang'];
/*
	Param: abbrLanguage

	Purpose:
	Language for the RSS feed

	Options:
	Any valid 2 character language abbreviation

	Default:
	[LANG]

	Related:
	- <language>
*/
$rss_placeholders['rss_link'] = $modx->config['site_url']."[~".$modx->documentObject['id']."~]";
$rss_placeholders['rss_ttl'] = isset($ttl) ? intval($ttl):120;
/*
	Param: ttl

	Purpose:
	Time to live for the RSS feed

	Options:
	Any integer greater than 1

	Default:
	120
*/
$rss_placeholders['rss_charset'] = isset($charset) ? $charset : $modx->config['modx_charset'];
/*
	Param: charset

	Purpose:
	Charset to use for the RSS feed

	Options:
	Any valid charset identifier

	Default:
	MODX default charset
*/
$rss_placeholders['rss_xsl'] = isset($xsl) ? "\n" . '<?xml-stylesheet type="text/xsl" href="'.$modx->config['site_url'].$xsl.'" ?>' : ''; 
/*
	Param: xsl

	Purpose:
	XSL Stylesheet to format the RSS feed with

	Options:
	The path to any valid XSL Stylesheet

	Default:
	None
*/

global $dateSource;
$dateSource = isset($modx->event->params['dateSource']) ? $modx->event->params['dateSource'] : 'publishedon';
if(!isset($orderBy ['unparsed'])) $orderBy ['unparsed'] = "{$dateSource} DESC";

	// date type to display (values can be createdon, pub_date, editedon)
	
// set tpl rss placeholders
$placeholders['rss_date'] = array($dateSource,"rss_date");
$placeholders['rss_pagetitle'] = array("pagetitle","rss_pagetitle");
$placeholders['rss_author'] = array("createdby","rss_author");

if(!function_exists("rss_date")) {
	function rss_date($resource) {
		global $dateSource;
		return date("r",  intval($resource[$dateSource]) + $modx->config["server_offset_time"]);
	}
}
if(!function_exists("rss_pagetitle")) {
	function rss_pagetitle($resource) {
		return htmlspecialchars(html_entity_decode($resource['pagetitle'], ENT_QUOTES));
	}
}
if(!function_exists("rss_author")) {
	function rss_author($resource) {
		return htmlspecialchars(html_entity_decode(ditto::getAuthor($resource['createdby']), ENT_QUOTES));
	}
}

$extenders[] = "summary";
	// load required summary extender for backwards compatibility
	// TODO: Remove summary extender in next major version
	
// set default templates

$rss_header = <<<TPL
<?xml version="1.0" encoding="[+rss_charset+]" ?>[+rss_xsl+]
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title>[*pagetitle*]</title>
		<link>[(site_url)]</link>
		<description>[*description:strip*]</description>
		<language>[+rss_lang+]</language>
		<copyright>[+rss_copyright+]</copyright>
		<ttl>[+rss_ttl+]</ttl>
TPL;

$rss_tpl = <<<TPL

		<item>
			<title>[+rss_pagetitle+]</title>
			<link>[+url+]</link>
			<description><![CDATA[ [+summary:strip+] ]]></description>
			<pubDate>[+rss_date+]</pubDate>
			<guid isPermaLink="true">[+url+]</guid>
			<dc:creator>[+rss_author+]</dc:creator>
			[+tagLinks+]
		</item>
	
TPL;

$rss_footer = <<<TPL
</channel>
</rss>
TPL;

// set template values
$header = isset($header) ? $header : template::replace($rss_placeholders,$rss_header);

$tpl = isset($tpl) ? $tpl : "@CODE:".$rss_tpl;

$footer = isset($footer) ? $footer : $rss_footer;

// set emptytext
$noResults = "      ";
