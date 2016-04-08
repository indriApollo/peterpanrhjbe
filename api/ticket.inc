<?php
$mailmsg = "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
		<title>PETERPAN RHJ</title>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'/>
	</head>
</html>
<body style='margin: 0; padding: 0;' >
	<table style='border: 1px solid #cccccc; padding: 0 0 30px 0;' align='center' border='0' cellpadding='0' cellspacing='0' width='600'>
		<tr>
	  		<td>
	   			<img width='598' src='https://www.rhjodoigne.be/mailtop.png' alt='top banner'>
	  		</td>
		</tr>
		<tr>
			<td style='font-family: Arial, sans-serif; font-size: 24px;padding: 15px;'>
				<b>Nous sommes heureux de vous accueillir au spectacle Peter Pan le $eudate à $htime h.</b>
				<hr>
				<table border='0' cellpadding='0' cellspacing='0' width='568'>
					<tr>
						<td style='text-align: center;'>$utitle $uname</td>
					</tr>
					<tr>
						<td><b>Voici le détail de vos places :</b></td>
					</tr>
					<tr>
						<td style='padding-left: 100px;'><i>Place(s) adulte(s) : $nAdults</i></td>
					</tr>
					<tr>
						<td style='padding-left: 100px;'><i>Place(s) enfant(s) : $nChildren</i></td>
					</tr>
					<tr>
						<td><b>Numéros de places :</b></td>
					</tr>
					<tr>
						<td style='text-align: center;'>$places</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td style='padding: 15px; text-align:center;'>
				<hr>
				<i>Veuillez imprimer ce billet et le présenter à l'entrée.</i>
			</td>
		</tr>
	</table>
</body>
</html>
";
?>