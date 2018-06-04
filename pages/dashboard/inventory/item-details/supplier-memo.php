<?php

function get_memo(){

	// Query for the memo.

	$db = DB::get();
	$q = $db->query("
		SELECT supmemo
		FROM ".DB_SCHEMA_ERP.".icsupl s
		WHERE item = ".$db->quote($_POST['item'])."
			AND vendno = ".$db->quote($_POST['vendno'])."
			AND vpartno = ".$db->quote($_POST['vpartno'])."
	");

	return $q->fetch()['supmemo'];

}

// Handle AJAX requests.
if(isset($_POST['action'])){

	// Handle memo updates.
	if($_POST['action']=='update-memo'){

		// Get POSTed values.
		$memo = $_POST['memo'];
		$item = $_POST['item'];
		$vendno = $_POST['vendno'];
		$vpartno = $_POST['vpartno'];

		// Update the memo.
		$db = DB::get();
		$db->query("
			UPDATE ".DB_SCHEMA_ERP.".icsupl
			SET supmemo = ".$db->quote($memo)."
			WHERE item = ".$db->quote($item)."
				AND vendno = ".$db->quote($vendno)."
				AND vpartno = ".$db->quote($vpartno)."
		");

		print json_encode(array(
			'success' => true
		));

		return;

	}

}

// Get the memo.
$memo = get_memo();

ob_start(); // Start loading output into buffer.

?>
<style type="text/css">
	#memo-input {
		height: 200px;
		width: 300px;
	}
</style>
<div class="container-fluid">

	<form id="supplier-memo-form" class="pull-left" method="post" action="">
		<h3>Memo:</h3>
		<input type="hidden" name="action" value="update-memo">
		<input type="hidden" name="item" value="<?php print htmlentities($_POST['item']) ?>">
		<input type="hidden" name="vendno" value="<?php print htmlentities($_POST['vendno']) ?>">
		<input type="hidden" name="vpartno" value="<?php print htmlentities($_POST['vpartno']) ?>">
		<div class="control-group">
			<label class="control-label" for="memo-input"></label>
			<div class="controls">
				<textarea id="memo-input" name="memo" required><?php print htmlentities(trim($memo)) ?></textarea>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<button id="memo-submit" type="submit" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function update_memo(e){

			// Prevent form submission.
			e.preventDefault()

			// Get the form.
			var form = this
			var $form = $(form)

			// Get the form data.
			var data = new FormData(form)

			// Get the overlay.
			var $overlayz_body = $form.closest('.overlayz-body')
			var $overlayz = $overlayz_body.closest('.overlayz')

			// Submit the form data via AJAX.
			$.ajax({
				'url' : BASE_URI+'/dashboard/inventory/item-details/supplier-memo',
				'method' : 'POST',
				'dataType' : 'JSON',
				'processData': false,
				'contentType': false,
				'enctype': 'multipart/form-data',
				'data' : data,
				'success' : function(xhr){
					console.log('success')
					console.log(xhr)
				},
				'error' : function(xhr){
					console.log('error')
					console.log(xhr)
				}
			})

		}

		// Enable memo updates.
		$(document).off('submit', '#supplier-memo-form')
		$(document).on('submit', '#supplier-memo-form', update_memo)

	})
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));