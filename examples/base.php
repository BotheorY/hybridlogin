<?php
	
	require_once '../src/common.php';

	$app_auth = false;

	if ((!empty($_GET)) && (!empty($_GET['email'])) && (!empty($_GET['account'])) && (!empty($_GET['pwd']))) {
		$app_auth = hl_chk_login($_GET['email'], $_GET['account'], $_GET['pwd']);
	}

?><!DOCTYPE html>
<html>

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Login Page</title>
		<link rel="stylesheet" href="../src/hybridlogin.css?ver=1">
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>	
		<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script>	
		<script src="../src/hybridlogin.js?ver=202"></script>
	</head>

	<body>

	<div><button id="start-test"></button></div>
	<div><button style="display: none;" id="delete-user">Delete logged in user</button></div>


<?php if ($app_auth) { ?>
		<div><h1>LOGGED IN APP!</h1></div>
<?php } ?>


		<div id="hl-main-div"></div>
		
		<script>

<?php if ($app_auth) { ?>
			var appAuth = true;
<?php } else { ?>
			var appAuth = false;
<?php } ?>

			var connected = true;

			document.addEventListener('DOMContentLoaded', function() {
				hlObj.init('it');
				var button = document.getElementById('start-test');
				button.addEventListener('click', function() {
					if (connected) {
						hlObj.disconnect();
					} else {
						hlObj.start(login, false, registerCallback);
					}
				});
				var delUserBtn = document.getElementById('delete-user');
				delUserBtn.addEventListener('click', function() {
					if (hlObj.deleteEmailAccount()) {
						alert('User deleted');
						window.location.href = getCurrUrl({});
					}
				});
				hlObj.start(login, true, registerCallback);
			});
	
			function login(email, connType, password, err = null) {
	
				if (email) {
					if (appAuth) {
						$("#start-test").html("<center><b>Disconnect</b><br>(Logged in as " + email + ' via ' + connType + ') [' + password + ']</center>');
						if (connType.toUpperCase().trim() === 'EMAIL') {
							$("#delete-user").show();
						} else {
							$("#delete-user").hide();
						}
						if (!connected) {
							connected = true;
							alert("Logged in as " + email + ' via ' + connType);
						}
					} else {
						window.location.href = getCurrUrl({email: email, account: connType, pwd: password});
						return true;
					}
				} else {
					if (err) {
						alert("[ERROR] " + err);
					} else {
						if (appAuth) {
							window.location.href = getCurrUrl({});
							return true;
						} else {
							connected = false;
							$("#start-test").html("<b>Login</b>");
							$("#delete-user").hide();
						}
					}
				}
	
			}

			function registerCallback(email, password) {

				alert(hlObj.local.registrationSucceeded);

			}

			function getCurrUrl(parameters) {

				var url = window.location.href;

				if (parameters) {
					url = url.split('?')[0] + '?' + new URLSearchParams(parameters).toString();
				}

				if (url.endsWith("?")) {
					url = url.slice(0, -1);
				}

				return url;
			}

		</script>
	
	</body>

</html>
