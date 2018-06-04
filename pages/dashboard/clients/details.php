<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_overview = $db->query("
	SELECT
		LTRIM(RTRIM(arcust.custno)) AS custno,
		LTRIM(RTRIM(arcust.company)) AS company,
		LTRIM(RTRIM(arcust.oob)) AS oob,
		LTRIM(RTRIM(arcust.oob_notes)) AS oob_notes
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
");
$overview = $grab_overview->fetch();
unset($grab_overview);

ob_start(); // Start loading output into buffer.
?>
<style type="text/css">
	#client-details-container .log-call {
		display:inline-block;
		margin-left:12px;
		color:#fff;
	}
	#client-logo-container {
		margin-left: 0px;
		margin-right: 7px;
		align-items: center;
		align-content: center;
		display: flex;
	}

	#file-actions-container {
		//border: 1px solid blue;
	}
	#related-file-select {
		margin-top: 5px;
	}
	#download-button {
		margin-left: 5px;
		margin-bottom: 6px;
	}
	#upload-overlay-button {
		margin-left: 5px;
		margin-bottom: 6px;
	}
	#file-download-container {
		margin-left: -44px;
	}


</style>

<div id="client-details-container" custno="<?php print htmlentities($overview['custno'], ENT_QUOTES);?>" custno="<?php print htmlentities($overview['custno'], ENT_QUOTES);?>">
	<div id="client-logo-container" class="span1"></div>
	<h2 style="display:inline-block;">
		<?php print htmlentities($overview['company']);?>
		(<?php print htmlentities($overview['custno']);?>)
	</h2>
	<button type="button" class="log-call btn btn-primary overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/clients/log-call" overlayz-data="<?php print htmlentities(json_encode(['custno' => $overview['custno']]), ENT_QUOTES);?>">Log Call</button>




	<div id="file-actions-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<div id="init-file-upload-container" class="container span2">
			<button id="upload-overlay-button" class="btn btn-small">Upload File</button>
		</div>
		<div id="file-download-container" class="container span4"></div>
	</div>




	<?php
	if($overview['oob'] == 1) {
		?>
		<div class="notification-container notification notification-error">
			<h4>OUT OF BUSINESS</h4>
			<span><?php print htmlentities($overview['oob_notes']);?>
		</div>
		<?php
	}
	?>
	<div class="tabs">
		<div class="tab" page="overview">Overview</div>
		<div class="tab" page="addresses">Addresses</div>
		<div class="tab" page="purchased">Purchased</div>
		<div class="tab" page="orders">Open Orders</div>
		<div class="tab" page="invoices">Open AR</div>
		<div class="tab" page="shipments">Shipments</div>
		<div class="tab" page="routing">Routing/Packaging</div>
		<div class="tab" page="sa-bo">SA & BO</div>
		<div class="tab" page="quotes">Quotes</div>
		<div class="tab" page="contacts">Contacts</div>
		<div class="tab" page="payments">Payments</div>
	</div>
	<div class="tab-content" id="client-details-page"></div>
</div>

<script type="text/javascript">
	var client_details_tab_xhr;
	$(document).off('click', '#client-details-container .tabs .tab');
	$(document).on('click', '#client-details-container .tabs .tab', function(event) {
		var $tab = $(this);
		var $tabs = $tab.closest('.tabs');
		$tabs.find('.tab.active').removeClass('active');
		$tab.addClass('active');
		var $page_container = $('#client-details-page');
		var page = $tab.attr('page');

		var data = {
			'custno': '<?php print htmlentities($_POST['custno'], ENT_QUOTES);?>'
		};

		var post_data = $tab.attr('data');
		if(post_data) {
			post_data = JSON.parse(post_data);
			data = $.extend(data, post_data);
		}

		// Replace page contents w/ loading icon.
		$page_container.html('<div style="width:150px;margin:auto;padding-top:120px;"><img src="/interface/images/ajax-loading.gif" /></div>');

		// Cancel working AJAX request (if present,) to preventtab body skipping.
		if(client_details_tab_xhr) {
			client_details_tab_xhr.abort();
		}

		client_details_tab_xhr = $.ajax({
			'url': BASE_URI + '/dashboard/clients/details/' + page,
			'data': data,
			'method': 'POST',
			'dataType': 'html',
			'success': function(response_html) {
				$page_container.html(response_html);
			}
		});
	});


	function load_logo(){

		// Get the client logo.

		// The customer number.
		var custno = '<?php print $_POST['custno'] ?>'

		// Get the image.
		$.ajax({
			'url' : 'http://10.1.247.195/get-client-logo?custno='+custno,
			'method' : 'GET',
			'dataType' : 'JSONP',
			'success' : function(rsp){

				// Get the logo value.
				var logo = rsp.logo

				// Get the logo container.
				var $logo_container = $('#client-logo-container')

				// If the logo isn't available, remove the container.
				if(logo==null){
					$logo_container.remove()
					return;
				}

				// Create the image tag.
				var $img = $('<img>',{
					'class' : 'client-logo',
					'src' : 'data:image/jpg;base64,'+logo
				})

				// Put the image in the container.
				$logo_container.html($img)

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function get_related_files(){

		// Get the related files for the Client.

		// Get the Client.
		var $div = $('#client-details-container')
		var client = $div.attr('custno')

		// The data required for the overlay.
		var data = {
			'type' : 'client',
			'assoc-id' : client
		}

		// Get the files.
		$.ajax({
			'url' : 'http://10.1.247.195/files/get-file-list',
			'method' : 'GET',
			'dataType' : 'JSONP',
			'data' : data,
			'success' : function(rsp){

				// Create a select for the files.
				var $select = $('<select>',{
					'id' : 'related-file-select'
				})
				$select.append($('<option>',{
					'value' : '',
					'text' : '-- Select File --'
				}))

				// Create an option for each file.
				var files = rsp.files
				$.each(files, function(idx, file){

					var $option = $('<option>', {
						'value' : file.file_id,
						'text' : file.filename
					})
					$select.append($option)

				})

				// Replace any existing select.
				var $container = $('#file-download-container')
				$container.empty()
				$container.html($select)

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function upload_file_overlay(){

		// Produce an overlay for file uploads.

		// Get the Client.
		var $div = $('#client-details-container')
		var client = $div.attr('custno')

		produce_file_upload_overlay('client', client)

	}

	function enable_download(){

		// Create and display a download button when a file is selected.

		// Remove a button if one exists.
		var $container = $('#file-download-container')
		$container.find('#download-button').remove()

		// Get the currently selected file.
		var $select = $('#related-file-select')
		var file_id = $select.val()

		// Add the button.
		var $button = $('<button>',{
			'id' : 'download-button',
			'class' : 'btn btn-small',
			'text' : 'Download'
		})

		if(file_id!=''){
			$container.append($button)
		}

	}

	function do_download_file(){

		// Download a related file.

		// Get the file ID.
		var $select = $('#related-file-select')
		var file_id = $select.val()

		// Do the download.
		download_file(file_id)

	}

	// Force loading of first tab.
	$('#client-details-container .tabs .tab').first().trigger('click');

	// Load the logo.
	load_logo()

	// Get any files related to the Client.
	get_related_files()

	// Support file uploads.
	$(document).off('click', '#upload-overlay-button')
	$(document).on('click', '#upload-overlay-button', upload_file_overlay)

	// Support enabling file downloads.
	$(document).off('change', '#related-file-select')
	$(document).on('change', '#related-file-select', enable_download)

	// Support file downloads.
	$(document).off('click', '#download-button')
	$(document).on('click', '#download-button', do_download_file)

</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
