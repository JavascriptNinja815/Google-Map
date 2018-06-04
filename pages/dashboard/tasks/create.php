<?php

$session->ensureLogin();

// Ensure login_id exists and is assignable.
$grab_assigned_login = $db->query("
	SELECT
		logins.login_id
	FROM
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
	WHERE
		" . (ERP_SYSTEM === 'PRO' ? Null : "logins.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
		logins.login_id = " . $db->quote($_POST['assigned_login_id']) . "
");
$assigned_login = $grab_assigned_login->fetch();
if(empty($assigned_login)) {
	print json_encode([
		'success' => False,
		'message' => 'Assigned To value is invalid'
	]);
	exit;
}

$grab_task = $db->query("
	INSERT INTO
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".tasks
	(
		account_id,
		added_on,
		due_on,
		assignedby_login_id,
		subject,
		description,
		status,
		priority
	)
	" . (ERP_SYSTEM === 'PRO' ? 'OUTPUT INSERTED.task_id' : Null) . "
	VALUES (
		" . $db->quote(ACCOUNT_ID) . ",
		" . (ERP_SYSTEM === 'PRO' ? 'GETDATE()' : 'NOW()') . ",
		" . $db->quote($_POST['duedate']) . ",
		" . $db->quote($session->login['login_id']) . ",
		" . $db->quote($_POST['subject']) . ",
		" . $db->quote($_POST['description']) . ",
		" . $db->quote($_POST['status']) . ",
		" . $db->quote($_POST['priority']) . "
	)
	" . (ERP_SYSTEM === 'PRO' ? Null : 'RETURNING tasks.task_id') . "
");
$task = $grab_task->fetch();

$db->query("
	INSERT INTO
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_assignees
	(
		task_id,
		login_id,
		added_on,
		assignedby_login_id
	) VALUES (
		" . $db->quote($task['task_id']) . ",
		" . $db->quote($_POST['assigned_login_id']) . ",
		" . (ERP_SYSTEM === 'PRO' ? 'GETDATE()' : 'NOW()') . ",
		" . $db->quote($session->login['login_id']) . "
	)
");

print json_encode([
	'success' => True,
	'task' => $task
]);
