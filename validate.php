<?php

function validLogin($str) {
	return (filter_var($str, FILTER_VALIDATE_EMAIL)) ? $str : false;
}

function validID($i) {
	return (is_numeric($i) && $i >= 0) ? $i : false;
}

function validToken($str) {
	return (ctype_xdigit($str)) ? $str : false;
}

/****************
//JSON RESPONSE//
****************/

function validCdateJSON($date) {
	date_default_timezone_set("UTC");
	$dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $date);
	//http://stackoverflow.com/questions/10120643/php-datetime-createfromformat-functionality/10120725#10120725
    $errors = DateTime::getLastErrors();
    if (!empty($errors['warning_count'])) {
        $dateTime = false;
    }
    return ($dateTime) ? "'".$date."'" : json_response_error("400","Invalid cdate (Y-m-d H:i:s)");
}

function validTokenJSON($str) {
	return (ctype_xdigit($str)) ? $str : die(json_response_error("400","Invalid token (hexadecimal)"));
}

function validUnameJSON($str) {
	return (filter_var($str, FILTER_SANITIZE_STRING)) ? $str : die(json_response_error("400","Invalid uname"));
}

function validUtitleJSON($i) {
	return (is_numeric($i) && $i >= 0 && $i < 2) ? $i : die(json_response_error("400","Invalid utitle (enum 0 , 1)"));
}

function validnAdultsJSON($i) {
	return (is_numeric($i) && $i >= 0 ) ? $i : die(json_response_error("400","Invalid nAdults"));
}

function validnChildrenJSON($i) {
	return (is_numeric($i) && $i >= 0 ) ? $i : die(json_response_error("400","Invalid nChildren"));
}

function checkUid($uid,$pdo) {
	$q = "SELECT uid FROM auth WHERE uid=:uid LIMIT 1";
	
	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (check uid)");
	}
	if(!$arr) json_response_error("404","Unknown uid");
	return true;
}

function checkCid($cid,$pdo) {
	$q = "SELECT cid FROM concerts WHERE cid=:cid LIMIT 1";
	
	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":cid",$cid,PDO::PARAM_INT);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (check cid)");
	}
	if(!$arr) json_response_error("404","Unknown cid");
	return true;
}

?>