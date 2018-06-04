<?php

$session->ensureLogin();
$session->ensureRole('Sales');

if(empty($_POST['memo'])) {
	// If memo field is empty, there is nothing to do.
	print json_encode([
		'success' => True
	]);
	exit;
}

if($_REQUEST['field'] == 'routing') {
	$field = 'routing';
} else if($_REQUEST['field'] == 'packaging') {
	$field = 'packaging';
}

// grab existing memo to see if it is bank or if we should append to it.
$grab_arcust = $db->query("
	SELECT
		arcust.custno,
		arcust." . $field . "
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		arcust.custno = " . $db->quote($_POST['custno']) . "
");
$arcust = $grab_arcust->fetch();

$text = $arcust[$field];
if(empty($text)) {
	$text = '';
} else {
	// Add separator.
	$text .= "\n^~*~^\n";
}
$add_text = htmlentities($_POST['memo']);
$add_text .= "\n- By " . htmlentities($session->login['initials']) . ' on ' . time('m/d/y h:i:s A');
$text .= $add_text;

// Save the memo to the DB.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".arcust
	SET
		arcust." . $field . " = " . $db->quote($text) . "
	WHERE
		arcust.custno = " . $db->quote($arcust['custno']) . "
");

print json_encode([
	'success' => True,
	'text' => $add_text
]);
