<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'salesmen' => array()
);

$current_year = date('Y', time());
$current_month = date('F', time());
$current_monthdate = date('F j', time());
$last_year = $current_year - 1;

$grab_salesman_counts = $db->query("
	WITH
		clients_this_month AS (
			SELECT
				arcust.salesmn,
				COUNT(DISTINCT arcust.custno) AS count
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				somast.custno = arcust.custno
			WHERE
				arcust.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_month . ' 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
				AND
				somast.sotype != 'B'
				AND
				somast.sostat != 'V'
			GROUP BY
				arcust.salesmn
		),
		clients_this_year AS (
			SELECT
				arcust.salesmn,
				COUNT(DISTINCT arcust.custno) AS count
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				somast.custno = arcust.custno
			WHERE
				arcust.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
				AND
				somast.sotype != 'B'
				AND
				somast.sostat != 'V'
			GROUP BY
				arcust.salesmn
		),
		sales_orders AS (
			SELECT
				somast.salesmn,
				SUM(somast.ordamt) AS open_orders,
				SUM(somast.shpamt) AS billed_orders
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				somast.custno = arcust.custno
			WHERE
				arcust.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
				AND
				somast.sotype != 'B'
				AND
				somast.sostat != 'V'
			GROUP BY
				somast.salesmn
		)
	SELECT
		clients_this_year.salesmn,
		clients_this_month.count AS newclients_thismonth,
		clients_this_year.count AS newclients_thisyear,
		sales_orders.open_orders,
		sales_orders.billed_orders
	FROM
		clients_this_year
	LEFT JOIN
		clients_this_month
		ON
		clients_this_year.salesmn = clients_this_month.salesmn
	LEFT JOIN
		sales_orders
		ON
		clients_this_year.salesmn = sales_orders.salesmn
	ORDER BY
		sales_orders.billed_orders DESC
");

$total_newclients_thismonth = 0;
$total_newclients_thisyear = 0;
$total_open_orders = 0;
$total_billed_orders = 0;

if(!$session->hasRole('Administration')) {
	$clickable_permissions = $session->getPermissions('Sales', 'view-orders');
}
foreach($grab_salesman_counts as $sales) {
	$total_newclients_thismonth += $sales['newclients_thismonth'];
	$total_newclients_thisyear += $sales['newclients_thisyear'];
	$total_open_orders += $sales['open_orders'];
	$total_billed_orders += $sales['billed_orders'];

	$response['salesmen'][] = array(
		'salesman' => htmlentities($sales['salesmn']),
		'newclients-thismonth' => number_format($sales['newclients_thismonth'], 0),
		'newclients-thisyear' => number_format($sales['newclients_thisyear'], 0),
		'sales-openorders' => number_format($sales['open_orders'], 2),
		'sales-billedorders' => number_format($sales['billed_orders'], 2),
	);
}

$response['total'] = array(
	'newclients-thismonth' => number_format($total_newclients_thismonth, 0),
	'newclients-thisyear' => number_format($total_newclients_thisyear, 0),
	'sales-openorders' => number_format($total_open_orders, 2),
	'sales-billedorders' => number_format($total_billed_orders, 2),
);

print json_encode($response);
