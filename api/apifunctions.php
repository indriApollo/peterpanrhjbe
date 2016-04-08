<?php

function utitleToStr($i) {
	switch ($i) {
		case 0:
			return "M.";
			break;
		case 1:
			return "Mme";
			break;
	}
}

function find_place($n,$cid,$pdo) {
	
	//returns array with free places
	$q = "SELECT * FROM places WHERE NOT EXISTS 
		  (SELECT pid FROM places_reservations_joint LEFT JOIN reservations 
		  ON reservations.rid = places_reservations_joint.rid 
		  WHERE reservations.cid = :cid AND places.pid = places_reservations_joint.pid)";
	
	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (get free places)");
	}
	if(!$row) json_response_error("404","No places left");

	//om remplit rangée par rangée
	//first free place
	$pArr[0] = array("pid"=>$row["pid"],"pname"=>$row["pname"]);
	$y = $row["y"];		//rangée
	$x = $row["x"]+1;	//place suivante attendue
	$i = 0;
	//one place asked
	if($n == 1) return $pArr;
	//multiple places asked
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

		if($row["y"]==$y && $row["x"]==$x) {
			array_push($pArr, array("pid"=>$row["pid"],"pname"=>$row["pname"]) );
			$x++;	//place suivante attendue

			if(count($pArr) >= $n) {
				//all places found
				return $pArr;
			}
		}
		else {	//first free place from new row
			$pArr = []; //clear previous
			$pArr[0] = array("pid"=>$row["pid"],"pname"=>$row["pname"]);
			$y = $row["y"];		//nouvelle rangée
			$x = $row["x"]+1;	//place suivante attendue
		}
		$i++;
	}
	
	json_response_error("404","Could not fit n places");
}

function get_priceTotal($nAdults,$nChildren,$pdo) {

	$q = "SELECT * FROM prices LIMIT 1";

	try {
		$stmt = $pdo->prepare($q);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (get prices)");
	}
	if(!$arr) json_response_error("500","Internal Api error (prices not set)");

	return ($nAdults*$arr["Adults"]) + ($nChildren*$arr["Children"]);
}

function sendTicket($rid,$pdo) {

	$q1 = "SELECT uname,utitle,email,nAdults,nChildren,TIME_FORMAT(ctime,'%H') AS htime,DATE_FORMAT(cdate,'%d/%m/%Y') AS eudate 
		   FROM users,auth,reservations,concerts 
		   WHERE reservations.uid=users.uid AND reservations.uid=auth.uid AND reservations.cid=concerts.cid 
		   AND reservations.rid=:rid LIMIT 1";

	$q2 = "SELECT pname FROM places,places_reservations_joint 
	       WHERE places_reservations_joint.pid=places.pid AND places_reservations_joint.rid=:rid";

	try {
		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":rid",$rid,PDO::PARAM_INT);
		$stmt1->execute();
		$details = $stmt1->fetch(PDO::FETCH_ASSOC);
		$error = "no details returned";
		if(!$details) {
			throw new Exception($error);
		}

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":rid",$rid,PDO::PARAM_INT);
		$stmt2->execute();
		$pname = $stmt2->fetchAll(PDO::FETCH_ASSOC);
		$error = "no pname returned";
		if(!$pname) {
			throw new Exception($error);	
		}

	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (get ticket details)");
	}
	
	$email = $details["email"];
	$uname = $details["uname"];
	$utitle = $details["utitle"];
	$nAdults = $details["nAdults"];
	$nChildren = $details["nChildren"];
	$eudate = $details["eudate"];
	$htime = $details["htime"];
	$places = " ";
	for ($i=0; $i < count($pname); $i++) { 
		$places .= $pname[$i]["pname"]." ";
	}

	require "ticket.inc";
	//mail
	$to      = $email;
	$subject = "Ticket concert Peter Pan - $eudate";
	$message = $mailmsg;
	$headers = 'MIME-Version: 1.0' . "\r\n" .
			   'Content-type: text/html; charset=utf-8' . "\r\n" .
			   'From: tickets@rhjodoigne.be' . "\r\n" .
			   'Reply-To: tickets@rhjodoigne.be' . "\r\n" .
			   'X-Mailer: PHP/' . phpversion();

	if(!mail($to, $subject, $message, $headers)) {
		json_response_error("500","Internal Api error (send ticket)");
	} else {
		json_response_success("200","Ticket sent");
	}
}

?>