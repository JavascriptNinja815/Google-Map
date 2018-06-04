<?php

$session->ensureLogin();

// Ensure all required fields are present.
$required = [
	'nameoncard' => 'Name On Card',
	'card_number' => 'Card Number',
	'expiration_year' => 'Expiration Year',
	'expiration_month' => 'Expiration Month',
	'code' => 'Security Code'
];
foreach($required as $key => $text) {
	if(empty($_POST[$key])) {
		print json_encode([
			'success' => False,
			'message' => $text . ' is required'
		]);
		exit;
	}
}

// Ensure either Billing Address or Billing Zip Code is present.
if(empty($_POST['street_address']) && empty($_POST['zip_code'])) {
	print json_encode([
		'success' => False,
		'message' => 'Billing Address and/or Billing Zip are required'
	]);
	exit;
}

$payment_profile = new AuthorizePaymentProfile(
	$_POST['custno'] // $custno
);

if(!$payment_profile->exists) {
	// Payment profile does not exist, let's create it.
	try {
		$payment_profile->create(
			$_POST['nickname'], // $nickname,
			$_POST['nameoncard'], // $nameoncard,
			$_POST['card_number'], // $card_number,
			$_POST['expiration_year'] . '-' . $_POST['expiration_month'], // $expiration,
			$_POST['code'], // $cardcode,
			$_POST['street_address'], // $address,
			$_POST['zip_code'] // $zip
		);
	} catch (AuthorizePaymentProfileException $e) {
		// Seems something failed, return the errors for display.
		print json_encode([
			'success' => False,
			'message' => $e->getMessage()
		]);
		exit;
	} catch (AuthorizeCustomerProfileException $e) {
		// Seems something failed, return the errors for display.
		print json_encode([
			'success' => False,
			'message' => $e->getMessage()
		]);
		exit;
	}
}

print json_encode([
	'success' => True,
	'payment_profile_id' => $payment_profile->getLocalPaymentProfileId()
]);

exit;





/*
 * OLD CODE BELOW.
 */

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
	$profile_ids = $authorize->createCustomerAndPaymentProfile($custno, $card['number'], $card['expiration'], $card['code'], $_POST['billing_address'], $_POST['billing_zip']);
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
	$authorize_payment_profile_id = $authorize->createPaymentProfile($customer_profile['authorize_custprofile_id'], $card['number'], $card['expiration'], $card['code'], $_POST['billing_address'], $_POST['billing_zip']);
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
