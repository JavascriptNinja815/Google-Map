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

// Refund a credit card transaction
try {
	// Instantiate the refund object.
	$refund = new AuthorizeRefund($original_transaction['authorize_transaction_id'], $original_transaction['last4']);

	// Perform the refund request.
	$refund->refund($_POST['amount'], $_POST['memo']);

	// Looks like everything went well, return success to the user.
	print json_encode([
		'success' => True,
		'transaction_id' => $refund->getAuthorizeTransactionID()
	]);
} catch(Exception $e) {
	print json_encode([
		'success' => False,
		'message' => $e->getMessage() . ' (Line ' . $e->getLine() . ')'
	]);
	exit;
}
