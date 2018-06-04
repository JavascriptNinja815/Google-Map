<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$create_goal = $db->query("
	INSERT INTO
		" . DB_SCHEMA_INTERNAL . ".goals
	(
		login_id,
		start_date,
		end_date,
		goal,
		title,
		type
	)
	OUTPUT INSERTED.goal_id
	VALUES (
		" . $db->quote($_POST['login_id']) . ",
		" . $db->quote($_POST['start']) . ",
		" . $db->quote($_POST['end']) . ",
		" . $db->quote($_POST['amount']) . ",
		" . $db->quote($_POST['title']) . ",
		" . $db->quote($_POST['type']) . "
	)
");
$goal = $create_goal->fetch();

print json_encode(array(
	'success' => True,
	'goal_id' => $goal['goal_id']
));
