<?php
ini_set('display_errors',1);
switch ($_API["method"]) {
	case "GET":
		return_reservation($_API["id"],$_API["id2"],$_API["privuid"],$_API["privlvl"],$_API["pdo"]);
		break;
	case "PUT":
		create_reservation($_API["json"],$_API["id"],$_API["privuid"],$_API["pdo"]);
		break;
	default:
		json_response_error("400","Wrong request method");
		break;
}

function create_reservation($json,$cid,$uid,$pdo) {

	if(!$json) json_response_error("400","Could not parse json");
	if(!$cid) json_response_error("400","Missing cid");

	//disable dates (uaf)
	if($cid==1) json_response_error("400","This date is no more available");
	if($cid==2) json_response_error("400","This date is no more available");
	if($cid==3) json_response_error("400","This date is no more available");


	if($uid == 0) json_response_error("400","missing token");

	//nAdults
	if(!isset($json->nAdults)) json_response_error("400","Missing nAdults");
	$_nAdults = validnAdultsJSON($json->nAdults);

	//nChildren
	if(!isset($json->nChildren)) json_response_error("400","Missing nChildren");
	$_nChildren = validnChildrenJSON($json->nChildren);

	$n = $_nAdults + $_nChildren;
	if($n <= 0) json_response_error("400","Can't reservate < 1");

	//Assign places
	$pArr = find_place($n,$cid,$pdo);

	//get prices and calc total
	$sum = get_priceTotal($_nAdults,$_nChildren,$pdo);

	//save reservation
	$q1 = "INSERT INTO reservations(uid,cid,nAdults,nChildren,sum)
		   VALUES (:uid,:cid,:nAdults,:nChildren,:sum)";

	$q2 = "INSERT INTO places_reservations_joint(pid,rid) VALUES (:pid,:rid)";

	try {
		$pdo->beginTransaction();

		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt1->bindParam(":cid",$cid,PDO::PARAM_INT);
		$stmt1->bindParam(":nAdults",$_nAdults,PDO::PARAM_INT);
		$stmt1->bindParam(":nChildren",$_nChildren,PDO::PARAM_INT);
		$stmt1->bindParam(":sum",$sum,PDO::PARAM_STR);
		$stmt1->execute();

		$rid = $pdo->lastInsertId();

		$stmt2 = $pdo->prepare($q2);

		for($i=0;$i < $n;$i++) {
			$pid = $pArr[$i]["pid"];
			$stmt2->bindParam(":rid",$rid,PDO::PARAM_INT);
			$stmt2->bindParam(":pid",$pid,PDO::PARAM_INT);
			$stmt2->execute();
		}

		$pdo->commit();

	} catch(Exception $e) {
		$pdo->rollBack();
		error_log("Caught $e");
		json_response_error("500","Internal Api error (save reservation)");
	}

	$reason = "PETERPANRHJ TICKET#$rid"; //Max lenght 27 (see sofort api doc)
	$url = Sofort\SofortLib\payement($rid,$sum,$reason,$pdo);

	$arr = array("url"=>$url);
	json_response("200",$arr);
}

function return_reservation($uid,$cid,$privuid,$privlvl,$pdo) {

	if(!$privuid) json_response_error("400","Missing token");
	if(!$uid) json_response_error("400","Missing uid");

	if($uid != $privuid && $privlvl < 2) json_response_error("401","This info is private");
	if(!$cid) json_response_error("400","Missing cid");
	if($uid=="*" && $cid=="*") {
		//TODO set from-to limits to prevent huge data transfer/browser crash
		$q = "SELECT reservations.rid,auth.uid,email,nAdults,nChildren,sum,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate 
			  FROM concerts,reservations,auth 
			  WHERE auth.uid = reservations.uid AND concerts.cid = reservations.cid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return reservation *-*)");
		}

		$arr = array("reservations" => []);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		    $arr["reservations"][] = $row;
		}

		json_response("200",$arr);
	}
	elseif($uid=="*"){
		checkCid($cid,$pdo);

		$q = "SELECT reservations.rid,auth.uid,email,nAdults,nChildren,sum,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate 
			  FROM concerts,reservations,auth 
			  WHERE auth.uid = reservations.uid AND concerts.cid = reservations.cid AND reservations.cid = :cid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return reservation *-cid)");
		}

		$arr = array("reservations" => []);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		    $arr["reservations"][] = $row;
		}

		json_response("200",$arr);
	}
	elseif($cid=="*"){
		checkUid($uid,$pdo);

		$q = "SELECT reservations.rid,nAdults,nChildren,sum,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate 
		      FROM concerts,reservations,sofort WHERE concerts.cid = reservations.cid 
			  AND sofort.rid=reservations.rid AND sofort.status <> 'waiting' AND reservations.uid = :uid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":uid",$uid,PDO::PARAM_INT);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return reservation uid-*)");
		}

		$arr = array("reservations" => []);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		    $arr["reservations"][] = $row;
		}

		json_response("200",$arr);
	}
	else{
		checkUid($uid,$pdo);
		checkCid($cid,$pdo);

		//get reservation
		$q = "SELECT reservations.rid,nAdults,nChildren,sum,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate FROM concerts,reservations 
			  WHERE concerts.cid = reservations.cid AND reservations.uid = :uid AND reservations.cid = :cid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
			$stmt->bindParam(":uid",$uid,PDO::PARAM_INT);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return reservation uid-cid)");
		}

		$arr = array("reservations" => []);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		    $arr["reservations"][] = $row;
		}

		json_response("200",$arr);
	}
}
?>
