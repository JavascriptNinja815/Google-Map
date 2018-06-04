<?php

if($session->hasRole('Administration')) {
	$where = " 1 = 1 "; // Returns all.
} else if($session->hasRole('Supervisor')) {
	$where = "logins.initials IN ('" . implode(
		"','",
		$session->getPermissions('Supervisor', 'timesheets')
	) . "')";
} else {
	print json_encode([
		'success' => False,
		'message' => 'You do not have permission to be accessing this resource'
	]);
	exit;
}

if(empty($_POST['notes'])) {
	print json_encode([
		'success' => False,
		'message' => 'Notes cannot be empty'
	]);
	exit;
}

// Grab existing notes.
$grab_existing_notes = $db->query("
	SELECT
		timesheets.notes
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = timesheets.login_id
	WHERE
		timesheets.timesheet_id = " . $db->quote($_POST['timesheet_id']) . "
		AND
		" . $where . "
");
$existing_notes = $grab_existing_notes->fetch();
$existing_notes = $existing_notes['notes'];

$new_note = $session->login['initials'] . ' on ' . date('Y-m-d', time()) . ': ' . $_POST['notes'];
$notes = $existing_notes . "\r\n\r\n" . $new_note;

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".timesheets
	SET
		notes = " . $db->quote($notes) . "
	FROM
		" . DB_SCHEMA_INTERNAL . ".logins
	WHERE
		logins.login_id = timesheets.login_id
		AND
		timesheets.timesheet_id = " . $db->quote($_POST['timesheet_id']) . "
		AND
		" . $where . "
");

print json_encode([
	'success' => True,
	'note' => $new_note
]);
