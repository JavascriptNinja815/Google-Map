<?php
ob_start(); // Start loading output into buffer.

function get_details(){

	// Query for the item shipments.

	$db = DB::get();

	$query = "
		SELECT
			i.item,
			t.custno,
			CAST(t.qtyshp AS integer) AS qtyshp,
			CONVERT(DECIMAL(10,2), t.price) AS price,
			CAST(t.shipdate AS date) AS shipdate,
			t.sono,
			t.loctid,
			t.terr,
			t.salesmn,
			t.custmemo,
			t.intmemo
		FROM ".DB_SCHEMA_ERP.".icitem i
		INNER JOIN ".DB_SCHEMA_ERP.".sotran t
			ON t.item = i.item
		WHERE LTRIM(RTRIM(i.item)) = ".$db->quote($_POST['item-number'])."
	";

	$q = $db->query($query);

	return $q->fetchAll();

}

// Get the shipment data.
$data = get_details();

?>
<style type="text/css">
	.text-center {
		text-align: center !important;
	}
	.memo {
		cursor: pointer;
	}
	.shipment-row {
		display: table-row !important;
	}
</style>
<div>
	<h3>Shipments</h3>
</div>
<div>
	<select id="date-select" data-item-number="<?php print htmlentities($_POST['item-number']) ?>">
		<option value="90">Last 90 Days</option>
		<option value="365">Last 12 Month</option>
		<option value="all">All Time</option>
	</select>
</div>
<div>
	<table id="item-shipments" class="table table-small table-hover table-striped columns-sortable columns-filterable">
		<thead>
			<tr>
				<th class="filterable sortable">Client</th>
				<th class="filterable sortable">Qty</th>
				<th class="filterable sortable">Price</th>
				<th class="filterable sortable">Ship Date</th>
				<th class="filterable sortable">SO #</th>
				<th class="filterable sortable">Location</th>
				<th class="filterable sortable">Office</th>
				<th class="filterable sortable">Sales Person</th>
				<th class="text-center filterable ">Client Note</th>
				<th class="text-center filterable ">Internal Note</th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($data as $row){

					// Get the number of days between now and the shipping date.
					$then = strtotime($row['shipdate']);
					$now = time();
					$diff = $now-$then;
					$days = $diff/60/60/24;

					// If the order is less than 90 days old, show it.
					$old = '0';

					// If the order is more than 90 days old, hide it.
					if($days>90){
						$old = 'old old-90';
					};

					// If the order is over a year old, hide it.
					if($days>365){
						$old = 'old old-365';
					};

					?>
					<tr class="shipment-row <?php print($old) ?>">
						<td class="overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => $row['custno']]), ENT_QUOTES);?>"><?php print htmlentities($row['custno']) ?></td>
						<td><?php print htmlentities($row['qtyshp']) ?></td>
						<td><?php print htmlentities($row['price']) ?></td>
						<td><?php print htmlentities($row['shipdate']) ?></td>
						<td class="overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(json_encode(['so-number' => $row['sono']]), ENT_QUOTES);?>"><?php print htmlentities($row['sono']) ?></td>
						<td><?php print htmlentities($row['loctid']) ?></td>
						<td><?php print htmlentities($row['terr']) ?></td>
						<td><?php print htmlentities($row['salesmn']) ?></td>
						<?php
							// Create icons if there is a memo.
							$cmemo = $row['custmemo'];
							$imemo = $row['intmemo'];

							$cmicon = '';
							$imicon = '';

							if($cmemo){
								$cmicon = '<i class="fa fa-envelope-open"></i>';
							};
							if($imemo){
								$imicon = '<i class="fa fa-envelope-open"></i>';
							};
						?>
						<td class="text-center memo" data-memo="<?php print htmlentities($cmemo) ?>"><?php print $cmicon ?></td>
						<td class="text-center memo" data-memo="<?php print htmlentities($imemo) ?>"><?php print $imicon ?></td>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function show_memo(){

			// Get the memo text.
			$td = $(this)
			memo = $td.attr('data-memo')

			// TODO: This should probably be a proper popup.
			//alert(memo)

			// The CSS for the overlay.
			var css = {
				'body' : {
					'width' : '100%',
					'height' : '100%'
				}
			}

			var layz = $.overlayz({
				'html' : memo,
				'alias' : 'memo-overlay',
				//'css' : css
			})
			layz.fadeIn()

		}

		function get_shipments(){

			// Get new shipment values.

			// Get the constraint.
			$select = $(this)
			constraint = $select.val()

			// All old rows.
			var $old = $('.old')

			// All rows over 90 days old.
			var $old90 = $('.old-90')

			// All rows over 1 year old.
			var $old365 = $('.old-365')

			// Show all old rows.
			$old.show()

			// If 'last 90' is selected, hide rows older than 90 days.
			if(constraint=='90'){
				// $old90.hide()
				// $old365.hide()
				hide_90()
				hide_365()
			}

			// If 'last 365' is selected, hide rows older than 1 year.
			if(constraint=='365'){
				// $old90.show()
				// $old365.hide()
				show_90()
				hide_365()
			}

			// If 'all' is selected, show all rows.
			if(constraint=='all'){
				// $old90.show()
				// $old365.show()
				show_90()
				show_365()
			}

		}

		/* Dirty, ugly, nasty, nausea-inducing hacks */
		function hide_90(){
			$('.old-90').attr('style', 'display:none !important')
		}
		function hide_365(){
			$('.old-365').attr('style', 'display:none !important')
		}
		function show_90(){
			$('.old-90').attr('style', 'display: table-row !important')
		}
		function show_365(){
			$('.old-365').attr('style', 'display: table-row !important')
		}
		function show_old(){
			$('.old').attr('style', 'display:table-row !ipmrotant')
		}
		function hide_old(){

			// Hide all old rows by default.
			$('.old').attr('style', 'display:none !important')

		}

		function make_sortable(){

			// Make the table sortable/filterable.
			applyTableFeatures($('#item-shipments'), $('#item-shipments tbody'))
		}

		// Support showing memos.
		$(document).off('click', '.memo')
		$(document).on('click', '.memo', show_memo)

		// Support different date ranges.
		$(document).off('change', '#date-select')
		$(document).on('change', '#date-select', get_shipments)

		// Hide old rows.
		// For some reason this doesn't work without a delay.
		setTimeout(hide_old, 10)

		// Make the table filterable/sortable.
		make_sortable()

	})
</script>

<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);