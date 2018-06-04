<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$args = array(
	'title' => 'Sales Orders Not Printed',
	'breadcrumbs' => array(
		'Orders' => BASE_URI . '/dashboard/sales-orders/not-printed',
		'Not Printed' => BASE_URI . '/dashboard/sales-orders/not-printed'
	)
);

Template::Render('header', $args, 'account');

?>

<script type="text/javascript">
	$(function() {
		var one_minute = 60 * 1000; // Calculated in milliseconds.

		/**
		 * POPULATE "NOT PRINTED" TABLE
		 */
		var $notprinted_datetime = $('#notprinted-datetime');
		var $notprinted_table = $('#notprinted-table');
		var loadNotPrinted = function() {
			$.ajax({
				'url': BASE_URI + '/dashboard/sales-orders/not-printed/retrieve',
				'dataType': 'json',
				'success': function(data) {
					// Populate Date/Time
					$notprinted_datetime.text('Last updated on ' + data.datetime);

					var $notprinted_container = $notprinted_table.find('tbody');
					$notprinted_container.empty();

					// Populate not printed data.
					$.each(data['not-printed'], function(index, not_printed) {
						$('<tr>').append(
							$('<td class="mark-printed">').append(
								$('<a href="' + BASE_URI + '/dashboard/sales-orders/not-printed/mark-printed?so=' + not_printed['sales_order_number'].trim() + '">').append(
									$('<button type="button">').text('Mark Printed')
								)
							),
							$('<td>')
								.addClass('content')
								.addClass('content-sales-order-number')
								.addClass('overlayz-link')
								.attr('overlayz-url', BASE_URI + '/dashboard/sales-order-status/so-details')
								.attr('overlayz-data', JSON.stringify({
									'so-number': not_printed['sales_order_number'].trim()
								}))
								.text(not_printed['sales_order_number'].trim())
							,
							$('<td>').text(not_printed['territory']),
							$('<td>').text(not_printed['location']),
							$('<td>').text(not_printed['customer_code']),
							$('<td>').text(not_printed['customer_po']),
							$('<td>').text(not_printed['sales_person']),
							$('<td>').text(not_printed['who_entered']),
							$('<td>').text(not_printed['input_date']),
							$('<td>').text(not_printed['due_date'])
						).appendTo($notprinted_container);
					});
				}
			});
		};

		loadNotPrinted();
		setInterval(loadNotPrinted, one_minute);
	});
</script>

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
	<?php
	if(!in_array(trim($session->login['initials']), array('JSP', 'JDB', 'ZTB', 'CJB', 'PAR', 'PLH', 'KSV', 'tlo', 'BJW'))) {
		?>
		#notprinted-table tr th:first-child {
			display:none;
		}
		#notprinted-table tr td:first-child {
			display:none;
		}
		<?php
	}
	?>
</style>

<div class="padded">
	<fieldset>
		<legend>
			Sales Orders: Not Printed
			<span style="font-size:12px;font-style:italic;line-height:21px;vertical-align:middle;font-weight:normal;display:inline-block;padding-left:20px;" id="notprinted-datetime"></span>
		</legend>

		<table id="notprinted-table">
			<thead>
				<tr>
					<th>Mark Printed</th>
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
<?php

Template::Render('footer', 'account');
