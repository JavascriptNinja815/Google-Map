<?php

$session->ensureLogin();

$args = array(
	'title' => 'Team Profile',
	'breadcrumbs' => array(
		'Profile' => BASE_URI . '/dashboard/account/profile',
		'Profile Directory' => BASE_URI . '/dashboard/account/profile/profile-directory'
	),
	'body-class' => 'padded'
);

function get_profiles(){

	// Query for user's profiles.

	$db = DB::get();
	$q = $db->query("
		SELECT
			p.profile_id,
			p.login_id,
			p.nickname,
			l.login,
			l.first_name,
			l.last_name,
			l.job_title,
			l.location_id,
			l.office,

			l.direct_line,
			l.cell_phone,
			l.extension,

			COALESCE(l.office, l.location_id) AS location,

			ph.profile_photo_id
		FROM Neuron.profiles.profile p
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = p.login_id
		INNER JOIN Neuron.profiles.profile_photos ph
			ON ph.profile_id = p.profile_id
		ORDER BY last_name, first_name
	");

	return $q->fetchAll();

}

function get_classes(){

	// Get the parent and profile container classes.

	// Should the profiles be displayed in list-view?
	$list = isset($_GET['listview']);

	if($list){
		return array(
			'parent' => 'list-container container',
			'profile' => 'container list-profile-container profile-container'
		);
	}else{
		return array(
			'parent' => 'container-fluid',
			'profile' => 'container span6 profile-container'
		);
	}

}

// Get everyone's profile information.
$profiles = get_profiles();

// Get the proper container classes.
$classes = get_classes();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#profile-directory-container {
		//border: 1px solid black;
	}
	.profile-container {
		min-height: 209px;
		background-color: #edeff0;
		border-radius: 5px;
		margin:10px;
		cursor: pointer;
		//border: 1px solid green;
	}
	.photo-container {
		//height: 290px;
		margin-top: 5px;
		margin-left: 5px;
		margin-bottom: 5px;
		display: block;
		//border: 1px solid blue;
	}
	.name-container {
		margin-left: 5px;
	}
	.position-container {
		margin-left: 5px;
	}
	.profile-photo {
		max-height: 100%;
		max-width: 60%;
		display: block;
		margin: auto;
		border-radius: 55px;
	}
	.tile-label {
		margin-top: -15px;
	}
	.list-container {
		width: 99%;
	}
	.list-profile-container {
		width: 99%;
	}
	.change-view {
		cursor: pointer;
	}
</style>

<div id="profile-directory-container" class="<?php print $classes['parent'] ?>">
	
	<div id="view-container" class="container-fluid">
		<div class="change-view pull-right" data-view="tile"><i class="fa fa-fw fa-2x fa-th"></i></div>
		<div class="change-view pull-right" data-view="list"><i class="fa fa-fw fa-2x fa-list-ul"></i></div>
	</div>

	<?php
	foreach($profiles as $profile){
		?>
		<div class="<?php print $classes['profile'] ?>" data-login-id="<?php print htmlentities($profile['login_id']) ?>">
			<?php
				// The profile photo ID.
				$photo_id = htmlentities($profile['profile_photo_id'])
			?>
			<div class="container span3 photo-container" data-photo-id="<?php print $photo_id ?>"></div>

			<?php
				// Get the name.
				$name = $profile['first_name'].' '.$profile['last_name'];
			?>
			<div class="tile-label-container container span3">
				<div class="container span3 name-container"><h3><?php print htmlentities($name) ?></h3></div>
				<div class="container span3 tile-label"><h4><?php print htmlentities($profile['location']) ?></h4></div>
				<div class="container span3 tile-label"><h5><?php print htmlentities($profile['login']) ?></h5></div>
				<div class="container span3 tile-label"><h5>Cell: <?php print htmlentities($profile['cell_phone']) ?></h5></div>
				<div class="container span3 tile-label"><h5>Direct: <?php print htmlentities($profile['direct_line']) ?></h5></div>
				<div class="container span3 tile-label"><h5>Ext: <?php print htmlentities($profile['extension']) ?></h5></div>
			</div>
		</div>
		<?php
	}
	?>

</div>

<script type="text/javascript">
$(document).ready(function(){

	function get_profile_photo(container){

		// Get an individual profile photo.

		// Get the photo ID.
		$container = $(container)
		photo_id = $container.attr('data-photo-id')

		// Get the photo.
		return $.ajax({
			'url' : 'http://10.1.247.195/profiles/get-profile-photo?thumbnail=true&photo-id='+photo_id,
			'method' : 'GET',
			'dataType' : 'JSONP',
			'success' : function(rsp){},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function get_profile_photos(){

		// Get the user's profile photos.

		// Get all profile photo blocks.
		var $blocks = $('.photo-container')
		$.each($blocks, function(idx, block){

			$.when(get_profile_photo(block)).done(function(rsp){

				// Create the URI.
				var uri = 'data:image/jpg;base64,'+rsp.image

				// Create the image tag.
				var $img = $('<img/>',{
					'class' : 'profile-photo',
					'src' : uri
				})

				// Set the profile photo.
				var $block = $(block)
				$block.empty()
				$block.html($img)

			})
		})

	}

	function visit_profile(){

		// Navigate to a user's profile.

		// Get the login ID.
		var $container = $(this)
		var login_id = $container.attr('data-login-id')

		// Navigate to the profile.
		window.location.href = '/dashboard/account/profile?login-id='+login_id

	}

	function change_view(){

		// Change between list and tile views.

		// Get the view.
		var $div = $(this)
		var view = $div.attr('data-view')

		// Get the current URL.
		var path = window.location.pathname

		// Listview.
		if(view=='list'){
			path += '?listview'
		}

		// Redirect.
		window.location.href = path

	}

	// Display the profile photos.
	get_profile_photos()

	// Support visiting a user's profile.
	$(document).on('click', '.profile-container', visit_profile)

	// Support changing the view.
	$(document).on('click', '.change-view', change_view)

})
</script>