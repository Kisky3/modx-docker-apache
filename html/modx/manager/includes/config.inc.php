<?php
/**
 *	MODX Configuration file
 */
$database_type               = 'mysqli';
$database_server             = 'db';
$database_user               = 'root';
$database_password           = 'root_pass_fB3uWvTS';
$database_connection_charset = 'utf8';
$database_connection_method  = 'SET CHARACTER SET';
$dbase                       = 'modx5';
$table_prefix                = 'modx_';

$https_port                  = '443';

$lastInstallTime             = 1626396159;

setlocale (LC_TIME, 'ja_JP.UTF-8');
if(function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Tokyo');

include_once(dirname(__FILE__) . '/initialize.inc.php');
