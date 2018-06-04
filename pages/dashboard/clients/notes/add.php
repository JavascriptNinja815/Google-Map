<?php

$date = new DateTime();
$new_note = $_POST['notes'] . ' by ' . $session->login['initials']  . ' on ' . $date->format('m/d/y h:m:i A');

if($_POST['type'] == 'invoice-notes') {
	// Grab existing notes.
	$grab_notes = $db->query("
		SELECT
			artran.arinvnts AS notes
		FROM
			" . DB_SCHEMA_ERP . ".artran
		WHERE
			RTRIM(LTRIM(artran.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			RTRIM(LTRIM(artran.invno)) = " . $db->quote(trim($_POST['invno'])) . "
	");
	$notes = $grab_notes->fetch();
	$notes = trim($notes['notes']);
	
	// Append new notes to existing notes.
	if(!empty($notes)) {
		$notes .= "\r\n";
	}
	$notes .= $new_note;
	
	// Update notes.
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".artran
		SET
			arinvnts = " . $db->quote($notes) . "
		WHERE
			RTRIM(LTRIM(artran.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			RTRIM(LTRIM(artran.invno)) = " . $db->quote(trim($_POST['invno'])) . "
	");
} else if($_POST['type'] == 'client-notes') {
	// Grab existing notes.
	$grab_notes = $db->query("
		SELECT
			arcust.arnotes AS notes
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		WHERE
			RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	");
	$notes = $grab_notes->fetch();
	$notes = trim($notes['notes']);
	
	// Append new notes to existing notes.
	if(!empty($notes)) {
		$notes .= "\r\n";
	}
	$notes .= $new_note;
	
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".arcust
		SET
			arnotes = " . $db->quote($notes) . "
		WHERE
			RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	");
}

print json_encode([
	'success' => True,
	'note' => $new_note
]);
