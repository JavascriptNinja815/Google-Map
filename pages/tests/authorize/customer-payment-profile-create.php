<?php

define('AUTHORIZE_LOGINID', '6Xk88Qu5h'); // SANDBOX
define('AUTHORIZE_KEY', '7979cw3QwjT5LLfm'); // SANDBOX
//define('AUTHORIZE_LOGINID', '8sY9FtB49W2Z'); // LIVE
//define('AUTHORIZE_KEY', '6x285d5q82De2ZPx'); // LIVE

$custno = $_REQUEST['custno'];
if(!$custno) {
	print 'custno must be passed as GET argument.';
	exit;
}

$card = [
	'number' => '4111111111111111',
	'expiration' => '12/17',
	'cvv' => '123'
];

// Ensure authorize customer profile entry already exists.
$grab_profile = $db->query("
	SELECT
		authorize_customer_profiles.customer_profile_id,
		authorize_customer_profiles.custno,
		authorize_customer_profiles.authorize_custprofile_id
	FROM
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
	WHERE
		authorize_customer_profiles.custno = " . $db->quote($custno) . "
");
$profile = $grab_profile->fetch();
if(!$profile) {
	print 'Authorize.net customer profile doesn\'t appear to exist.';
	exit;
}

$authorize = new Authorize(
	'sandbox', // Endpoint. Either `production` or `sandbox`.
	AUTHORIZE_LOGINID,
	AUTHORIZE_KEY
);
$authorize_customer_profile_id = $authorize->createPaymentProfile($profile['authorize_custprofile_id'], $card['number'], $card['expiration'], $card['cvv']);

// Ensure authorize payment profile id doesn't already exits.
$grab_profile = $db->query("
	SELECT
		authorize_payment_profiles.custno
	FROM
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
	WHERE
		authorize_payment_profiles.customer_profile_id = " . $db->quote($custno) . "
");
$profile = $grab_profile->fetch();
if(!$profile) {

$db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
	(
		custno,
		authorize_custprofile_id
	) VALUES (
		" . $db->quote($custno) . ",
		" . $db->quote($authorize_customer_profile_id) . "
	)
");

print 'DONE!';
