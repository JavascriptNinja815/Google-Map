<?php

ob_start();

function get_users(){

	// Get a list of users that can be assigned to a feedback.

	$db = DB::get();
	$q = $db->query("
		SELECT
			l.login_id,
			first_name + ' ' + last_name AS name
		FROM Neuron.dbo.logins l
		INNER JOIN Neuron.dbo.login_companies c
			ON c.login_id = l.login_id
		WHERE c.company_id = ".COMPANY."
			AND status = 1
		ORDER BY last_name, first_name
	");

	return $q->fetchAll();

}

function get_assigned(){

	// Query for the current assigned values.

	$db = DB::get();
	$q = $db->query("
		SELECT
			CASE WHEN resolution_owner IS NOT NULL
				THEN 1
				ELSE 0
			END AS success,
			resolution_owner,
			projected_completion_date
		FROM Neuron.dbo.feedback
		WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
	");

	// Structure the response.
	$results = $q->fetch();
	$response = array(
		'set' => false,
		'resolution_owner' => null,
		'projected_completion_date' => null
	);

	if($results['success']){
		$response['set'] = true;
		$response['resolution_owner'] = $results['resolution_owner'];
		$response['projected_completion_date'] = $results['projected_completion_date'];
	}

	return $response;

}

function get_is_admin(){


	// Return a boolean indicating whether the logged-in user can view/operate
	// admin-level icons/actions.

	global $session;
	if($session->hasRole('Administration') || $session->hasRole('Supervisor')){
		return true;
	};

	return false;

}

// Check to see if the user is admin-level.
$is_admin = get_is_admin();

// Get the users.
$users = get_users();

// Get the current assignment values.
$assigned = get_assigned();

?>

<style type="text/css">
	#feedback-user {
		width: 300px;
	}
	#feedback-resolution {
		width: 300px;
		height: 150%;
	}
</style>

<form class="form-horizontal" id="assign-feedback-form" method="post" action="<?php print BASE_URI;?>/dashboard/account/feedback/submit">
	<h2>Assign Feedback</h2>

	<input type="hidden" name="action", value="assign">
	<input type="hidden" name="feedback_id" value="<?php print htmlentities($_POST['feedback_id']) ?>">

	<div class="control-group">
		<label class="control-label" for="feedback-user">Resolution Owner</label>
		<div class="controls">
			<select name="user" id="feedback-user" required>
				<option value="">-- Select User --</option>
				<?php
				foreach($users as $user){

					// Check if option should be selected.
					$selected = "";
					if($assigned['set']){

						// Get selected user.
						if($user['login_id']==$assigned['resolution_owner']){
							$selected="selected";
						};
					};

				?>
					<option <?php print htmlentities($selected) ?> value="<?php print htmlentities($user['login_id']) ?>"><?php print htmlentities($user['name']) ?></option>
				<?php
				}
				?>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="feedback-resolution-date">Resolution Date</label>
		<div class="controls">

			<?php

			// Get date.
			$d = date('Y-m-d');
			if($assigned['set']){
				$d = $assigned['projected_completion_date'];
			};

			?>

			<input type="date" name="resolution-date" class="span4" id="feedback-resolution-date" value="<?php print $d?>" requried>
		</div>
	</div>

	<?php
		if($is_admin){
			?>
				<div class="control-group">
					<div class="controls">
						<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</div>
			<?php
		}
	?>



</form>

<script type="text/javascript">

	$(document).off('submit', '#assign-feedback-form');
	$(document).on('submit', '#assign-feedback-form', function(e) {

		e.preventDefault();

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
				var msg = '<div style="padding:24px;">Feedback successfully assigned.</div>'
				$overlayz_body.html(msg)
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

?>