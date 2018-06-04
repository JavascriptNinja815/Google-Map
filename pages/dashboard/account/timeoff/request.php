<?php

if(empty($_POST['date'])) {
	print json_encode([
		'success' => False,
		'message' => 'Date is required'
	]);
	exit;
}

if(!empty($_POST['allday'])) {
	// All day
	$from_datetime = new DateTime(date('Y-m-d 8:00:00', strtotime($_POST['date'])));
	$from_datetime = $db->quote(
		$from_datetime->format('Y-m-d H:i:s')
	);
	$to_datetime = 'NULL';
} else {
	// Specific hours
	$from_datetime = new DateTime(date('Y-m-d ' . $_POST['from-hour'] . ':00:00', strtotime($_POST['date'])));
	$from_datetime = $db->quote(
		$from_datetime->format('Y-m-d H:i:s')
	);
	$to_datetime = new DateTime(date('Y-m-d ' . $_POST['to-hour'] . ':00:00', strtotime($_POST['date'])));
	$to_datetime = $db->quote(
		$to_datetime->format('Y-m-d H:i:s')
	);
}

// Ensure date has not already been requested off.
$grab_existing = $db->query("
	SELECT
		timesheets.timesheet_id
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	WHERE
		timesheets.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		timesheets.from_datetime = " . $from_datetime . "
");
if($grab_existing->rowCount()) {
	print json_encode([
		'success' => False,
		'message' => 'The date specified has already been requested off'
	]);
	exit;
}

$notes = 'Entered by ' . $session->login['initials'] . ' on ' .  date('Y-m-d g:i A');
if(!empty($_POST['notes'])) {
	$notes .= "\r\n\r\n" . $session->login['initials'] . ' on ' .  date('Y-m-d g:i A') . ': ' . $_POST['notes'];
}

$db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".timesheets
	(
		login_id,
		entered_login_id,
		entered_date,
		timesheet_type_id,
		from_datetime,
		to_datetime,
		status,
		notes
	) VALUES (
		" . $db->quote($session->login['login_id']) . ",
		" . $db->quote($session->login['login_id']) . ",
		GETDATE(),
		" . $db->quote($_POST['reason']) . ",
		" . $from_datetime . ",
		" . $to_datetime . ",
		0,
		" . $db->quote($notes) . "
	)
");

print json_encode([
	'success' => True
]);
