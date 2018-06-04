<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_client = $db->query("
	SELECT
		arcust.custno,
		arcust.oob,
		arcust.oob_notes
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		arcust.custno = " . $db->quote($_POST['custno']) . "
");
$client = $grab_client->fetch();

if(empty($client)) {
	print 'Client does not exist';
	exit;
}

?>
<form id="client-logcall" class="form-horizontal" method="POST" action="<?php print BASE_URI;?>/dashboard/clients/log-call/log">
	<input type="hidden" name="custno" value="<?php print htmlentities($_POST['custno'], ENT_QUOTES);?>" />
	<h1>Log Call - <?php print htmlentities($client['custno']);?></h1>
	<div class="control-group">
		<label class="control-label" for="client-logcall-memo">Call Memo</label>
		<div class="controls">
			<textarea name="memo" id="client-logcall-memo"></textarea>
		</div>
	</div>
	<br />
	<div class="control-group">
		<label class="control-label" for="client-logcall-oob">OOB</label>
		<div class="controls">
			<label><input type="checkbox" name="oob" value="1" id="client-logcall-oob" <?php print $client['oob'] == 1 ? 'checked' : Null;?>> Out Of Business</label>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="client-logcall-oob-memo">OOB Memo</label>
		<div class="controls">
			<textarea name="oob-memo" id="client-logcall-oob-memo"><?php print htmlentities($client['oob_notes']);?></textarea>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">Submit</button>
		</div>
	</div>
</form>

<script type="text/javascript">
	/**
	 * Bind to form submissions.
	 */
	$(document).off('submit', '#client-logcall');
	$(document).on('submit', '#client-logcall', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		// Display loading overlay.
		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');
		
		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': $form.attr('method'),
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			},
			'error': function() {
				alert('Something didn\'t go right');
			},
			'success': function(response) {
				if(!response.success) {
					if('message' in response) {
						alert(response.message);
					}
					if('errors' in response) {
						$.each(response.errors, function(offset, error) {
							alert('Error Code ' + error.code + ': ' + error.message);
						});
					}
					return;
				}
				
				// Success, close the window.
				var $overlay = $form.closest('.overlayz');
				$overlay.fadeOut('fast', function() {
					$overlay.remove();
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
