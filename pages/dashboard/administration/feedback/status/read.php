<?php

$read_on = date('Y-m-d H:i:s');
$read_by = $session->login['login_id'];
$db->query("
	UPDATE
		" . DB_SCHEMA_INTERNAL . ".feedback
	SET
		readby_login_id = " . $db->quote($read_by) . ",
		read_on = GETDATE()
	WHERE
		feedback.feedback_id = " . $db->quote($_POST['feedback_id']) . "
");

print json_encode([
	'success' => True,
	'read_on' => $read_on,
	'read_by' => $read_by
]);
