<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_lineitem = $db->query("
	SELECT
		opportunity_lineitems.opportunity_lineitem_id,
		opportunity_lineitems.opportunity_group_id,
		opportunity_lineitems.position
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	WHERE
		opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
");
$lineitem = $grab_lineitem->fetch();

$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	WHERE
		opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($lineitem['opportunity_lineitem_id']) . "
");

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	SET
		position = position - 1
	WHERE
		opportunity_lineitems.opportunity_group_id = " . $db->quote($lineitem['opportunity_group_id']) . "
		AND
		opportunity_lineitems.position >= " . $db->quote($lineitem['position']) . "
");

print json_encode([
	'success' => True
]);
