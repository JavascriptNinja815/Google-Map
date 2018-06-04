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

$notes = "\r\n\r\nRequest [DENIED] by " . $session->login['initials'] . ' on ' . date('Y-m-d g:i A');

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".timesheets
	SET
		status = -2,
		notes = CONVERT(NVARCHAR(MAX), notes) + " . $db->quote($notes) . "
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
	'success' => True
]);
