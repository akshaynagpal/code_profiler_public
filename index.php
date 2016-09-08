<?php 
	session_start();
	require 'db_connect.php';
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
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<title>Code Profiler - Github Code Summary and Analysis</title>
	<link rel="stylesheet" type="text/css" href="css/index.css">
	<script src="canvasjs.min.js" type="text/javascript"></script>
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-XXXXXXXX-1', 'auto');
	  ga('send', 'pageview');
	</script>
</head>
<body>
<h1>CODE PROFILER</h1>
<small>Github Code Summary & Analysis</small>
<hr>
<?php
	if(isset($_GET['u'])){  // User Profile Display
		$_SESSION['u'] = $_GET['u'];
		$u = $_SESSION['u'];
		$select_query = 'SELECT * FROM codeprofiles WHERE github_username=$1';
		$table_select = pg_query_params($pg_conn,$select_query,array($u)) or die("Error while selecting.");
		$codeprofile = pg_fetch_assoc($table_select);
		$num_rows = pg_num_rows($table_select);
		if($num_rows<1){
			echo "Looks like <strong> ".$u."</strong> has not used CodeProfiler even once! <br> Please visit <a href='https://codeprofiler.herokuapp.com'>CodeProfiler</a> .";
		}
		else{
			echo '<p style="text-align:center;"><img src="'.$codeprofile['avatar_url'].'" class="img-responsive img-circle center-block"  width="250" height="250"></p>';
			echo '<h1>'.$codeprofile['github_username'].'</h1>';
			if($codeprofile['bio']!="NULL") echo $codeprofile['bio'].'<br>';

			echo '<div class="container-fluid">
				  <div class="row">
				  <div class="col-sm-6" align="center"><div class = "panel panel-default">';
			if($codeprofile['current_employer']!="NULL"){
				echo '<div class = "panel-heading">';
				echo 'Works at: </div><div class="panel-body">'.$codeprofile['current_employer'].'</div></div>';
			}
			echo '</div><div class="col-sm-6" align="center"><div class = "panel panel-default">';
			echo '<div class = "panel-heading">Network</div>';
			echo '<div class = "panel-body">';
			echo 'Followers: <span class="badge">'.$codeprofile['followers'].'</span><br> Following: <span class="badge">'.$codeprofile['following'].'</span></div></div>';
			echo '</div>
			</div>
			<div class="row">
			<div class="col-sm-6" align="center"><div class = "panel panel-default">';
			echo '<div class="panel-heading">Stars</div><div class = "panel-body">';
			echo 'Repo with max stars: <a href="'.$codeprofile['repo_with_max_stars_url'].'" target="_blank">'.$codeprofile['repo_with_max_stars'].'</a><span class="badge">'.$codeprofile['max_stars'].'</span>';
			//echo $codeprofile["repo_with_max_stars_url"];
			echo '<br>';
			echo 'Total stars: '.$codeprofile['sum_of_stars'];
			echo '</div></div>';
			echo '</div><div class="col-sm-6" align="center"><div class = "panel panel-default">';
			echo '<div class="panel-heading">Forks</div><div class = "panel-body">';
			echo 'Repo with max forks: <a href="'.$codeprofile['repo_with_max_forks_url'].'" target="_blank">'.$codeprofile['repo_with_max_forks'].'</a><span class="badge">'.$codeprofile['max_forks'].'</span>';
			echo '<br>';
			echo 'Total forks: '.$codeprofile['sum_of_forks'];
			echo '</div></div>';
			echo '</div>
			</div>
			<div class="row"><div class="col-sm-12" align="center"><div class = "panel panel-default">';
			echo '<div class="panel-heading">Open Source Contribution</div><div class = "panel-body">';
			$open_pr = $codeprofile["total_pr"]-$codeprofile["closed_pr"];
			echo $codeprofile["github_username"].' has made <span class="badge">'.$codeprofile["total_pr"].'</span> Pull Requests in last 1 year, out of which <span class="badge">'.$codeprofile["closed_pr"].'</span> have been closed with <span class="badge">'.$codeprofile["merged_pr"].'</span> merges, and <span class="badge">'.$open_pr.'</span> are still open.';
			echo '</div></div>';
			echo '</div>
			</div>';

			$language_count_array = explode(',', $codeprofile['lang_count_string']);
			$language_count_array_size = count($language_count_array);
			$final_language_count_array = [];
			for($i=0;$i<$language_count_array_size;$i++){
				$temp = explode('-', $language_count_array[$i]);
				$final_language_count_array[$temp[0]] = $temp[1];
			}
			$sum_of_lang_count = 0.000;
			foreach ($final_language_count_array as $lang => $count) {
				$sum_of_lang_count += (float)$count;
			}
			echo '<div class="row"><div class="col-sm-12" align="center"><div class = "panel panel-default">';
			echo '<div id="lang_chart" style="height: 500px; width: 100%; margin: auto;"></div>';
			echo '</div></div></div>';
			//echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';


?>
<script type="text/javascript">
		window.onload = function(){
			var chart = new CanvasJS.Chart("lang_chart",
			{
				backgroundColor: "#99CCFF",
				title:{
					text:"Languages Used in Projects (%)"
				},
				data:[
				{
					type:"doughnut",
					showInLegend: true, 
					dataPoints:[
					<?php
						$counter = 0;
						foreach ($final_language_count_array as $lang => $count) {
							$counter++;
							$percentage = (float)$count*100/$sum_of_lang_count;
							if($counter==$language_count_array_size){
								echo '{label:"'.$lang.'", legendText:"'.$lang.' '.number_format((float)$percentage,2,'.','').'%", y:'.number_format((float)$percentage,2,'.','').'}';	
							}
							else{
								echo '{label:"'.$lang.'", legendText:"'.$lang.' '.number_format((float)$percentage,2,'.','').'%", y:'.number_format((float)$percentage,2,'.','').'},';	
							}
						}
					?>
					]
				}
				]
			});
			chart.render();
		}	
</script>

<?php
			// echo '<div id="someDiv" style="margin-right:auto; margin-left:auto; float: left; width:50%; position:relative;">';
			echo '<div class="row"><div class="col-sm-6" align="center"><div class = "panel panel-default">';
			echo '<div class = "panel-heading">Gists and Commit Rate</div>';
			echo 'Number of gists contributed: '.$codeprofile['num_gists'];
			echo '<br>';
			echo 'Commits in last 1 year to Owner repos: '.$codeprofile['commit_sum'];
			echo '</div>';
			echo '</div><div class="col-sm-6" align="center"><div class = "panel panel-default">';
			echo '<div class = "panel-heading">Share CodeProfile</div>';
			echo 'Link: <a href="https://codeprofiler.herokuapp.com/?u='.$codeprofile['github_username'].'">codeprofiler.herokuapp.com/?u='.$codeprofile['github_username'].'</a>';
			echo '<br>';
			echo '<a href="http://www.web2pdfconvert.com/engine?curl=https://codeprofiler.herokuapp.com/?u='.$u.'">Download PDF of CodeProfile</a>';
			echo '</div></div></div></div>';
			// echo '</div>';
		}
		pg_close($pg_conn);
	}
	else{ // Home Page display
?>	

<script type="text/javascript">
	function waitingMessageDisplay(){
		document.getElementById("waitingMessage").innerHTML = "Authenticating.. Please wait..";
		return true;
	}
</script>

<p>
	Scan your Github profile and get summary about your repositories and gists.
</p>
<p>
	Click the <strong> Login button below </strong>to see your code's analysis and summary.
</p>

<br>

<?php $client_id = 'ce65f658f01109625b06'; ?>

<a href="https://github.com/login/oauth/authorize?scope=user:email%20repo%20gist&client_id=<?=$client_id?>" onclick="return waitingMessageDisplay();" class="button">Login using Github</a>
<br>
<br>

<div id="waitingMessage"></div>

<br>
<br>

<?php
	}
?>
</body>
<hr>
<footer>Copyright 2016 <a href="https://linkedin.com/in/akshaynagpal" target="_blank">Akshay Nagpal</a>.</footer>
</html>