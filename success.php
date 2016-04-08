<?php

$msg;

if(!isset($_GET["r"])) {
	header("Location: /");
	die();
} else {
	switch ($_GET["r"]) {
		case 'mail':
			$msg = "Un email a été envoyé a votre adresse";
			break;
		case 'pass':
			$msg = "Votre mot de pase a bien été mis à jour";
			break;
		default:
			die("invalid r param");
			break;
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Succès</title>
		<meta charset="utf-8">
		<link href='https://fonts.googleapis.com/css?family=Ubuntu:400,500,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="reset.css">
		<link rel="stylesheet" type="text/css" href="style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<div class="center">
			<h1><?php echo $msg; ?></h1>
		</div>
	</body>
</html>