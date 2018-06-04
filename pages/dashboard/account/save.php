<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$errors = array();

if(empty($_POST['first_name'])) {
	$errors['first_name'] = 'First Name is required';
}
if(empty($_POST['last_name'])) {
	$errors['first_name'] = 'Last Name is required';
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
				logins.login_id = " . $db->quote($session->login['login_id']) . "
		");
	}
	// Save First and Last names.
	$db->query("
		UPDATE
			" . DB_SCHEMA_INTERNAL . ".logins
		SET
			first_name = " . $db->quote($_POST['first_name']) . ",
			last_name = " . $db->quote($_POST['last_name']) . ",
			email_password = " . $db->quote($_POST['email_password']) . "
		WHERE
			logins.login_id = " . $db->quote($session->login['login_id']) . "
	");

	print json_encode(array(
		'success' => True
	));
} else {
	print json_encode(array(
		'success' => False,
		'errors' => $errors
	));
}
