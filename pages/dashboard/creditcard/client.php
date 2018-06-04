<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

if($session->hasRole('Administration')) {
	$permission_constraint = "1 = 1"; // Show all.
} else if($session->hasRole('Sales')) {
	$permissions = $session->getPermissions('Sales', 'view-orders');
	if(!empty($permissions)) {
		// Sanitize values for DB querying.
		$permissions = array_map(function($value) {
			$db = \PM\DB\SQL::connection();
			return $db->quote($value);
		}, $permissions);
		$permission_constraint = "arcust.salesmn IN (" . implode(',', $permissions) . ")";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$permission_constraint = "1 != 1";
	}
} else {
	$permission_constraint = "1 != 1"; // Don't show any.
}
$grab_clients = $db->query("
	SELECT
		arcust.custno,
		arcust.company,
		arcust.salesmn
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		" . $permission_constraint . "
	ORDER BY
		arcust.custno
");
$clients = [];
foreach($grab_clients as $client) {
	$clients[trim($client['custno'])] = strtoupper(
		trim($client['custno']) . ' - ' . trim($client['company'])
	);
}

?>

<style type="text/css">
	#cc-charge-container label.savecard {
		padding-top:8px;
		font-weight:bold;
		display:inline-block;
	}
	#cc-charge-container label.savecard input[type="checkbox"] {
		padding-top:8px;
		zoom:1.4;
	}

	#cc-salesorders-wrapper,
	#cc-payment-wrapper,
	#cc-creditcard-wrapper {
		display:inline-block;
		vertical-align:top;
		margin-right:24px;
	}

	#cc-salesorders {
		max-height:300px;
		overflow:auto;
	}
	#cc-salesorders td {
		vertical-align:top;
	}
	#cc-salesorders .salesorder, #cc-salesorders .invoice {
		min-width:160px
	}
	#cc-salesorders .salesorders input.sono,
	#cc-salesorders .salesorders input.invno {
		margin-left:4px;
		float:left;
		zoom:2.0;
	}
	#cc-salesorders .salesorders .details {
		padding-left:40px;
	}
	#cc-salesorders .salesorders .date,
	#cc-salesorders .salesorders .age {
		display:inline-block;
	}
	#cc-salesorders .salesorders .amount {
		padding-top:4px;
		font-size:1.2em;
	}
	#cc-salesorders .salesorders .amount-text {
		padding:2px;
		font-weight:bold;
	}
	#cc-salesorders .salesorders .edit-amount {
		padding:8px;
		color:#00f;
		cursor:pointer;
	}
	#cc-salesorders .salesorders .selected {
		background-color:#ff9;
	}

	#cc-submit-wrapper {
		padding-top:16px;
		clear:both;
	}
	#cc-charge-container input[required],
	#cc-charge-container select[required],
	#cc-charge-container textarea[required] {
		border-color:#000;
	}
	#cc-charge-container input[name="billing_address"],
	#cc-charge-container input[name="billing_zip"] {
		border-color:#f99;
	}
</style>

<div id="cc-charge-container">
	<a name="PAYMENTNOTIFICATION"></a>
	<h2>Existing Client</h2>

	<?php
	if(!ISLIVE) {
		?>
		<div class="notification-container notification-error stay">
			<div class="notification "><b>WARNING</b>: This is the <b>TEST</b> site and any credit card transactions ran will not be posted to an actual account.</div>
		</div>
		<?php
	}
	?>

	<form method="post" action="<?php print BASE_URI;?>/dashboard/creditcard/transaction/perform" id="cc-client-form" class="padded form-horizontal">
		<div class="control-group">
			<label class="control-label" for="cc-custno">Client Code</label>
			<div class="controls">
				<input type="text" name="custno" id="cc-custno" required />
			</div>
		</div>

		<div id="cc-transaction-wrapper">
			<div id="cc-salesorders-wrapper">
				<fieldset id="cc-salesorders" class="hidden">
					<legend>Sales Orders & Invoices</legend>
					<!input type="text" id="cc-salesorder-filter" placeholder="Filter Sales Orders..." />
					<table>
						<thead>
							<tr>
								<th>Sales Orders</th>
								<th>Invoices</th>
							</tr>
						</thead>
						<tbody class="salesorders"></tbody>
					</table>
				</fieldset>
			</div>

			<div id="cc-payment-wrapper">
				<fieldset id="cc-payment-info" class="hidden">
					<legend>Payment</legend>
					<input type="hidden" name="payment_action" value="charge" />
					<div class="control-group" class="hidden">
						<label class="control-label" for="cc-amount">Amount</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">$</span>
								<input type="number" step="0.01" min="0.00" name="amount" class="span2" required id="cc-amount" placeholder="Required" />
							</div>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Memo</label>
						<div class="controls">
							<input type="text" name="memo" />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="cc-profileid">Card On File</label>
						<div class="controls">
							<select name="payment_profile_id" required id="cc-profileid" placeholder="Required">
								<option value=""></option>
								<option value="different">Enter a new/different card</option>
							</select>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Billing Address</label>
						<div class="controls">
							<input type="text" name="billing_address" placeholder="Address and/or Zip Required" />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Billing Zip</label>
						<div class="controls">
							<input type="text" name="billing_zip" class="span3" placeholder="Address and/or Zip Required" />
						</div>
					</div>
				</fieldset>
			</div>

			<div id="cc-creditcard-wrapper">
				<fieldset id="cc-creditcard-info" class="hidden">
					<legend>Credit Card Info</legend>
					<div class="control-group">
						<label class="control-label" for="cc-nameoncard">Name On Card</label>
						<div class="controls">
							<input type="text" name="nameoncard" id="cc-nameoncard" placeholder="Required" /><small></small>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="cc-cardnumber">Card Number</label>
						<div class="controls">
							<input type="text" name="card_number" id="cc-cardnumber" placeholder="Required" /><small></small>
							<br />
							<label class="savecard"><input type="checkbox" name="save-card" value="1" /> <span>Save card for later use</span></label>
							<div class="hidden cc-creditcard-nickname-container">
								<input type="text" name="nickname" placeholder="Saved Card's Nickname" /> (required)
							</div>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="cc-expiration">Expiration</label>
						<div class="controls">
							<select name="expiration_month" class="span2" id="cc-expiration">
								<option value="" disabled selected>Month</option>
								<option value="01">01 - Jan</option>
								<option value="02">02 - Feb</option>
								<option value="03">03 - Mar</option>
								<option value="04">04 - Apr</option>
								<option value="05">05 - May</option>
								<option value="06">06 - Jun</option>
								<option value="07">07 - Jul</option>
								<option value="08">08 - Aug</option>
								<option value="09">09 - Sep</option>
								<option value="10">10 - Oct</option>
								<option value="11">11 - Nov</option>
								<option value="12">12 - Dec</option>
							</select>
							/
							<select name="expiration_year" class="span2">
								<option value="" disabled selected>Year</option>
								<?php
								$start_year = date('Y', time());
								$year = $start_year;
								while($year < $start_year + 12) {
									?><option value="<?php print htmlentities($year, ENT_QUOTES);?>"><?php print htmlentities($year);?></option><?php
									$year++;
								}
								?>
							</select>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="cc-securitycode">Security Code</label>
						<div class="controls">
							<input type="text" name="code" class="span2" id="cc-securitycode" placeholder="Required" />
						</div>
					</div>
					<!--div class="control-group">
						<label class="control-label" for="cc-streetaddress">Street Address</label>
						<div class="controls">
							<input type="text" name="street_address" class="span1" id="cc-streetaddress" />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="cc-zipcode">Zip Code</label>
						<div class="controls">
							<input type="text" name="zip_code" class="span1" id="cc-zipcode" />
						</div>
					</div-->
				</fieldset>
			</div>

			<div id="cc-submit-wrapper">
				<button type="submit" class="btn btn-primary">Process Payment</button>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	/**
	 * Auto-focus "Client Code" input.
	 */
	$(function() {
		$('#cc-client-form :input[name="custno"]').focus();
	});

	function renderPendingPayments(salesorders) {
		var $salesorders = $('#cc-salesorders .salesorders');

		$.each(salesorders, function(offset, salesorder) {
			var so_amount = Number(salesorder.amount);
			var $salesorder = $('<div class="salesorder">').append(
				(so_amount > Number('0') ?
					$('<input type="checkbox" class="sono" />').attr('name', 'sonos[' + salesorder.sono + ']').attr('value', salesorder.amount)
				: ''),
				$('<div class="details">').append(
					$('<div class="name overlayz-link">').text(salesorder.sono).attr('overlayz-url', BASE_URI + '/dashboard/sales-order-status/so-details').attr('overlayz-data', JSON.stringify({'so-number': salesorder.sono})),
					$('<div class="date">').text(salesorder.date),
					' ',
					$('<div class="age">').text('(' + salesorder.age + ')'),
					(so_amount > Number('0') ?
						$('<div class="amount">').append(
							$('<i class="fa fa-pencil edit-amount">'),
							$('<span class="amount-text">').text('$' + Intl.NumberFormat().format(so_amount.toFixed(2)))
						)
					: '')
				)
			).attr('amount', so_amount);

			var $invoices = $('<div class="invoices">');
			$.each(salesorder.invoices, function(offset, invoice) {
				var inv_amount = Number(invoice.amount);
				$invoices.append('<div class="invoice').append(
					$('<div class="invoice">').append(
						$('<input type="checkbox" class="invno" />').attr('name', 'invnos[' + invoice.invno + ']').attr('value', invoice.amount),
						$('<div class="details">').append(
							$('<div class="name">').text(invoice.invno),
							$('<div class="date">').text(invoice.date),
							' ',
							$('<div class="age">').text('(' + invoice.age + ')'),
							$('<div class="amount">').append(
								$('<i class="fa fa-pencil edit-amount">'),
								$('<span class="amount-text">').text('$' + Intl.NumberFormat().format(inv_amount.toFixed(2)))
							)
						)
					).attr('amount', inv_amount)
				);
			});

			var $salesorder_row = $('<tr class="so-row">').append(
				$('<td>').append($salesorder),
				$('<td>').append($invoices)
			);
			$salesorders.append($salesorder_row);
		});
		$salesorders.show();
	}

	function renderCreditCards(creditcards) {
		var $paymentinfo_container = $('#cc-payment-info');
		var $cc_select = $paymentinfo_container.find(':input[name="payment_profile_id"]');
		$cc_select.find('option').not('[value=""], [value="different"]').remove();
		if(creditcards.length) {
			$cc_select.val('').trigger('change'); // Set value to un-selected.
			$.each(creditcards, function(offset, creditcard) {
				$cc_select.append(
					$('<option>')
						.attr('value', creditcard.payment_profile_id)
						.text(creditcard.name + ' - ' + creditcard.last4 + ' (exp ' + creditcard.expiration + ')')
						.attr('address', creditcard.address)
						.attr('zip', creditcard.zip)
				);
			});
		} else {
			// No credit cards on file.
			$cc_select.val('different').trigger('change');
		}
		$paymentinfo_container.show();
	}

	// Populate list of clients for later reference.
	var clients = <?php print json_encode($clients);?>;

	// Implement autocomplete
	var $sono_autocomplete = $('#cc-client-form :input[name="custno"]').autoComplete({
		'minChars': 1,
		'source': function(term, response) {
			term = term.toUpperCase();
			var matches = [];
			$.each(clients, function(i, client) {
				if(client.toUpperCase().indexOf(term) > -1) {
					matches.push(client);
				}
			});
			response(matches);
		},
		'onSelect': function(event, term, item) {
			$('#cc-client-form :input[name="custno"]').blur().trigger('change');
		}
	});

	/**
	 * Bind to changes on Client Code input.
	 */
	$(document).off('change', '#cc-client-form :input[name="custno"]');
	$(document).on('change', '#cc-client-form :input[name="custno"]', function(event) {
		// Remove existing notifications.
		$('#cc-charge-container').find('.notification-container').not('.stay').remove();

		var $custno = $('#cc-client-form :input[name="custno"]');
		var custno = $custno.val().split(' - ')[0];
		var $custno_parent = $custno.parent();
		$custno_parent.find('.error').remove();

		if(!custno) { // Don't verify if blank custno.
			return;
		}

		// Ensure we're working with a valid customer code.
		$.ajax({
			'url': BASE_URI + '/dashboard/creditcard/client/verify-custno',
			'method': 'POST',
			'data': {
				'custno': custno
			},
			'dataType': 'json',
			'success': function(response) {
				if(!response.success) {
					var $notification = $('<div class="notification-container notification-error">').append(
						$('<div class="notification">').text('ERROR: ' + response.message)
					).hide();
					$('#cc-charge-container h2').after($notification);
					$notification.slideDown('fast');

					$('#cc-salesorders').hide();
					$('#cc-payment-info').hide();
					$('#cc-creditcard').hide();
					$('#cc-submit').hide();
					return;
				}
				$custno.val(response.custno); // Sets exact value from DB. Eg: uppercase.
				custno = response.custno;

				/**
				 * VALID CUSTOMER CODE, PROCEED.
				 */

				// Grab containers for ajax content.
				var $salesorders_container = $('#cc-salesorders');
				var $payment_container = $('#cc-payment-info');

				// Remove and hide anything necessary to cleanup the containers.
				$payment_container.find('.control-group').hide();

				// Append a loading animation to each of the containers and slide down into view.
				$salesorders_container.find('.salesorders').empty().append($ajax_loading_prototype.clone());
				$salesorders_container.slideDown('fast');
				$payment_container.append($ajax_loading_prototype.clone()).slideDown('fast');
				$payment_container.find(':input[name="amount"]').val('0.00').prop('readonly', false);

				var data = {
					'custno': custno
				};

				/**
				 * AJAX request for Sales Orders
				 */
				$.ajax({
					'url': BASE_URI + '/dashboard/creditcard/client/get/pending-payment',
					'data': data,
					'method': 'POST',
					'dataType': 'json',
					'success': function(response) {
						if(!response.success) {
							if(response.message) {
								alert(response.message);
							} else {
								alert('Something didn\'t go right');
							}
							return;
						}
						$salesorders_container.find('.ajax-loading-container').remove();
						
						renderPendingPayments(response.salesorders);
					}
				});

				/**
				 * AJAX request for Credit Cards
				 */
				$.ajax({
					'url': BASE_URI + '/dashboard/creditcard/client/get/creditcards',
					'data': data,
					'method': 'POST',
					'dataType': 'json',
					'success': function(response) {
						if(!response.success) {
							if(response.message) {
								alert(response.message);
							} else {
								alert('Something didn\'t go right');
							}
							return;
						}
						$payment_container.find('.ajax-loading-container').remove();
						$payment_container.find('.control-group').show();
						renderCreditCards(response.creditcards);
					}
				});
			}
		});

	});

	/**
	 * Bind to form submissions.
	 */
	$(document).off('submit', '#cc-client-form');
	$(document).on('submit', '#cc-client-form', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		var $transaction_wrapper = $form.find('#cc-transaction-wrapper');

		// Remove existing notifications.
		$('#cc-charge-container').find('.notification-container').not('.stay').remove();

		// Ensure Billing Address and/or Billing Zip are specified.
		var $billing_address = $form.find(':input[name="billing_address"]');
		var $billing_zip = $form.find(':input[name="billing_zip"]');
		var $payment_profile_id = $form.find(':input[name="payment_profile_id"]');
		if($payment_profile_id.val() === 'different' && !$billing_address.val() && !$billing_zip.val()) {
			var $notification = $('<div class="notification-container notification-error">').append(
				$('<div class="notification">').text('ERROR: Billing Address and/or Billing Zip must be specified')
			).hide();
			$('#cc-charge-container h2').after($notification);
			$notification.slideDown('fast');
			window.location = '#PAYMENTNOTIFICATION';
			return false;
		}

		$transaction_wrapper.slideUp('fast');
		var $ajax_loader = $ajax_loading_prototype.clone().hide();
		$ajax_loader.appendTo($form).slideDown('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/creditcard/transaction/perform',
			'method': 'POST',
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'error': function() {
				// Hide the ajax loader and remove it.
				$ajax_loader.slideUp('fast', function() {
					$ajax_loader.remove();
				});

				var $notification = $('<div class="notification-container notification-error">').append(
					$('<div class="notification">').text('ERROR: The request was interrupted (timeout, disconnected) and it appears the transaction as failed')
				).hide();
				$('#cc-charge-container h2').after($notification);
				$notification.slideDown('fast');
				window.location = '#PAYMENTNOTIFICATION';

				// Show the form again.
				$transaction_wrapper.slideDown('fast');
			},
			'success': function(response) {
				// Show the form again.
				$transaction_wrapper.slideDown('fast');

				// Hide the ajax loader and remove it.
				$ajax_loader.slideUp('fast', function() {
					$ajax_loader.remove();
				});

				// Determine if we have errors.
				if(!response.success) {
					if('message' in response) {
						var $notification = $('<div class="notification-container notification-error">').append(
							$('<div class="notification">').text('ERROR: ' + response.message)
						).hide();
						$('#cc-charge-container h2').after($notification);
						$notification.slideDown('fast');
					}
					if('errors' in response) {
						$.each(response.errors, function(offset, error) {
							var $notification = $('<div class="notification-container notification-error">').append(
								$('<div class="notification">').text('ERROR: (Error Code ' + error.code + ') ' + error.message)
							).hide();
							$('#cc-charge-container h2').after($notification);
							$notification.slideDown('fast');
						});
					}
					window.location = '#PAYMENTNOTIFICATION';
					return;
				}

				// Remove existing notifications.
				$('#cc-charge-container').find('.notification-container').not('.stay').remove();

				var $custno = $('#cc-custno');
				var custno = $custno.val();

				$form.find(':input').not('[type="hidden"], [type="checkbox"], [type="radio"]').val('').trigger('change');

				$('#cc-salesorders').hide();
				$('#cc-payment-info').hide();
				$('#cc-creditcard').hide();
				$('#cc-submit').hide();

				var $notification = $('<div class="notification-container notification-success">').append(
					$('<div class="notification">').append(
						(custno ?
							'Transaction for "' + custno + '" successful (Auth Code ' + response.auth_code + ')'
						:
							'Transaction successful (Auth Code ' + response.auth_code + ')'
						),
						$('<br />'),
						$('<br />'),
						$('<a>').attr('href', BASE_URI + '/dashboard/creditcard/receipt?transaction_id=' + response.transaction_id).attr('target', '_receipt_' + response.transaction_id).append(
							$('<button type="button" class="btn btn-success">').text('Print Receipt')
						),
						$('<br />')
					)
				).hide();
				$('#cc-charge-container h2').after($notification);
				$notification.slideDown('fast');
				window.location = '#PAYMENTNOTIFICATION';

				$custno.focus();
				//$submit_button.prop('disabled', true);
			}
		});
		return false;
	});

	/**
	 * Bind to clicks on an Autocomplete Suggestion
	 */
	$(document).off('click', '#cc-client-form .autocomplete-suggestions .autocomplete-suggestion');
	$(document).on('click', '#cc-client-form .autocomplete-suggestions .autocomplete-suggestion', function(event) {
		alert('woot?');
	});

	/**
	 * Bind to changes on Credit Card Select
	 */
	$(document).off('change', '#cc-client-form :input[name="payment_profile_id"]');
	$(document).on('change', '#cc-client-form :input[name="payment_profile_id"]', function(event) {
		var $select = $(this);
		var $form = $select.closest('form');
		var value = $select.val();
		var $option = $select.find('option[value="' + value + '"]');
		var address = $option.attr('address');
		var zip = $option.attr('zip');
		var $cc_info_container = $('#cc-creditcard-info');
		if(value === 'different') {
			$cc_info_container.slideDown('fast');
			$cc_info_container.find(':input[name="nameoncard"]').prop('required', true);
			$cc_info_container.find(':input[name="card_number"]').prop('required', true);
			$cc_info_container.find(':input[name="expiration_month"]').prop('required', true);
			$cc_info_container.find(':input[name="expiration_year"]').prop('required', true);
			$cc_info_container.find(':input[name="code"]').prop('required', true);
			$form.find(':input[name="billing_address"]').closest('.control-group').slideDown('fast');
			$form.find(':input[name="billing_zip"]').closest('.control-group').slideDown('fast');
		} else {
			$cc_info_container.slideUp('fast');
			$cc_info_container.find(':input[name="nameoncard"]').prop('required', false);
			$cc_info_container.find(':input[name="card_number"]').prop('required', false);
			$cc_info_container.find(':input[name="expiration_month"]').prop('required', false);
			$cc_info_container.find(':input[name="expiration_year"]').prop('required', false);
			$cc_info_container.find(':input[name="code"]').prop('required', false);
			$form.find(':input[name="billing_address"]').closest('.control-group').slideUp('fast');
			$form.find(':input[name="billing_zip"]').closest('.control-group').slideUp('fast');
		}
	});

	/**
	 * Bind to click of "Edit" icon within Sales Orders.
	 */
	$(document).off('click', '#cc-salesorders .salesorders .edit-amount');
	$(document).on('click', '#cc-salesorders .salesorders .edit-amount', function(event) {
		var $icon = $(this);
		var $entry = $icon.closest('.salesorder, .invoice');
		var $checkbox = $entry.find('input.invno, input.sono');
		var amount = $entry.attr('amount');
		var new_amount = prompt('Please enter a different amount:', amount);
		new_amount = Number(new_amount);
		if(new_amount) {
			if($checkbox.is(':checked')) {
				var difference = new_amount - Number(amount);
				var $amount = $('#cc-payment-info #cc-amount');
				var amount_value = Number($amount.val());
				amount_value += difference;
				$amount.val(amount_value.toFixed(2));
			}
			$entry.find('.amount-text').text('$' + Intl.NumberFormat().format(new_amount.toFixed(2)));
			$entry.attr('amount', new_amount.toFixed(2));
			$checkbox.val(new_amount);
		}
		return false;
	});

	/**
	 * Bind to check/uncheck of Sales Orders
	 */
	$(document).off('change', '#cc-salesorders input.invno, #cc-salesorders input.sono');
	$(document).on('change', '#cc-salesorders input.invno, #cc-salesorders input.sono', function(event) {
		var $checkbox = $(this);
		var $entry =  $checkbox.closest('.salesorder, .invoice');
		var checked = $checkbox.is(':checked');
		var $amount = $('#cc-payment-info :input[name="amount"]');
		var entry_amount = Number($entry.attr('amount'));
		var total_amount = $amount.val();
		if(total_amount && $amount.is('[readonly]')) {
			total_amount = Number(total_amount);
		} else {
			total_amount = Number('0.00');
		}
		if(checked) {
			$entry.addClass('selected');
			total_amount += entry_amount;
		} else {
			$entry.removeClass('selected');
			total_amount -= entry_amount;
		}
		$amount.val(total_amount.toFixed(2)).trigger('change');
		if(total_amount) {
			$amount.prop('readonly', true);
		} else {
			$amount.prop('readonly', false);
		}
	});

	/**
	 * Bind to changes of "Save Card" checkbox.
	 */
	$(document).off('change', '#cc-charge-container .savecard :input[name="save-card"]');
	$(document).on('change', '#cc-charge-container .savecard :input[name="save-card"]', function(event) {
		var $checkbox = $(this);
		var checked = $checkbox.is(':checked');
		var $nickname_container = $('#cc-charge-container .cc-creditcard-nickname-container');
		if(checked) {
			$nickname_container.show();
			$nickname_container.find(':input[name="nickname"]').prop('required', true).focus();
		} else {
			$nickname_container.hide();
			$nickname_container.find(':input[name="nickname"]').prop('required', false).focus();
		}
	});

	/**
	 * Bind to changes on Amount input.
	 */
	/*$(document).off('change', '#cc-payment-info :input[name="amount"]');
	$(document).on('change', '#cc-payment-info :input[name="amount"]', function(event) {
		var $amount = $(this);
		var amount = $amount.val();
		var $submit_button = $('#cc-submit-wrapper button[type="submit"]');
		if(Number(amount) > Number('0.00')) {
			$submit_button.prop('disabled', false);
		} else {
			$submit_button.prop('disabled', true);
		}
	});*/

	/**
	 * Bind to key pressed on Client Code input, prevent Enter from being pressed.
	 */
	$(document).off('keydown', '#cc-charge-container #cc-custno');
	$(document).on('keydown', '#cc-charge-container #cc-custno', function(event) {
		var $input = $(this);
		if(event.keyCode == 13) {
			event.preventDefault();
			$input.blur();
			return false;
		}
	});
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
