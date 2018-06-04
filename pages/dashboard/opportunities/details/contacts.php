<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity = $db->query("
	SELECT
		opportunities.custno,
		opportunities.opportunity_id
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

// `custno` is in the format "ASC123 - ABC Company Inc" -- All we want is "ABC123".
$custno = explode(' - ', $opportunity['custno']);
$custno = trim($custno[0]);

if(!empty($opportunity['custno'])) { // Client.
	$grab_contacts = $db->query("
		SELECT
			contacts.sf_contact_id AS contact_id,
			contacts.FirstName,
			contacts.LastName,
			contacts.Title,
			contacts.Email,
			contacts.Phone,
			contacts.HomePhone,
			contacts.MobilePhone,
			contacts.Fax
		FROM
			" . DB_SCHEMA_ERP . ".sf_contacts AS contacts
		INNER JOIN
			" . DB_SCHEMA_ERP . ".arcust
			ON
			arcust.sfid LIKE (contacts.AccountId + '%')
		WHERE
			LTRIM(RTRIM(arcust.custno)) = " . $db->quote($custno) . "
		ORDER BY
			contacts.FirstName,
			contacts.LastName,
			contacts.Title
	");
	$debug_class = 'client';
} else { // Prospect.
	$grab_contacts = $db->query("
		SELECT
			contacts.contact_id,
			contacts.FirstName,
			contacts.LastName,
			contacts.Title,
			contacts.Email,
			contacts.Phone,
			contacts.HomePhone,
			contacts.MobilePhone,
			contacts.Fax
		FROM
			" . DB_SCHEMA_ERP . ".opportunity_contacts AS contacts
		WHERE
			contacts.opportunity_id = " . $db->quote($opportunity['opportunity_id']) . "
	");
	$debug_class = 'prospect';
}

/**
 * Gather all contacts into a reusable list.
 */
$all_contacts = [];
foreach($grab_contacts as $contact) {
	$all_contacts[$contact['contact_id']] = $contact;
	$all_contacts[$contact['contact_id']]['primary'] = False;
}

/**
 * Gather contact info for each contact selected as "Primary".
 */
$grab_primary_contacts = $db->query("
	SELECT
		opportunity_contacts_primary.primary_contact_id,
		opportunity_contacts_primary.contact_id
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_contacts_primary
	WHERE
		opportunity_contacts_primary.opportunity_id = " . $db->quote($opportunity['opportunity_id']) . "
");
$primary_contacts = [];
foreach($grab_primary_contacts as $primary_contact) {
	if(!empty($all_contacts[$primary_contact['contact_id']])) {
		$primary_contact_entry = $all_contacts[$primary_contact['contact_id']];
		$primary_contacts[] = $primary_contact_entry;
		$all_contacts[$primary_contact['contact_id']]['primary'] = True;
	}
}

// Sort the list of `primary_contacts in the same fashion `all_contacts` are sorted.
uasort($primary_contacts, function($a, $b) {
	$a_merged = $a['FirstName'] . $a['LastName'] . $a['Title'];
	$b_merged = $b['FirstName'] . $b['LastName'] . $b['Title'];
	if($a_merged == $b_merged) {
		return 0;
	}
	if($a_merged < $b_merged) {
		return -1;
	} else {
		return 1;
	}
});

?>

<style type="text/css">
	#opportunity-contacts-body .phone::before {
		width:50px;
		display:inline-block;
		font-weight:bold;
	}
	#opportunity-contacts-body .phone-primary::before {
		content:"Primary:";
	}
	#opportunity-contacts-body .phone-home::before {
		content:"Home:";
	}
	#opportunity-contacts-body .phone-mobile::before {
		content:"Mobile:";
	}
	#opportunity-contacts-body .phone-fax::before {
		content:"Fax:";
	}
	#opportunity-contacts-body td.quote {
		width:110px;
	}
	#opportunity-contacts-body .contact.prototype {
		display:none;
	}
	#opportunity-contacts-body .action-hideall-contacts,
	#opportunity-contacts-body .action-showall-contacts {
		cursor:pointer;
	}
	.fa-tree {
		font-size:200px;
		animation: shake 1s infinite linear;
		color:#090;
	}
	@keyframes shake {
	  5%, 45% {
		transform: translate3d(-1px, 0, 0);
	  }
	  50% {
		  transform: translate3d(0px, 0, 0);
	  }

	  10%, 40% {
		transform: translate3d(1px, 0, 0);
	  }

	  15%, 25%, 35% {
		transform: translate3d(-2px, 0, 0);
	  }

	  20%, 30% {
		transform: translate3d(2px, 0, 0);
	  }
	}
</style>

<div id="opportunity-contacts-body" type="<?php print htmlentities($debug_class, ENT_QUOTES);?>">
	<h3>Primary Contacts</h3>
	<table class="table-striped table-small selectedcontacts-table">
		<thead>
			<tr>
				<th></th>
				<th>Name</th>
				<th>Title</th>
				<th>Phone</th>
				<th>E-Mail</th>
			</tr>
		</thead>
		<tbody>
			<tr class="nocontacts <?php print !empty($primary_contacts) ? 'hidden' : Null;?>">
				<td colspan="6">Please select primary contact(s) from the All Contact section below.</td>
			</tr>
			<?php
			if(!empty($primary_contacts)) {
				foreach($primary_contacts as $contact) {
					?>
					<tr class="contact" contact_id="<?php print htmlentities($contact['contact_id'], ENT_QUOTES);?>">
						<td class="quote">
							<button type="button" class="btn btn-primary action-sendquote" opportunity_id="<?php print htmlentities($opportunity['opportunity_id'], ENT_QUOTES);?>" name="<?php print htmlentities($contact['FirstName'] . ' ' . $contact['LastName'], ENT_QUOTES);?>" email="<?php print htmlentities($contact['Email'], ENT_QUOTES);?>">Send Quote</button>
						</td>
						<td class="name"><?php print htmlentities($contact['FirstName'] . ' ' . $contact['LastName']);?></td>
						<td class="title"><?php print htmlentities($contact['Title']);?></td>
						<td class="phones">
							<div class="phone phone-primary <?php print empty($contact['Phone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['Phone']);?></div>
							<div class="phone phone-home <?php print empty($contact['HomePhone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['HomePhone']);?></div>
							<div class="phone phone-mobile <?php print empty($contact['MobilePhone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['MobilePhone']);?></div>
							<div class="phone phone-fax <?php print empty($contact['Fax']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['Fax']);?></div>
						</td>
						<td class="email"><?php print htmlentities($contact['Email']);?></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>

	<h3 class="<?php print !empty($primary_contacts) ? 'action-showall-contacts' : 'action-hideall-contacts';?>">
		<?php
		if(!empty($primary_contacts)) {
			?><i class="fa fa-plus"></i><?php
		} else {
			?><i class="fa fa-minus"></i><?php
		}
		?>
		 All Contacts
	</h3>

	<div id="allcontacts-container" class="<?php print !empty($primary_contacts) ? 'hidden' : Null;?>">
		<table class="table-striped table-small allcontacts-table">
			<thead>
				<tr>
					<th>Primary</th>
					<th>Name</th>
					<th>Title</th>
					<th>Phone</th>
					<th>E-Mail</th>
				</tr>
			</thead>
			<tbody>
				<tr class="contact prototype" contact_id="">
					<td class="select"><input type="checkbox" name="primary" /></td>
					<td class="name"></td>
					<td class="title"></td>
					<td class="phones">
						<div class="phone phone-primary"></div>
						<div class="phone phone-home"></div>
						<div class="phone phone-mobile"></div>
						<div class="phone phone-fax"></div>
					</td>
					<td class="email"><?php print htmlentities($contact['Email']);?></td>
				</tr>
				<?php
				foreach($all_contacts as $contact) {
					?>
					<tr class="contact" contact_id="<?php print htmlentities($contact['contact_id'], ENT_QUOTES);?>">
						<td class="primary"><input type="checkbox" name="primary" <?php print $contact['primary'] ? 'checked' : Null;?> /></td>
						<td class="name"><?php print htmlentities($contact['FirstName'] . ' ' . $contact['LastName']);?></td>
						<td class="title"><?php print htmlentities($contact['Title']);?></td>
						<td class="phones">
							<div class="phone phone-primary <?php print empty($contact['Phone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['Phone']);?></div>
							<div class="phone phone-home <?php print empty($contact['HomePhone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['HomePhone']);?></div>
							<div class="phone phone-mobile <?php print empty($contact['MobilePhone']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['MobilePhone']);?></div>
							<div class="phone phone-fax <?php print empty($contact['Fax']) ? 'hidden' : Null;?>"><?php print htmlentities($contact['Fax']);?></div>
						</td>
						<td class="email"><?php print htmlentities($contact['Email']);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="6">
						<button type="button" class="btn btn-primary action-add-contact overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/contacts/edit" overlayz-data="<?php print htmlentities(json_encode(['opportunity_id' => $opportunity['opportunity_id'], 'custno' => !empty($custno) ? $custno : '']), ENT_QUOTES);?>"><i class="fa fa-plus"></i> Add Contact</button>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<script type="text/javascript">
	/**
	 * Bind to clicks on "Send Quote" icon.
	 */
	$(document).off('click', '#opportunity-contacts-body .quote .action-sendquote');
	$(document).on('click', '#opportunity-contacts-body .quote .action-sendquote', function(event) {
		var $contact = $(this).closest('.contact');
		var $contacts_table = $contact.closest('.contacts-table');
		var $opportunity_details_container = $('#opportunity-details-container');
		var $email = $contact.find('.email');
		var $name = $contact.find('.name');
		var email = $email.text().trim();
		var name = $name.text().trim();
		var opportunity_id = $opportunity_details_container.attr('opportunity_id');

		var $loading_overlay = $.overlayz({
			'html': $('<div>').append(
				$('<div style="width:400px;margin:auto;mrgin-top:48px;">').append(
					$('<i class="fa fa-tree" style="display:block;float:left;">'),
					$('<div style="font-size:24px;font-weight:bold;padding-left:16px;padding-top:64px;">').text('Please wait while we chop a tree to generate this PDF...')
				)
			)
		}).hide();
		$loading_overlay.fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/quote',
			'data': {
				'opportunity_id': opportunity_id,
				'name': name,
				'email': email
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				$loading_overlay.find('.overlayz-body').html(response.html);
			}
		});
	});

	/**
	 * Bind to clicks on Primary Contact checkbox.
	 */
	$(document).off('change', '#opportunity-contacts-body .contact :input[name="primary"]');
	$(document).on('change', '#opportunity-contacts-body .contact :input[name="primary"]', function(event) {
		var $checkbox = $(this);
		var $primary_contacts_tbody = $('#opportunity-contacts-body .selectedcontacts-table > tbody');

		var $opportunity = $('#opportunity-details-container');
		var opportunity_id = $opportunity.attr('opportunity_id');

		var $source_contact = $checkbox.closest('.contact');
		var contact_id = $source_contact.attr('contact_id');

		if($checkbox.is(':checked')) { // Add Primary
			$primary_contacts_tbody.find('.nocontacts').hide();
			var $contact = $source_contact.clone();

			var name = $contact.find('.name').text().trim();
			var email = $contact.find('.email').text().trim();

			var overlayz_data = JSON.stringify({
				'opportunity_id': opportunity_id,
				'name': name,
				'email': email
			});
			var overlayz_url = BASE_URI + '/dashboard/opportunities/quote/email';

			$contact.find('.primary').removeClass('primary').addClass('quote').empty().append(
				$('<button type="button" class="btn btn-primary action-sendquote overlayz-link">').text('Send Quote').attr('overlayz-url', overlayz_url).attr('overlayz-data', overlayz_data)
			);

			$contact.appendTo($primary_contacts_tbody);

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/contacts/primary/add',
				'method': 'POST',
				'data': {
					'opportunity_id': opportunity_id,
					'contact_id': contact_id
				},
				'dataType': 'json',
				'success': function(response) {
					// Nothing to do.
				}
			});
		} else { // Remove Primary
			var $contact = $primary_contacts_tbody.find('.contact[contact_id="' + $source_contact.attr('contact_id') + '"]');
			$contact.remove();

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/contacts/primary/remove',
				'method': 'POST',
				'data': {
					'opportunity_id': opportunity_id,
					'contact_id': contact_id
				},
				'dataType': 'json',
				'success': function(response) {
					// Nothing to do.
				}
			});

			// If no more primary contacts, show "please select" text.
			if(!$primary_contacts_tbody.find('.contact').length) {
				$primary_contacts_tbody.find('.nocontacts').show();
			}
		}
	});

	/**
	 * Bind to clicks on "Show all contacts" icon.
	 */
	$(document).off('click', '#opportunity-contacts-body .action-showall-contacts');
	$(document).on('click', '#opportunity-contacts-body .action-showall-contacts', function(event) {
		var $heading = $(this);
		var $icon = $heading.find('.fa');
		$icon.removeClass('fa-plus').addClass('fa-minus');
		$heading.removeClass('action-showall-contacts').addClass('action-hideall-contacts');
		var $allcontacts_table = $('#allcontacts-container');
		$allcontacts_table.slideDown('fast');
	});

	/**
	 * Bind to clicks on "Show all contacts" icon.
	 */
	$(document).off('click', '#opportunity-contacts-body .action-hideall-contacts');
	$(document).on('click', '#opportunity-contacts-body .action-hideall-contacts', function(event) {
		var $heading = $(this);
		var $icon = $heading.find('.fa');
		$icon.removeClass('fa-minus').addClass('fa-plus');
		$heading.removeClass('action-hideall-contacts').addClass('action-showall-contacts');
		var $allcontacts_table = $('#allcontacts-container');
		$allcontacts_table.slideUp('fast');
	});
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
