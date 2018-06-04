<?php

function validate_request(){

	// Is the request valid?
	$valid = true;

	// Make sure everything is set properly.
	if(!isset($_POST['action'],$_POST['issue_id'],$_POST['login_id'],$_POST['details'])) {
		$valid = false;
	}

	return $valid;

}

function update_issue(){

	// Update an existing issue.

	// Get the update values.
	$issue_id = $_POST['issue_id'];
	$login_id = $_POST['login_id'];
	$details = $_POST['details'];

	// Update the issue.
	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.order_issues
		SET details = N".$db->quote($details).",
			updated_on = GETDATE(),
			updated_by = ".$db->quote($login_id)."
		WHERE issue_id = ".$issue_id."
	");

}

// Make sure the request is valid.
$valid = validate_request();

// Return an error for invalid requests.
if(!$valid){
	print json_encode(array(
		'success' => false,
		'message' => 'Invalid Request'
	));
	exit();
}

// Make sure the user is an administrator.
if(!$session->hasRole('Administration')) {
	print json_encode(array(
		'success' => False,
		'message' => 'You do not have permission to perform this action'
	));
	exit();
}

// Update the issue.
update_issue();

print json_encode(array(
	'success' => true
));