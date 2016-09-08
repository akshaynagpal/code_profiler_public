<?php
function is_new_user($pg_conn,$github_username){
	$select_query = 'SELECT * FROM TABLE_NAME_SECRET WHERE COLUM_NAME_HIDDEN=$1';
	$table_select = pg_query_params($pg_conn,$select_query,array($github_username)) or die("Error while selecting.");
	if(pg_num_rows($table_select)==0){
		return true;
	}
	else{
		return false;
	}
}

function is_eligible_for_new_scan($github_username){
	return true;
}
?>