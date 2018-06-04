<?php

/**
 * This script is accessed via an AJAX request through the dashboard.
 * It is present in this folder because it is Sales Order-specific.
 */

$session->ensureLogin();
$session->ensureRole('Sales');

if(empty($_POST['salesman'])) {
	$result = array(
		'success' => False,
		'message' => '"salesman" must be specified.'
	);
} else if(!$session->hasRole('Admin') && !$session->hasPermission('Sales', 'view-orders', $_POST['salesman'])) {
	$result = array(
		'success' => False,
		'message' => 'User doesn\'t have permission to access this salesman'
	);
} else {
	$result = array(
		'success' => True
	);
}

print json_encode($result);
