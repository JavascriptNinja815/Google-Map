<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_contacts = $db->query("
	SELECT
		sf_contacts.sf_contact_id,
		sf_contacts.FirstName,
		sf_contacts.LastName,
		sf_contacts.Title
	FROM
		" . DB_SCHEMA_ERP . ".sf_contacts
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.sfid LIKE (sf_contacts.AccountId + '%')
	WHERE
		arcust.custno = " . $db->quote($_POST['custno']) . "
	ORDER BY
		sf_contacts.FirstName,
		sf_contacts.LastName,
		sf_contacts.Title
");

$contacts = [];
foreach($grab_contacts as $contact) {
	$contacts[$contact['sf_contact_id']] = $contact['FirstName'] . ' ' . $contact['LastName'] . (!empty($contact['Title']) ? ' ' . $contact['Title'] : Null);
}

print json_encode([
	'success' => True,
	'contacts' => $contacts
]);
