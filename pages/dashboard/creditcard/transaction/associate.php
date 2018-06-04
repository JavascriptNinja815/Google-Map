<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

$grab_invoices = $db->query("
	SELECT
		authorize_transaction_relations.relation_type_id AS invno,
		authorize_transaction_relations.amount
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transaction_relations
	WHERE
		authorize_transaction_relations.transaction_id = " . $db->quote($_POST['transaction_id']) . "
		AND
		authorize_transaction_relations.relation_type = 'invno'
	ORDER BY
		authorize_transaction_relations.relation_type_id
");
$invoices = [];
foreach($grab_invoices as $invoice) {
	$invoices[] = $invoice;
}

$grab_sos = $db->query("
	SELECT
		authorize_transaction_relations.relation_type_id AS sono,
		authorize_transaction_relations.amount
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transaction_relations
	WHERE
		authorize_transaction_relations.transaction_id = " . $db->quote($_POST['transaction_id']) . "
		AND
		authorize_transaction_relations.relation_type = 'sono'
	ORDER BY
		authorize_transaction_relations.relation_type_id
");
$sos = [];
foreach($grab_sos as $so) {
	$sos[] = $so;
}

?>

<style type="text/css">
	#cc-associate-container .action-add {
		font-size:2em;
		color:#090;
	}
	#cc-associate-container .action-remove {
		font-size:2em;
		color:#900;
	}
	#cc-associate-container td {
		vertical-align:top;
	}
</style>

<form id="cc-associate-container" action="<?php print BASE_URI;?>/dashboard/creditcard/transaction/associate/save">
	<h2>Transaction ID: <?php print htmlentities($_POST['transaction_id']);?></h3>

	<input type="hidden" name="transaction_id" value="<?php print htmlentities($_POST['transaction_id'], ENT_QUOTES);?>" />
	<table>
		<thead>
			<tr>
				<th width="50%"><h3>Invoices</h3></th>
				<th width="50%"><h3>SOs</h3></th>
			</tr>
			<tr>
				<th><i class="fa fa-plus action-add add-invno"></i></th>
				<th><i class="fa fa-plus action-add add-sono"></i></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="invnos">
					<?php
					if(!empty($invoices)) {
						foreach($invoices as $invoice) {
							?>
							<div class="invno">
								<i class="fa fa-minus action-remove remove-invno"></i>
								<input type="text" name="invnos[]" value="<?php print htmlentities($invoice['invno'], ENT_QUOTES);?>" placeholder="invno" />
								<input type="text" name="invnos_amounts[]" value="<?php print htmlentities($invoice['amount'], ENT_QUOTES);?>" placeholder="amount" />
							</div>
							<?php
						}
					} else {
						?>
						<div class="invno">
							<i class="fa fa-minus action-remove remove-invno"></i>
							<input type="text" name="invnos[]" placeholder="invno" />
							<input type="text" name="invnos_amounts[]" placeholder="amount" />
						</div>
						<?php
					}
					?>
				</td>
				<td class="sonos">
					<?php
					if(!empty($sos)) {
						foreach($sos as $so) {
							?>
							<div class="sono">
								<i class="fa fa-minus action-remove remove-sono"></i>
								<input type="text" name="sonos[]" value="<?php print htmlentities($so['sono'], ENT_QUOTES);?>" placeholdere="sono" />
								<input type="text" name="sonos_amounts[]" value="<?php print htmlentities($so['amount'], ENT_QUOTES);?>" placeholder="amount" />
							</div>
							<?php
						}
					} else {
						?>
						<div class="sono">
							<i class="fa fa-minus action-remove remove-sono"></i>
							<input type="text" name="sonos[]" placeholder="sono" />
							<input type="text" name="sonos_amounts[]" placeholder="amount" />
						</div>
						<?php
					}
					?>
				<td>
			</tr>
		</tbody>
	</table>
	<button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<script type="text/javascript">
	/**
	 * Bind to clicks on "Add" icon for SOs and Invoices.
	 */
	$(document).off('click', '#cc-associate-container .action-add');
	$(document).on('click', '#cc-associate-container .action-add', function(event) {
		var $icon = $(this);
		var $form = $icon.closest('form');
		if($icon.hasClass('add-invno')) {
			var subject = 'invno';
		} else if($icon.hasClass('add-sono')) {
			var subject = 'sono';
		}
		var $container = $form.find('.' + subject + 's');
		var $row = $('<div>').addClass(subject).append(
			$('<i class="fa fa-minus action-remove">').addClass('remove-' + subject),
			' ',
			$('<input type="text" name="' + subject + 's[]">').attr('placeholder', subject),
			' ',
			$('<input type="text" name="' + subject + 's_amounts[]" placeholder="amount">')
		);
		$row.appendTo($container);
	});

	/**
	 * Bind to clicks on "Remove" icon for SOs and Invoices.
	 */
	$(document).off('click', '#cc-associate-container .action-remove');
	$(document).on('click', '#cc-associate-container .action-remove', function(event) {
		var $icon = $(this);
		var $row = $icon.closest('div');
		$row.remove();
	});

	/**
	 * Bind to form submissions.
	 */
	$(document).off('submit', '#cc-associate-container');
	$(document).on('submit', '#cc-associate-container', function(event) {
		var form = this;
		var $form = $(this);
		var data = new FormData(form);
		var $transaction_id = $form.find('input[name="transaction_id"]');
		var transaction_id = $transaction_id.val();
	
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
			'error': function() {
				alert('Something didnt go right?');
			},
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

				var $overlay = $form.closest('.overlayz');
				$overlay.fadeOut('fast', function() {
					$overlay.remove();
				});

				var $invnos = $('#cc-search-container .transaction[transaction_id="' + transaction_id + '"] .invno .invnos-container').empty();
				$.each(response.invnos, function(invno, amount) {
					$invnos.append(
						$('<span class="invno">').text(invno)
					);
				});
				
				var $sonos = $('#cc-search-container .transaction[transaction_id="' + transaction_id + '"] .sono .sonos-container').empty();
				$.each(response.sonos, function(sono, amount) {
					$sonos.append(
						$('<a class="sono overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details">').attr('overlayz-data', JSON.stringify({"so-number": sono})).text(sono)
					);
				});
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

<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
