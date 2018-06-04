<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2016, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'My Account',
	'breadcrumbs' => array(
		'VIC Accounts' => BASE_URI . '/dashboard/vics'
	),
	'body-class' => 'padded'
);

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	.table-striped tbody > tr.late:nth-child(odd) > td,
	.table-striped tbody > tr.late:nth-child(even) > td {
		background-color:#fcc;
	}
	.late-notifier {
		color:#f00;
		font-weight:bold;
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
	img.late-icon {
		max-width:24px;
	}
</style>

<h2 class="padded">VIC Orders</h2>

<fieldset>
	<legend class="padded">Due Today & Tomorrow <span class="datetime" id="priority-datetime"></span></legend>
	<table class="table table-small table-striped table-hover" id="priority-container">
		<head>
			<tr>
				<th>Due Date</th>
				<th>Code</th>
				<th>Client</th>
				<th>SO #</th>
				<th>PO #</th>
				<th>Status</th>
				<th>Location</th>
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
				<th>Due Date</th>
				<th>Code</th>
				<th>Client</th>
				<th>SO #</th>
				<th>PO #</th>
				<th>Status</th>
				<th>Location</th>
			</tr>
		</thead>
		<tbody id="outlook-tbody"></tbody>
	</table>
</fieldset>

<script type="text/javascript">
$(document).ready(function() {
	var refresh_interval = 60 * 1000 * 5; // 5 mins

	/**
	 * POPULATE "PERSONAL SALES" TABLES
	 */
	var $priority_datetime = $('#priority-datetime');
	var $priority_table = $('#priority-table');
	var $priority_tbody = $('#priority-tbody');

	var $outlook_datetime = $('#outlook-datetime');
	var $outlook_table = $('#outlook-table');
	var $outlook_tbody = $('#outlook-tbody');

	var loadOrders = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard/vics/orders',
			'data': {
				'type': 'priority'
			},
			'dataType': 'json',
			'success': function(data) {
				// Populate Date/Time
				$priority_datetime.text('Last updated on ' + data.datetime);

				// Populate sales.
				$priority_tbody.empty();

				$.each(data.orders, function(offset, order) {
					var $tr = $('<tr>').append(
						$('<td>').text(order['ordate']),
						$('<td>').text(order['custno']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/clients/details').attr('overlayz-data', JSON.stringify({
							'custno': order['custno']
						})),
						$('<td>').text(order['company']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/clients/details').attr('overlayz-data', JSON.stringify({
							'custno': order['custno']
						})),
						$('<td>').text(order['sono']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/sales-order-status/so-details').attr('overlayz-data', JSON.stringify({
							'so-number': order['sono']
						})),
						$('<td>').text(order['ponum']),
						$('<td>').text(order['orderstat']),
						$('<td>').text(order['defloc'])
					);
					$tr.appendTo($priority_tbody);
				});
			}
		});

		$.ajax({
			'url': BASE_URI + '/dashboard/vics/orders',
			'data': {
				'type': 'outlook'
			},
			'dataType': 'json',
			'success': function(data) {
				// Populate Date/Time
				$outlook_datetime.text('Last updated on ' + data.datetime);
				// Populate sales.
				$outlook_tbody.empty();

				$.each(data.orders, function(offset, order) {
					var late_text = '';
					if(order['late'] === true) {
						late_text = $('<img src="/interface/images/skullandbones.png" class="late-icon" />');
					}

					var $tr = $('<tr>').append(
						$('<td>').html(late_text),
						$('<td>').text(order['ordate']),
						$('<td>').text(order['custno']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/clients/details').attr('overlayz-data', JSON.stringify({
							'custno': order['custno']
						})),
						$('<td>').text(order['company']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/clients/details').attr('overlayz-data', JSON.stringify({
							'custno': order['custno']
						})),
						$('<td>').text(order['sono']).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/sales-order-status/so-details').attr('overlayz-data', JSON.stringify({
							'so-number': order['sono']
						})),
						$('<td>').text(order['ponum']),
						$('<td>').text(order['orderstat']),
						$('<td>').text(order['defloc'])
					);
					if(order['late'] === true) {
						$tr.addClass('late');
					}
					$tr.appendTo($outlook_tbody);
				});
			}
		});
	 };
	loadOrders();
	setInterval(loadOrders, refresh_interval);
});
</script>

<?php Template::Render('footer', 'account');
