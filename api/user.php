<?php

switch ($_API["method"]) {
	case "GET":
		return_user($_API["id"],$_API["pdo"],$_API["privlvl"],$_API["privuid"]);
		break;
	case "PUT":
		create_update_user($_API["json"],$_API["pdo"],$_API["privlvl"],$_API["privuid"]);
	default:
		json_response_error("400","Wrong request method");
		break;
}

function create_update_user($json,$pdo,$privlvl,$privuid) {

	if(!$json) json_response_error("400","Could not parse json");

	//utitle
	if(!isset($json->utitle)) json_response_error("400","Missing utitle");
	$_utitle = utitleToStr( validUtitleJSON($json->utitle) );

	//uname
	if(!isset($json->uname)) json_response_error("400","Missing uname");
	$_uname = validUnameJSON($json->uname);

	$uid = $privuid;
	//save user details
	$q1 = "UPDATE users SET utitle=:utitle,uname=:uname WHERE uid=:uid";
	//get email
	$q2 = "SELECT email FROM auth WHERE uid=:uid LIMIT 1";

	try {
		
		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt1->bindParam(":utitle",$_utitle,PDO::PARAM_STR);
		$stmt1->bindParam(":uname",$_uname,PDO::PARAM_STR);
		$stmt1->execute();
		//update returns fale when same data as before is entered

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt2->execute();

		$error = "error getting email";
		if(!$ret = $stmt2->fetch(PDO::FETCH_ASSOC)) {
			throw new Exception($error);
		}
		$email = $ret["email"];

	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","$Internal Api error (save new user)");
	}

	$arr = array("utitle"=>$_utitle,
				 "uname"=>$_uname,
				 "uid"=>$uid,
				 "email"=>$email);
	json_response("201",$arr);
}

function return_user($uid,$pdo,$privlvl,$privuid) {

	if($uid=="*") {
		//return all

		if ($privlvl < 2) json_response_error("401","This info is private");

		$q = "SELECT users.uid,users.utitle,users.uname,auth.email,auth.privlvl 
			  FROM users,auth WHERE users.uid=auth.uid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return users)");
		}

		$uArr = array("users" => array());

		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$uArr["users"][] = $row;
		}
		
		json_response("200",$uArr);

	} else {
		if(!$uid){
			$uid = $privuid; //our own
		}
		elseif ($privuid != $uid && $privlvl < 2) {
			json_response_error("401","This info is private");
		}

		$q = "SELECT auth.uid,users.utitle,users.uname,auth.email,auth.privlvl FROM auth LEFT JOIN users
			  ON users.uid=auth.uid WHERE auth.uid=$uid";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":uid",$uid,PDO::PARAM_INT);
			$stmt->execute();
			$arr = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Internal Api error (return user)");
		}
		if(!$arr) json_response_error("404","No user associated to uid");
		
		json_response("200",$arr);
	}
}

?>