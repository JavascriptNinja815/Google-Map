<?php

// Make sure the user has logged in.
$session->ensureLogin();

// Header vars.
$args = array(
	'title' => 'Rocks',
	'breadcrumbs' => array(
		'Rocks' => BASE_URI . '/dashboard/account/rocks'
	)
);

function days_until($date){

	// Get the number of days until the due date.
	$now = time();
	$dif = strtotime($date) - $now;

	$days = ceil($dif / (60 * 60 * 24));

	if ($days<=0){
		return;
	};

	return '('.$days.')';

}

if(isset($_POST['action'])){

	// Handle new rocks
	if($_POST['action'] == 'create'){

		// Get the user information.
		$user = $session->login;
		$company_id = $user['company_id'];
		$login_id = $user['login_id'];

		// Get POST data.
		$name = $_POST['name'];
		$date = $_POST['date'];
		$description = $_POST['description'];

		// Insert a new rock for the employee/company.
		$db = DB::get();
		$db->query("
			INSERT INTO Neuron.dbo.employee_rocks (
				login_id,
				company_id,
				name,
				description,
				due_on,
				status
			) SELECT
				" . $db->quote($login_id) . ",
				" . $db->quote($company_id) . ",
				" . $db->quote($name) . ",
				" . $db->quote($description) . ",
				" . $db->quote($date) . ",
				1
		");

		echo json_encode(array(
			'success' => true
		));
	}

	// Handle removing rocks.
	elseif ($_POST['action'] == 'remove') {

		// Get user and company
		$user = $session->login;
		$company_id = $user['company_id'];
		$login_id = $user['login_id'];

		$db = DB::get();
		$db->query("
			UPDATE Neuron.dbo.employee_rocks
			SET status = 0
			WHERE login_id = ".$db->quote($login_id)."
				AND company_id = ".$db->quote($company_id)."
				AND rock_id = ".$db->quote($_POST['rock_id'])."
		");

		echo json_encode(array(
			'success' => true
		));

	}

	// Handle updating rocks.
	elseif ($_POST['action'] == 'save'){

		// Get user and company
		$user = $session->login;
		$company_id = $user['company_id'];
		$login_id = $user['login_id'];

		// Get the rock ID and progress.
		$rock_id = $_POST['rock_id'];
		$progress = $_POST['progress'];

		// Get the completed-on value.
		if($progress=='100'){
			$completed_on = 'CONVERT([date],getdate(),0)';
		}else{
			$completed_on = 'NULL';
		};

		// Update the progress.
		$db = DB::get();
		$db->query("
			-- Historical value.
			INSERT INTO Neuron.dbo.employee_rocks_progress (
				rock_id, percent_complete
			)
			SELECT ".$db->quote($rock_id).",
				".$db->quote($progress)."
			WHERE NOT EXISTS (
				SELECT 1
				FROM Neuron.dbo.employee_rocks_progress
				WHERE rock_id = ".$db->quote($rock_id)."
					AND percent_complete = ".$db->quote($progress)."
			);
			-- Keep current value.
			UPDATE Neuron.dbo.employee_rocks
			SET percent_complete = ".$db->quote($progress).",
				completed_on = ".$completed_on."
			WHERE rock_id = ".$db->quote($rock_id)."
		");

		echo json_encode(array(
			'success' => true
		));
	}

	return;

};

function get_rocks(){

	// Get Employee rocks.
	global $session;
	$user = $session->login;
	$company_id = $user['company_id'];
	$login_id = $user['login_id'];

	$db = DB::get();
	return $db->query("
		SELECT
			r.rock_id,
			l.first_name,
			l.last_name,
			name,
			description,
			due_on,
			COALESCE(r.percent_complete, 0) AS percent_complete
		FROM Neuron.dbo.employee_rocks r
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = r.login_id
		WHERE company_id = ".$db->quote($company_id)."
			AND r.login_id = ".$db->quote($login_id)."
			AND r.status = 1 -- Constrain to enabled rocks
	");

};

// Render the header.
Template::Render('header', $args, 'account');

// Get existing Rocks.
$rocks = get_rocks();
?>

<style type="text/css">

	#description {
		width: 400px;
		height: 150px;
	}

	.percent-input {
		width:42px;
	}

</style>

<script type="text/javascript">
$(document).ready(function(){

	// Handle AJAX.
	function do_ajax(data, callback){

		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){
			callback(rsp)
		})

	}

	// Activate a new tab.
	function activate_tab(tab, div){

		// JQuery makes things easy.
		var $tab = $(tab)
		var $div = $(div)

		// Remove active status from other tabs.
		var $tabs = $("li.nav-item")
		$tabs.removeClass('active')

		// Add active status to the selected tab.
		$tab.addClass('active')

		// Hide all tab targets.
		var $targets = $(".tab-target")
		$targets.hide()

		// Show the selected tab target.
		$div.show()

	}

	// Support tab switching.
	function switch_tabs(){

		// Get the selected tab.
		var $tab = $(this)

		// Get the ID of the target tab.
		var target_id = $tab.attr('data-target')

		// Get the proper div
		var $div = $('#'+target_id)

		// Switch to the selected tab.
		activate_tab($tab, $div)

	}

	// Create a new rock.
	function create_new_rock(){
		
		// Get form values.
		var name = $('#rock-name').val()
		var date = $('#due-date').val()
		var desc = $('#description').val()

		// The data to POST.
		var data = {
			'action' : 'create',
			'name' : name,
			'date' : date,
			'description' : desc
		}

		// POST the data.
		do_ajax(data, function(rsp){
			window.location.reload()
		})

	}

	// Support removing rocks.
	function remove_rock(){
		
		// Get the tr and the rock ID.
		var $tr = $(this).parents('tr')
		var rock_id = $tr.attr('data-rock-id')

		// The data to POST.
		var data = {
			'action' : 'remove',
			'rock_id' : rock_id
		}

		// POST the data.
		do_ajax(data, function(){
			
			// Remove the tr.
			$tr.remove()

		})

	}

	// Support saving rocks.
	function save_rock(){

		// Get rock ID and progress.
		var $tr = $(this).parents('tr')
		var rock_id = $tr.attr('data-rock-id')
		var $progress = $tr.find('.percent-input')
		var progress = $progress.val()

		// The data to POST.
		var data = {
			'action' : 'save',
			'rock_id' : rock_id,
			'progress' : progress
		}

		// POST the data.
		do_ajax(data, function(rsp){
			console.log(rsp)
		})

	}

	// Enable tab switching.
	$(document).on('click', '.nav-item', switch_tabs)

	// Enable creating new rocks.
	$(document).on('click', '#new-rock-submit', create_new_rock)

	// Enable removing rocks.
	$(document).on('click', '.remove-rock', remove_rock)

	// Enable saving rock progress.
	$(document).on('click', '.save-rock', save_rock)

})
</script>

<div class="container padded col-xs-12 pull-left pad-left">
	
<h2>Employee Rocks</h2>

<ul class="nav nav-tabs">
	<li class="nav-item active" data-target="existing-tab">
		<a class="nav-link" href="#">Existing</a>
	</li>
	<li class="nav-item" data-target="new-tab">
		<a class="nav-link" href="#">New</a>
	</li>
</ul>

<div id="existing-tab" class="tab-target">
	
	<table id="existing-rocks-table" class="table table-striped table-hover">
		<thead>
			<th class="span1"></th>
			<th class="span1"></th>
			<th>Who</th>
			<th class="span3">Rock Name</th>
			<th>Rock Desc</th>
			<th class="span1">% Done</th>
			<th class="span2">Due Date</th>
		</thead>
		<tbody>
			<?php
				foreach($rocks as $rock){
					?>
					<tr data-rock-id="<?php print htmlentities($rock['rock_id']) ?>">
						<td class="span1 remove-rock"><i class="fa fa-fw fa-times"></i></td>
						<td class="span1 save-rock"><i class="fa fa-fw fa-floppy-o"></i></td>
						<td><?php print htmlentities($rock['first_name']) ?></td>
						<td class="span3"><?php print htmlentities($rock['name']) ?></td>
						<td><?php print htmlentities($rock['description']) ?></td>
						<td class="span1">
							<input class="percent-input" type="number" min="0" max="100" value="<?php print htmlentities($rock['percent_complete']) ?>"/>
						</td>
						<td class="span2"><?php print htmlentities($rock['due_on']." ".days_until($rock['due_on'])) ?></td>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>

</div>
<div id="new-tab" class="tab-target" style="display:none;">
	<form>
		<div class="form-group">
			<label for="rock-name">Rock Name</label>
			<input type="text" class="form-control" id="rock-name" placeholder="Rock Name">
		</div>
		<div class="form-group">
			<label for="due-date">Due Date</label>
			<input type="date" class="form-control" id="due-date" value="<?php print date('Y-m-d') ?>">
		</div>
		<div class="form-group">
			<label for="description">Rock Description</label>
			<textarea id="description" placeholder="Rock Description"></textarea>
		</div>
	</form>
	<button id="new-rock-submit" class="btn btn-primary">Create</button>
</div>

</div>