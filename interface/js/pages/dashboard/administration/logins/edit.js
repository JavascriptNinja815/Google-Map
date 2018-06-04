
function render_goal(goal_id, type, title, amount, start, end) {
	var $goals = $('.goals');

	var $goal_prototype = $goals.find('.goal-prototype');
	var $goal = $goal_prototype.clone().removeClass('hidden').removeClass('prototype').removeClass('goal-prototype').appendTo($goals);

	var $edit = $goal.find('.edit').hide();
	var $delete = $goal.find('.delete').hide();

	var $save = $goal.find('.save');
	var $cancel = $goal.find('.cancel');

	var $type_container = $goal.find('.type');
	var $title_container = $goal.find('.title');
	var $amount_container = $goal.find('.amount');
	var $start_container = $goal.find('.start');
	var $end_container = $goal.find('.end');

	var datepicker_config = {
		'lang': 'en',
		'datepicker': true,
		'timepicker': false,
		'formatDate': 'Y-m-d', //'formatDate': 'n/j/Y g:ia',
		'format': 'Y-m-d',
		'closeOnDateSelect': true
	};

	var $type_input, $title_input, $amount_input, $start_input, $end_input;

	if(typeof goal_id === 'undefined') {
		// New goal.
		$type_input = $('<select name="type">').append(
			$('<option value="Sales Goal">').text('Sales Goal'),
			$('<option value="Margin Goal">').text('Margin Goal'),
			$('<option value="Client Count Goal">').text('Client Count Goal'),
			$('<option value="New Client Count Goal">').text('New Client Count Goal'),
			$('<option value="New Client Sales Goal">').text('New Client Sales Goal')
		).appendTo($type_container);
		$title_input = $('<input type="text" name="title" />').appendTo($title_container);
		$amount_input = $('<input type="number" name="amount" />').appendTo($amount_container);
		$start_input = $('<input type="date" name="start" />').appendTo($start_container);
		$end_input = $('<input type="date" name="end" />').appendTo($end_container);

		var datepicker_onShow = function(selected_datetime) {
			var month = parseInt(selected_datetime.getMonth()) + 1;
			if(month < 10) {
				month = '0' + month;
			}
			var date = parseInt(selected_datetime.getDate());
			if(date < 10) {
				date = '0' + date;
			}
			this.setOptions({
				//'maxDate': $start_input.val(),
				'value': selected_datetime.getFullYear() + '-' + month + '-' + date
			});
		};
		var datepicker_onChangeDateTime = function(selected_datetime) {
			var month = parseInt(selected_datetime.getMonth()) + 1;
			if(month < 10) {
				month = '0' + month;
			}
			var date = parseInt(selected_datetime.getDate());
			if(date < 10) {
				date = '0' + date;
			}
			this.setOptions({
				//'maxDate': $end_input.val(),
				'value': selected_datetime.getFullYear() + '-' + month + '-' + date
			});
		};

		// Generate DatePicker config for `start` input.
		var datepicker_from_config = $.extend(datepicker_config, {});
		datepicker_from_config.onShow = datepicker_onShow;
		datepicker_from_config.onChangeDateTime = datepicker_onChangeDateTime;

		// Generate DatePicker config for `end` input.
		var datepicker_to_config = $.extend(datepicker_config, {});
		datepicker_to_config.onShow = datepicker_onShow;
		datepicker_to_config.onChangeDateTime = datepicker_onChangeDateTime;

		$start_input.datetimepicker(datepicker_from_config);
		$end_input.datetimepicker(datepicker_to_config);

		$edit.hide();
		$delete.hide();

		$save.show();
		$cancel.show();
	} else {
		// Load existing goal.
		$goal.removeClass('goal-new');
		$goal.attr('goal_id', goal_id);

		$type_container.text(type);
		$title_container.text(title);
		$amount_container.text(amount);
		$start_container.text(start);
		$end_container.text(end);

		$edit.show();
		$delete.show();

		$save.hide();
		$cancel.hide();
	}

	$edit.on('click', function(event) {
		var type = $type_container.text().trim();
		var title = $title_container.text().trim();
		var amount = $amount_container.text().replace(/\$/g, '').replace(/\,/g, '');
		var start = $start_container.text().trim();
		var end = $end_container.text().trim();

		$type_container.empty();
		$title_container.empty();
		$amount_container.empty();
		$start_container.empty();
		$end_container.empty();

		$type_input = $('<select name="type">').append(
			$('<option value="Sales Goal">').text('Sales Goal'),
			$('<option value="Margin Goal">').text('Margin Goal'),
			$('<option value="Client Count Goal">').text('Client Count Goal'),
			$('<option value="New Client Count Goal">').text('New Client Count Goal'),
			$('<option value="New Client Sales Goal">').text('New Client Sales Goal')
		).appendTo($type_container).val(type);
		$title_input = $('<input type="text" name="title" />').appendTo($title_container).val(title);
		$amount_input = $('<input type="number" name="amount" />').appendTo($amount_container).val(amount);
		$start_input = $('<input type="date" name="start" />').appendTo($start_container).val(start);
		$end_input = $('<input type="date" name="end" />').appendTo($end_container).val(end);

		var datepicker_onShow = function(selected_datetime) {
			var month = parseInt(selected_datetime.getMonth()) + 1;
			if(month < 10) {
				month = '0' + month;
			}
			var date = parseInt(selected_datetime.getDate());
			if(date < 10) {
				date = '0' + date;
			}
			this.setOptions({
				//'maxDate': $start_input.val(),
				'value': selected_datetime.getFullYear() + '-' + month + '-' + date
			});
		};
		var datepicker_onChangeDateTime = function(selected_datetime) {
			var month = parseInt(selected_datetime.getMonth()) + 1;
			if(month < 10) {
				month = '0' + month;
			}
			var date = parseInt(selected_datetime.getDate());
			if(date < 10) {
				date = '0' + date;
			}
			this.setOptions({
				//'maxDate': $end_input.val(),
				'value': selected_datetime.getFullYear() + '-' + month + '-' + date
			});
		};

		// Generate DatePicker config for `start` input.
		var datepicker_from_config = $.extend(datepicker_config, {});
		datepicker_from_config.onShow = datepicker_onShow;
		datepicker_from_config.onChangeDateTime = datepicker_onChangeDateTime;

		// Generate DatePicker config for `end` input.
		var datepicker_to_config = $.extend(datepicker_config, {});
		datepicker_to_config.onShow = datepicker_onShow;
		datepicker_to_config.onChangeDateTime = datepicker_onChangeDateTime;

		$start_input.datetimepicker(datepicker_from_config);
		$end_input.datetimepicker(datepicker_to_config);

		$edit.hide();
		$delete.hide();

		$save.show();
		$cancel.show();
	});

	$delete.on('click', function(event) {
		if(confirm('Are you sure you want to remove this Goal?')) {
			var goal_id = $goal.attr('goal_id');
			if(typeof goal_id !== 'undefined') {
				$.ajax({
					'url': BASE_URI + '/dashboard/administration/logins/goals/delete',
					'type': 'POST',
					'data': {
						'goal_id': goal_id
					},
					'dataType': 'json',
					'success': function() {
						// Nothing extra to do.
					}
				});
			}

			$goal.remove();
		}
	});

	$save.on('click', function(event) {
		var goal_id = $goal.attr('goal_id');
		var $login_id = $('input[name="login_id"]');

		var type = $type_input.val();
		var title = $title_input.val();
		var amount = $amount_input.val().replace(/\$/g, '').replace(/\,/g, '');
		var start = $start_input.val();
		var end = $end_input.val();
		var login_id = $login_id.val();

		var data = {
			'type': type,
			'title': title,
			'amount': amount,
			'start': start,
			'end': end,
			'login_id': login_id
		};

		if(typeof goal_id === 'undefined') {
			// Create a new goal entry.
			var url = BASE_URI + '/dashboard/administration/logins/goals/create';
			var success_fn = function(response, status, jqXHR) {
				// Set Goal ID on the goal entry.
				$goal.attr('goal_id', response.goal_id);
			};
		} else {
			// Update an existing goal.
			data['goal_id'] = goal_id;
			var url = BASE_URI + '/dashboard/administration/logins/goals/update';
			var success_fn = function(response, status, jqXHR) {
				// Nothing extra to do.
			};
		}

		if(!type.length || !title.length || !amount.length || !start.length || !end.length) {
			// Don't do anything if we're missing anything.
			return false;
		}

		$.ajax({
			'url': url,
			'type': 'POST',
			'data': data,
			'dataType': 'json',
			'success': success_fn
		});

		$type_container.empty().text(type);
		$title_container.empty().text(title);
		$amount_container.empty().text(amount);
		$start_container.empty().text(start);
		$end_container.empty().text(end);

		$edit.show();
		$delete.show();

		$save.hide();
		$cancel.hide();
	});

	$cancel.on('click', function(event) {
		if($goal.hasClass('goal-new')) {
			$goal.remove();
			return;
		}

		var type = $type_input.val();
		var title = $title_input.val();
		var amount = $amount_input.val().replace(/\$/g, '').replace(/\,/g, '');
		var start = $start_input.val();
		var end = $end_input.val();

		$type_container.text(type);
		$title_container.text(title);
		$amount_container.text(amount);
		$start_container.text(start);
		$end_container.text(end);

		$edit.show();
		$delete.show();

		$save.hide();
		$cancel.hide();
	});
};

$(function() {
	/**
	 * Bind to "Delete" icon clicks.
	 */
	$(document).on('click', '.delete-login', function(event) {
		var $icon = $(this);
		var login_id = $('input[name="login_id"]').val();
		if(confirm('Are you sure you want to delete this login?\n\nThis action cannot be undone.')) {
			window.location = BASE_URI + '/dashboard/administration/logins/delete?login_id=' + login_id;
		}
	});
	
	/**
	 * Bind to "status" drop-down changes.
	 */
	$(document).on('change', 'select[name="status"]', function(event) {
		var $select = $(this);
		var $status_disabled_warning = $('.status-disabled-warning');
		if($select.val() == '0') {
			$status_disabled_warning.slideDown();
		} else {
			$status_disabled_warning.slideUp();
		}
	});

	/**
	 * Bind to "roles" checkbox changes
	 */
	$(document).on('change', 'input[name^="roles["]', function(event) {
		var $input = $(this);
		var $role_container = $input.closest('.role');
		var $permissions_container = $role_container.find('.permissions-container');
		if($input.is(':checked')) {
			$permissions_container.slideDown();
		} else {
			$permissions_container.slideUp();
		}
	});

	/**
	 * Bind to "Sales" role "view-orders" permission changes
	 */
	$(document).on('change', '.permission-container.permission-view-orders input[type="checkbox"]', function(event) {
		var $vieworders_checkbox = $(this);

		var $editorders_container = $('.permission-container.permission-edit-orders');
		var $editorders_checkbox = $editorders_container.find('input[value="' + $vieworders_checkbox.val() + '"]');
		var $editorders_permission_container = $editorders_checkbox.closest('.permission');
		if($vieworders_checkbox.is(':checked')) {
			$editorders_permission_container.show();
			$editorders_checkbox.prop('checked', true).change();
		} else {
			$editorders_permission_container.hide();
			$editorders_checkbox.prop('checked', false).change();
		}
	});

	/**
	 * Bind to permission's "Check All" link.
	 */
	$(document).on('click', '.permission-container .permission-checkall', function(event) {
		var $permission_container = $(this).closest('.permission-container');
		var $permission_inputs = $permission_container.find('input[type="checkbox"]:visible');
		$permission_inputs.prop('checked', true).change();
	});

	/**
	 * Bind to permission's "Un-Check All" link.
	 */
	$(document).on('click', '.permission-container .permission-uncheckall', function(event) {
		var $permission_container = $(this).closest('.permission-container');
		var $permission_inputs = $permission_container.find('input[type="checkbox"]:visible');
		$permission_inputs.prop('checked', false).change();
	});

	/**
	 * When page loads, hide/show "Sales" role "edit-orders" permissions based
	 * on what is checked under "view-orders" permissions.
	 */
	$(function() {
		var $vieworders_container = $('.permission-container.permission-view-orders');
		var $editorders_container = $('.permission-container.permission-edit-orders');
		var $editorders_permissions = $editorders_container.find('.permission');
		$.each($editorders_permissions, function(index, editorders_permission_container) {
			var $editorders_permission_container = $(editorders_permission_container);
			var $editorders_checkbox = $editorders_permission_container.find('input[type="checkbox"]');
			var $vieworders_checkbox = $vieworders_container.find('input[value="' + $editorders_checkbox.val() + '"]');
			if(!$vieworders_checkbox.is(':checked')) { // If the view-orders permission is present, hide the edit-orders version of the permission.
				$editorders_permission_container.hide();
				$editorders_checkbox.prop('checked', false).change(); // Since it's not even an option, it should never be checked.
			}
		});
	});

	/**
	 * Bind to form submissions
	 */ 
	$(function() {
		$('#edit-login-container').on('submit', function(event) {
			var form = this;
			var $form = $(this);
			var data = new FormData(form);

			/*var $inputs = $form.find('input[type="text"], input[type="date"], input[type="number"], input[type="password"], input[type="hidden"], input[type="checkbox"]:checked, input[type="radio"]:checked, textarea, button, select');
			var data = {};
			$.each($inputs, function(index, input) {
				var $input = $(input);
				if(!$input.val()) { // Skip inputs with no value.
					return;
				}
				data[$input.attr('name')] = $input.val();
			});*/

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
				'url': BASE_URI + '/dashboard/administration/logins/save',
				'data': data,
				'method': 'POST',
				'dataType': 'json',
				'processData': false,
				'contentType': false,
				'enctype': 'multipart/form-data',
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
						window.location = BASE_URI + '/dashboard/administration/logins/edit?login_id=' + params.login_id + '&saved';
					}
				}
			});
			return false;
		});
	});

	$(document).on('click', '.goals-container .new-goal', function(event) {
		render_goal();
	});

});
