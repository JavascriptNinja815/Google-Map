<?php

ini_set('max_execution_time', 300);

$session->ensureLogin();

$search_where = [];
if(!empty($_POST['salesmn'])) {
	$search_where[] = "authorize_transactions.salesmn = " . $db->quote($_POST['salesmn']);
}

if(!empty($_POST['date_from']) && !empty($_POST['date_to'])) {
	$date_from_timestamp = strtotime($_POST['date_from']);
	$date_from_sql = date('Y-m-d', $date_from_timestamp);
	$date_to_timestamp = strtotime($_POST['date_to']);
	$date_to_sql = date('Y-m-d', $date_to_timestamp);
	if($date_from_sql != $date_to_sql) {
		// Date Range.
		$search_where[] = "(authorize_transactions.added_on >= " . $db->quote($date_from_sql . ' 00:00:00.000') . " AND authorize_transactions.added_on <= " . $db->quote($date_to_sql . ' 23:59:59.999') . ")";
	} else {
		// Exact Date.
		$search_where[] = "CAST(authorize_transactions.added_on AS DATE) = " . $db->quote($date_from_sql);
	}
}

if(isset($_POST['status']) && $_POST['status'] != '') {
	$search_where[] = "authorize_transactions.status = " . $db->quote($_POST['status']);
}

if(!empty($_POST['auth_id'])) {
	$search_where[] = "(authorize_transactions.authorize_transaction_id = " . $db->quote($_POST['auth_id']) . " OR authorize_transactions.auth_code = " . $db->quote($_POST['auth_id']) . ")";
}

if(!empty($_POST['amount']) && strlen($_POST['amount']['from']) > 0) {
	if($_POST['amount']['operator'] == '<') {
		$search_where[] = "authorize_transactions.amount <= " . $db->quote($_POST['amount']['from']);
	} else if($_POST['amount']['operator'] == '=') {
		$search_where[] = "authorize_transactions.amount = " . $db->quote($_POST['amount']['from']);
	} else if($_POST['amount']['operator'] == '>') {
		$search_where[] = "authorize_transactions.amount >= " . $db->quote($_POST['amount']['from']);
	} else if($_POST['amount']['operator'] == 'between') {
		$search_where[] = "(authorize_transactions.amount >= " . $db->quote($_POST['amount']['from']) . " AND authorize_transactions.amount <= " . $db->quote($_POST['amount']['to']) . ")";
	}
}

if(!empty($_POST['name'])) {
	$search_where_part = "(";
		$search_where_part .= "UPPER(authorize_transactions.nameoncard) LIKE UPPER(" . $db->quote('%' . $_POST['name'] . '%') . ")";
		$search_where_part .= " OR ";
		$search_where_part .= "UPPER(authorize_transactions.name) LIKE UPPER(" . $db->quote('%' . $_POST['name'] . '%') . ")";
		$search_where_part .= " OR ";
		$search_where_part .= "UPPER(authorize_transactions.company) LIKE UPPER(" . $db->quote('%' . $_POST['name'] . '%') . ")";
		$search_where_part .= " OR ";
		$search_where_part .= "UPPER(authorize_transactions.ap_name) LIKE UPPER(" . $db->quote('%' . $_POST['name'] . '%') . ")";
	$search_where_part .= ")";
	$search_where[] = $search_where_part;
}

if(!empty($_POST['custno'])) {
	$search_where[] = "authorize_transactions.custno = " . $db->quote($_POST['custno']);
}

if(!empty($_POST['sono'])) {
	$search_where[] = "transaction_sonos.relation_type_id = " . $db->quote($_POST['sono']);
}

if(!empty($_POST['invno'])) {
	$search_where[] = "transaction_invnos.relation_type_id = " . $db->quote($_POST['invno']);
}

if(!empty($_POST['memo'])) {
	$search_where[] = "UPPER(CONVERT(VARCHAR(MAX), memo)) LIKE UPPER(" . $db->quote('%' . $_POST['memo'] . '%') . ")";
}

$query = "
	WITH
		transactions AS (
			SELECT
				authorize_transactions.transaction_id
			FROM
				" . DB_SCHEMA_ERP . ".authorize_transactions
			LEFT JOIN
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
				ON
				authorize_payment_profiles.payment_profile_id = authorize_transactions.payment_profile_id
			LEFT JOIN
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations AS transaction_invnos
				ON
				transaction_invnos.transaction_id = authorize_transactions.transaction_id
				AND
				transaction_invnos.relation_type = 'invno'
			LEFT JOIN
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations AS transaction_sonos
				ON
				transaction_sonos.transaction_id = authorize_transactions.transaction_id
				AND
				transaction_sonos.relation_type = 'sono'
			WHERE
				authorize_transactions.live = " . (ISLIVE ? '1' : '0') . "
				" . (!empty($search_where) ? "AND\r\n\t\t\t\t\t\t\t\t\t" . implode("\r\n\t\t\t\t\t\t\t\t\tAND\r\n\t\t\t\t\t\t\t\t\t", $search_where) : Null) . "
			GROUP BY
				authorize_transactions.transaction_id
		)
	SELECT
		authorize_transactions.transaction_id,
		authorize_transactions.ref_transaction_id,
		authorize_transactions.authorize_transaction_id,
		authorize_transactions.custno,
		authorize_transactions.auth_code,
		authorize_transactions.status,
		authorize_transactions.amount,
		authorize_transactions.added_on,
		authorize_transactions.salesmn,
		authorize_transactions.memo,
		authorize_transactions.last4,
		authorize_transactions.action,
		STUFF(
			(
				SELECT
					',' + CONVERT(VARCHAR(30), authorize_transaction_relations.relation_type_id )
				FROM
					" . DB_SCHEMA_ERP . ".authorize_transaction_relations
				WHERE
					authorize_transaction_relations.transaction_id = authorize_transactions.transaction_id
					AND
					authorize_transaction_relations.relation_type = 'sono'
				FOR
					XML PATH('')
			), 1, 1, ''
		) AS sonos,
		STUFF(
			(
				SELECT
					',' + CONVERT(VARCHAR(30), authorize_transaction_relations.relation_type_id)
				FROM
					" . DB_SCHEMA_ERP . ".authorize_transaction_relations
				WHERE
					authorize_transaction_relations.transaction_id = authorize_transactions.transaction_id
					AND
					authorize_transaction_relations.relation_type = 'invno'
				FOR
					XML PATH('')
			), 1, 1, ''
		) AS invnos,
		authorize_payment_profiles.profile_name AS payment_profile,
		authorize_transactions.payment_profile_id
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transactions
	INNER JOIN
		transactions
		ON
		transactions.transaction_id = authorize_transactions.transaction_id
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
		ON
		authorize_payment_profiles.payment_profile_id = authorize_transactions.payment_profile_id
	ORDER BY
		authorize_transactions.added_on DESC
";
$grab_transactions = $db->query($query);

function renderRow($row) {
	foreach($row as $value) {
		print '"' . str_replace('"', '', $value) . '"';
		print "\t";
	}
	print "\r\n";
}

if(!empty($_POST['download'])) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=\'Transaction Report ' . date('Y-m-d') . '.xls\'');
	renderRow([
		'Date',
		'Client Code',
		'Amount',
		'Auth Code',
		'Last 4',
		'Action',
		'Status',
		'Authorize Transaction ID',
		'User',
		'Memo',
		'Invoice',
		'SO',
		'Internal Transaction ID',
		'Ref Internal Transaction ID'
	]);
}

$transactions = [];

$current_date = date('Y-m-d');

foreach($grab_transactions as $transaction_row) {
	$added_on = date('Y-m-d', strtotime($transaction_row['added_on']));
	if(!empty($_POST['download'])) {
		renderRow([
			$added_on,
			$transaction_row['custno'],
			$transaction_row['amount'],
			$transaction_row['auth_code'],
			$transaction_row['last4'],
			$transaction_row['action'],
			$transaction_row['status'],
			$transaction_row['authorize_transaction_id'],
			$transaction_row['salesmn'],
			$transaction_row['memo'],
			$transaction_row['invnos'],
			$transaction_row['sonos'],
			$transaction_row['transaction_id'],
			$transaction_row['authorize_transaction_id'],
		]);
	} else {
		$sonos = explode(',', $transaction_row['sonos']);
		$invnos = explode(',', $transaction_row['invnos']);
		$allow_refund = $current_date == $added_on ? False : True;
		$allow_void = $current_date == $added_on ? True : False;
		$transaction = [
			'transaction_id' => $transaction_row['transaction_id'],
			'authorize_transaction_id' => $transaction_row['authorize_transaction_id'],
			'custno' => $transaction_row['custno'],
			'auth_code' => $transaction_row['auth_code'],
			'status' => $transaction_row['status'],
			'action' => $transaction_row['action'],
			'amount' => $transaction_row['amount'],
			'added_on' => $added_on,
			'salesmn' => $transaction_row['salesmn'],
			'memo' => $transaction_row['memo'],
			'sonos' => $sonos,
			'invnos' => $invnos,
			'last4' => $transaction_row['last4'],
			'payment_profile' => $transaction_row['payment_profile'],
			'payment_profile_id' => $transaction_row['payment_profile_id'],
			'allow_void' => $allow_void,
			'allow_refund' => $allow_refund
		];
		$transactions[] = $transaction;
	}
}

if(!empty($_POST['download'])) {
	exit;
}

print json_encode([
	'success' => True,
	'transactions' => $transactions,
	'query' => $query
]);
