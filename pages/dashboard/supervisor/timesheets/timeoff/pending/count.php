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

$grab_timeoff_count = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
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
		timesheets.status = 0
		AND
		" . $where . "
");
$timeoff_count = $grab_timeoff_count->fetch();

print json_encode([
	'success' => True,
	'count' => $timeoff_count['count']
]);
