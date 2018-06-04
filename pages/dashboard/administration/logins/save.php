<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

/**
 * This script is included by edit.php upon form submission.
 */

if($_POST['login_id'] == $session->login['login_id']) {
	$errors['login'] = 'You cannot administrate your own Login';
}

$session->ensureLogin();
$session->ensureRole('Administration');

$errors = array();

if(empty($_POST['login'])) {
	$errors['login'] = 'Login is required';
}
if(empty($_POST['first_name'])) {
	$errors['first_name'] = 'First Name is required';
}
if(empty($_POST['initials'])) {
	$errors['initials'] = 'Initials are required';
}

if(empty($errors)) {
	// Save Password
	if(!empty($_POST['password'])) {
		$password_salt = Misc::RandomString(64);
		$password_hashed = hash(HASHING_ALGO, $password_salt . $_POST['password']);
		$db->query("
			UPDATE
				" . DB_SCHEMA_INTERNAL . ".logins
			SET
				password_hashed = " . $db->quote($password_hashed) . ",
				password_salt = " . $db->quote($password_salt) . "
			WHERE
				logins.login_id = " . $db->quote($_POST['login_id']) . "
		");
	}

	// Save Roles and Permissions
	if(empty($_POST['roles'])) {
		$_POST['roles'] = array();
	}

	// Check if any role is specified, but no permissions are... In which case
	// we need to delete any permissions associated with that role because
	// there shouldn't be any lingering in the DB.
	foreach($_POST['roles'] as $role_id) {
		if(!isset($_POST['permissions'][$role_id])) {
			$db->query("
				DELETE FROM
					" . DB_SCHEMA_INTERNAL . ".login_role_permissions
				WHERE
					login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
					AND
					login_role_permissions.role_id = " . $db->quote($role_id) . "
			");
		}
	}

	// Grab current roles applied to this Login.
	$grab_role_ids = $db->query("
		SELECT
			login_roles.role_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".login_roles
		WHERE
			login_roles.login_id = " . $db->quote($_POST['login_id']) . "
	");
	$existing_role_ids = array();
	foreach($grab_role_ids as $role_id) {
		$existing_role_ids[] = $role_id['role_id'];
	}

	// Add roles to this Login.
	foreach(array_diff($_POST['roles'], $existing_role_ids) as $role_id) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".login_roles
			(
				login_id,
				role_id
			) VALUES (
				" . $db->quote($_POST['login_id']) . ",
				" . $db->quote($role_id) . "
			)
		");
	}

	// Remove roles from this Login.
	foreach(array_diff($existing_role_ids, $_POST['roles']) as $role_id) {
		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".login_role_permissions
			WHERE
				login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
				AND
				login_role_permissions.role_id = " . $db->quote($role_id) . "
		");
		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".login_roles
			WHERE
				login_roles.login_id = " . $db->quote($_POST['login_id']) . "
				AND
				login_roles.role_id = " . $db->quote($role_id) . "
		");
	}

	/**
	 * Save Permissions
	 */
	if(empty($_POST['permissions'])) {
		$_POST['permissions'] = array();
	}
	foreach($_POST['permissions'] as $role_id => $permissions) {
		// Skip permissions if the associated role_id was not passed,
		// and make sure there are no lingering permissions in the DB.
		if(!in_array($role_id, $_POST['roles'])) {
			$db->query("
				DELETE FROM
					" . DB_SCHEMA_INTERNAL . ".login_role_permissions
				WHERE
					login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
					AND
					login_role_permissions.role_id = " . $db->quote($role_id) . "
			");
			continue;
		}

		// Grab permission types currently applied to this Login.
		$grab_permission_types = $db->query("
			SELECT
				DISTINCT login_role_permissions.permission_type
			FROM
				" . DB_SCHEMA_INTERNAL . ".login_role_permissions
			WHERE
					login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
					AND
					login_role_permissions.role_id = " . $db->quote($role_id) . "
		");
		foreach($grab_permission_types as $permission_type) {
			// If currently applied permission type is not present in
			// data submitted by the form, delete all permissions
			// associated with that permission type from the DB.
			if(!isset($permissions[$permission_type['permission_type']])) {
				$db->query("
					DELETE FROM
						" . DB_SCHEMA_INTERNAL . ".login_role_permissions
					WHERE
						login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
						AND
						login_role_permissions.role_id = " . $db->quote($role_id) . "
						AND
						login_role_permissions.permission_type = " . $db->quote($permission_type['permission_type']) . "
				");
			}
		}

		// Iterate over permission types submmitted.
		foreach($permissions as $permission_type => $permission_values) {
			// Retrieve all permission values currently applied to this login.
			$grab_permission_values = $db->query("
				SELECT
					login_role_permissions.permission_value
				FROM
					" . DB_SCHEMA_INTERNAL . ".login_role_permissions
				WHERE
					login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
					AND
					login_role_permissions.role_id = " . $db->quote($role_id) . "
					AND
					login_role_permissions.permission_type = " . $db->quote($permission_type) . "
			");
			$existing_permission_values = array();
			foreach($grab_permission_values as $permission_value) {
				$existing_permission_values[] = $permission_value['permission_value'];
			}

			// Add permissions to this permission type.
			foreach(array_diff($permission_values, $existing_permission_values) as $permission_value) {
				$db->query("
					INSERT INTO
						" . DB_SCHEMA_INTERNAL . ".login_role_permissions
					(
						role_id,
						login_id,
						permission_type,
						permission_value
					) VALUES (
						" . $db->quote($role_id) . ",
						" . $db->quote($_POST['login_id']) . ",
						" . $db->quote($permission_type) . ",
						" . $db->quote($permission_value) . "
					)
				");
			}

			// Remove permissions from this permission type.
			foreach(array_diff($existing_permission_values, $permission_values) as $permission_value) {
				$db->query("
					DELETE FROM
						" . DB_SCHEMA_INTERNAL . ".login_role_permissions
					WHERE
						login_role_permissions.role_id = " . $db->quote($role_id) . "
						AND
						login_role_permissions.login_id = " . $db->quote($_POST['login_id']) . "
						AND
						login_role_permissions.permission_type = " . $db->quote($permission_type) . "
						AND
						login_role_permissions.permission_value = " . $db->quote($permission_value) . "
				");
			}
		}
	}

	// Save General Information
	$birthday = !empty($_POST['birthday']) ? $db->quote($_POST['birthday']) : 'NULL';
	$hire_date = !empty($_POST['hire_date']) ? $db->quote($_POST['hire_date']) : 'NULL';

	$db->query("
		UPDATE
			" . DB_SCHEMA_INTERNAL . ".logins
		SET
			login = " . $db->quote($_POST['login']) . ",
			first_name = " . $db->quote($_POST['first_name']) . ",
			last_name = " . $db->quote($_POST['last_name']) . ",
			initials = " . $db->quote($_POST['initials']) . ",
			email_password = " . $db->quote($_POST['email_password']) . ",
			status = " . $db->quote($_POST['status']) . ",
			birthday = " . $birthday . ",
			hire_date = " . $hire_date . ",
			avail_sick_hours = " . $db->quote($_POST['avail_sick_hours']) . ",
			avail_vacation_hours = " . $db->quote($_POST['avail_vacation_hours']) . ",
			label_printer_id = " . $db->quote($_POST['label_printer_id']) . "
		WHERE
			logins.login_id = " . $db->quote($_POST['login_id']) . "
	");

	/**
	 * Save relationships to companies.
	 */
	$grab_companies = $db->query("
		SELECT
			login_companies.company_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".login_companies
		WHERE
			login_companies.login_id = " . $db->quote($_POST['login_id']) . "
	");
	$current_companies = array();
	foreach($grab_companies as $company) {
		$current_companies[] = $company['company_id'];
	}

	if(!isset($_POST['companies'])) {
		$_POST['companies'] = array();
	}
	$companies_to_remove = array_diff($current_companies, $_POST['companies']);
	$companies_to_add = array_diff($_POST['companies'], $current_companies);

	foreach($companies_to_remove as $company_id) {
		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".login_companies
			WHERE
				login_companies.login_id = " . $db->quote($_POST['login_id']) . "
				AND
				login_companies.company_id = " . $db->quote($company_id) . "
		");
	}
	foreach($companies_to_add as $company_id) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".login_companies
			(
				login_id,
				company_id
			) VALUES (
				" . $db->quote($_POST['login_id']) . ",
				" . $db->quote($company_id) . "
			)
		");
	}
	
	/**
	 * Save relationships to locations.
	 */
	$grab_companies = $db->query("
		SELECT
			companies.company_id,
			companies.dbname
		FROM
			" . DB_SCHEMA_INTERNAL . ".companies
		ORDER BY
			companies.company
	");
	foreach($grab_companies as $company) {
		$grab_locations = $db->query("
			SELECT
				LTRIM(RTRIM(somast.defloc)) AS defloc
			FROM
				" . $company['dbname'] . ".somast
			WHERE
				somast.defloc != 'DROPSH'
			GROUP BY
				somast.defloc
			ORDER BY
				somast.defloc
		");
		foreach($grab_locations as $location) {
			$grab_location_entry = $db->query("
				SELECT
					login_locations.entry_id
				FROM
					" . DB_SCHEMA_INTERNAL . ".login_locations
				WHERE
					login_locations.login_id = " . $db->quote($_POST['login_id']) . "
					AND
					login_locations.company_id = " . $db->quote($company['company_id']) . "
					AND
					login_locations.location = " . $db->quote($location['defloc']) . "
			");
			$location_entry = $grab_location_entry->fetch();
			if(!empty($_POST['locations'][$company['company_id']]) && in_array($location['defloc'], $_POST['locations'][$company['company_id']])) {
				if(empty($location_entry)) {
					// Add location entry that has been newly specified.
					$db->query("
						INSERT INTO
							" . DB_SCHEMA_INTERNAL . ".login_locations
						(
							login_id,
							company_id,
							location
						) VALUES (
							" . $db->quote($_POST['login_id']) . ",
							" . $db->quote($company['company_id']) . ",
							" . $db->quote($location['defloc']) . "
						)
					");
				}
			} else {
				if(!empty($location_entry)) {
					// Delete existing location that is no longer specified.
					$db->query("
						DELETE FROM
							" . DB_SCHEMA_INTERNAL . ".login_locations
						WHERE
							login_id = " . $db->quote($_POST['login_id']) . "
							AND
							company_id = " . $db->quote($company['company_id']) . "
							AND
							location = " . $db->quote($location['defloc']) . "
					");
				}
			}
		}
	}

	print json_encode(array(
		'success' => True
	));
} else {
	print json_encode(array(
		'success' => False,
		'errors' => $errors
	));
}
