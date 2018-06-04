<?php

// Ensure login has "Sales" role.
if(!$session->hasRole('Sales')) {
	print json_encode(array(
		'success' => False,
		'message' => 'You do not have permission to perform this action'
	));
	exit();
}

// Ensure a note has been specified.
if(empty($_POST['order-note'])) {
	print json_encode(array(
		'success' => False,
		'message' => 'Order Note is required'
	));
	exit();
}

// Query DB for Sales Person associated with the Order ID passed.
$grab_order_data = $db->prepare("
	SELECT
		LTRIM(RTRIM(
			CAST(
				ISNULL(somast.notes, '')
				AS NVARCHAR(max)
			)
		)) AS notes
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		RTRIM(LTRIM(somast.sono)) = " . $db->quote($_POST['order-id']) . "
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$grab_order_data->execute(); // We use prepare/execute so we can return rowCount.

// Ensure the Order ID specified is valid.
if(!$grab_order_data->rowCount()) {
	print json_encode(array(
		'success' => False,
		'message' => 'Order ID specified does not exist'
	));
	exit();
}
$order_data = $grab_order_data->fetch();

// Define the body of the notes to be saved. If notes previously exist, the
// notes passed will be appended on a new line.
$order_note_datetime = new DateTime();
$order_note = "Note: " . trim($_POST['order-note']) . " by " . $session->login['initials'] . " on " . $order_note_datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($order_data['notes'])) {
	$order_notes = $order_data['notes'] . "\r\n\r\n" . $order_note;
} else {
	$order_notes = $order_note;
}

// Append the specified note to the Order.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".somast
	SET
		notes = " . $db->quote($order_notes) . "
	WHERE
		RTRIM(LTRIM(somast.sono)) = " . $db->quote($_POST['order-id']) . "
");

print json_encode(array(
	'success' => True,
	'note' => $order_note
));
