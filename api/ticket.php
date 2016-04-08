<?php

switch ($_API["method"]) {
	case "GET":
		return_ticket($_API["id"],$_API["privuid"],$_API["pdo"]);
		break;
	default:
		json_response_error("400","Wrong request method");
		break;
}

function return_ticket($rid,$uid,$pdo) {

	if(!$rid) {
		json_response_error("400","Missing rid");
	}
	if(!$uid) {
		json_response_error("400","Missing token");
	}

	//check if uid really owns this reservation
	$q1 = "SELECT rid FROM reservations WHERE uid=:uid AND rid=:rid LIMIT 1";

	//check payement status
	$q2 = "SELECT status FROM sofort WHERE rid=:rid LIMIT 1";

	try {

		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt1->bindParam(":rid",$rid,PDO::PARAM_INT);
		$stmt1->execute();
		$arr = $stmt1->fetch(PDO::FETCH_ASSOC);
		if(!$arr) {
			json_response_error("404","rid not associated to this uid");
		}

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":rid",$rid,PDO::PARAM_INT);
		$stmt2->execute();
		$arr = $stmt2->fetch(PDO::FETCH_ASSOC);
		$error = "no status returned";
		if(!$arr) {
			throw new Exception($error);
		}
		elseif($arr["status"] == "waiting") json_response_error("401","Payement still pending");

	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","$e Internal Api error (ticket check uid)");
	}

	sendTicket($rid,$pdo);
}

?>