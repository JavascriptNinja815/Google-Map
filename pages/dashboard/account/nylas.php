<?php

$session->ensureLogin();

$args = array(
	'title' => 'My Account',
	'breadcrumbs' => array(
		'My Account' => BASE_URI . '/dashboard/account',
		'Nylas' => BASE_URI . '/dashboard/account/nylas'
	),
	'body-class' => 'padded'
);

function get_nylas_credentials(){

	// Get the Nylas credentials for the user.

	global $session;
	$login_id = $session->login['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT refresh_token, access_token
		FROM Neuron.dbo.nylas_auth
		WHERE login_id = ".$db->quote($login_id)."
	");

	$r = $q->fetch();

	if($r){return $r;};
	return;

}

function get_auth_path(){

	if(ISLIVE){return 'live';}
	else{return 'dev';}

}

// Get the Nylas credentials.
$nylas = get_nylas_credentials();

// Check whether this page was loaded in dev or productin.
$path = get_auth_path();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#nylas-container {
		align-items: center;
		align-content: center;
		display: flex;
	}
	#oauth-init-container {
		height: 300px;
	}
	.btn-container {
		border-radius: 5px;
		height: 50px;
		padding-top: 30px;
		font-size: 20px;
		background-color: #feed0545;
		cursor: pointer;
	}
	.btn-container:hover {
		background-color: #feed0561;
	}
	.btn-container:active {
		background-color: #feed0545;
	}
	img {
		max-height: 100%;
		max-width: 100%;
	}
	.cd-nylas-logo-container {
		padding-top: 20px;
		padding-bottom: 30px;
		height: 100px;
	}

	/* Hide all but the first tab by default. */
	#placeholder-tab {
		display:none;
	}

</style>

<h2>Nylas Console</h2>
<?php
	if(!$nylas){
		?>
			<div id="nylas-container" class="container">
				<div id="oauth-init-container" class="row span5 offset2">
					<div class="row-fluid text-center">
						<h3>Connect with Nylas</h3>
					</div>
					<div class="row-fluid text-center cd-nylas-logo-container"><img src="/interface/images/casterdepot-cd-logo.png"></div>
					<div class="row-fluid text-center btn-container auth-btn" data-provider="gmail">GMAIL LOGIN</div>
				</div>
			</div>
		<?php
	}
	else {
		?>
		<div id="nav-container">
			<ul class="nav nav-tabs">
				<li class="nav-item active" data-target="statements-tab">
					<a class="nav-link" href="#">Statements</a>
				</li>
			</ul>
		</div>
		<div id="nylas-console">
			<div id="statements-tab" class="tab-target">
				Statements
			</div>
		</div>
		<?php
	}
?>


<script type="text/javascript">
	$(document).ready(function(){

		function init_oauth(){

			// Initialize the OAuath flow.

			// Check whther the user is on dev or production.
			var path = '<?php print $path ?>'
			var url = 'http://10.1.247.195/nylas/get-redirect?dev='

			// Get the proper redirect URL.
			if(path=='live'){
				url += '0'
			}else{
				url += '1'
			}

			// Get the URL.
			$.ajax({
				'url' : url,
				'method' : 'GET',
				'dataType' : 'jsonp',
				'async' : false,
				'success' : function(rsp){

					// Redirect.
					window.location.href = rsp.url

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
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


		// Make the login buttons clickable.`
		$(document).on('click', '.auth-btn', init_oauth)

		// Enable tab switching.
		$(document).on('click', '.nav-item', switch_tabs)

	})
</script>