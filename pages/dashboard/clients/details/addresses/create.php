<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Ensure there is not already a cshipno w/ the same value.
$grab_cshipno = $db->query("
	SELECT
		cshipno
	FROM
		" . DB_SCHEMA_ERP . ".arcadr
	WHERE
		RTRIM(LTRIM(arcadr.custno)) = " . $db->quote(trim($_POST['custno'])) . " -- Under this customer.
		AND
		RTRIM(LTRIM(arcadr.cshipno)) = " . $db->quote(trim($_POST['cshipno'])) . " -- w/ Same cshipno.
");
if($grab_cshipno->rowCount()) {
	print json_encode([
		'success' => False,
		'message' => 'There is already an Address with the Ship To No specified. Ship To No must be unique'
	]);
	exit;
}

$residential = !empty($_POST['resdntl']) && $_POST['resdntl'] ? 1 : 0;
$defaship = !empty($_POST['defaship']) && $_POST['defaship'] == 'Y' ? 'Y' : 'N';

// Insert into the DB.
$db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".arcadr
	(
		custno,
		cshipno,
		company,
		contact,
		title,
		email,
		phone,
		faxno,
		address1,
		address2,
		city,
		addrstate,
		zip,
		country,
		resdntl,
		fob,
		shipvia,
		shipchg,
		upsshpact,
		salesmn,
		terr,
		tax,
		entered,
		defaship,
		comment
	) VALUES (
		" . $db->quote($_POST['custno']) . ",
		" . $db->quote($_POST['cshipno']) . ",
		" . $db->quote($_POST['company']) . ",
		" . $db->quote($_POST['contact']) . ",
		" . $db->quote($_POST['title']) . ",
		" . $db->quote($_POST['email']) . ",
		" . $db->quote($_POST['phone']) . ",
		" . $db->quote($_POST['fax']) . ",
		" . $db->quote($_POST['address1']) . ",
		" . $db->quote($_POST['address2']) . ",
		" . $db->quote($_POST['city']) . ",
		" . $db->quote($_POST['state']) . ",
		" . $db->quote($_POST['zip']) . ",
		" . $db->quote($_POST['country']) . ",
		" . $db->quote($residential) . ",
		" . $db->quote($_POST['fob']) . ",
		" . $db->quote($_POST['shipvia']) . ",
		" . $db->quote((int)$_POST['frt_pay_method']) . ",
		" . $db->quote($_POST['carrier_account_number']) . ",
		" . $db->quote($session->login['initials']) . ",
		" . $db->quote($_POST['terr']) . ",
		" . $db->quote((float)$_POST['tax']) . ",
		GETDATE(),
		" . $db->quote($defaship) . ",
		" . $db->quote($_POST['comment']) . "
	)
");

print json_encode([
	'success' => True
]);
