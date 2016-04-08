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
$errormsg = (isset($_SESSION["errormsg"])) ? $_SESSION["errormsg"] : "";
$errordisplay = (isset($_SESSION["errormsg"])) ? "block" : "none";
?>
<!DOCTYPE html>
<html>
	<head>
		<title>join</title>
		<meta charset="utf-8">
		<link href='https://fonts.googleapis.com/css?family=Ubuntu:400,500,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="reset.css">
		<link rel="stylesheet" type="text/css" href="style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<div class="center">
			<div class="infobox">
				<p>Le mot de passe doit contenir : </p>
				<ul><li>Au moins une minuscule</li>
				<li>Au moins une majuscule</li>
				<li>Au moins un chiffre</li>
				<li>Au moins un caractère spécial (#?!@$%^&*-=)</li>
				<li>Au minimum 8 caractères</li></ul>
			</div>
			<div style="display: <?php echo $errordisplay ?>;" id="errorbox"><?php echo "$errormsg" ?></div>
			<div id="form-cont">
				<form method="post" action="session.php">
					<input type="hidden" name="signature" value="<?php echo $sign ?>">
					<input type="hidden" name="p" value="join">
					<h1>Email</h1><input type="text" name="login" id="login">
					<br/>
					<h1>Mot de passe</h1><input type="password" name="password" id="password">
					<br/>
					<h1>Confirmation</h1><input type="password" name="password2" id="password2">
					<br/>
					<input type="submit" value="créé">
				</form>
				<p><a href="session.php?p=login&appid=<?php echo $appid ?>">Se connecter à un compte existant</a></p>
			</div>
		</div>
	</body>
</html>