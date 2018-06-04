<?php

$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

function get_tags(){

	// Query for the supported feedback tags.

	$db = DB::get();
	$q = $db->query("
		SELECT tag_id, tag
		FROM Neuron.dbo.feedback_tags
		ORDER BY tag
	");

	return $q->fetchAll();

}

function get_types(){

	// Query for the supported feedback types.

	$db = DB::get();
	$q = $db->query("
		SELECT type_id, type
		FROM Neuron.dbo.feedback_types
		ORDER BY type
	");

	return $q->fetchAll();

}

// Get the tag options.
$tags = get_tags();

// Get the type options.
$types = get_types();

?>

<!-- Include the bootstrap JavaScript -->
<script type="text/javascript" src="/interface/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/interface/js/bootstrap-dropdown-checkbox.min.js"></script>

<!-- Include the CSS necessary for the dropdowns -->
<link rel="stylesheet" type="text/css" href="/interface/css/bootstrap-dropdown-checkbox.css">

<style type="text/css">
	.dropdown-checkbox-menu li {
		width: 150px;
	}
</style>

<form class="form-horizontal" id="feedback-form" method="post" action="<?php print BASE_URI;?>/dashboard/feedback/submit">
	<h2>Submit Feedback</h2>
	<div class="control-group">
		<label class="control-label" for="feedback-topic">Topic</label>
		<div class="controls">
			<select name="topic" id="feedback-topic" required>
				<option value="">-- Select Topic --</option>
				<option value="General">General</option>
				<option value="Maven">Maven</option>
				<option value="Customer Service">Customer Service</option>
				<option value="Production / Warehouse">Production / Warehouse</option>
				<option value="Materials">Materials</option>
				<option value="Marketing">Marketing</option>
				<option value="Sales">Sales</option>
				<option value="Corporate Operation">Corporate Operation</option>
				<option value="Other">Other</option>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="feedback-type">Type</label>
		<div class="controls">
			<select id="feedback-type" name="type">
				<option value="">-- Select Type --</option>
				<?php
				foreach($types as $type){
				?>
					<option value="<?php print htmlentities($type['type_id']) ?>"><?php print htmlentities($type['type']) ?></option>
				<?php
				}
				?>
			</select>
		</div>
	</div>

	<!-- TODO: Uncomment this once tags are fully supported -->
	<!--
	<div class="control-group">
		<label class="control-label">Tags</label>
		<div class="controls">
			<div id="tag-dropdown"></div>
		</div>
	</div>
	-->

	<div class="control-group">
		<label class="control-label" for="feedback-subject">Subject</label>
		<div class="controls">
			<input type="text" class="span4" name="subject" maxlength="255" id="feedback-subject" required />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="feedback-memo">Memo</label>
		<div class="controls">
			<textarea name="memo" id="feedback-memo" required></textarea>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">Submit</button>
		</div>
	</div>
</form>

<script type="text/javascript">

	// Create the tag dropdown.
	function dropdown(){

		// Get the tag data from PHP.
		var data = []
		<?php
			foreach($tags as $tag){
			?>
				data.push({
					'id' : '<?php echo $tag["tag_id"] ?>',
					'label' : '<?php echo $tag["tag"] ?>',
					'value' : '<?php echo $tag["tag_id"] ?>'
				})
			<?php
			}
		?>

		// Create a non-fugly button template.
		var button = '<button class="dropdown-checkbox-toggle btn" data-toggle="dropdown-checkbox">Test <b class="caret"></b></button'

		// Get and set the dropdown and its values.
		$("#tag-dropdown").dropdownCheckbox({
			data : data,
			title: 'Tags',
			btnClass : 'btn',
			templateButton : button,
			showNbSelected : true
		})

	}

	// TODO: Uncomment this when tags should be fully supported.
	// Enable the tag dropdown.
	//dropdown()

	$(document).off('submit', '#feedback-form');
	$(document).on('submit', '#feedback-form', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		// TODO: Uncomment this when tags should be supported
		// // Get the tag IDs.
		// var $checked = $('#tag-dropdown').dropdownCheckbox('checked')
		// var $tag_ids = [];
		// $.each($checked, function(idx, input){
		// 	$tag_ids.push(input.value)
		// })

		// // Make sure tag IDs are submitted.
		// data.append('tag_ids', $tag_ids)

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
			'success': function(response) {
				$overlayz_body.html('<div style="padding:24px;">Thank you for providing us with your feedback. We take your submission very seriously, reading each and every one. When necessary, it will also be forwarded onto the proper department and individual.</div>')
			}
		})
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
