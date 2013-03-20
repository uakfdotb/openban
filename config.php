<?php

$config = array();

//time in seconds between ban table exports
//this is set on a per-key basis
$config['exportLimit'] = 60 * 120;

//time in seconds between rolling ban table exports
//this is set on a per-key basis
$config['exportLimitRolling'] = 60;

//only allow HTTPS connections
$config['restrictHTTPS'] = true;

//database settings
$config['db_name'] = "ghost";
$config['db_host'] = "localhost";
$config['db_username'] = "root";
$config['db_password'] = "";

//cron settings
$config['targets'] = array();
$config['botid'] = 0;

?>
