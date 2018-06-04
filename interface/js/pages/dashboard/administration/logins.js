$(function() {
	$('#create-login-container').on('submit', function(event) {
		var $form = $(this);
		var $input_login = $form.find('input[name="login"]');
		var $input_password = $form.find('input[name="password"]');
		var $input_firstname = $form.find('input[name="first_name"]');
		var $input_lastname = $form.find('input[name="last_name"]');
		var $input_initials = $form.find('input[name="initials"]');
		var $input_companies = $form.find('input[name="companies[]"]:checked');

		var companies = [];
		$.each($input_companies, function(offset, company) {
			var $company = $(company);
			companies.push($company.val());
		});

		var $layz = $.overlayz({
			'html': '<div style="width:100px;margin:auto;height:300px;padding-top:100px;padding-bottom:100px;"><img src="' + STATIC_PATH + '/images/ajax-loading.gif" /></a>',
			'css': {
				'body': {
					'width': 300,
					'height': 300,
					'border-radius': 150,
					'border': 0,
					'padding': 0
				}
			},
			'close-actions': false // Prevent the user from being able to close the overlay on demand.
		});

		$.ajax({
			'url': BASE_URI + '/dashboard/administration/logins/create',
			'dataType': 'json', // Returned data type.
			'type': 'POST', // Data submission type.
			'data': { // Data to submit.
				'login':      $input_login.val(),
				'password':   $input_password.val(),
				'first_name': $input_firstname.val(),
				'last_name':  $input_lastname.val(),
				'initials':  $input_initials.val(),
				'companies': companies
			},
			'beforeSend': function(xhr, settings) {
				$layz.fadeIn();
			},
			'success': function(data, status, xhr) {
				if(data['success'] === false) {
					$.each(data['errors'], function(input_name, message) {
						$('#create-login-container .' + input_name + '-error').text(message);
					});
					$layz.fadeOut();
				} else if(data['success'] === true) {
					window.location = BASE_URI + '/dashboard/administration/logins/edit?login_id=' + data['login_id'];
				}
			}
		});

		return false;
	});
});