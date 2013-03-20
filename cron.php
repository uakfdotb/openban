<?php

include("config.php");
include("common.php");

$result = $db->query("SELECT id, name, url, last_export FROM openban_targets");

while($row = $result->fetch_array()) {
	$target_id = escape($row[0]);
	$target = escape($row[1]);
	$url = $row[2];
	$last_export = $row[3];
	
	//clear bans in case we want a hard refresh
	if($last_export == 0) {
		$db->query("DELETE FROM bans WHERE openban_target = '$target'");
	}
	
	$char = "&";
	
	if(strpos($url, "?") === false) {
		$char = "?";
	}
	
	$fin = fopen($url . $char . "last=" . $last_export, 'r');
	$header = trim(fgets($fin));
	
	if($header !== "*success:export") {
		echo "Error: invalid header $header.\n";
		continue;
	}
	
	while($line = fgets($fin)) {
		$line = trim($line);
		
		if(substr($line, 0, 6) == "*cols:") {
			$columnsSource = explode("\t", substr($line, 6));
			$columns = array();
	
			foreach($columnsSource as $i => $column) {
				$columns[trim($column)] = $i;
			}
	
			if(!isset($column['name']) || !isset($column['server']) || !isset($column['ip']) || !isset($column['reason']) || !isset($column['id'])) {
				echo "Error: missing some columns: $line.\n";
				break;
			}
		} else if(substr($line, 0, 8) == "*delete:") {
			$delete_id = escape(substr($line, 8));
			$db->query("DELETE FROM bans WHERE openban_target = '$target' AND openban_id = '$delete_id'");
		} else if(substr($line, 0, 6) == "*time:") {
			$last_export = escape(substr($line, 6));
			$db->query("UPDATE openban_targets SET last_export = '$last_export' WHERE id = '$target_id'");
		} else if(substr($line, 0, 1) != "*" && isset($columns)) {
			$parts = explode("\t", trim($line));
	
			if(count($parts) != count($columns)) {
				echo "Error: column mismatch: $line.\n";
				continue;
			}
	
			$id = escape($parts[$columns['id']]);
			$name = escape($parts[$columns['name']]);
			$server = escape($parts[$columns['server']]);
			$ip = escape($parts[$columns['ip']]);
			$reason = escape($parts[$columns['reason']]);
	
			$db->query("INSERT INTO bans (botid, server, name, ip, date, gamename, admin, reason, context, expiredate, openban_target, openban_id) VALUES ('{$config['botid']}', '$server', '$name', '$ip', NOW(), '', '', '$reason', 'ttr.cloud', DATE_ADD(NOW(), INTERVAL 1 YEAR), '$target', '$id')");
		} else {
			echo "Invalid line: $line.\n";
		}
	}
	
	fclose($fin);
}

?>
