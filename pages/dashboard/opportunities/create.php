<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$fields = [];
$fields['login_id'] = $db->quote($_POST['login_id']);
$fields['entered_login_id'] = $db->quote($session->login['login_id']);
$fields['login_id'] = $db->quote($_POST['login_id']);
$fields['name'] = $db->quote($_POST['name']);
if($_POST['clienttype'] === 'prospect') {
	$fields['client_name'] = $db->quote($_POST['client_name']);
	$fields['custno'] = 'NULL';
	$fields['terr'] = $db->quote($_POST['office']);
} else if($_POST['clienttype'] === 'client') {
	$custno = explode(' - ', $_POST['custno']);
	$custno = $custno[0];
	$fields['custno'] = $db->quote($custno);
	$fields['client_name'] = 'NULL';
	//$grab_terr = $db->query("
	//	SELECT
	//		arcust.terr
	//	FROM
	//		" . DB_SCHEMA_ERP . ".arcust
	//	WHERE
	//		arcust.custno = " . $db->quote($custno) . "
	//");
	//$terr = $grab_terr->fetch();
	//$terr = trim($terr['terr']);
	//if(!in_array($terr, ['GR', 'DT', 'EG', 'VA'])) {
	//	$terr = 'CS';
	//}
	//$fields['terr'] = $db->quote(trim($terr['office']));
	$fields['terr'] = $db->quote($_POST['office']);
}
$fields['opportunity_type_id'] = $db->quote($_POST['opportunity_type_id']);
$fields['stage'] = $db->quote($_POST['stage']);
if($_POST['stage'] === 'Closed Lost') {
	$fields['lost_to'] = $db->quote($_POST['lost_to']);
	$fields['lost_reason'] = $db->quote($_POST['lost_reason']);
} else {
	$fields['lost_to'] = 'NULL';
	$fields['lost_reason'] = 'NULL';
}
$fields['next_step'] = $db->quote('');
$fields['next_step_memo'] = 'NULL';
/*$fields['next_step'] = $db->quote($_POST['next_step']);
if($_POST['next_step'] === 'Other') {
	$fields['next_step_memo'] = $db->quote($_POST['next_step_memo']);
} else {
	$fields['next_step_memo'] = 'NULL';
}*/
$fields['due_date'] = $db->quote(date('Y-m-d\Th:i:s', time()));
//$fields['due_date'] = $db->quote(date('Y-m-d\Th:i:s', strtotime($_POST['due_date'])));
if(!empty($_POST['close_date'])) {
	$fields['close_date'] = $db->quote(date('Y-m-d', strtotime($_POST['close_date'])));
} else {
	$fields['close_date'] = 'NULL';
}
if(!empty($_POST['expires'])) {
	$fields['expires'] = $db->quote(date('Y-m-d', strtotime($_POST['expires'])));
} else {
	$fields['expires'] = 'NULL';
}
$fields['amount'] = (int)$_POST['amount'];
if(!empty($_POST['competitors'])) {
	$fields['competitors'] = $db->quote(implode('|', $_POST['competitors']));
} else {
	$fields['competitors'] = 'NULL';
}
$fields['vendor_lead'] = $db->quote($_POST['vendor_lead']);
$fields['source'] = $db->quote($_POST['source']);
//$fields['notes'] = $db->quote($_POST['notes']);
$fields['vendor_ref'] = $db->quote($_POST['vendor_ref']);
$fields['quotetemplate_id'] = $db->quote($_POST['quotetemplate_id']);

/*
if(!empty($_POST['contacts'])) {
	$fields['contacts'] = [];
	foreach($_POST['contacts']['name'] as $offset => $name) {
		if(
			!empty($name) ||
			(!empty($_POST['contacts']['sf_contact_id']) && !empty($_POST['contacts']['sf_contact_id'][$offset])) ||
			(!empty($_POST['contacts']['name']) && !empty($_POST['contacts']['name'][$offset])) ||
			(!empty($_POST['contacts']['title']) && !empty($_POST['contacts']['title'][$offset])) ||
			(!empty($_POST['contacts']['phone']) && !empty($_POST['contacts']['phone'][$offset])) ||
			(!empty($_POST['contacts']['email']) && !empty($_POST['contacts']['email'][$offset])) ||
			(!empty($_POST['contacts']['memo']) && !empty($_POST['contacts']['memo'][$offset]))
		) {
			$fields['contacts'][] = [
				'sf_contact_id' => isset($_POST['contacts']['sf_contact_id']) && isset($_POST['contacts']['sf_contact_id'][$offset]) ? $_POST['contacts']['sf_contact_id'][$offset] : '',
				'name' => isset($_POST['contacts']['name']) && isset($_POST['contacts']['name'][$offset]) ? $_POST['contacts']['name'][$offset] : '',
				'title' => isset($_POST['contacts']['title']) && isset($_POST['contacts']['title'][$offset]) ? $_POST['contacts']['title'][$offset] : '',
				'phone' => isset($_POST['contacts']['phone']) && isset($_POST['contacts']['phone'][$offset]) ? $_POST['contacts']['phone'][$offset] : '',
				'email' => isset($_POST['contacts']['email']) && isset($_POST['contacts']['email'][$offset]) ? $_POST['contacts']['email'][$offset] : '',
				'memo' => isset($_POST['contacts']['memo']) && isset($_POST['contacts']['memo'][$offset]) ? $_POST['contacts']['memo'][$offset] : ''
			];
		}
	}
	$fields['contacts'] = $db->quote(json_encode($fields['contacts']));
} else {
	$fields['contacts'] = $db->quote(json_encode(new ArrayObject()));
}
 */

$keys = implode(",\r\n\t\t", array_keys($fields));
$values = implode(",\r\n\t\t", array_values($fields)); 

$grab_opportunity = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunities
	(
		" . $keys . "
	)
	OUTPUT Inserted.opportunity_id
	VALUES (
		" . $values . "
	)
");
$opportunity = $grab_opportunity->fetch();

print json_encode([
	'success' => True,
	'opportunity_id' => $opportunity['opportunity_id']
]);
