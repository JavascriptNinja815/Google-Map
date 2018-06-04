<?php

$grab_opportunities = $db->query("
	SELECT
		opportunities.opportunity_id,
		opportunities.contacts
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.contacts IS NOT NULL
");
foreach($grab_opportunities as $opportunity) {
	if($opportunity['contacts'] == '{}') {
		continue;
	}
	$contacts = json_decode($opportunity['contacts'], True);
	foreach($contacts as $contact) {
		if($contact['sf_contact_id']) {
			continue;
		}
		print_r($contact);
		print '<br />';
		$db->query("
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
			VALUES(
				" . $db->quote($contact['name']) . ",
				" . $db->quote('') . ",
				" . $db->quote($contact['title']) . ",
				" . $db->quote($contact['email']) . ",
				" . $db->quote($contact['phone']) . ",
				" . $db->quote('') . ",
				" . $db->quote('') . ",
				" . $db->quote('') . ",
				" . $db->quote($opportunity['opportunity_id']) . "
			)
		");
	}
}
