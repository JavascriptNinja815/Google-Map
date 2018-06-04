<?php

function validate_request(){

	// Is the request valid?
	$valid = true;

	// Make sure everything is set properly.
	if(!isset($_POST['issue_id'], $_POST['login_id'], $_POST['comment'])) {
		$valid = false;
	}

	return $valid;

}

function add_comment(){

	// Add the comment in SQL Server.

	// Get the details for the comment.
	$issue_id = $_POST['issue_id'];
	$login_id = $_POST['login_id'];
	$comment = $_POST['comment'];

	// Insert only new comments.
	$db = DB::get();
	$q = $db->query("
		INSERT INTO Neuron.dbo.order_issues_comments (
			issue_id, login_id, comment
		)
		OUTPUT INSERTED.comment_id, INSERTED.created_on
		SELECT
			".$db->quote($issue_id).",
			".$db->quote($login_id).",
			".$db->quote($comment)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.order_issues_comments
			WHERE issue_id = ".$db->quote($issue_id)."
				AND login_id = ".$db->quote($login_id)."
				AND CAST(comment AS varchar) = ".$db->quote($comment)."
		)
	");

	return $q->fetch();

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

// Insert the comment.
$response = add_comment();

print json_encode(array(
	'success' => true,
	'comment_id' => $response['comment_id'],
	'created_on' => $response['created_on']
));