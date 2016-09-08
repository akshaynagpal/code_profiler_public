<?php
session_start();
require 'curl_operations.php';
require 'db_connect.php';
require 'db_functions.php';
$access_token = $_SESSION["access_token"];
if(!isset($_SESSION["access_token"])){
	header("Location: https://codeprofiler.herokuapp.com");
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Your Codeprofile - CodeProfiler</title>
</head>
<body>
	
<div id="loadingMessage"><h1>Almost Done! Hold on! Your CodeProfile is being prepared!</h1></div>

<?php
	// CODE FOR `codeprofile` ARRAY CREATION
	date_default_timezone_set('UTC');
	$codeprofile = array();

	// CURL to get user info
	$user = curlGet('https://api.github.com/user?access_token='.$access_token);
	$user_info = json_decode($user,true);
	//var_dump($user_info);

	$codeprofile['username'] = $user_info['login'];
	$codeprofile['github_profile_url'] = $user_info['html_url'];
	$codeprofile['last_scan_date'] = date('Y-m-d');
	$codeprofile['bio'] = ($user_info['bio']!=null ? $user_info['bio']:"NULL");
	$codeprofile['avatar_url'] = $user_info['avatar_url'];
	$codeprofile['current_employer'] = ($user_info['company']!=null?$user_info['company']:"NULL");

	// number of followers
	$followers = $user_info["followers"];
	$codeprofile['number_of_followers'] = $followers;

	// number of people following
	$following = $user_info["following"];
	$codeprofile['number_of_following'] = $following;

	// CURL get repo list
	$repos = curlGet('https://api.github.com/user/repos?per_page=100&access_token='.$access_token);
	$repos_info = json_decode($repos,true);
	//var_dump($repos_info);

	//variables to store parameters analyzed for each repo
	$sum_of_stars = 0;
	$max_stars = 0;
	$repo_with_max_stars = NULL;
	$sum_of_forks = 0;
	$max_forks = 0;
	$repo_with_max_forks = NULL;
	$total_pull_requests_to_forked_repos = 0;
	$merged_pull_requests = 0;
	$closed_pull_requests = 0;
	$language_count = array();
	$number_of_gists = 0;
	$commit_sum = 0;
	$repo_with_max_stars_url = "";
	$repo_with_max_forks_url = "";
	$language_count_string = "";

	foreach($repos_info as $repository){
		if(!$repository["fork"]){ // owner repo
			//stargazers
			$number_of_stargazers = $repository["stargazers_count"]; 
			$sum_of_stars += $number_of_stargazers; 
			if($number_of_stargazers>$max_stars && $repository["private"]==false){
				$max_stars = $number_of_stargazers;
				$repo_with_max_stars = $repository["name"];
				$repo_with_max_stars_url = $repository["html_url"];
			}

			//forks
			$number_of_forks = $repository["forks_count"];
			$sum_of_forks += $number_of_forks;
			if($number_of_forks>$max_forks && $repository["private"]==false){
				$max_forks = $number_of_forks;
				$repo_with_max_forks = $repository["name"];
				$repo_with_max_forks_url = $repository["html_url"];
			}

			//language_count
			$languages = json_decode(curlGet($repository["languages_url"].'?access_token='.$access_token),true);
			foreach ($languages as $language => $bytes) {
				if(array_key_exists($language, $language_count)){
					$language_count[$language] += $bytes;
				}
				else{
					$language_count[$language] = $bytes;
				}
			}

			$participation = json_decode(curlGet($repository["url"].'/stats/participation?access_token='.$access_token),true);
			$commit_sum += array_sum($participation["owner"]);
		}
		else{  //forked repos and contributions to the, in last 1 year
			$forked_repository = json_decode(curlGet($repository["url"].'?access_token='.$access_token),true);
			$pulls_url = explode("{", $forked_repository["source"]["pulls_url"])[0];
			$pulls_of_forked_repo = json_decode(curlGet($pulls_url.'?state=all&access_token='.$access_token),true);
			foreach($pulls_of_forked_repo as $pull_request){
				if($pull_request["user"]["login"]==$codeprofile['username']){ // pull req created by authenticated user
					$date_pull_request_created = substr($pull_request['created_at'],0,10);
					$difference = date_diff(date_create($date_pull_request_created),date_create());
					if($difference->days<367){ //within last 1 year
						$total_pull_requests_to_forked_repos += 1;  // total pull req
						if($pull_request['state']=="closed"){
							$closed_pull_requests += 1;				// closed (merger + non-merged)
							if($pull_request['merged_at']!=null){	
								$merged_pull_requests += 1;			// merged pull req
							}	
						}				
					}
				}
			}
		}
	}

	arsort($language_count); // sorting in decreasing value of bytes of code written in a language
	$codeprofile['repo_with_max_stars'] = $repo_with_max_stars;
	$codeprofile['max_stars'] = $max_stars;
	$codeprofile['sum_of_stars'] = $sum_of_stars;
	$codeprofile['repo_with_max_forks'] = $repo_with_max_forks;
	$codeprofile['max_forks'] = $max_forks;
	$codeprofile['sum_of_forks'] = $sum_of_forks;
	$codeprofile['total_pull_requests_to_forked_repos'] = $total_pull_requests_to_forked_repos;
	$codeprofile['closed_pull_requests'] = $closed_pull_requests;
	$codeprofile['merged_pull_requests'] = $merged_pull_requests;
	$codeprofile['language_count'] = $language_count;
	$codeprofile['number_of_gists'] = $user_info["public_gists"];
	$codeprofile['commit_sum'] = $commit_sum;
	$codeprofile['repo_with_max_stars_url'] = $repo_with_max_stars_url;
	$codeprofile['repo_with_max_forks_url'] = $repo_with_max_forks_url;

	foreach ($language_count as $language => $bytes) {
		$language_count_string .= $language."-".$bytes.",";
	}
	$codeprofile['language_count_string'] = substr($language_count_string, 0, strlen($language_count_string)-1);

	//var_dump($codeprofile);
?>

<?php
	// inserting into DB
if ($_SESSION["is_new_user"]==true) {
	$insert_query = 'INSERT INTO TABLE_NAME_SECRET(COLUMN_NAMES_SECRET)
	VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20,$21,$22);';
}
else{
	$insert_query = 'UPDATE TABLE_NAME_SECRET SET COLUMN_NAMES_SECRET WHERE COLUMN_NAMES_SECRET=\''.$codeprofile['username'].'\';';
}
	$table_insert = pg_prepare($pg_conn,'insert_query',$insert_query);
	$table_insert = pg_execute($pg_conn,'insert_query',array($codeprofile['username'],$codeprofile['github_profile_url'],$codeprofile['last_scan_date'],$codeprofile['bio'],$codeprofile['avatar_url'],$codeprofile['current_employer'],$codeprofile['number_of_followers'],$codeprofile['number_of_following'],$codeprofile['repo_with_max_stars'],$codeprofile['repo_with_max_stars_url'],$codeprofile['max_stars'],$codeprofile['sum_of_stars'],$codeprofile['repo_with_max_forks'],$codeprofile['repo_with_max_forks_url'],$codeprofile['max_forks'],$codeprofile['sum_of_forks'],$codeprofile['total_pull_requests_to_forked_repos'],$codeprofile['closed_pull_requests'],$codeprofile['merged_pull_requests'],$codeprofile['language_count_string'],$codeprofile['number_of_gists'],$codeprofile['commit_sum'])) or die("Error while inserting.");
	
	pg_close($pg_conn);

	header('Location: https://codeprofiler.herokuapp.com/?u='.$codeprofile['username']);
	
	// if(!$table_insert){
	// 	echo pg_result_error(pg_get_result($table_insert));
	// 	echo pg_last_error($pg_conn);
	// }
	// else echo "table insert success!";
?>
<script type="text/javascript">
	// hide loading message
	var element = document.getElementById("loadingMessage");
	element.style.display="none";
</script>
</body>
</html>