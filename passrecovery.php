<?php
session_start();
if(!isset($_SESSION["signature"])) {
	//session expired
	header("Location: /");
	die();
}
$sign = $_SESSION["signature"];

$errormsg = "";
if(isset($_SESSION["errormsg"])) {
	$errormsg = $_SESSION["errormsg"];
	unset($_SESSION["errormsg"]);
}

$errordisplay = "none";
if($errormsg) {
	$errordisplay = "block";
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>login</title>
		<meta charset="utf-8">
		<link href='https://fonts.googleapis.com/css?family=Ubuntu:400,500,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="reset.css">
		<link rel="stylesheet" type="text/css" href="style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<div class="center">
			<div style="display: <?php echo $errordisplay ?>;" id="errorbox"><?php echo "$errormsg" ?></div>
			<div class="form-cont">
				<form method="post" action="session.php">
					<input type="hidden" name="signature" value="<?php echo $sign ?>">
					<input type="hidden" name="p" value="passrecovery">
					<h1>Email</h1>
					<input type="text" name="login">
					<br/>
					<input type="submit" value="continuer">
				</form>
			</div>
		</div>
	</body>
</html>