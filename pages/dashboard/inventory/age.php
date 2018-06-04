<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

ini_set('max_execution_time', 3000);

$session->ensureLogin();

$grab_locations = $db->query("
	SELECT
		LTRIM(RTRIM(icloct.loctid)) AS loctid
	FROM
		" . DB_SCHEMA_ERP . ".icloct
	WHERE
		icloct.loctid IN ('DC', 'DET', 'EGV', 'VA')
	ORDER BY
		icloct.loctid
");

$args = array(
	'title' => 'Inventory Age',
	'breadcrumbs' => array(
		'Warehouse: Inventory Age' => BASE_URI . '/dashboard/inventory/age'
	)
);

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	#item-age-container .locations .location {
		display:inline-block;
		width:180px;
		padding:12px;
	}
	#item-age-container .locations .location:hover {
		background-color:#eee;
	}

	#item-age-container tr.zero-value td {
		background-color:#fcc;
	}
	#item-age-container .aged-table td,
	#item-age-container .aged-table th {
		line-height:16px;
		font-size:11px;
		padding:8px;
		border-top:1px solid #ddd;
		border-bottom:1px solid #ddd;
		text-align:right !important;
	}
	#item-age-container .aged-table th {
		background-color:#ddd;
		font-weight:bold;
	}
	#item-age-container .datetime {
		font-size:12px;
		font-style:italic;
		line-height:21px;
		vertical-align:middle;
		font-weight:normal;
		display:inline-block;
		padding-left:20px;
	}
</style>

<div id="item-age-container">
	<div class="padded">
		<h3>
			Inventory Age
			<span class="datetime"></span>
		</h3>
	</div>
	<div class="locations">
		<label class="location" loctid="*">
			<input type="radio" name="loctid" value="*" <?php print isset($_REQUEST['loctid']) && $_REQUEST['loctid'] == '*' ? 'checked' : Null;?> />
			All Locations
		</label>
		<?php
		foreach($grab_locations as $location) {
			?>
			<label class="location" loctid="<?php print htmlentities($location['loctid'], ENT_QUOTES);?>">
				<input type="radio" name="loctid" value="<?php print htmlentities($location['loctid'], ENT_QUOTES);?>" <?php print isset($_REQUEST['loctid']) && $_REQUEST['loctid'] == $location['loctid'] ? 'checked' : Null;?> />
				<?php print htmlentities($location['loctid']);?>
			</label>
			<?php
		}
		?>
	</div>
	<?php
	if(isset($_REQUEST['loctid'])) {
		$grab_items = $db->prepare("
			SELECT
				LTRIM(RTRIM(iccost.item)) AS item,
				LTRIM(RTRIM(icitem.itmdesc)) AS itmdesc,
				(
					SUM(
						DATEDIFF(
							day,
							iccost.adddate,
							GETDATE()
						) * iccost.conhand
					) / SUM(
						iccost.conhand
					)
				) AS average_age,
				SUM(iccost.conhand) as qty,
				SUM(iccost.cost * iccost.conhand) AS value
			FROM
				" . DB_SCHEMA_ERP . ".icitem
			INNER JOIN
				" . DB_SCHEMA_ERP . ".iccost
				ON
				iccost.item = icitem.item
			WHERE
				iccost.conhand != 0
				" . (
					$_REQUEST['loctid'] != '*' ?
						"AND icitem.item IN (SELECT DISTINCT iciloc.item FROM PRO01.dbo.iciloc WHERE iciloc.item = icitem.item AND iciloc.loctid = " . $db->quote($_REQUEST['loctid']) . ")"
					:
						Null
				) . "
			GROUP BY
				iccost.item,
				icitem.itmdesc
			ORDER BY
				average_age DESC
		", [
			PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
		]);
		$grab_items->execute();
		?>

		<div class="sales-container" style="min-height: 67px;">
			<table class="aged-table">
				<thead>
					<tr>
						<th></th>
						<th class="right">0-30 Days</th>
						<th class="right">31-60 Days</th>
						<th class="right">61-90 Days</th>
						<th class="right">91-120 Days</th>
						<th class="right">121-365 Days</th>
						<th class="right">366-547 Days</th>
						<th class="right">548-730 Days</th>
						<th class="right">731+ Days</th>
						<th class="right">Total</th>
					</tr>
				</thead>
				<tbody>
					<tr class="aged-values">
						<th>Value</th>
						<td class="aged-0">...</td>
						<td class="aged-31">...</td>
						<td class="aged-61">...</td>
						<td class="aged-91">...</td>
						<td class="aged-121">...</td>
						<td class="aged-366">...</td>
						<td class="aged-548">...</td>
						<td class="aged-731">...</td>
						<td class="total">...</td>
					</tr>
					<tr class="aged-percentages">
						<th></th>
						<td class="aged-0">...</td>
						<td class="aged-31">...</td>
						<td class="aged-61">...</td>
						<td class="aged-91">...</td>
						<td class="aged-121">...</td>
						<td class="aged-366">...</td>
						<td class="aged-548">...</td>
						<td class="aged-731">...</td>
						<td class="total">...</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="padded">
			<h3>Items Count: <span id="order-count"><?php print number_format($grab_items->rowCount());?></span></h3>
		</div>

		<table class="table table-small table-striped table-hover columns-sortable columns-filterable" id="inventory-age-table">
			<thead>
				<tr>
					<th class="sortable filterable">Item Code</th>
					<th class="sortable filterable">Part #</th>
					<th class="sortable filterable">Average Age</th>
					<th class="sortable filterable">Qty On Hand</th>
					<th class="sortable filterable">Value</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_items as $item) {
					?>
					<tr class="item <?php print !((float)$item['value']) ? 'zero-value' : Null;?>" item="<?php print htmlentities($item['item'], ENT_QUOTES);?>">
						<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities(json_encode(['item-number' => $item['item']]), ENT_QUOTES);?>"><?php print htmlentities($item['item']);?></td>
						<td><?php print htmlentities($item['itmdesc']);?></td>
						<td><?php print number_format($item['average_age'], 0);?></td>
						<td><?php print number_format($item['qty'], 0);?></td>
						<td>$<?php print number_format($item['value'], 2);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>

	<script type="text/javascript">
		var one_minute = 60 * 1000; // Calculated in milliseconds.

		var loadInventoryAgeStatsXHR; // For tracking AJAX requests. When another one comes along, ensures existing XHR is cancelled.
		var loadInventoryAgeStats = function() {
			var items = [];

			// Check if rows are hidden. If they are, then we'll populate items. Otherwise, we'll leave blank which assumes calculation on all.
			if($('#inventory-age-table').find('tbody tr:not(:visible)').length) {
				// Get the items from visible rows.
				$('#inventory-age-table').find('tbody tr:visible').each(function() {
					var $item = $(this);
					var item = $item.attr('item');
					items.push(item);
				});
			}

			var $datetime = $('item-age-container .datetime');
			var $table = $('#item-age-container .aged-table');
			var $tbody = $table.find('tbody');

			if(loadInventoryAgeStatsXHR) {
				loadInventoryAgeStatsXHR.abort();
			}
			var $percentages = $tbody.find('tr.aged-percentages');
			//var $quantities = $tbody.find('tr.aged-quantities');
			var $values = $tbody.find('tr.aged-values');

			loadInventoryAgeStatsXHR = $.ajax({
				'url': BASE_URI + '/dashboard/inventory/age/stats',
				'dataType': 'json',
				'method': 'POST',
				'data': {
					'items': items,
					'loctid': <?php print json_encode($_REQUEST['loctid']);?>
				},
				'beforeSubmit': function() {
					// Replace all values with "..." while we're loading.
					$percentages.find('td').text('...');
					//$quantities.find('td').text('...');
					$values.find('td').text('...');
				},
				'success': function(data) {
					// Populate Date/Time
					$datetime.text('Last updated on ' + data.datetime);

					// Populate Quantities.
					/*$quantities.find('.aged-0').text(data.quantities.aged_0);
					$quantities.find('.aged-31').text(data.quantities.aged_31);
					$quantities.find('.aged-61').text(data.quantities.aged_61);
					$quantities.find('.aged-91').text(data.quantities.aged_91);
					$quantities.find('.aged-121').text(data.quantities.aged_121);
					$quantities.find('.aged-366').text(data.quantities.aged_366);
					$quantities.find('.aged-548').text(data.quantities.aged_548);
					$quantities.find('.aged-731').text(data.quantities.aged_731);
					$quantities.find('.total').text(data.quantities.total);*/

					// Populate Values.
					$values.find('.aged-0').text(data.values.aged_0);
					$values.find('.aged-31').text(data.values.aged_31);
					$values.find('.aged-61').text(data.values.aged_61);
					$values.find('.aged-91').text(data.values.aged_91);
					$values.find('.aged-121').text(data.values.aged_121);
					$values.find('.aged-366').text(data.values.aged_366);
					$values.find('.aged-548').text(data.values.aged_548);
					$values.find('.aged-731').text(data.values.aged_731);
					$values.find('.total').text(data.values.total);

					// Populate Percentages.
					var hundred = Number('100');
					var total = Number(data.values.total.replace(/\,/g, '').replace(/\$/g, ''));
					var zero = Number('0');
					var aged_0 = total > zero ? ((hundred / total) * Number(data.values.aged_0.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_31 = total > zero ? ((hundred / total) * Number(data.values.aged_31.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_61 = total > zero ? ((hundred / total) * Number(data.values.aged_61.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_91 = total > zero ? ((hundred / total) * Number(data.values.aged_91.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_121 = total > zero ? ((hundred / total) * Number(data.values.aged_121.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_366 = total > zero ? ((hundred / total) * Number(data.values.aged_366.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_548 = total > zero ? ((hundred / total) * Number(data.values.aged_548.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';
					var aged_731 = total > zero ? ((hundred / total) * Number(data.values.aged_731.replace(/\,/g, '').replace(/\$/g, ''))).toFixed(0) + '%' : '0%';

					$percentages.find('.aged-0').text(aged_0);
					$percentages.find('.aged-31').text(aged_31);
					$percentages.find('.aged-61').text(aged_61);
					$percentages.find('.aged-91').text(aged_91);
					$percentages.find('.aged-121').text(aged_121);
					$percentages.find('.aged-366').text(aged_366);
					$percentages.find('.aged-548').text(aged_548);
					$percentages.find('.aged-731').text(aged_731);
					$percentages.find('.total').text('100%');
				}
			});
		};
		loadInventoryAgeStats();
		setInterval(loadInventoryAgeStats, one_minute);

		// Bind to the tablesorter.endFilter event for the filtered table to re-calculate stats.
		$('#inventory-age-table').on('filterEnd', function() {
			// Reload the stats based on rows shown.
			loadInventoryAgeStats();
		});
	</script>
	<?php
}
?>
</div>

<script type="text/javascript">
	$(document).off('click', '#item-age-container .locations .location');
	$(document).on('click', '#item-age-container .locations .location', function(event) {
		var $location = $(this);
		var loctid = $location.attr('loctid');
		window.location = BASE_URI + '/dashboard/inventory/age?loctid=' + loctid;
	});
</script>
<?php

Template::Render('footer', 'account');
