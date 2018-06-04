<?php

$session->ensureLogin();

$grab_creditcards = $db->query("
	SELECT
		authorize_payment_profiles.payment_profile_id,
		authorize_payment_profiles.last4,
		authorize_payment_profiles.expiration,
		authorize_payment_profiles.profile_name,
		authorize_payment_profiles.address,
		authorize_payment_profiles.zip
	FROM
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
	INNER JOIN
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
		ON
		authorize_customer_profiles.customer_profile_id = authorize_payment_profiles.customer_profile_id
	WHERE
		authorize_customer_profiles.custno = " . $db->quote($_POST['custno']) . "
		AND
		authorize_payment_profiles.live = " . $db->quote(ISLIVE) . "
	ORDER BY
		authorize_payment_profiles.profile_name
");
$creditcards = [];
foreach($grab_creditcards as $creditcard) {
	$creditcards[] = [
		'payment_profile_id' => $creditcard['payment_profile_id'],
		'name' => $creditcard['profile_name'],
		'last4' => $creditcard['last4'],
		'expiration' => $creditcard['expiration'],
		'address' => $creditcard['address'],
		'zip' => $creditcard['zip']
	];
}

print json_encode([
	'success' => True,
	'creditcards' => $creditcards
]);
