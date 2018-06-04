<?php

// TEMPORARY. Need to implement correct "conflict" detection code.
if($_REQUEST['status'] == 'THROW-CONFLICT') {
	print json_encode(array(
		'success' => False,
		'message' => 'conflict'
	));
	exit();
}

$grab_customer_order = $db->query("
	SELECT
		somast.sono AS customer_order_number,
		LTRIM(RTRIM(somast.orderstat)) AS order_status,
		LTRIM(RTRIM(
			CAST(
				ISNULL(somast.notes, '') AS NVARCHAR(max)
			)
		)) AS notes
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		LTRIM(RTRIM(somast.sono)) = " . $db->quote($_REQUEST['customer-order-number']) . "
");

// Ensure the Order ID specified is valid.
if(!$grab_customer_order->rowCount()) {
	print json_encode(array(
		'success' => False,
		'message' => 'Order ID specified does not exist'
	));
	exit();
} 

// Now that we know the Customer Order ID passed exists, let's grab it.
$customer_order = $grab_customer_order->fetch();

// Query existing Sales Order notes from the DB. If they're empty, create a
// first entry. If they're not empty, append to them.
$order_note_datetime = new DateTime();
$order_note = "Order Status Changed To: [" . trim($_REQUEST['status']) . "] by " . $_REQUEST['requested-by'] . " on " . $order_note_datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($customer_order)) {
	$order_notes = $customer_order['notes'] . "\r\n\r\n" . $order_note;
} else {
	$order_notes = $order_note;
}

if(empty($customer_order)) {
	print json_encode(array(
		'success' => False,
		'message' => 'customer order number not found'
	));
	exit();
}

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".somast
	SET
		somast.orderstat = " . $db->quote($_REQUEST['status']) . ",
		somast.notes = " . $db->quote($order_notes) . "
	WHERE
		LTRIM(RTRIM(somast.sono)) = " . $db->quote($_REQUEST['customer-order-number']) . "
");

print json_encode(array(
	'success' => True
));
 