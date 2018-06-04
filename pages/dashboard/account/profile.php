<?php

$session->ensureLogin();

$args = array(
	'title' => 'Team Profile',
	'breadcrumbs' => array(
		'Profile' => BASE_URI . '/dashboard/account/profile'
	),
	'body-class' => 'padded'
);

function get_profile($login_id){

	// Get the logged in user's profile.

	// TODO: Remove this.
	// $login_id = 136;

	$db = DB::get();
	$q = $db->query("
		DECLARE @today DATE
		SET @today = GETDATE()

		SELECT
			l.first_name,
			l.last_name,
			l.login AS email,
			p.nickname,
			COALESCE(p.address1, '') +' '+ COALESCE(p.address2, '') AS street,
			p.city,
			p.state,
			p.zip,
			COALESCE(l.direct_line, '-') AS phone,
			COALESCE(l.cell_phone, '-') AS cell,
			l.location_id,
			l.office,

			-- Get a two-digit MM-DD birthday.
			RIGHT('00'+CAST(DATEPART(MONTH, birthday) AS VARCHAR(2)),2)
			 + '-' +
			RIGHT('00'+CAST(DATEPART(DAY, birthday) AS VARCHAR(2)),2)
			 + '-'
			AS birthday,

			-- How long has the employee been with the company?
			hire_date,
			COALESCE(
				CAST(ROUND(
					CAST(DATEDIFF(DAY, hire_date, @today) AS numeric)/365.0,
				2)AS numeric(20,2)), 0
			) AS years_with_company,

			e.name AS emergency_name,
			e.phone AS emergency_phone,
			l.avail_vacation_hours AS vacation_available,
			l.avail_sick_hours AS sick_available,
			photos.profile_photo_id
		FROM Neuron.dbo.logins l
		INNER JOIN Neuron.profiles.profile p
			ON p.login_id = l.login_id
		LEFT JOIN Neuron.profiles.emergency_contacts e
			ON e.profile_id = p.profile_id
		LEFT JOIN Neuron.profiles.profile_photos photos
			ON photos.profile_id = p.profile_id
		WHERE l.login_id = ".$db->quote($login_id)."
	");

	// Get the profile details.
	$profile = $q->fetch();

	// Get time-off details.
	$time_off = get_time_off($login_id);

	// Combine the data sets.
	$profile = array_merge($profile, $time_off);

	return $profile;

}

function get_time_off($login_id){

	// Query for the amount of time someone has used as sick/vaction time.

	$db = DB::get();
	$q = $db->query("
		-- Declare date variables.
		DECLARE @today date;
		DECLARE @janfirst date;

		-- Set date variables.
		SET @today = GETDATE();
		SET @janfirst = DATEADD(YYYY, DATEDIFF(YYYY, 0, @today),0);

		-- Begin query.
		SELECT
			COALESCE(SUM(
				CASE WHEN timesheet_type_id IN (3, 5)
					THEN COALESCE(DATEDIFF(hour, from_datetime, to_datetime), 8)
				ELSE 0
			END),0) AS sick,
			COALESCE(SUM(
				CASE WHEN timesheet_type_id = 4
					THEN COALESCE(DATEDIFF(hour, from_datetime, to_datetime), 8)
				ELSE 0
			END
			),0) AS vacation
		FROM ".DB_SCHEMA_ERP.".timesheets
		WHERE from_datetime >= @janfirst
			AND login_id = ".$db->quote($login_id)."
	");

	$d = $q->fetch();
	return array(
		'vacation_used' => $d['vacation'],
		'sick_used' => $d['sick']
	);

}

function get_login_id(){

	if(isset($_GET['login-id'])){
		return $_GET['login-id'];
	}

	global $session;
	$login = $session->login;
	$login_id = $login['login_id'];

	return $login_id;

}

// Get the login ID.
$login_id = get_login_id();

// Get the profile data.
$profile = get_profile($login_id);

Template::Render('header', $args, 'account');
?>

<style type="text/css">

	#body {
		background-color: #e6e7eb;
	}

	@font-face {
		font-family: 'noto';
		src: url('http://dev.maven.local/interface/fonts/NotoSans-Regular.ttf');
	}
	.noto {
		font-family: noto;
	}

	#profile-container {
		height: 99%;
		width: 100%;
		//border: 1px solid green;
	}
	#about-row {
		height: 250px;
		//border: 1px solid blue;
	}
	#profile-photo-container {
		margin-left: 10px;
		//border: 1px solid blue;
		height: 100%;
		//overflow: hidden;
	}
	#profile-photo {
		//border-radius: 55px;
		max-height: 164%;
		//max-width: 50%;
		margin: -39px 0 0 0px;
		display:block;
	}

	.image-container {
		//border: 1px solid green;
		border-radius: 150px;
		width: 100%;
		height: 100%;
		overflow: hidden;
	}

	#about-container {
		height: 100%;
		margin-left: 34px;
		//border: 1px solid orange;
	}
	#nickname-header {
		margin-top: 0px !important;
	}
	#nav-tab-container {
		padding-top: 101px;
	}
	#body-row {
		height: 500px;
		//border: 1px solid pink;
	}
	#contact-container {
		//border: 1px solid purple;
		height: 100%;
	}
	#tabs-container {
		height: 100%;
		//border: 1px solid black;
	}
	.contact {
		font-size: 16px;
		color: gray;
	}
	.contact i {
		padding-right: 5px;
	}

	.phone {
		color: gray;
	}
	.contact-header {
		padding-top: 10px;
	}
	#tabs-container {
		background-color: #fdfeff;
	}
	.time-off-container{
		////border: 1px solid yellow;
	}
	.text-center {
		text-align: center;
	}
	.time-off-label {
		padding-top: 5px;
	}

</style>

<div id="profile-container" class="container">
	<div id="about-row" class="row-fluid pull-left">

		<?php
			// Get the profile photo ID.
			$photo_id = htmlentities($profile['profile_photo_id']);
		?>

		<div id="profile-photo-container" class="container-fluid span2" data-photo-id="<?php print $photo_id ?>"></div>
		<div id="about-container" class="container-fluid span9">
			<div id="name-container" class="row-fluid pull-left noto">

				<?php
					// Get the user's name.
					$name = $profile['first_name'].' '.$profile['last_name'];
				?>

				<h2><?php print htmlentities($name) ?></h2>
			</div>
			<div id="nickname-container" class="row-fluid pull-left noto">
				<h4 id="nickname-header">"<?php print htmlentities($profile['nickname']) ?>"</h4>
			</div>
			<div id="profile-attributes-container" class="row-fluid pull-left">
				<div class="attribute noto">
					<i class="fa fa-fw fa-map-marker"></i> <?php print htmlentities($profile['office']) ?>
				</div>

			</div>

			<div id="nav-tab-container" class="row-fluid pull-left">
				<ul class="nav nav-tabs">
					<li class="nav-item active" data-target="overview-tab">
						<a class="nav-link" href="#">Overview</a>
					</li>
					<li class="nav-item" data-target="goals-tab">
						<a class="nav-link" href="#">Goals</a>
					</li>
					<li class="nav-item" data-target="time-off-tab">
						<a class="nav-link" href="#">Time Off</a>
					</li>
					<li class="nav-item" data-target="feedback-tab">
						<a class="nav-link" href="#">Feedback</a>
					</li>
					<li class="nav-item" data-target="family-tab">
						<a class="nav-link" href="#">Family</a>
					</li>
					<li class="nav-item" data-target="notes-tab">
						<a class="nav-link" href="#">Notes</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="body-row" class="row-fluid pull-left">
		<div id="contact-container" class="container-fluid span2">
			
			<div id="contact-info">
				<div class="contact-container">
					<h5 class="contact-header">CONTACT INFORMATION</h5>

					<?php
						// Get the address info.
						$street = htmlentities($profile['street']);
						$city_state_zip = htmlentities($profile['city'].', '.$profile['state'].' '.$profile['zip']);
					?>

					<div class="contact noto"><?php print $street ?></div>
					<div class="contact noto"><?php print $city_state_zip ?></div>
					<div class="contact noto"><i class="fa fa-fw fa-mobile"></i><?php print htmlentities($profile['phone']) ?></div>
					<div class="contact noto"><i class="fa fa-fw fa-mobile"></i><?php print htmlentities($profile['cell']) ?></div>
					<div class="contact noto"><i class="fa fa-fw fa-envelope"></i><?php print htmlentities($profile['email']) ?></div>
				</div>
				<div class="contact-container">
					<h5 class="contact-header">ABOUT</h5>

					<?php
						// Get 'About' details.
						$birthday = htmlentities($profile['birthday']);
						$ywc = htmlentities($profile['years_with_company']);
						$hd = htmlentities($profile['hire_date']);
						$hire = $hd .' ('. $ywc.' years)';
					?>

					<div class="contact noto"><i class="fa fa-fw fa-birthday-cake"></i> <?php print $birthday ?></div>
					<div class="contact noto"><i class="fa fa-fw fa-calendar"></i> <?php print $hire ?></div>
				</div>
				<div class="contact-container">

					<?php
						// Get emergency contact infor.
						$ename = htmlentities($profile['emergency_name']);
						$ephone = htmlentities($profile['emergency_phone'])
					?>

					<h5 class="contact-header">EMERGENCY CONTACT</h5>
					<div class="contact noto">
						<i class="fa fa-fw fa-user-md"></i> <?php print $ename ?>
					</div>
					<div class="contact noto"><i class="fa fa-fw fa-mobile"></i> <?php print $ephone ?></div>
				</div>
			</div>

		</div>
		<div id="tabs-container" class="container-fluid span10">
			
			<div id="overview-tab" class="tab-target">
				<h3>Overview</h3>
			</div>
			<div id="goals-tab" class="tab-target" style="display:none;">
				<h3>Goals</h3>
			</div>
			<div id="time-off-tab" class="tab-target" style="display:none;">
				<h3>Time Off</h3>

				<div class="row">
					<div id="sick-container" class="time-off-container container-fluid span2"></div>
					<div id="vacation-container" class="time-off-container container-fluid span2"></div>
				</div>
				<div class="row text-center">
					<div class="time-off-label span2"><h4>Sick Days</h4></div>
					<div class="time-off-label span2"><h4>Vacation Days</h4></div>
				</div>

			</div>
			<div id="feedback-tab" class="tab-target" style="display:none;">
				<h3>Feedback</h3>
			</div>
			<div id="family-tab" class="tab-target" style="display:none;">
				<h3>Family</h3>
			</div>
			<div id="notes-tab" class="tab-target" style="display:none;">
				<h3>Notes</h3>
			</div>

		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){

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

			/* Tab-specific actions */

			// Time-off
			if(target_id=='time-off-tab'){
				// Start the time-off progress bars.
				init_time_off()
			}

		}

		function init_time_off(){

			// Used time off.
			var sused = parseInt('<?php print $profile['sick_used'] ?>')
			var vused = parseInt('<?php print $profile['vacation_used'] ?>')

			// Available time off.
			var savail = parseInt('<?php print $profile['sick_available'] ?>')
			var vavail = parseInt('<?php print $profile['vacation_available'] ?>')

			// Used percentage.
			var sperc = sused/savail
			var vperc = vused/vavail

			// Empty the containers.
			$('#sick-container').empty()
			$('#vacation-container').empty()

			// Start the time-off progress bargs.
			sbar = get_circular_progress_bar('#sick-container', sperc)
			vbar = get_circular_progress_bar('#vacation-container', vperc)

		}

		function get_profile_photo(){

			// Get the user's profile photo.

			// Get the photo ID.
			var $div = $('#profile-photo-container')
			var photo_id = $div.attr('data-photo-id')

			// Get the photo.
			$.ajax({
				'url' : 'http://10.1.247.195/profiles/get-profile-photo?photo-id='+photo_id,
				'method' : 'GET',
				'dataType' : 'jsonp',
				'success' : function(rsp){

					// Create the image tag.
					var uri = 'data:image/jpg;base64,'+rsp.image
					var $img = $('<img/>',{
						'id' : 'profile-photo',
						'src' : uri
					})

					var $container_div = $('<div>',{'class':'image-container'})
					$container_div.append($img)

					// Add the image to the photo container.
					$div.empty()
					$div.html($container_div)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}

			})

		}

		// Get the user's profile photo.
		get_profile_photo()

		// Enable tab switching.
		$(document).on('click', '.nav-item', switch_tabs)

	})
</script>