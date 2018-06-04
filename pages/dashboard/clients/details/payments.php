<?php

$grab_payment_profiles = $db->query("
	SELECT
		authorize_payment_profiles.payment_profile_id,
		authorize_customer_profiles.authorize_custprofile_id,
		authorize_payment_profiles.authorize_payprofile_id,
		authorize_payment_profiles.profile_name,
		authorize_payment_profiles.nameoncard,
		authorize_payment_profiles.last4,
		authorize_payment_profiles.code,
		authorize_payment_profiles.expiration,
		authorize_payment_profiles.salesmn,
		authorize_payment_profiles.address,
		authorize_payment_profiles.zip,
		authorize_payment_profiles.added_on
		
	FROM
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
	INNER JOIN
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
		ON
		authorize_customer_profiles.customer_profile_id = authorize_payment_profiles.customer_profile_id
	WHERE
		authorize_customer_profiles.custno = " . $db->quote($_POST['custno']) . "
		AND
		authorize_customer_profiles.live = " . (ISLIVE ? '1' : '0') . "
");

?>

<div id="payments-container" custno="<?php print htmlentities($_POST['custno'], ENT_QUOTES);?>">
	<h3>Stored Cards</h3>

	<?php
	if(!ISLIVE) {
		?>
		<div class="notification-container notification-error stay">
			<div class="notification "><b>WARNING</b>: This is the <b>TEST</b> site and any credit card transactions ran will not be posted to an actual account.</div>
		</div>
		<?php
	}
	?>

	<table class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky">
		<thead>
			<tr>
				<th></th>
				<th class="filterable sortable">ID</th>
				<th class="filterable sortable">Authorize Customer<br />Profile ID</th>
				<th class="filterable sortable">Authorize Payment<br />Profile ID</th>
				<th class="filterable sortable">Nickname</th>
				<th class="filterable sortable">Name On Card</th>
				<th class="filterable sortable">Last 4</th>
				<th class="filterable sortable">Code</th>
				<th class="filterable sortable">Expiration</th>
				<th class="filterable sortable">Added On</th>
				<th class="filterable sortable">Added By</th>
				<th class="filterable sortable">Billing Address</th>
				<th class="filterable sortable">Billing Zip</th>
			</tr>
		</thead>
		<tbody class="transactions-tbody">
			<?php
			foreach($grab_payment_profiles as $payment_profile) {
				?>
				<tr class="payment-profile" payment_profile_id="<?php print htmlentities($payment_profile['payment_profile_id'], ENT_QUOTES);?>">
					<td><i class="fa fa-minus action action-remove"></i></td>
					<td><?php print htmlentities($payment_profile['payment_profile_id']);?></td>
					<td><?php print htmlentities($payment_profile['authorize_custprofile_id']);?></td>
					<td><?php print htmlentities($payment_profile['authorize_payprofile_id']);?></td>
					<td><?php print htmlentities($payment_profile['profile_name']);?></td>
					<td><?php print htmlentities($payment_profile['nameoncard']);?></td>
					<td><?php print htmlentities($payment_profile['last4']);?></td>
					<td><?php print htmlentities($payment_profile['code']);?></td>
					<td><?php print htmlentities($payment_profile['expiration']);?></td>
					<td><?php print htmlentities($payment_profile['added_on']);?></td>
					<td><?php print htmlentities($payment_profile['salesmn']);?></td>
					<td><?php print htmlentities($payment_profile['address']);?></td>
					<td><?php print htmlentities($payment_profile['zip']);?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<h3>Store New Card</h3>
	<form id="cc-addcard" class="form-horizontal" method="post" action="<?php print BASE_URI;?>/dashboard/creditcard/payment-profiles/create">
		<input type="hidden" name="custno" value="<?php print htmlentities(trim($_POST['custno']), ENT_QUOTES);?>" />
		<div class="control-group">
			<label class="control-label" for="cc-nickname">Saved Card Nickname</label>
			<div class="controls">
				<input type="text" name="nickname" id="cc-nickname" required>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="cc-nameoncard">Name On Card</label>
			<div class="controls">
				<input type="text" name="nameoncard" id="cc-nameoncard" required>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="cc-cardnumber">Card Number</label>
			<div class="controls">
				<input type="text" name="card_number" id="cc-cardnumber" required>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="cc-expiration">Expiration</label>
			<div class="controls">
				<select name="expiration_month" class="span2" id="cc-expiration" required>
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
			<label class="control-label" for="cc-securitycode">Security Code</label>
			<div class="controls">
				<input type="text" name="code" class="span1" id="cc-securitycode" required />
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="cc-streetaddress">Street Address</label>
			<div class="controls">
				<input type="text" name="street_address" class="span3" id="cc-streetaddress" placeholder="Address and/or Zip Required" />
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="cc-zipcode">Zip Code</label>
			<div class="controls">
				<input type="text" name="zip_code" class="span3" id="cc-zipcode" placeholder="Address and/or Zip Required" />
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">Add Card</button>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	/**
	 * Bind to clicks on Remove Payment Profile icon.
	 */
	$(document).off('click', '#payments-container .payment-profile .action-remove');
	$(document).on('click', '#payments-container .payment-profile .action-remove', function(event) {
		alert('clicky.');
	});

	/**
	 * Bind to Add New Card form submissions.
	 */
	$(document).off('submit', '#cc-addcard');
	$(document).on('submit', '#cc-addcard', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		// Display loading overlay.
		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');
		
		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': $form.attr('method'),
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			},
			'error': function() {
				alert('Something didn\'t go right');
			},
			'success': function(response) {
				if(!response.success) {
					if('message' in response) {
						alert(response.message);
					}
					if('errors' in response) {
						$.each(response.errors, function(offset, error) {
							alert('Error Code ' + error.code + ': ' + error.message);
						});
					}
					return;
				}
				
				// Force re-load of tab now that payment profile has been created.
				$('#client-details-container .tabs [page="payments"]').click();
			}
		});
		
		return false;
	});
	
	/**
	 * Bind to clicks on "Delete Card" icon.
	 */
	$(document).off('click', '#payments-container .payment-profile .action-remove');
	$(document).on('click', '#payments-container .payment-profile .action-remove', function(event) {
		var $icon = $(this);
		var $payment_profile = $icon.closest('.payment-profile');
		var payment_profile_id = $payment_profile.attr('payment_profile_id');
		var $payments_container = $payment_profile.closest('#payments-container');
		var custno = $payments_container.attr('custno');
		var data = {
			'custno': custno,
			'payment_profile_id': payment_profile_id
		};

		if(confirm('Are you sure you want to permanently delete this stored card?')) {
			// Display loading overlay.
			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/dashboard/creditcard/payment-profiles/delete',
				'method': 'POST',
				'dataType': 'json',
				'data': data,
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				},
				'error': function() {
					alert('Something didn\'t go right');
				},
				'success': function(response) {
					if(!response.success) {
						if(!response.message) {
							alert('Something didn\'t go right...');
						} else {
							alert(response.message);
						}
						return;
					}

					// Success.
					$payment_profile.remove();
				}
			});
		}
	});
</script>

<?php
