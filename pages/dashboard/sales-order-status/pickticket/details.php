<?php

$grab_printers = $db->query("
	SELECT
		printers.printer,
		printers.real_printer_name
	FROM
		" . DB_SCHEMA_INTERNAL . ".printers
	WHERE
		printers.company_id = " . $db->quote(COMPANY) . "
	ORDER BY
		printers.printer
");

ob_start(); // Start loading output into buffer.

?>
<form id="so-pickticket-container" class="form-horizontal" method="POST" action="<?php print BASE_URI;?>/dashboard/sales-order-status/pickticket/print">
	<h3>Print Picking Ticket</h3>
	
	<input type="hidden" name="sono" value="<?php print htmlentities($_POST['sono'], ENT_QUOTES);?>" />
	
	<div class="control-group">
		<label class="control-label" for="pickticket-printer">Printer</label>
		<div class="controls">
			<select id="pickticket-printer" name="printer">
				<option value="">-- Select -- </option>
				<?php
				foreach($grab_printers as $printer) {
					?><option value="<?php print htmlentities($printer['real_printer_name'], ENT_QUOTES);?>"><?php print htmlentities($printer['printer']);?></option><?php
				}
				?>
			</select>
		</div>
	</div>
	
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">Print</button>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(document).off('submit', '#so-pickticket-container');
	$(document).on('submit', '#so-pickticket-container', function(event) {

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		var form = this;
		var $form = $(form);
		var data = new FormData(form);
		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'beforeSend': function() {
				$form.find('').slideUp('fast', function() {
					$(this).remove();
				});
			},
			'success': function(response) {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
				if(!response.success) {
					if(response.message) {
						alert(message);
					} else {
						alert("Something didn't go right...");
					}
					return;
				}

				var $overlayz_body = $form.closest('.overlayz-body');
				$overlayz_body.empty().append(
					$('<h2>').text('Success!')
				);
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
