<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_contacts = $db->query("
	SELECT
		sf_contacts.Title,
		sf_contacts.FirstName,
		sf_contacts.LastName,
		sf_contacts.Email,
		sf_contacts.Phone,
		sf_contacts.HomePhone,
		sf_contacts.MobilePhone,
		sf_contacts.Fax,
		sf_contacts.MailingStreet,
		sf_contacts.MailingCity,
		sf_contacts.MailingState,
		sf_contacts.MailingPostalCode,
		sf_contacts.MailingCountry
	FROM 
		" . DB_SCHEMA_ERP . ".arcust
	INNER JOIN
		" . DB_SCHEMA_ERP . ".sf_contacts
		ON
		arcust.sfid LIKE (sf_contacts.AccountId + '%')
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	ORDER BY
		sf_contacts.LastName,
		sf_contacts.FirstName
");

?>

<style type="text/css">
	#client-details-page .contacts .phone::before {
		width:50px;
		display:inline-block;
		font-weight:bold;
	}
	#client-details-page .contacts .phone-primary::before {
		content:"Primary:";
	}
	#client-details-page .contacts .phone-home::before {
		content:"Home:";
	}
	#client-details-page .contacts .phone-mobile::before {
		content:"Mobile:";
	}
	#client-details-page .contacts .phone-fax::before {
		content:"Fax:";
	}
</style>

<h2>Contacts</h2>

<table class="contacts">
	<thead>
		<tr>
			<th>Title</th>
			<th>Name</th>
			<th>Address</th>
			<th>E-Mail</th>
			<th>Phone</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($grab_contacts as $contact) {
			?>
			<tr>
				<td><?php print htmlentities($contact['Title']);?></td>
				<td>
					<?php
					print $contact['FirstName'];
					if(!empty($contact['FirstName']) && !empty($contact['LastName'])) {
						print ' ' ;
					}
					print $contact['LastName'];
					?>
				</td>
				<td>
					<?php
					print $contact['MailingStreet'];
					print '<br />';
					print $contact['MailingCity'] . ' ' . $contact['MailingState'] . ' ' . $contact['MailingPostalCode'];
					print '<br />';
					print $contact['MailingCountry'];
					?>
				</td>
				<td class="email"><?php print htmlentities($contact['Email']);?></td>
				<td class="phones">
					<?php
					if(!empty($contact['Phone'])) {
						?><div class="phone phone-primary"><?php print htmlentities($contact['Phone']);?></div><?php
					}
					if(!empty($contact['HomePhone'])) {
						?><div class="phone phone-home"><?php print htmlentities($contact['HomePhone']);?></div><?php
					}
					if(!empty($contact['MobilePhone'])) {
						?><div class="phone phone-mobile"><?php print htmlentities($contact['MobilePhone']);?></div><?php
					}
					if(!empty($contact['Fax'])) {
						?><div class="phone phone-fax"><?php print htmlentities($contact['Fax']);?></div><?php
					}
					?>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
