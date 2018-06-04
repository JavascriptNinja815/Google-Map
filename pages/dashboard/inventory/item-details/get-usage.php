<?php

$session->ensureLogin();

function get_usage_by_location($item, $location){

	// Get the usage details for the report.
	$db = DB::get();
	$q = $db->query("
		DECLARE @today DATE;
		DECLARE @days_30 DATE;
		DECLARE @days_90 DATE;
		DECLARE @days_180 DATE;
		DECLARE @days_365 DATE;
		DECLARE @days_730 DATE;

		DECLARE @item VARCHAR(MAX);
		DECLARE @location VARCHAR(MAX);
		DECLARE @type_wo VARCHAR(MAX);
		DECLARE @type_so VARCHAR(MAX);
		DECLARE @type_transfer_out VARCHAR(MAX);
		DECLARE @type_transfer_in VARCHAR(MAX);
		DECLARE @type_client_returns VARCHAR(MAX);
		DECLARE @type_received_from_vendor VARCHAR(MAX);
		DECLARE @type_return_to_vendor VARCHAR(MAX);

		SET @today = GETDATE();
		SET @days_30 = DATEADD(DAY, -30, @today);
		SET @days_90 = DATEADD(DAY, -90, @today);
		SET @days_180 = DATEADD(DAY, -180, @today);
		SET @days_365 = DATEADD(DAY, -365, @today);
		SET @days_730 = DATEADD(DAY, -730, @today);

		SET @item = ".$db->quote($item).";
		SET @location = ".$db->quote($location).";
		SET @type_wo = 'EI';
		SET @type_so = ' I';
		SET @type_transfer_out = 'TI';
		SET @type_transfer_in = 'TR';
		SET @type_client_returns = 'RR';
		SET @type_received_from_vendor = ' R';
		SET @type_return_to_vendor = 'RI';

		/* WO - Usage */
		WITH wo_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_wo
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), wo_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_wo
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), wo_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_wo
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), wo_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_wo
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), wo_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_wo
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		)

		/* SO Usage */
		, so_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_so
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), so_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_so
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), so_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_so
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), so_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_so
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), so_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_so
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		/* Transfer Out Usage */
		ti_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_out
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), ti_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_out
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), ti_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_out
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), ti_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_out
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), ti_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_out
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		/* Transfer in Usage */
		tr_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_in
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), tr_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_in
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), tr_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_in
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), tr_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_in
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), tr_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_transfer_in
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		/* Client Returns Usage */
		rr_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_client_returns
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), rr_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_client_returns
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), rr_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_client_returns
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), rr_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_client_returns
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), rr_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_client_returns
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		/* Received from Vendor Usage */
		rv_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_received_from_vendor
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), rv_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_received_from_vendor
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), rv_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_received_from_vendor
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), rv_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_received_from_vendor
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), rv_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_received_from_vendor
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		/* Return to Vendor Usage */
		rt_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_return_to_vendor
				AND tdate >= @days_30
				AND loctid = @location
			GROUP BY item
		), rt_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_return_to_vendor
				AND tdate >= @days_90
				AND loctid = @location
			GROUP BY item
		), rt_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_return_to_vendor
				AND tdate >= @days_180
				AND loctid = @location
			GROUP BY item
		), rt_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_return_to_vendor
				AND tdate >= @days_365
				AND loctid = @location
			GROUP BY item
		), rt_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND trantyp = @type_return_to_vendor
				AND tdate >= @days_730
				AND loctid = @location
			GROUP BY item
		),

		item_table AS (
			SELECT @item AS item
		)

		SELECT
			/* WO Quantities. */
			ABS(COALESCE(w30.quantity, 0)) AS w30qty,
			ABS(COALESCE(w90.quantity, 0)) AS w90qty,
			ABS(COALESCE(w180.quantity, 0)) AS w180qty,
			ABS(COALESCE(w365.quantity, 0)) AS w365qty,
			ABS(COALESCE(w730.quantity, 0)) AS w730qty,
			
			/* SO Quantities */
			ABS(COALESCE(s30.quantity, 0)) AS s30qty,
			ABS(COALESCE(s90.quantity, 0)) AS s90qty,
			ABS(COALESCE(s180.quantity, 0)) AS s180qty,
			ABS(COALESCE(s365.quantity, 0)) AS s365qty,
			ABS(COALESCE(s730.quantity, 0)) AS s730qty,

			/* Transfer Out Quantities */
			ABS(COALESCE(ti30.quantity, 0)) AS ti30qty,
			ABS(COALESCE(ti90.quantity, 0)) AS ti90qty,
			ABS(COALESCE(ti180.quantity, 0)) AS ti180qty,
			ABS(COALESCE(ti365.quantity, 0)) AS ti365qty,
			ABS(COALESCE(ti730.quantity, 0)) AS ti730qty,

			/* Transfer In Quantities */
			ABS(COALESCE(tr30.quantity, 0)) AS tr30qty,
			ABS(COALESCE(tr90.quantity, 0)) AS tr90qty,
			ABS(COALESCE(tr180.quantity, 0)) AS tr180qty,
			ABS(COALESCE(tr365.quantity, 0)) AS tr365qty,
			ABS(COALESCE(tr730.quantity, 0)) AS tr730qty,

			/* Client Returns */
			ABS(COALESCE(rr30.quantity, 0)) AS rr30qty,
			ABS(COALESCE(rr90.quantity, 0)) AS rr90qty,
			ABS(COALESCE(rr180.quantity, 0)) AS rr180qty,
			ABS(COALESCE(rr365.quantity, 0)) AS rr365qty,
			ABS(COALESCE(rr730.quantity, 0)) AS rr730qty,

			/* Received From Vendor Quantities */
			ABS(COALESCE(rv30.quantity, 0)) AS rv30qty,
			ABS(COALESCE(rv90.quantity, 0)) AS rv90qty,
			ABS(COALESCE(rv180.quantity, 0)) AS rv180qty,
			ABS(COALESCE(rv365.quantity, 0)) AS rv365qty,
			ABS(COALESCE(rv730.quantity, 0)) AS rv730qty,

			/* Return To Vendor Quantities */
			ABS(COALESCE(rt30.quantity, 0)) AS rt30qty,
			ABS(COALESCE(rt90.quantity, 0)) AS rt90qty,
			ABS(COALESCE(rt180.quantity, 0)) AS rt180qty,
			ABS(COALESCE(rt365.quantity, 0)) AS rt365qty,
			ABS(COALESCE(rt730.quantity, 0)) AS rt730qty

		/* WO Tables */
		FROM item_table t
		LEFT JOIN wo_30 w30
			ON w30.item = t.item
		LEFT JOIN wo_90 w90
			ON w30.item = t.item
		LEFT JOIN wo_180 w180
			ON w180.item = t.item
		LEFT JOIN wo_365 w365
			ON w365.item = t.item
		LEFT JOIN wo_730 w730
			ON w730.item = t.item

		/* SO Tables */
		LEFT JOIN so_30 s30
			ON s30.item = t.item
		LEFT JOIN so_90 s90
			ON s90.item = t.item
		LEFT JOIN so_180 s180
			ON s180.item = t.item
		LEFT JOIN so_365 s365
			ON s365.item = t.item
		LEFT JOIN so_730 s730
			ON s730.item = t.item

		/* Transfer Out Tables */
		LEFT JOIN ti_30 ti30
			ON ti30.item = t.item
		LEFT JOIN ti_90 ti90
			ON ti90.item = t.item
		LEFT JOIN ti_180 ti180
			ON ti180.item = t.item
		LEFT JOIN ti_365 ti365
			ON ti365.item = t.item
		LEFT JOIN ti_730 ti730
			ON ti730.item = t.item

		/* Transfer In Tables */
		LEFT JOIN tr_30 tr30
			ON tr30.item = t.item
		LEFT JOIN tr_90 tr90
			ON tr90.item = t.item
		LEFT JOIN tr_180 tr180
			ON tr180.item = t.item
		LEFT JOIN tr_365 tr365
			ON tr365.item = t.item
		LEFT JOIN tr_730 tr730
			ON tr730.item = t.item

		/* Client Return Tables */
		LEFT JOIN rr_30 rr30
			ON rr30.item = t.item
		LEFT JOIN rr_90 rr90
			ON rr90.item = t.item
		LEFT JOIN rr_180 rr180
			ON rr180.item = t.item
		LEFT JOIN rr_365 rr365
			ON rr365.item = t.item
		LEFT JOIN rr_730 rr730
			ON rr730.item = t.item

		/* Received From Vendor Tables */
		LEFT JOIN rv_30 rv30
			ON rv30.item = t.item
		LEFT JOIN rv_90 rv90
			ON rv90.item = t.item
		LEFT JOIN rv_180 rv180
			ON rv180.item = t.item
		LEFT JOIN rv_365 rv365
			ON rv365.item = t.item
		LEFT JOIN rv_730 rv730
			ON rv730.item = t.item

		/* Return To Vendor Tables */
		LEFT JOIN rt_30 rt30
			ON rt30.item = t.item
		LEFT JOIN rt_90 rt90
			ON rt90.item = t.item
		LEFT JOIN rt_180 rt180
			ON rt180.item = t.item
		LEFT JOIN rt_365 rt365
			ON rt365.item = t.item
		LEFT JOIN rt_730 rt730
			ON rt730.item = t.item
	");

	$data = $q->fetch();

	// Translate zeros.
	foreach($data AS $key => $value){
		if($value == 0){
			$data[$key] = '-';
		}
	}

	return $data;

}

function get_total_demand($item){

	// Get the total demand for the item.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today DATE;
		DECLARE @days_30 DATE;
		DECLARE @days_90 DATE;
		DECLARE @days_180 DATE;
		DECLARE @days_365 DATE;
		DECLARE @days_730 DATE;

		DECLARE @item VARCHAR(MAX);
		DECLARE @location VARCHAR(MAX);
		DECLARE @type_wo VARCHAR(MAX);
		DECLARE @type_wo2 VARCHAR(MAX);

		SET @today = GETDATE();
		SET @days_30 = DATEADD(DAY, -30, @today);
		SET @days_90 = DATEADD(DAY, -90, @today);
		SET @days_180 = DATEADD(DAY, -180, @today);
		SET @days_365 = DATEADD(DAY, -365, @today);
		SET @days_730 = DATEADD(DAY, -730, @today);

		SET @item = ".$db->quote($item).";
		SET @location = 'DC';
		SET @type_wo = 'EI';
		SET @type_wo2 = ' I';

		/* WO - Usage */
		WITH wo_30 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND (
					trantyp = @type_wo
					OR trantyp = @type_wo2
				)
				AND tdate >= @days_30
			GROUP BY item
		), wo_90 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND (
					trantyp = @type_wo
					OR trantyp = @type_wo2
				)
				AND tdate >= @days_90
			GROUP BY item
		), wo_180 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND (
					trantyp = @type_wo
					OR trantyp = @type_wo2
				)
				AND tdate >= @days_90
			GROUP BY item
		), wo_365 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND (
					trantyp = @type_wo
					OR trantyp = @type_wo2
				)
				AND tdate >= @days_365
			GROUP BY item
		), wo_730 AS (
			SELECT
				item,
				CAST(SUM(tqty) AS INTEGER) AS quantity
			FROM ".DB_SCHEMA_ERP.".ictran
			WHERE item = @item
				AND (
					trantyp = @type_wo
					OR trantyp = @type_wo2
				)
				AND tdate >= @days_730
			GROUP BY item
		),

		item_table AS (
			SELECT @item AS item
		)

		SELECT
			/* WO Quantities. */
			ABS(COALESCE(w30.quantity, 0)) AS w30qty,
			ABS(COALESCE(w90.quantity, 0)) AS w90qty,
			ABS(COALESCE(w180.quantity, 0)) AS w180qty,
			ABS(COALESCE(w365.quantity, 0)) AS w365qty,
			ABS(COALESCE(w730.quantity, 0)) AS w730qty


		/* WO Tables */
		FROM item_table t
		LEFT JOIN wo_30 w30
			ON w30.item = t.item
		LEFT JOIN wo_90 w90
			ON w30.item = t.item
		LEFT JOIN wo_180 w180
			ON w180.item = t.item
		LEFT JOIN wo_365 w365
			ON w365.item = t.item
		LEFT JOIN wo_730 w730
			ON w730.item = t.item
	");

	return $q->fetch();

}

function get_locations(){

	// Get the available warehouse locations.

	$db = DB::get();
	$q = $db->query("
		SELECT LTRIM(RTRIM(loctid)) AS loctid
		FROM ".DB_SCHEMA_ERP.".icloct
		WHERE addrs1 IS NOT NULL
			AND addrs1 != ''
	");

	$results = $q->fetchAll();
	$locations = array();
	foreach($results as $row){

		array_push($locations, $row['loctid']);

	}

	return $locations;

}

function get_usage($locations, $item){

	// Get the usage by location.
	$usage = array();
	foreach($locations as $location){
		$usage[$location] = get_usage_by_location($item, $location);
	}

	return $usage;

}

function get_totals($locations, $usage){

	// Calculate the total usage by location and date range.

	// The totals for each column.
	$totals = array();

	// Get the totals
	$usage_values = array_values($usage);
	for($i=0; $i<(count($usage_values[0])-1)/2; $i++){

		// The column total.
		$t = 0;
		foreach($locations as $location){
			$t += $usage[$location][$i];
		}

		// Prevent zero totals.
		if($t==0){
			$t = '-';
		}

		array_push($totals, $t);

	}

	return $totals;

}


// Get the item.
$item = $_POST['item-number'];
// $item = 'R001.601';

// Get the locations.
$locations = get_locations();

// Get the usage.
$usage = get_usage($locations, $item);

// Get the total usage.
$total_usage = get_total_demand($item);

// Get the usage totals.
$totals = get_totals($locations, $usage);

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	.text-center {
		text-align: center !important;
	}
	.usage-category {
		background-color: #80808012;
	}
	.usage-group {
		background-color: #80808012;
		border-bottom: 1px solid gray;
	}
	.header-boundary {
		border-right: 1px solid gray;
	}
	#totals-row td {
		background-color: #80808012;	
		border-top: 1px solid gray;
	}
	#total-usage-table {
		width: 20%;
	}
</style>

<table id="total-usage-table" class="table table-striped table-hover table-small">
	<thead>
		<th></th>
		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group">730</th>
	</thead>
	<tbody>
		<tr>
			<td>Total Demand</td>
			<td><?php print htmlentities($total_usage['w30qty']) ?></td>
			<td><?php print htmlentities($total_usage['w90qty']) ?></td>
			<td><?php print htmlentities($total_usage['w180qty']) ?></td>
			<td><?php print htmlentities($total_usage['w365qty']) ?></td>
			<td><?php print htmlentities($total_usage['w730qty']) ?></td>
		</tr>
	</tbody>
</table>

<table id="usage-table" class="table table-striped table-hover table-small">
	<thead>
		<th></th>
		<th id="group-wo" class="text-center usage-category header-boundary" colspan="5">WO</th>
		<th id="group-so" class="text-center usage-category header-boundary" colspan="5">SO</th>
		<th id="group-to" class="text-center usage-category header-boundary" colspan="5">Transfer Out</th>
		<th id="group-ti" class="text-center usage-category header-boundary" colspan="5">Transfer In</th>
		<th id="group-tr" class="text-center usage-category header-boundary" colspan="5">Client Returns</th>
		<th id="group-rr" class="text-center usage-category header-boundary" colspan="5">Received From Vendor</th>
		<th id="group-rt" class="text-center usage-category" colspan="5">Return To Vendor</th>
	</thead>
	<thead>
		<th class="usage-group">Location</th>
		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group header-boundary">730</th>

		<th class="usage-group">30</th>
		<th class="usage-group">90</th>
		<th class="usage-group">180</th>
		<th class="usage-group">365</th>
		<th class="usage-group">730</th>
	</thead>
	<tbody>

		<tr>
			<td>DC</td>
			<td><?php print htmlentities($usage['DC']['w30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['w90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['w180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['w365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['w730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['s30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['s90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['s180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['s365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['s730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['ti30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['ti90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['ti180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['ti365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['ti730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['tr30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['tr90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['tr180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['tr365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['tr730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['rr30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rr90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rr180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rr365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rr730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['rv30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rv90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rv180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rv365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rv730qty']) ?></td>

			<td><?php print htmlentities($usage['DC']['rt30qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rt90qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rt180qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rt365qty']) ?></td>
			<td><?php print htmlentities($usage['DC']['rt730qty']) ?></td>
		</tr>

		<?php
			// Remove DC.
			unset($usage['DC']);
			ksort($usage);
			foreach($usage AS $location => $lusage){
				?>
				<tr>
					<td><?php print htmlentities($location) ?></td>
					<td><?php print htmlentities($lusage['w30qty']) ?></td>
					<td><?php print htmlentities($lusage['w90qty']) ?></td>
					<td><?php print htmlentities($lusage['w180qty']) ?></td>
					<td><?php print htmlentities($lusage['w365qty']) ?></td>
					<td><?php print htmlentities($lusage['w730qty']) ?></td>

					<td><?php print htmlentities($lusage['s30qty']) ?></td>
					<td><?php print htmlentities($lusage['s90qty']) ?></td>
					<td><?php print htmlentities($lusage['s180qty']) ?></td>
					<td><?php print htmlentities($lusage['s365qty']) ?></td>
					<td><?php print htmlentities($lusage['s730qty']) ?></td>

					<td><?php print htmlentities($lusage['ti30qty']) ?></td>
					<td><?php print htmlentities($lusage['ti90qty']) ?></td>
					<td><?php print htmlentities($lusage['ti180qty']) ?></td>
					<td><?php print htmlentities($lusage['ti365qty']) ?></td>
					<td><?php print htmlentities($lusage['ti730qty']) ?></td>

					<td><?php print htmlentities($lusage['tr30qty']) ?></td>
					<td><?php print htmlentities($lusage['tr90qty']) ?></td>
					<td><?php print htmlentities($lusage['tr180qty']) ?></td>
					<td><?php print htmlentities($lusage['tr365qty']) ?></td>
					<td><?php print htmlentities($lusage['tr730qty']) ?></td>

					<td><?php print htmlentities($lusage['rr30qty']) ?></td>
					<td><?php print htmlentities($lusage['rr90qty']) ?></td>
					<td><?php print htmlentities($lusage['rr180qty']) ?></td>
					<td><?php print htmlentities($lusage['rr365qty']) ?></td>
					<td><?php print htmlentities($lusage['rr730qty']) ?></td>

					<td><?php print htmlentities($lusage['rv30qty']) ?></td>
					<td><?php print htmlentities($lusage['rv90qty']) ?></td>
					<td><?php print htmlentities($lusage['rv180qty']) ?></td>
					<td><?php print htmlentities($lusage['rv365qty']) ?></td>
					<td><?php print htmlentities($lusage['rv730qty']) ?></td>

					<td><?php print htmlentities($lusage['rt30qty']) ?></td>
					<td><?php print htmlentities($lusage['rt90qty']) ?></td>
					<td><?php print htmlentities($lusage['rt180qty']) ?></td>
					<td><?php print htmlentities($lusage['rt365qty']) ?></td>
					<td><?php print htmlentities($lusage['rt730qty']) ?></td>
				</tr>
				<?php
			}

		?>

		<!--
		<tr>
			<td>DET</td>
			<td><?php print htmlentities($usage['DET']['w30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['w90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['w180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['w365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['w730qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['s30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['s90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['s180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['s365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['s730qty']) ?></td>

			<td><?php print htmlentities($usage['DET']['ti30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['ti90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['ti180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['ti365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['ti730qty']) ?></td>

			<td><?php print htmlentities($usage['DET']['tr30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['tr90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['tr180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['tr365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['tr730qty']) ?></td>

			<td><?php print htmlentities($usage['DET']['rr30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rr90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rr180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rr365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rr730qty']) ?></td>

			<td><?php print htmlentities($usage['DET']['rv30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rv90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rv180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rv365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rv730qty']) ?></td>

			<td><?php print htmlentities($usage['DET']['rt30qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rt90qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rt180qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rt365qty']) ?></td>
			<td><?php print htmlentities($usage['DET']['rt730qty']) ?></td>
		</tr>

		<tr>
			<td>EGV</td>
			<td><?php print htmlentities($usage['EGV']['w30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['w90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['w180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['w365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['w730qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['s30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['s90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['s180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['s365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['s730qty']) ?></td>

			<td><?php print htmlentities($usage['EGV']['ti30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['ti90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['ti180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['ti365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['ti730qty']) ?></td>

			<td><?php print htmlentities($usage['EGV']['tr30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['tr90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['tr180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['tr365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['tr730qty']) ?></td>

			<td><?php print htmlentities($usage['EGV']['rr30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rr90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rr180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rr365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rr730qty']) ?></td>

			<td><?php print htmlentities($usage['EGV']['rv30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rv90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rv180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rv365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rv730qty']) ?></td>

			<td><?php print htmlentities($usage['EGV']['rt30qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rt90qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rt180qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rt365qty']) ?></td>
			<td><?php print htmlentities($usage['EGV']['rt730qty']) ?></td>
		</tr>

		<tr>
			<td>VA</td>
			<td><?php print htmlentities($usage['VA']['w30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['w90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['w180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['w365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['w730qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['s30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['s90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['s180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['s365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['s730qty']) ?></td>

			<td><?php print htmlentities($usage['VA']['ti30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['ti90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['ti180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['ti365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['ti730qty']) ?></td>

			<td><?php print htmlentities($usage['VA']['tr30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['tr90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['tr180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['tr365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['tr730qty']) ?></td>

			<td><?php print htmlentities($usage['VA']['rr30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rr90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rr180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rr365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rr730qty']) ?></td>

			<td><?php print htmlentities($usage['VA']['rv30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rv90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rv180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rv365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rv730qty']) ?></td>

			<td><?php print htmlentities($usage['VA']['rt30qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rt90qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rt180qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rt365qty']) ?></td>
			<td><?php print htmlentities($usage['VA']['rt730qty']) ?></td>
		</tr>
		-->
		<tr id="totals-row">

			<td class="total-label">Totals:</td>

			<?php
			foreach($totals AS $total){
				?>
					<td class="total-td"><?php print htmlentities($total) ?></td>
				<?php
			}
			?>

		</tr>

	</tbody>

</table>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => true,
	'html' => $html
));

?>