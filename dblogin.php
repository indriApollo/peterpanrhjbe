<?php
function open_db() {
	$host = "<db>.mysql.db";
	$user = "<user>";
	$pass = "<pass>";
	$db = "<db>";

	try {
		$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
		$pdo ->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	} catch(Exception $e) {
		die($e->getMessage());
	}
	return $pdo;
}
?>
