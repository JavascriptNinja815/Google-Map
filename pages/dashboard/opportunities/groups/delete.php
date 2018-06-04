<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Delete lineitems associated with this group.
$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	WHERE
		opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");

// Grab the group's current position.
$grab_group = $db->query("
	SELECT
		opportunity_groups.position
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_groups
	WHERE
		opportunity_groups.opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");
$group = $grab_group->fetch();

// Delete the group.
$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".opportunity_groups
	WHERE
		opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");

// Update any groups after this group, setting their position back one.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_groups
	SET
		position = position - 1
	WHERE
		opportunity_groups.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
		AND
		opportunity_groups.position > " . $db->quote($group['position']) . "
");

print json_encode([
	'success' => True
]);
