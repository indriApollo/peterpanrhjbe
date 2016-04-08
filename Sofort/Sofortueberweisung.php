<?php

namespace Sofort\SofortLib;
use \PDO;
use \Exception;

function payement($rid,$amount,$reason,$pdo) {

	$configkey = 'xxxxxx:xxxxxx:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

	$Sofortueberweisung = new Sofortueberweisung($configkey);

	$Sofortueberweisung->setAmount($amount); //decimal
	$Sofortueberweisung->setCurrencyCode('EUR');
	$Sofortueberweisung->setReason($reason);
	$Sofortueberweisung->setSenderCountryCode('BE');
	$Sofortueberweisung->setNotificationUrl('https://rhjodoigne.be/Sofort/TransactionData.php');

	$sign = bin2hex(openssl_random_pseudo_bytes(32));
	$Sofortueberweisung->setUserVariable($sign);

	$Sofortueberweisung->sendRequest();

	if($Sofortueberweisung->isError()) {
		//SOFORT-API didn't accept the data
		error_log( $Sofortueberweisung->getError() );
		json_response_error("500","Internal Api error (Sofortueberweisung)");
	} else {
		$status = "waiting";
		$transId = $Sofortueberweisung->getTransactionId();
		$time = date("Y-m-d H:i:s");

		$q = "INSERT INTO sofort(rid,reason,transId,time,sign,status) VALUES (:rid,:reason,:transId,:time,:sign,:status)";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":rid",$rid,PDO::PARAM_INT);
			$stmt->bindParam(":reason",$reason,PDO::PARAM_STR);
			$stmt->bindParam(":transId",$transId,PDO::PARAM_STR);
			$stmt->bindParam(":time",$time,PDO::PARAM_STR);
			$stmt->bindParam(":sign",$sign,PDO::PARAM_STR);
			$stmt->bindParam(":status",$status,PDO::PARAM_STR);
			$stmt->execute();
			
		} catch(Exception $e) {
			error_log("Caught $e");
			json_response_error("500","Sofortueberweisung error");
		}

		//buyer must be redirected to $paymentUrl else payment cannot be successfully completed!
		$paymentUrl = $Sofortueberweisung->getPaymentUrl();
		return $paymentUrl;
	}
}

