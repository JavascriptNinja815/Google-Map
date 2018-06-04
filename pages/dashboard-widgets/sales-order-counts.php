<?php

$counts = array();

$date_format = 'Y-m-d';
$now = time();
$yesterday = date($date_format, strtotime('yesterday'));
$today = date($date_format, $now);
$tomorrow = date($date_format, strtotime('tomorrow'));
$first_of_month = date($date_format, strtotime(date('Y-m-01')));
$first_of_year = date($date_format, strtotime(date('Y-01-01')));
$beginning_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-01-01'));
$end_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-12-31'));

/**
 * PAST DUE
 */
$grab_pastdue_orders = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate <= " . $db->quote($yesterday) . "
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
		--RTRIM(LTRIM(somast.salesmn)) = 'JTS'

		-- BELOW SHOULD ALWAYS BE PRESENT
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
");
$pastdue_orders = $grab_pastdue_orders->fetch();
$counts['pastdue'] = $pastdue_orders['count'];

/**
 * TODAY
 */
$grab_today_orders = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = " . $db->quote($today) . "
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
		--RTRIM(LTRIM(somast.salesmn)) = 'JTS'

		-- BELOW SHOULD ALWAYS BE PRESENT
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
");
$today_orders = $grab_today_orders->fetch();
$counts['today'] = $today_orders['count'];

/**
 * AT RISK
 */
$grab_atrisk_orders = $db->query("
	SELECT
		COUNT(*) AS count
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
$atrisk_orders = $grab_atrisk_orders->fetch();
$counts['atrisk'] = $atrisk_orders['count'];

/**
 * MTD Clients
 */
$grab_mtdclient_orders = $db->query("
	SELECT
		COUNT(DISTINCT arcust.custno) AS count
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
");
$mtdclient_orders = $grab_mtdclient_orders->fetch();
$counts['mtdclients'] = $mtdclient_orders['count'];

/**
 * MTD Clients $250+
 */
$grab_mtdclient250_orders = $db->query("
	WITH client_sales AS (
		SELECT
			SUM(artran.extprice) AS month_sales,
			arcust.custno
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
			arcust.custno
	)
	SELECT
		COUNT(*) AS count
	FROM
		client_sales
	WHERE
		client_sales.month_sales > 250
");
$mtdclient250_orders = $grab_mtdclient250_orders->fetch();
$counts['mtdclients250'] = $mtdclient250_orders['count'];

print json_encode(array(
	'success' => True,
	'counts' => $counts
));
