<?php

$session->ensureLogin();
$session->ensureRole('Supervisor');

$args = array(
	'title' => 'Feedback',
	'breadcrumbs' => array(
		'My Account' => BASE_URI . '/dashboard/account',
		'Switch Users' => BASE_URI . '/dashboard/account/switch-user'
	),
	'body-class' => 'padded'
);

function get_users(){

	// Query for users to switch to.
	$db = DB::get();
	$q = $db->query("
		SELECT
			login_id,
			last_name + ', ' +
			first_name AS name
		FROM Neuron.dbo.logins
		WHERE status = 1
		ORDER BY last_name
	");

	return $q->fetchAll();

}

// Get users.
$users = get_users();

// Handle AJAX.
if(isset($_POST['action'])){

	// Handle alias sessions.
	if($_POST['action']=='assume-alias'){

		// Create an alias session.
		Session::loginAlias($_POST['alias-id']);

		print json_encode(array(
			'success' => true,
			'wtf' => 'wtf??'
		));

		return;

	}

}

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#switch-user-button {
		margin-bottom: 8px;
		margin-left: 2px;
	}
</style>

<h2>Switch User</h2>
<div id="switch-user-container" class="container-fluid">
	<form id="switch-user-form">
		<fieldset>
			<legend>Select User</legend>
				<div id="user-select-container" class="row-fluid span2 pull-left">
					<select id="user-select">
						<option value="">--Select User--</option>
						<?php
						foreach($users as $user){
						?>
						<option value="<?php print htmlentities($user['login_id']) ?>"><?php print htmlentities($user['name']) ?></option>
						<?php
						}
						?>
					</select>
				<button id="switch-user-button" type="button" class="btn btn-primary">Use Alias</button>
				</div>
		</fieldset>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function assume_alias(){

			// Assume another user's login role.

			// Get the selected user's login id.
			var $select = $('#user-select')
			var alias_id = $select.val()

			// The data to POST.
			var data = {
				'action' : 'assume-alias',
				'alias-id' : alias_id
			}

			console.log("About to AJAX...")

			// Log in as another user.
			$.ajax({
				'url' : '',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){
					// Redirect to the dashboard.
					window.location.href = '/dashboard'
				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		// Enable user-switching.
		$(document).on('click', '#switch-user-button', assume_alias)

	})
</script>