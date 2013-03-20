<?

$db = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_name']);

if($db->connect_error) {
	die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

function escape($str) {
	return mysql_real_escape_string($str);
}

function escapeBan($str) {
	return str_replace(array("\t", "\n"), array(" ", " "), $str);
}

function uid($length) {
	$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
	$string = "";	

	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(0, strlen($characters) - 1)];
	}

	return $string;
}

?>
