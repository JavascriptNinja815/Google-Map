<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time())
);

$grab_salesman_sales = $db->query("
	WITH
	today AS (
		SELECT
			SUM(somast.ordamt + somast.shpamt) AS amount,
			LTRIM(RTRIM(somast.salesmn)) AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.sotype != 'B'
			AND
			somast.adddate = " . $db->quote(
				date(
					'Y-m-d',
					strtotime('today')
				)
			) . "
		GROUP BY
			LTRIM(RTRIM(somast.salesmn))
		HAVING
			SUM(
				ISNULL(somast.ordamt, 0.00) + ISNULL(somast.shpamt, 0.00)
			) > 0
	),
	week AS (
		SELECT
			SUM(somast.ordamt + somast.shpamt) AS amount,
			LTRIM(RTRIM(somast.salesmn)) AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.sotype != 'B'
			AND
			somast.adddate >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('last Sunday')
				)
			) . "
		GROUP BY
			LTRIM(RTRIM(somast.salesmn))
		HAVING
			SUM(
				ISNULL(somast.ordamt, 0.00) + ISNULL(somast.shpamt, 0.00)
			) > 0
	),
	month AS (
		SELECT
			SUM(somast.ordamt + somast.shpamt) AS amount,
			LTRIM(RTRIM(somast.salesmn)) AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.sotype != 'B'
			AND
			somast.adddate >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('first day of this month')
				)
			) . "
		GROUP BY
			LTRIM(RTRIM(somast.salesmn))
		HAVING
			SUM(
				ISNULL(somast.ordamt, 0.00) + ISNULL(somast.shpamt, 0.00)
			) > 0
	),
	year AS (
		SELECT
			SUM(somast.ordamt + somast.shpamt) AS amount,
			LTRIM(RTRIM(somast.salesmn)) AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.sotype != 'B'
			AND
			somast.adddate >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('January 1st')
				)
			) . "
		GROUP BY
			LTRIM(RTRIM(somast.salesmn))
		HAVING
			SUM(
				ISNULL(somast.ordamt, 0.00) + ISNULL(somast.shpamt, 0.00)
			) > 0
	)
	SELECT DISTINCT
		LTRIM(RTRIM(somast.salesmn)) AS salesman,
		today.amount AS today,
		week.amount AS week,
		month.amount AS month,
		year.amount AS year
	FROM
		" . DB_SCHEMA_ERP . ".somast
	LEFT JOIN
		today
		ON
		today.salesman = LTRIM(RTRIM(somast.salesmn))
	LEFT JOIN
		week
		ON
		week.salesman = LTRIM(RTRIM(somast.salesmn))
	LEFT JOIN
		month
		ON
		month.salesman = LTRIM(RTRIM(somast.salesmn))
	LEFT JOIN
		year
		ON
		year.salesman = LTRIM(RTRIM(somast.salesmn))
	GROUP BY
		LTRIM(RTRIM(somast.salesmn)),
		today.amount,
		week.amount,
		month.amount,
		year.amount
	HAVING
		SUM(
			ISNULL(today.amount, 0.00) + ISNULL(week.amount, 0.00) + ISNULL(month.amount, 0.00) + ISNULL(year.amount, 0.00)
		) > 0
	ORDER BY
		LTRIM(RTRIM(somast.salesmn))

");

$sales = [];
foreach($grab_salesman_sales as $salesman) {
	$sales[] = [
		'salesman' => $salesman['salesman'],
		'sales-today' => number_format($salesman['today'], 2),
		'sales-week' => number_format($salesman['week'], 2),
		'sales-month' => number_format($salesman['month'], 2),
		'sales-year' => number_format($salesman['year'], 2)
	];
}
$response['sales'] = $sales;

print json_encode($response);
