<?php

$today = date('Y-m-d', time());

$grab_data = $db->query("
	SELECT
		COUNT(*) AS count,
		LTRIM(RTRIM(somast.defloc)) AS location -- Warehouse Location
	FROM
		" . DB_SCHEMA_ERP . ".somast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".sotran
		ON
		sotran.id_col = (
			-- This query ensures multiple rows aren't encountered matching in `sotran`.
			SELECT
				TOP (1) id_col
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			WHERE
				sono = somast.sono
		)
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.id_col = (
			-- This query ensures multiple rows aren't encountered matching in `arcust`.
			SELECT
				TOP (1) id_col
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			WHERE
				custno = somast.custno
		)
	WHERE
		somast.orderstat IN ( -- TODO: Should this be exclusive rather than inclusive?
			'BACKORDER','HOLD','ISS','NSP','ON HOLD','OTHER','PICKING',
			'PRODUCTION','PURCHASING','Q''D - CUSTOM','QCUSTOM','QUEUED',
			'SHIPPING','SSP','STAGED','STAGED FOR SHIP','TRANSFER','VENDOR',
			'WAIT ON PAYMENT','WAIT ON PICKUP','WAIT ON PRODUCT',
			'WAIT ON TRANSFR','WAITING: TRANS','WAITING: VENDOR',''
		)
		AND
		(
			(
				(somast.sotype IS NULL OR somast.sotype = '')
				AND
				(somast.sostat IS NULL OR somast.sostat = '')
			)
			OR
			(
				somast.sotype = 'O'
				AND
				(somast.sostat IS NULL
				OR
				somast.sostat != 'C')
			)
		)
		AND 
		somast.sostat NOT IN ('V', 'ISS', 'SSP', 'NSP') -- V = Void, ISS = In Stock; Ship, SSP = Stage Stock &amp; Purchase, NSP = Non-Stock; Purchase
		AND
		somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
		AND
		somast.ordate < " . $db->quote($today) . " -- Due Date is today (after work day, SO is late) or later
	GROUP BY
		LTRIM(RTRIM(somast.defloc))
	ORDER BY
		LTRIM(RTRIM(somast.defloc))
");
$late = [];
foreach($grab_data as $data) {
	$late[] = [
		'location' => $data['location'],
		'count' => $data['count']
	];
}

print json_encode([
	'success' => True,
	'data' => [
		'late' => $late
	]
]);
