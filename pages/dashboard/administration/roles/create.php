<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

$errors = array();

if(empty($_POST['role'])) {
	$errors['role'] = 'Role is required';
}

if(empty($errors)) {
	$grab_existing_role = $db->query("
		SELECT
			role_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".roles
		WHERE
			role = " . $db->quote($_POST['role']) . "
	");
	$existing_role = $grab_existing_role->fetch();
	if($existing_role) {
		$errors['role'] = 'Role specified already exists';
	}
}

if(!empty($errors)) {
	print json_encode(array(
		'success' => False,
		'errors' => $errors
	));
} else {
	$create_role = $db->query("
		INSERT INTO
			" . DB_SCHEMA_INTERNAL . ".roles
		(
			role
		)
		OUTPUT
			INSERTED.role_id
		VALUES (
			" . $db->quote($_POST['role']) . "
		)
	");
	$created_role = $create_role->fetch();

	print json_encode(array(
		'success' => True,
		'role_id' => $created_role['role_id']
	));
}
