<?php
	session_start();
	require 'curl_operations.php';
	require 'db_connect.php';
	require 'db_functions.php';
	// preventing random access without sequence
	if(isset($_GET['code'])){
		$code = $_GET['code'];
	}
	else{
		header("Location: https://codeprofiler.herokuapp.com");
	}

	// CURL to get access token
	$output = curlPost("https://github.com/login/oauth/access_token?","client_id=ID&client_secret=SECRET&code=".$code);
	$output_separated = explode('&', $output);
	$access_token = explode('=',$output_separated[0])[1];
	$_SESSION["access_token"] = $access_token;

	if(!isset($_SESSION["access_token"])){
		header("Location: https://codeprofiler.herokuapp.com");
	}

	// CURL to get user info
	$user = curlGet('https://api.github.com/user?access_token='.$access_token);
	$user_info = json_decode($user,true);
	$_SESSION["u"] = $user_info['login'];

	if(is_new_user($pg_conn,$user_info['login'])){
		$_SESSION["is_new_user"] = true;
		$body_html='
		<h2>Hi! New User!</h2>
		<a href="scan_profile.php" onclick="return waitingMessageDisplay();" class = "button"> Scan Github Profile Now! </a>
		';
	}
	else{
		$_SESSION["is_new_user"] = false;
		$body_html = '
		<h2>Welcome back @'.$_SESSION["u"].'!</h2>
		<a href="https://codeprofiler.herokuapp.com/?u='.$_SESSION["u"].'" class = "button">Show last scanned profile </a>
		<br><br><br>
		OR 
		<br><br><br>
		<a href="scan_profile.php" onclick="return waitingMessageDisplay();" class = "button"> Scan Github Profile Now! </a> <br><br> ';
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="description" content="Github Code Summary & Analysis">
	<meta name="author" content="Akshay Nagpal">
	<meta name="keywords" content="code analytics, code analysis, github analysis, code summary">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='https://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css'>
	<base href="https://codeprofiler.herokuapp.com/">
	<title>Code Profiler - Github Code Summary and Analysis</title>
	<link rel="stylesheet" type="text/css" href="css/github_login.css">

</head>
<body>

	
<h1>CODE PROFILER</h1>
<small>Github Code Summary & Analysis</small>
<hr>
<script type="text/javascript">
	function waitingMessageDisplay(){
		document.getElementById("message").innerHTML = "Scanning your Github Profile.. Please wait..This may take time depending on the size of your Github repositories and internet speed.";
		return true;
	}
</script>
<div id="message"></div>
<?php echo $body_html; ?> 
<br>
<br>
<hr>
</body>
<footer>Copyright 2016 <a href="https://linkedin.com/in/akshaynagpal" target="_blank">Akshay Nagpal</a>.</footer>
</html>