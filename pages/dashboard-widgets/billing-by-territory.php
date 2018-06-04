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
$priormonth_month = date('F', strtotime('Last Month'));
$priormonth_year = $priormonth_month == 'December' ? $current_year - 1 : $current_year;
$last_year = $current_year - 1;
$grab_territory_sales = $db->query("
	WITH
		prior_month AS (
			SELECT
				arcust.terr AS territory,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
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
						strtotime($priormonth_month . ' 1, ' . $priormonth_year) // Ex: February 1, 2015
					)
				) . "
				AND
				artran.invdte < " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_month . ' 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
				AND
				RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
			GROUP BY
				arcust.terr
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		this_month AS (
			SELECT
				arcust.terr AS territory,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
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
				AND
				RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
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
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
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
				AND
				RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
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
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
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
				AND
				RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
			GROUP BY
				arcust.terr
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		)
	SELECT
		this_year.territory,
		prior_month.sales AS sales_priormonth,
		this_month.sales AS sales_thismonth,
		this_year.sales AS sales_thisyear,
		last_year.sales AS sales_lastyear,
		this_year.sales - last_year.sales AS dollar_change,
		((this_year.sales - last_year.sales) / ABS(last_year.sales)) * 100 as percent_change
	FROM
		this_year
	LEFT JOIN
		prior_month
		ON
		this_year.territory = prior_month.territory
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
$total_priormonth = 0;
$total_thismonth = 0;
$total_thisyear = 0;
$total_lastyear = 0;
if(!$session->hasRole('Administration')) {
	$clickable_permissions = $session->getPermissions('Sales', 'view-orders');
}
foreach($grab_territory_sales as $sales) {
	//$dollar_change = $sales['sales_thisyear'] - $sales['sales_lastyear'];
	//$percent_change = ($dollar_change / $sales['sales_thisyear']) * 100;
	$total_priormonth += $sales['sales_priormonth'];
	$total_thismonth += $sales['sales_thismonth'];
	$total_thisyear += $sales['sales_thisyear'];
	$total_lastyear += $sales['sales_lastyear'];

	$response['territories'][] = array(
		'territory' => htmlentities($sales['territory']),
		'sales-priormonth' => number_format($sales['sales_priormonth'], 2),
		'sales-thismonth' => number_format($sales['sales_thismonth'], 2),
		'sales-thisyear' => number_format($sales['sales_thisyear'], 2),
		'sales-lastyear' => number_format($sales['sales_lastyear'], 2),
		'change-percentage' => number_format($sales['percent_change'], 1),
		'change-dollars' => number_format($sales['dollar_change'], 2)
	);
}
$total_dollar_change = $total_thisyear - $total_lastyear;
$total_percent_change = ($total_dollar_change / $total_thisyear) * 100;

$response['total'] = array(
	'sales-priormonth' => number_format($total_priormonth, 2),
	'sales-thismonth' => number_format($total_thismonth, 2),
	'sales-thisyear' => number_format($total_thisyear, 2),
	'sales-lastyear' => number_format($total_lastyear, 2),
	'change-percentage' => number_format($total_percent_change, 1),
	'change-dollars' => number_format($total_dollar_change, 2)
);

print json_encode($response);
