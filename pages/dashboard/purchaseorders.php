<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Purchase Orders',
	'breadcrumbs' => array(
		'Purchase Orders' => BASE_URI . '/dashboard/purchaseorders'
	)
);

Template::Render('header', $args, 'account');

// Default form input values.
$date_from_timestamp = time() - (86400 * 90);
$date_from_input = date('n/j/Y', $date_from_timestamp); // 90 Days Previous
$date_to_timestamp = time();
$date_to_input = date('n/j/Y', $date_to_timestamp);

$where = [];
if(!empty($_REQUEST['purno']) && ctype_digit($_REQUEST['purno'])) {
	// PO #
	$where[] = "LTRIM(RTRIM(pomast.purno)) = " . $db->quote($_REQUEST['purno']);
} else {
	/**
	 * Date constraints
	 */
	if(isset($_GET['date_from'])) {
		$date_from_input = $_GET['date_from'];
		$date_from_timestamp = strtotime($date_from_input);
	}
	$date_from_sql = date('Y/m/d', $date_from_timestamp);
	if(isset($_GET['date_to'])) {
		$date_to_input = $_GET['date_to'];
		$date_to_timestamp = strtotime($date_to_input);
	}
	// Determine whether we're filtering by Required Date or Add Date.
	$date_to_sql = date('Y/m/d', $date_to_timestamp);
	if(empty($_GET['filterby-date']) || $_GET['filterby-date'] == 'reqdate') {
		$date_column = 'pomast.reqdate';
	} else if($_GET['filterby-date'] == 'adddate') {
		$date_column = 'pomast.adddate';
	}
	$where[] = $date_column . " BETWEEN " . $db->quote($date_from_sql) . " AND " . $db->quote($date_to_sql);

	/**
	 * Status Constraints
	 */
	if(!empty($_GET['status-open'])) { // Open Order
		$status_sql[] = "(pomast.postat IS NULL OR pomast.postat = '' OR pomast.postat = ' ')";
	}
	if(!empty($_GET['status-partial'])) { // Partially Shipped Order
		$status_sql[] = "pomast.postat = 'O'";
	}
	if(!empty($_GET['status-received'])) { // Fully Shipped Order
		$status_sql[] = "pomast.postat = 'C'";
	}
	if(!empty($_GET['status-voided'])) { // Voided Order
		$status_sql[] = "pomast.postat = 'V'";
	}
	if(!empty($status_sql)) {
		$where[] = '(' . implode(') OR (', $status_sql) . ')';
	} else {
		$where[] = "pomast.postat IS NULL OR pomast.postat = '' OR pomast.postat = ' '";
	}

	/**
	 * Type Constraints
	 */
	$type_sql = array();
	if(!empty($_GET['type-open'])) { // Open Order
		$type_sql[] = "(pomast.potype IS NULL OR pomast.potype = '')";
	}
	if(!empty($_GET['type-partial'])) { // Partially Shipped Order
		$type_sql[] = "pomast.potype = 'O'";
	}
	if(!empty($_GET['type-received'])) { // Fully Shipped Order
		$type_sql[] = "pomast.potype = 'C'";
	}
	if(!empty($type_sql)) {
		$where[] = '(' . implode(') OR (', $type_sql) . ')';
	} else {
		$where[] = "pomast.potype IS NULL OR pomast.potype = ''";
	}
}

$where_sql = '(' . implode(') AND (', $where) . ')';
if(!empty($where_sql)) {
	$where_sql = ' WHERE ' . $where_sql;
}

// Grab the orders to display on this page.
// We use prepare / execute so we can actually return the rowCount().
$grab_orders = $db->prepare("
	SELECT
		CONVERT(varchar(10), pomast.reqdate, 120) AS required_date,
		pomast.vendno AS vendor,
		pomast.purno AS purchase_order_number,
		pomast.cnf_price AS price_confirmation,
		pomast.cnf_del AS delivery_confirmation,
		pomast.postat AS status,
		pomast.potype AS type,
		pomast.loctid AS location,
		pomast.notes AS notes,
		CONVERT(varchar(10), pomast.adddate, 120) AS add_date,
		pomast.adduser AS buyer
	FROM
		" . DB_SCHEMA_ERP . ".pomast
	" . $where_sql . "
	ORDER BY
		pomast.reqdate DESC
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed equired for retrieving rowCount.
$grab_orders->execute();

?>

<style type="text/css">
	#orders-container .content-notes .notes-new-button {
		height:23px;
		line-height:23px;
		padding:0 10px;
	}
	#orders-container .notes-container {
		/*overflow:hidden;
		text-overflow:ellipsis;
		max-height:32px;*/
	}
	#orders-container .notes-row {
		border-bottom:1px solid #ccc;
		padding-bottom:2px;
		padding-top:2px;
	}
	#orders-container .notes-row:first-of-type {
		padding-top:0;
	}
	#orders-container .notes-row:last-of-type {
		border-bottom:none;
		padding-bottom:0;
	}
	#orders-container .order-status-edit-container {
		cursor:pointer;
		font-size:1.5em;
		white-space:nowrap;
		display:inline-block;
	}
	#orders-container .notes-new-container {
		margin:0;
		min-height:23px;
	}
	#orders-container .notes-new-container button {
		font-size:11px;
		height:23px;
		line-height:17px;
		padding:2px 4px;
	}
	#orders-container .content-price-confirmation .value,
	#orders-container .content-delivery-confirmation .value,
	#orders-container .content-price-confirmation .edit-container,
	#orders-container .content-delivery-confirmation .edit-container,
	#orders-container .content-price-confirmation .icon,
	#orders-container .content-delivery-confirmation .icon,
	#orders-container .content-price-confirmation select,
	#orders-container .content-delivery-confirmation select {
		display:inline-block;
		line-height:20px;
		vertical-align:middle;
	}
	#orders-container .content-price-confirmation .icon,
	#orders-container .content-delivery-confirmation .icon {
		cursor:pointer;
		font-size:16px;
	}
	#orders-container .content-price-confirmation select,
	#orders-container .content-delivery-confirmation select {
		width:100px;
		margin:0;
	}
	#orders-container .content .edit-container .edit-icon {
		color:#00f;
	}
	#orders-container .content .edit-container .cancel-icon {
		color:#f00;
	}
	#dates-container td {
		vertical-align:top;
	}
</style>

<div class="padded">
	<fieldset>
		<legend>Search POs</legend>
		<form id="dates-container" method="get" class="form-horizontal">
			<input type="hidden" name="submit" value="date" />
			<table>
				<thead>
					<tr>
						<th style="text-align:center;">PO Status</th>
						<th style="text-align:center;">PO Type</th>
						<th style="text-align:center;">Date</th>
						<th style="text-align:center;"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="vertical-align:top;">
							<div class="control-group">
								<label class="control-label" for="status-open">Open</label>
								<div class="controls">
									<input type="checkbox" name="status-open" id="status-open" value="1" <?php !empty($_REQUEST['status-open']) || !isset($_REQUEST['submit']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="status-partial">Partially Received</label>
								<div class="controls">
									<input type="checkbox" name="status-partial" id="status-partial" value="1" <?php !empty($_REQUEST['status-partial']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="status-received">Closed</label>
								<div class="controls">
									<input type="checkbox" name="status-received" id="status-received" value="1" <?php !empty($_REQUEST['status-received']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="status-voided">Void</label>
								<div class="controls">
									<input type="checkbox" name="status-voided" id="status-voided" value="1" <?php !empty($_REQUEST['status-voided']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
						</td>
						<td style="vertical-align:top;">
							<div class="control-group">
								<label class="control-label" for="type-normal">Normal</label>
								<div class="controls">
									<input type="checkbox" name="type-normal" id="type-normal" value="1" <?php !empty($_REQUEST['type-normal']) || !isset($_REQUEST['submit']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="type-return">Return</label>
								<div class="controls">
									<input type="checkbox" name="type-return" id="type-return" value="1" <?php !empty($_REQUEST['type-return']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="type-dropship">Drop-Ship</label>
								<div class="controls">
									<input type="checkbox" name="type-dropship" id="type-dropship" value="1" <?php !empty($_REQUEST['type-dropship']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
						</td>
						<td style="vertical-align:top;">
							<div class="control-group">
								<label class="control-label" for="datepicker-from">Search By...</label>
								<div class="controls">
									<select class="span2" name="filterby-date">
										<option value="reqdate" <?php empty($_REQUEST['filterby-date']) || $_REQUEST['filterby-date'] == 'reqdate' ? print 'selected="selected"' : Null;?>>Required Date</option>
										<option value="adddate" <?php !empty($_REQUEST['filterby-date']) && $_REQUEST['filterby-date'] == 'adddate' ? print 'selected="selected"' : Null;?>>Add Date</option>
									</select>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="datepicker-from">From</label>
								<div class="controls">
									<input class="span2" type="text" name="date_from" id="datepicker-from" value="<?php print htmlentities($date_from_input, ENT_QUOTES);?>" />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="datepicker-to">To</label>
								<div class="controls">
									<input class="span2" type="text" name="date_to" id="datepicker-to" value="<?php print htmlentities($date_to_input, ENT_QUOTES);?>" />
								</div>
							</div>
						</td>
						<td>
							<div class="control-group">
								<label class="control-label" for="purno">PO #</label>
								<div class="controls">
									<input class="span2" type="text" name="purno" id="purno" value="<?php !empty($_REQUEST['purno']) ? print htmlentities($_REQUEST['purno'], ENT_QUOTES) : Null;?>" />
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="control-group">
								<div class="controls">
									<button class="btn btn-primary" type="submit">
										<i class="fa fa-calendar fa-fw"></i>
										Retrieve
									</button>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</fieldset>
</div>

<fieldset>
	<legend>
		<div class="padded-x">
			Purchase Orders Matching Criteria:
			<span id="order-count"><?php print number_format($grab_orders->rowCount());?></span>
		</div>
	</legend>
	<table id="orders-container" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
		<thead>
			<tr>
				<th class="filterable sortable">Required Date</th>
				<th class="filterable sortable">Vendor</th>
				<th class="filterable sortable" title="Purchase Order Number">PO #</th>
				<th class="filterable sortable">Price Confirmation</th>
				<th class="filterable sortable">Delivery Confirmation</th>
				<th class="filterable sortable">Status</th>
				<th class="filterable sortable">Type</th>
				<th class="filterable sortable">Location</th>
				<th class="filterable sortable">Notes</th>
				<th class="filterable sortable">Add Date</th>
				<th class="filterable sortable">Buyer</th>
			</tr>
		<tbody>
			<?php
			while($order = $grab_orders->fetch()) {
				//$required_date = new DateTime($order['required_date']);
				//$add_date = new DateTime($order['add_date']);
				?>
				<tr class="stripe" purchase-order-number="<?php print trim($order['purchase_order_number']);?>">
					<td class="content content-required-date"><?php
						print $order['required_date'];
					?></td>
					<td class="content content-vendor"><?php print htmlentities($order['vendor']);?></td>
					<td class="content content-purchase-order-number overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/purchaseorders/details" overlayz-data="<?php print htmlentities(json_encode(['purno' => trim($order['purchase_order_number'])]), ENT_QUOTES);?>" overlayz-response-type="html"><?php print htmlentities($order['purchase_order_number']);?></td>
					<td class="content content-price-confirmation">
						<div class="edit-container">
							<i class="icon edit-icon fa fa-pencil fa-fw"></i>
						</div>
						<div class="value">
							<?php
							$order['price_confirmation'] = trim($order['price_confirmation']);
							if(empty($order['price_confirmation'])) {
								print 'Waiting';
							} else {
								print htmlentities($order['price_confirmation']);
							}
							?>
						</div>
					</td>
					<td class="content content-delivery-confirmation">
						<div class="edit-container">
							<i class="icon edit-icon fa fa-pencil fa-fw"></i>
						</div>
						<div class="value">
							<?php
							$order['delivery_confirmation'] = trim($order['delivery_confirmation']);
							if(empty($order['delivery_confirmation'])) {
								print 'Waiting';
							} else {
								print htmlentities($order['delivery_confirmation']);
							}
							?>
						</div>
					</td>
					<td class="content content-status"><?php
						if(!trim($order['status'])) {
							print 'Open';
						} else if($order['status'] == 'O') {
							print 'Partially Received';
						} else if($order['status'] == 'C') {
							print 'Closed';
						} else if($order['status'] == 'V') {
							print 'Void';
						} else {
							print '"' . $order['status'] . '" (???)';
						}
					?></td>
					<td class="content content-type"><?php
						if(!trim($order['type'])) {
							print 'Normal';
						} else if($order['type'] == 'R') {
							print 'Return';
						} else if($order['type'] == 'D') {
							print 'Drop Ship';
						} else {
							print '"' . $order['type'] . '" (???)';
						}
					?></td>
					<td class="content content-location"><?php print htmlentities($order['location']);?></td>
					<td class="content content-notes">
						<div class="notes-container">
							<?php
							foreach(explode("\n", trim($order['notes'])) as $note) {
								$note = trim(str_replace("\r", '', $note));
								if(!empty($note)) {
									?>
									<div class="notes-row">
										<?php print htmlentities($note);?>
									</div>
									<?php
								}
							}
							?>
						</div>
						<div class="notes-new-container input-append input-block-level">
							<input type="text" class="notes-new-input" />
							<button class="notes-new-button btn" type="button">Add</button>
						</div>
					</td>
					<td class="content content-add-date"><?php
						print $order['add_date'];
					?></td>
					<td class="content content-buyer"><?php print htmlentities($order['buyer']);?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</fieldset>

<script type="text/javascript">
/**
 * Bind DatePickers to Date Range "From" and "To" input boxes.
 */
var $datetime_from = $('#datepicker-from');
var $datetime_to = $('#datepicker-to');

var datepicker_config = {
	'lang': 'en',
	'datepicker': true,
	'timepicker': false,
	'formatDate': 'n/j/Y', //'formatDate': 'n/j/Y g:ia',
	'format': 'n/j/Y',
	'closeOnDateSelect': true
};

var datepicker_from_config = {};
$.extend(datepicker_from_config, datepicker_config, {
	'onShow': function(selected_datetime) {
		this.setOptions({
			'maxDate': $datetime_to.val(),
			'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
		});
	},
	'onChangeDateTime': function(selected_datetime) {
		this.setOptions({
			'maxDate': $datetime_to.val(),
			'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
		});
	}
});

var datepicker_to_config = {};
$.extend(datepicker_to_config, datepicker_config, {
	'onShow': function(selected_datetime) {
		this.setOptions({
			'minDate': $datetime_from.val(),
			'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
		});
	},
	'onChangeDateTime': function(selected_datetime) {
		this.setOptions({
			'minDate': $datetime_from.val(),
			'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
		});
	}
});

$datetime_from.datetimepicker(datepicker_from_config);
$datetime_to.datetimepicker(datepicker_to_config);

/**
* Bind to Order Status "Edit" icon presses
 */
$(document).off('click', '#orders-container .order-status-edit-icon');
$(document).on('click', '#orders-container .order-status-edit-icon', function(event) {
	var $order_container = $(event.target).closest('tr');
	var $existing_order_notes_container = $order_container.find('.order-notes-container');
	var order_id = $order_container.attr('order-id');
	var $edit_container = $order_container.find('.order-status-edit-container');
	var $status_container = $order_container.find('.order-status-container');
	var current_status = $status_container.text().trim();
	var $edit_icon = $edit_container.find('.order-status-edit-icon');
	var $status_select = $('<select class="order-status-edit-select">');
	var $cancel_icon = $('<i class="order-status-edit-cancel-icon fa fa-times fw" title="Cancel"></i>');

	// Populate the options of the `$status_select`.
	$.each(order_statuses, function(order_status_value, order_status_name) {
		$status_select.append(
			$('<option>').attr('value', order_status_value).text(order_status_name)
		);
	});

	$status_container.hide(); // Hide status container
	$edit_icon.hide(); // Hide edit icon
	$cancel_icon.appendTo($edit_container); // Show cancel icon
	$status_select.appendTo($edit_container); // Show status select

	$status_select.val(current_status);

	var $ajax_loading_container = $('<div class="ajax-loading-container">').append(
		$('<img src="' + STATIC_PATH + '/images/ajax-loading-horizontal.gif" />')
	);

	/**
	 * Bind to Select changes.
	 */
	$status_select.on('change', function(event) {
		var order_status = $status_select.val();

		$.ajax({
			'url': BASE_URI + '/dashboard/sales-order-status/order-status-update',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'order-id': order_id,
				'order-status': order_status
			},
			'beforeSend': function(jqXHR, settings) {
				$status_select.hide(); // Hide status select
				$cancel_icon.hide(); // Hide cancel icon

				$ajax_loading_container.appendTo($edit_container);
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$status_container.text(order_status);
					$existing_order_notes_container.append(
						$('<div class="order-notes-row">').text(data.note)
					);
				} else {
					alert(data.message);
				}
			},
			'complete': function(jqXHR, status) {
				$status_select.remove(); // Remove status select
				$cancel_icon.remove(); // Remove cancel icon
				$edit_icon.show(); // Show edit icon
				$status_container.show(); // Show status container
				$ajax_loading_container.remove();
			}
		});
	});

	/**
	 * Bind to "Cancel" icon clicks.
	 */
	$cancel_icon.on('click', function(event) {
		$status_select.remove(); // Remove status select
		$cancel_icon.remove(); // Remove cancel icon
		$edit_icon.show(); // Show edit icon
		$status_container.show(); // Show status container
	});
});

/**
 * Bind to page loads in which an AJAX request is fired to retrieve which
 * orders contain tracking information, adding icons appropriately.
 */
$(function() {
	// Grab all sales order IDs displayed within the interface and pack them
	// into an array.
	var sales_order_ids = [];
	var $sales_order_trs = $('#orders-container tr');
	$.each($sales_order_trs, function(index, tr) {
		var $tr = $(tr);
		var sales_order_number = $tr.attr('sales-order-number');
		if(sales_order_number) {
			sales_order_ids.push(sales_order_number);
		}
	});

	// Perform ana AJAX query, which will return Sales Order IDs which
	// we have tracking information for.
	$.ajax({
		'url': BASE_URI + '/dashboard/sales-order-status/shipping-icons',
		'type': 'POST',
		'dataType': 'json',
		'data': {
			'sales-order-ids': sales_order_ids
		},
		'success': function(data) {
			if(data.success) {
				$.each(data['sales-order-ids'], function(index, sales_order_id) {
					var $tr = $('#orders-container .sales-order-' + sales_order_id);
					var $po_status_container = $tr.find('.content-po-status');

					// Check if a shipping icon is already present.
					var $icon = $po_status_container.find('.content-po-status-shipped');
					if(!$icon.length) {
						// When icon is not present, add it.
						$icon = $('<i>').addClass('content-po-status-shipped').addClass('fa').addClass('fw');

						// Determine whether to show open box or closed box icon.
						if($po_status_container.hasClass('content-po-status-shipped')) {
							var icon_class = 'fa-archive fa-2x';
							$icon.attr('title', 'Fully Shipped');
						} else if($po_status_container.hasClass('content-po-status-partial')) {
							var icon_class = 'fa-dropbox fa-2x';
							$icon.attr('title', 'Partially Shipped');
						} else if($po_status_container.hasClass('content-po-status-open')) {
							var icon_class = 'fa-building-o fa-2x';
							$icon.attr('title', 'Not Yet Shipped');
						} else {
							// Better to show the wrong icon than no icon at all :)
							var icon_class = 'fa-building-o fa-2x';
						}
						$icon.addClass(icon_class);
						$icon.prependTo($po_status_container);
					}
				});
			}
		}
	});
});

/**
 * Defines the variable used in storing touch events for later comparison.
 */
var touch_target;

/**
 * Ensure options within "Available" drop-down are never selected.
 */
var available_option_click_fn = function(event) {
	var $option = $(this);

	var $available_container = $option.closest('.search-field-available');
	var $filterby_container = $available_container.closest('.search-field').find('.search-field-filterby');

	// Grab the name for the hiddne input to be added to the option.
	var input_name = $available_container.attr('input-name');

	// Grab the value for the hidden input to be added to the option.
	var input_value = $option.attr('input-value');

	$option.appendTo($filterby_container).append(
		$('<input type="hidden">').attr('name', input_name).attr('value', input_value)
	);

	// Sort Filter By options by name.
	var $filterby_options = $filterby_container.find('.search-field-value');
	$filterby_options.sort(function(a, b) {
		var a_name = a.getAttribute('input-value'),
			b_name = b.getAttribute('input-value');
		return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
	});
	$filterby_options.detach().appendTo($filterby_container);
};
$(document).on('click', '#dates-container .search-field-available .search-field-value', available_option_click_fn);
$(document).on('touchstart', '#dates-container .search-field-available .search-field-value', function(event) {
	touch_target = event.target;
});
$(document).on('touchend', '#dates-container .search-field-available .search-field-value', function(event) {
	if(touch_target == event.target) {
		// Only activate when touch start and touch end elements match up.
		filterby_option_click_fn(event);
	}
});

/**
 * Bind to change events on Selected Statuses drop-down.
 */
var filterby_option_click_fn = function(event) {
	var $option = $(this);

	var $filterby_container = $option.closest('.search-field-filterby');
	var $available_container = $filterby_container.closest('.search-field').find('.search-field-available');

	$option.appendTo($available_container);
	$option.find('input').remove();

	// Sort Available options by name.
	var $available_options = $available_container.find('.search-field-value');
	$available_options.sort(function(a, b) {
		var a_name = a.getAttribute('input-value'),
			b_name = b.getAttribute('input-value');
		return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
	});
	$available_options.detach().appendTo($available_container);
};
$(document).on('click', '#dates-container .search-field-filterby .search-field-value', filterby_option_click_fn);
$(document).on('touchstart', '#dates-container .search-field-filterby .search-field-value', function(event) {
	touch_target = event.target;
});
$(document).on('touchend', '#dates-container .search-field-filterby .search-field-value', function(event) {
	if(touch_target == event.target) {
		// Only activate when touch start and touch end elements match up.
		filterby_option_click_fn(event);
	}
});

/**
 * Bind to clicks on "+" and "-" icons within search fields.
 */
$(document).off('click', '.search-field-add'); // Prevents double-binding.
$(document).on('click', '.search-field-add', function() {
	var $icon = $(this);
	var $search_field_container = $icon.closest('.search-field');
	var $available_container = $search_field_container.find('.search-field-available');
	$available_container.find('.search-field-value').click();
});
$(document).off('click', '.search-field-remove'); // Prevents double-binding.
$(document).on('click', '.search-field-remove', function() {
	var $icon = $(this);
	var $search_field_container = $icon.closest('.search-field');
	var $filter_by_container = $search_field_container.find('.search-field-filterby');
	$filter_by_container.find('.search-field-value').click();
});

/**
$(function() {
	var $datetime_from = $('#datepicker-from');
	var $datetime_to = $('#datepicker-to');

	var datepicker_config = {
		'lang': 'en',
		'datepicker': true,
		'timepicker': false,
		'formatDate': 'n/j/Y', //'formatDate': 'n/j/Y g:ia',
		'format': 'n/j/Y',
		'closeOnDateSelect': true
	};

	var datepicker_from_config = {};
	$.extend(datepicker_from_config, datepicker_config, {
		'onShow': function(selected_datetime) {
			this.setOptions({
				'maxDate': $datetime_to.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		},
		'onChangeDateTime': function(selected_datetime) {
			this.setOptions({
				'maxDate': $datetime_to.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		}
	});

	var datepicker_to_config = {};
	$.extend(datepicker_to_config, datepicker_config, {
		'onShow': function(selected_datetime) {
			this.setOptions({
				'minDate': $datetime_from.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		},
		'onChangeDateTime': function(selected_datetime) {
			this.setOptions({
				'minDate': $datetime_from.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		}
	});

	$datetime_from.datetimepicker(datepicker_from_config);
	$datetime_to.datetimepicker(datepicker_to_config);

	// Bind to Order Note input "Add" button clicks and "Enter/Return"
	// key-presses.
	var new_notes_callback = function(event) {
		if(event.type === 'keydown' && event.keyCode !== 13) {
			return;
		}

		var $purchase_order_number_container = $(event.target).closest('tr');
		var purchase_order_number = $purchase_order_number_container.attr('purchase-order-number');

		var $notes_container = $purchase_order_number_container.find('.content-notes');

		var $existing_notes_container = $notes_container.find('.notes-container');

		var $new_notes_container = $notes_container.find('.notes-new-container');
		var $new_note_input = $new_notes_container.find('.notes-new-input');
		var note = $new_note_input.val();

		// If the input is empty, there is nothing to append.
		if(note === undefined || !note.length) {
			return;
		}

		var $ajax_loading_container;

		$.ajax({
			'url': BASE_URI + '/dashboard/purchase-order-status/notes-append',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'purchase-order-number': purchase_order_number,
				'note': note
			},
			'beforeSend': function(jqXHR, settings) {
				$ajax_loading_container = $('<div class="ajax-loading-container">').append(
					$('<img src="' + STATIC_PATH + '/images/ajax-loading-horizontal.gif" />')
				);
				$existing_notes_container.append($ajax_loading_container);
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$existing_notes_container.append(
						$('<div class="notes-row">').text(data.note)
					);
					$new_note_input.val('');
				} else {
					alert(data.message);
				}
			},
			'complete': function(jqXHR, status) {
				$ajax_loading_container.remove();
			}
		});
	};
	$(document).off('click', '#orders-container .notes-new-button');
	$(document).on('click', '#orders-container .notes-new-button', new_notes_callback);
	$(document).off('keydown', '#orders-container .notes-new-input');
	$(document).on('keydown', '#orders-container .notes-new-input', new_notes_callback);

	// Bind to Price Confirmation and Delivery Confirmation "edit" and
	// "cancel" icon clicks.
	$(document).off('click', '#orders-container .content-price-confirmation .icon, #orders-container .content-delivery-confirmation .icon');
	$(document).on('click', '#orders-container .content-price-confirmation .icon, #orders-container .content-delivery-confirmation .icon', function(event) {
		var $icon = $(event.target);
		var $icon_container = $icon.closest('.edit-container');
		var $value_container = $icon_container.siblings('.value');
		var current_value = $value_container.text().trim();

		if($icon.hasClass('edit-icon')) {
			// "Edit" icon clicked.

			// Replace "edit" icon with "cancel" icon.
			$icon.removeClass('edit-icon').removeClass('fa-pencil').addClass('cancel-icon').addClass('fa-times');

			// Add and populate the drop-down.
			$icon_container.append(
				$('<select>').append(
					$('<option>').attr('value', 'Waiting').text('Waiting').prop('selected', true),
					$('<option>').attr('value', 'Confirmed').text('Confirmed'),
					$('<option>').attr('value', 'Discrepancy').text('Discrepancy')
				)
			);
			$icon_container.find('option[value="' + current_value + '"]').prop('selected', true);

			// Hide the current value.
			$value_container.hide();
		} else {
			// "Cancel" icon clicked.

			// Replace "cancel" icon with "edit" icon.
			$icon.removeClass('cancel-icon').removeClass('fa-times').addClass('edit-icon').addClass('fa-pencil');

			// Remove the drop-down.
			$icon_container.find('select').remove();

			// Show the current value.
			$value_container.show();
		}
	});

	// Bind to Price Confirmation and Delivery Confirmation drop-down selection
	// changes.
	$(document).off('change', '#orders-container .content-price-confirmation select, #orders-container .content-delivery-confirmation select');
	$(document).on('change', '#orders-container .content-price-confirmation select, #orders-container .content-delivery-confirmation select', function(event) {
		var $select = $(event.target);
		var $icon = $select.siblings('.icon');
		var $icon_container = $icon.closest('.edit-container');
		var $value_container = $icon_container.siblings('.value');
		var $content_container = $value_container.closest('.content');

		var $purchase_order_number_container = $content_container.closest('tr');
		var purchase_order_number = $purchase_order_number_container.attr('purchase-order-number');

		var $notes_container = $purchase_order_number_container.find('.content-notes .notes-container');

		// Grab the newly selected value.
		var new_value = $select.val();

		var type = '';
		if($content_container.hasClass('content-price-confirmation')) {
			type = 'price';
		} else if($content_container.hasClass('content-delivery-confirmation')) {
			type = 'delivery';
		}

		// Perform AJAX request to apply changes.
		var $ajax_loading_container;
		$.ajax({
			'url': BASE_URI + '/dashboard/purchase-order-status/confirmation-set',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'purchase-order-number': purchase_order_number,
				'type': type,
				'value': new_value
			},
			'beforeSend': function(jqXHR, settings) {
				// Hide the icon and select drop-down.
				$select.hide();
				$icon.hide();

				$ajax_loading_container = $('<div class="ajax-loading-container">').append(
					$('<img src="' + STATIC_PATH + '/images/ajax-loading-horizontal.gif" />')
				);
				$icon_container.append($ajax_loading_container);
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					// Set the newly selected value in the value container and display the value.
					$value_container.text(new_value).show();

					// Replace "cancel" icon with "edit" icon, and show the icon.
					$icon.removeClass('cancel-icon').removeClass('fa-times').addClass('edit-icon').addClass('fa-pencil').show();

					// Remove the select drop-down.
					$select.remove();

					// Append notes.
					$('<div class="notes-row">').text(data.note).hide().appendTo($notes_container).slideDown();
				} else {
					alert(data.message);
				}
			},
			'complete': function(jqXHR, status) {
				$ajax_loading_container.remove();
			}
		});
	});
});
**/

</script>
<?php

Template::Render('footer', 'account');
