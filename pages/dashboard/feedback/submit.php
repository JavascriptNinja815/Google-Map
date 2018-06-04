<?php

$db->query("
	INSERT INTO
		" . DB_SCHEMA_INTERNAL . ".feedback
	(
		login_id,
		company_id,
		topic,
		subject,
		memo,
		feedback_type_id
	) VALUES (
		" . $db->quote($session->login['login_id']) . ",
		" . $db->quote(COMPANY) . ",
		" . $db->quote($_POST['topic']) . ",
		" . $db->quote($_POST['subject']) . ",
		" . $db->quote($_POST['memo']) . ",
		".$db->quote($_POST['type'])."
	)
");

function add_tag($feedback_id, $tag_id){

	// Add an entry to the tagged table.

	$db = DB::get();
	$db->query("
		INSERT INTO Neuron.dbo.feedback_tagged (
			feedback_id, tag_id
		)
		SELECT
			".$db->quote($feedback_id).",
			".$db->quote($tag_id)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.feedback_tagged
			WHERE feedback_id = ".$db->quote($feedback_id)."
				AND tag_id = ".$db->quote($tag_id)."
		)
	");

};

function add_tags(){

	// Add each tag to the feedback.
	// TODO: Finish this when tags are supported.

}

print json_encode([
	'success' => True
]);
