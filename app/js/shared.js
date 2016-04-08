
function toHome() {
	window.location.href = "index.html";
}

function checkLoggedIn() {
	if(!sessionStorage.getItem("email")) {
		window.location.href = "/session.php?p=login&appid=1";
	}
}

function user(restrictedBool) {
	var loginp = document.getElementById("loginp");
	var email = sessionStorage.getItem("email");
	if(!email) {
		if(restrictedBool) {
			window.location.href="/session.php?p=login&appid=1";
		} else {
			//login
			var btn = document.createElement("button");
			btn.textContent = "Connexion";
			btn.onclick = function () {
				window.location.href="/session.php?p=login&appid=1";
			}
			loginp.appendChild(btn);
			//signup
			btn = document.createElement("button");
			btn.textContent = "Inscription";
			btn.onclick = function () {
				window.location.href="/session.php?p=join&appid=1";
			}
			loginp.appendChild(btn);
		}
	} else {
		//email
		var p = document.createElement("p");
		p.textContent = email;
		loginp.appendChild(p);
		//gear
		var img = document.createElement("img");
		img.setAttribute("src","img/gear.png");
		img.setAttribute("title","Panneau d'utilisateur");
		img.className = "panelsIcon";
		img.onclick = function () {
			window.location.href = "userpanel_reservations.html";
		}
		loginp.appendChild(img);
		//logout
		img = document.createElement("img");
		img.setAttribute("src","img/logout.png");
		img.setAttribute("title","Déconnexion");
		img.className = "panelsIcon";
		img.onclick = function () {
			window.location.href = "logout.html";
		}
		loginp.appendChild(img);
	}
}

function saveSessionToken() {
	var token = window.location.hash;
	if(!token) {
		alert("missing token");
		throw new Error("missing token");
	}
	token = token.substring(1); //remove '#'
	sessionStorage.setItem("token",token);
	return token;
}

function intToStrUtitle(int) {
	switch(int) {
		case 0:
			return "M."
			break;
		case 1:
			return "Mme"
			break;
		default:
			return ""
			break;	
	}
}

function admin() {
	var ul = document.getElementById("menul");
	if(sessionStorage.getItem("privlvl") >= 2) {
		var li = document.createElement("li");
		li.innerHTML = "<a href='adminpanel_reservations.html'>Administration</a>";
		ul.appendChild(li);
	}
}
