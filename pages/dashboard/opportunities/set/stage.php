<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Compare new stage vs previously value.
$grab_opportunity = $db->query("
	SELECT
		opportunities.stage
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

if($opportunity['stage'] != $_POST['stage']) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunities
		SET
			stage = " . $db->quote($_POST['stage']) . "
		WHERE
			opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
	");

	// Log the change.
	$db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".opportunity_logs
		(
			opportunity_id,
			login_id,
			field,
			from_value,
			to_value
		) VALUES (
			" . $db->quote($_POST['opportunity_id']) . ",
			" . $db->quote($session->login['login_id']) . ",
			'stage',
			" . $db->quote($opportunity['stage']) . ",
			" . $db->quote($_POST['stage']) . "
		)
	");
}

print json_encode([
	'success' => True
]);
