<?php

$session->ensureLogin();
$session->ensureRole('Accounting');

ob_start(); // Start loading output into buffer.

$grab_ap = $db->query("
	SELECT
		apmast.invno,
		apmast.notes,
		apmast.udref
	FROM
		" . DB_SCHEMA_ERP . ".apmast
	WHERE
		apmast.invno = " . $db->quote($_POST['invno']) . "
");
$ap = $grab_ap->fetch();

?>
<style type="text/css">
	#ap-notes-container .ap-status-container .fa {
		font-size:1.4em;
		cursor:pointer;
	}
	#ap-notes-container .ap-status-edit-select {
		margin:0;
	}
</style>

<div id="ap-notes-container" invno="<?php print htmlentities(trim($ap['invno']))?>">
	<h2 style="display:inline-block;">Invoice #<?php print htmlentities($ap['invno']);?></h2>

	<div class="notes-wrapper">
		<h3>Notes</h3>
		<div class="notes notes-container">
			<?php
			foreach(explode("\n", $ap['notes']) as $note_part) {
				$note_part = trim(str_replace("\r", '', $note_part));
				if(!empty($note_part)) {
					?><div class="notes-row"><?php print htmlentities($note_part);?></div><?php
				}
			}
			?>
		</div>
		<div class="notes-new-container input-append input-block-level">
			<input type="text" class="notes-new-input" />
			<button type="button" class="notes-new-button btn">Add</button>
		</div>
	</div>

	<div class="reference-wrapper">
		<h3>Reference</h3>
		<div class="notes-new-container input-append input-block-level">
			<textarea name="reference"><?php print htmlentities(trim($ap['udref']));?></textarea><button type="button" class="btn">Save</button>
		</div>
	</div>
</div>

<script type="text/javascript">
	/**
	 * Bind to `Add` button clicks and `Enter/Return` key-presses for Order
	 * Notes.
	 */
	var $ap_notes_container = $('#ap-notes-container');
	var new_ap_notes_callback = function(event) {
		if(event.type === 'keydown' && event.keyCode !== 13) {
			return;
		}

		var $ap_notes_wrapper = $(event.target).closest('.notes-wrapper');

		var $ap_notes_container = $ap_notes_wrapper.find('.notes-container');

		var $new_ap_notes_container = $ap_notes_wrapper.find('.notes-new-container');
		var $new_ap_input = $new_ap_notes_container.find('.notes-new-input');
		var ap_note = $new_ap_input.val();

		// If the input is empty, there is nothing to append.
		if(ap_note === undefined || !ap_note.length) {
			return;
		}

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/ap/notes/append',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'invno': <?php print json_encode($_POST['invno']);?>,
				'note': ap_note
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$ap_notes_container.append(
						$('<div class="notes-row">').text(data.note)
					);
					$new_ap_input.val('');
				} else {
					alert(data.message);
				}
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	};
	$(document).off('click', '#ap-notes-container .notes-new-button');
	$(document).on('click', '#ap-notes-container .notes-new-button', new_ap_notes_callback);
	$(document).off('keydown', '#ap-notes-container .notes-new-input');
	$(document).on('keydown', '#ap-notes-container .notes-new-input', new_ap_notes_callback);

	/**
	* Bind to clicks on Reference's Save button.
	 */
	$(document).off('click', '#ap-notes-container .reference-wrapper button');
	$(document).on('click', '#ap-notes-container .reference-wrapper button', function(event) {
		var $button = $(this);
		var $reference_container = $button.closest('.reference-wrapper');
		var $reference = $reference_container.find(':input[name="reference"]');

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/ap/reference/update',
			'data': {
				'invno': <?php print json_encode($_POST['invno']);?>,
				'udref': $reference.val()
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));
