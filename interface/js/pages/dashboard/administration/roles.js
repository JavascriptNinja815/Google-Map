$(function() {
	/**
	 * Bind to "Create Role" form submissions.
	 */
	$(document).on('submit', '#create-role-container', function(event) {
		var $form = $(this);
		var url = $form.attr('action');
		var $role = $form.find('input[name="role"]');
		var role = $role.val();

		if(!role) { // Ensure role has been defined
			console.log('no role');
			return false;
		}

		var $layz = $.overlayz({
			'html': '<div style="width:100px;margin:auto;height:300px;padding-top:100px;padding-bottom:100px;"><img src="/interface/images/ajax-loading.gif" /></a>',
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
			'url': url,
			'dataType': 'json', // Returned data type.
			'type': 'POST', // Data submission type.
			'data': { // Data to submit.
				'role': role
			},
			'beforeSend': function(xhr, settings) {
				$layz.fadeIn();
			},
			'success': function(data, status, xhr) {
				if(data['success'] === false) {
					$.each(data['errors'], function(input_name, message) {
						$form.find('.' + input_name + '-error').hide().text(message).slideDown();
					});
					$layz.fadeOut();
				} else if(data['success'] === true) {
					window.location = '/dashboard/administration/roles/edit?role_id=' + data['role_id'];
				}
			}
		});

		return false; // Prevent form submission propogation.
	});
});