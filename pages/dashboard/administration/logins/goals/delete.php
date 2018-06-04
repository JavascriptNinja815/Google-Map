<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$db->query("
	DELETE FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goal_id = " . $db->quote($_POST['goal_id']) . "
");

print json_encode(array(
	'success' => True
));
