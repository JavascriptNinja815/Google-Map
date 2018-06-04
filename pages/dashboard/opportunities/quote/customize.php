<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		opportunities.terr,
		opportunities.custno
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	WHERE
		opportunities.opportunity_id = " . $db->quote($_REQUEST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

$grab_attachments = $db->query("
	SELECT
		opportunity_attachments.attachment_id,
		opportunity_attachments.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_attachments
	ORDER BY
		opportunity_attachments.name
");

if(!$opportunity['custno']) {
	// Prospect.
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
} else {
	// Client.
	$grab_contacts = $db->query("
		SELECT
			sf_contacts.sf_contact_id,
			sf_contacts.FirstName,
			sf_contacts.LastName,
			sf_contacts.Title,
			sf_contacts.Email
		FROM
			" . DB_SCHEMA_ERP . ".sf_contacts
		INNER JOIN
			" . DB_SCHEMA_ERP . ".arcust
			ON
			arcust.sfid LIKE (sf_contacts.AccountId + '%')
		WHERE
			arcust.custno = " . $db->quote(trim($opportunity['custno'])) . "
		ORDER BY
			sf_contacts.FirstName,
			sf_contacts.LastName,
			sf_contacts.Title
	");
}

$grab_offices = $db->query("
	SELECT
		offices.email,
		offices.name
	FROM
		" . DB_SCHEMA_ERP . ".offices
	WHERE
		offices.email IS NOT NULL
		AND
		offices.email != ''
	ORDER BY
		offices.name
");

?>

<style type="text/css">
	#sendquote-form .trumbowyg-box {
		margin:0;
	}
	#sendquote-form .trumbowyg-box,
	#sendquote-form .trumbowyg-editor {
		min-height:200px;
	}
	#sendquote-form input[type="checkbox"] {
		zoom:2;
		margin:0;
	}
	#send-header {
		align-items: center;
		align-content: center;
		display: flex;
		padding-bottom: 10px;
	}
	.api-radio {
		vertical-align: top !important;
		margin-right: 5px !important;
	}
</style>

<form id="sendquote-form" class="form-horizontal" method="post" action="<?php print BASE_URI;?>/dashboard/opportunities/quote/send">
	<div id="send-header" class="row-fluid">
		<div class="span2"><h2>Send Quote</h2></div>
		<div class="span1">
			<label><input class="api-radio" type="radio" name="api" value="gmail" checked/>Gmail</label>
		</div>
		<div class="span1">
			<label><input class="api-radio" type="radio" name="api" value="nylas"/>Nylas</label>
		</div>
		
	</div>
	<input type="hidden" name="opportunity_id" value="<?php print htmlentities($_REQUEST['opportunity_id'], ENT_QUOTES);?>" />
	<input type="hidden" name="email" value="<?php print htmlentities($_REQUEST['email'], ENT_QUOTES);?>" />
	<input type="hidden" name="name" value="<?php print htmlentities($_REQUEST['name'], ENT_QUOTES);?>" />
	<input type="hidden" name="filename" value="<?php print htmlentities($_REQUEST['filename'], ENT_QUOTES);?>" />

	<div class="control-group">
		<label class="control-label" for="sendquote-message">Personalized Message</label>
		<div class="controls">
			<textarea name="message" id="sendquote-message">Thank you for the opportunity - <?php print htmlentities(COMPANY_NAME);?></textarea>
		</div>
	</div>

	<div class="half">
		<div class="control-group">
			<label class="control-label" for="sendquote-ccself">Carbon Copy</label>
			<div class="controls">
				<label>
					<input type="checkbox" name="ccself" id="sendquote-ccself" value="1" /> Send Copy To Me
				</label>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="sendquote-message">Attachments</label>
			<div class="controls">
				<div class="attachments">
					<label class="attachment">
						<input type="checkbox" disabled="disabled" checked="checked" /> Quote
					</label>
					<?php
					foreach($grab_attachments as $attachment) {
						?>
						<label class="attachment">
							<input type="checkbox" name="attachments[]" value="<?php print htmlentities($attachment['attachment_id'], ENT_QUOTES);?>" />
							<?php print htmlentities($attachment['name']);?>
						</label>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="half">
		<div class="control-group">
			<label class="control-label" for="sendquote-offices">CC Offices</label>
			<div class="controls">
				<div class="offices">
					<?php
					foreach($grab_offices as $office) {
						?>
						<label class="ofice">
							<input type="checkbox" name="offices[]" value="<?php print htmlentities('"' . $office['name'] . '" <' . $office['email'] . '>', ENT_QUOTES);?>" />
							<?php print htmlentities($office['name']);?>
						</label>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="sendquote-contacts">CC Contacts</label>
			<div class="controls">
				<div class="contacts">
					<?php
					foreach($grab_contacts as $contact) {
						?>
						<label class="contact">
							<input type="checkbox" name="contacts[]" value="<?php print htmlentities('"' . $contact['FirstName'] . ' ' . $contact['LastName'] . '" <' . $contact['Email'] . '>', ENT_QUOTES);?>" />
							<?php print !empty($contact['Title']) ? htmlentities($contact['Title'] . ' - ') : Null;?>
							<?php print htmlentities($contact['FirstName']);?>
							<?php print htmlentities($contact['LastName']);?>
						</label>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<br />
	<button class="btn btn-primary">Send Quote</button>
</form>

<script type="text/javascript">
	// Initialize WYSIWYG Editor.
	$('#sendquote-message').trumbowyg();
	
	$(document).off('submit', '#sendquote-form');
	$(document).on('submit', '#sendquote-form', function(event) {
		var form = this;
		var $form = $(form);

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

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
					if(!response.message){
						alert('ERROR: Unable to send quote...');
					}else{
						alert('ERROR: '+response.message)
					}
					return;
				}
				alert('Quote successfully sent');
				
				var $overlayz = $form.closest('.overlayz');
				$overlayz.fadeOut('fast', function() {
					$overlayz.remove();
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
