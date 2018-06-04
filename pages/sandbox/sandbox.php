<?php

$session->ensureLogin();

$args = array(
	'title' => 'Dev Sandbox',
	'breadcrumbs' => array(
		'SecreT Place' => BASE_URI . '/sandbox'
	),
	'body-class' => 'padded'
);

// Handle AJAX.
if(isset($_POST['action'])){

	// Assume an alias role.
	if($_POST['action']=='assume-alias'){

		//global $session;
		Session::loginAlias($_POST['alias-id']);

		print json_encode(array(
			'success' => true,
		));

		return;

	}

}

Template::Render('header', $args, 'account');
?>

<!-- <script type="text/javascript" src="/interface/js/bootstrap.min.js"></script>
<link href="https://fonts.googleapis.com/css?family=Raleway:400,300,600,800,900" rel="stylesheet" type="text/css">
<style type="text/css">

	@font-face {
		font-family: 'noto';
		src: url('http://dev.maven.local/interface/fonts/NotoSans-Regular.ttf');
	}

	#container {
		height : 250px;
		width: 250px;
		padding-bottom: 15px;
		font-family: noto;
	}

	#container2 {
		height : 250px;
		width: 250px;
		padding-bottom: 15px;
		font-family: noto;
	}
	#update-btn {
		margin-bottom: 10px;
	}
	#update-btn2 {
		margin-bottom: 10px;
	}
	#hidden {
		display:none;
	}
	iframe {
		border: 1px solid red;
	}
</style>

<?php
if($session->alias==true){

	?>
	<h1>ALIAS WORKED</h1>
	<?php

}
?>

<div id="hidden">
	<div id="alias-login-container" class="container-fluid">
		<div class="span4 pull-left">
			<input id="alias-login-input" type="text">
			<button id="alias-login-button" class="btn btn-primary btn-small">Assume Alias</button>
		</div>	
	</div>
	<div class="container">
		<div class="btn-group">
			<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
				FOO
				<span class="caret"></span>
			</a>
			<ul class="dropdown-menu">
				<li><a href="#">1</a></li>
				<li><a href="#">2</a></li>
				<li><a href="#">3</a></li>
				<li><a href="#">4</a></li>
			</ul>
		</div>
	</div>
</div>

<div id="test-1">
	<div id="container">
		<div id="test"></div>
	</div>

	<div class="span4 pull-left">
		<input id="new-value-input" type="text">
		<button id="update-btn" class="btn btn-primary btn-small">Update</button>
	</div>	
</div>

<div id="test-2">
	<div id="container2">
		<div id="test2"></div>
	</div>

	<div class="span4 pull-left">
		<input id="new-value-input" type="text">
		<button id="update-btn2" class="btn btn-primary btn-small">Update</button>
	</div>	
</div> -->

<style type="text/css">
	#submit-file-search {
		margin-bottom: 10px;
	}
	#download-button {
		margin-bottom: 10px;
		margin-left: 5px;
	}
</style>

<div class="container-fluid">
	<h3>Upload</h3>
	<button id="overlay-test-button" type="button" class="btn btn-primary">Produce Overlay</button>
</div>

<hr>

<div class="container-fluid">
	<h3>Download</h3>
	<form>
	<fieldset>
		<legend>Find/Download Files</legend>
		<input id="search-input" type="text" placeholder="SO">
		<button id="submit-file-search" type="submit" class="btn">Search</button>
		<div id="file-select-container"></div>
	</fieldset>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		
		// function load_progress_bar(){

		// 	// Create a circular progress bar.

		// 	bar = get_circular_progress_bar('#test', 0.5)

		// }

		// function load_progress_bar2(){

		// 	// Create a second progress bar.

		// 	path = new ProgressBar.Path('test2', {easing: 'easeInOut'})

		// }

		// function do_update_progress(value){

		// 	// Set the progress bar value.
		// 	bar.animate(value)

		// }

		// function update_progress(){

		// 	// Get the input value.
		// 	var $input = $('#new-value-input')
		// 	var value = parseInt($input.val())

		// 	// Allow integer submissions - translate to decimal.
		// 	if(value>=1){
		// 		value/=100
		// 	}

		// 	// If the value is negative, set it to 0%.
		// 	if(value < 0){
		// 		value = 0
		// 	}

		// 	// If the translated value is still too large, set it to 100%.
		// 	if(value>1){
		// 		value = 1
		// 	}

		// 	// Set the value.
		// 	bar.animate(value)

		// }

		// function assume_alias(){

		// 	// Assume a user's login role.

		// 	// Get the user ID.
		// 	var $input = $('#alias-login-input')
		// 	var alias_id = $input.val()

		// 	// The data to POST.
		// 	var data = {
		// 		'action' : 'assume-alias',
		// 		'alias-id' : alias_id
		// 	}

		// 	// Log in as another user.
		// 	$.ajax({
		// 		'url' : '',
		// 		'method' : 'POST',
		// 		'dataType' : 'JSON',
		// 		'data' : data,
		// 		'success' : function(rsp){
		// 			console.log('success')
		// 			console.log(rsp)

		// 			window.location.href = '/dashboard'
		// 		},
		// 		'error' : function(rsp){
		// 			console.log('error')
		// 			console.log(rsp)
		// 		}
		// 	})

		// }

		// // Create the progress bar.
		// load_progress_bar()
		// load_progress_bar2()

		// // Enable the update button.
		// $(document).on('click', '#update-btn', update_progress)

		// // Enable assuming alias roles.
		// $(document).on('click', '#alias-login-button', assume_alias)


		function test_overlay(){

			// Test the file-upload overlay.
			
			var type = 'so'
			var id = 12345

			produce_file_upload_overlay(type, id)

		}

		function get_files(event){

			// Search for file related to the search value.

			// Prevent form submission.
			event.preventDefault()

			// Get the search value.
			var $input = $('#search-input')
			var value = $input.val()

			// The data for the API call.w
			var data = {
				'assoc-id' : value,
				'type' : 'so'
			}

			// Get the related files.
			$.ajax({
				'url' : 'http://10.1.247.195/files/get-file-list',
				'method' : 'GET',
				'dataType' : 'JSONP',
				'data' : data,
				'success' : function(rsp){

					// Create a select for the files.
					var $select = $('<select>',{
						'id' : 'file-select'
					})
					$select.append($('<option>',{
						'value' : '',
						'text' : '-- Select File --'
					}))

					// Create an option for each file.
					var files = rsp.files
					$.each(files, function(idx, file){

						var $option = $('<option>',{
							'value' : file.file_id,
							'text' : file.filename
						})
						$select.append($option)

					})

					// Replace any existing select.
					var $container = $('#file-select-container')
					$container.empty()
					$container.html($select)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function enable_download(){

			// When a file is selected display a download button.

			// Remove the button.
			var $container = $('#file-select-container')
			$container.find('#download-button').remove()

			var $button = $('<button>',{
				'id' : 'download-button',
				'class' : 'btn',
				'text' : 'Download'
			})

			// Get the currently selected file.
			var $select = $('#file-select')
			var file_id = $select.val()

			// Add the button.
			if(file_id!=''){
				$container.append($button)
			}

		}

		function do_download_file(event){

			// Actually download the file.

			// Prevent form submission.
			event.preventDefault()

			// Get the file ID.
			var $select = $('#file-select')
			var file_id = $select.val()

			// Test the global JS to support this.
			download_file(file_id)

		}

		// Enable the overlay button.
		$(document).on('click', '#overlay-test-button', test_overlay)

		// Enable search button.
		$(document).on('click', '#submit-file-search', get_files)

		// Enable download button.
		$(document).on('change', '#file-select', enable_download)

		// Enable downloads.
		$(document).on('click', '#download-button', do_download_file)

	})
</script>