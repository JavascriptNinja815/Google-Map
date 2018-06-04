<?php

$session->ensureLogin();

$grab_original_transaction = $db->query("
	SELECT
		authorize_transactions.authorize_transaction_id,
		authorize_transactions.last4
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transactions
	WHERE
		authorize_transactions.transaction_id = " . $db->quote($_POST['transaction_id']) . "
");
$original_transaction = $grab_original_transaction->fetch();

// Void a credit card transaction
try {
	// Instantiate the void object.
	$void = new AuthorizeVoid($original_transaction['authorize_transaction_id']);
	// Perform the void request.
	$void->void($_POST['memo']);

	// Looks like everything went well, return success to the user.
	print json_encode([
		'success' => True,
		'authorize_transaction_id' => $void->getAuthorizeTransactionID(),
		'transaction_id' => $void->getLocalTransactionID(),
		'auth_code' => $void->getAuthCode()
	]);
} catch(Exception $e) {
	print json_encode([
		'success' => False,
		'message' => $e->getMessage() . ' (Line ' . $e->getLine() . ')'
	]);
	exit;
}
