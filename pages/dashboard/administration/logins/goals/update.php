<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$db->query("
	UPDATE
		" . DB_SCHEMA_INTERNAL . ".goals
	SET
		start_date = " . $db->quote($_POST['start']) . ",
		end_date = " . $db->quote($_POST['end']) . ",
		goal = " . $db->quote($_POST['amount']) . ",
		title = " . $db->quote($_POST['title']) . ",
		type = " . $db->quote($_POST['type']) . "
	WHERE
		goal_id = " . $db->quote($_POST['goal_id']) . "
");

print json_encode(array(
	'success' => True
));
