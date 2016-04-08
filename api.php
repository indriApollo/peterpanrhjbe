<?php

//*******************
//		DEBUG
//*******************

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require "autoload.php"; //has to be first
require "api/apifunctions.php";
require "api/jsonresp.php";
require "dblogin.php";
require "validate.php";
require "Sofort/Sofortueberweisung.php";
//require "api/ssl.php";

$_API = array();
$_API["version"] = 1;

/********************************************
	GET REQUESTED MODULE & DATA FROM URI 	
********************************************/

$request = $_SERVER['REQUEST_URI'];
//keep what is after */api/
$needle = "api.php/";
$uri = substr( strstr($request,$needle),strlen($needle) );

if(!$uri) {
	http_response_code(404);
	//die( json_response_error("404","Missing api params");
}

$params = explode("/", $uri);
$requestedModule = $params[0];

/**********************
	GLOBAL API DATA
**********************/

$_API["pdo"] = open_db();
$_API["privlvl"] = 0;
$_API["privuid"] = 0;
if(isset($_SERVER["HTTP_APITOKEN"])) {
	$token = substr($_SERVER["HTTP_APITOKEN"], strlen("token "));
	$_token = validToken($token);

	//token timeout
	date_default_timezone_set("Europe/Paris");
	$timeOut = strtotime('-20 minutes');
	$time = date('Y-m-d H:i:s', $timeOut);
	$currtime = date('Y-m-d H:i:s');
	
	$q = "SELECT uid,privlvl,time FROM auth WHERE token=:token AND time > :time LIMIT 1";

	$q1 = "UPDATE auth SET time=:time WHERE uid=:uid";

	try {
		$stmt = $_API["pdo"]->prepare($q);
		$stmt->bindParam(":token",$_token,PDO::PARAM_STR);
		$stmt->bindParam(":time",$time,PDO::PARAM_STR);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!$arr) json_response_error("404","Unknown token, please relog ($time)");

		//too fast time update (< 1 sec) results in update error (same time already in db)
		if($arr["time"]!=$currtime) {
			$stmt1 = $_API["pdo"]->prepare($q1);
			$stmt1->bindParam(":time",$currtime,PDO::PARAM_STR);
			$stmt1->bindParam(":uid",$arr["uid"],PDO::PARAM_STR);
			$stmt1->execute();

			if(!$stmt1->rowCount()) json_response_error("500","Internal Api error (renew token timeout $currtime)");
		}

	} catch(Exception $e) {
		error_log("Caught $e");
		json_response_error("500","Internal Api error (get prilvl and uid)");
	}
	

	$_API["privlvl"] = $arr["privlvl"];
	$_API["privuid"] = $arr["uid"];
}

$_API["json"] = json_decode(file_get_contents('php://input'));
$_API["method"] = $_SERVER["REQUEST_METHOD"];
$_API["id"] = false;
if(isset($params[1])) {
	$_API["id"] = ($params[1]=="*" || (is_numeric($params[1]) && $params[1] >= 0) ) ? $params[1] : false;
}
$_API["id2"] = false;
if(isset($params[2])) {
	$_API["id2"] = ($params[2]=="*" || (is_numeric($params[2]) && $params[2] >= 0) ) ? $params[2] : false;
}


switch ($requestedModule) {
	case "users":
		require "api/user.php";
		break;
	case "reservations":
		require "api/reservation.php";
		break;
	case "concerts":
		require "api/concert.php";
		break;
	case "places":
		require "api/place.php";
		break;
	case "tickets":
		require "api/ticket.php";
		break;
	case "report":
		require "api/csvreport.php";
		break;
	default:
		json_response_error("404","Unknown api module");
		break;
}

?>