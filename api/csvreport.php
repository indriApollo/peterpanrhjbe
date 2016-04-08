<?php
ini_set('display_errors',1);

switch ($_API["method"]) {
	case "GET":
		return_csv($_API["privlvl"],$_API["pdo"]);
		break;
	default:
		json_response_error("400","Wrong request method");
		break;
}

function return_csv($privlvl,$pdo) {

	if(!$privlvl) json_response_error("400","Missing token");
	if($privlvl < 2) json_last_error("401","Unauthorized !");

	$q1 = "SELECT reservations.rid,uname,email,DATE_FORMAT(cdate,'%d/%m/%Y'),nAdults,nChildren,sum,DATE_FORMAT(sofort.time,'%d/%m/%Y %hh%mm%ss'),sign 
		   FROM concerts,reservations,auth,users,sofort 
		   WHERE auth.uid = reservations.uid AND concerts.cid = reservations.cid AND users.uid=reservations.uid AND sofort.rid = reservations.rid AND sofort.status='untraceable'";

	$q2 = "SELECT pname FROM places,places_reservations_joint WHERE places.pid = places_reservations_joint.pid AND rid=:rid";
	
	try {

		header('Content-Type: application/csv; charset=UTF-8');
    	// open the "output" stream
   		// see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    	$f = fopen('php://output', 'w');
    	$cellTitles = ["res ID","nom","email","date concert","nb adultes","nb enfants","somme","date paiement","sign","places"];
    	fputcsv($f, $cellTitles, ";");

		$stmt1 = $pdo->prepare($q1);
		$stmt2 = $pdo->prepare($q2);
		$stmt1->execute();

		while($row1 = $stmt1->fetch(PDO::FETCH_NUM)) {
			$pnames = 0;
			$stmt2->bindParam(":rid",$row1[0],PDO::PARAM_INT);
			$stmt2->execute();
			while($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
				if(!$pnames) {
					$pnames = $row2["pname"];
				} else {
					$pnames .= "-".$row2["pname"]; //add separator - 
				}
			}
			$row1[9] = $pnames;
			fputcsv($f, $row1, ";");
		}

		
	} catch(Exception $e) {
		error_log("Caught $e");
		die("ERROR : $e");
	}
}
?>
