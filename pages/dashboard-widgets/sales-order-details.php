<?php

$date_format = 'Y-m-d';
$now = time();
$yesterday = date($date_format, strtotime('yesterday'));
$today = date($date_format, $now);
$tomorrow = date($date_format, strtotime('tomorrow'));
$first_of_month = date($date_format, strtotime(date('Y-m-01')));
$first_of_year = date($date_format, strtotime(date('Y-01-01')));
$beginning_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-01-01'));
$end_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-12-31'));

if($_REQUEST['block'] == 'Past Due') {
	/**
	 * PAST DUE
	 */
	$grab_sales_orders = $db->query("
		SELECT
			somast.sono                    AS sales_order_number,    -- Sales Order Number
			somast.orderstat               AS status,                -- SO Status
			somast.terr                    AS territory,             -- Warehouse Territory
			somast.defloc                  AS location,              -- Warehouse Location
			somast.custno                  AS customer_code,         -- Customer Code
			somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
			somast.adduser                 AS who_entered,           -- Who Entered
			CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
			CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
			somast.sostat                  AS sales_order_status,    -- Sales Order Status
			somast.sotype                  AS sales_order_type      -- Sales Order Type
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.ordate <= " . $db->quote($yesterday) . "
			AND
			RTRIM(LTRIM(somast.defloc)) = 'DC'
			AND
			RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
			AND
			RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
			AND
			RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
			AND
			RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
			--RTRIM(LTRIM(somast.salesmn)) = 'JTS'
	");

	print json_encode(array(
		'success' => True,
		'sales-orders' => $grab_sales_orders->fetchAll()
	));
} else if($_REQUEST['block'] == 'Today') {
	/**
	 * TODAY
	 */
	$grab_sales_orders = $db->query("
		SELECT
			somast.sono                    AS sales_order_number,    -- Sales Order Number
			somast.orderstat               AS status,                -- SO Status
			somast.terr                    AS territory,             -- Warehouse Territory
			somast.defloc                  AS location,              -- Warehouse Location
			somast.custno                  AS customer_code,         -- Customer Code
			somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
			somast.adduser                 AS who_entered,           -- Who Entered
			CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
			CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
			somast.sostat                  AS sales_order_status,    -- Sales Order Status
			somast.sotype                  AS sales_order_type      -- Sales Order Type
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.ordate = " . $db->quote($today) . "
			AND
			RTRIM(LTRIM(somast.defloc)) = 'DC'
			AND
			RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
			AND
			RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
			AND
			RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
			AND
			RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
			--RTRIM(LTRIM(somast.salesmn)) = 'JTS'
	");

	print json_encode(array(
		'success' => True,
		'sales-orders' => $grab_sales_orders->fetchAll()
	));
} else if($_REQUEST['block'] == 'At Risk') {
	/**
	 * AT RISK
	 */
	$grab_sales_orders = $db->query("
		SELECT
			somast.sono                    AS sales_order_number,    -- Sales Order Number
			somast.orderstat               AS status,                -- SO Status
			somast.terr                    AS territory,             -- Warehouse Territory
			somast.defloc                  AS location,              -- Warehouse Location
			somast.custno                  AS customer_code,         -- Customer Code
			somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
			somast.adduser                 AS who_entered,           -- Who Entered
			CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
			CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
			somast.sostat                  AS sales_order_status,    -- Sales Order Status
			somast.sotype                  AS sales_order_type      -- Sales Order Type
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.ordate BETWEEN " . $db->quote($today) . " AND " . $db->quote($tomorrow) . "
			AND
			RTRIM(LTRIM(somast.defloc)) = 'DC'
			AND
			RTRIM(LTRIM(somast.orderstat)) IN ('NSP', 'SSP', 'ON HOLD', 'OTHER', 'PURCHASING', 'STAGED', 'TRANSFER', 'VENDOR')
			AND
			RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
			--RTRIM(LTRIM(somast.salesmn)) = 'JTS'

			-- BELOW SHOULD ALWAYS BE PRESENT
			AND
			RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
			AND
			RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
	");

	print json_encode(array(
		'success' => True,
		'sales-orders' => $grab_sales_orders->fetchAll()
	));
} else if($_REQUEST['block'] == 'MTD Clients' || $_REQUEST['block'] == 'MTD Clients $250+') {
	/**
	 * MTD CLIENTS AND MTD CLIENTS $250+
	 */
	$grab_sales_orders = $db->query("
		WITH
			this_month AS (
				SELECT
					artran.custno,
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
					artran.invdte BETWEEN " . $db->quote($first_of_month) . " AND " . $db->quote($today) . "
					AND
					RTRIM(LTRIM(arcust.salesmn)) = " . $db->quote($session->login['initials']) . "
					--RTRIM(LTRIM(arcust.salesmn)) = 'JTS'
				GROUP BY
					artran.custno
				HAVING
					SUM(
						ISNULL(artran.extprice, 0.00)
					) > 0
			),
			this_year AS (
				SELECT
					artran.custno,
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
					artran.invdte BETWEEN " . $db->quote($first_of_year) . " AND " . $db->quote($today) . "
					AND
					RTRIM(LTRIM(arcust.salesmn)) = " . $db->quote($session->login['initials']) . "
					--RTRIM(LTRIM(arcust.salesmn)) = 'JTS'
				GROUP BY
					artran.custno
				HAVING
					SUM(
						ISNULL(artran.extprice, 0.00)
					) > 0
			),
			last_year AS (
				SELECT
					artran.custno,
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
					artran.invdte BETWEEN " . $db->quote($beginning_of_last_year) . " AND " . $db->quote($end_of_last_year) . "
					AND
					RTRIM(LTRIM(arcust.salesmn)) = " . $db->quote($session->login['initials']) . "
					--RTRIM(LTRIM(arcust.salesmn)) = 'JTS'
				GROUP BY
					artran.custno
				HAVING
					SUM(
						ISNULL(artran.extprice, 0.00)
					) > 0
			)
		SELECT
			this_year.custno AS client,
			this_month.sales AS sales_thismonth,
			this_year.sales AS sales_thisyear,
			last_year.sales AS sales_lastyear,
			this_year.sales - last_year.sales AS number_change,
			(
				(
					CAST(this_year.sales AS Float) - CAST(last_year.sales AS Float)
				) / ABS(CAST(last_year.sales AS Float))
			) * 100.00 as percent_change
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
	$sales_orders = array();
	
	foreach($grab_sales_orders as $sales_order) {
		$sales_orders[] = array(
			'client' => $sales_order['client'],
			'sales_thismonth' => number_format($sales_order['sales_thismonth'], 2),
			'sales_thisyear' => number_format($sales_order['sales_thisyear'], 2),
			'sales_lastyear' => number_format($sales_order['sales_lastyear'], 2),
			'number_change' => number_format($sales_order['number_change'], 2),
			'percent_change' => number_format($sales_order['percent_change'], 1)
		);
	}

	print json_encode(array(
		'success' => True,
		'sales-orders' => $sales_orders
	));
}
