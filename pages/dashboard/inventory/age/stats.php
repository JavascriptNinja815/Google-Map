<?php

ini_set('max_execution_time', 3000);

$datetime = date('n/j/Y \a\t g:i:sa', time());

$items = [];
if(!empty($_POST['items'])) {
	foreach($_POST['items'] as $item) {
		$items[] = $db->quote($item);
	}
}
$items = implode(', ', $items);

// TODO: Can we use these instead of DATEDIFF? Queries are SLOW.
$days_ago_30 = date('Y-m-d', strtotime('30 days ago'));
$days_ago_60 = date('Y-m-d', strtotime('60 days ago'));
$days_ago_90 = date('Y-m-d', strtotime('90 days ago'));
$days_ago_120 = date('Y-m-d', strtotime('120 days ago'));
$days_ago_365 = date('Y-m-d', strtotime('365 days ago'));
$days_ago_547 = date('Y-m-d', strtotime('547 days ago'));
$days_ago_730 = date('Y-m-d', strtotime('730 days ago'));

$base_query = "
			SELECT
				SUM(iccost.conhand) as qty
			FROM
				" . DB_SCHEMA_ERP . ".icitem
			INNER JOIN
				" . DB_SCHEMA_ERP . ".iccost
				ON
				iccost.item = icitem.item
			INNER JOIN
				" . DB_SCHEMA_ERP . ".iciloc
				ON
				iciloc.item = icitem.item
			WHERE
				iccost.conhand != 0
				" . ($_REQUEST['loctid'] != '*' ? "AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY
				iccost.item
			HAVING
";

$grab_quantities = $db->query("
	WITH
		aged_0 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 30
		),
		aged_31 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 31
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 60
		),
		aged_61 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 61
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 90
		),
		aged_91 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 91
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 120
		),
		aged_121 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 121
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 365
		),
		aged_366 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 366
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 547
		),
		aged_548 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 548
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 730
		),
		aged_731 AS (
				" . $base_query . "
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 731
		)
	SELECT
		(SELECT SUM(qty) FROM aged_0) AS aged_0,
		(SELECT SUM(qty) FROM aged_31) AS aged_31,
		(SELECT SUM(qty) FROM aged_61) AS aged_61,
		(SELECT SUM(qty) FROM aged_91) AS aged_91,
		(SELECT SUM(qty) FROM aged_121) AS aged_121,
		(SELECT SUM(qty) FROM aged_366) AS aged_366,
		(SELECT SUM(qty) FROM aged_548) AS aged_548,
		(SELECT SUM(qty) FROM aged_731) AS aged_731
");
$db_quantities = $grab_quantities->fetch();

$quantities = [
	'aged_0' => number_format($db_quantities['aged_0'], 0),
	'aged_31' => number_format($db_quantities['aged_31'], 0),
	'aged_61' => number_format($db_quantities['aged_61'], 0),
	'aged_91' => number_format($db_quantities['aged_91'], 0),
	'aged_121' => number_format($db_quantities['aged_121'], 0),
	'aged_366' => number_format($db_quantities['aged_366'], 0),
	'aged_548' => number_format($db_quantities['aged_548'], 0),
	'aged_731' => number_format($db_quantities['aged_731'], 0),
	'total' => number_format(
		$db_quantities['aged_0']   +
		$db_quantities['aged_31']  +
		$db_quantities['aged_61']  +
		$db_quantities['aged_91']  +
		$db_quantities['aged_121'] +
		$db_quantities['aged_366'] +
		$db_quantities['aged_548'] +
		$db_quantities['aged_731']
		,
		0
	)
];

$grab_values = $db->query("
	WITH
		aged_0 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 30
		),
		aged_31 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 31
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 60
		),
		aged_61 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 61
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 90
		),
		aged_91 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 91
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 120
		),
		aged_121 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 121
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 365
		),
		aged_366 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 366
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 547
		),
		aged_548 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 548
				AND
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) <= 730
		),
		aged_731 AS (
			SELECT SUM(iccost.cost * iccost.conhand) as value
			FROM " . DB_SCHEMA_ERP . ".icitem
			INNER JOIN " . DB_SCHEMA_ERP . ".iccost ON iccost.item = icitem.item
			" . (!empty($_REQUEST['loctid']) && $_REQUEST['loctid'] != '*' ? "INNER JOIN " . DB_SCHEMA_ERP . ".iciloc ON iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) : Null) . "
			WHERE iccost.conhand != 0
				" . (!empty($items) ? "AND iccost.item IN (" . $items . ")" : Null) . "
			GROUP BY iccost.item
			HAVING
				(SUM(DATEDIFF(day, iccost.adddate, GETDATE()) * iccost.conhand) / SUM(iccost.conhand)) >= 731
		)
	SELECT
		(SELECT SUM(value) FROM aged_0) AS aged_0,
		(SELECT SUM(value) FROM aged_31) AS aged_31,
		(SELECT SUM(value) FROM aged_61) AS aged_61,
		(SELECT SUM(value) FROM aged_91) AS aged_91,
		(SELECT SUM(value) FROM aged_121) AS aged_121,
		(SELECT SUM(value) FROM aged_366) AS aged_366,
		(SELECT SUM(value) FROM aged_548) AS aged_548,
		(SELECT SUM(value) FROM aged_731) AS aged_731
");
$db_values = $grab_values->fetch();

$values = [
	'aged_0' => '$' . number_format($db_values['aged_0'], 2),
	'aged_31' => '$' . number_format($db_values['aged_31'], 2),
	'aged_61' => '$' . number_format($db_values['aged_61'], 2),
	'aged_91' => '$' . number_format($db_values['aged_91'], 2),
	'aged_121' => '$' . number_format($db_values['aged_121'], 2),
	'aged_366' => '$' . number_format($db_values['aged_366'], 2),
	'aged_548' => '$' . number_format($db_values['aged_548'], 2),
	'aged_731' => '$' . number_format($db_values['aged_731'], 2),
	'total' => '$' . number_format(
		$db_values['aged_0']   +
		$db_values['aged_31']  +
		$db_values['aged_61']  +
		$db_values['aged_91']  +
		$db_values['aged_121'] +
		$db_values['aged_366'] +
		$db_values['aged_548'] +
		$db_values['aged_731']
		,
		2
	)
];

print json_encode([
	'success' => True,
	'datetime' => $datetime,
	'quantities' => $quantities,
	'values' => $values
]);
