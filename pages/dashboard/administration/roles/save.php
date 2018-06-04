<?php

ini_set('display_errors', True);
error_reporting(E_ALL | E_STRICT);

$session->ensureLogin();
$session->ensureRole('Administration');

$errors = array();

// Ensure role name is not empty, and does not conflict with any other role name.
if(empty($_POST['role'])) {
	$errors['role'] = 'Role name must be specified';
} else {
	$grab_conflicting_role_name = $db->prepare("
		SELECT
			roles.role_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".roles
		WHERE
			roles.role = " . $db->quote($_POST['role']) . "
			AND
			roles.role_id != " . $db->quote($_POST['role_id']) . "
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$grab_conflicting_role_name->execute();
	if($grab_conflicting_role_name->rowCount()) {
		$errors['role'] = 'Role with name specified already exists';
	}
}

// If no errors encountered, perform the actions.
if(empty($errors)) {
	/**
	 * Update role.
	 */
	$db->query("
		UPDATE
			" . DB_SCHEMA_INTERNAL . ".roles
		SET
			role = " . $db->quote($_POST['role']) . ",
			permission_type = " . $db->quote($_POST['permission-type']) . "
		WHERE
			role_id = " . $db->quote($_POST['role_id']) . "
	");

	/**
	 * Grab existing role permissions.
	 */
	$grab_permissions = $db->query("
		SELECT
			role_permissions.permission
		FROM
			" . DB_SCHEMA_INTERNAL . ".role_permissions
	");
	$existing_permissions = array();
	foreach($grab_permissions as $permission) {
		$existing_permissions[] = trim($permission['permission']);
	}

	if(empty($_POST['static-permissions'])) {
		$static_permissions = array();
	} else {
		$static_permissions = $_POST['static-permissions'];
	}

	/**
	 * Determine permissions which need to be removed, and remove them.
	 */
	$delete_permissions = array_diff($existing_permissions, $static_permissions);
	if(!empty($delete_permissions)) {
		// Sanitize permissions to delete to prevent breaking / SQL onjection
		$delete_permissions_sanitized = array();
		foreach($delete_permissions as $delete_permission) {
			$delete_permissions_sanitized[] = $db->quote($delete_permission);
		}
		// Delete the permissions in question.
		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".role_permissions
			WHERE
				role_id = " . $db->quote($_POST['role_id']) . "
				AND
				permission IN (" . implode(",", $delete_permissions_sanitized) . ")
		");
	}

	/**
	 * Determine permissions which need to be added, and add them.
	 */
	$add_permissions = array_diff($static_permissions, $existing_permissions);
	if(!empty($add_permissions)) {
		foreach($add_permissions as $add_permission) {
			$db->query("
				INSERT INTO
					" . DB_SCHEMA_INTERNAL . ".role_permissions
				(
					permission,
					role_id
				) VALUES (
					" . $db->quote($add_permission) . ",
					" . $db->quote($_POST['role_id']) . "
				)
			");
		}
	}

	/**
	 * Determine whether dynamic permission needs to be added, updated or
	 * removed and perform the appropriate action.
	 */
	$grab_query = $db->prepare("
		SELECT
			permission_query_id,
			query
		FROM
			" . DB_SCHEMA_INTERNAL . ".role_permission_queries
		WHERE
			role_id = " . $db->quote($_POST['role_id']) . "
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$grab_query->execute();

	if(!empty($_POST['dynamic-permission-query'])) {
		if($grab_query->rowCount()) {
			// Record exists, update it.
			$db->query("
				UPDATE
					" . DB_SCHEMA_INTERNAL . ".role_permission_queries
				SET
					query = " . $db->quote($_POST['dynamic-permission-query']) . "
				WHERE
					role_id = " . $db->quote($_POST['role_id']) . "
			");
		} else {
			// Record doesn't exist, create it.
			$db->query("
				INSERT INTO
					" . DB_SCHEMA_INTERNAL . ".role_permission_queries
				(
					role_id,
					query
				) VALUES (
					" . $db->quote($_POST['role_id']) . ",
					" . $db->quote($_POST['dynamic-permission-query']) . "
				)
			");
		}
	} else {
		// Query is empty, delete the entr if it exists.
		if($grab_query->rowCount()) {
			$db->query("
				DELETE FROM
					" . DB_SCHEMA_INTERNAL . ".role_permission_queries
				WHERE
					role_id = " . $db->quote($_POST['role_id']) . "
			");
		}
	}

	// Set the response's "success" to True.
	$result = array(
		'success' => True
	);
} else {
	$result = array(
		'success' => False,
		'errors' => $errors
	);
}

print json_encode($result);
