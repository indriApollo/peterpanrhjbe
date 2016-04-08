<?php
session_start();
if(!isset($_SESSION["signature"])) {
	//session expired
	header("Location: /");
	die();
}
$sign = $_SESSION["signature"];

if(!isset($_SESSION["appid"])) {
	//session expired
	header("Location: /");
	die();
}
$appid = $_SESSION["appid"];

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
					<input type="hidden" name="p" value="login">
					<h1>Email</h1>
					<input type="text" name="login">
					<br/>
					<h1>Mot de passe</h1>
					<input type="password" name="password">
					<br/>
					<input type="submit" value="connexion">
				</form>
				<p><a href="session.php?p=join&appid=<?php echo $appid ?>">créer un nouveau compte</a></p>
				<br/>
				<p><a href="session.php?p=passrecovery">J'ai perdu mon mot de passe</a></p>
			</div>
		</div>
	</body>
</html>