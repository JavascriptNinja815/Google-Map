<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

if(!empty($_REQUEST['contact_id'])) {
	$grab_contact = $db->query("
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
		INNER JOIN
			" . DB_SCHEMA_ERP . ".
		WHERE
			contacts.contact_id = " . $db->quote($_REQUEST['contact_id']) . "
	");
	$contact = $grab_contact->fetch();
}

?>

<style type="text/css">
	#new-contact-form .floatable {
		position:absolute;
		bottom:0px;
		left:12px;
		padding-bottom:6px;
		height:32px;
	}
	#new-contact-container {
		position:absolute;
		top:0px;
		left:0px;
		right:0px;
		bottom:48px;
		overflow:auto;
	}
	#new-contact-container table {
		max-width:600px;
	}
	#new-contact-container table td {
		background-color:#fff;
		border:0;
	}
	#new-contact-container table td {
		vertical-align:top;
	}
</style>
<form id="new-contact-form" class="form-horizontal" method="post" action="<?php print BASE_URI;?>/dashboard/opportunities/contacts/<?php print isset($contact) ? 'update' : 'create';?>">
	<?php
	if(isset($contact)) {
		?><input type="hidden" name="contact_id" value="<?php print htmlentities($contact['contact_id'], ENT_QUOTES);?>" /><?php
	}
	?>
	<input type="hidden" name="custno" value="<?php print !empty($_POST['custno']) ? $_POST['custno'] : '';?>" />
	<input type="hidden" name="opportunity_id" value="<?php print htmlentities($_REQUEST['opportunity_id']);?>" />
	<div id="new-contact-container">
		<h2 class="padded"><?php
			if(isset($contact)) {
				print 'Edit Contact';
			} else {
				print 'Add Contact';
			}
		?></h2>
		<table>
			<tbody>
				<tr>
					<td>
						<div class="control-group">
							<label class="control-label" for="newcontact-name">First Name</label>
							<div class="controls">
								<input type="text" name="firstname" id="newcontact-name" value="<?php print isset($contact) ? htmlentities($contact['FirstName'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-name">Last Name</label>
							<div class="controls">
								<input type="text" name="lastname" id="newcontact-name" value="<?php print isset($contact) ? htmlentities($contact['LastName'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-title">Title</label>
							<div class="controls">
								<input type="text" name="title" id="newcontact-title" value="<?php print isset($contact) ? htmlentities($contact['Title'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-email">E-Mail</label>
							<div class="controls">
								<input type="email" name="email" id="newcontact-email" value="<?php print isset($contact) ? htmlentities($contact['Email'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
					</td>
					<td>
						<div class="control-group">
							<label class="control-label" for="newcontact-phone">Phone</label>
							<div class="controls">
								<input type="tel" name="phone" id="newcontact-phone" value="<?php print isset($contact) ? htmlentities($contact['Phone'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-homephone">Home Phone</label>
							<div class="controls">
								<input type="tel" name="homephone" id="newcontact-homephone" value="<?php print isset($contact) ? htmlentities($contact['HomePhone'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-mobilephone">Mobile Phone</label>
							<div class="controls">
								<input type="tel" name="mobilephone" id="newcontact-mobilephone" value="<?php print isset($contact) ? htmlentities($contact['MobilePhone'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="newcontact-fax">Fax</label>
							<div class="controls">
								<input type="tel" name="fax" id="newcontact-fax" value="<?php print isset($contact) ? htmlentities($contact['Fax'], ENT_QUOTES) : Null;?>" />
							</div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="floatable">
		<button type="submit" class="btn btn-primary"><?php
			if(isset($contact)) {
				print 'Submit Changes';
			} else {
				print 'Add Contact';
			}
		?></button>
	</div>
</form>

<script type="text/javascript">
	var $contacts_tbody = $('#opportunity-contacts-body .allcontacts-table tbody');
	$(document).off('submit', '#new-contact-form');
	$(document).on('submit', '#new-contact-form', function(event) {
		var form = this;
		var $form = $(this);

		var firstname = $form.find(':input[name="firstname"]').val();
		var lastname = $form.find(':input[name="lastname"]').val();
		var title = $form.find(':input[name="title"]').val();
		var phone = $form.find(':input[name="phone"]').val();
		var homephone = $form.find(':input[name="homephone"]').val();
		var mobilephone = $form.find(':input[name="mobilephone"]').val();
		var fax = $form.find(':input[name="fax"]').val();
		var email = $form.find(':input[name="email"]').val();

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		if($form.attr('action').endsWith('update')) {
			var contact_action = 'update';
		} else if($form.attr('action').endsWith('create')) {
			var contact_action = 'create';
		}

		$.ajax({
			'url': $form.attr('action'),
			'data': new FormData(form),
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				// Remove loading overlay.
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});

				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return false;
				}

				if(contact_action === 'update') {
					var contact_id = $form.find(':input[name="contact_id"]');
					var $contact = $contacts_tbody.find('[contact_id="' + contact_id + '"]');
				} else if(contact_action === 'create') {
					var $contact = $contacts_tbody.find('.contact.prototype').clone().removeClass('prototype');
					$contact.appendTo($contacts_tbody);
				}

				$contact.attr('contact_id', response.contact_id);
				$contact.find('.name').text(firstname + ' ' + lastname);
				$contact.find('.title').text(title);
				if(phone) {
					$contact.find('.phone-primary').text(phone).show();
				} else {
					$contact.find('.phone-primary').hide();
				}
				if(homephone) {
					$contact.find('.phone-home').text(homephone).show();
				} else {
					$contact.find('.phone-home').hide();
				}
				if(mobilephone) {
					$contact.find('.phone-mobile').text(mobilephone).show();
				} else {
					$contact.find('.phone-mobile').hide();
				}
				if(fax) {
					$contact.find('.phone-fax').text(fax).show();
				} else {
					$contact.find('.phone-fax').hide();
				}
				$contact.find('.email').text(email);

				// Remove Add/Edit Contact overlay.
				var $contact_overlay = $form.closest('.overlayz');
				$contact_overlay.fadeOut('fast', function() {
					$contact_overlay.remove();
				});
			}
		});

		return false;
	});
</script>

<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
