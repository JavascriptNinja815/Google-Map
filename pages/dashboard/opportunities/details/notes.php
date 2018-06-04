<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		opportunities.notes
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = opportunities.login_id
	LEFT JOIN
		" . DB_SCHEMA_INTERNAL . ".logins AS enteredby_logins
		ON
		enteredby_logins.login_id = opportunities.entered_login_id
	INNER JOIN
		" . DB_SCHEMA_ERP . ".opportunity_types
		ON
		opportunity_types.opportunity_type_id = opportunities.opportunity_type_id
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = opportunities.custno
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".offices
		ON
		offices.terr = opportunities.terr
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

?>

<style type="text/css">
	#opportunity-notes-body .notes-container .notes-row {
		border-bottom:1px solid #ccc;
		padding-bottom:2px;
		padding-top:2px;
		font-size:0.8em;
	}
	#opportunity-notes-body .notes-container .notes-row:first-of-type {
		padding-top:0;
	}
	#opportunity-notes-body .notes-container .notes-row:last-of-type {
		border-bottom: none;
		padding-bottom: 0;
	}
	#opportunity-notes-body .notes-new-container {
		margin:0;
		min-height:23px;
		font-size:0.8em;
	}
	#opportunity-notes-body .notes-new-container input {
		font-size:11px;
		height:17px;
		line-height:17px;
		padding:2px 4px;
		margin:0;
	}
	#opportunity-notes-body .notes-new-container button {
		font-size:11px;
		height:23px;
		line-height:17px;
		padding:2px 4px;
		margin:0;
		left:-4px;
		position:relative;
	}
</style>

<div id="opportunity-notes-body">
	<div class="customer-notes-wrapper">
		<h3>Opportunity Notes</h3>
		<div class="notes notes-container">
			<?php
			foreach(explode("\n", $opportunity['notes']) as $note_part) {
				$note_part = trim(str_replace("\r", '', $note_part));
				if(!empty($note_part)) {
					?><div class="notes-row"><?php print htmlentities($note_part);?></div><?php
				}
			}
			?>
		</div>
		<form id="opportunity-notes-form">
			<input type="hidden" name="opportunity_id" value="<?php print htmlentities($opportunity['opportunity_id'], ENT_QUOTES);?>" />
			<div class="notes-new-container input-append input-block-level">
				<input type="text" class="notes-new-input" name="notes">
				<button type="submit" class="notes-new-button btn">Add</button>
			</div>
		</form>
	</div>
</div>

<script type="text/javascript">
	$(document).off('submit', '#opportunity-notes-form');
	$(document).on('submit', '#opportunity-notes-form', function(event) {
		var form = this;
		var $form = $(this);
		var data = new FormData(form);
		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/notes/add',
			'method': 'POST',
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return false;
				}
				var $notes_container = $('#opportunity-notes-body .notes-container');
				$notes_container.append(
					$('<div class="notes-row">').text(response.note)
				);
			}
		});
		return false; // Prevent form submission propogation.
	});
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
