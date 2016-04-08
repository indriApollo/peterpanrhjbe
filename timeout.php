<?php
ini_set('display_errors',1);
require "dblogin.php";

$pdo = open_db();
date_default_timezone_set("Europe/Paris");
$timeOut = strtotime('-15 minutes');
$time =  date('Y-m-d H:i:s', $timeOut);
echo "time $time ";

//records which are more than 15 mins old get deleted
$q = "DELETE FROM recovery WHERE time < :time";

$q1 = "SELECT rid FROM sofort WHERE status='waiting' AND time < :time";

$q2 = "DELETE FROM sofort WHERE rid=:rid";

$q3 = "DELETE FROM reservations WHERE rid=:rid";

$q4 = "DELETE FROM places_reservations_joint WHERE rid=:rid";

try {

	$pdo->beginTransaction();

	$stmt = $pdo->prepare($q);
	$stmt->bindParam(":time",$time,PDO::PARAM_STR);
	$stmt->execute();

	$stmt1 = $pdo->prepare($q1);
	$stmt1->bindParam(":time",$time,PDO::PARAM_STR);
	$stmt1->execute();
	$ret = $stmt1->fetchAll();

	if(!$ret) exit("nothing");
	$rid = $ret[0]["rid"];

	$stmt2 = $pdo->prepare($q2);
	$stmt2->bindParam(":rid",$rid,PDO::PARAM_INT);

	$stmt3 = $pdo->prepare($q3);
	$stmt3->bindParam(":rid",$rid,PDO::PARAM_INT);

	$stmt4 = $pdo->prepare($q4);
	$stmt4->bindParam(":rid",$rid,PDO::PARAM_INT);

	for ($i=0; $i < count($ret); $i++) { 

		$rid = $ret[$i]["rid"];
		echo "RID $rid";

		$stmt2->execute();
		if(!$stmt2->rowCount()) {
			throw new Exception("delete rid: $rid from sofort");
		}

		$stmt3->execute();
		if(!$stmt3->rowCount()) {
			throw new Exception("delete rid: $rid from reservations");
		}

		$stmt4->execute();
		if(!$stmt4->rowCount()) {
			throw new Exception("delete rid: $rid from joint");
		}
	}

	$pdo->commit();

} catch(Exception $e) {
	$pdo->rollBack();
	echo "$e";
	error_log("Caught $e");
	//mail
	$to      = "indri.apollo@gmail.com";
	$subject = "Timeout cleanup error";
	$message = "Caught $e\r\n";
	$headers = 'From: reporting@rhjodoigne.be' . "\r\n" .
			    'Reply-To: reporting@rhjodoigne.be' . "\r\n" .
			    'X-Mailer: PHP/' . phpversion();

	mail($to, $subject, $message, $headers);
}

exit();

?>
