<?php

$session->ensureLogin();

$args = array(
	'title' => 'Web Orders',
	'breadcrumbs' => array(
		'Orders' => BASE_URI . '/dashboard/sales-orders/web',
		'Web' => BASE_URI . '/dashboard/sales-orders/web'
	)
);

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	h3 {
		font-size: 21px;
	}
	tbody tr:hover td {
		background-color: #eee;
		border-bottom: 1px solid black;
		border-top: 1px solid black;
	}
	tr:nth-child(2n) {
		background-color: #f9f9f9;
	}
	tr:nth-child(2n+1):last-of-type {
		border-bottom: 1px solid #ddd;
	}
	tbody tr td {
		border-top: 1px solid #ddd;
		font-size: 11px;
		line-height: 16px;
		padding: 8px;
	}
	thead, tfoot {
		background-color: #f9f9f9;
		font-size: 11px;
		line-height: 16px;
	}
	thead tr > *, tfoot tr > * {
		font-weight: bold;
		padding: 8px;
	}
	table {
		width: 100%;
	}
	tfoot {
		background-color: #ddd;
	}
	.right {
		text-align: right;
	}
	.content-po-status {
		color: #840;
		font-size: 1.4em;
	}
	.datetime {
		font-size: 12px;
		font-style: italic;
		line-height: 21px;
		vertical-align: middle;
		font-weight: normal;
		display: inline-block;
		padding-left: 20px;
	}
	.table-striped tbody > tr.late:nth-child(odd) > td,
	.table-striped tbody > tr.late:nth-child(even) > td {
		background-color:#fcc;
	}
	img.late-icon {
		max-width: 24px;
	}
</style>

<h2 class="padded">Web Orders</h2>

<fieldset>
	<legend class="padded">Due Today & Tomorrow <span class="datetime" id="priority-datetime"></span></legend>
	<table class="table table-small table-striped table-hover" id="priority-container">
		<thead>
			<tr>
				<th></th>
				<th>Order Status</th>
				<th></th>
				<th>SO #</th>
				<th>Territory</th>
				<th>Location</th>
				<th>Customer</th>
				<th title="Customer Purchase Order Number">Customer PO</th>
				<th>Sales Person</th>
				<th>Who Entered</th>
				<th>Add Date</th>
				<th>Due Date</th>
			</tr>
		</thead>
		<tbody id="priority-tbody"></tbody>
	</table>
</fieldset>

<fieldset>
	<legend class="padded">Due In Next 14 Days <span class="datetime" id="outlook-datetime"></span></legend>
	<table class="table table-small table-striped table-hover" id="outlook-container">
		<thead>
			<tr>
				<th></th>
				<th>Order Status</th>
				<th></th>
				<th>SO #</th>
				<th>Territory</th>
				<th>Location</th>
				<th>Customer</th>
				<th title="Customer Purchase Order Number">Customer PO</th>
				<th>Sales Person</th>
				<th>Who Entered</th>
				<th>Add Date</th>
				<th>Due Date</th>
			</tr>
		</thead>
		<tbody id="outlook-tbody"></tbody>
	</table>
</fieldset>

<!--
<div class="padded">
	<fieldset>
		<legend>
			Web Orders
			<span style="font-size:12px;font-style:italic;line-height:21px;vertical-align:middle;font-weight:normal;display:inline-block;padding-left:20px;" id="web-datetime"></span>
		</legend>

		<table id="web-table">
			<thead>
				<tr>
					<th>Order Status</th>
					<th title="Sales Order Status"><i class="fa fa-dropbox fw"></i></th>
					<th>SO #</th>
					<th>Territory</th>
					<th>Location</th>
					<th>Customer</th>
					<th title="Customer Purchase Order Number">Customer PO</th>
					<th>Sales Person</th>
					<th>Who Entered</th>
					<th>Add Date</th>
					<th>Due Date</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</fieldset>
</div>
-->

<script type="text/javascript">
	var refresh_interval = 60 * 1000 * 5; // 5 mins

	var containers = {
		'priority': {
			'datetime': $('#priority-datetime'),
			'tbody': $('#priority-container > tbody')
		},
		'outlook': {
			'datetime': $('#outlook-datetime'),
			'tbody': $('#outlook-container > tbody')
		}
	};

	$(function() {
		/**
		 * POPULATE "WEB" TABLES
		 */
		var loadOrders = function() {
			$.each(containers, function(request_type, request) {
				var $datetime = request.datetime;
				var $tbody = request.tbody;

				$.ajax({
					'url': BASE_URI + '/dashboard/sales-orders/web/retrieve',
					'data': {
						'type': request_type
					},
					'dataType': 'json',
					'success': function(data) {
						// Populate Date/Time
						$datetime.text('Last updated on ' + data.datetime);

						// Populate sales.
						$tbody.empty();

						// Populate web data.
						$.each(data['web'], function(index, record) {
							var sales_order_status;
							if(record.sales_order_status === 'C') {
								sales_order_status = 'closed';
							} else if(record.sales_order_status === null || record.sales_order_status === '' && record.sales_order_type === 'O') {
								sales_order_status = 'partial';
							} else {
								sales_order_status = 'open';
							}

							var sales_order_icon;
							if(sales_order_status === 'closed') {
								// Closed Order
								sales_order_icon = $('<i class="content-po-status-shipped fa fa-archive fw" title="Fully Shipped">');
							} else if(sales_order_status === 'partial') {
								// Partially Closed Order
								sales_order_icon = $('<i class="content-po-status-shipped fa fa-dropbox fw" title="Partially Shipped"></i>');
							} else {
								// Open Order
								sales_order_icon = '';
							}

							var late = '';
							if(record.late) {
								late = $('<img src="/interface/images/skullandbones.png" class="late-icon" />');
							}
							$('<tr>').addClass(late ? 'late' : '').append(
								$('<td>').addClass().append(late),
								$('<td>').addClass('content').text(record['status']),
								$('<td>').addClass('content').addClass('content-po-status').append(sales_order_icon),
								$('<td>')
									.addClass('content')
									.addClass('content-sales-order-number')
									.addClass('overlayz-link')
									.attr('overlayz-url', BASE_URI + '/dashboard/sales-order-status/so-details')
									.attr('overlayz-data', JSON.stringify({
										'so-number': record['sales_order_number'].trim()
									}))
									.text(record['sales_order_number'])
								,
								$('<td>').addClass('content').text(record['territory']),
								$('<td>').addClass('content').text(record['location']),
								$('<td>').addClass('content').text(record['customer_code']),
								$('<td>').addClass('content').text(record['customer_po']),
								$('<td>').addClass('content').text(record['sales_person']),
								$('<td>').addClass('content').text(record['who_entered']),
								$('<td>').addClass('content').text(record['input_date']),
								$('<td>').addClass('content').text(record['due_date'])
							).attr('sales-order-number', record['sales_order_number']).addClass('sales-order-' + record['sales_order_number']).appendTo($tbody);
						});

						/**
						 * Bind to page loads in which an AJAX request is fired to retrieve which
						 * orders contain tracking information, adding icons appropriately.
						 */
						(function() {
							// Grab all sales order IDs displayed within the interface and pack them
							// into an array.
							var sales_order_ids = [];
							var $sales_order_trs = $('#priority-tbody > tr, #outlook-tbody > tr');
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
											var $tr = $('#hot-table .sales-order-' + sales_order_id);
											var $po_status_container = $tr.find('.content-po-status');

											// Check if a shipping icon is already present.
											var $icon = $po_status_container.find('.content-po-status-shipped');
											if(!$icon.length) {
												// When icon is not present, add it.
												$icon = $('<i>').addClass('content-po-status-shipped').addClass('fa').addClass('fw');

												// Determine whether to show open box or closed box icon.
												if($po_status_container.hasClass('content-po-status-shipped')) {
													var icon_class = 'fa-archive';
													$icon.attr('title', 'Fully Shipped');
												} else if($po_status_container.hasClass('content-po-status-partial')) {
													var icon_class = 'fa-dropbox';
													$icon.attr('title', 'Partially Shipped');
												} else if($po_status_container.hasClass('content-po-status-open')) {
													var icon_class = 'fa-building-o';
													$icon.attr('title', 'Not Yet Shipped');
												} else {
													// Better to show the wrong icon than no icon at all :)
													var icon_class = 'fa-building-o';
												}
												$icon.addClass(icon_class);
												$icon.prependTo($po_status_container);
											}
										});
									}
								}
							});
						})();
					}
				});
			});
		};

		loadOrders();
		setInterval(loadOrders, refresh_interval);
	});
</script>
<?php

Template::Render('footer', 'account');
