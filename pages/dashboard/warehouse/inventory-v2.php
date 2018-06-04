<?php

$session->ensureLogin();

$args = array(
	'title' => 'Inventory V2',
	'breadcrumbs' => array(
		'Inventory V2' => BASE_URI . '/dashboard/warehouse/inventory-v2'
	),
	'body-class' => 'padded'
);

function get_inserts(){

	// Query for inserts.
	$db = DB::get();
	$q = $db->query("
		SELECT
			i.type,
			i.item,
			i.inrt_type,
			i.inrt_mtrl,
			i.inrt_fnsh,
			i.inrt_gage,
			i.inrt_tbod,
			i.inrt_tbid,
			i.inrt_od,
			i.inrt_hght,
			i.inrt_width,
			i.inrt_hole_number,
			i.inrt_hole_size,
			i.inrt_hole_shape,
			i.inrt_tabs,
			i.stm_type,
			i.stm_dia,
			i.thrd_cnt
		FROM ".DB_SCHEMA_ERP.".icspec i
		INNER JOIN ".DB_SCHEMA_ERP.".icitem c
			ON c.item = i.item
		WHERE i.type = 'Insert'
		ORDER BY item
	");

	return $q->fetchAll();

}

// Get inserts by default.
$inserts = get_inserts();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#filter-container {
		padding-left: 20px;
	}
	#inserts-in-stock-input {
		margin-bottom: 5px;
		margin-left: 5px;
	}
	#inserts-in-stock-container {
		align-items: center;
		align-content: center;
		display:flex;
	}
	#floor-locks-in-stock-input {
		margin-bottom: 5px;
		margin-left: 5px;
	}
	#floor-locks-in-stock-container {
		align-items: center;
		align-content: center;
		display:flex;
	}
	#insert-search {
		margin-right: 10px;
	}
	#floor-locks-search {
		margin-right: 10px;
	}
	.text-center {
		text-align: center !important;
	}
</style>

<h2>Inventory V2</h2>

<div id="nav-container" class="row-fluid">
	<ul class="nav nav-tabs">
		<li class="nav-item active" data-target='inserts-tab'>
			<a class="nav-link" href="#">Inserts</a>
		</li>
		<li class="nav-item" data-target='floor-locks-tab'>
			<a id="floor-locks-tab-link" class="nav-link" href="#">Floor Locks</a>
		</li>
	</ul>
</div>

<div id="inserts-tab" class="tab-target">

	<div id="filter-container" class="row-fluid">
		<div id="inserts-in-stock-container" class="form-group row">
			<button id="insert-search" type="button" class="btn btn-primary btn-small">Submit</button>
			<div class="input-group">
				<span class="input-group-addon">In-Stock</span>
				<input id="inserts-in-stock-input" type="checkbox" class="form-control">
			</div>
		</div>
	</div>

	<h5>Inserts</h5>

	<div class="row-fluid">
		
		<table id="inserts-table" class="table table-small table-striped table-hover columns-sortable columns-filterable">
			<thead>
				<th class="sortable filterable">Item</th>
				<th class="sortable filterable">Type</th>
				<th class="sortable filterable">Material</th>
				<th class="sortable filterable">Finish</th>
				<th class="sortable filterable">Gauge</th>
				<th class="sortable filterable">Tube OD</th>
				<th class="sortable filterable">Tube OD</th>
				<th class="sortable filterable">Outer Diameter</th>
				<th class="sortable filterable">Height</th>
				<th class="sortable filterable">Width</th>
				<th class="sortable filterable">Number of Holes</th>
				<th class="sortable filterable">Hole Size</th>
				<th class="sortable filterable">Hole Shape</th>
				<th class="sortable filterable">Tabs</th>
				<th class="sortable filterable">Stem Type</th>
				<th class="sortable filterable">Stem Diameter</th>
				<th class="sortable filterable">Thread Count</th>
			</thead>
			<tbody>

				<?php
					foreach($inserts as $insert){
					?>
					<tr>
						<?php
							$data = json_encode(array('item-number'=>trim($insert['item'])))
						?>
						<td class="overlayz-link" overlayz-url="/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities($data) ?>"><?php print htmlentities($insert['item']) ?></td>
						<td><?php print htmlentities($insert['inrt_type']) ?></td>
						<td><?php print htmlentities($insert['inrt_mtrl']) ?></td>
						<td><?php print htmlentities($insert['inrt_fnsh']) ?></td>
						<td><?php print htmlentities($insert['inrt_gage']) ?></td>
						<td><?php print htmlentities($insert['inrt_tbid']) ?></td>
						<td><?php print htmlentities($insert['inrt_tbod']) ?></td>
						<td><?php print htmlentities($insert['inrt_od']) ?></td>
						<td><?php print htmlentities($insert['inrt_hght']) ?></td>
						<td><?php print htmlentities($insert['inrt_width']) ?></td>
						<td><?php print htmlentities($insert['inrt_hole_number']) ?></td>
						<td><?php print htmlentities($insert['inrt_hole_size']) ?></td>
						<td><?php print htmlentities($insert['inrt_hole_shape']) ?></td>
						<td><?php print htmlentities($insert['inrt_tabs']) ?></td>
						<td><?php print htmlentities($insert['stm_type']) ?></td>
						<td><?php print htmlentities($insert['stm_dia']) ?></td>
						<td><?php print htmlentities($insert['thrd_cnt']) ?></td>
					</tr>
					<?php
					}
				?>
			</tbody>
		</table>

	</div>
</div>

<div id="floor-locks-tab" class="tab-target">

	<div id="filter-container" class="row-fluid">
		<div id="floor-locks-in-stock-container" class="form-group row">
			<button id="floor-locks-search" type="button" class="btn btn-primary btn-small">Submit</button>
			<div class="input-group">
				<span class="input-group-addon">In-Stock</span>
				<input id="floor-locks-in-stock-input" type="checkbox" class="form-control">
			</div>
		</div>
	</div>

	<h5>Floor Locks</h5>
	<div class="row-fluid">
		<table id="floor-locks-table"></table>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		// Activate a new tab.
		function activate_tab(tab, div){

			// JQuery makes things easy.
			var $tab = $(tab)
			var $div = $(div)

			// Remove active status from other tabs.
			var $tabs = $("li.nav-item")
			$tabs.removeClass('active')

			// Add active status to the selected tab.
			$tab.addClass('active')

			// Hide all tab targets.
			var $targets = $(".tab-target")
			$targets.hide()

			// Show the selected tab target.
			$div.show()

		}

		// Support tab switching.
		function switch_tabs(){

			// Get the selected tab.
			var $tab = $(this)

			// Get the ID of the target tab.
			var target_id = $tab.attr('data-target')

			// Get the proper div
			var $div = $('#'+target_id)

			// Switch to the selected tab.
			activate_tab($tab, $div)

		}

		function get_inserts(){

			// Get the in-stock value.
			var $input = $('#inserts-in-stock-input')
			var in_stock = $input.is(':checked')

			// Transalte the in-stock value.
			in_stock = {
				true : '1',
				false : '0'
			}[in_stock]

			// The data to POST.
			var data = {
				'action' : 'get-inserts',
				'in-stock' : in_stock
			}

			// Get the inserts.
			$.ajax({
				'url' : '/dashboard/warehouse/inventoryv2/get-inserts',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){
					
					// Replace the table with the HTML response.
					var $table = $('#inserts-table')
					$table.replaceWith(rsp.html)
					setTimeout(function(){
						bindTableSorter($('#inserts-table'))
					},500)
				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				},

			})

		}

		function get_floor_locks(e){

			// Get floor locks.

			e.preventDefault()

			// Get the in-stock value.
			var $input = $('#floor-locks-in-stock-input')
			var in_stock = $input.is(':checked')

			// Transalte the in-stock value.
			in_stock = {
				true : '1',
				false : '0'
			}[in_stock]

			// The data to POST.
			var data = {
				'action' : 'get-floor-locks',
				'in-stock' : in_stock
			}

			// Get the floor locks.
			$.ajax({
				'url' : '/dashboard/warehouse/inventoryv2/get-floor-locks',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){
					// Replace the table with the HTML response.
					var $table = $('#floor-locks-table')
					$table.replaceWith(rsp.html)
					setTimeout(function(){
						bindTableSorter($('#floor-locks-table'))
					},500)
				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				},

			})

		}

		// Enable tab switching.
		$(document).on('click', '.nav-item', switch_tabs)

		// Enable searches.
		$(document).on('click', '#insert-search', get_inserts)
		$(document).on('click', '#floor-locks-search', get_floor_locks)

		// Support tab-specific AJAX.
		$(document).on('click', '#floor-locks-tab-link', get_floor_locks)

	})
</script>