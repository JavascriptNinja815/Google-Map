<?php

function validate_request(){

	// Is the request valid?
	$valid = true;

	// Make sure everything is set properly.
	if(!isset($_POST['issue_id'])) {
		$valid = false;
	}

	return $valid;

}

function get_comments(){

	// Look up the comments for a particular issue.

	// The issue ID for the comments.
	$issue_id = $_POST['issue_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT
			c.comment_id,
			l.first_name,
			c.comment,
			c.created_on
		FROM Neuron.dbo.order_issues_comments c
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = c.login_id
		WHERE c.issue_id = ".$db->quote($issue_id)."
		ORDER BY c.created_on
	");

	return $q->fetchAll();

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

// Get the comments.
$comments = get_comments();

print json_encode(array(
	'success' => true,
	'comments' => $comments
));