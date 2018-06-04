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
			SUM(opportunities.amount) AS amount,
			logins.initials AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".logins
			ON
			logins.login_id = opportunities.login_id
		WHERE
			opportunities.entered_date = " . $db->quote(
				date(
					'Y-m-d',
					strtotime('today')
				)
			) . "
		GROUP BY
			logins.initials
	),
	week AS (
		SELECT
			SUM(opportunities.amount) AS amount,
			logins.initials AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".logins
			ON
			logins.login_id = opportunities.login_id
		WHERE
			opportunities.entered_date >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('last Sunday')
				)
			) . "
		GROUP BY
			logins.initials
	),
	month AS (
		SELECT
			SUM(opportunities.amount) AS amount,
			logins.initials AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".logins
			ON
			logins.login_id = opportunities.login_id
		WHERE
			opportunities.entered_date >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('first day of this month')
				)
			) . "
		GROUP BY
			logins.initials
	),
	year AS (
		SELECT
			SUM(opportunities.amount) AS amount,
			logins.initials AS salesman
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".logins
			ON
			logins.login_id = opportunities.login_id
		WHERE
			opportunities.entered_date >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('January 1st')
				)
			) . "
		GROUP BY
			logins.initials
	)
	SELECT DISTINCT
		logins.initials AS salesman,
		today.amount AS today,
		week.amount AS week,
		month.amount AS month,
		year.amount AS year
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = opportunities.login_id
	LEFT JOIN
		today
		ON
		today.salesman = logins.initials
	LEFT JOIN
		week
		ON
		week.salesman = logins.initials
	LEFT JOIN
		month
		ON
		month.salesman = logins.initials
	LEFT JOIN
		year
		ON
		year.salesman = logins.initials
	GROUP BY
		logins.initials,
		today.amount,
		week.amount,
		month.amount,
		year.amount
	ORDER BY
		logins.initials
");

$sales = [];
foreach($grab_salesman_sales as $salesman) {
	$sales[] = [
		'salesman' => $salesman['salesman'],
		'amount-today' => number_format($salesman['today'], 2),
		'amount-week' => number_format($salesman['week'], 2),
		'amount-month' => number_format($salesman['month'], 2),
		'amount-year' => number_format($salesman['year'], 2)
	];
}
$response['opportunities'] = $sales;

print json_encode($response);
