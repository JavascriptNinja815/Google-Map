<?php

ob_start(); // Start loading output into buffer.

?>

<div id="image-container">
	<img id="item-image">
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function get_image_source(){

			var sono = '<?php print $_POST['sono'] ?>'
			var item = '<?php print $_POST['item'] ?>'
			var company_id = '<?php print $_POST['company_id'] ?>'

			// Create the URL.
			var url = 'http://10.1.247.195/get-shipping-record?sono='+sono+'&item='+item+'&company_id='+company_id+'&callback=jsonp'

			// Make the request.
			$.ajax({
				'url' : url,
				'method' : 'GET',
				'async' : false,
				'dataType' : 'jsonp',
				'success' : set_image_source,
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function set_image_source(rsp){

			// Handle the XHR request on success.

			// Get the image data.
			var img_bin = rsp.image

			// Get the image container.
			var $img = $('#item-image')

			// Genreate the data-URI.
			var src = 'data:image/jpg;base64,'+img_bin

			// Set the image source.
			$img.attr('src', src)

		}

		// Load the image
		get_image_source()

	})
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));
