<?php

$grab_salesorders = $db->query("
	SELECT
		somast.sono AS sono,
		somast.ordamt AS amount,
		somast.ordate AS date
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.custno = " . $db->quote($_POST['custno']) . "
		AND
		somast.sostat != 'V'
		AND
		somast.sotype != 'B'
		AND
		(
			somast.ordamt > 0
			OR
			(
				SELECT
					SUM(armast.balance)
				FROM
					" . DB_SCHEMA_ERP . ".armast
				WHERE
					armast.ornum = somast.sono
			) > 0
		)
	ORDER BY
		somast.ordate ASC
");
$now = new DateTime(date('Y-m-d', time()));
$salesorders = [];
foreach($grab_salesorders as $salesorder) {
	$so_date = new DateTime(date('Y-m-d', strtotime($salesorder['date'])));
	$so_age = $now->diff($so_date);
	$so = [
		'sono' => trim($salesorder['sono']),
		'amount' => $salesorder['amount'],
		'date' => $so_date->format('Y-m-d'),
		'age' => $so_age->days,
		'invoices' => []
	];
	$grab_invoices = $db->query("
		SELECT
			armast.invno AS invno,
			armast.balance AS amount,
			armast.invdte AS date
		FROM
			" . DB_SCHEMA_ERP . ".armast
		WHERE
			armast.ornum = " . $db->quote($salesorder['sono']) . "
			AND
			armast.balance > 0
		ORDER BY
			armast.invdte ASC
	");
	foreach($grab_invoices as $invoice) {
		$invoice_date = new DateTime(date('Y-m-d', strtotime($invoice['date'])));
		$invoice_age = $now->diff($invoice_date);
		$so['invoices'][] = [
			'invno' => trim($invoice['invno']),
			'amount' => $invoice['amount'],
			'date' => $invoice_date->format('Y-m-d'),
			'age' => $invoice_age->days
		];
	}
	$salesorders[] = $so;
}

print json_encode([
	'success' => True,
	'salesorders' => $salesorders
]);
