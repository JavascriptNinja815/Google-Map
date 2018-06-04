<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.
?>
<form id="shipping-address-form" class="form-horizontal">
	<h3>Shipping Address</h3>
	<div class="sono-group">
		<div class="control-group">
			<label class="control-label" for="shipping-address-company">Company</label>
			<div class="controls">
				<input type="text" id="shipping-address-company" name="address[company]" class="input-xlarge" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-line1">Address Line 1</label>
			<div class="controls">
				<input type="text" id="shipping-address-line1" name="address[address1]" class="input-xlarge" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-line2">Address Line 2</label>
			<div class="controls">
				<input type="text" id="shipping-address-line2" name="address[address2]" class="input-xlarge" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-line3">Address Line 3</label>
			<div class="controls">
				<input type="text" id="shipping-address-line3" name="address[address3]" class="input-xlarge" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-city">City</label>
			<div class="controls">
				<input type="text" id="shipping-address-city" name="address[city]" class="input-xlarge" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-state">State</label>
			<div class="controls">
				<input type="text" id="shipping-address-state" name="address[state]" class="input-small" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-zip">Zip Code</label>
			<div class="controls">
				<input type="text" id="shipping-address-zip" name="address[zip]" class="input-small" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-address-country">Country</label>
			<div class="controls">
				<input type="text" id="shipping-address-country" name="address[country]" class="input-small" maxlength="2" />
		</div>
		</div>
	</div>

	<h3>Contact Info</h3>
	<div class="sono-group">
		<div class="control-group">
			<label class="control-label" for="shipping-contact-name">Name</label>
			<div class="controls">
				<input type="text" id="shipping-contact-name" name="contact[name]" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-contact-phone">Phone</label>
			<div class="controls">
				<input type="text" id="shipping-contact-phone" name="contact[phone]" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="shipping-contact-email">E-Mail</label>
			<div class="controls">
				<input type="text" id="shipping-contact-email" name="contact[email]" />
			</div>
		</div>
	</div>

	<div id="acc-no-container">
		<h3>Account Number</h3>
		<div class="sono-group">
			<div class="control-group">
				<label class="control-label" for="acct-no">Account #</label>
				<div class="controls">
					<input id="acct-no" type="text" name="acct-no" />
				</div>
			</div>
		</div>
	</div>


	<button class="btn btn-primary" type="submit">Apply</button>
</form>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
