$(function() {
	$('#account-submit-form').on('submit', function(event) {
		var $form = $(this);
		var data = $.lazyForm($form);

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
			'url': BASE_URI + '/dashboard/account/save',
			'dataType': 'json', // Returned data type.
			'type': 'POST', // Data submission type.
			'data': data,
			'beforeSend': function(xhr, settings) {
				$layz.fadeIn();
			},
			'success': function(data, status, xhr) {
				var $error_container = $('.notification-container.notification-error').empty().slideUp();
				var $success_container = $('.notification-container.notification-success').empty().slideUp();
				if(data['success'] === false) {
					$.each(data['errors'], function(input_name, message) {
						$('<div class="notification">').text(message).appendTo($error_container);
					});
					$error_container.slideDown();
				} else if(data['success'] === true) {
					$('<div class="notification">').text('Changes successfully saved').appendTo($success_container);
					$success_container.slideDown();
				}
				$layz.fadeOut();
			}
		});
		return false;
	});
});