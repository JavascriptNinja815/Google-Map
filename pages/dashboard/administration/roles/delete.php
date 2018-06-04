<?php

$session->ensureLogin();
$session->ensureRole('Administration');

/**
 * Delete Role Permissions.
 */
$db->query("
	DELETE FROM
		" . DB_SCHEMA_INTERNAL . ".role_permissions
	WHERE
		role_permissions.role_id = " . $db->quote($_POST['role_id']) . "
");

/**
 * Delete Role.
 */
$db->query("
	DELETE FROM
		" . DB_SCHEMA_INTERNAL . ".roles
	WHERE
		roles.role_id = " . $db->quote($_POST['role_id']) . "
");

print json_encode(array(
	'success' => True
));
