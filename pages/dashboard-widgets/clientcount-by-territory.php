<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'territories' => array()
);

$current_year = date('Y', time());
$current_month = date('F', time());
$current_monthdate = date('F j', time());
$last_year = $current_year - 1;
$grab_territory_counts = $db->query("
	WITH
		this_month AS (
			SELECT
				arcust.terr AS territory,
				COUNT(DISTINCT arcust.custno) AS count
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_month . ' 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
			GROUP BY
				arcust.terr
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		this_year AS (
			SELECT
				arcust.terr AS territory,
				COUNT(DISTINCT arcust.custno) AS count
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $current_year)
					)
				) . "
			GROUP BY
				arcust.terr
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		last_year AS (
			SELECT
				arcust.terr AS territory,
				COUNT(DISTINCT arcust.custno) AS count
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $last_year)
					)
				) . "
				AND
				artran.invdte <= " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_monthdate . ', ' . $last_year)
					)
				) . "
			GROUP BY
				arcust.terr
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		)
	SELECT
		this_year.territory,
		this_month.count AS count_thismonth,
		this_year.count AS count_thisyear,
		last_year.count AS count_lastyear,
		this_year.count - last_year.count AS number_change,
		(
			(
				CAST(this_year.count AS Float) - CAST(last_year.count AS Float)
			) / ABS(CAST(last_year.count AS Float))
		) * 100.00 as percent_change
	FROM
		this_year
	LEFT JOIN
		last_year
		ON
		this_year.territory = last_year.territory
	LEFT JOIN
		this_month
		ON
		this_year.territory = this_month.territory
	ORDER BY
		percent_change DESC
");
$total_thismonth = 0;
$total_thisyear = 0;
$total_lastyear = 0;
if(!$session->hasRole('Administration')) {
	$clickable_permissions = $session->getPermissions('Sales', 'view-orders');
}
foreach($grab_territory_counts as $count) {
	$total_thismonth += $count['count_thismonth'];
	$total_thisyear += $count['count_thisyear'];
	$total_lastyear += $count['count_lastyear'];

	$response['territories'][] = array(
		'territory' => htmlentities($count['territory']),
		'count-thismonth' => number_format($count['count_thismonth'], 0),
		'count-thisyear' => number_format($count['count_thisyear'], 0),
		'count-lastyear' => number_format($count['count_lastyear'], 0),
		'change-percentage' => number_format($count['percent_change'], 1),
		'change-number' => number_format($count['number_change'], 0)
	);
}
$total_number_change = $total_thisyear - $total_lastyear;
$total_percent_change = (float)((float)$total_number_change / (float)$total_thisyear) * 100;

$response['total'] = array(
	'count-thismonth' => number_format($total_thismonth, 0),
	'count-thisyear' => number_format($total_thisyear, 0),
	'count-lastyear' => number_format($total_lastyear, 0),
	'change-percentage' => number_format($total_percent_change, 1),
	'change-number' => number_format($total_number_change, 0)
);

print json_encode($response);
