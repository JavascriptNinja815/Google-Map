<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		opportunities.login_id,
		opportunities.stage,
		logins.initials,
		opportunities.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = opportunities.login_id
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

$permissions = $session->getPermissions('Sales', 'view-orders');
$editable = False;
if($session->hasRole('Administration')) {
	// Administrator, can edit anything.
	$editable = True;
} else if($opportunity['login_id'] == $session->login['login_id']) {
	// Owner, can edit.
	$editable = True;
} else if($session->hasRole('Sales')) {
	// Has sales role, may have permissions to edit.
	$permissions = $session->getPermissions('Sales', 'edit-orders');
	if(in_array($opportunity['initials'], $permissions)) {
		// Has permission to edit orders of this owner.
		$editable = True;
	}
}

if(substr($opportunity['stage'], 0, 11) === "Prospecting") {
	$stage = 1;
} else if(substr($opportunity['stage'], 0, 9) === "Discovery") {
	$stage = 2;
} else if(substr($opportunity['stage'], 0, 6) === "Quoted") {
	$stage = 3;
} else if(substr($opportunity['stage'], 0, 11) === "Negotiation") {
	$stage = 4;
} else if(substr($opportunity['stage'], 0, 17) === "Stalled") {
	$stage = 5;
} else if(substr($opportunity['stage'], 0, 17) === "Verbal Commitment") {
	$stage = 6;
} else if(substr($opportunity['stage'], 0, 6) === "Closed") {
	$stage = 7;
}

?>

<style type="text/css">
	#opportunity-details-container .edit-button {
		display: inline-block;
		position: absolute;
		top: 19px;
		right: 60px;
		height: 29px;
		border: 1px solid #a00000;
		border-radius: 3px;
		cursor: pointer;
		font-size: 16px;
		font-weight: bold;
		background-color: rgb(204, 0, 0);
		color: #fff;
		text-align: center;
		line-height: 29px;
		padding-left:8px;
		padding-right:8px;
	}
	#opportunity-details-container .stages {
		padding-bottom:12px;
	}
	#opportunity-details-container .stages .stage {
		display:inline-block;
		position:relative;
		background-color:#eee;
		min-width:120px;
		height:24px;
		line-height:24px;
		font-size:12px;
		text-align:center;
		color:#666;
		vertical-align:middle;
		padding-left:10px;
		padding-right:10px;
	}
	#opportunity-details-container .stages .stage::after {
		content:'';
		display:block;  
		position:absolute;
		left:100%;
		top:50%;
		margin-top:-12px;
		width:0;
		height:0;
		border-top:12px solid transparent;
		border-right:12px solid transparent;
		border-bottom:12px solid transparent;
		border-left:12px solid #eee;
		z-index:3;
	}
	#opportunity-details-container .stages .stage::before {
		content:'';
		display:block;  
		position:absolute;
		left:101%;
		top:50%;
		margin-top:-12px;
		width:0;
		height:0;
		border-top:12px solid transparent;
		border-right:12px solid transparent;
		border-bottom:12px solid transparent;
		border-left:12px solid #fff;
		z-index:2;
	}

	#opportunity-details-container .stages .stage.complete {
		background-color:#090;
		color:#fff;
	}
	#opportunity-details-container .stages .stage.complete::after {
		border-left: 10px solid #090;
	}

	#opportunity-details-container .stages .stage.current {
		background-color:#04c;
		color:#fff;
	}
	#opportunity-details-container .stages .stage.current::after {
		border-left: 10px solid #04c;
	}

	#opportunity-details-container .stages .stage.won {
		background-color:#090;
		color:#fff;
	}
	#opportunity-details-container .stages .stage.won::after {
		border-left: 10px solid #090;
	}

	#opportunity-details-container .stages .stage.lost {
		background-color:#a00;
		color:#fff;
	}
	#opportunity-details-container .stages .stage.lost::after {
		border-left: 10px solid #a00;
	}

	#opportunity-details-container .stages .stage.never {
		background-color:#ccc;
	}
	#opportunity-details-container .stages .stage.never::after {
		border-left: 10px solid #ccc;
	}
	#opportunity-details-container .change-stage-container {
		display:inline-block;
		margin-left:8px;
	}
	#opportunity-details-container .change-stage-container i {
		font-size:1.6em;
		vertical-align:middle;
		cursor:pointer;
	}
	#opportunity-details-container .change-stage-container i.fa-arrow-circle-right {
		color:#090;
	}
	#opportunity-details-container .change-stage-container i.fa-times-circle {
		color:#c00;
	}
	#opportunity-details-container .change-stage-container [name="stage"] {
		display:none;
		padding:0;
		margin:0;
		height:28px;
		vertical-align:middle;
		font-size:0.9em;
		width:auto;
		margin-right:6px;
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

<div id="opportunity-details-container" opportunity_id="<?php print htmlentities($opportunity['opportunity_id'], ENT_QUOTES);?>">
	<?php
	if($editable) {
		?><div class="edit-button overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/edit" overlayz-data="<?php print htmlentities(json_encode(['opportunity_id' => $_POST['opportunity_id']]), ENT_QUOTES);?>">Edit</div><?php
	}
	?>

	<h2 style="display:inline-block;">
		<?php print htmlentities($opportunity['name']);?>
		(#<?php print htmlentities($opportunity['opportunity_id']);?>)
	</h2>

	<div class="change-stage-container">
		<select name="stage">
			<optgroup label="1. Discovery">
				<option value="Discovery - Site Survey" <?php print $opportunity['stage'] == 'Discovery - Site Survey' ? 'selected' : Null;?>  >Site Survey</option>
				<option value="Discovery - Gather Info" <?php print $opportunity['stage'] == 'Discovery - Gather Info' ? 'selected' : Null;?>>Gather Info</option>
				<option value="Discovery - Other" <?php print $opportunity['stage'] == 'Discovery - Other' ? 'selected' : Null;?>>Other</option>
			</optgroup>
			<option value="Quoted" <?php print $opportunity['stage'] == 'Quoted' ? 'selected' : Null;?>>2. Quoted</option>
			<optgroup label="3. Negotiation">
				<option value="Negotiation - Samples" <?php print $opportunity['stage'] == 'Negotiation - Samples' ? 'selected' : Null;?>>Samples</option>
				<option value="Negotiation - Price" <?php print $opportunity['stage'] == 'Negotiation - Price' ? 'selected' : Null;?>>Price</option>
				<option value="Negotiation - Delivery" <?php print $opportunity['stage'] == 'Negotiation - Delivery' ? 'selected' : Null;?>>Delivery</option>
				<option value="Negotiation - Other" <?php print $opportunity['stage'] == 'Negotiation - Other' ? 'selected' : Null;?>>Other</option>
			</optgroup>
			<option value="Verbal Commitment" <?php print $opportunity['stage'] == 'Verbal Commitment' ? 'selected' : Null;?>>4. Verbal Commitment</option>
			<optgroup label="5. Won/Lost">
				<option value="Closed Won" <?php print $opportunity['stage'] == 'Closed Won' ? 'selected' : Null;?>>Closed Won</option>
				<option value="Closed - Never Ordered" <?php print $opportunity['stage'] == 'Closed - Never Ordered' ? 'selected' : Null;?>>Closed - Never Ordered</option>
				<option value="Closed Lost" <?php print $opportunity['stage'] == 'Closed Lost' ? 'selected' : Null;?>>Closed Lost</option>
			</optgroup>
		</select>
		<i class="fa fa-arrow-circle-right toggle-stage"></i>
	</div>


	<div id="file-actions-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<div id="init-file-upload-container" class="container span2">
			<button id="upload-overlay-button" class="btn btn-small">Upload File</button>
		</div>
		<div id="file-download-container" class="container span4"></div>
	</div>




	<div class="stages">
		<!--div title="Prospecting" class="stage stage-prospecting <?php print $stage == 1 ? 'current' : ($stage > 1 ? 'complete' : Null);?>"><?php print $stage > 1 ? '✔' : Null;?> Prospecting</div--><!--
		--><div title="Discovery" class="stage stage-discovery <?php print $stage == 2 ? 'current' : ($stage > 2 ? 'complete' : Null);?>"><?php print $stage > 2 ? '✔' : Null;?> Discovery</div><!--
		--><div title="Quoted" class="stage stage-quoted <?php print $stage == 3 ? 'current' : ($stage > 3 ? 'complete' : Null);?>"><?php print $stage > 3 ? '✔' : Null;?> Quoted</div><!--
		--><div title="Negotiation" class="stage stage-negotiation <?php print $stage == 4 ? 'current' : ($stage > 4 ? 'complete' : Null);?>"><?php print $stage > 4 ? '✔' : Null;?> Negotiation</div><!--
		--><div title="Commitment" class="stage stage-commitment <?php print $stage == 6 ? 'current' : ($stage > 6 ? 'complete' : Null);?>"><?php print $stage > 6 ? '✔' : Null;?> Commitment</div><!--
		--><div title="Closed" class="stage stage-closed <?php
		if($stage == 7) {
			if($opportunity['stage'] == 'Closed Won') {
				print 'won';
			} else if($opportunity['stage'] == 'Closed - Never Ordered') {
				print 'never';
			} else if($opportunity['stage'] == 'Closed Lost') {
				print 'lost';
			}
		}
		?>">Closed</div>
	</div>
	<div class="tabs">
		<div class="tab" page="overview">Overview</div>
		<div class="tab" page="lineitems">Line Items</div>
		<div class="tab" page="contacts">Contacts</div>
		<div class="tab" page="activity">Activity</div>
		<div class="tab" page="notes">Notes</div>
	</div>
	<div class="tab-content" id="opportunity-details-page"></div>
</div>

<script type="text/javascript">
	var opportunity_details_tab_xhr;
	$(document).off('click', '#opportunity-details-container .tabs .tab');
	$(document).on('click', '#opportunity-details-container .tabs .tab', function(event) {
		var $tab = $(this);
		var $tabs = $tab.closest('.tabs');
		$tabs.find('.tab.active').removeClass('active');
		$tab.addClass('active');
		var $page_container = $('#opportunity-details-page');
		var page = $tab.attr('page');

		var data = {
			'opportunity_id': '<?php print htmlentities($_POST['opportunity_id'], ENT_QUOTES);?>'
		};

		// Replace page contents w/ loading icon.
		$page_container.html('<div style="width:150px;margin:auto;padding-top:120px;"><img src="/interface/images/ajax-loading.gif" /></div>');

		// Cancel working AJAX request (if present,) to preventtab body skipping.
		if(opportunity_details_tab_xhr) {
			opportunity_details_tab_xhr.abort();
		}

		opportunity_details_tab_xhr = $.ajax({
			'url': BASE_URI + '/dashboard/opportunities/details/' + page,
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
	<?php
	if(!empty($_POST['tab'])) {
		?>var $tab = $('#opportunity-details-container .tabs .tab[page="<?php print htmlentities($_POST['tab'], ENT_QUOTES);?>"]');<?php
	} else {
		?>var $tab = $('#opportunity-details-container .tabs .tab').first();<?php
	}
	?>
	$tab.trigger('click');

	/**
	 * Bind to clicks on Stage arrow/close icon.
	 */
	$(document).off('click', '#opportunity-details-container .change-stage-container .toggle-stage');
	$(document).on('click', '#opportunity-details-container .change-stage-container .toggle-stage', function(event) {
		var $icon = $(this);
		var $select = $icon.closest('.change-stage-container').find(':input[name="stage"]');
		if($icon.hasClass('fa-arrow-circle-right')) {
			$icon.removeClass('fa-arrow-circle-right');
			$icon.addClass('fa-times-circle');
			$select.css('display', 'inline-block');
		} else if($icon.hasClass('fa-times-circle')) {
			$icon.removeClass('fa-times-circle');
			$icon.addClass('fa-arrow-circle-right');
			$select.hide();
		}
	});
	
	/**
	 * Bind to changes in Stage dropdown.
	 */
	$(document).off('change', '#opportunity-details-container .change-stage-container :input[name="stage"]');
	$(document).on('change', '#opportunity-details-container .change-stage-container :input[name="stage"]', function(event) {
		var $input = $(this);
		var stage = $input.val();
		var $icon = $input.closest('.change-stage-container').find('.toggle-stage');
		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/set/stage',
			'dataType': 'json',
			'method': 'POST',
			'data': {
				'opportunity_id': <?php print json_encode($_POST['opportunity_id']);?>,
				'stage': stage
			}
		});
		$icon.trigger('click');
		
		var $stages_container = $('#opportunity-details-container .stages');
		var $discovery = $stages_container.find('.stage-discovery');
		var $quoted = $stages_container.find('.stage-quoted');
		var $negotiation = $stages_container.find('.stage-negotiation');
		var $commitment = $stages_container.find('.stage-commitment');
		var $closed = $stages_container.find('.stage-closed');
		$stages_container.find('.stage').removeClass('complete').removeClass('current');
		if(stage.startsWith('Discovery')) {
			$discovery.addClass('current');
		} else if(stage.startsWith('Quoted')) {
			$discovery.addClass('complete');
			$quoted.addClass('current');
		} else if(stage.startsWith('Negotiation')) {
			$discovery.addClass('complete');
			$quoted.addClass('complete');
			$negotiation.addClass('current');
		} else if(stage.startsWith('Verbal Commitment')) {
			$discovery.addClass('complete');
			$quoted.addClass('complete');
			$negotiation.addClass('complete');
			$commitment.addClass('current');
		} else if(stage.startsWith('Closed')) {
			$discovery.addClass('complete');
			$quoted.addClass('complete');
			$negotiation.addClass('complete');
			$commitment.addClass('complete');
			$closed.addClass('current');
		}
	});



	function get_related_files(){

		// Get the related files for the Quote.

		// Get the Quote.
		var $div = $('#opportunity-details-container')
		var oid = $div.attr('opportunity_id')

		// The data required for the overlay.
		var data = {
			'type' : 'opportunity',
			'assoc-id' : oid
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

		// Get the Quote.
		var $div = $('#opportunity-details-container')
		var oid = $div.attr('opportunity_id')

		produce_file_upload_overlay('opportunity', oid)

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
