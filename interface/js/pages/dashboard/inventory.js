
$(function() {

	/**
	 * Defines the variable used in storing touch events for later comparison.
	 */
	var touch_target;

	/**
	 * Ensure options within "Available" drop-down are never selected.
	 */
	var available_option_click_fn = function(event) {
		var $option = $(this);

		var $available_container = $option.closest('.search-field-available');
		var $filterby_container = $available_container.closest('.search-field').find('.search-field-filterby');

		// Grab the name for the hiddne input to be added to the option.
		var input_name = $available_container.attr('input-name');

		// Grab the value for the hidden input to be added to the option.
		var input_value = $option.attr('input-value');

		$option.appendTo($filterby_container).append(
			$('<input type="hidden">').attr('name', input_name).attr('value', input_value)
		);

		// Sort Filter By options by name.
		var $filterby_options = $filterby_container.find('.search-field-value');
		$filterby_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$filterby_options.detach().appendTo($filterby_container);
	};
	$(document).on('click', '#dates-container .search-field-available .search-field-value', available_option_click_fn);
	$(document).on('touchstart', '#dates-container .search-field-available .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '#dates-container .search-field-available .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});

	/**
	 * Bind to change events on Selected Statuses drop-down.
	 */
	var filterby_option_click_fn = function(event) {
		var $option = $(this);

		var $filterby_container = $option.closest('.search-field-filterby');
		var $available_container = $filterby_container.closest('.search-field').find('.search-field-available');

		$option.appendTo($available_container);
		$option.find('input').remove();

		// Sort Available options by name.
		var $available_options = $available_container.find('.search-field-value');
		$available_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$available_options.detach().appendTo($available_container);
	};
	$(document).on('click', '#dates-container .search-field-filterby .search-field-value', filterby_option_click_fn);
	$(document).on('touchstart', '#dates-container .search-field-filterby .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '#dates-container .search-field-filterby .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});
	
	/**
	 * Bind to clicks on "+" and "-" icons within search fields.
	 */
	$(document).off('click', '.search-field-add'); // Prevents double-binding.
	$(document).on('click', '.search-field-add', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $available_container = $search_field_container.find('.search-field-available');
		$available_container.find('.search-field-value').click();
	});
	$(document).off('click', '.search-field-remove'); // Prevents double-binding.
	$(document).on('click', '.search-field-remove', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $filter_by_container = $search_field_container.find('.search-field-filterby');
		$filter_by_container.find('.search-field-value').click();
	});
});
