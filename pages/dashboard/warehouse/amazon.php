<?php

// Make sure the user has logged in.
$session->ensureLogin();

// Header vars.
$args = array(
	'title' => 'Amazon',
	'breadcrumbs' => array(
		'Warehouse' => BASE_URI . '/dashboard/warehouse/amazon',
		'Amazon' => BASE_URI . '/dashboard/warehouse/amazon',
	)
);

function get_orders(){

	$db = DB::get();
	return $db->query("
		SELECT DISTINCT TOP 1000
			t.custno,
			CAST(t.qtyord AS integer) AS qtyord,
			RTRIM(LTRIM(t.sono)) AS sono,
			t.sostat,
			t.item
		FROM ". DB_SCHEMA_ERP . ".sotran t
		INNER JOIN ". DB_SCHEMA_ERP .".somast m
			ON m.sono = t.sono
		LEFT JOIN ". DB_SCHEMA_ERP .".products_amazon a
			ON t.item COLLATE Latin1_General_CI_AS = a.item
		WHERE qtyord > 0
			AND t.custno = 'AMA01'
			AND t.sostat NOT IN ('V', 'C')
			AND m.sostat NOT IN ('V', 'C')
			AND t.item != 'FRT'
	");

};

function get_amazon($item){

	$db = DB::get();
	$r = $db->query("
		SELECT asin, amazon_part_number, brand
		FROM ".DB_SCHEMA_ERP.".products_amazon
		WHERE item = ".$db->quote($item)."
	");

	$amazon = $r->fetch();
	return $amazon;

}

function get_all_amazon(){

	// Get all Amazon items.
	$db = DB::get();
	return $db->query("
		SELECT
			amazon_product_id,
			RTRIM(LTRIM(item)) AS item,
			RTRIM(LTRIM(asin)) AS asin
		FROM ".DB_SCHEMA_ERP.".products_amazon
		WHERE item IS NOT NULL
		ORDER BY item, asin
	");

}

function get_all_mappings(){

	// Get all item->Amazon mappings.
	$db = DB::get();
	return $db->query("
		SELECT
			amazon_product_id,
			RTRIM(LTRIM(item)) AS item,
			RTRIM(LTRIM(asin)) AS asin,
			RTRIM(LTRIM(amazon_part_number)) AS amazon_part_number,
			RTRIM(LTRIM(brand)) AS brand
		FROM ".DB_SCHEMA_ERP.".products_amazon
		ORDER BY item
	");

}

function is_item_valid($item){

	// Make sure the item exists and is valid.

	$db = DB::get();
	$q = $db->query("
		SELECT
			CASE
				WHEN EXISTS (
					SELECT 1
					FROM ".DB_SCHEMA_ERP.".icitem
					WHERE item= ".$db->quote($item)."
				)
				THEN 1
				ELSE 0
			END AS valid
	");

	// Get the value as a boolean.
	$r = $q->fetch()['valid'];
	return (bool)$r;
}

function is_item_mapped($item){

	// Check to see if the item is already mapped.
	$db = DB::get();
	$q = $db->query("
		SELECT
			CASE
				WHEN EXISTS (
					SELECT 1
					FROM ".DB_SCHEMA_ERP.".products_amazon
					WHERE item = ".$db->quote($item)."
				)
				THEN 1
				ELSE 0
			END AS is_mapped
	");

	$r = $q->fetch()['is_mapped'];
	return (bool)$r;

}

function is_asin_mapped($asin){

	// Check to see if the ASIN has already been mapped to an item.
	$db = DB::get();
	$q = $db->query("
		SELECT
			CASE
				WHEN EXISTS(
					SELECT 1
					FROM ".DB_SCHEMA_ERP.".products_amazon
					WHERE asin = ".$db->quote($asin)."
						AND item IS NOT NULL
				)
				THEN 1
				ELSE 0
			END AS is_mapped
	");

	$r = $q->fetch()['is_mapped'];
	return (bool)$r;

}

function mapping_changed($item, $asin){

	// Check to see if the POSTed mapping has actually changed.
	$db = DB::get();
	$q = $db->query("
		SELECT
			CASE
				WHEN EXISTS(
					SELECT 1
					FROM ".DB_SCHEMA_ERP.".products_amazon
					WHERE asin = ".$db->quote($asin)."
						AND item = ".$db->quote($item)."
				)
				THEN 0
				ELSE 1
			END AS has_changed
	");

	$r = $q->fetch()['has_changed'];
	return (bool)$r;

}

function item_null($item){

	// Make sure the item is null when it shouldn't be.
	if(trim($item)==''){
		return null;
	}
	return $item;

}

function do_update($amazon_product_id, $item){

	// Update the relationship in the Amazon table.
	$db = DB::get();

	// Use a different query to set NULLs.
	if(is_null($item)){

		$db->query("
			UPDATE ".DB_SCHEMA_ERP.".products_amazon
			SET item = NULL
			WHERE amazon_product_id = ".$db->quote($amazon_product_id)."
		");

	}else{

		$db->query("
			UPDATE ".DB_SCHEMA_ERP.".products_amazon
			SET item = ".$db->quote($item)."
			WHERE amazon_product_id = ".$db->quote($amazon_product_id)."
		");

	}

}

function do_add($item, $asin, $amazon_part_number, $brand){

	// Insert a new mapping.
	$db = DB::get();
	$db->query("
		INSERT INTO ".DB_SCHEMA_ERP.".products_amazon (
			item, asin, amazon_part_number, brand
		)
		SELECT
			".$db->quote($item).",
			".$db->quote($asin).",
			".$db->quote($amazon_part_number).",
			".$db->quote($brand)."
	");

}

// Get all Amazon items.
$items = get_all_amazon();

// Get all item->Amazon mappings.
$mappings = get_all_mappings();

// Get the orders.
$orders = get_orders();

if(isset($_POST['action'])){

	// Handle printing.
	if($_POST['action'] == 'print'){

		try{

			// Get POSTed params.
			$item = $_POST['item'];
			$qty = $_POST['quantity'];

			// Get the ASIN.
			$amazon = get_amazon($item);
			$asin = $amazon['asin'];
			$brand = $amazon['brand'];
			$part_number = $amazon['amazon_part_number'];

			// Print the label.
			$labelPrinter = new LabelPrinter();
			$labelPrinter->printAmazonProductLabels($asin, $part_number, $qty, $brand);

			echo json_encode(array(
				'success' => true,
				'asin' => $asin,
				'amazon_part_number' => $part_number,
				'quantity' => $qty
			));

		}catch (Exception $e) {

			echo json_encode(array(
				'success' => false
			));

		}

		return;

	}

	// Handle editing mappings.
	elseif($_POST['action'] == 'edit-mapping') {

		// Get the POST data.
		$amazon_product_id = $_POST['amazon_product_id'];
		$item = $_POST['item'];
		$asin = $_POST['asin'];

		// Make sure the item posted is valid.
		$null = item_null($item);
		$valid = is_item_valid($item);

		// If the mapping hasn't changed, don't do anything.
		$changed = mapping_changed($item, $asin);
		if(!$changed){
			echo json_encode(array(
				'success' => true,
				'message' => 'Mapping has not changed.'
			));
			return;
		}

		// If the item has already been mapped, error.
		$mapped = is_item_mapped($item);
		if($mapped){
			echo json_encode(array(
				'success' => false,
				'message' => 'Item has already been mapped.'
			));
			return;
		}

		// If the item is null OR valid, update the record.
		if($null == null or $valid){

			// Do the upate.
			//do_update($amazon_product_id, $item){
			do_update($amazon_product_id, $null);

			echo json_encode(array(
				'success' => true,
				'amazon_product_id' => $amazon_product_id,
				'item' => $item,
				'null' => $null,
				'is_null' => is_null($null),
				'valid' => $valid,
				'mapped' => $mapped
			));

		}

		// Otherwise, the item POSTed shouldn't have been - don't do anything.
		else {
			echo json_encode(array(
				'success' => false,
				'message' => 'Invalid item',
				'amazon_product_id' => $amazon_product_id,
				'item' => $item
			));
		}

		return;

	}

	// Support creating new mappings.
	elseif($_POST['action'] == 'create') {

		// Get the POSTed data.
		$item = $_POST['item'];
		$asin = $_POST['asin'];
		$amazon_part_number = $_POST['amazon_part_number'];
		$brand = $_POST['brand'];

		// Make sure nothing is NULL.
		$item_null = item_null($item);
		$asin_null = item_null($asin);
		$amz_null = item_null($amazon_part_number);
		$brand_null = item_null($brand);

		// Check for nulls.
		$nulls = false;
		$reqd = array($item_null, $asin_null, $amz_null, $brand_null);
		foreach($reqd as $r){
			if(is_null($r)){$nulls=true;};
		}

		// Error on null field.
		if($nulls){
			echo json_encode(array(
				'success' => false,
				'message' => 'All fields must be populated.'
			));
			return;
		}

		// Make sure the item is valid.
		$valid = is_item_valid($item);

		// If the item is invalid, error.
		if(!$valid){
			echo json_encode(array(
				'success' => false,
				'message' => 'Item does not exist.'
			));
			return;
		}

		// Make sure the item hasn't already been mapped.
		$mapped = is_item_mapped($item);

		// If the item has been mapped, error.
		if($mapped){
			echo json_encode(array(
				'success' => false,
				'message' => 'Item has already been mapped.'
			));
			return;
		}

		// Make sure the ASIN hasn't already been mapped.
		$mapped = is_asin_mapped($asin);

		// If the ASIN has been mapped, error.
		if($mapped){
			echo json_encode(array(
				'success' => false,
				'message' => 'ASIN has already been mapped.'
			));
			return;
		}

		// Add the entry.
		do_add($item, $asin, $amazon_part_number, $brand);

		echo json_encode(array(
				'success' => true,
				'item' => $item,
				'asin' => $asin,
				'amazon_part_number' => $amazon_part_number,
				'brand' => $brand,
				'mapped' => $mapped
			));

		return;

	}

};

// Render the header.
Template::Render('header', $args, 'account');

?>

<style type="text/css">
	.row {
		padding-left: 15px;
	}
	.text-center {
		text-align: center !important;
	}
	.print-icon {
		cursor:pointer;
	}
	.print-icon-all {
		cursor:pointer;
	}
	.progress-bar {
		width:0%;
	}
	.all-quantity {
		width:40px;
	}
	#all-items-table .tablesorter-filter {
		font-size: 11px;
		line-height: 17px;
		height: 17px;
		padding: 2px 4px;
	}
	#mappings-table .tablesorter-filter {
		font-size: 11px;
		line-height: 17px;
		height: 17px;
		padding: 2px 4px;
	}
	#mappings-table-new .new-mapping-input{
		font-size: 11px;
		line-height: 17px;
		height: 17px;
		padding: 2px 4px;
	}
	.item-container input {
		width:90%;
	}
</style>

<script type="text/javascript">
$(document).ready(function(){

	// Keep track of whether labels are being printed.
	var printing = false

	// Handle AJAX.
	function do_ajax(data, callback){

		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){
			callback(rsp)
		})

	}

	function print(){

		// Only let one print job run at a time.
		if(printing){
			return
		}

		// Keep track of the job printing.
		printing = true

		// Get the TR values.
		var $row = $(this).parents('tr');
		//var $sono = $row.find('.sono-container').text();
		var $item = $row.find('.item-container').text();
		var $quantity = $row.find('.quantity-container').text();

		// Check to make sure the labels haven't already been printed.
		var $printed = $row.attr('data-printed')
		if($printed){
			alert('Already printed')
			printing = false
			return
		}

		// Get the progress bar.
		var $progress_container = $row.find('.progress')
		var $progress_bar = $row.find('.bar')

		// // Fill up the progress bar and animate it.
		$progress_bar.css('width', '100%')
		$progress_container.addClass('active')

		// The data to POST.
		var data = {
			'action' : 'print',
			'item' : $item,
			'quantity' : $quantity
		}

		// POST the data.
		do_ajax(data, function(rsp){

			// Get the success indactor.
			var success = rsp.success;

			// Request was successful.
			if(success){$progress_container.addClass('progress-success')} 
			// Request was not successful.
			else{$progress_container.addClass('progress-danger')}

			// Enable printing.
			printing = false

			// Keep track of which rows have been printed.
			$row.attr('data-printed', 'printed')

		})

		// Regardless of success, stop the progress bar.
		$progress_container.removeClass('active')

	}

	function print_all(){

		// Only let one print job run at a time.
		if(printing){
			return
		}

		// Keep track of the job printing.
		printing = true

		// Get the item number and quantity.
		var $tr = $(this).parents('tr')
		var item = $tr.find('.item-container').text()
		var qty = $tr.find('.all-quantity').val()

		// Check to make sure the labels haven't already been printed.
		var $printed = $tr.attr('data-printed')
		if($printed){
			alert('Already printed')
			printing = false
			return
		}

		// Get the progress bar.
		var $progress_container = $tr.find('.progress')
		var $progress_bar = $tr.find('.bar')

		// // Fill up the progress bar and animate it.
		$progress_bar.css('width', '100%')
		$progress_container.addClass('active')

		// The data to POST.
		var data = {
			'action' : 'print',
			'item' : item,
			'quantity' : qty
		}

		// POST the data.
		do_ajax(data, function(rsp){

			console.log(rsp)

			// Get the success indactor.
			var success = rsp.success;

			// Request was successful.
			if(success){$progress_container.addClass('progress-success')} 
			// Request was not successful.
			else{$progress_container.addClass('progress-danger')}

			// Enable printing.
			printing = false

			// Keep track of which rows have been printed.
			$tr.attr('data-printed', 'printed')
		})

		// Regardless of success, stop the progress bar.
		$progress_container.removeClass('active')

	}

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

	// Support editing the item value.
	function edit_item(){

		// Get the TR,
		var $td = $(this)
		var $tr = $td.parents('tr')

		// If there is already an input, don't do anything.
		if($td.find('input').length > 0){
			return;
		}

		// Get the current item value.
		var item = $td.text()

		// Create an input.
		var $input = $('<input type="text" class="map-edit">')
		$input.val(item)

		// Put the input in the td.
		$td.html($input)
		$input.focus()

	}

	// Submit the edit and update the database.
	function submit_edit(){

		// Get the input, td and tr.
		var $input = $(this)
		var $td = $input.parents('td')
		var $tr = $td.parents('tr')

		// Get the new item value, the amazon product ID and ASIN.
		var original = $tr.attr('data-oringal-item')
		var item = $input.val()
		var asin = $tr.find('.asin-container').text()
		var amazon_product_id = $tr.attr('data-amazon-product-id')

		// The data to POST.
		var data = {
			'action' : 'edit-mapping',
			'item' : item,
			'asin' : asin,
			'amazon_product_id' : amazon_product_id
		}

		// POST the data.
		do_ajax(data, function(rsp){
			
			console.log(rsp)

			// Was the request succesful?
			var success = rsp.success
			console.log(success)

			if(success){
				// Replace the input with the item value.
				$td.html(item)
			}

			// If it wasn't, let the client know about it.
			if(!success){

				// Replace the POSTed item with the original.
				$td.html(original)
				alert("Error - " + rsp.message)
			}

		})

	}

	// Support adding new mappings.
	$(document).keypress(function(e){

		// Ignore all other keystrokes.
		if(e.which == 13){

			// Get the tr.
			var $input = $(':focus')
			var $tr = $input.parents('tr')

			// Check to see in the input is for adding a new item.
			if(!$input.hasClass('new-mapping-input')){
				console.log($input.hasClass('new-mapping-input'))
				return
			}

			// Get the tr values.
			var asin = $tr.find('.asin-input input').val()
			var item = $tr.find('.item-input input').val()
			var amazon_part_number = $tr.find('.amazon-part-number-input input').val()
			var brand = $tr.find('.brand-input input').val()

			// The data to POST.
			var data = {
				'action' : 'create',
				'asin' : asin,
				'item' : item,
				'amazon_part_number' : amazon_part_number,
				'brand' : brand
			}

			// POST the data.
			do_ajax(data, function(rsp){

				console.log(rsp)

				// If the request was successful, empty all input.s
				var success = rsp.success
				if(success){
					$tr.find('input').val('')
				}else{
					alert(rsp.message)
				}

			})

		}

	})

	// Enable tab switching.
	$(document).on('click', '.nav-item', switch_tabs)

	// Enable print functionality.
	$(document).on('click', '.print-icon', print)

	// Enable arbitrary print functionality.
	$(document).on('click', '.print-icon-all', print_all)

	// Enable editing of the item field.
	$(document).on('click', '.mapping-item-container', edit_item)
	$(document).on('focusout', '.map-edit', submit_edit)

})
</script>

<div class="container padded col-xs-12 pull-left pad-left padded">

<h2>Amazon</h2>

<ul class="nav nav-tabs">
	<li class="nav-item active" data-target="open-tab">
		<a class="nav-link" href="#">Open Orders</a>
	</li>
	<li class="nav-item" data-target="all-tab">
		<a class="nav-link" href="#">All Items</a>
	</li>
	<?php
		if($session->hasRole('Administration')){
		?>
		<li class="nav-item" data-target="map-tab">
			<a class="nav-link" href="#">Mapping</a>
		</li>
		<?php
		}
	?>
</ul>

	<div id="open-tab" class="tab-target">
		<div class="row col-xs-12">
			<table id="amazon-table" class="table table-striped table-hover">
				<thead>
					<th>SO#</th>
					<th>Item</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Print</th>
					<th class="text-center">Progress</th>
				</thead>
				<tbody>
					<?php
						foreach($orders as $order){
							?>
							<tr class="item-row" data-printed="">
								<td class="sono-container"><?php print $order['sono']; ?></td>
								<td class="item-container"><?php print $order['item']; ?></td>
								<td class="text-center quantity-container"><?php print $order['qtyord']; ?></td>
								<td class="text-center print-icon"><i class="fa fa-fw fa-print"></i></td>
								<td class="progress-container">
									<div class="progress progress-striped">
										<div class="bar"></div>
									</div>
								</td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>.
		</div>
	</div>
	<div id="all-tab" class="tab-target" style="display:none;">
		<div class="row col-xs-12">
			<table id="all-items-table" class="table table-striped table-hover columns-sortable columns-filterable">
				<thead>
					<th class="filterable sortable">Item</th>
					<th class="filterable sortable">ASIN</th>
					<th class="span1">Quantity</th>
					<th class="text-center">Print</th>
					<th class="text-center">Progress</th>
				</thead>
				<tbody>
					<?php
						foreach($items as $item){
							?>
							<tr class="all-items-row" data-printed="">
								<td class="item-container"><?php print htmlentities($item['item']); ?></td>
								<td class="asin-container"><?php print htmlentities($item['asin']); ?></td>
								<td class="span1 quantity-container"><input class="all-quantity" name="all-quantity" type="number" min="0" value="1"/></td>
								<td class="text-center print-icon-all"><i class="fa fa-fw fa-print"></i></td>
								<td class="progress-container">
									<div class="progress progress-striped">
										<div class="bar"></div>
									</div>
								</td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
		if($session->hasRole('Administration')){
		?>

		<div id="map-tab" class="tab-target" style="display:none">

			<table id="mappings-table-new" class="table table-striped">
				<thead>
					<th>Item</th>
					<th>ASIN</th>
					<th>Amazon Part Number</th>
					<th>Brand</th>
				</thead>
				<tbody>
					<tr>
						<td class="item-input"><input class="new-mapping-input" type="text"></td>
						<td class="asin-input"><input class="new-mapping-input" type="text"></td>
						<td class="amazon-part-number-input"><input class="new-mapping-input" type="text"></td>
						<td class="brand-input"><input class="new-mapping-input" type="text"></td>
					</tr>
				</tbody>
			</table>

			<table id="mappings-table" class="table table-striped table-hover columns-sortable columns-filterable">
				<thead>
					<th class="filterable sortable">Item</th>
					<th class="filterable sortable">ASIN</th>
					<th class="filterable sortable">Amazon Part Number</th>
					<th class="filterable sortable">Brand</th>
				</thead>
				<tbody>
					<?php
						foreach($mappings AS $mapping){
							?>
							<tr data-amazon-product-id="<?php print htmlentities($mapping['amazon_product_id']); ?>" data-oringal-item="<?php print htmlentities($mapping['item']) ?>">
								<td class="mapping-item-container" ><?php print htmlentities($mapping['item']); ?></td>
								<td class="asin-container"><?php print htmlentities($mapping['asin']); ?></td>
								<td class="amazon-part-number-container"><?php print htmlentities($mapping['amazon_part_number']); ?></td>
								<td class="brand-container"><?php print htmlentities($mapping['brand']); ?></td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>		
		</div>

		<?php
		}
	?>
</div>