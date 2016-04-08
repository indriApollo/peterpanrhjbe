<?php

//*******************
//		DEBUG
//*******************

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require "dblogin.php";
require "validate.php";

$m = $_SERVER["REQUEST_METHOD"];

switch ($m) {
	case 'GET':
		new_session();
		handle_get($_GET,$_SESSION);
		break;
	
	case 'POST':
		session_start();
		handle_post($_POST,$_SESSION);
		break;

	default:
		die("wrong http method");
		break;
}

function handle_get($get,&$session) {

	$session["appid"] = (isset($get["appid"])) ?  ValidID($get["appid"]) : false;
	$session["login"] = (isset($get["login"])) ?  validLogin($get["login"]) : false;
	$session["token"] = (isset($get["token"])) ?  validToken($get["token"]) : false;

	if(isset($get["p"])) {
		switch ($get["p"]) {
			case 'join':
				redir_join($session);
				break;

			case 'login':
				redir_login($session);
				break;

			case 'changepass':
				redir_changepass($session);
				break;

			case 'passrecovery':
				redir_passrecovery($session);
				break;

			case 'newpass':
				redir_newpassrecovery($session);
				break;

			case 'logout':
				redir_logout($session);
			default:
				del_session();
				die("The world's prettiest server can't return pages that don't exist");
				break;
		}
	}
}

function handle_post($post,&$session) {

	$sign = (isset($session["signature"])) ? $session["signature"] : die("session expired");
	$_appid = (isset($session["appid"])) ? $session["appid"] : die("session expired");

	if(!isset($post["signature"])) die("missing signature");
	if($post["signature"] != $sign) die("signature mismatch");

	$login = (isset($post["login"])) ? $post["login"] : false;
	$password = (isset($post["password"])) ? $post["password"] : false;
	$password2 = (isset($post["password2"])) ? $post["password2"] : false;
	$oldpass = (isset($post["oldpass"])) ? $post["oldpass"] : false;

	$p = (isset($post["p"])) ? $post["p"] : die("missing p");

	$pdo = open_db();

	switch ($p) {
		case 'join':

			if(!$login) {
				$session["errormsg"] = "<p>Il manque l'identifiant</p>";
				redir_join($session);
			}

			$_login = validLogin($login);
			if(!$_login) {
				$session["errormsg"] = "<p>Votre identifiant n'est pas valide</p>";
				redir_join($session);
			}

			if(!$password) {
				$session["errormsg"] = "<p>Il manque le mot de passe</p>";
				redir_join($session);
			}

			if(!$password2) {
				$session["errormsg"] = "<p>Il manque la confirmation de mot de passe</p>";
				redir_join($session);
			}

			if($password != $password2) {
				$session["errormsg"] = "<p>Les mots de passe ne correspondent pas</p>";
				redir_join($session);
			}
			
			if(!passreg($password)) {
				$session["errormsg"] = "<p>Le mot de passe n'est pas valide (voir tableau ci-dessus)</p>";
				redir_join($session);
			}

			$resp = new_user($_login,$password,$session,$pdo);

			if(!$resp) {
				redir_join($session);
			} else {
				del_session();
				redir_newuser($resp,$_appid,$pdo);
			}

			break;
		
		case 'login':

			if(!$login) {
				$session["errormsg"] = "<p>Il manque l'identifiant</p>";
				redir_login($session);
			}

			$_login = validLogin($login);
			if(!$_login) {
				$session["errormsg"] = "<p>Votre identifiant n'est pas valide</p>";
				redir_login($session);
			}

			if(!$password) {
				$session["errormsg"] = "<p>Il manque le mot de passe</p>";
				redir_login($session);
			}

			$resp = auth_user($_login,$password,$session,$pdo,true);

			if(!$resp) {
				redir_login($session);
			} else {
				del_session();
				redir_authuser($resp,$_appid,$pdo);
			}

			break;

		case 'changepass':

			if(!$oldpass) {
				$session["errormsg"] = "<p>Il manque l'ancien mot de passe</p>";
				redir_changepass($session);
			}

			if(!$login) {
				del_session();
				die("missing login");
			}

			$_login = validLogin($login);
			if(!$_login) {
				del_session();
				die("invalid login");
			}

			$resp = auth_user($_login,$oldpass,$session,$pdo,false);

			if(!$resp) {
				redir_changepass($session);
			}

			if(!$password) {
				$session["errormsg"] = "<p>Il manque le nouveau mot de passe</p>";
				redir_changepass($session);
			}

			if(!$password2) {
				$session["errormsg"] = "<p>Il manque la confirmation du nouveau mot de passe</p>";
				redir_changepass($session);
			}

			if($password != $password2) {
				$session["errormsg"] = "<p>Les mots de passe ne correspondent pas</p>";
				redir_changepass($session);
			}

			if(!passreg($password)) {
				$session["errormsg"] = "<p>Le mot de passe n'est pas valide (voir tableau ci-dessus)</p>";
				redir_changepass($session);
			}
			
			//create hash
			$hash = password_hash($password,PASSWORD_DEFAULT);

			$q = "UPDATE auth SET hash=:hash WHERE email=:email LIMIT 1";

			try {
				$stmt = $pdo->prepare($q);
				$stmt->bindParam(":email",$_login,PDO::PARAM_STR);
				$stmt->bindParam(":hash",$hash,PDO::PARAM_STR);
				$stmt->execute();
			} catch(Exception $e) {
				error_log("Caught $e");
				$session["errormsg"] = "<p> 500 Erreur interne (update pass)</p>";
				redir_changepass($session);
			}

			del_session();
			redir_success("pass");
			break;

		case 'passrecovery':

			if(!$login) {
				$session["errormsg"] = "<p>Il manque l'identifiant</p>";
				redir_passrecovery($session);
			}

			$_login = validLogin($login);
			if(!$_login) {
				$session["errormsg"] = "<p>Votre identifiant n'est pas valide</p>";
				redir_passrecovery($session);
			}

			$resp = email_passrecovery($_login,$session,$pdo);

			if(!$resp) {
				redir_passrecovery($session);
			} else {
				del_session();
				redir_success("mail");
			}
			break;

		case 'newpass':

			if(!$login) {
				del_session();
				die("missing login");
			}

			$_login = validLogin($login);
			if(!$_login) {
				del_session();
				die("invalid login");
			}

			if(!$password) {
				$session["errormsg"] = "<p>Il manque le nouveau mot de passe</p>";
				redir_newpass($session);
			}

			if(!$password2) {
				$session["errormsg"] = "<p>Il manque la confirmation du nouveau mot de passe</p>";
				redir_newpass($session);
			}

			if($password != $password2) {
				$session["errormsg"] = "<p>Les mots de passe ne correspondent pas</p>";
				redir_newpass($session);
			}

			if(!passreg($password)) {
				$session["errormsg"] = "<p>Le mot de passe n'est pas valide (voir tableau ci-dessus)</p>";
				redir_newpass($session);
			}
			
			//create hash
			$hash = password_hash($password,PASSWORD_DEFAULT);

			$q = "UPDATE auth SET hash=:hash WHERE email=:email LIMIT 1";

			try {
				$stmt = $pdo->prepare($q);
				$stmt->bindParam(":email",$_login,PDO::PARAM_STR);
				$stmt->bindParam(":hash",$hash,PDO::PARAM_STR);
				$stmt->execute();
			} catch(Exception $e) {
				error_log("Caught $e");
				$session["errormsg"] = "<p>$e 500 Erreur interne (update pass)</p>";
				redir_newpass($session);
			}

			del_session();
			redir_success("pass");
			break;

		default:
			die("The world's prettiest server can't handle pages that don't exist");
			break;
	}
	
}

function auth_user($_login,$password,&$session,$pdo,$updateTokenBool) {

	//get hash
	$q = "SELECT hash FROM auth WHERE email=:email LIMIT 1";

	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":email",$_login,PDO::PARAM_STR);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		$session["errormsg"] = "<p>500 Erreur interne (get hash)</p>";
		return false;
	}
	if(!$arr) {
		$session["errormsg"] = "<p>Cet identifiant est inconnu</p>";
		return false;
	}
	$hash = $arr["hash"];

	if (!password_verify($password, $hash)) {
		sleep(3); //minimal bruteforce protection (caution : session expiration)
	    $session["errormsg"] = "<p>Mot de passe erroné</p>";
	    return false;
	}

	if($updateTokenBool) {
		//save token
		$token = bin2hex(openssl_random_pseudo_bytes(32));

		date_default_timezone_set("Europe/Paris");
		$time = date("Y-m-d H:i:s"); //mysql datetime format

		$q = "UPDATE auth SET token=:token,time=:time WHERE email=:email";

		try {
			$stmt = $pdo->prepare($q);
			$stmt->bindParam(":token",$token,PDO::PARAM_STR);
			$stmt->bindParam(":time",$time,PDO::PARAM_STR);
			$stmt->bindParam(":email",$_login,PDO::PARAM_STR);
			$stmt->execute();
		} catch(Exception $e) {
			error_log("Caught $e");
			$session["errormsg"] = "<p>500 Erreur interne (update token)</p>";
			return false;
		}
		return $token;

	} else {

		return true;
	}

}

function new_user($_login,$_password,&$session,$pdo) {

	//verify email availability
	$q = "SELECT email FROM auth WHERE email=:email LIMIT 1";

	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":email",$_login,PDO::PARAM_STR);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		$session["errormsg"] = "<p>500 Erreur interne (verify email)</p>";
		return false;
	}
	if($arr) {
		$session["errormsg"] = "<p>Cet identifiant existe déjà (perdu mon compte ?)</p>";
		return false;
	}

	//create hash
	$hash = password_hash($_password,PASSWORD_DEFAULT);

	//create token
	$token = bin2hex(openssl_random_pseudo_bytes(32));
	date_default_timezone_set("Europe/Paris");
	$time = date("Y-m-d H:i:s"); //mysql datetime format

	$q1 = "INSERT INTO auth(email,hash,token,time,privlvl)
		   VALUES (:email,:hash,:token,:time,1)";

	$q2 = "INSERT INTO users(uid) VALUES (:uid)";

	try {
		$pdo->beginTransaction();

		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":email",$_login,PDO::PARAM_STR);
		$stmt1->bindParam(":hash",$hash,PDO::PARAM_STR);
		$stmt1->bindParam(":token",$token,PDO::PARAM_STR);
		$stmt1->bindParam(":time",$time,PDO::PARAM_STR);
		$stmt1->execute();

		$uid = $pdo->lastInsertId(); //returns as string

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":uid",$uid,PDO::PARAM_STR);
		$stmt2->execute();

		$pdo->commit();

	} catch(Exception $e) {
		$pdo->rollback();
		error_log("Caught $e");
		$session["errormsg"] = "<p>500 Erreur interne (new user)</p>";
		return false;
	}

	return $token;

}

function redir_logout($session) {

	$token = $session["token"];
	$appid = $session["appid"];
	del_session();

	if(!$token) {
		die("Missing or invalid token");
	}

	if(!$appid) {
		die("Missing appid");
	}

	$pdo = open_db();

	$q1 = "UPDATE auth SET token=NULL,time=NULL WHERE token=:token";

	$q2 = "SELECT domain FROM trusted WHERE appid=$appid LIMIT 1";

	try {
		$stmt = $pdo->prepare($q1);
		$stmt->bindParam(":token",$cid,PDO::PARAM_STR);
		$stmt->execute();

		$stmt = $pdo->prepare($q2);
		$stmt->bindParam(":appid",$appid,PDO::PARAM_INT);
		$stmt->execute();
		$arr = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		die("Could not discard token");
	}
	if(!$arr) die("Unknown appid");

	$url = $arr["domain"];
	header("Location: $url");
	die();
}

function redir_join(&$session) {
	
	if(!$session["appid"]) die("Invalid appid");
	//page is signed by server to protect against forgery
	$session["signature"] = bin2hex(openssl_random_pseudo_bytes(20));
	header("Location: /join.php");
	die();
}

function redir_login(&$session) {
	if(!$session["appid"]) die("Invalid appid");
	$session["signature"] = bin2hex(openssl_random_pseudo_bytes(20));
	header("Location: /login.php");
	die();
}

function redir_changepass(&$session) {
	if(!$session["login"]) die("Invalid login");
	$session["signature"] = bin2hex(openssl_random_pseudo_bytes(20));
	header("Location: /changepass.php");
	die();
}

function redir_success($r) {

	header("Location: /success.php?r=$r");
	die();
}

function redir_newuser($token,$_appid,$pdo) {

	$q = "SELECT redirect_newuser,domain FROM trusted WHERE appid=:appid LIMIT 1";

	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":appid",$_appid,PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		die("Could not fetch trusted domain");
	}
	if(!$row) die("unknown appid");

	$url = $row["domain"].$row["redirect_newuser"]."#$token";
	header("Location: $url");
	die();
}

function redir_authuser($token,$_appid,$pdo) {

	$q = "SELECT redirect_authuser,domain FROM trusted WHERE appid=:appid LIMIT 1";

	try {
		$stmt = $pdo->prepare($q);
		$stmt->bindParam(":appid",$_appid,PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
		error_log("Caught $e");
		die("Could not fetch trusted domain");
	}
	if(!$row) die("unknown appid");

	$url = $row["domain"].$row["redirect_authuser"]."#$token";
	header("Location: $url");
	die();
}

function redir_passrecovery($session) {
	$session["signature"] = bin2hex(openssl_random_pseudo_bytes(20));
	header("Location: /passrecovery.php");
	die();
}

function redir_newpassrecovery(&$session) {

	$pdo = open_db();

	$token = $session["token"] or die("missing token");

	$q1 = "SELECT email from auth LEFT JOIN recovery 
		   ON recovery.uid = auth.uid WHERE recovery.token=:token";

	$q2 = "DELETE FROM recovery WHERE token=:token";

	try {
		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":token",$token,PDO::PARAM_STR);
		$stmt1->execute();
		$arr = $stmt1->fetch(PDO::FETCH_ASSOC);
		$error = "Unknown recovery token";
		if(!$arr) throw new Exception($error);

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":token",$session["token"],PDO::PARAM_STR);
		$stmt2->execute();

	} catch(Exception $e) {
		error_log("Caught $e");
		die("$e Could not fetch email from token");
	}

	$session["login"] = $arr["email"];
	
	redir_newpass($session);
}

function redir_newpass(&$session) {
	$session["signature"] = bin2hex(openssl_random_pseudo_bytes(20));
	header("Location: /newpass.php");
	die();
}

function email_passrecovery($email,&$session,$pdo) {

	$token = bin2hex(openssl_random_pseudo_bytes(20));
	date_default_timezone_set("Europe/Paris");
	$time = date("Y-m-d H:i:s"); //mysql datetime format

	$q1 = "SELECT uid FROM auth WHERE email=:email LIMIT 1";

	$q2 = "INSERT INTO recovery(uid,time,token) VALUES (:uid,:time,:token)";

	try {
		$pdo->beginTransaction();

		$stmt1 = $pdo->prepare($q1);
		$stmt1->bindParam(":email",$email,PDO::PARAM_STR);
		$stmt1->execute();
		$stmt1->bindColumn("uid", $uid);
		$uid = $stmt1->fetch(PDO::FETCH_BOUND);
		if(!$uid) {
			$session["errormsg"] = "<p>Cet indentifiant est inconnu</p>";
			return false;
		}

		$stmt2 = $pdo->prepare($q2);
		$stmt2->bindParam(":token",$token,PDO::PARAM_STR);
		$stmt2->bindParam(":time",$time,PDO::PARAM_STR);
		$stmt2->bindParam(":uid",$uid,PDO::PARAM_INT);
		$stmt2->execute();

		$pdo->commit();

	} catch(Exception $e) {
		$pdo->rollback();
		error_log("Caught $e");
		$session["errormsg"] = "<p>500 Erreur interne (recovery)</p>";
		return false;
	}

	$recoveryUrl = "https://rhjodoigne.be/session.php?p=newpass&token=".$token;

	$to      = $email;
	$subject = "Récupération de mdp";
	$message = "Vous avez fait une demande de récupération de mot de passe.\r\nVeuillez suivre ce lien :\r\n$recoveryUrl\r\n";
	$headers = 'From: postmaster@rhjodoigne.be' . "\r\n" .
			    'Reply-To: postmaster@rhjodoigne.be' . "\r\n" .
			    'X-Mailer: PHP/' . phpversion();

	if(!mail($to, $subject, $message, $headers)) {
		$session["errormsg"] = "<p>Le mail n'a pas pu être envoyé</p>";
		return false;
	} else {
		return true;
	}
}

function new_session() {

	/*$session_name = "auth session";
	$cookieParams = session_get_cookie_params();

	$lifetime = 60;
	$secure = true;
	$httponly = true;
	$path = $cookieParams["path"];
	$domain = $cookieParams["domain"];

	session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
	session_name($session_name);*/
	session_start();
	//session id will still be the same when next person logins even after destroy
	//so generate new one to avoid session hijacking
	session_regenerate_id(true);
}

function del_session() {

	session_unset();
	session_destroy();
}

function passreg($password) {

	/*
			This regex will enforce these rules:

    	      - At least one upper case english letter
			  - At least one lower case english letter
			  - At least one digit
			  - At least one special character (#?!@$%^&*-=)
			  - Minimum 8 in length

			*/

	return preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-=]).{8,}$/", $password);
}

header("Location: index.php");
die();
?>