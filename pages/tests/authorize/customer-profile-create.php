<?php

$custno = $_REQUEST['custno'];
if(!$custno) {
	print 'custno must be passed as GET argument.';
	exit;
}

$card = [
	'number' => '4111111111111112',
	'expiration' => '12/17',
	'code' => '123'
];
$card['last4'] = substr($card['number'], -4);

// Instantiate the Authorize API Interface object.
$authorize = new Authorize(
	AUTHORIZE_ENDPOINT, // Endpoint. Either `production` or `sandbox`.
	AUTHORIZE_LOGINID,
	AUTHORIZE_KEY
);

// Check if Customer Profile already present in the DB.
$grab_profile = $db->query("
	SELECT
		authorize_customer_profiles.customer_profile_id,
		authorize_customer_profiles.authorize_custprofile_id
	FROM
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
	WHERE
		authorize_customer_profiles.custno = " . $db->quote($custno) . "
");
$customer_profile = $grab_profile->fetch();
if(!$customer_profile) {
	// Customer Profile not present in the DB, create it on Authorize and insert entry into DB.
	$profile_ids = $authorize->createCustomerProfile($custno, $card['number'], $card['expiration'], $card['code']);
	$authorize_customer_profile_id = $profile_ids['customer_profile_id'];
	$authorize_payment_profile_id = $profile_ids['payment_profile_id'];

	$grab_customer_profile = $db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".authorize_customer_profiles
		(
			custno,
			authorize_custprofile_id
		)
		OUTPUT
			INSERTED.customer_profile_id,
			INSERTED.authorize_custprofile_id
		VALUES (
			" . $db->quote($custno) . ",
			" . $db->quote($authorize_customer_profile_id) . "
		)
	");
	$customer_profile = $grab_customer_profile->fetch();
} else {
	$authorize_payment_profile_id = $authorize->createPaymentProfile($customer_profile['authorize_custprofile_id'], $card['number'], $card['expiration'], $card['code']);
}

$grab_payment_profile = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
	(
		customer_profile_id,
		authorize_payprofile_id,
		last4,
		code,
		expiration
	)
	OUTPUT INSERTED.payment_profile_id
	VALUES (
		" . $db->quote($customer_profile['customer_profile_id']) . ",
		" . $db->quote($authorize_payment_profile_id) . ",
		" . $db->quote($card['last4']) . ",
		" . $db->quote($card['code']) . ",
		" . $db->quote($card['expiration']) . "
	)
");
$payment_profile = $grab_payment_profile->fetch();

print json_encode([
	'customer_profile_id' => $customer_profile['customer_profile_id'],
	'payment_profile_id' => $payment_profile['payment_profile_id']
]);
