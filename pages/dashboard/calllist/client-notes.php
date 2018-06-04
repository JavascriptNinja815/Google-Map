<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'success' => true
);

if(isset($_POST['save'])) {
	$date = new DateTime();
	$new_note = $_POST['note'] . ' by ' . $session->login['initials']  . ' on ' . $date->format('m/d/y h:m:i A');
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".armast
		SET
			arnotes = arnotes + '
' + " . $db->quote($new_note) . "
		WHERE
			RTRIM(LTRIM(armast.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	");
} else {
	$grab_notes = $db->query("
		SELECT
			armast.arnotes AS notes
		FROM
			" . DB_SCHEMA_ERP . ".armast
		WHERE
			RTRIM(LTRIM(armast.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	");
	$notes = $grab_notes->fetch();
	$notes = explode("\n", $notes['notes']);

	$html = '<div id="client-notes-overlay">';
	$html = '<h2>Client Notes</h2>';
	$html .= '<div class="notes order-notes-container" custno="' . htmlentities($_POST['custno'], ENT_QUOTES) . '">';
	foreach($notes as $note) {
		$html .= '<div class="note order-notes-row">' . htmlentities($note) . '</div>';
	}
	$html .= '</div>';
	$html .= '<div class="order-notes-new-container"><input type="text" class="notes client-notes order-notes-new-input" name="client-notes" /><button type="button" class="add-note">Add</button></div>';
	$html .= '</div>';

	$response['html'] = $html;
}

$response['html'] .= "
	<script type=\"text/javascript\">
		$(document).off('click', '.overlayz #client-notes-overlay button.add-note');
		$(document).on('click', '.overlayz #client-notes-overlay button.add-note', function(event) {
			var \$button = $(this);
			var \$overlay_body = \$button.closest('.overlayz-body');
			var \$input = \$overlay_body.find('input.notes');
			var note_type = \$input.attr('name');
			var note = \$input.val();

			if(note.length) {
				var \$notes_container = \$overlay_body.find('.notes');
				var \$note = $('<div class=\"note order-notes-row\">');
				\$note.text(note).appendTo(\$notes_container);
				\$input.val('');

				var data = {
					'save': true,
					'custno': \$notes_container.attr('custno'),
					'note': note
				};
				if(note_type === 'client-notes') {
					var url = BASE_URI + '/dashboard/calllist/client-notes';
				} else if(note_type === 'invoice-notes') {
					var url = BASE_URI + '/dashboard/calllist/invoice-notes';
					data['invno'] = \$notes_container.attr('invno')
				}

				$.ajax({
					'url': url,
					'type': 'POST',
					'dataType': 'json',
					'data': data,
					'beforeSend': function() {
						\$layz.fadeIn();
					},
					'success': function(data, status, jqXHR) { },
					'complete': function() {
						\$layz.fadeOut();
					}
				});
			}
		});
	</script>
";

print json_encode($response);
