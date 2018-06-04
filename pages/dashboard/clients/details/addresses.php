<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_addresses = $db->query("
	SELECT
		arcadr.cshipno AS ship_to_number,
		arcadr.company AS company,
		arcadr.contact,
		arcadr.title,
		arcadr.email,
		arcadr.phone,
		arcadr.faxno,
		arcadr.address1,
		arcadr.address2,
		arcadr.city,
		arcadr.addrstate AS state,
		arcadr.zip,
		arcadr.country,
		arcadr.resdntl AS residential,
		arcadr.fob,
		arcadr.shipvia,
		arcadr.shipchg AS frt_pay_method,
		arcadr.upsshpact AS carrier_account_number,

		arcadr.salesmn AS salesman,
		arcadr.terr AS territory,
		arcadr.tax AS address_tax,
		arcust.tax AS client_tax,
		CONVERT(varchar(10), arcadr.entered, 120) AS entered,
		arcadr.defaship,
		arcadr.comment
	FROM 
		" . DB_SCHEMA_ERP . ".arcust
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcadr
		ON
		arcadr.custno = arcust.custno
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	ORDER BY
		(CASE WHEN RTRIM(LTRIM(arcadr.defaship)) = 'Y' THEN
			1
		ELSE
			0
		END) DESC
");

?>

<style type="text/css">
	#client-details-container .addresses tfoot td {
		padding-top:12px;
	}
</style>

<h2>Ship-To Addresses</h2>

<table class="addresses">
	<thead>
		<tr>
			<th></th>
			<th></th>
			<th>Ship To No</th>
			<th>Company</th>
			<th>Contact</th>
			<th>Address</th>
			<th>Residential</th>
			<th>FOB</th>
			<th>Ship Via</th>
			<th>Frt Pay Method</th>
			<th>Carrier Account No.</th>
			<th>Sales Person</th>
			<th>Territory</th>
			<th>Tax Rate</th>
			<th>Entered</th>
			<th>Comments</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($grab_addresses as $address) {
			?><tr class="address <?php print trim($address['defaship'] == 'Y') ? 'bg-highlight' : Null;?>" cshipno="<?php print htmlentities(trim($address['ship_to_number']), ENT_QUOTES);?>"><?php
				?><td><?php
					?><i class="fa fa-pencil action action-edit-address overlayz-link" overlayz-url="/dashboard/clients/details/addresses/edit" overlayz-data="<?php print htmlentities(
						json_encode([
							'action' => 'edit',
							'cshipno' => trim($address['ship_to_number']),
							'custno' => trim($_POST['custno'])
						])
					);?>"></i><?php
				?><td class="client-address-default"><?php
					if(trim($address['defaship']) == 'Y') {
						print '<b>Default</b>';
					} else {
						?><i class="fa fa-check action action-default-address action-link" title="Set as Default Address"></i><?php
					}
				?></td><?php
				?><td><?php print htmlentities(trim($address['ship_to_number']));?></td><?php
				?><td><?php print htmlentities(trim($address['company']));?></td><?php
				?><td class="client-address-contact"><?php
					if(!empty(trim($address['title']))) {
						?><div class="client-address-title"><?php print htmlentities(trim($address['title']));?></div><?php
					}
					?><div class="client-address-contactname"><?php print htmlentities(trim($address['contact']));?></div><?php
					?><div class="client-address-email">Email: <span class="contactname"><?php print htmlentities(trim($address['email']));?></span></div><?php
					?><div class="client-address-phone">Phone: <span class="contact-name"><?php print htmlentities(trim($address['phone']));?></span></div><?php
					?><div class="client-address-fax">Fax: <?php print htmlentities(trim($address['faxno']));?></div><?php
				?></td><?php
				?><td class="client-address-address"><?php
					?><div class="client-address-address1"><?php print htmlentities(trim($address['address1']));?><?php
					if(!empty(trim($address['address2']))) {
						?><div class="client-address-address2"><?php print htmlentities(trim($address['address2']));?><?php
					}
					?><div class="client-address-citystatezip"><?php
						?><span class="client-address-city"><?php print htmlentities(trim($address['city']));?></span><?php
						if(!empty($address['city']) && !empty($address['state'])) {
							?>, <?php
						}
						?><span class="client-address-state"><?php print htmlentities(trim($address['state']));?></span><?php
						?> <span class="client-address-zip"><?php print htmlentities(trim($address['zip']));?></span><?php
					?></div><?php
					?><div class="client-address-country"><?php print htmlentities(trim($address['country']));?><?php
				?></td><?php
				?><td class="client-address-residential"><?php
					if(trim($address['residential']) == 1) {
						print 'Yes';
					}
				?></td><?php
				?><td class="client-address-fob"><?php print htmlentities(trim($address['fob']));?></td><?php
				?><td class="client-address-shipvia"><?php print htmlentities(trim($address['shipvia']));?></td><?php
				?><td class="client-address-frtpaymethod"><?php print htmlentities(trim($address['frt_pay_method']));?></td><?php
				?><td class="client-address-carrieraccountno"><?php print htmlentities(trim($address['carrier_account_number']));?></td><?php
				?><td class="client-address-salesman"><?php print htmlentities(trim($address['salesman']));?></td><?php
				?><td class="client-address-territory"><?php print htmlentities(trim($address['fob']));?></td><?php
				?><td class="client-address-tax <?php print trim($address['client_tax']) != trim($address['address_tax']) ? 'client-address-highlight' : Null;?>"><?php
					if(!(int)trim($address['address_tax'])) {
						print 'Exempt';
					} else {
						print number_format(trim($address['address_tax']), 2) . '%';
					}
				?></td><?php
				?><td class="client-address-entered"><?php print htmlentities(trim($address['entered']));?></td><?php
				?><td class="client-address-comments"><?php print htmlentities(trim($address['comment']));?></td><?php
			?></tr><?php
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="16">
				<span class="action action-add-address overlayz-link" overlayz-url="/dashboard/clients/details/addresses/edit" overlayz-data="<?php print htmlentities(json_encode([
						'action' => 'add',
						'custno' => trim($_POST['custno'])
					])
				);?>">
					<i class="fa fa-plus"></i>
					Add Address
				</span>
			</td>
		</tr>
	</tfoot>
</table>

<script type="text/javascript">
	// Bind to clicks on "Make Default" icon.
	$(document).off('click', '#client-details-page .addresses .address .action-default-address');
	$(document).on('click', '#client-details-page .addresses .address .action-default-address', function(event) {
		if(confirm('Are you sure you want this to be the client\'s Default Address?')) {
			var $address = $(this).closest('.address');
			var $client = $address.closest('#client-details-container');

			var cshipno = $address.attr('cshipno');
			var custno = $client.attr('custno');

			var post_data = {
				'custno': custno,
				'cshipno': cshipno
			};
			var $default_address_overlay = activateOverlayZ(
				BASE_URI + '/dashboard/clients/details/addresses/default', // url
				post_data, // post_data
				undefined, // beforesend_callback
				function(data) { // success_callback 
					// Fade out and remove "loading" overlay after successful default address appointment.
					$default_address_overlay.fadeOut(function() {
						$(this).remove();
					});

					// Grab old default address.
					var $old_default_address = $address.closest('.addresses').find('.address.bg-highlight');
					// Remove highlight from default address.
					$old_default_address.removeClass('bg-highlight');
					$old_default_address.find('.client-address-default').html(
						$('<i class="fa fa-check action action-default-address" title="Set as Default Address">')
					);

					// Add highlight to newly appointed default address.
					$address.addClass('bg-highlight');
					$address.find('.client-address-default').html('<b>').text('Default');
				},
				undefined // complete_callback
			);
		}
	});
</script>
