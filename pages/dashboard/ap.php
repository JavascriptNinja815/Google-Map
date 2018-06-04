<?php

ini_set('max_execution_time', 300);

$session->ensureLogin();
$session->ensureRole('Accounting');

$args = array(
	'title' => 'AP Dashboard',
	'breadcrumbs' => array(
		'AP Dashboard' => BASE_URI . '/dashboard/ap'
	)
);

Template::Render('header', $args, 'account');

$grab_ap = $db->prepare("
	SELECT
		LTRIM(RTRIM(apmast.vendno)) AS vendno,
		LTRIM(RTRIM(apmast.invno)) AS invno,
		LTRIM(RTRIM(apmast.pterms)) AS pterms,
		apmast.ppriority,
		CONVERT(varchar(10), apmast.purdate, 120) AS purdate,
		CONVERT(varchar(10), apmast.duedate, 120) AS duedate,
		apmast.puramt,
		apmast.paidamt,
		apmast.puramt - apmast.paidamt - apmast.disamt AS balance,
		apmast.aprpay,
		apmast.paydesc,
		apmast.notes,
		apmast.udref
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.apstat != 'V'
		AND
		apmast.puramt - apmast.paidamt - apmast.disamt != 0
	ORDER BY
		apmast.duedate
", [
	PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
]);
$grab_ap->execute();

?>

<style type="text/css">
	#openap-summary th, #openap-summary td {
		line-height:16px;
		font-size:11px;
		padding:8px;
		border-top:1px solid #ddd;
		border-bottom:1px solid #ddd;
	}
	#openap-summary th {
		background-color:#ddd;
		font-weight:bold;
	}
	#ap-dashboard .add-on {
		font-size:11px;
		padding:1px 3px 0px 3px;
	}
	#ap-dashboard input[name="aprpay"] {
		width:80px;
	}
	#ap-dashboard .amount-approved {
		white-space:nowrap;
	}
	#ap-dashboard .amount-approved .fa {
		font-size:1.8em;
		height:20px;
		line-height:20px;
		padding:0 4px;
		top:3px;
		position:relative;
		top:-5px;
		vertical-align:middle;
		cursor:pointer;
	}
</style>

<div id="ap-dashboard" class="padded dashboard">
	<h3>Open AP</h3>
	<table id="openap-summary">
		<thead>
			<tr>
				<th class="right">&lt; 15 Days</th>
				<th class="right">16-30 Days</th>
				<th class="right">31-45 Days</th>
				<th class="right">46-60 Days</th>
				<th class="right">61-75 Days</th>
				<th class="right">76-90 Days</th>
				<th class="right">91-105 Days</th>
				<th class="right">106-120 Days</th>
				<th class="right">121+ Days</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="right lessthanfifteen">...</td>
				<td class="right fifteen">...</td>
				<td class="right thirty">...</td>
				<td class="right fortyfive">...</td>
				<td class="right sixty">...</td>
				<td class="right seventyfive">...</td>
				<td class="right ninety">...</td>
				<td class="right oneohfive">...</td>
				<td class="right onetwenty">...</td>
				<td class="right total">...</td>
			</tr>
		</tbody>
	</table>
	<br />

	<fieldset>
		<legend class="padded-x padded-y openap-title"><span class="openap-count"><?php print number_format($grab_ap->rowCount(), 0);?></span> Open APs ($<span class="approveltopay-total">...</span> Approved To Pay)</legend>

		<table class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky" id="openap-table">
			<thead>
				<tr>
					<th class="sortable filterable">Approve To Pay</th>
					<th class="sortable filterable">Amount Approved</th>
					<th class="sortable filterable">Vendor</th>
					<th class="sortable filterable">Invoice #</th>
					<th class="sortable filterable">Payment Terms</th>
					<th class="sortable filterable">Priority</th>
					<th class="sortable filterable">Invoice Date</th>
					<th class="sortable filterable">Age (Days)</th>
					<th class="sortable filterable">Due Date</th>
					<th class="sortable filterable right">Original Amount</th>
					<th class="sortable filterable right">Paid Amount</th>
					<th class="sortable filterable right">Balance</th>
					<th class="sortable filterable">Description</th>
					<th>Notes</th>
					<th>Reference</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_ap as $ap) {
					$now = new DateTime();
					if($ap['aprpay'] == '.0000') {
						$ap['aprpay'] = '0';
					}
					$notes_data = [
						'invno' => $ap['invno']
					];
					$reference_data = [
						'invno' => $ap['invno']
					];
					$invoice_date = new DateTime($ap['purdate']);
					$age = $now->diff($invoice_date);
					?>
					<tr class="ap" invno="<?php print htmlentities($ap['invno'], ENT_QUOTES);?>">
						<td><input type="checkbox" name="approved" value="1" <?php print !empty($ap['aprpay']) ? 'checked' : Null;?> /></td>
						<td class="amount-approved">
							<i class="fa fa-pencil fa-fw" style="display:<?php print empty($ap['aprpay']) ? 'none' : 'inline-block';?>"></i>
							<div class="input-prepend amount-approved-container" style="display:<?php print empty($ap['aprpay']) ? 'none' : 'inline-block';?>">
								<span class="add-on">$</span>
								<input name="aprpay" type="number" step="0.01" value="<?php print number_format(!empty($ap['aprpay']) ? $ap['aprpay'] : ($ap['puramt'] - $ap['paidamt']), 2, '.', '');?>" min="0.00" disabled />
							</div>
						</td>
						<td><?php print htmlentities($ap['vendno']);?></td>
						<td><?php print htmlentities($ap['invno']);?></td>
						<td><?php print htmlentities($ap['pterms']);?></td>
						<td><?php print htmlentities($ap['ppriority']);?></td>
						<td><?php print htmlentities($ap['purdate']);?></td>
						<td><?php print number_format($age->format('%a'), 0);?></td>
						<td><?php print htmlentities($ap['duedate']);?></td>
						<td class="right">$<?php print number_format($ap['puramt'], 2);?></td>
						<td class="right">$<?php print number_format($ap['paidamt'], 2);?></td>
						<td class="right">$<?php print number_format($ap['balance'], 2);?></td>
						<td><?php print htmlentities($ap['paydesc']);?></td>
						<td><i class="fa fa-file-text notes-icon overlayz-link <?php print !trim($ap['notes']) ? 'no-highlight' : Null;?>" overlayz-url="<?php print BASE_URI;?>/dashboard/ap/notes" overlayz-data="<?php print htmlentities(json_encode($notes_data), ENT_QUOTES);?>"></i></td>
						<td><i class="fa fa-file-text reference-icon overlayz-link <?php print !trim($ap['udref']) ? 'no-highlight' : Null;?>" overlayz-url="<?php print BASE_URI;?>/dashboard/ap/notes" overlayz-data="<?php print htmlentities(json_encode($reference_data), ENT_QUOTES);?>"></i></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</legend>
</div>

<script type="text/javascript">
	/**
	 * Bind to change events on "Approved To Pay" checkbox.
	 */
	$(document).off('change', '#ap-dashboard :input[name="approved"]');
	$(document).on('change', '#ap-dashboard :input[name="approved"]', function(event) {
		var $checkbox = $(this);
		var $ap = $checkbox.closest('tr');
		var $tr = $ap.closest('tr');
		var $approved_container = $ap.find('.amount-approved .amount-approved-container');
		var $amount_approved = $approved_container.find(':input[name="aprpay"]');

		if($checkbox.is(':checked')) {
			$approved_container.css('display', 'inline-block');
			$ap.find('.fa-pencil').css('display', 'inline-block');
			$ap.find('.fa-pencil').click();
		} else {
			$approved_container.slideUp('fast');
			if(!$amount_approved.is(':disabled')) {
				$ap.find('.fa-times').click().slideUp();
			} else {
				$ap.find('.fa-pencil').slideUp();
			}

			$.ajax({
				'url': BASE_URI + '/dashboard/ap/save-amount-approved',
				'data': {
					'invno': $tr.attr('invno'),
					'amount': '0'
				},
				'dataType': 'json',
				'method': 'POST',
				'success': function(response) {
					tallyApprovedToPayTotal();
				}
			});
		}
	});

	/**
	 * Bind to click events on Amount Approved Edit icon.
	 */
	$(document).off('click', '#ap-dashboard .amount-approved .fa-pencil');
	$(document).on('click', '#ap-dashboard .amount-approved .fa-pencil', function(event) {
		var $icon = $(this);
		var $container = $icon.closest('.amount-approved');
		var $amount_approved_container = $container.find('.amount-approved-container');
		var $input = $container.find(':input[name="aprpay"]');
		var $check = $('<i class="fa fa-check">').insertAfter($amount_approved_container);
		$icon.removeClass('fa-pencil').addClass('fa-times');
		$input.prop('disabled', false);
		$input.attr('initial-val', $input.val());
	});

	/**
	 * Bind to click events on Amount Approved Cancel icon.
	 */
	$(document).off('click', '#ap-dashboard .amount-approved .fa-times');
	$(document).on('click', '#ap-dashboard .amount-approved .fa-times', function(event) {
		var $icon = $(this);
		var $container = $icon.closest('.amount-approved');
		var $input = $container.find(':input[name="aprpay"]');
		var $button = $container.find('.fa-check');
		$icon.removeClass('fa-times').addClass('fa-pencil');
		$input.prop('disabled', true);
		$input.val($input.attr('initial-val'));
		$button.remove();
	});

	/**
	 * Bind to clicks on "Amount Approved" check icon.
	 */
	$(document).off('click', '#ap-dashboard .amount-approved .fa-check');
	$(document).on('click', '#ap-dashboard .amount-approved .fa-check', function(event) {
		var $icon = $(this);
		var $tr = $icon.closest('tr');
		var $cancel = $tr.find('.fa-times');
		var $input = $tr.find(':input[name="aprpay"]');

		$.ajax({
			'url': BASE_URI + '/dashboard/ap/save-amount-approved',
			'data': {
				'invno': $tr.attr('invno'),
				'amount': $input.val()
			},
			'dataType': 'json',
			'method': 'POST',
			'success': function(response) {
				tallyApprovedToPayTotal();
			}
		});
		$input.attr('initial-val', $input.val());
		$cancel.click();
	});

	/**
	 * Bind to clicks on 
	 */

	/**
	 * Populate top AP summary table.
	 */
	var loadAPSummaryXHR; // For tracking/cancelling XHR requests.
	function loadAPSummary() {
		var invoice_numbers = [];
		var $openap_count = $('.openap-title .openap-count');

		// Check if rows are hidden. If they are, then we'll populate invoice_numbers. Otherwise, we'll leave blank which assumes calculation on all.
		if($('#openap-table').find('tbody tr:not(:visible)').length) {
			// Get the invoice numbers on the visible rows
			$('#openap-table').find('tbody tr:visible').each(function() {
				invoice_numbers.push($(this).attr('invno'));
			});
			$openap_count.text(invoice_numbers.length.toLocaleString());
		} else {
			$openap_count.text($('#openap-table').find('tbody tr').length.toLocaleString());
		}
		var $table = $('#openap-summary');

		if(loadAPSummaryXHR) {
			loadAPSummaryXHR.abort();
		}
		loadAPSummaryXHR = $.ajax({
			'url': BASE_URI + '/dashboard/ap/summaries',
			'dataType': 'json',
			'method': 'POST',
			'data': {
				'invoice_numbers': invoice_numbers
			},
			'beforeSend': function() {
				$table.find('tbody td').text('...');
			},
			'success': function(response) {
				$.each(response.summaries, function(selector, value) {
					$table.find('.' + selector).text('$' + value);
				});
			}
		});
	}
	loadAPSummary(); // For initial load.
	
	// Bind to the tablesorter.endFilter event for the filtered table
	$('#openap-table').on('filterEnd',function(){
		//reload the Open AR summary data for the filtered invoices
		loadAPSummary();
	});

	function tallyApprovedToPayTotal() {
		var $approved_total = $('#ap-dashboard .approveltopay-total');
		var total = 0.00;
		$.each($('#ap-dashboard tr.ap .amount-approved .amount-approved-container:visible'), function(offset, approved_container) {
			var $input = $(approved_container).find(':input[name="aprpay"]');
			var approved_amount = parseFloat($input.val());
			total += approved_amount;
		});
		$approved_total.text(total.toLocaleString(undefined, {'maximumFractionDigits': 2}));
	}
	tallyApprovedToPayTotal(); // For initial load.
</script>

<?php

Template::Render('footer', 'account');
