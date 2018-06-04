<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_count = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_groups
	WHERE
		opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$count = $grab_count->fetch();
$position = $count['count'] + 1;

if($position == 1) {
	$selected = 1;
} else {
	$selected = 1; // We used to only force the initial group to be checked. Now we want all checked by default.
}

$grab_opportunity_group = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunity_groups
	(
		name,
		opportunity_id,
		selected,
		position
	)
	OUTPUT
		INSERTED.opportunity_group_id
	VALUES (
		" . $db->quote($_POST['name']) . ",
		" . $db->quote($_POST['opportunity_id']) . ",
		" . $db->quote($selected) . ",
		" . $db->quote($position) . "
	)
");
$opportunity_group = $grab_opportunity_group->fetch();

print json_encode([
	'success' => True,
	'opportunity_group_id' => $opportunity_group['opportunity_group_id'],
	'selected' => $selected
]);
