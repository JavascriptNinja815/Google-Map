<?php

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	[hidden] {
		display: none !important;
	}

	@font-face {
		font-family: 'noto';
		src: url('http://dev.maven.local/interface/fonts/NotoSans-Regular.ttf');
	}

	.noto-text {
		font-family: noto;
		font-size: 25px;
		color: gray;
	}

	#file-upload-container {
		width: 600px;
		height: 400px;
		position: relative;
	}
	#file-upload-area {
		height: 100%;
		width: 100%;
		border: 1px dashed black;
		align-content: center;
		align-items: center;
		display: flex;
	}
	#text-container {
		text-align: center;
		width: 50%;
		margin: 0 auto;
	}
	#browse-button-container {
		position: absolute;
		bottom: 0px;
		left: 0px;
	}
	#submit-button-container {
		position: absolute;
		bottom: 0px;
		right: 4px;
	}

	.overlayz-body {
		align-items: center;
		align-content: center;
		display: flex !important;
	}

</style>

<div id="file-upload-container" class="container">
	
	<div id="file-upload-area" class="container">
		<div id="text-container" class="noto-text">
			<div>Drop File Here</div>
		</div>
	</div>

</div>

<script type="text/javascript">

	function prevent_default_propogation(event){

		// Prevent default behavior.
		event.preventDefault()
		event.stopPropagation()

	}

	// Handle drag over and drag leave events.
	function dragover(e){prevent_default_propogation(e)}
	function dragleave(e){prevent_default_propogation(e)}

	function drop(e){

		// Handle drop events.

		// Prevent default browser behavior on drop.
		prevent_default_propogation(e)

		// Get the dropped file.
		var file = e.originalEvent.dataTransfer.files[0]
		var type = '<?php print htmlentities($_POST['type']) ?>'
		var assoc_id = '<?php print htmlentities($_POST['id']) ?>'

		// Show a loading indicator.
		var $container = $('#file-upload-container')
		var $upload_area = $('#file-upload-area')
		var $loading = get_loading_indicator()
		$upload_area.hide()
		$container.append($loading)

		upload_file(file, type, assoc_id, stop_loading)

	}

	function stop_loading(){

		// Stop showing the loading indicator.

		// Get the necessary containers.
		var $container = $('#file-upload-container')
		var $upload_area = $('#file-upload-area')

		// Get the loading indicator.
		var $loading = $('#file-upload-indicator')

		// Remove the indicator and show the file upload area.
		$loading.remove()
		$upload_area.show()

	}

	function get_loading_indicator(){

		// Get a loading indicator.

		// Create the container.
		var $container = $('<div>',{
			'id' : 'file-upload-indicator',
			'class' : 'ajax-loading-container',
			'style' : 'width:200px;text-align:center;margin:auto;'
		})

		// Create the image.
		var $img = $('<img>',{
			'src' : '/interface/images/ajax-loading.gif'
		})

		// Add the image to the container and return the container.
		$container.append($img)
		return $container

	}

	// Make sure bindings are reset for cases where the overlay is produced
	// multiple times.
	$(document).off('dragover', '#file-upload-container')
	$(document).off('dragleave', '#file-upload-container')
	$(document).off('drop', '#file-upload-container')

	// Support drag-n-drop.
	$(document).on('dragover', '#file-upload-container', dragover)
	$(document).on('dragleave', '#file-upload-container', dragleave)
	$(document).on('drop', '#file-upload-container', drop)


</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);