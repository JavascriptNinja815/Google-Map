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

$grab_timeoff_today = $db->query("
	SELECT
		logins.initials,
		logins.first_name,
		logins.last_name,
		timesheets.from_datetime,
		timesheets.to_datetime,
		timesheets.notes,
		timesheet_types.name AS reason
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	INNER JOIN
		" . DB_SCHEMA_ERP . ".timesheet_types
		ON
		timesheet_types.timesheet_type_id = timesheets.timesheet_type_id
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = timesheets.login_id
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".login_companies
		ON
		login_companies.login_id = logins.login_id
	WHERE
		login_companies.company_id = " . $db->quote(COMPANY) . "
		AND
		timesheets.status = 1
		AND
		timesheets.from_datetime = GETDATE()
		AND
		" . $where . "
");
$timeoff_today = [];
foreach($grab_timeoff_today as $timeoff) {
	$timeoff_today[] = [
		'initials' => $timeoff['initials'],
		'first_name' => $timeoff['first_name'],
		'last_name' => $timeoff['last_name'],
		'from' => $timeoff['from_datetime'],
		'to' => $timeoff['to_datetime'],
		'reason' => $timeoff['reason'],
		'notes' => $timeoff['notes']
	];
}

print json_encode([
	'success' => True,
	'timeoff' => $timeoff_today
]);
