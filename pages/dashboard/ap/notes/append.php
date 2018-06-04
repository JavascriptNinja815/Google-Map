<?php

$session->ensureLogin();
$session->ensureRole('Accounting');

$grab_notes = $db->query("
	SELECT
		apmast.notes
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.invno = " . $db->quote($_POST['invno']) . "
");
$ap = $grab_notes->fetch();

// Define the body of the notes to be saved. If notes previously exist, the
// notes passed will be appended on a new line.
$datetime = new DateTime();
$note = "Note: " . trim($_POST['note']) . " by " . $session->login['initials'] . " on " . $datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($ap['notes'])) {
	$notes = $ap['notes'] . "\r\n\r\n" . $note;
} else {
	$notes = $note;
}

// Append the specified note
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".apmast
	SET
		notes = " . $db->quote($notes) . "
	WHERE
		apmast.invno = " . $db->quote($_POST['invno']) . "
");

print json_encode(array(
	'success' => True,
	'note' => $note
));
