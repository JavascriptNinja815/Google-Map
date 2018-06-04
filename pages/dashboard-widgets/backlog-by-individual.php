<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'salesmen' => array()
);

function get_next_month_beginning($year, $month){

	// This is a hack to accomodate "next month" values in December.
	// Before this, in December 2017, "next month" would be calculated
	// to be "2017-01-01". That's not a typo - 2017 instead of 2018.
	//
	// There is a better way to do this, but this hack works for now.

	// If its january, increment year too.
	if($month == 'January'){$year += 1;};

	// Get the date string.
	return date('Y-m-d', strtotime($month .' '. $year));

};

function get_next_month_end($year, $month){

	// This is a hack to accomodate "next month" values in December.
	// Before this, in December 2017, "next month" would be calculated
	// to be "2017-01-01". That's not a typo - 2017 instead of 2018.
	//
	// There is a better way to do this, but this hack works for now.

	// If its january, increment year too.
	if($month == 'January'){$year += 1;};

	return date('Y-m-d', strtotime('last day of '  . $month . ' ' . $year));

};

$current_year = date('Y', time());
$current_month = date('F', time());
$current_monthdate = date('F j', time());
$last_year = $current_year - 1;

$next_month = date('F', strtotime($current_month . ' ' . $current_year . ' + 1 month'));

$today = date(
	'Y-m-d',
	strtotime($current_monthdate)
);
$today_plus_six = date(
	'Y-m-d',
	strtotime($current_monthdate . ' + 6 days')
);
$this_month_beginning = date(
	'Y-m-d',
	strtotime('first day of '  . $current_month . ' ' . $current_year)
);
$this_month_end = date(
	'Y-m-d',
	strtotime('last day of '  . $current_month . ' ' . $current_year)
);
/*
$next_month_beginning = date(
	'Y-m-d',
	strtotime($next_month)
);
$next_month_end = date(
	'Y-m-d',
	strtotime('last day of '  . $next_month . ' ' . $current_year)
);
*/

$next_month_beginning = get_next_month_beginning($current_year, $next_month);
$next_month_end = get_next_month_end($current_year, $next_month);

$grab_salesman_counts = $db->query("
	WITH
		before_today AS (
			SELECT
				sotran.salesmn,
				SUM(
					(
						COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
					) * COALESCE(sotran.tprice, 0)
				) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				sotran.sono = somast.sono
			WHERE
				sotran.sostat NOT IN ('C', 'V', 'X')
				AND
				somast.sotype IN ('', 'O', 'R')
				AND
				sotran.rqdate < " . $db->quote($today) . "
				AND
				(sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
				AND
				sotran.tqtyord != 0.00
			GROUP BY
				sotran.salesmn
		),
		today_plux_six AS (
			SELECT
				sotran.salesmn,
				SUM(
					(
						COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
					) * COALESCE(sotran.tprice, 0)
				) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				sotran.sono = somast.sono
			WHERE
				sotran.sostat NOT IN ('C', 'V', 'X')
				AND
				somast.sotype IN ('', 'O', 'R')
				AND
				sotran.rqdate >= " . $db->quote($today) . "
				AND
				sotran.rqdate <= " . $db->quote($today_plus_six) . "
				AND
				(sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
				AND
				sotran.tqtyord != 0.00
			GROUP BY
				sotran.salesmn
		),
		this_month AS (
			SELECT
				sotran.salesmn,
				SUM(
					(
						COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
					) * COALESCE(sotran.tprice, 0)
				) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				sotran.sono = somast.sono
			WHERE
				sotran.sostat NOT IN ('C', 'V', 'X')
				AND
				somast.sotype IN ('', 'O', 'R')
				AND
				sotran.rqdate >= " . $db->quote($today) . "
				AND
				sotran.rqdate <= " . $db->quote($this_month_end) . "
				AND
				(sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
				AND
				sotran.tqtyord != 0.00
			GROUP BY
				sotran.salesmn
		),
		next_month AS (
			SELECT
				sotran.salesmn,
				SUM(
					(
						COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
					) * COALESCE(sotran.tprice, 0)
				) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				sotran.sono = somast.sono
			WHERE
				sotran.sostat NOT IN ('C', 'V', 'X')
				AND
				somast.sotype IN ('', 'O', 'R')
				AND
				sotran.rqdate >= " . $db->quote($next_month_beginning) . "
				AND
				sotran.rqdate <= " . $db->quote($next_month_end) . "
				AND
				(sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
				AND
				sotran.tqtyord != 0.00
			GROUP BY
				sotran.salesmn
		),
		total AS (
			SELECT
				sotran.salesmn,
				SUM(
					(
						COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
					) * COALESCE(sotran.tprice, 0)
				) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".somast
				ON
				sotran.sono = somast.sono
			WHERE
				sotran.sostat NOT IN ('C', 'V', 'X')
				AND
				somast.sotype IN ('', 'O', 'R')
				AND
				(sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
				AND
				sotran.tqtyord != 0.00
			GROUP BY
				sotran.salesmn
		)
	SELECT
		total.salesmn,
		ABS(before_today.amount) AS before_today,
		ABS(today_plux_six.amount) AS today_plux_six,
		ABS(this_month.amount) AS this_month,
		ABS(next_month.amount) AS next_month,
		ABS(total.amount) AS total
	FROM
		total
	LEFT JOIN
		before_today
		ON
		before_today.salesmn = total.salesmn
	LEFT JOIN
		today_plux_six
		ON
		today_plux_six.salesmn = total.salesmn
	LEFT JOIN
		this_month
		ON
		this_month.salesmn = total.salesmn
	LEFT JOIN
		next_month
		ON
		next_month.salesmn = total.salesmn
	ORDER BY
		total.salesmn
");

$total_before_today = 0;
$total_today_plux_six = 0;
$total_this_month = 0;
$total_next_month = 0;
$total_total = 0;

if(!$session->hasRole('Administration')) {
	$clickable_permissions = $session->getPermissions('Sales', 'view-orders');
}

foreach($grab_salesman_counts as $sales) {
	$total_before_today += $sales['before_today'];
	$total_today_plux_six += $sales['today_plux_six'];
	$total_this_month += $sales['this_month'];
	$total_next_month += $sales['next_month'];
	$total_total += $sales['total'];

	$response['salesmen'][] = array(
		'salesman' => htmlentities($sales['salesmn']),
		'before-today' => number_format($sales['before_today'], 0),
		'today-plus-six' => number_format($sales['today_plux_six'], 0),
		'this-month' => number_format($sales['this_month'], 2),
		'next-month' => number_format($sales['next_month'], 2),
		'total' => number_format($sales['total'], 2)
	);
}

$response['total'] = array(
	'before-today' => number_format($total_before_today, 0),
	'today-plus-six' => number_format($total_today_plux_six, 0),
	'this-month' => number_format($total_this_month, 2),
	'next-month' => number_format($total_next_month, 2),
	'total' => number_format($total_total, 2),
);

print json_encode($response);
