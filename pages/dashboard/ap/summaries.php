<?php

$session->ensureLogin();
$session->ensureRole('Accounting');

$invoice_numbers = [];
if(!empty($_POST['invoice_numbers'])) {
	foreach($_POST['invoice_numbers'] as $invoice_number) {
		$invoice_numbers[] = $db->quote($invoice_number);
	}
}

$one_day = 86400;

$date_today = date('Y-m-d', time());
$date_15 = date('Y-m-d', strtotime('15 days ago'));
$date_30 = date('Y-m-d', strtotime('30 days ago'));
$date_45 = date('Y-m-d', strtotime('45 days ago'));
$date_60 = date('Y-m-d', strtotime('60 days ago'));
$date_75 = date('Y-m-d', strtotime('75 days ago'));
$date_90 = date('Y-m-d', strtotime('90 days ago'));
$date_105 = date('Y-m-d', strtotime('105 days ago'));
$date_120 = date('Y-m-d', strtotime('120 days ago'));

$grab_lessthanfifteen = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate <= " . $db->quote($date_today) . "
		AND
		apmast.duedate >= " . $db->quote($date_15) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$lessthanfifteen = $grab_lessthanfifteen->fetch();

$grab_fifteen = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate <= " . $db->quote($date_15) . "
		AND
		apmast.duedate >= " . $db->quote($date_30) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$fifteen = $grab_fifteen->fetch();

$grab_thirty = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_30) . "
		AND
		apmast.duedate >= " . $db->quote($date_45) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$thirty = $grab_thirty->fetch();

$grab_fortyfive = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_45) . "
		AND
		apmast.duedate >= " . $db->quote($date_60) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$fortyfive = $grab_fortyfive->fetch();

$grab_sixty = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_60) . "
		AND
		apmast.duedate >= " . $db->quote($date_75) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$sixty = $grab_sixty->fetch();

$grab_seventyfive = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_75) . "
		AND
		apmast.duedate >= " . $db->quote($date_90) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$seventyfive = $grab_seventyfive->fetch();

$grab_ninety = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_90) . "
		AND
		apmast.duedate >= " . $db->quote($date_105) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$ninety = $grab_ninety->fetch();

$grab_oneohfive = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_105) . "
		AND
		apmast.duedate >= " . $db->quote($date_120) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$oneohfive = $grab_oneohfive->fetch();

$grab_onetwenty = $db->query("
	SELECT TOP 100
		SUM(apmast.puramt - apmast.paidamt - apmast.disamt) AS amount
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt > 0
		AND
		apmast.duedate < " . $db->quote($date_120) . "
		" . (!empty($invoice_numbers) ? "AND LTRIM(apmast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
");
$onetwenty = $grab_onetwenty->fetch();

print json_encode([
	'success' => True,
	'summaries' => [
		'lessthanfifteen' => number_format($lessthanfifteen['amount'], 2),
		'fifteen' => number_format($fifteen['amount'], 2),
		'thirty' => number_format($thirty['amount'], 2),
		'fortyfive' => number_format($fortyfive['amount'], 2),
		'sixty' => number_format($sixty['amount'], 2),
		'seventyfive' => number_format($seventyfive['amount'], 2),
		'ninety' => number_format($ninety['amount'], 2),
		'oneohfive' => number_format($oneohfive['amount'], 2),
		'onetwenty' => number_format($onetwenty['amount'], 2),
		'total' => number_format(
			$lessthanfifteen['amount'] +
			$fifteen['amount'] +
			$thirty['amount'] +
			$fortyfive['amount'] +
			$sixty['amount'] +
			$seventyfive['amount'] +
			$ninety['amount'] +
			$oneohfive['amount'] +
			$onetwenty['amount']
		, 2)
	]
]);
