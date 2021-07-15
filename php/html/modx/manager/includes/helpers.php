<?php
function evo() {
    global $modx;
    if (!$modx) {
        return false;
    }
    return $modx;
}

function db() {
    return evo()->db;
}

function manager() {
    global $modx;
    return $modx->manager;
}

function hasPermission($key = null) {
    return evo()->hasPermission($key);
}

function config($key, $default = null) {
    return evo()->config($key, $default);
}

function docid() {
    return evo()->documentIdentifier;
}

function base_path() {
    if (defined('MODX_BASE_PATH')) {
        return constant('MODX_BASE_PATH');
    }
    exit('base_path not defined.');
}

function lang($key) {
    global $_lang;
    if (!$_lang) {
        include MODX_CORE_PATH . sprintf(
                'lang/%s.inc.php'
                , evo()->config('manager_language', 'english')
            );
    }
    return array_get($_lang, $key, $key);
}

function style($key) {
    global $_style;
    return array_get($_style, $key);
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}

if(!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
    return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if(!function_exists('str_ends_with')) {
    function str_ends_with ($haystack, $needle) {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}

function hsc($string = '', $flags = ENT_COMPAT, $encode = '', $double_encode = false) {
    return evo()->hsc($string, $flags, $encode, $double_encode);
}

function parseText($tpl, $ph, $left = '[+', $right = '+]', $execModifier = false) {
    if (evo()) {
        return evo()->parseText($tpl, $ph, $left, $right, $execModifier);
    }
    foreach ($ph as $k => $v) {
        $k = sprintf('[+%s+]', $k);
        $tpl = str_replace($k, $v, $tpl);
    }
    return $tpl;
}

function html_tag($tag_name, $attrib = array(), $content = null) {
    return evo()->html_tag($tag_name, $attrib, $content);
}

function input_text_tag($props = array()) {
    $props['type'] = 'text';
    $props['maxlength'] = evo()->array_get($props, 'maxlength', 255);
    $props['class'] = evo()->array_get($props, 'class', 'inputBox');
    foreach ($props as $k => $v) {
        if ($v === false) {
            unset($props[$k]);
        }
    }
    return evo()->html_tag('input', $props);
}

function textarea_tag($props = array(), $content) {
    $props['class'] = evo()->array_get($props, 'class', 'inputBox');
    return evo()->html_tag('textarea', $props, $content);
}

function select_tag($props = array(), $options) {
    $props['class'] = evo()->array_get($props, 'class', 'inputBox');
    if (is_array($options)) {
        $options = implode("\n", $options);
    }
    return evo()->html_tag('select', $props, $options);
}

function img_tag($src, $props = array()) {
    $props['src'] = $src;
    return evo()->html_tag('img', $props);
}

function alert() {
    static $e = null;
    if ($e) {
        return $e;
    }
    include_once(__DIR__ . '/error.class.inc.php');
    $e = new errorHandler;
    return $e;
}

function array_get($array, $key = null, $default = null) {
    if (evo()) {
        return evo()->array_get($array, $key, $default);
    }

    if ($key === null || trim($key) == '') {
        return $array;
    }

    static $cache = array();
    $cachekey = md5(print_r(func_get_args(), true));
    if (isset($cache[$cachekey]) && $cache[$cachekey] !== null) {
        return $cache[$cachekey];
    }

    if (isset($array[$key])) {
        $cache[$cachekey] = $array[$key];
        return $array[$key];
    }
    $segments = explode('.', $key);
    foreach ($segments as $segment) {
        if (!is_array($array) || !isset($array[$segment])) {
            return $default;
        }
        $array = $array[$segment];
    }
    return $array;
}

function array_set(&$array, $key, $value) {
    $array[$key] = $value;
}

function request_intvar($key) {
    if (preg_match('@^[1-9][0-9]*$@', evo()->input_any($key))) {
        return evo()->input_any($key);
    }
    return 0;
}

function event() {
    return evo()->event;
}

function parent($docid) {
    if (evo()) {
        return evo()->getParentID($docid ? $docid : docid());
    }
}

function uparent($docid=null, $top = 0) {
    if (evo()) {
        return evo()->getUltimateParentId($docid ? $docid : docid(), $top);
    }
}
function exprintf() {
    $args = func_get_args();
    $args[0] = str_replace('@{%([0-9]+)}@', '%$1s', $args[0]);
    return call_user_func_array(
        'sprintf'
        , $args
    );
}

function getv($key = null, $default = null) {
    if (evo()) {
        return evo()->input_get($key, $default);
    }
    return array_get($_GET, $key, $default);
}

function post($key = null, $default = null) {
    return postv($key, $default);
}

function postv($key = null, $default = null) {
    if (evo()) {
        return evo()->input_post($key, $default);
    }
    return array_get($_POST, $key, $default);
}

function cookiev($key = null, $default = null) {
    if (evo()) {
        return evo()->input_cookie($key, $default);
    }
    return array_get($_COOKIE, $key, $default);
}

function anyv($key = null, $default = null) {
    if (evo()) {
        return evo()->input_any($key, $default);
    }
    return array_get($_REQUEST, $key, $default);
}

function serverv($key = null, $default = null) {
    if (evo()) {
        return evo()->server($key, $default);
    }
    return array_get($_SERVER, $key, $default);
}

function sessionv($key = null, $default = null) {
    if (strpos($key,'.')!==false && evo()) {
        return evo()->session($key, $default);
    }
    if (strpos($key, '*') === 0) {
        $_SESSION[ltrim($key, '*')] = $default;
        return $default;
    }
    return array_get($_SESSION, $key, $default);
}

function filev($key = null, $default = null) {
    return array_get($_FILES, $key, $default);
}

function globalv($key = null, $default = null) {
    if (strpos($key,'.')!==false && evo()) {
        return evo()->global_var($key, $default);
    }
    if (strpos($key, '*') === 0) {
        $GLOBALS[ltrim($key, '*')] = $default;
        return $default;
    }
    return array_get($GLOBALS, $key, $default);
}

function checked($cond) {
    if ($cond) {
        return 'checked';
    }
    return '';
}

function selected($cond) {
    if ($cond) {
        return 'selected';
    }
    return '';
}

function pr($content) {
    if(is_array($content)) {
        echo '<pre>' . print_r($content, true) . '</pre>';
        return;
    }
    echo '<pre>' . $content . '</pre>';
}

function real_ip() {
    return serverv(
        'http_client_ip'
        , serverv(
            'http_x_forwarded_for'
            , serverv(
                'remote_addr'
                , 'UNKNOWN'
            )
        )
    );
}

function user_agent() {
    return serverv('http_user_agent', '');
}

function doc($key, $default=null) {
    global $modx, $docObject;
    if (isset($docObject)) {
        $doc = $docObject;
    } elseif (isset($modx->documntObject)) {
        $doc = &$modx->documntObject;
    }
    if (strpos($key, '*') === 0) {
        $value = $default;
        $doc[substr($key, 1)] = $value;
        return $value;
    }
    if (str_contains($key, '@parent')) {
        $a = evo()->getDocumentObject('id', doc('parent'));
        $key = str_replace('@parent', '', $key);
    } elseif(evo()->isFrontEnd()) {
        $a = evo()->documentObject;
    } else {
        $a = $doc;
    }
    if (str_contains($key, '|hsc')) {
        return hsc(
            evo()->array_get(
                $a
                , str_replace('|hsc', '', $key, $default)
            )
        );
    }
    return evo()->array_get($a, $key, $default);
}

function ob_get_include($path) {
    if (!is_file($path)) {
        return false;
    }
    ob_start();
    $return = eval(preg_replace('{^\s*<\?php}', '', file_get_contents($path)));
    return ob_get_clean() ?: $return;
}

function request_uri() {
    return serverv('request_uri');
}

function easy_hash($seed) {
    return strtr(rtrim(base64_encode(pack('H*', crc32($seed))), '='), '+/', '-_');
}

function device() {
    if (!serverv('http_user_agent')) {
        return 'pc';
    }

    $ua = strtolower(serverv('http_user_agent'));

    if (strpos($ua, 'ipad') !== false) {
        return 'tablet';
    }
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipod') !== false) {
        return 'smartphone';
    }

    if (strpos($ua, 'android') === false) {
        if (strpos($ua, 'windows phone') !== false) {
            return 'smartphone';
        }
        if (strpos($ua, 'docomo') !== false || strpos($ua, 'softbank') !== false) {
            return 'mobile';
        }
        if (strpos($ua, 'up.browser') !== false) {
            return 'mobile';
        }
        if (strpos($ua, 'bot') !== false || strpos($ua, 'spider') !== false) {
            return 'bot';
        }
        return 'pc';
    }

    if (strpos($ua, 'mobile') !== false) {
        return 'smartphone';
    }

    return 'tablet';
}

function datetime_format($format, $timestamp = '', $default = '')
{
	if (!$timestamp || strpos($timestamp, '0000-00-00') === 0) {
		return $default;
	}
	if (!preg_match('@^[0-9]+$@', $timestamp)) {
		$timestamp = strtotime($timestamp);
	}
	if (strpos($format, '%') === false) {
		return date($format, $timestamp);
	}
	if (strpos($format, '%曜') === false) {
		return strftime($format, $timestamp);
	}
	$week = ['日', '月', '火', '水', '木', '金', '土'];
	return strftime(
		str_replace('%曜', $week[date('w', $timestamp)], $format),
		$timestamp
	);
}
