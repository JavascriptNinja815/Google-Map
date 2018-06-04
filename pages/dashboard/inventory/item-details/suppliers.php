<?php

$session->ensureLogin();

function get_default_suppliers(){

	// Query for the default supplier by locatino.

	$db = DB::get();
	$q = $db->query("
		SELECT TOP 100
			i.item,
			l.loctid,
			l.lsupplr,
			l.llstsup,
			CAST(l.lrecv AS date) AS lrecv
		FROM ".DB_SCHEMA_ERP.".icitem i
		INNER JOIN ".DB_SCHEMA_ERP.".iciloc l
			ON l.item = i.item
		WHERE i.item = ".$db->quote($_POST['item-number'])."
			AND loctid NOT IN ('DROP','LC-CON,','LC-DC','TRA-DT','TRA-EG', 'TRA-DC')
	");

	return $q->fetchAll();

}

function get_all_suppliers(){

	// Query for all suppliers.

	$db = DB::get();
	$q = $db->query("
		SELECT TOP 100
			CASE
				WHEN i.itemstat = 'A'
				THEN 1
				ELSE 0
			END AS active,
			s.vendno,
			s.vpartno,
			s.lastcst,
			CAST(s.lrecdte AS date) AS lrecdte,
			s.lead,
			s.ordmin,
			s.ordincr,
			CAST(s.adddate AS DATE) AS adddate,
			s.supmemo
		FROM ".DB_SCHEMA_ERP.".icitem i
		INNER JOIN ".DB_SCHEMA_ERP.".icsupl s
			ON s.item = i.item
		WHERE i.item = ".$db->quote($_POST['item-number'])."
		ORDER BY lrecdte DESC
	");

	return $q->fetchAll();

}

// Get default suppliers.
$default_suppliers = get_default_suppliers();

// Get all suppliers.
$all_suppliers = get_all_suppliers();

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	.icon {
		cursor: pointer;
	}
	#all-suppliers-header {
		align-items: center;
		align-content: center;
		display: flex;
	}
	.hide {
		display:none;
	}
</style>

<h3>Default Suppliers</h3>
<table>
	<thead>
		<th>Location</th>
		<th>Supplier</th>
		<th>Last Supplier</th>
		<th>Last Receipt</th>
	</thead>
	<tbody>
		<?php
		foreach($default_suppliers as $sup){
			?>
			<tr>
				<td><?php print htmlentities($sup['loctid']) ?></td>
				<td><?php print htmlentities($sup['lsupplr']) ?></td>
				<td><?php print htmlentities($sup['llstsup']) ?></td>
				<td><?php print htmlentities($sup['lrecv']) ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>

<div id="all-suppliers-header" class="row-fluid">
	<div class="span2">
		<h3>All Suppliers</h3>
	</div>
	<div id="btn-container" class="span2">
		<button id="toggle-inactive" class="btn btn-primary">Show Inactive</button>
	</div>
</div>
<table>
	<thead>
		<th>Vendor Number</th>
		<th>Vendor Part Number</th>
		<th>Last Cost</th>
		<th>Last Receipt</th>
		<th>Lead</th>
		<th>Minimum Order</th>
		<th>Order Increment</th>
		<th>Add Date</th>
		<th class="text-center">Memo</th>
	</thead>
	<tbody>
		<?php
		foreach($all_suppliers as $sup){

			// Determine whether the row should be displayed.
			$active = 'active';
			$hide = '';
			if(!$sup['active']){
				$active = 'inactive';
				$hide = 'hide';
			}

			?>
			<tr class="all-suppliers-row <?php print htmlentities($hide) ?>" data-item-number="<?php print htmlentities($_POST['item-number']) ?>" data-vendor-number="<?php print htmlentities($sup['vendno']) ?>" data-vendor-part-number="<?php print htmlentities($sup['vpartno']) ?>" data-active="<?php print htmlentities($active) ?>">
				<td><?php print htmlentities($sup['vendno']) ?></td>
				<td><?php print htmlentities($sup['vpartno']) ?></td>
				<td><?php print htmlentities($sup['lastcst']) ?></td>
				<td><?php print htmlentities($sup['lrecdte']) ?></td>
				<td><?php print htmlentities($sup['lead']) ?></td>
				<td><?php print htmlentities($sup['ordmin']) ?></td>
				<td><?php print htmlentities($sup['ordincr']) ?></td>
				<td><?php print htmlentities($sup['adddate']) ?></td>
				<td class="text-center"><i class="icon fa fa-sticky-note-o"></i></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>

<script type="text/javascript">

	// Keep track of whether inactive rows are hidden.
	hidden = true

	function show_memo(){

		// Produce an overlay displaying a supplier memo.

		// Get the data for memo.
		var $i = $(this)
		var $row = $i.parents('tr')
		var item = $row.attr('data-item-number')
		var vendno = $row.attr('data-vendor-number')
		var vpartno = $row.attr('data-vendor-part-number')

		// The data for the overlay.
		var data = {
			'item' : item,
			'vendno' : vendno,
			'vpartno' : vpartno
		}

		// The URL for the overlay.
		var url = BASE_URI+'/dashboard/inventory/item-details/supplier-memo'

		// Create the overlay.
		activateOverlayZ(url, data)

	}

	function toggle_inactive(){

		// Show/hide inactive rows in the all-suppliers table.

		// Get the button and change its text.
		var $btn = $(this)
		var btxt = $btn.text()

		var new_text = {
			'Show Inactive' : 'Hide Inactive',
			'Hide Inactive' : 'Show Inactive'
		}[btxt]
		$btn.text(new_text)

		// Find all inactive rows.
		$inactive = $('.all-suppliers-row[data-active="inactive"]')

		// Hide or show inactive.
		if(hidden){
			$inactive.removeClass('hide')
			hidden=false
		}else{
			$inactive.addClass('hide')
			hidden=true
		}

	}

	// Enable memos.
	$(document).off('click', '.icon')
	$(document).on('click', '.icon', show_memo)

	// Enable showing inactive.
	$(document).off('click', '#toggle-inactive')
	$(document).on('click', '#toggle-inactive', toggle_inactive)

</script>
<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);