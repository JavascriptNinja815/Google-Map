<?php

$session->ensureLogin();

$grab_item = $db->query("
	SELECT
		LTRIM(RTRIM(icitem.item)) AS item,
		LTRIM(RTRIM(icitem.itmdesc)) AS itmdesc,
		LTRIM(RTRIM(icitem.itmdes2)) AS itmdes2
	FROM
		" . DB_SCHEMA_ERP . ".icitem
	WHERE
		UPPER(RTRIM(LTRIM(icitem.item))) = " . $db->quote(strtoupper(trim($_REQUEST['item-number']))) . "
		OR
		UPPER(RTRIM(LTRIM(icitem.itmdesc))) = " . $db->quote(strtoupper(trim($_REQUEST['item-number']))) . "
");
$item = $grab_item->fetch();

$grab_pricingpresent = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".price_items
	INNER JOIN
		" . DB_SCHEMA_ERP . ".price_quantities
		ON
		price_quantities.price_item_id = price_items.price_item_id
");
$pricing_present = $grab_pricingpresent->fetch();
$pricing_present = $pricing_present['count'] ? True : False;

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$random_id = 'item-details-' . rand(10000, 99999);
?>

<style type="text/css">
	.item-details-container .action-edit {
		font-size:20px;
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

<div>
	<?php
	if($item === False) {
		?><h2>Item does not appear to exist.</h2><?php
	} else {
		?>
		<div id="<?php print $random_id;?>" class="item-details-container" item="<?php print htmlentities($item['item'], ENT_QUOTES);?>">
			<h2 style="display:inline-block;">
				Item #<?php print htmlentities($item['item']);?>
				<span style="font-weight:normal;">| <?php print htmlentities($item['itmdesc']);?></span>
			</h2>


	<div id="file-actions-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<div id="init-file-upload-container" class="container span2">
			<button id="upload-overlay-button" class="btn btn-small">Upload File</button>
		</div>
		<div id="file-download-container" class="container span4"></div>
	</div>



			<div class="item-description-container">
				<?php
				if($session->hasRole('Administrator')) {
					?><i class="fa fa-pencil edit-item-description action action-edit"></i><?php
				}
				?>
				<span class="item-description"><?php print htmlentities($item['itmdes2']);?></span>
				<br />
				&nbsp;
			</div>
			<div class="tabs">
				<div class="tab" page="overview">Overview</div>
				<div class="tab" page="whereused">Where Used</div>
				<?php
				if($pricing_present) {
					?><div class="tab" page="pricing">Pricing</div><?php
				}
				?>
				<div class="tab" page="suppliers">Suppliers</div>
				<div class="tab" page="shipments">Shipments</div>
				<div class="tab" page="get-usage">Usage</div>
			</div>
			<div class="tab-content item-details-page"></div>
		</div>
		<?php
	}
	?>
</div>

<script type="text/javascript">
	var item_details_tab_xhr;
	$(document).off('click', '#<?php print $random_id;?> .tabs .tab');
	$(document).on('click', '#<?php print $random_id;?> .tabs .tab', function(event) {
		var $tab = $(this);
		var $tabs = $tab.closest('.tabs');
		$tabs.find('.tab.active').removeClass('active');
		$tab.addClass('active');

		var $item_details_container = $tabs.closest('.item-details-container');

		var $page_container = $item_details_container.find('.item-details-page');
		var page = $tab.attr('page');

		var data = {
			'item-number': '<?php print htmlentities($item['item'], ENT_QUOTES);?>'
		};

		// Replace page contents w/ loading icon.
		$page_container.html('<div style="width:150px;margin:auto;padding-top:120px;"><img src="/interface/images/ajax-loading.gif" /></div>');

		// Cancel working AJAX request (if present,) to preventtab body skipping.
		if(item_details_tab_xhr) {
			item_details_tab_xhr.abort();
		}

		item_details_tab_xhr = $.ajax({
			'url': BASE_URI + '/dashboard/inventory/item-details/' + page,
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				if(response.success) {
					$page_container.html(response.html);
				}
				
			}
		});
	});

	// Force loading of first tab.
	$('#<?php print $random_id;?> .tabs .tab').first().trigger('click');
	
	/**
	 * Bind to clicks on "Edit Description" icon.
	 */
	$(document).off('click', '.item-details-container .action-edit');
	$(document).on('click', '.item-details-container .action-edit', function(event) {
		var $icon = $(this);
		var $item_details_container = $icon.closest('.item-details-container');
		var $item_description = $item_details_container.find('.item-description');
		var item_description = $item_description.text().trim();
		var new_item_description = prompt('Enter a new description', item_description);
		if(new_item_description) {
			$.ajax({
				'url': BASE_URI + '/dashboard/inventory/item-details/update-description',
				'method': 'POST',
				'data': {
					'item': '<?php print htmlentities($item['item'], ENT_QUOTES);?>',
					'description': new_item_description
				}
			});
			$item_description.text(new_item_description);
		}
	});




	function get_related_files(){

		// Get the related files for the Item.

		// Get the Item.
		var $div = $('.item-details-container')
		var item = $div.attr('item')

		// The data required for the overlay.
		var data = {
			'type' : 'item',
			'assoc-id' : item
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

		// Get the Item.
		var $div = $('.item-details-container')
		var item = $div.attr('item')

		produce_file_upload_overlay('item', item)

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
