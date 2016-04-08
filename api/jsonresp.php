<?php
function json_response_error($status,$message) {
	http_response_code($status);
	$arr = array("status" => $status, "message" => $message);
	exit(json_encode($arr, JSON_PRETTY_PRINT));
}

function json_response_success($status,$message) {
	http_response_code($status);
	$arr = array("status" => $status, "message" => $message);
	exit(json_encode($arr, JSON_PRETTY_PRINT));
}

function json_response($status,$arr) {

	http_response_code($status);
	$arr["status"] = $status;
	exit(json_encode($arr, JSON_PRETTY_PRINT));
}
?>