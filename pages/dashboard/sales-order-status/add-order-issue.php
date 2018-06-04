<?php

function validate_request(){

	// Is the request valid?
	$valid = true;

	// Make sure everything is set properly.
	if(!isset($_POST['action'],$_POST['sono'],$_POST['issue_code_id'],$_POST['details'])) {
		$valid = false;
	}

	return $valid;

}

function add_issue(){

	// Get the user values.
	global $session;
	$login = $session->login;
	$login_id = $login['login_id'];

	// Get the issue values.
	$sono = $_POST['sono'];
	$issue_code_id = $_POST['issue_code_id'];
	$details = $_POST['details'];

	// Insert.
	$db = DB::get();
	$q = $db->query("
		INSERT INTO Neuron.dbo.order_issues (
			login_id,
			sono,
			issue_code_id,
			details
		)
		OUTPUT INSERTED.issue_id, INSERTED.created_on
		SELECT
			".$db->quote($login_id).",
			".$db->quote($sono).",
			".$db->quote($issue_code_id).",
			N".$db->quote($details)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.order_issues
			WHERE login_id = ".$db->quote($login_id)."
				AND sono = ".$db->quote($sono)."
				AND issue_code_id = ".$db->quote($issue_code_id)."
				AND CAST(details AS varchar) = ".$db->quote($details)."
		)
	");

	$r = $q->fetch();
	return $r;

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

// Ensure login has "Sales" role.
if(!$session->hasRole('Sales')) {
	print json_encode(array(
		'success' => False,
		'message' => 'You do not have permission to perform this action'
	));
	exit();
}

// Create the issue.
$response = add_issue();

// Return success.
$login = $session->login;
$name = $login['first_name'];
print json_encode(array(
	'success' => true,
	'issue_id' => $response['issue_id'],
	'created_on' => $response['created_on'],
	'session' => json_encode($session),
	'name' => $name,
	'admin' => $session->hasRole("Administration")
));