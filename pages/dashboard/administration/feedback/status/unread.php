<?php

$db->query("
	UPDATE
		" . DB_SCHEMA_INTERNAL . ".feedback
	SET
		readby_login_id = NULL,
		read_on = NULL
	WHERE
		feedback.feedback_id = " . $db->quote($_POST['feedback_id']) . "
");

print json_encode([
	'success' => True
]);
