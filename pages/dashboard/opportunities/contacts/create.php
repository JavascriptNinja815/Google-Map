<?php

$session->ensureLogin();
$session->ensureRole('Sales');

if(!empty($_POST['custno'])) { // Client.
	$grab_client = $db->query("
		SELECT
			arcust.sfid
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		WHERE
			arcust.custno = " . $db->quote($_POST['custno']) . "
	");
	$client = $grab_client->fetch();
	
	if(!$client) {
		print json_encode([
			'success' => False,
			'message' => 'Unable to add contacts for clients which do not exist in Salesforce'
		]);
		exit;
	}

	$temporary_id = 'temp-' . rand(1000000000, 2000000000);
	$grab_contact = $db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".sf_contacts
		(
			Id,
			FirstName,
			LastName,
			Title,
			Email,
			Phone,
			HomePhone,
			MobilePhone,
			Fax,
			AccountId
		)
		OUTPUT INSERTED.sf_contact_id
		VALUES(
			" . $db->quote($temporary_id) . ",
			" . $db->quote($_POST['firstname']) . ",
			" . $db->quote($_POST['lastname']) . ",
			" . $db->quote($_POST['title']) . ",
			" . $db->quote($_POST['email']) . ",
			" . $db->quote($_POST['phone']) . ",
			" . $db->quote($_POST['homephone']) . ",
			" . $db->quote($_POST['mobilephone']) . ",
			" . $db->quote($_POST['fax']) . ",
			" . $db->quote($client['sfid']) . "
		)
	");
	$contact = $grab_contact->fetch();
	$contact_id = $contact['sf_contact_id'];
} else if(empty($_POST['custno'])) { // Prospect.
	$grab_contact = $db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".opportunity_contacts
		(
			FirstName,
			LastName,
			Title,
			Email,
			Phone,
			HomePhone,
			MobilePhone,
			Fax,
			opportunity_id
		)
		OUTPUT INSERTED.contact_id
		VALUES(
			" . $db->quote($_POST['firstname']) . ",
			" . $db->quote($_POST['lastname']) . ",
			" . $db->quote($_POST['title']) . ",
			" . $db->quote($_POST['email']) . ",
			" . $db->quote($_POST['phone']) . ",
			" . $db->quote($_POST['homephone']) . ",
			" . $db->quote($_POST['mobilephone']) . ",
			" . $db->quote($_POST['fax']) . ",
			" . $db->quote($_POST['opportunity_id']) . "
		)
	");
	$contact = $grab_contact->fetch();
	$contact_id = $contact['contact_id'];
}

print json_encode([
	'success' => True,
	'contact_id' => $contact_id
]);
