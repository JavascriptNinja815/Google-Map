<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

$errors = array();

if(empty($_POST['login'])) {
	$errors['login'] = 'Login is required';
}
if(empty($_POST['password'])) {
	$errors['password'] = 'Password is required';
}
if(empty($_POST['first_name'])) {
	$errors['first_name'] = 'First Name is required';
}
if(empty($_POST['initials'])) {
	$errors['initials'] = 'Initials are required';
}

if(empty($errors)) {
	$grab_existing_login = $db->query("
		SELECT
			login_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".logins
		WHERE
			login = " . $db->quote($_POST['login']) . "
	");
	$existing_login = $grab_existing_login->fetch();
	if($existing_login) {
		$errors['login'] = 'Login specified already exists';
	}
}

if(!empty($errors)) {
	print json_encode(array(
		'success' => False,
		'errors' => $errors
	));
} else {
	$password_salt = Misc::RandomString(64);
	$password_hashed = hash(HASHING_ALGO, $password_salt . $_POST['password']);

	$create_login = $db->query("
		INSERT INTO
			" . DB_SCHEMA_INTERNAL . ".logins
		(
			login,
			password_hashed,
			password_salt,
			first_name,
			last_name,
			initials
		)
		OUTPUT
			INSERTED.login_id
		VALUES (
			" . $db->quote($_POST['login']) . ",
			" . $db->quote($password_hashed) . ",
			" . $db->quote($password_salt) . ",
			" . $db->quote($_POST['first_name']) . ",
			" . $db->quote($_POST['last_name']) . ",
			" . $db->quote($_POST['initials']) . "
		)
	");
	$created_login = $create_login->fetch();

	if(!empty($_POST['companies'])) {
		foreach($_POST['companies'] as $company_id) {
			$db->query("
				INSERT INTO
					" . DB_SCHEMA_INTERNAL . ".login_companies
				(
					login_id,
					company_id
				) VALUES (
					'" . $created_login['login_id'] . "',
					" . $db->quote($company_id) . "
				)
			");
		}
	}

	print json_encode(array(
		'success' => True,
		'login_id' => $created_login['login_id']
	));
}
