var $permissions_container;
var $permission_prototype;

$(function() {
	/**
	 * Bind to "Delete Role" clicks.
	 */
	$(document).on('click', '.delete', function() {
		var $trash = $(this);
		var $role = $('input[name="role"]');
		var role = $role.val();
		if(confirm('Are you sure you want to delete this Role?\nAll Permissions associated with this Role will also be deleted.\n\nThis action is irreversible.\n\n' + role)) {
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
				'url': BASE_URI + '/dashboard/administration/roles/delete',
				'dataType': 'json', // Returned data type.
				'type': 'POST', // Data submission type.
				'data': { // Data to submit.
					'role_id': params.role_id
				},
				'beforeSend': function(xhr, settings) {
					$layz.fadeIn();
				},
				'success': function(data, status, xhr) {
					if(data.sucess) {
						window.location = BASE_URI + '/dashboard/administration/roles';
					} else {
						
					}
				}
			});
		}
	});

	/**
	 * Bind to "Add Permission" clicks.
	 */
	$(document).on('click', '.permissions .add', function() {
		var $plus = $(this);
		var permission = prompt('What is the name for the new Permission?');
		if(permission) {
			// Ensure permission specified isn't already present.
			var permission_exists = false;
			$.each($permissions_container.find('input[name="static-permissions[]"]'), function(index, input) {
				if($(input).val() === permission) {
					permission_exists = true;
				}
			});
			// Permission entered already exists, so don't add it again.
			if(permission_exists) {
				return;
			}

			var $permission = $permission_prototype.clone().removeClass('prototype').hide().insertBefore($plus);
			$permission.find('.permission').text(permission);
			$permission.append(
				$('<input type="hidden" name="static-permissions[]" />').val(permission)
			);
			$permission.slideDown();
		}
	});

	/**
	 * Bind to "Remove Permission" clicks.
	 */
	$(document).on('click', '.permissions .remove', function() {
		var $minus = $(this);
		var $permission = $minus.closest('.permission');
		var permission = $permission.find('.permission').text();
		if(confirm('Are you sure you want to remove this permission?\n\n' + permission)) {
			$permission.slideUp(function() {
				$(this).remove();
			});
		}
	});

	/**
	 * Bind to "Permission Type" radio button changes.
	 */
	$(document).on('change', 'input[name="permission-type"]', function(event) {
		var $radio = $(this);
		var permission_type = $radio.val();

		var $permission_containers = $('.permissions-container');

		if(permission_type === '0') {
			var $static_permissions_container = $('.permissions-container-static');
			$permission_containers.not($static_permissions_container).hide();
			$static_permissions_container.show();
		} else if(permission_type === '1') {
			var $dynamic_permissions_container = $('.permissions-container-dynamic');
			$permission_containers.not($dynamic_permissions_container).hide();
			$dynamic_permissions_container.show();
		}
	});

	/**
	 * Bind to "Test Query" button clicks.
	 */
	$(document).on('click', '#test-dynamic-permission-query', function(event) {
		alert('TODO: On success, render table w/ output data from query. On failure, display error message for debugging.');
		
		/*
		var query = $('textarea[name="dynamic-permission-query"]').val();
		$.ajax({
			'url': BASE_URI + '/dashboard/administration/roles/test-query',
			'data': {
				'query': query
			},
			'success': function(data) {
				if(data.success) {
					
				} else {
					
				}
			}
		});*/
	});

	/**
	 * Bind to Role form submissions.
	 */
	$(document).on('submit', '#edit-role-container', function() {
		var $form = $(this);
		var inputs = $form.lazyForm();

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
			'url': BASE_URI + "/dashboard/administration/roles/save",
			'dataType': 'json', // Returned data type.
			'type': 'POST', // Data submission type.
			'data': inputs,
			'beforeSend': function(xhr, settings) {
				$layz.fadeIn();
			},
			'success': function(data, status, xhr) {
				var role_id = $('input[name="role_id"]').val();
				window.location = BASE_URI + '/dashboard/administration/roles/edit?role_id=' + role_id;
			}
		});

		return false; // Prevent form submission propogation.
	});
});

$(document).ready(function() {
	$permissions_container = $('.permissions');
	$permission_prototype = $permissions_container.find('.permission.prototype');
});
