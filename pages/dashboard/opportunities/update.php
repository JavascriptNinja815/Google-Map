<?php

$session->ensureLogin();
$session->ensureRole('Sales');

function log_change($fieldname, $from, $to) {
	global $db, $session;
	//$db = $db['internal'];
	$db['internal']->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".opportunity_logs
		(
			opportunity_id,
			login_id,
			field,
			from_value,
			to_value
		) VALUES (
			" . $db['internal']->quote($_POST['opportunity_id']) . ",
			" . $db['internal']->quote($session->login['login_id']) . ",
			" . $db['internal']->quote($fieldname) . ",
			" . $db['internal']->quote($from) . ",
			" . $db['internal']->quote($to) . "
		)
	");
}

// Thanks to @treeface :: https://stackoverflow.com/questions/3876435/recursive-array-diff
function array_diff_recursive($arr1, $arr2) {
	$outputDiff = [];
	foreach($arr1 as $key => $value) {
		if(array_key_exists($key, $arr2)) {
			//if the key exists in the second array, recursively call this function 
			//if it is an array, otherwise check if the value is in arr2
			if(is_array($value)) {
				$recursiveDiff = array_diff_recursive($value, $arr2[$key]);
				if(count($recursiveDiff)) {
					$outputDiff[$key] = $recursiveDiff;
				}
			} else if(!in_array($value, $arr2)) {
				$outputDiff[$key] = $value;
			}
		} else if(!in_array($value, $arr2)) {
			//if the key is not in the second array, check if the value is in 
			//the second array (this is a quirk of how array_diff works)
			$outputDiff[$key] = $value;
		}
	}
	return $outputDiff;
}

$grab_opportunity = $db->query("
	SELECT
		opportunities.login_id,
		opportunities.opportunity_type_id,
		opportunities.custno,
		opportunities.client_name,
		opportunities.name,
		opportunities.stage,
		opportunities.lost_to,
		opportunities.lost_reason,
		opportunities.next_step,
		opportunities.next_step_memo,
		opportunities.amount,
		CONVERT(varchar(10), opportunities.due_date, 120) AS due_date,
		CONVERT(varchar(10), opportunities.close_date, 120) AS close_date,
		opportunities.competitors,
		opportunities.vendor_lead,
		opportunities.notes,
		opportunities.source,
		--opportunities.contacts,
		CONVERT(varchar(10), opportunities.expires, 120) AS expires,
		opportunities.terr,
		opportunities.vendor_ref
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();
//$opportunity['contacts'] = json_decode($opportunity['contacts'], True);

$fields = [];

// login_id
$login_id = $_POST['login_id'];
if($login_id != $opportunity['login_id']) {
	$fields['login_id'] = $db->quote($login_id);
	log_change('login_id', $opportunity['login_id'], $login_id);
}

// name
$name = $_POST['name'];
if($name != $opportunity['name']) {
	$fields['name'] = $db->quote($name);
	//log_change('name', $opportunity['name'], $name);
}

// client_name AND custno
if($_POST['clienttype'] === 'prospect') {
	$client_name = $_POST['client_name'];
	$custno = 'NULL';
} else if($_POST['clienttype'] === 'client') {
	$custno_parts = explode(' - ', $_POST['custno']);
	$client_name = 'NULL';
	$custno = $custno_parts[0];
}
if($custno === 'NULL' && !empty($opportunity['custno'])) {
	$fields['custno'] = $custno;
	log_change('custno', $opportunity['custno'], $custno);
} else if($custno !== 'NULL' && $custno != $opportunity['custno']) {
	$fields['custno'] = $db->quote($custno);
	log_change('custno', $opportunity['custno'], $custno);
}
if($client_name === 'NULL' && !empty($opportunity['client_name'])) {
	$fields['client_name'] = $client_name;
	log_change('client_name', $opportunity['client_name'], $client_name);
} else if($client_name !== 'NULL' && $client_name != $opportunity['client_name']) {
	$fields['client_name'] = $db->quote($client_name);
	log_change('client_name', $opportunity['client_name'], $client_name);
}

// opportunity_type_id
$opportunity_type_id = $_POST['opportunity_type_id'];
if($opportunity_type_id != $opportunity['opportunity_type_id']) {
	$fields['opportunity_type_id'] = $db->quote($opportunity_type_id);
	//log_change('opportunity_type_id', $opportunity['opportunity_type_id'], $opportunity_type_id);
}

// stage
$stage = $_POST['stage'];
if($stage != $opportunity['stage']) {
	$fields['stage'] = $db->quote($stage);
	log_change('stage', $opportunity['stage'], $stage);
}

// lost_to AND lost_reason
if($_POST['stage'] === 'Closed Lost') {
	$lost_to = $_POST['lost_to'];
	$lost_reason = $_POST['lost_reason'];
} else {
	$lost_to = 'NULL';
	$lost_reason = 'NULL';
}
if($lost_to === 'NULL' && !empty($opportunity['lost_to'])) {
	$fields['lost_to'] = $lost_to;
	//log_change('lost_to', $opportunity['lost_to'], $lost_to);
} else if($lost_to !== 'NULL' && $lost_to != $opportunity['lost_to']) {
	$fields['lost_to'] = $db->quote($lost_to);
	//log_change('lost_to', $opportunity['lost_to'], $lost_to);
}
if($lost_reason === 'NULL' && !empty($opportunity['lost_reason'])) {
	$fields['lost_reason'] = $lost_reason;
	//log_change('lost_reason', $opportunity['lost_reason'], $lost_reason);
} else if($lost_reason !== 'NULL' && $lost_reason != $opportunity['lost_reason']) {
	$fields['lost_reason'] = $db->quote($lost_reason);
	//log_change('lost_reason', $opportunity['lost_reason'], $lost_reason);
}

// next_step AND next_step_memo
/*$fields['next_step'] = $db->quote($_POST['next_step']);
if($_POST['next_step'] === 'Other') {
	$fields['next_step_memo'] = $db->quote($_POST['next_step_memo']);
} else {
	$fields['next_step_memo'] = 'NULL';
}*/

// due_date
//$fields['due_date'] = $db->quote(date('Y-m-d\Th:i:s', strtotime($_POST['due_date'])));

// close_date
if(!empty($_POST['close_date'])) {
	$close_date = date('Y-m-d', strtotime($_POST['close_date']));
} else {
	$close_date = 'NULL';
}
if($close_date === 'NULL' && !empty($opportunity['close_date'])) {
	$fields['close_date'] = $close_date;
	//log_change('close_date', $opportunity['close_date'], $close_date);
} else if($close_date !== 'NULL' && $close_date != $opportunity['close_date']) {
	$fields['close_date'] = $db->quote($close_date);
	//log_change('close_date', $opportunity['close_date'], $close_date);
}

// expires
if(!empty($_POST['expires'])) {
	$expires = date('Y-m-d', strtotime($_POST['expires']));
} else {
	$expires = 'NULL';
}
if($expires === 'NULL' && !empty($opportunity['expires'])) {
	$fields['expires'] = $expires;
	//log_change('expires', $opportunity['expires'], $expires);
} else if($expires !== 'NULL' && $expires != $opportunity['expires']) {
	$fields['expires'] = $db->quote($expires);
	//log_change('expires', $opportunity['expires'], $expires);
}

// amount
$amount = (int)$_POST['amount'];
if($amount != (int)$opportunity['amount']) {
	$fields['amount'] = $amount;
	//log_change('amount', $opportunity['amount'], $amount);
}

if(!empty($_POST['competitors'])) {
	$competitors = implode('|', $_POST['competitors']);
} else {
	$competitors = 'NULL';
}
if($competitors === 'NULL' && !empty($opportunity['competitors'])) {
	$fields['competitors'] = $competitors;
	//log_change('competitors', $opportunity['competitors'], $competitors);
} else if($competitors !== 'NULL' && $competitors != $opportunity['competitors']) {
	$fields['competitors'] = $db->quote($competitors);
	//log_change('competitors', $opportunity['competitors'], $competitors);
}

// terr
$terr = $_POST['office'];
if($terr != $opportunity['terr']) {
	$fields['terr'] = $db->quote($terr);
	//log_change('terr', $opportunity['terr'], $terr);
}

// vendor_lead
$vendor_lead = $_POST['vendor_lead'];
if($vendor_lead != $opportunity['vendor_lead']) {
	$fields['vendor_lead'] = $db->quote($vendor_lead);
	//log_change('vendor_lead', $opportunity['vendor_lead'], $vendor_lead);
}

// source
$source = $_POST['source'];
if($source != $opportunity['source']) {
	$fields['source'] = $db->quote($source);
	//log_change('source', $opportunity['source'], $source);
}

// Vendor Ref
$vendor_ref = $_POST['vendor_ref'];
if($vendor_ref != $opportunity['vendor_ref']) {
	$fields['vendor_ref'] = $db->quote($vendor_ref);
}

// Quote Template
$quotetemplate_id = $_POST['quotetemplate_id'];
if($quotetemplate_id != $opportunity['quotetemplate_id']) {
	$fields['quotetemplate_id'] = $db->quote($quotetemplate_id);
}

// notes
/*
$notes = $_POST['notes'];
if($notes != $opportunity['notes']) {
	$fields['notes'] = $db->quote($notes);
	//log_change('notes', $opportunity['notes'], $notes);
}*/

/*
$contacts = new ArrayObject();
if(!empty($_POST['contacts'])) {
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
			$contacts[] = [
				'sf_contact_id' => isset($_POST['contacts']['sf_contact_id']) && isset($_POST['contacts']['sf_contact_id'][$offset]) ? $_POST['contacts']['sf_contact_id'][$offset] : '',
				'name' => isset($_POST['contacts']['name']) && isset($_POST['contacts']['name'][$offset]) ? $_POST['contacts']['name'][$offset] : '',
				'title' => isset($_POST['contacts']['title']) && isset($_POST['contacts']['title'][$offset]) ? $_POST['contacts']['title'][$offset] : '',
				'phone' => isset($_POST['contacts']['phone']) && isset($_POST['contacts']['phone'][$offset]) ? $_POST['contacts']['phone'][$offset] : '',
				'email' => isset($_POST['contacts']['email']) && isset($_POST['contacts']['email'][$offset]) ? $_POST['contacts']['email'][$offset] : '',
				'memo' => isset($_POST['contacts']['memo']) && isset($_POST['contacts']['memo'][$offset]) ? $_POST['contacts']['memo'][$offset] : ''
			];
		}
	}
}
$fields['contacts'] = $db->quote(json_encode($contacts));
//log_change('contacts', json_encode($opportunity['contacts']), json_encode($contacts));
*/

if(!empty($fields)) {
	$set = [];
	foreach($fields as $key => $value) {
		$set[] = $key . ' = ' . $value;
	}

	$query = "
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunities
		SET
			" . implode(",\r\n\t\t", $set) . "
		WHERE
			opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
	";
	$db->query($query);
}

print json_encode([
	'success' => True,
	'updated-fields' => array_keys($fields)
]);
