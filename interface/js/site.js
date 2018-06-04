
/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Great Lakes Caster
 */

var $loading_prototype = $('<div>').addClass('loading-icon').append(
	$('<img src="' + BASE_URI + '/interface/images/loading-animation.gif" draggable="false" style="margin-bottom:12px;" />'),
	$('<br />'),
	$('<b>').text('Please Wait...')
);

function applyTableFeatures(table, sticky_header_parent) {
	/**
	 * Table is expected to have one or more of the following classes for the
	 * features to be properly applied:
	 *   columns-sortable
	 *   columns-filterable
	 *   headers-sticky
	 */
	
	if(!sticky_header_parent) {
		sticky_header_parent = '#body';
	}

	var $table = $(table);
	var options = {
		'selectorHeaders': [],
		'widgets': [],
		'widgetOptions': {}
	};

	if($table.hasClass('columns-sortable')) {
		options.selectorHeaders.push('> thead > tr > th.sortable');
		options.selectorHeaders.push('> thead > tr > td.sortable');
		options.onRenderHeader = function() {
			// Fixes text that wraps when it doesn't necessarily need to.
			$(this).find('div').css('width', '100%');
		};
	}
	if($table.hasClass('columns-filterable')) {
		options.selectorHeaders.push('> thead > tr > th.filterable');
		options.selectorHeaders.push('> thead > tr > td.filterable');
		options.widgets.push('filter');
		options.widgetOptions.filter_ignoreCase = true;
		options.widgetOptions.filter_searchDelay = 100;
		options.widgetOptions.filter_childRows = true;
	}
	if($table.hasClass('headers-sticky')) {
		options.widgets.push('stickyHeaders');
		options.widgetOptions.stickyHeaders_attachTo = $(sticky_header_parent);
	}

	// Convert array to comma-separated string.
	options.selectorHeaders = options.selectorHeaders.join(',');

	$table.tablesorter(options);
}

$(document).ready(function() {

	/***************************************************************************
	 *     DROP-DOWN NAVIGATION                                                *
	 **************************************************************************/

	// Bind to mouse clicks/touches on logo (for mobile only)
	$(document.body).on('click hover touchstart', '#header .logo-container .logo', function(event) {
		var $header = $('#header');
		var header_width = $header.outerWidth(true);

		if(header_width <= 640) {
			var $logo = $(this);
			var $navigation = $logo.closest('.navigation');
			var $nav_containers = $navigation.children('.nav-container');
			if($nav_containers.is(':visible')) {
				$header.css('bottom', '');
				$nav_containers.slideUp();
			} else {
				$header.css('bottom', '0');
				$nav_containers.slideDown();
			}
			return false;
		}
	});

	// Bind to mouse over/touch events on primary navigation.
	$(document.body).on('mouseenter click touchstart', '#header > .navigation > .nav-container > .nav', function(event) {
		var $header = $('#header');
		var header_width = $header.outerWidth(true);

		if(header_width > 640 || event.type == 'touchstart' || event.type == 'click') {
			var $nav = $(this); // Grab primary navigation item

			// Hide all other drop-downs (drop downs not automatically closing on iOS higher def tablets)
			var $nav_container = $nav.closest('.navigation'); // Grab the container which contains navigation elements.
			$nav_container.children('.nav-container').children('.nav').not($nav).children('.nav-container').slideUp();

			var nav_position = $nav.position(); // Grab the x/y coords of the navigation item.
			var x_offset = nav_position.left;
			var $dropdown = $nav.children('.nav-container').css('left', x_offset).css('z-index', 10000);
			$dropdown.finish().slideDown(200);
		}
	});

	// Bind to mouse-leave.
	$(document.body).on('mouseleave', '#header > .navigation .nav-container > .nav', function(event) {
		var $header = $('#header');
		var header_width = $header.outerWidth(true);

		if(header_width > 640) {
			var $nav = $(this);
			var $dropdown = $nav.children('.nav-container');
			$dropdown.finish().slideUp(150);
		}
	});

	// Bind to mouse clicks.
	$(document.body).on('click', '#header .breadcrumbs-container > .collapse-navigation-container > .collapse-navigation', function(event) {
		var $icon = $(this);
		var $navigation = $('#header > .navigation');
		var $breadcrumbs = $('#header > .breadcrumbs-container');
		var breadcrumbs_height = $breadcrumbs.outerHeight(true);
		var $body = $('#body');
		if($icon.hasClass('fa-arrow-circle-up')) {
			$navigation.finish().slideUp({
				'progress': function(animation, progress, remaining_ms) {
					var navigation_height = $navigation.outerHeight(true);
					$body.css('margin-top', navigation_height + breadcrumbs_height);
				}
			});
			$icon.removeClass('fa-arrow-circle-up').addClass('fa-arrow-circle-down');
		} else {
			$navigation.finish().slideDown({
				'progress': function(animation, progress, remaining_ms) {
					var navigation_height = $navigation.outerHeight(true);
					$body.css('margin-top', navigation_height + breadcrumbs_height);
				}
			});
			$icon.removeClass('fa-arrow-circle-down').addClass('fa-arrow-circle-up');
		}
	});

	/***************************************************************************
	 *     TABLES: FILTERABLE, SORTABLE, STICKY                                *
	 **************************************************************************/

	var $table_columns_drag = $('table.columns-draggable');
	$table_columns_drag.dragtable({
		//'placeholder': 'dragtable-col-placeholder test3',
		'items': 'thead th:not(.notdraggable):not(:has(.dragtable-drag-handle)), .dragtable-drag-handle, thead th > *',
		//'appendTarget': $(this).parent(),
		//'scroll': true
	});

	$.each($([
		'table.columns-filterable',
		'table.columns-sortable',
		'table.headers-sticky'
	]), function(index, table) {
		var $table = $(table);
		var options = {
			'selectorHeaders': [],
			'widgets': [],
			'widgetOptions': {}
		};

		if($table.hasClass('columns-sortable')) {
			options.selectorHeaders.push('> thead > tr > th.sortable');
			options.selectorHeaders.push('> thead > tr > td.sortable');
			options.onRenderHeader = function() {
				// Fixes text that wraps when it doesn't necessarily need to.
				$(this).find('div').css('width', '100%');
			};
		}
		if($table.hasClass('columns-filterable')) {
			options.selectorHeaders.push('> thead > tr > th.filterable');
			options.selectorHeaders.push('> thead > tr > td.filterable');
			options.widgets.push('filter');
			options.widgetOptions.filter_ignoreCase = true;
			options.widgetOptions.filter_searchDelay = 100;
			options.widgetOptions.filter_childRows = true;
		}
		if($table.hasClass('headers-sticky')) {
			options.widgets.push('stickyHeaders');
			options.widgetOptions.stickyHeaders_attachTo = $('#body');
		}

		// Convert array to comma-separated string.
		options.selectorHeaders = options.selectorHeaders.join(',');

		$table.tablesorter(options);
	});

	var $table_rows_navigate = $('table.rows-navigate').find('> tbody > tr');
	$table_rows_navigate.on('click', function(event) {
		var $tr = $(this);
		var navigate_to = $tr.attr('navigate-to');
		window.location = navigate_to;
	});

	/**
	 * Bind to Shipping Icon clicks
	 */
	$(document).off('click', '.content-po-status-shipped');
	$(document).on('click', '.content-po-status-shipped', function(event) {
		var $shipping_status_container = $(this).closest('.content-po-status');
		var $so_number_container = $shipping_status_container.siblings('.content-sales-order-number');
		var so_number = $so_number_container.text().trim();
		var post_data = {
			'so-number': so_number
		};
		var url = BASE_URI + '/dashboard/sales-order-status/shipping-details';
		activateOverlayZ(url, post_data);
	});
});

function numberFormat(x) {
    return isNaN(x)?"":x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function createOverlay(body_css, url, data, method, response_format, callback) {
	if(typeof body_css !== 'object') {
		body_css = {};
	}

	var $overlay = $.overlayz({
		'css': {
			'container': {
				'background-color': 'rgba(0, 0, 0, 0.6)'
			},
			'cell': {
				'position': 'relative'
			},
			'body': $.extend({
				'position': 'absolute',
				//'width': 'auto',
				//'height': 'auto',
				'top': '10%',
				'right': '10%',
				'bottom': '10%',
				'left': '10%',
				'padding': '16px',
				'margin': 'auto',
				'overflow': 'auto'
			}, body_css),
			'close': {
				'position': 'absolute',
				'top': '10%',
				'right': '10%',
				'margin-top': '20px',
				'margin-right': '30px'
			}
		}
	});
	$overlay.fadeIn('fast');
	var $overlay_body = $overlay.find('.overlayz-body');

	$overlay_body.append($loading_prototype.clone());

	if(typeof url !== 'undefined') {
		if(typeof data === 'undefined') {
			data = {};
		}
		if(typeof method === 'undefined') {
			method = 'GET';
		}
		if(typeof response_format === 'undefined') {
			response_format = 'json';
		}
		var payload = {
			'url': url,
			'data': data,
			'method': method,
			'dataType': response_format,
			'success': function(response) {
				if(response_format.toLowerCase() === 'html') {
					$overlay_body.html(response);
				} else if(typeof callback !== 'undefined') {
					callback(response, $overlay_body);
				} else {
					$overlay_body.html(response.html);
				}
			}
		};
		if(typeof data === 'FormData') {
			payload.processData = false;
			payload.contentType = false;
			payload.enctype = 'multipart/form-data';
		}
		$.ajax(payload);
	}

	return $overlay;
}

var ajax_loading_styles = {
	'body': {
		'padding-top': '50px',
		'max-width': '200px',
		'max-height': '150px',
		'border-radius': '200px'
	}
};

function bindTableSorter(table) {
	var $table = $(table);
	var options = {
		'selectorHeaders': [],
		'widgets': [],
		'widgetOptions': {}
	};

	if($table.hasClass('columns-sortable')) {
		options.selectorHeaders.push('> thead > tr > th.sortable');
		options.selectorHeaders.push('> thead > tr > td.sortable');
		options.onRenderHeader = function() {
			// Fixes text that wraps when it doesn't necessarily need to.
			$(this).find('div').css('width', '100%');
		};
	}
	if($table.hasClass('columns-filterable')) {
		options.selectorHeaders.push('> thead > tr > th.filterable');
		options.selectorHeaders.push('> thead > tr > td.filterable');
		options.widgets.push('filter');
		options.widgetOptions.filter_ignoreCase = true;
		options.widgetOptions.filter_searchDelay = 100;
		options.widgetOptions.filter_childRows = true;
	}
	if($table.hasClass('headers-sticky')) {
		options.widgets.push('stickyHeaders');
		options.widgetOptions.stickyHeaders_attachTo = $('#body');
	}

	// Convert array to comma-separated string.
	options.selectorHeaders = options.selectorHeaders.join(',');

	$table.tablesorter(options);
}

function get_circular_progress_bar(container, value){

	/*
	Create a circular progress bar with an initial value inside
	the container. Returns a ``ProgressBar`` - call ``.animate(value)``
	to update the progress bar value.

		Arguments:
			``container`` is the container css-selector.
				This should be an empty div - sizing can
				be set by placing ``container`` within a parent
				with the appropriate sizing styles.

			``values`` is a float between 0.0 and 1.0.

		TODO: Add default arguments to support additional flexibility.
	*/


	// Create the bar itself.
	var bar = new ProgressBar.Circle(container, {
		color: '#aaa',
		strokeWidth: 3,
		trailWidth: 2,
		easing: 'easeInOut',
		duration: 1400,
		text: {
			autoStyleContainer: true
		},
		from: { color: '#aaa', width: 3 },
		to: { color: '#333', width: 4 },
		// Set default step function for all animate calls
		step: function(state, circle) {
			circle.path.setAttribute('stroke', state.color);
			circle.path.setAttribute('stroke-width', state.width);

			var value = Math.round(circle.value() * 100);
			if (value === 0) {
				circle.setText('0%');
			} else {
				circle.setText(value+'%');
			}

		}
	})

	// Set the font size an initial value.
	bar.text.style.fontSize = '3vw';
	bar.animate(value); // Percentage complete (0.0-1.0)

	return bar

}

function produce_file_upload_overlay(file_type, id){

	// Produce a file-upload overlay.

	// The URL of the overlay.
	var url = '/files/file-upload'

	// The data for the overlay.
	var data = {
		'type' : file_type,
		'id' : id
	}

	// Activate the overlay.
	var layz = activateOverlayZ(url, data)

	// Set the CSS.
	layz.find('.overlayz-body').css({
		'width' : '605px',
		'height' : '405px'
	})


}

function sleep(miliseconds){

	// An actual sleep function.
	// This will only work in async functions
	// that is, function declarations prefixed with `async`
	// ie: async function myfunc(foo, bar){..}

	return new Promise(resolve => setTimeout(resolve, miliseconds));

}

async function upload_file(file, type, assoc_id, callback){

	// Upload a file to SQL Server.

	var reader = new FileReader()
	reader.readAsBinaryString(file)

	while(true){

		// Wait until the file has been read.
		await sleep(1000)
		if(reader.readyState==2){
			break
		}

	}

	// Get the file data.
	var data = {
		'filename' : file.name,
		'type' : type,
		'assoc_id' : assoc_id,
		'mimetype' : file.type,
		'data' : reader.result
	}

	var xhr = new XMLHttpRequest()
	xhr.open("POST", 'http://10.1.247.195/files/upload-file', false)

	// Support callbacks.
	xhr.onreadystatechange = function(){
		if(xhr.readyState === 4 && xhr.status === 200) {
			callback()
		}
	}

	var xdata = new FormData()
	xdata.append('data', file)
	xdata.append('filename',file.name)
	xdata.append('type', type)
	xdata.append('assoc_id', assoc_id)
	xdata.append('mimetype',file.type)

	// xhr.send(JSON.stringify(data))
	xhr.send(xdata)

}

function download_file(file_id){

	// Download a file from SQL Server.

	// Define the payload for the request.
	var payload = {'file_id':file_id}

	// Create the XHR object.
	var xhr = new XMLHttpRequest()

	// Define the onload callback.
	xhr.onload = function(){

		// Get the file details.
		rsp = JSON.parse(xhr.response)
		filename = rsp.filename
		mimetype = rsp.mimetype
		contents = atob(rsp.data)

		// Convert non-text file contents.
		if(!mimetype.includes('text')){

			// Create an array of charcodes.
			c = contents.length
			var cc_array = new Array(c)
			for(var i=0; i<c; i++){
				cc_array[i] = contents.charCodeAt(i)
			}

			// Create a byte array.
			contents = new Uint8Array(cc_array)

		}

		// Create the blob.
		var blob = new Blob([contents], {type:mimetype})

		// Create a DOM element.
		var a = document.createElement('a')
		document.body.appendChild(a)

		// Get an object URL for the blob.
		var ourl = window.URL.createObjectURL(blob)

		// Create a link and click it to initiate the download.
		a.href = ourl
		a.download = filename
		a.click()

		// Revoke the object URL to avoid memory leaks.
		window.URL.revokeObjectURL(ourl)

	}

	// Send the request.
	xhr.open('POST', 'http://10.1.247.195/files/download-related-file', false)
	xhr.send(JSON.stringify(payload))

}