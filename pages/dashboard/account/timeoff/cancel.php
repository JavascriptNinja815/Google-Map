<?php

$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".timesheets
	WHERE
		timesheets.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		timesheets.timesheet_id = " . $db->quote($_GET['timesheet_id']) . "
");

header('Location: ' . BASE_URI . '/dashboard/account/timeoff');
