<?php

switch ($_API["method"]) {
	case "GET":
		return_xyFreePlaces($_API["id"],$_API["pdo"]);
		break;
	default:
		json_response_error("400","Wrong request method");
		break;
}

function return_xyFreePlaces($cid,$pdo) {

	//cid
	if(!$cid) json_response_error("400","Missing cid");
	if($cid=="*") json_response_error("400","Invalid cid");

	$q1 = "SELECT MAX(x) AS nx,MAX(y) AS ny FROM places";

	$q2 = "SELECT pname,x,y FROM places WHERE NOT EXISTS 
		   (SELECT pid FROM places_reservations_joint LEFT JOIN reservations 
		   ON reservations.rid = places_reservations_joint.rid 
		   WHERE reservations.cid = :cid AND places.pid = places_reservations_joint.pid)";

	try {
		$stmt = $pdo->prepare($q1);
		$stmt->execute();
		$error = "Internal Api error (xy min-max)";
		$minMaxXY = $stmt->fetch(PDO::FETCH_ASSOC); 
		if(!$minMaxXY) throw new Exception($error);

		$stmt = $pdo->prepare($q2);
		$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
		$stmt->execute();
	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (return xyFreePlaces)");
	}

	$arr = $minMaxXY;
	$arr["freePlaces"] = array();
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$arr["freePlaces"][] = $row;
	}

	json_response("200",$arr);
}

?>