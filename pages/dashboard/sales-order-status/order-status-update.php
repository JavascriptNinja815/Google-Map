<?php

// Ensure login has "Sales" role.
if(!$session->hasRole('Sales')) {
	print json_encode(array(
		'success' => False,
		'message' => 'You do not have permission to perform this action'
	));
	exit();
}

// Ensure a status has been specified.
if(!isset($_POST['order-status'])) {
	print json_encode(array(
		'success' => False,
		'message' => 'Order Status is required'
	));
	exit();
}

// Query DB for Sales Person associated with the Order ID passed.
$grab_order_data = $db->prepare("
	SELECT
		LTRIM(RTRIM(somast.salesmn)) AS sales_person,
		LTRIM(RTRIM(
			CAST(
				ISNULL(somast.notes, '') AS NVARCHAR(max)
			)
		)) AS notes
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.id_col = " . $db->quote($_POST['order-id']) . "
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$grab_order_data->execute(); // We use prepare/execute so we can return rowCount.
$order_data = $grab_order_data->fetch();

// Ensure the Order ID specified is valid.
if(!$grab_order_data->rowCount()) {
	print json_encode(array(
		'success' => False,
		'message' => 'Order ID specified does not exist'
	));
	exit();
}

// Ensure login has permission to modify this order.
if(!$session->hasPermission('Sales', 'edit-orders', $order_data['sales_person'])) {
	print json_encode(array(
		'success' => False,
		'message' => 'You do not have permission to perform this action'
	));
	exit();
}

// Define the body of the notes to be saved. If notes previously exist, the
// notes passed will be appended on a new line.
$order_note_datetime = new DateTime();
$order_note = "Order Status Changed To: [" . trim($_POST['order-status']) . "] by " . $session->login['initials'] . " on " . $order_note_datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($order_data['notes'])) {
	$order_notes = $order_data['notes'] . "\r\n\r\n" . $order_note;
} else {
	$order_notes = $order_note;
}

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".somast
	SET
		orderstat = " . $db->quote($_POST['order-status']) . ",
		notes = " . $db->quote($order_notes) . "
	WHERE
		somast.id_col = " . $db->quote($_POST['order-id']) . "
");

//Logging::Log('Sales Order', 'Status', $_POST['order-id']);

print json_encode(array(
	'success' => True,
	'note' => $order_note
));
