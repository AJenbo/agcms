<?php
	date_default_timezone_set('Europe/Copenhagen');
	function test_get() {
		return test();
	}
	
	function test_post() {
		return test();
	}
	
	function test() {
		$s = "URI: ".$_SERVER['PHP_SELF'];
		$s .= "\n\n-- GET --\n";
		if(!empty($_GET["rsargs"]))
			$s .= $_GET["rsargs"];
		
		$s .= "\n-- POST --\n";
		if(!empty($_POST["rsargs"]))
			$s .= $_POST["rsargs"];
		
		return $s;
	}
	
	function get_the_time() {
		return date("Y-m-d h:i:s");
	}
	
	require_once("sajax.php");
//	$sajax_debug_mode = true;
	$sajax_failure_redirect = "http://sajax.info/sajaxfail.html";
	sajax_export(
		array("name" => "test_get", "method" => "GET"),
		array("name" => "test_post", "method" => "POST"),
		array("name" => "get_the_time", "method" => "GET"),
		array("name" => "test", "method" => "GET"),
		array("name" => "sleep", "asynchronous" => false),
		array("name" => "otherefucntion", "uri" => "example_otheruri.php"),
		array("name" => "otherefucntion2")
	);
	sajax_handle_client_request();
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Example of Sajax Options</title>
<script type="text/javascript" src="json2.stringify.js"></script>
<script type="text/javascript" src="json_stringify.js"></script>
<script type="text/javascript" src="json_parse_state.js"></script>
<script type="text/javascript" src="sajax.js"></script>
<script type="text/javascript"><!--
	<?php sajax_show_javascript(); ?>
	function print_result(v) {
		alert(v);
	}
//-->
</script>
</head>
<body>
<!-- Testing if the browser supports GET -->
<button onclick="x_test_get(1, 2, 3, print_result);">Test GET</button>

<!-- Testing if the browser supports POST -->
<button onclick="x_test_post(1, 2, 3, print_result);">Test POST</button>

<!-- Forcing the function to POST -->
<button onclick="sajax_request_type ='POST'; x_test(1, 2, 3, print_result); sajax_request_type ='';">Test force POST</button>

<!-- if sajax_target_id is set, the sesponce will be inserted as HTML in an element with sajax_target_id for id-->
<button onclick="sajax_target_id = 'time'; x_get_the_time(); sajax_target_id = '';">Test updating IDs</button>

<!-- Calling a synchronous will cause the script to wait for the responce -->
<button onclick="x_sleep(3, function(){}); alert('Link was clicked!');">Test synchronous</button>

<!-- Different URI set at config -->
<button onclick="x_otherefucntion(print_result);">Call to other uri.</button>

<!-- Forece different URI at runtime -->
<button onclick="sajax_remote_uri = 'example_otheruri.php'; x_otherefucntion2(print_result); sajax_remote_uri = '';">Force call to other uri.</button>
<div id="time"><em>Time will appear here</em></div>
</body>
</html>
