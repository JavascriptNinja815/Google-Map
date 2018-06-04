<?php

$session->ensureLogin();

// Ensure a note has been specified.
if(empty($_POST['note'])) {
	print json_encode(array(
		'success' => False,
		'message' => 'Note is required'
	));
	exit();
}

// Query DB for Sales Person associated with the Purchase Order Number passed.
$grab_note_data = $db->prepare("
	SELECT
		LTRIM(
			RTRIM(
				CAST(
					ISNULL(pomast.notes, '')
					AS NVARCHAR(max)
				)
			)
		) AS notes
	FROM
		" . DB_SCHEMA_ERP . ".pomast
	WHERE
		LTRIM(RTRIM(pomast.purno)) = " . $db->quote($_POST['purchase-order-number']) . "
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$grab_note_data->execute(); // We use prepare/execute so we can return rowCount.
$note_data = $grab_note_data->fetch();

// Ensure the Purchase Order Number specified is valid.
if(!$grab_note_data->rowCount()) {
	print json_encode(array(
		'success' => False,
		'message' => 'Purchase Order Number specified does not exist'
	));
	exit();
} else if($grab_note_data->rowCount() > 1) { // More for debugging to ensure we don't do something horrible...
	print json_encode(array(
		'success' => False,
		'message' => 'Update will cause ' . $grab_order_data->rowCount() . ' POs to get updated'
	));
	exit();
}

// Define the body of the notes to be saved. If notes previously exist, the
// notes passed will be appended on a new line.
$note_datetime = new DateTime();
$note = "Note: " . trim($_POST['note']) . " by " . $session->login['initials'] . " on " . $note_datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($note_data['notes'])) {
	$notes = $note_data['notes'] . "\r\n\r\n" . $note;
} else {
	$notes = $note;
}

// Append the specified note to the Purchase Order.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".pomast
	SET
		notes = " . $db->quote($notes) . "
	WHERE
		LTRIM(RTRIM(pomast.purno)) = " . $db->quote($_POST['purchase-order-number']) . "
");

print json_encode(array(
	'success' => True,
	'note' => $note
));
