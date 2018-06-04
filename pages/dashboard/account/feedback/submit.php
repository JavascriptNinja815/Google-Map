<?php

function assign_feedback(){

	// Assign the feedback to a user and record resolution.

	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.feedback
		SET
			resolution_owner = ".$db->quote($_POST['user']).",
			projected_completion_date = ".$db->quote($_POST['resolution-date'])."
		WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
	");

}

function kill_feedback(){

	// Mark the feedback as killed.
	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET killed = 1,
			resolution = ".$db->quote($_POST['resolution'])."
		WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
	");

}

function complete_feedback(){

	// Mark the feedback as completed.
	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET completed_on = GETDATE(),
			resolution = ".$db->quote($_POST['resolution'])."
		WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
	");

}

// Handle submissions.
if(isset($_POST['action'])){

	// Handle feedback assignments.
	if($_POST['action'] == 'assign'){
		assign_feedback();
	}

	// Handle killing feedback.
	if($_POST['action'] == 'kill'){
		kill_feedback();
	}

	// Handle completing feedback.
	if($_POST['action'] == 'complete'){
		complete_feedback();
	}

}

print json_encode(array(
	'success' => true
));