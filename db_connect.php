<?php
function pg_connection_string_from_database_url(){
	extract(parse_url($_ENV["DATABASE_URL"]));
	return "user=$user password=$pass host=$host dbname=" . substr($path, 1);
}

$pg_conn = pg_connect(pg_connection_string_from_database_url());

if(!$pg_conn){
	echo "unable to connect to database!!";
}

$table_create_query = "
CREATE TABLE IF NOT EXISTS TABLE_NAME_SECRET(
	TABLE_COLUMN_NAMES_SECRET;
);
";
$table_create = pg_query($pg_conn,$table_create_query);

if(!$table_create){
	echo "table creation error!";
}

?>