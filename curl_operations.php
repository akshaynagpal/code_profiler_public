<?php
function curlPost($url,$postFields){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$post_output = curl_exec($ch);
	if ($error = curl_error($ch)) {
	    echo "<br> Error: $error<br />\n";
	}
	curl_close($ch);
	return $post_output;
}

function curlGet($url){
	$ch2 = curl_init();
	curl_setopt($ch2, CURLOPT_USERAGENT, 'akshaynagpal');
	curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch2, CURLOPT_URL, $url);
	$get_output = curl_exec($ch2);
	curl_close($ch2);
	return $get_output;
}

?>