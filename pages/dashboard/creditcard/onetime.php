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
	#cc-charge-container .inline-wrapper {
		display:inline-block;
		vertical-align:top;
		margin-right:24px;
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
	<h2>One-Time Payment</h2>

	<?php
	if(!ISLIVE) {
		?>
		<div class="notification-container notification-error stay">
			<div class="notification "><b>WARNING</b>: This is the <b>TEST</b> site and any credit card transactions ran will not be posted to an actual account.</div>
		</div>
		<?php
	}
	?>

	<form method="post" action="<?php print BASE_URI;?>/dashboard/creditcard/transaction/perform" id="cc-onetime-form" class="padded form-horizontal">
		<div class="transaction-wrapper">

			<div class="inline-wrapper">
				<fieldset>
					<legend>Company Info</legend>

					<div class="control-group">
						<label class="control-label">Customer Type</label>
						<div class="controls">
							<label><input type="radio" name="existing-customer" value="1" required /> Existing Customer</label>
							<label><input type="radio" name="existing-customer" value="0" required /> New Customer</label>
						</div>
					</div>

					<div class="buyer-existingcustomer hidden">
						<div class="control-group">
							<label class="control-label">Client Code</label>
							<div class="controls">
								<input type="text" name="custno" placeholder="Required" />
							</div>
						</div>
					</div>

					<div class="buyer-newcustomer hidden">
						<div class="control-group">
							<label class="control-label">Company</label>
							<div class="controls">
								<input type="text" name="buyer[company]" placeholder="Required" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label">Buyer Name</label>
							<div class="controls">
								<input type="text" name="buyer[name]" placeholder="Required" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label">Buyer Phone</label>
							<div class="controls">
								<input type="text" name="buyer[phone]" placeholder="Required" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label">Buyer E-Mail</label>
							<div class="controls">
								<input type="email" name="buyer[email]" />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label">Shipping Address</label>
							<div class="controls">
								<input type="text" name="buyer[street]" placeholder="Address Line 1" />
								<br />
								<input type="text" name="buyer[street2]" placeholder="Address Line 2" />
								<br />
								<input type="text" name="buyer[city]" placeholder="City" class="span1" />,
								<input type="text" name="buyer[state]" placeholder="State" maxlength="2" class="span1" />
								<input type="text" name="buyer[zip]" placeholder="Zip" class="span1" />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label">A/P Name</label>
							<div class="controls">
								<input type="text" name="ap[name]" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label">A/P Phone</label>
							<div class="controls">
								<input type="text" name="ap[phone]" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label">A/P E-Mail</label>
							<div class="controls">
								<input type="email" name="ap[email]" />
							</div>
						</div>
					</div>
				</fieldset>

			</div>

			<div class="inline-wrapper">
				<div class="hidden buyer-taxexempt">
					<fieldset>
						<legend>Sales Tax</legend>
						<div class="control-group">
							<label class="control-label">Tax Exempt?</label>
							<div class="controls">
								<label><input type="radio" name="tax_exempt" value="No" checked /> No</label>
								<label><input type="radio" name="tax_exempt" value="Out Of State" /> Yes - Out Of State</label>
								<label><input type="radio" name="tax_exempt" value="Tax Exempt Organization" /> Yes - Tax Exempt Organization</label>
								<label><input type="radio" name="tax_exempt" value="Sales Tax Exemption Form" /> Yes - Sales Tax Exemption Form</label>
							</div>
						</div>
					</fieldset>
				</div>

				<div class="hidden buyer-payment">
					<fieldset>
						<legend>Payment</legend>
						<div class="control-group" class="hidden">
							<label class="control-label" for="cc-amount">Amount</label>
							<div class="controls">
								<div class="input-prepend">
									<span class="add-on">$</span>
									<input type="number" step="0.01" min="0.00" name="amount" class="span2" id="cc-amount" required placeholder="Required" />
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
								<select name="payment_profile_id" id="cc-profileid" placeholder="Required">
									<option value=""></option>
									<option value="different">Enter a new/different card</option>
								</select>
							</div>
						</div>
						<div class="buyer-payment-newcc">
							<div class="control-group">
								<label class="control-label">Name On Card</label>
								<div class="controls">
									<input type="text" name="nameoncard" placeholder="Required" />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label">Card Number</label>
								<div class="controls">
									<input type="text" name="card_number" placeholder="Required" />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label">Expiration</label>
								<div class="controls">
									<select name="expiration_month" class="span2">
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
										while($year < $start_year + 20) {
											?><option value="<?php print htmlentities($year, ENT_QUOTES);?>"><?php print htmlentities($year);?></option><?php
											$year++;
										}
										?>
									</select>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label">Security Code</label>
								<div class="controls">
									<input type="text" name="code" class="span2" placeholder="Required" />
								</div>
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
			</div>
		</div>

		<button type="submit" class="btn btn-primary">Process Payment</button>
	</form>
</div>

<script type="text/javascript">
	var $onetime_submit_button = $('#cc-charge-container button[type="submit"]');

	function renderCreditCards(creditcards) {
		var $paymentinfo_container = $('#cc-charge-container .buyer-payment');
		var $cc_select = $paymentinfo_container.find(':input[name="payment_profile_id"]');
		$cc_select.find('option').not('[value=""], [value="different"]').remove();
		if(creditcards.length) {
			$cc_select.val('').trigger('change');
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
		var $amount = $paymentinfo_container.find('[name="amount"]');
		$amount.focus();
	}

	// Populate list of clients for later reference.
	var clients = <?php print json_encode($clients);?>;

	/**
	 * Bind to form submissions.
	 */
	$(document).off('submit', '#cc-onetime-form');
	$(document).on('submit', '#cc-onetime-form', function(event) {
		var form = this;
		var $form = $(this);

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

		// Hide the payment form info.
		var $transaction_wrapper = $form.find('.transaction-wrapper');
		//$onetime_submit_button.slideUp('fast');
		$transaction_wrapper.slideUp('fast');
		var $ajax_loader = $ajax_loading_prototype.clone().hide();
		$ajax_loader.appendTo($form).slideDown('fast');

		var data = new FormData(form);
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

				var $custno = $form.find(':input[name="custno"]');
				var custno = $custno.val();

				$form.find(':input').not('[type="hidden"], [type="checkbox"], [type="radio"]').val('').trigger('change');

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
	 * Bind to changes on "Customer Type" radio.
	 */
	$(document).off('change', '#cc-onetime-form :input[name="existing-customer"]');
	$(document).on('change', '#cc-onetime-form :input[name="existing-customer"]', function(event) {
		var $radio = $(this);
		var $form = $radio.closest('form');
		var existing_customer = $radio.val();
		var $amount = $form.find('[name="amount"]');
		//$amount.val('');
		var $payment_profile_id = $form.find(':input[name="payment_profile_id"]');

		// Delete credit cards previously specified.
		$payment_profile_id.find('option').not('[value=""], [value="different"]').remove();

		//$onetime_submit_button.prop('disabled', true);

		var $existing_customer_buyerinfo = $form.find('.buyer-existingcustomer');
		var $new_customer_buyerinfo = $form.find('.buyer-newcustomer');

		var $required_existingcustomer_fields = $existing_customer_buyerinfo.find(':input[name="custno"]');
		var $required_newcustomer_fields = $new_customer_buyerinfo.find(':input[name="buyer[name]"], :input[name="buyer[phone]"], :input[name="tax_exempt"], :input[name="buyer[company]"]');

		var $buyer_payment = $form.find('.buyer-payment');
		var $buyer_taxexempt = $form.find('.buyer-taxexempt');

		var $cc_on_file_container = $form.find('[name="payment_profile_id"]').closest('.control-group');
		var $cc_info_container = $form.find('.buyer-payment-newcc');

		if(existing_customer === '1') {
			$cc_on_file_container.show();

			// Existing customer.
			$existing_customer_buyerinfo.slideDown('fast');
			$new_customer_buyerinfo.slideUp('fast');

			$required_existingcustomer_fields.prop('required', true);
			$required_newcustomer_fields.prop('required', false);

			$buyer_payment.slideUp('fast');
			$buyer_taxexempt.slideUp('fast');
			$buyer_taxexempt.find(':input').prop('required', false);

			$payment_profile_id.prop('required', true);

			$cc_info_container.find(':input[name="nameoncard"]').prop('required', false);
			$cc_info_container.find(':input[name="card_number"]').prop('required', false);
			$cc_info_container.find(':input[name="expiration_month"]').prop('required', false);
			$cc_info_container.find(':input[name="expiration_year"]').prop('required', false);
			$cc_info_container.find(':input[name="code"]').prop('required', false);

			// Forces payment inputs to be required.
			$cc_on_file_container.val('').trigger('change');

			$existing_customer_buyerinfo.find(':input[name="custno"]').focus();

		} else if(existing_customer === '0') {
			// New customer.
			$new_customer_buyerinfo.slideDown('fast');
			$existing_customer_buyerinfo.slideUp('fast');

			$required_newcustomer_fields.prop('required', true);
			$required_existingcustomer_fields.prop('required', false);

			$buyer_payment.slideDown('fast');
			$buyer_taxexempt.slideDown('fast');
			$buyer_taxexempt.find(':input').prop('required', true);

			$cc_on_file_container.hide();
			$cc_on_file_container.val('different').trigger('change');

			$cc_info_container.find(':input[name="nameoncard"]').prop('required', true);
			$cc_info_container.find(':input[name="card_number"]').prop('required', true);
			$cc_info_container.find(':input[name="expiration_month"]').prop('required', true);
			$cc_info_container.find(':input[name="expiration_year"]').prop('required', true);
			$cc_info_container.find(':input[name="code"]').prop('required', true);

			$form.find('.buyer-payment-newcc').show();

			$payment_profile_id.prop('required', false);

			$existing_customer_buyerinfo.find(':input[name="custno"]').val('');
		}
	});

	// Implement autocomplete
	var $sono_autocomplete = $('#cc-onetime-form :input[name="custno"]').autoComplete({
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
			$('#cc-onetime-form :input[name="custno"]').blur().trigger('change');
		}
	});

	/**
	 * Bind to changes on Client Code input.
	 */
	$(document).off('change', '#cc-onetime-form :input[name="custno"]');
	$(document).on('change', '#cc-onetime-form :input[name="custno"]', function(event) {
		var $form = $(this).closest('form');

		// Remove existing notifications.
		$('#cc-charge-container').find('.notification-container').not('.stay').remove();

		var $custno = $('#cc-onetime-form :input[name="custno"]');
		var custno = $custno.val().split(' - ')[0];
		var $custno_parent = $custno.parent();
		$custno_parent.find('.error').remove();

		var $buyer_payment = $form.find('.buyer-payment');
		var $buyer_taxexempt = $form.find('.buyer-taxexempt');
		var $new_customer_buyerinfo = $form.find('.buyer-newcustomer');

		$buyer_payment.slideUp('fast');
		$buyer_taxexempt.slideUp('fast');
		$new_customer_buyerinfo.slideUp('fast');

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
				/**
				 * Handle potential errors.
				 */
				if(!response.success) {
					var $notification = $('<div class="notification-container notification-error">').append(
						$('<div class="notification">').text('ERROR: ' + response.message)
					).hide();
					$('#cc-charge-container h2').after($notification);
					$notification.slideDown('fast');

					// TODO: Hide some things.
					$form.find('.buyer-payment').slideUp('fast');
					return;
				}

				/**
				 * CUSTOMER CODE IS VALID, PROCEED.
				 */

				$custno.val(response.custno); // Sets exact value from DB. Eg: uppercase.
				$custno.blur();
				custno = response.custno;

				var data = {
					'custno': custno
				};

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
						renderCreditCards(response.creditcards);
					}
				});
			}
		});
	});

	/**
	 * Bind to changes on Credit Card Select
	 */
	$(document).off('change', '#cc-onetime-form :input[name="payment_profile_id"]');
	$(document).on('change', '#cc-onetime-form :input[name="payment_profile_id"]', function(event) {
		var $select = $(this);
		var $form = $select.closest('form');
		var value = $select.val();
		var $option = $select.find('option[value="' + value + '"]');
		var address = $option.attr('address');
		var zip = $option.attr('zip');
		var $cc_info_container = $form.find('.buyer-payment-newcc');
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
	 * Bind to changes on Amount input.
	 */
	/*$(document).off('change', '#cc-onetime-form :input[name="amount"]');
	$(document).on('change', '#cc-onetime-form :input[name="amount"]', function(event) {
		var $amount = $(this);
		var amount = $amount.val();
		if(Number(amount) > Number('0.00')) {
			$onetime_submit_button.prop('disabled', false);
		} else {
			$onetime_submit_button.prop('disabled', true);
		}
	});*/
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
