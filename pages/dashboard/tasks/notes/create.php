<?php

$session->ensureLogin();

$where = '';
if(ERP_SYSTEM === 'PRO') {
	if($session->hasRole('Administration')) {
		$where = '';
	} else if($session->hasRole('Supervisor')) {
		$initials = [];
		foreach($session->getPermissions('Supervisor', 'timesheets') as $initial) {
			$initials[] = $db->quote($initial);
		}
		$initials = implode(', ', $initials);
		$where = "AND logins.initials IN (" . $initials . ")";
	} else {
		$where = 'AND 1 = 0'; // Return No One
	}
}

// Ensure user has access to this task..
$grab_task = $db->query("
	SELECT
		tasks.task_id
	FROM
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".tasks
	WHERE
		tasks.task_id = (
			SELECT DISTINCT
				task_assignees.task_id
			FROM
				" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_assignees
			INNER JOIN
				" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
				ON
				task_assignees.login_id = logins.login_id
			WHERE
				" . (ERP_SYSTEM === 'PRO' ? Null : "logins.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
				task_assignees.task_id = " . $db->quote($_POST['task_id']) . "
				" . ($where ? $where : Null) . " 
		)
");
$task = $grab_task->fetch();
if(!$task) {
	print json_encode([
		'success' => False,
		'message' => 'Invalid task specified'
	]);
	exit;
}

$db->query("
	INSERT INTO
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_entries
	(
		task_id,
		login_id,
		added_on,
		description
	) VALUES (
		" . $db->quote($task['task_id']) . ",
		" . $db->quote($session->login['login_id']) . ",
		" . (ERP_SYSTEM === 'PRO' ? 'GETDATE()' : 'NOW()') . ",
		" . $db->quote($_POST['note']) . "
	)
");

print json_encode([
	'success' => True,
	'note' => [
		'text' => str_replace("\r", '', str_replace("\n", '<br />', htmlentities($_POST['note']))),
		'whowhen' => (ERP_SYSTEM === 'PRO' ? $session->login['initials'] : $session->login['first_name'] . ' ' . $session->login['last_name']) . ' on ' . date('m/d/y h:i:sa')
	]
]);
