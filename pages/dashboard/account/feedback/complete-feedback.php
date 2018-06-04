<?php

ob_start();

?>

<form id="complete-feedback-form" class="form-horizontal" method="post" action="<?php print BASE_URI; ?>/dashboard/account/feedback/submit">
	
	<h2>Complete Feedback</h2>

	<input type="hidden" name="action" value="complete">
	<input type="hidden" name="feedback_id" value="<?php print htmlentities($_POST['feedback_id']) ?>">

	<div class="control-group">
		
		<label class="control-label" for="resolution">Resolution</label>
		<div class="controls">
			<textarea id="resolution" name="resolution" placeholder="Enter Resolution" required></textarea>
		</div>

	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">Submit</button>
		</div>
	</div>

</form>

<script type="text/javascript">
	
	$(document).off('submit', '#complete-feedback-form')
	$(document).on('submit', '#complete-feedback-form', function(e){

		e.preventDefault()

		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		var $overlayz_body = $form.closest('.overlayz-body');
		var $overlayz = $overlayz_body.closest('.overlayz');

		$.ajax({
			'url': $form.attr('action'),
			'method': $form.attr('method'),
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(rsp) {
				var msg = '<div style="padding:24px;">Feedback successfully completed.</div>'
				$overlayz_body.html(msg)
			}
		})
		return false;

	})

</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);

?>