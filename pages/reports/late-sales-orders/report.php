<?php

$grab_companies = $db->query("
	SELECT
		companies.company_id,
		companies.company,
		companies.dbname
	FROM
		Neuron.dbo.companies
	ORDER BY
		companies.company
");

if(!empty($_GET['date'])) {
	$ordate = $_GET['date'];
} else {
	$ordate = date('Y-m-d', time());
}

print '<h2>Late Sales Orders</h2>';
print '<pre>';
foreach($grab_companies as $company) {
	$grab_lateorders = $db->query("
		SELECT
			LTRIM(RTRIM(somast.sono)) AS sono, -- Sales Order Number
			LTRIM(RTRIM(somast.orderstat)) AS orderstat,       -- Order Status
			DATEDIFF(dd, somast.ordate, getdate()) + 1 AS days_past_due, -- Days past due date
			LTRIM(RTRIM(somast.terr)) AS terr,          -- Warehouse Territory
			LTRIM(RTRIM(somast.defloc)) AS defloc         -- Warehouse Location
		FROM
			" . $company['dbname'] . ".somast
		INNER JOIN
			" . $company['dbname'] . ".sotran
			ON
			sotran.id_col = (
				-- This query ensures multiple rows aren't encountered matching in `sotran`.
				SELECT
					TOP (1) id_col
				FROM
					" . $company['dbname'] . ".sotran
				WHERE
					sono = somast.sono
			)
		INNER JOIN
			" . $company['dbname'] . ".arcust
			ON
			arcust.id_col = (
				-- This query ensures multiple rows aren't encountered matching in `arcust`.
				SELECT
					TOP (1) id_col
				FROM
					" . $company['dbname'] . ".arcust
				WHERE
					custno = somast.custno
			)
		WHERE
			LTRIM(RTRIM(somast.orderstat)) IN ( -- TODO: Should this be exclusive rather than inclusive?
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
			somast.ordate <= " . $db->quote($ordate) . " -- Due Date is today (after work day, so is late) or later
		ORDER BY
			somast.ordate DESC
	");
	foreach($grab_lateorders as $late_order) {
		print "SONO: " . $late_order['sono'] . "\r\n";

		// Insert late order into the log.
		$db->query("
			INSERT INTO
				Neuron.dbo.late_sos
			(
				company_id,
				reported_on,
				sono,
				orderstat,
				days_past_due,
				terr,
				defloc
			) VALUES (
				" . $db->quote($company['company_id']) . ",
				" . $db->quote(date('Y-m-d', time())) . ",
				" . $db->quote($late_order['sono']) . ",
				" . $db->quote($late_order['orderstat']) . ",
				" . $db->quote($late_order['days_past_due']) . ",
				" . $db->quote($late_order['terr']) . ",
				" . $db->quote($late_order['defloc']) . "
			)
		");
	}
}

print '</pre>';

