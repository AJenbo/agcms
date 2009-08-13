<?php	
if (!isset($SAJAX_INCLUDED)) {

	/*  
	 * GLOBALS AND DEFAULTS
	 *
	 */ 
	$GLOBALS['sajax_version'] = "0.13";
	$GLOBALS['sajax_debug_mode'] = false;
	$GLOBALS['sajax_export_list'] = array();
	$GLOBALS['sajax_remote_uri'] = "";
	$GLOBALS['sajax_failure_redirect'] = "";
	$GLOBALS['sajax_request_type'] = "GET";
	
	/*
	 * CODE
	 *
	 */
	 
	//
	// Initialize the Sajax library.
	//
	function sajax_init() {
	}
	
	function sajax_handle_client_request() {
		
		if (! empty($_GET["rs"])) 
			$method = "GET";
		
		if (!empty($_POST["rs"]))
			$method = "POST";
		
		if (empty($method))
			return;
		
		ob_start();

		if ($method == "GET") {
			$func_name = $_GET["rs"];
			if (! empty($_GET["rsargs"])) 
				$args = $_GET["rsargs"];
			else
				$args = array();
		} else {
			$func_name = $_POST["rs"];
			if (! empty($_POST["rsargs"])) 
				$args = $_POST["rsargs"];
			else
				$args = array();
		}
		
		if(get_magic_quotes_gpc())
			$args = array_map("stripslashes", $args);
		
		$args = array_map("unserialize", $args);
		
		if(get_magic_quotes_gpc()) {
			function array_addslashes($value) {
				if(is_array($value))
					return array_map("array_addslashes", $value);
				else
					return addslashes($value);
			}
			
			$args = array_map("array_addslashes", $args);
		}
		
		global $sajax_export_list;
		foreach($sajax_export_list as $function)
			if(is_array($function))
				$function_name_list[] = $function['name'];
			else
				$function_name_list[] = $function;
		
		if (! in_array($func_name, $function_name_list)) {
			$error = $func_name." not callable";
		} else {
			$result = call_user_func_array($func_name, $args);

			$error = ob_get_contents();
			ob_end_clean();
		}
			
		header('Content-Type: text/plain; charset=UTF-8');
		if(!empty($error)) {
			echo '-:'.$error;
		} else {
			echo "+:".json_encode($result);
		}
		exit;
	}
	
	function sajax_get_common_js() {
		global $sajax_debug_mode;
		global $sajax_remote_uri;
		global $sajax_failure_redirect;
		
		ob_start();
		?>
		
		// remote scripting library
		// (c) copyright 2005 modernmethod, inc
		var sajax_debug_mode = <?php echo($sajax_debug_mode ? "true" : "false"); ?>;
		var sajax_request_type = "";
		var sajax_target_id = "";
		var sajax_failure_redirect = "<?php echo($sajax_failure_redirect); ?>";
		
		function sajax_debug(text) {
			if (sajax_debug_mode)
				alert(text);
		}
		
		var sajax_requests = new Array();
		
		function sajax_cancel(id) {
			 if(arguments.length == 0) {
				for (var i = 0; i < sajax_requests.length; i++)
					if(sajax_requests[i]) {
						sajax_requests[i].abort();
						sajax_requests.splice(i, 1, null);
					}
			} else if(sajax_requests[id]) {
				sajax_requests[id].abort();
				sajax_requests.splice(id, 1, null);
			}
		}
		
        if (typeof(XMLHttpRequest) == "undefined") {
            XMLHttpRequest = function() {
                var msxmlhttp = Array(
                    'Msxml2.XMLHTTP.6.0',
                    'Msxml2.XMLHTTP.5.0',
                    'Msxml2.XMLHTTP.4.0',
                    'Msxml2.XMLHTTP.3.0',
                    'Msxml2.XMLHTTP',
                    'Microsoft.XMLHTTP');
                for (var i = 0; i < msxmlhttp.length; i++) {
                    try { return new ActiveXObject(msxmlhttp[i]); }
                    catch(e) {}
                }
                throw new Error("This browser does not support XMLHttpRequest.");
                return null;
            };
        }
		
        if (typeof(encodeURIComponent) == "undefined") {
        	sajax_debug("No encodeURIComponent using escape, international characters will not be transferred correctly.");
            encodeURIComponent = function($string) {
                return escape($string);
            };
        }
		
		function sajax_do_call(func_name, args, method, asynchronous) {
			
			//Handle old code calls
			if(arguments.length == 2) {
				var method = "GET";
				var asynchronous = true;
			} else if(arguments.length == 3) {
				var asynchronous = true;
			}
			
			if(sajax_request_type != "")
				method = sajax_request_type;
			
			if(method != "POST")
				method = "GET";
			
			var i, x, n;
			var uri;
			var post_data;
			var target_id = sajax_target_id;
			
			sajax_debug("in sajax_do_call().." + method + "/" + sajax_target_id);
			uri = <?php echo(empty($sajax_remote_uri) ? 'window.location.href.replace(/#.*$/, "")' : '"'.$sajax_remote_uri.'"'); ?>;
			
			var geturi = "";
			
			if (method == "GET") {
				geturi = uri;
				if (geturi.indexOf("?") == -1) 
					geturi += "?rs=" + encodeURIComponent(func_name);
				else
					geturi += "&rs=" + encodeURIComponent(func_name);
				
				for (i = 0; i < args.length-1; i++) 
					geturi += "&rsargs[]=" + encodeURIComponent(serialize(args[i]));

				if(geturi.length > 512){
					method = "POST";
					sajax_debug("Data to long for GET switching to POST");
				} else {
					uri = geturi;
					post_data = null;
				}
			}
            
			if (method == "POST") {
				post_data = "rs=" + encodeURIComponent(func_name);
				
				for(i = 0; i < args.length-1; i++) {
					post_data = post_data + "&rsargs[]=" + encodeURIComponent(serialize(args[i]));
				}
			}
			
			x = new XMLHttpRequest();
			if(x == null) {
				//TODO support iframe ajaxing
				//document.getElementsByTagName("pre")[0].innerHTML
				if(sajax_failure_redirect != "") {
					window.location.href = sajax_failure_redirect;
					return false;
				} else {
					sajax_debug("NULL sajax object for user agent:\n" + navigator.userAgent);
					return false;
				}
			}
			
			x.open(method, uri, asynchronous);
			// window.open(uri);
			
			if (method == "POST") {
				x.setRequestHeader("Method", "POST " + uri + " HTTP/1.1");
				x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			}
			
			var responcefunc = function() {
				if (x.readyState != 4) 
					return false;
				
				var status;
				var data;
				var txt = x.responseText.replace(/^\s*|\s*$/g,"");
				status = txt.charAt(0);
				if(status == "-" || status == "+")
					data = txt.substring(2);
				else
					data = txt;
	
				if(status == "" && (x.status == 200 || x.status == "")) {
					// let's just assume this is a pre-response bailout and let it slide for now
					return false;
				} else if(status != "+" || x.status != 200) {
					//IE ignore faling to do POST, a GET attempt will be made
					if(x.status =! 12019)
						alert("Error " + x.status + ": " + data);
					return false;
				} else {
					try {
						var callback;
						var extra_data = false;
						if (typeof args[args.length-1] == "object") {
							callback = args[args.length-1].callback;
							extra_data = args[args.length-1].extra_data;
						} else {
							callback = args[args.length-1];
						}
						if(typeof(JSON) != "undefined" && typeof(JSON.parse) != "undefined")
							res = JSON.parse(data);
						else {
							eval("var res = "+data+"; res;");
						 }
						if(target_id) {
							document.getElementById(target_id).innerHTML = res;
						} else {
							callback(res, extra_data);
						}
					} catch(e) {
						sajax_debug("Caught error " + e + ": Could not parse " + data );
						return false;
					}
				}
				return true;
			}
			if(asynchronous) {
				x.onreadystatechange = responcefunc;
			}
			
			sajax_debug(func_name + " uri = " + uri + "/post = " + post_data);
			try {
				x.send(post_data);
			}
			catch(e) {
				if(method == "POST" && geturi == "") {
					sajax_debug("Browser did not support POST, tyring GET instead");
                    sajax_request_type = "";
					return sajax_do_call(func_name, args, "GET", asynchronous);
				} else  {
					delete x;
					alert("Browser not supported");
					return false;
				}
			}
			sajax_debug(func_name + " waiting..");
			
			if(asynchronous) {
				var id = sajax_requests.length;
				sajax_requests[id] = x;
				delete x;
				return id;
			} else {
				return responcefunc();
			}
		}
		
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	function sajax_export() {
		global $sajax_export_list;
		
		$n = func_num_args();
		for ($i = 0; $i < $n; $i++) {
			$sajax_export_list[] = func_get_arg($i);
		}
	}
	
	$sajax_js_has_been_shown = 0;
	function sajax_show_javascript()
	{
		global $sajax_request_type;
		global $sajax_js_has_been_shown;
		
		if (! $sajax_js_has_been_shown) {
			$html = sajax_get_common_js();
			
			global $sajax_export_list;
			foreach($sajax_export_list as $function) {
				if(!is_array($function))
					$function = array('name' => $function);
				
				if(!isset($function['asynchronous']))
					$function['asynchronous'] = true;
				
				if(!isset($function['method']))
					$function['method'] = $sajax_request_type;
				
				$html .= '
				function x_'.$function['name'].'() {
					return sajax_do_call("'.$function['name'].'", arguments, "'.strtoupper($function['method']).'", '.($function['asynchronous'] ? 'true' : 'false').');
				}
				';
			}
		echo $html;
		}
		
	}

	
	$SAJAX_INCLUDED = 1;
}
?>
