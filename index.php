<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

require_once('includes/bootstrap.php');

header('Content-Type: text/html; charset=UTF-8');

// Grab URL requested.
$page = explode('?', $_SERVER['REQUEST_URI']);

// Grab first element in array, removing the base URI from it.
$page = str_replace(BASE_URI, '', $page[0]);

// If last character is a slash, re-direct the user to the page which doesn't
// contain the final slash.
if(strlen($page) > 1 && substr($page, -1) == '/') {
	$page = substr($page, 0, strlen($page) - 1);
	header('Location: ' . $page);
}

// Dealing wit"h historical and canonical tags. Need to handle these in a more
// "dynamic" way in the future; perhaps through a URL registry which archives
// pages.
$canonicals = array(
	//'/welcome' => '/',
);

if(in_array($page, array_keys($canonicals))) {
	$page = $canonicals[$page];
	$canonical = $page;
} else {
	$canonical = False;
}

if($page == '/' || strlen($page) == 0) { // If no page is specified, default to "home".
	header('Location: /dashboard');
	exit;
}

$request = New HandleRequest($page, $canonical);
$request->Render();

// Give every page a red border if a supervisor/administrator is logged in as
// another user to make it obvious an alias session is being used.
if($session->alias){

	// If an admin has an alias session, do not add this CSS/JS to the request
	// response for AJAX calls.
	$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	if($is_ajax){
		return true;
	}

	?>
	<style type="text/css">
		#body {
			border: 5px solid red !important;
		}
		#alias-id-container {
			text-align: center;
			height: 10px;
			color: red;
		}
		#alias-id-container h3 {
			height: 10px;
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function(){

			function label_alias(){

				// For admins logged in as other users, add a way to identitfy
				// the user they're impersonating.

				var $label = $('<div/>',{
					id : "alias-id-container",
					class : "container",
					html : "<h3>User:"+'<?php print htmlentities($session->login['first_name'].' '.$session->login['last_name']) ?>'+"</h3>"
				})

				// Add the label to the HTML body.
				$('#body').prepend($label)

			}

			// Add an alias label.
			label_alias()

		})
	</script>
	<?php
}