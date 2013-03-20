<?php

include("config.php");
include("common.php");

if(php_sapi_name() !== "cli") {
	die('Nothing to see here.');
}

if(!isset($argv) || count($argv) < 2) {
	echo "usage: admin.php action\n";
	echo "\t admin.php add name [ip]\n";
	echo "\t admin.php remove name\n";
	echo "\t admin.php list\n";
	return;
}

$action = $argv[1];

if($action == "add") {
	if(count($argv) < 3) {
		echo "usage: admin.php add name [ip]\n";
		return;
	}
	
	$name = escape($argv[2]);
	$ip = "";
	
	if(count($argv) >= 4) {
		$ip = escape($argv[3]);
	}
	
	$result = $db->query("SELECT COUNT(*) FROM openban_keys WHERE name = '$name'");
	$row = $result->fetch_array();
	
	if($row[0] > 0) {
		echo "Key for that name already exists (use remove first maybe)\n";
		return;
	}
	
	$key = uid(64);
	$db->query("INSERT INTO openban_keys (name, ip, k, last_export) VALUES ('$name', '$ip', '$key', 0)");
	echo "Added with name=$name, ip=$ip\n";
	echo "Key: $key\n";
} else if($action == "remove") {
	if(count($argv) < 3) {
		echo "usage: admin.php remove name\n";
		return;
	}
	
	$name = escape($argv[2]);
	$db->query("DELETE FROM openban_keys WHERE name = '$name'");
	echo "Deleted {$db->affected_rows} rows.\n";
} else if($action == "list") {
	$result = $db->query("SELECT name, ip, k FROM openban_keys");
	
	while($row = $result->fetch_array()) {
		echo $row[0] . "\t" . $row[1] . "\t" . $row[2] . "\n";
	}
} else {
	echo "Invalid action: $action\n";
}

?>
