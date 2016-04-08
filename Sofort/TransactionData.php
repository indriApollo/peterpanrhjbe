<?php

namespace Sofort\SofortLib;
use \PDO;
use \Exception;

require "../autoload.php";
require "../dblogin.php";
require "../api/apifunctions.php";
require "../api/jsonresp.php";

ini_set('display_errors', 1);
$input = file_get_contents('php://input');

$configkey = 'xxxxxx:xxxxxx:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

// read the notification from php://input  (http://php.net/manual/en/wrappers.php.php)
// this class should be used as a callback function

$SofortLib_Notification = new Notification();

if(!$notification = $SofortLib_Notification->getNotification($input)) {
	http_response_code("400");
	die("no notification");
} else {
	$transId = $SofortLib_Notification->getTransactionId();
	$time = $SofortLib_Notification->getTime();
}

$SofortLibTransactionData = new TransactionData($configkey);


$SofortLibTransactionData->addTransaction($notification);
$SofortLibTransactionData->setApiVersion('2.0');
$SofortLibTransactionData->sendRequest();

if($SofortLibTransactionData->isError()) {
	//SOFORT-API didn't accept the data
	error_log($SofortLibTransactionData->getError());
	http_response_code("500");
	die("sofort error");
} else {
	$status = $SofortLibTransactionData->getStatus();
	$sign = $SofortLibTransactionData->getUserVariable();
}

$pdo = open_db();

$q1 = "UPDATE sofort SET time=:time,status=:status WHERE sign=:sign AND transId=:transId";
	  
$q2 = "SELECT rid FROM sofort WHERE transId=:transId LIMIT 1";


try {
	$stmt1 = $pdo->prepare($q1);
	$stmt1->bindParam(":time",$time,PDO::PARAM_STR);
	$stmt1->bindParam(":status",$status,PDO::PARAM_STR);
	$stmt1->bindParam(":transId",$transId,PDO::PARAM_STR);
	$stmt1->bindParam(":sign",$sign,PDO::PARAM_STR);
	$stmt1->execute();
	
	$error = "signature mismatch";
	if(!$stmt1->rowCount()) {
		throw new Exception($error);
	}
	
	$stmt2 = $pdo->prepare($q2);
	$stmt2->bindParam(":transId",$transId,PDO::PARAM_STR);
	$stmt2->execute();

	$error = "last id error";
	if(!$ret = $stmt2->fetch(PDO::FETCH_ASSOC)) {
		throw new Exception($error);
	}
	$rid = $ret["rid"];
	
} catch(Exception $e) {
	error_log("Caught $e");
	http_response_code("500");
	die("Sofortueberweisung error");
}

sendTicket($rid,$pdo);
