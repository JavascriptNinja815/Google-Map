<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$constrain_date = False;
if(!isset($_POST['constrain_date']) || $_POST['constrain_date'] == '12') {
	$constrain_date = new DateTime('-12 months');
	$constrain_date = $constrain_date->format('Y-m-d');
} else if($_POST['constrain_date'] == '36') {
	$constrain_date = new DateTime('-36 months');
	$constrain_date = $constrain_date->format('Y-m-d');
}

$grab_items = $db->prepare("
WITH
	customer AS (
		SELECT
			*
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		WHERE
			custno = " . $db->quote(trim($_POST['custno'])) . "
	),
	transactions AS (
		SELECT
			artran.invno,
			artran.item,
			artran.price,
			artran.cost,
			artran.invdte,
			artran.qtyord,
			artran.qtyshp,
			icitem.comcode,
			icitem.itemstat,
			icitem.itmdesc,
			icitem.itmdes2,
			icitem.lstcost,
			(
				CASE WHEN price_quantities.price IS NOT NULL THEN
					price_quantities.price
				ELSE
					icitem.lstcost
				END
			) AS currcost,
			(
				CASE WHEN price_quantities.price IS NOT NULL THEN
					'price_quantities'
				ELSE
					'icitem'
				END
			) AS currcost_source,
			icicus.cpartno
		FROM
			" . DB_SCHEMA_ERP . ".artran -- Current transactions
		INNER JOIN
			customer
			ON
			customer.custno = artran.custno
		INNER JOIN
			" . DB_SCHEMA_ERP . ".icitem
			ON
			icitem.item = artran.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".icicus
			ON
			icicus.custno = artran.custno
			AND
			icicus.item = icitem.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".price_items
			ON
			price_items.item = artran.item COLLATE Latin1_General_BIN
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".price_quantities
			ON
			price_quantities.price_item_id = price_items.price_item_id
			AND
			price_quantities.quantity = 1
			AND
			price_quantities.expires_date IS NULL
		WHERE
			LTRIM(RTRIM(artran.item)) NOT IN ('FRT', 'EXPEDITE', 'FREE-SHIP', 'NOTE-', 'SHIP', 'SHIP-EG', 'SHIP-GR', 'SHIP-VA', 'NOTE', 'MISC', 'OVERSTOCK', 'RESTOCKING', 'SAMPLES', 'SHIP-DT', 'SHIP-GLC_TRUCK', 'STAGE-ORDER', '_MANUAL_INVOICE')
			" . (!empty($constrain_date) ? " AND artran.invdte >= " . $db->quote($constrain_date) : Null) . "
		UNION
			ALL
		SELECT
			arytrn.invno,
			arytrn.item,
			arytrn.price,
			arytrn.cost,
			arytrn.invdte,
			arytrn.qtyord,
			arytrn.qtyshp,
			icitem.comcode,
			icitem.itemstat,
			icitem.itmdesc,
			icitem.itmdes2,
			icitem.lstcost,
			(
				CASE WHEN price_quantities.price IS NOT NULL THEN
					price_quantities.price
				ELSE
					icitem.lstcost
				END
			) AS currcost,
			(
				CASE WHEN price_quantities.price IS NOT NULL THEN
					'price_quantities'
				ELSE
					'icitem'
				END
			) AS currcost_source,
			icicus.cpartno
		FROM
			" . DB_SCHEMA_ERP . ".arytrn -- Archived transactitons
		INNER JOIN
			customer
			ON
			customer.custno = arytrn.custno
		INNER JOIN
			" . DB_SCHEMA_ERP . ".icitem
			ON
			icitem.item = arytrn.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".icicus
			ON
			icicus.custno = arytrn.custno
			AND
			icicus.item = icitem.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".price_items
			ON
			price_items.item = arytrn.item COLLATE Latin1_General_BIN
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".price_quantities
			ON
			price_quantities.price_item_id = price_items.price_item_id
			AND
			price_quantities.quantity = 1
			AND
			price_quantities.expires_date IS NULL
		WHERE
			LTRIM(RTRIM(arytrn.item)) NOT IN ('FRT', 'EXPEDITE', 'FREE-SHIP', 'NOTE-', 'SHIP', 'SHIP-EG', 'SHIP-GR', 'SHIP-VA', 'NOTE', 'MISC', 'OVERSTOCK', 'RESTOCKING', 'SAMPLES', 'SHIP-DT', 'SHIP-GLC_TRUCK', 'STAGE-ORDER', '_MANUAL_INVOICE')
			" . (!empty($constrain_date) ? " AND arytrn.invdte >= " . $db->quote($constrain_date) : Null) . "
	),
	latest_transactions AS (
		SELECT
			transactions.item,
			MAX(transactions.invdte) as invdte
		FROM
			transactions
		GROUP BY
			transactions.item
	),
	latest_transaction_info AS (
		SELECT
			transactions.item,
			transactions.cost,
			transactions.price,
			transactions.invdte,
			transactions.lstcost
		FROM
			transactions
		INNER JOIN
			latest_transactions
			ON
			latest_transactions.item = transactions.item
			AND
			latest_transactions.invdte = transactions.invdte
	),
	calculated_transactions AS (
		SELECT
			transactions.item,
			AVG(transactions.cost) AS avg_cost,
			AVG(transactions.price) AS avg_price,
			MAX(transactions.qtyord) AS max_qtyord,
			CAST(VAR(transactions.cost) AS NUMERIC(10,2)) AS variance_cost,
			CAST(VAR(transactions.price) AS NUMERIC(10,2)) AS variance_price,
			(
				CASE WHEN latest_transaction_info.price > 0 THEN
					(latest_transaction_info.price - latest_transaction_info.cost) / latest_transaction_info.price
				ELSE
					NULL
				END
			) AS latest_margin,
			(
				CASE WHEN latest_transaction_info.price > 0 THEN
					(latest_transaction_info.price - MAX(transactions.currcost)) / latest_transaction_info.price
				ELSE
					NULL
				END
			) AS current_margin,
			MAX(transactions.currcost) / 0.65 AS with_target_margin,
			(
				CASE WHEN latest_transaction_info.price > 0 THEN
					(MAX(transactions.currcost) / 0.65) / latest_transaction_info.price
				ELSE
					NULL
				END
			) - 1 AS increase_to_margin
		FROM
			transactions
		INNER JOIN
			latest_transaction_info
			ON
			latest_transaction_info.item = transactions.item
		GROUP BY
			transactions.item,
			latest_transaction_info.price,
			latest_transaction_info.cost
	)
SELECT
	LTRIM(RTRIM(transactions.item)) AS item,
	LTRIM(RTRIM(transactions.comcode)) AS comcode,
	LTRIM(RTRIM(transactions.itemstat)) AS itemstat,
	LTRIM(RTRIM(transactions.itmdesc)) AS itmdesc,
	LTRIM(RTRIM(transactions.itmdes2)) AS itmdes2,
	LTRIM(RTRIM(transactions.cpartno)) AS cpartno,
	latest_transaction_info.invdte AS last_invdte,
	latest_transaction_info.cost AS last_cost,
	transactions.currcost,
	transactions.currcost_source,
	latest_transaction_info.price AS last_price,
	COUNT(DISTINCT transactions.invno) AS invoices,
	SUM(transactions.qtyshp) AS pieces_shipped,
	SUM(transactions.qtyord) AS pieces_ordered,
	SUM(transactions.qtyord) - SUM(transactions.qtyshp) AS pieces_unshipped,
	calculated_transactions.max_qtyord,
	calculated_transactions.avg_price,
	calculated_transactions.variance_price,
	calculated_transactions.avg_cost,
	calculated_transactions.variance_cost,
	calculated_transactions.latest_margin * 100 AS latest_margin,
	calculated_transactions.current_margin * 100 AS current_margin,
	calculated_transactions.with_target_margin,
	calculated_transactions.increase_to_margin * 100 AS increase_to_margin
FROM
	transactions
INNER JOIN
	latest_transaction_info
	ON
	latest_transaction_info.item = transactions.item
INNER JOIN
	calculated_transactions
	ON
	calculated_transactions.item = transactions.item
GROUP BY
	transactions.item,
	transactions.comcode,
	transactions.itemstat,
	transactions.itmdesc,
	transactions.itmdes2,
	transactions.cpartno,
	transactions.lstcost,
	transactions.currcost,
	transactions.currcost_source,
	latest_transaction_info.invdte,
	latest_transaction_info.cost,
	latest_transaction_info.price,
	calculated_transactions.max_qtyord,
	calculated_transactions.avg_price,
	calculated_transactions.variance_price,
	calculated_transactions.avg_cost,
	calculated_transactions.variance_cost,
	calculated_transactions.latest_margin,
	calculated_transactions.current_margin,
	calculated_transactions.with_target_margin,
	calculated_transactions.increase_to_margin
ORDER BY
	transactions.item
", [ PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ]);
$grab_items->execute();

?>

<style type="text/css">
	#clients-purchased-container table .nowrap {
		white-space:nowrap;
	}
	#clients-purchased-container table .nothing {
		color:#bbb;
	}
	#clients-purchased-container table .badnumber {
		font-weight:bold;
		color:#f00;
	}
	#clients-purchased-container table .goodnumber {
		font-weight:bold;
		color:#090;
	}
	#clients-purchased-container table .fa-check {
		color:#090;
	}
	#clients-purchased-container table .fa-question {
		color:#f60;
	}
	#clients-purchased-container tr.price-warning {
		background-color:#ff3;
	}
	#clients-purchased-container tr.price-warning td.client-purchased-currentcost {
		font-weight:bold;
		color:#f60;
	}
</style>

<div id="clients-purchased-container">
	
	<h2>Purchased Items <span class="purchased-count"></span></h2>

	<div>
		<select name="constrain_date">
			<option value="12" <?php print empty($_POST['constrain_date']) || $_POST['constrain_date'] == '12' ? 'selected' : Null;?>>Past 12 Months</option>
			<option value="36" <?php print !empty($_POST['constrain_date']) && $_POST['constrain_date'] == '36' ? 'selected' : Null;?>>Past 36 Months</option>
			<option value="all" <?php print !empty($_POST['constrain_date']) && $_POST['constrain_date'] == 'all' ? 'selected' : Null;?>>All Time</option>
		</select>
	</div>
	
	<p>* Lines highlighted in yellow are not using recently provided Colson pricing. Instead, they are referencing the last cost that item was purchased for.</p>

	<table class="table table-small table-striped table-hover columns-sortable columns-filterable">
		<thead>
			<tr>
				<!--th>Select</th-->
				<th class="nowrap sortable filterable">Item Code</th>
				<th class="nowrap sortable filterable">Part Number</th>
				<th class="nowrap sortable filterable">Client<br />Part No.</th>
				<th class="nowrap sortable filterable">Description</th>
				<th class="nowrap sortable filterable">Total<br />Invoices</th>
				<th class="nowrap sortable filterable">Total<br />Ordered</th>
				<th class="nowrap sortable filterable">Total<br />Shipped</th>
				<th class="nowrap sortable filterable">Total<br />Unshipped</th>
				<th class="nowrap sortable filterable">Largest<br />Order</th>
				<th class="nowrap sortable filterable">Last<br />Purchase</th>
				<th class="nowrap sortable filterable">Last<br />Price</th>
				<th class="nowrap sortable filterable">Average<br />Price</th>
				<th class="nowrap sortable filterable">Price<br />Variance</th>
				<th class="nowrap sortable filterable">Last<br />Cost</th>
				<th class="nowrap sortable filterable">Current<br />Cost</th>
				<th class="nowrap sortable filterable">Average<br />Cost</th>
				<th class="nowrap sortable filterable">Cost<br />Variance</th>
				<th class="nowrap sortable filterable">Last<br />Margin</th>
				<th class="nowrap sortable filterable">Current<br />Margin</th>
				<th class="nowrap sortable filterable">35%<br />Margin</th>
				<th class="nowrap sortable filterable">Margin<br />Increase</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_items as $item) {

				?><tr class="item <?php
					if($item['currcost_source'] != 'price_quantities') {
						print 'price-warning';
					}
				?>"><?php
					/*?><td class="client-purchased-select"><?php
						if(trim($item['comcode']) == 'S') {
							?>High Priority Stocking Item<?php
						} elseif(trim($item['itemstat']) == 'I') {
							?>Stocking Item<?php
						}
					?></td><?php*/
					?><td class="client-purchased-itemcode overlayz-link nowrap" overlayz-url="<?php print BASE_URI;?>/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities(json_encode(['item-number' => $item['item']]));?>"><?php print htmlentities(trim($item['item']));?></td><?php
					?><td class="client-purchased-description"><?php print htmlentities($item['itmdesc']);?></td><?php
					?><td class="client-purchased-clientpartno"><?php print htmlentities(trim($item['cpartno']));?></td><?php
					?><td class="client-purchased-description"><?php print htmlentities($item['itmdes2']);?></td><?php
					?><td class="client-purchased-totalinvoices"><?php print number_format(trim($item['invoices']), 0);?></td><?php
					?><td class="client-purchased-totalordered"><?php print trim($item['pieces_ordered']) ? number_format(trim($item['pieces_ordered']), 0) : Null;?></td><?php
					?><td class="client-purchased-totalshipped"><?php print trim($item['pieces_shipped']) ? number_format(trim($item['pieces_shipped']), 0) : Null;?></td><?php
					?><td class="client-purchased-totalunshipped"><?php print trim($item['pieces_unshipped']) ? number_format(trim($item['pieces_unshipped']), 0) : Null;?></td><?php
					?><td class="client-purchased-largestorder"><?php print trim($item['max_qtyord']) ? number_format(trim($item['max_qtyord']), 0) : Null;?></td><?php
					?><td class="client-purchased-lastpurchase nowrap"><?php print date('Y-m-d', strtotime(trim($item['last_invdte'])));?></td><?php
					?><td class="client-purchased-lastprice"><?php print trim($item['last_price']) ? '$' . number_format(trim($item['last_price']), 2) : Null;?></td><?php
					?><td class="client-purchased-averageprice"><?php print trim($item['avg_price']) ? '$' . number_format(trim($item['avg_price']), 2) : Null;?></td><?php
					?><td class="client-purchased-varianceprice"><?php print trim($item['variance_price']) ? number_format(trim($item['variance_price']), 2) . '%' : '<span class="nothing">N/A</span>';?></td><?php
					?><td class="client-purchased-lastcost"><?php print trim($item['last_cost']) ? '$' . number_format(trim($item['last_cost']), 2) : Null;?></td><?php
					?><td class="client-purchased-currentcost"><?php print trim($item['currcost']) ? '$' . number_format(trim($item['currcost']), 2) : Null;?></td><?php
					?><td class="client-purchased-averagecost"><?php print trim($item['avg_cost']) ? '$' . number_format(trim($item['avg_cost']), 2) : Null;?></td><?php
					?><td class="client-purchased-variancecost"><?php print trim($item['variance_cost']) ? number_format(trim($item['variance_cost']), 2) . '%' : '<span class="nothing">N/A</span>';?></td><?php
					?><td class="client-purchased-lastmargin"><?php
						$last_margin = trim($item['latest_margin']);
						if($last_margin) {
							if($last_margin < 35.00) {
								print '<span class="badnumber">' . number_format(trim($item['latest_margin']), 2) . '%</span>';
							} else {
								print '<span class="goodnumber">' . number_format(trim($item['latest_margin']), 2) . '%' . '</span>';
							}
						} else {
							?><span class="nothing">N/A</span><?php
						}
					?></td><?php
					?><td class="client-purchased-currentmargin"><?php
						$current_margin = trim($item['current_margin']);
						if(trim($item['currcost']) == 0.00) {
							print '<i class="fa fa-question"></i>';
						} else if($current_margin > 0.00) {
							print number_format(trim($item['current_margin']), 2) . '%';
						} else {
							print '<span class="nothing">N/A</span>';
						}
					?></td><?php
					?><td class="client-purchased-targetmargin"><?php
						$target_margin = trim($item['with_target_margin']);
						if(trim($item['currcost']) == 0.00) {
							print '<i class="fa fa-question"></i>';
						} else if($target_margin > 0.00) {
							print '$' . number_format(trim($item['with_target_margin']), 2);
						}
					?></td><?php
					?><td class="client-purchased-marginincrease"><?php
						$margin_increase = trim($item['increase_to_margin']);
						if(trim($item['currcost']) == 0.00) {
							print '<i class="fa fa-question"></i>';
						} else if($margin_increase > 0.00) {
							print number_format(trim($item['increase_to_margin']), 2) . '%';
						} else {
							print '<i class="fa fa-check"></i>';
						}
					?></td><?php
				?></tr><?php
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$.each($([
		'#clients-purchased-container table.columns-filterable',
		'#clients-purchased-container table.columns-sortable',
		'#clients-purchased-container table.headers-sticky'
	]), function(index, table) {
		var $table = $(table);
		var options = {
			'selectorHeaders': [],
			'widgets': [],
			'widgetOptions': {}
		};

		if($table.hasClass('columns-sortable')) {
			options.selectorHeaders.push('> thead > tr > th.sortable');
			options.selectorHeaders.push('> thead > tr > td.sortable');
			options.onRenderHeader = function() {
				// Fixes text that wraps when it doesn't necessarily need to.
				$(this).find('div').css('width', '100%');
			};
		}
		if($table.hasClass('columns-filterable')) {
			options.selectorHeaders.push('> thead > tr > th.filterable');
			options.selectorHeaders.push('> thead > tr > td.filterable');
			options.widgets.push('filter');
			options.widgetOptions.filter_ignoreCase = true;
			options.widgetOptions.filter_searchDelay = 100;
			options.widgetOptions.filter_childRows = true;
		}
		if($table.hasClass('headers-sticky')) {
			options.widgets.push('stickyHeaders');
			options.widgetOptions.stickyHeaders_attachTo = $('#body');
		}

		// Convert array to comma-separated string.
		options.selectorHeaders = options.selectorHeaders.join(',');

		$table.tablesorter(options);
	});

	var $table_rows_navigate = $('table.rows-navigate').find('> tbody > tr');
	$table_rows_navigate.on('click', function(event) {
		var $tr = $(this);
		var navigate_to = $tr.attr('navigate-to');
		window.location = navigate_to;
	});

	// Set item count in "Purchased Items" heading.
	$(function() {
		var $purchase_count = $('#clients-purchased-container .purchased-count');
		var purchase_count = $('#clients-purchased-container table tr.item').length;
		$purchase_count.text('(' + purchase_count + ')');
	});
	
	// Bind to changes on constrain date dropdown.
	$(document).off('change', '#clients-purchased-container :input[name="constrain_date"]');
	$(document).on('change', '#clients-purchased-container :input[name="constrain_date"]', function(event) {
		var $constrain_date = $(this);
		var data = {
			'constrain_date': $constrain_date.val()
		};
		data = JSON.stringify(data);
		var $tab = $('#client-details-container .tab[page="purchased"]');
		$tab.attr('data', data);
		$tab.trigger('click');
	});
</script>
