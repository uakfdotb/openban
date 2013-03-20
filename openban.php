<?php

include("config.php");
include("common.php");

$exportLimit = $config['exportLimit'];
$exportLimitRolling = $config['exportLimitRolling'];
$restrictHTTPS = $config['restrictHTTPS'];
$extendedMode = $config['extendedMode'];

if(!isset($_REQUEST['action'])) {
	die('No action specified.');
} else if(!isset($_REQUEST['key'])) {
	die('No API key received.');
} else if($restrictHTTPS && empty($_SERVER['HTTPS'])) {
	die('Only HTTPS access is allowed.');
}

$action = $_REQUEST['action'];

if($action != "export") {
	die('Unknown action specified.');
}

$key = escape($_REQUEST['key']);
$result = $db->query("SELECT id, ip, last_export FROM openban_keys WHERE k = '$key'");
$last_time = 0;
$new_time = time();

if($row = $result->fetch_array()) {
	//check IP
	$remote_ip = $_SERVER['REMOTE_ADDR'];
	
	if(strlen($remote_ip) < strlen($row[1]) || $row[1] != substr($remote_ip, 0, strlen($row[1]))) {
		die('Authentication failure (addr).');
	}

	if(isset($_REQUEST['last']) && $_REQUEST['last'] != 0) { //if they have updated already, only get new updates
		$last_time = escape($_REQUEST['last']);
	}
	
	if(time() - $row[2] < $exportLimitRolling || (time() - $last_time > 3600 * 24 && time() - $row[2] <= $exportLimit)) {
		die("Error: exported too recently; limit set to $exportLimit/$exportLimitRolling (age: " . (time() - $row[2]) . ").");
	}
	
	$db->query("UPDATE openban_keys SET last_export = $new_time WHERE id = '{$row[0]}'");
} else {
	die('Authentication failure (key).');
}

//* update the bans cache

//mark deleted bans
$db->query("UPDATE openban_cache SET status = '1', time = '" . time() . "' WHERE status = '0' AND (SELECT COUNT(*) FROM bans WHERE bans.id = banid) = 0");

//add new bans
$result = $db->query("SELECT IFNULL(MAX(banid), 0) FROM openban_cache");
$row = $result->fetch_array();
$last_id = $row[0];

$db->query("INSERT INTO openban_cache (banid, time, status) SELECT id, '$new_time', 0 FROM bans WHERE id > $last_id AND (SELECT COUNT(*) FROM openban_cache WHERE bans.id = openban_cache.banid) = 0 AND openban_target IS NULL AND (context = '' OR context = 'ttr.cloud')");

//delete stale entries (only deleted bans)
$db->query("DELETE FROM openban_cache WHERE status = '1' AND time < '" . ($new_time - 3600 * 24 * 20) . "'");

//* get the new bans

$extendedSelect = "";

if($extendedMode) {
	$extendedSelect = ", gamename, admin, (expiredate - NOW()) / 60";
}

if($last_time != 0) { //if they have updated already, only get new updates
	$result = $db->query("SELECT banid, name, server, ip, reason, status$extendedSelect FROM openban_cache LEFT JOIN bans ON id = banid WHERE (status = 1 OR (expiredate > DATE_ADD(NOW(), INTERVAL 12 HOUR) AND openban_target IS NULL AND (context = '' OR context = 'ttr.cloud'))) AND time >= '$last_time'");
} else {
	$result = $db->query("SELECT id, name, server, ip, reason, 0$extendedSelect FROM bans WHERE expiredate > DATE_ADD(NOW(), INTERVAL 12 HOUR) AND openban_target IS NULL AND (context = '' OR context = 'ttr.cloud') ORDER BY id");
}

echo "*success:export\n";
echo "*cols:id\tname\tserver\tip\treason";

if($extendedMode) {
	echo "\tgamename\tadmin\tduration";
}

echo "\n";
echo "*time:$new_time\n";

while($row = $result->fetch_row()) {
	if($row[5] == 0) {
		$id = escapeBan($row[0]);
		$name = escapeBan($row[1]);
		$server = escapeBan($row[2]);
		$ip = escapeBan($row[3]);
		$reason = escapeBan($row[4]);
		
		if(empty($reason)) {
			$reason = "none";
		}
	
		echo "$id\t$name\t$server\t$ip\t$reason";
		
		if($extendedMode) {
			echo "\t" . escapeBan($row[6]) . "\t" . escapeBan($row[7]) . "\t" . escapeBan($row[8]);
		}
		
		echo "\n";
	} else {
		echo "*delete:" . $row[0] . "\n";
	}
}

?>
