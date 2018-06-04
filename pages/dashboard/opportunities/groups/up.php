<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_group = $db->query("
	SELECT
		opportunity_groups.position
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_groups
	WHERE
		opportunity_groups.opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");
$group = $grab_group->fetch();

if($group['position'] > 1) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunity_groups
		SET
			position = position + 1
		WHERE
			opportunity_groups.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
			AND
			opportunity_groups.position = " . $db->quote($group['position'] - 1) . "
	");

	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunity_groups
		SET
			position = position - 1
		WHERE
			opportunity_groups.opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
	");
}

print json_encode([
	'success' => True
]);