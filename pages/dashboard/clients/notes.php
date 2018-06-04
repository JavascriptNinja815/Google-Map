<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

if($_POST['type'] == 'invoice-notes') {
	$grab_notes = $db->query("
		SELECT
			artran.arinvnts AS notes
		FROM
			" . DB_SCHEMA_ERP . ".artran
		WHERE
			RTRIM(LTRIM(artran.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			RTRIM(LTRIM(artran.invno)) = " . $db->quote(trim($_POST['invno'])) . "
	");
	$notes = $grab_notes->fetch();
	$notes = explode("\n", $notes['notes']);
} else if($_POST['type'] == 'client-notes') {
	$grab_notes = $db->query("
		SELECT
			arcust.arnotes AS notes
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		WHERE
			RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	");
	$notes = $grab_notes->fetch();
	$notes = explode("\n", $notes['notes']);
}
?>
<form id="notes-overlay" method="POST" action="<?php print BASE_URI;?>/dashboard/clients/notes/add">
	<input type="hidden" name="type" value="<?php print htmlentities(trim($_POST['type']), ENT_QUOTES);?>" />
	<input type="hidden" name="custno" value="<?php print htmlentities(trim($_POST['custno']), ENT_QUOTES);?>" />
	<?php
	if($_POST['type'] == 'invoice-notes') {
		?>
		<h2>Invoice Notes for <?php print htmlentities(trim($_POST['invno']));?></h2>
		<input type="hidden" name="invno" value="<?php print htmlentities(trim($_POST['invno']), ENT_QUOTES);?>" />
		<?php
	} else if($_POST['type'] == 'client-notes') {
		?>
		<h2>Client Notes for <?php print htmlentities(trim($_POST['custno']));?></h2>
		<?php
	}
	?>
	<div class="notes notes-container">
		<?php
		foreach($notes as $note) {
			if(trim($note)) {
				?><div class="note notes-row"><?php print htmlentities(str_replace("\r", "", $note));?></div><?php
			}
		}
		?>
	</div>
	<div class="input-append">
		<input type="text" class="notes span4" name="notes" />
		<button type="submit" class="add-note btn">Add</button>
	</div>
</form>

<script type="text/javascript">
	$(document).off('submit', 'form#notes-overlay');
	$(document).on('submit', 'form#notes-overlay', function(event) {
		var $form = $(this);
		var $submit = $form.find(':input[type="submit"]');
		var $overlay_body = $form.closest('.overlayz-body');
		var $input = $overlay_body.find(':input[name="notes"]');
		var new_notes = $input.val();

		$submit.prop('disabled', true);
		var data = new FormData(this);

		if(new_notes.length) {
			var $notes_container = $overlay_body.find('.notes-container');
			var $note = $('<div class="note notes-row">');

			$.ajax({
				'url': $form.attr('action'),
				'method': $form.attr('method'),
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
						return;
					}
					$note.text(response.note).appendTo($notes_container);
					$input.val('');
					$submit.prop('disabled', false);
				}
			});
		}
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
