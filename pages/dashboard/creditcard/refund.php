<?php

$session->ensureLogin();

$grab_transaction = $db->query("
	SELECT
		authorize_transactions.*
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transactions
	WHERE
		authorize_transactions.transaction_id = " . $db->quote($_POST['transaction_id']) . "
");
$transaction = $grab_transaction->fetch();

if(empty($transaction)) {
	print json_encode([
		'success' => False,
		'message' => 'Invalid Transaction specified'
	]);
	exit;
}

?>
<form class="form-horizontal" id="cc-refund-form" action="<?php print BASE_URI;?>/dashboard/creditcard/transaction/refund">
	<input type="hidden" name="transaction_id" value="<?php print htmlentities($transaction['transaction_id'], ENT_QUOTES);?>" />
	<input type="hidden" name="last4" value="<?php print htmlentities($transaction['last4'], ENT_QUOTES);?>" />
	<h2>Refund Transaction # <?php print htmlentities($_POST['transaction_id']);?></h2>

	<div class="control-group">
		<label class="control-label">Original Amount</label>
		<div class="controls">
			<b>$<?php print number_format($transaction['amount'], 2);?></b>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Refund Amount</label>
		<div class="controls">
			<input type="number" name="amount" step="0.01" min="1.00" value="" required />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Reference</label>
		<div class="controls">
			<input type="text" name="memo" class="span3" required />
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">Submit Refund</button>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(document).off('submit', '#cc-refund-form');
	$(document).on('submit', '#cc-refund-form', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);
		
		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(!response.success) {
					if(response.errors) {
						$.each(response.errors, function(offset, error) {
							alert(error);
						});
					} else if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return;
				}
				
				alert('Success.');
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});

		return false;
	});
</script>
