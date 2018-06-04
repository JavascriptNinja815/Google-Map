<?php

$orders = array(
	'hot-today' => array(),
	'hot-tomorrow' => array(),
	'today' => array(),
	'backlog' => array(),
	'iss' => array(),
	'usps' => array(),
	'fedex' => array(),
	'ups' => array()
);

/**
 * HOT TODAY
 */
$grab_hot_today_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		somast.hot IS NOT NULL
		AND
		somast.hot > 0
");
foreach($grab_hot_today_orders as $order) {
	$orders['hot-today'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * HOT TOMORROW
 */
$grab_hot_tomorrow_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time() + 86400) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		somast.hot IS NOT NULL
		AND
		somast.hot > 0
");
foreach($grab_hot_tomorrow_orders as $order) {
	$orders['hot-tomorrow'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * TODAY
 */
$grab_today_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) IN ('ISS', 'PICKING', 'PRODUCTION', 'Q''D - CUSTOM', 'QCUSTOM', 'QUEUED')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		(
			somast.hot IS NULL
			OR
			somast.hot = 0
		)
");
foreach($grab_today_orders as $order) {
	$orders['today'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * BACKLOG
 */
$grab_backlog_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate <= '" . date('Y-m-d', time() - 86400) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		(
			somast.hot IS NULL
			OR
			somast.hot = 0
		)
");
foreach($grab_backlog_orders as $order) {
	$orders['backlog'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * ISS - In Stock Ship
 */
$grab_iss_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) = 'ISS'
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		(
			somast.hot IS NULL
			OR
			somast.hot = 0
		)
");
foreach($grab_iss_orders as $order) {
	$orders['iss'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * CARRIER: USPS
 */
$grab_usps_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.shipvia)) IN ('USPS-PM')
		AND
		RTRIM(LTRIM(somast.orderstat)) IN ('ISS', 'PICKING', 'Q''D - CUSTOM', 'QCUSTOM', 'QUEUED', 'SHIPPING')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
");
foreach($grab_usps_orders as $order) {
	$orders['usps'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * CARRIER: FEDEX
 */
$grab_fedex_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.shipvia)) IN ('FDX HOME DLR', 'FDX GROUND', 'FEDEX GROUND')
		AND
		RTRIM(LTRIM(somast.orderstat)) IN ('ISS', 'PICKING', 'Q''D - CUSTOM', 'QCUSTOM', 'QUEUED', 'SHIPPING')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
");
foreach($grab_fedex_orders as $order) {
	$orders['fedex'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

/**
 * CARRIER: UPS
 */
$grab_ups_orders = $db->query("
	SELECT DISTINCT
		RTRIM(LTRIM(somast.sono)) AS sales_order_number,
		RTRIM(LTRIM(somast.custno)) AS customer,
		RTRIM(LTRIM(somast.orderstat)) AS order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.shipvia)) IN ('UPS GROUND')
		AND
		RTRIM(LTRIM(somast.orderstat)) IN ('ISS', 'PICKING', 'Q''D - CUSTOM', 'QCUSTOM', 'QUEUED', 'SHIPPING')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
");
foreach($grab_ups_orders as $order) {
	$orders['ups'][] = array(
		'order-number' => $order['sales_order_number'],
		'customer' => $order['customer'],
		'order-status' => $order['order_status']
	);
}

print json_encode($orders);
