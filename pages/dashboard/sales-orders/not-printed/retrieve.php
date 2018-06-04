<?php

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'not-printed' => array()
);

$grab_sales_orders = $db->query("
	SELECT
		somast.sono                    AS sales_order_number, -- Sales Order Number
		somast.terr                    AS territory,          -- Warehouse Territory
		somast.defloc                  AS location,           -- Warehouse Location
		somast.custno                  AS customer_code,      -- Customer Code
		somast.ponum                   AS customer_po,        -- Customer Purchase Order Number
		LTRIM(RTRIM(somast.salesmn))   AS sales_person,       -- Sales Person
		somast.adduser                 AS who_entered,        -- Who Entered
		CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
		CONVERT(varchar(10), somast.ordate, 120) AS due_date  -- Due Date
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.printed = '1900-01-01'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
");

$sales_orders = array();
foreach($grab_sales_orders as $sales_order) {
	$response['not-printed'][] = $sales_order;
}

print json_encode($response);
