<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$current_year = date('Y', time());
$current_month = date('F', time());
$current_monthdate = date('F j', time());
$last_year = $current_year - 1;

$grab_customer_sales = $db->query("
	WITH
		this_month AS (
			SELECT
				arcust.custno,
				arcust.company,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				soslsm.salesmn = " . $db->quote($_POST['salesman']) . "
				AND
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_month . ' 1, ' . $current_year) // Ex: February 1, 2015
					)
				) . "
			GROUP BY
				arcust.custno,
				arcust.company
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		this_year AS (
			SELECT
				arcust.custno,
				arcust.company,
				arcust.onorder,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				soslsm.salesmn = " . $db->quote($_POST['salesman']) . "
				AND
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $current_year)
					)
				) . "
			GROUP BY
				arcust.custno,
				arcust.company,
				arcust.onorder
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		last_year AS (
			SELECT
				arcust.custno,
				arcust.company,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				soslsm.salesmn = " . $db->quote($_POST['salesman']) . "
				AND
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $last_year)
					)
				) . "
				AND
				artran.invdte < " . $db->quote(
					date(
						'Y-m-d',
						strtotime($current_monthdate . ', ' . $last_year)
					)
				) . "
			GROUP BY
				arcust.custno,
				arcust.company
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		)
	SELECT
		ISNULL(this_year.custno, ISNULL(last_year.custno, '???')) AS customer_code,
		ISNULL(this_year.company, ISNULL(last_year.company, '???')) AS customer_name,
		ISNULL(this_month.sales, 0.00) AS sales_thismonth,
		ISNULL(this_year.sales, 0.00) AS sales_thisyear,
		ISNULL(last_year.sales, 0.00) AS sales_lastyear,
		this_year.sales - last_year.sales AS dollar_change,
		((this_year.sales - last_year.sales) / ABS(last_year.sales)) * 100 as percent_change,
		this_year.onorder AS open_orders,
		(
			SELECT TOP 1
				CONVERT(varchar(10), artran.adddate, 120)
			FROM
				" . DB_SCHEMA_ERP . ".artran
			WHERE
				artran.custno = this_year.custno
				OR
				artran.custno = last_year.custno
			ORDER BY
				artran.adddate DESC
		) AS last_sale_date
	FROM
		this_year
	LEFT JOIN
		last_year
		ON
		this_year.custno = last_year.custno
	LEFT JOIN
		this_month
		ON
		this_year.custno = this_month.custno
	ORDER BY
		percent_change DESC
");

$result = array(
	'success' => True,
	'customer-sales' => array()
);

foreach($grab_customer_sales as $customer_sales) {
	$customer_sales['sales-thismonth-formatted'] = number_format($customer_sales['sales_thismonth'], 2);
	$customer_sales['sales-thisyear-formatted'] = number_format($customer_sales['sales_thisyear'], 2);
	$customer_sales['sales-lastyear-formatted'] = number_format($customer_sales['sales_lastyear'], 2);
	$customer_sales['dollar-change-formatted'] = number_format($customer_sales['dollar_change'], 2);
	$customer_sales['percent-change-formatted'] = number_format($customer_sales['percent_change'], 1);
	$customer_sales['open_orders'] = number_format($customer_sales['open_orders'], 0);
	$result['customer-sales'][] = $customer_sales;
}

print json_encode($result);
