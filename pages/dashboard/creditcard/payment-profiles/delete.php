<?php

$session->ensureLogin();

$grab_payment_profile = $db->query("
	SELECT
		authorize_payment_profiles.authorize_payprofile_id
	FROM
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
	WHERE
		authorize_payment_profiles.payment_profile_id = " . $db->quote($_POST['payment_profile_id']) . "
");
$payment_profile = $grab_payment_profile->fetch();
if(empty($payment_profile)) {
	print json_encode([
		'success' => False,
		'message' => 'Payment Profile ID doesn\'t exist'
	]);
	exit;
}

$profile = new AuthorizePaymentProfile($_POST['custno'], $_POST['payment_profile_id']);

try {
	$result = $profile->delete($payment_profile['authorize_payprofile_id']);
} catch(Exception $e) {
	print json_encode([
		'success' => False,
		'message' => $e->getMessage()
	]);
	exit;
}

print json_encode([
	'success' => True
]);
