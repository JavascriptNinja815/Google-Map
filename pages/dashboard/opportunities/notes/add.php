<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		opportunities.notes
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

// Define the body of the notes to be saved. If notes previously exist, the
// notes passed will be appended on a new line.
$note_datetime = new DateTime();
$note = "Note: " . trim($_POST['notes']) . " by " . $session->login['initials'] . " on " . $note_datetime->format('m/d/y h:i:s A') . "\r\n";
if(!empty($opportunity['notes'])) {
	$notes = $opportunity['notes'] . "\r\n\r\n" . $note;
} else {
	$notes = $note;
}

// Append the specified notes.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunities
	SET
		notes = " . $db->quote($notes) . "
	WHERE
		opportunities.opportunity_id = " . $db->quote($opportunity['opportunity_id']) . "
");

print json_encode(array(
	'success' => True,
	'note' => $note
));
