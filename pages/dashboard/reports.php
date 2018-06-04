<?php

$session->ensureLogin();

$args = array(
	'title' => 'Search',
	'breadcrumbs' => array(
		'Search' => BASE_URI . '/dashboard/search'
	),
	'body-class' => 'padded'
);

Template::Render('header', $args, 'account');

?>

<!-- Include the bootstrap JavaScript -->
<script type="text/javascript" src="/interface/js/bootstrap.min.js"></script>

<style type="text/css">
	#main-container {
		display:flex;
		height: 100%;
	}
	#menu-container {
		height: 100%;
		//border: 1px solid black;
		border-radius: 5px;
		background-color: #edefefb5;
		min-width: 10%;
	}
	#menu-container li a {
		color: #00000082;
		white-space: nowrap;
	}
	#report-container {
		height: 100%;
		//border: 1px solid green;
		//margin-left: 10px;
		flex-grow: 100;
		overflow: auto;
	}
	#collapse-menu {
		cursor: pointer;
	}
	#show-menu {
		cursor: pointer;
	}
</style>

<div id="main-container">
	<div id="menu-container" class="container-fluid pull-left">
		<div class="row-fluid">
			<div class="pull-left"><h3>Reports:</h3></div>
			<div id="collapse-menu" class="pull-right"><h4><i class="fa fa-chevron-left"></i></h4></div>
		</div>
		<ul class="nav nav-pills nav-stacked">
			<li class="report-item" data-report="address-search"><a href="#">Address Search</a></li>
			<li class="report-item" data-report="under-1k-margins"><a href="#">$1k Margins</a></li>
		</ul>
	</div>
<div id="show-menu" style="display:none;"><h4><i class="fa fa-chevron-right"></i></h4></div>
	<div id="report-container" class="container-fluid pull-left"></div>
</div>


<script type="text/javascript">
	$(document).ready(function(){

		function select_report(){

			// Select the report to run.

			// Deselect all reports in the menu.
			$reports = $('.report-item')
			$reports.removeClass('active')

			// Highlight the selected report.
			$selected = $(this)
			$selected.addClass('active')

			// Get the report type.
			report_type = $selected.attr('data-report')

			// Create a loading container.
			var $loading_container = $('<div/>',{
				'class' : 'ajax-loading-container'
			})

			// Create a container for the report.
			var  $container = $('#report-container')

			// Get the report.
			var url = {
				'address-search' : '/dashboard/reports/address-search',
				'under-1k-margins' : '/dashboard/reports/under-1k-margins'
			}[report_type]

			// Get the report.
			$.ajax({
				'url' : url,
				'method' : 'POST',
				'dataType' : 'JSON',
				'beforeSend' : function(){

					// The loading animation.
					var $loading_animation = $('<img/>', {
						'src' : '/interface/images/ajax-loading.gif'
					})
					
					// Empty the report container.
					$container.empty()

					// Display the loading animation.
					$loading_container.append($loading_animation)
					$container.append($loading_container)

				},
				'success' : function(rsp){

					// Populate the report container with the new report HTML.
					$container.html(rsp.html)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				},
				'complete' : function(){

					// Remove the loading container.
					$loading_container.remove()

				}
			})

		}

		function collapse_menu(){

			// Collapse the reports menu.
			$('#menu-container').hide()

			// Show the icon to display the menu.
			$('#show-menu').show()

		}

		function show_menu(){

			// Show the reports menu.
			$('#menu-container').show()

			// Hide the display icon.
			$('#show-menu').hide()

		}

		// Enable report selection.
		$(document).off('click', '.report-item')
		$(document).on('click', '.report-item', select_report)

		// Support toggling the reports menu.
		$(document).off('click', '#collapse-menu')
		$(document).on('click', '#collapse-menu', collapse_menu)
		$(document).off('click', '#show-menu')
		$(document).on('click', '#show-menu', show_menu)

	})
</script>