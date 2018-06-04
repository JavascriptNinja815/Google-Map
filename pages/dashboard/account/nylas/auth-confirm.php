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

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#continue-container {
		border-radius: 5px;
		height: 50px;
		padding-top: 30px;
		font-size: 20px;
		background-color: #feed0545;
		cursor: pointer;
		display:none;
	}
	#continue-container:hover {
		background-color: #feed0561;
	}
	#continue-container:active {
		background-color: #feed0545;
	}
</style>

<div class="container">
	<div class="row span8">
		<table class="table table-striped table-hover">
			<thead>
				<th>Stage</th>
				<th>Progress</th>
			</thead>
			<tbody>
				<tr>
					<td>OAuth Finalization</td>
					<td>
						<div class="progress progress-striped">
							<div id="oauth-final-bar" class="bar"></div>
						</div>
					</td>
				</tr>
				<tr>
					<td>Nylas Initialization</td>
					<td>
						<div class="progress progress-striped">
							<div id="nylas-init-bar" class="bar"></div>
						</div>
					</td>
				</tr>
				<tr>
					<td>Nylas Finalization</td>
					<td>
						<div class="progress progress-striped">
							<div id="nylas-final-bar" class="bar"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="continue-container" class="row span8">
		<div class="row-fluid text-center">Continue</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function get_url_params(){

			var match,
				pl = /\+/g,
				search = /([^&=]+)=?([^&]*)/g,
				decode = function(s){return decodeURIComponent(s.replace(pl, ' '))},
				query = window.location.search.substring(1)

			var params = {}
			while (match = search.exec(query)){
				params[decode(match[1])] = decode(match[2])
			}

			return params
		}

		function finalize_oauth(){

			// Finalize the OAuth workflow.

			// Get and start the progress bar.
			var $bar = $('#oauth-final-bar')
			var $bar_container = $bar.parents('.progress')
			$bar.css('width', '100%')
			$bar_container.addClass('active')

			// Get URL Parameters.
			var params = get_url_params()

			// Get the login ID.
			var login_id = '<?php print $session->login['login_id'] ?>'

			// Get state and code.
			var state = params['state']
			var code = params['code']

			// The data to use in the AJAX request.
			var data = {
				'state' : state,
				'login_id' : login_id,
				'url' : window.location.href
			}

			// Get the refresh token from Google.
			$.ajax({
				'url' : 'http://10.1.247.195/nylas/finalize-oauth',
				'method' : 'GET',
				'data' : data,
				'dataType' : 'jsonp',
				'async' : false,
				'success' : function(rsp){

					// Stop the progress bar.
					$bar_container.removeClass('active')
					$bar_container.addClass('progress-success')

					// Begin Nylas authentication.
					auth_nylas(rsp.refresh_token)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function auth_nylas(refresh_token){

			// Initialize Nylas authentication.

			// Get and start the progress bar.
			var $bar = $('#nylas-init-bar')
			var $bar_container = $bar.parents('.progress')
			$bar.css('width', '100%')
			$bar_container.addClass('active')

			// Get the user's email address and name.
			var email = '<?php print $session->login['login'] ?>'
			var first = '<?php print $session->login['first_name'] ?>'
			var last = '<?php print $session->login['last_name'] ?>'
			var name = first +' '+ last

			// The data to send in the AJAX request.
			var data = {
				'email' : email,
				'name' : name,
				'refresh_token' : refresh_token
			}

			// Get the code.
			$.ajax({
				'url' : 'http://10.1.247.195/nylas/init-nylas-auth',
				'method' : 'GET',
				'data' : data,
				'dataType' : 'jsonp',
				'async' : false,
				'success' : function(rsp){

					// Stop the progress bar.
					$bar_container.removeClass('active')
					$bar_container.addClass('progress-success')

					// Finalize the authentication.
					finalize_nylas(rsp.code)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function finalize_nylas(code){

			// Finalize the Nylas authentication.

			// Get and start the progress bar.
			var $bar = $('#nylas-final-bar')
			var $bar_container = $bar.parents('.progress')
			$bar.css('width', '100%')
			$bar_container.addClass('active')

			// Get the login ID.
			var login_id = '<?php print $session->login['login_id'] ?>'

			// The data to use in the AJAX request.
			var data = {
				'code' : code,
				'login_id' : login_id
			}

			// Send the code to Nylas for an access token.
			$.ajax({
				'url' : 'http://10.1.247.195/nylas/finalize-nylas-auth',
				'method' : 'GET',
				'data' : data,
				'dataType' : 'jsonp',
				'async' : false,
				'success' : function(rsp){

					// Stop the progress bar.
					$bar_container.removeClass('active')
					$bar_container.addClass('progress-success')

					// Display the continue button.w
					$('#continue-container').show()

					// TODO:
					// Do something here.

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function continue_to_nylas(){

			// Navigate to the Nylasconsole.
			window.location.href = BASE_URI + '/dashboard/account/nylas'

		}

		// Finalze the OAuth flow.
		finalize_oauth()

		// Enable the continue button.
		$(document).on('click', '#continue-container', continue_to_nylas)

	})
</script>