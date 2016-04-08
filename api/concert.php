<?php

switch ($_API["method"]) {
	case "GET":
		return_concert($_API["id"],$_API["pdo"]);
		break;
	default:
		json_response_error("400","Wrong request method");
		break;
}

function return_concert($cid,$pdo) {

	if($cid != 0) {
		$q = "SELECT cid,TIME_FORMAT(ctime,'%H') AS htime,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate 
		      FROM concerts WHERE cid=:cid LIMIT 1";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
			$stmt->execute();
			$arr = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return concert)");
		}
		if(!$arr) json_response_error("404","Unknown cid");

		json_response("200",$arr);
	}
	else {

		$q = "SELECT cid,TIME_FORMAT(ctime,'%H') AS htime,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate,
			  (SELECT count(pid) AS nFreePlaces FROM places WHERE NOT EXISTS 
			  (SELECT pid FROM places_reservations_joint LEFT JOIN reservations 
			  ON reservations.rid=places_reservations_joint.rid WHERE reservations.cid=concerts.cid 
			  AND places.pid=places_reservations_joint.pid)) AS nFreePlaces 
			  FROM concerts ORDER BY cdate";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return concerts)");
		}

		$arr = array("concerts"=> array());
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$arr["concerts"][] = $row;
		}

		json_response("200",$arr);
	}
}

?>