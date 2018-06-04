<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$residential = !empty($_POST['resdntl']) && $_POST['resdntl'] ? 1 : 0;
$defaship = !empty($_POST['defaship']) && $_POST['defaship'] == 'Y' ? 'Y' : 'N';

// Insert into the DB.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".arcadr
	SET
		company = " . $db->quote($_POST['company']) . ",
		contact = " . $db->quote($_POST['contact']) . ",
		title = " . $db->quote($_POST['title']) . ",
		email = " . $db->quote($_POST['email']) . ",
		phone = " . $db->quote($_POST['phone']) . ",
		faxno = " . $db->quote($_POST['fax']) . ",
		address1 = " . $db->quote($_POST['address1']) . ",
		address2 = " . $db->quote($_POST['address2']) . ",
		city = " . $db->quote($_POST['city']) . ",
		addrstate = " . $db->quote($_POST['state']) . ",
		zip = " . $db->quote($_POST['zip']) . ",
		country = " . $db->quote($_POST['country']) . ",
		resdntl = " . $db->quote($residential) . ",
		fob = " . $db->quote($_POST['fob']) . ",
		shipvia = " . $db->quote($_POST['shipvia']) . ",
		shipchg = " . $db->quote((int)$_POST['frt_pay_method']) . ",
		upsshpact = " . $db->quote($_POST['carrier_account_number']) . ",
		salesmn = " . $db->quote($session->login['initials']) . ",
		terr = " . $db->quote($_POST['terr']) . ",
		tax = " . $db->quote((float)$_POST['tax']) . ",
		defaship = " . $db->quote($defaship) . ",
		comment = " . $db->quote($_POST['comment']) . "
	WHERE
		custno = " . $db->quote($_POST['custno']) . "
		AND
		cshipno = " . $db->quote($_POST['cshipno']) . "
");

print json_encode([
	'success' => True
]);
