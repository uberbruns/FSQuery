<?

	require "../class.fsquery.php";

	header("Content-Type:text/plain") ; 
	$fs_query = new FSQuery("Blog", dirname($_SERVER['PHP_SELF'])."/Blog");

	$time_start = microtime(true);
	$query = $fs_query->query("#Blog *");
	$time_end = microtime(true);

	echo "Execution Time: ".($time_end - $time_start)." sec\n\n";
	print_r($query );

?>