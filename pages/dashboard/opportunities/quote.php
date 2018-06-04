<?php

$session->ensureLogin();
$session->ensureRole('Sales');

set_time_limit(0);

ob_start(); // Start loading output into buffer.

if(isset($_REQUEST['view'])) {
	$view = $_REQUEST['view'];
} else {
	$view = 'preview';
}

if($view == 'preview') {
	$grab_opportunity = $db->query("
		SELECT
			opportunities.opportunity_id,
			opportunities.terr,
			opportunities.quotetemplate_id
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		WHERE
			opportunities.opportunity_id = " . $db->quote($_REQUEST['opportunity_id']) . "
	");
	$opportunity = $grab_opportunity->fetch();

	if(!trim($opportunity['terr'])) {
		?>
		The opportunity must have an office specified in order to send a quote. Please edit the opportunity and specify an office.
		<?php
		$html = ob_get_contents(); // Load buffer into accessible var.
		ob_end_clean(); // Clear the buffer.

		print json_encode([
			'success' => True,
			'html' => $html
		]);
		exit;
	}

	$grab_attachments = $db->query("
		SELECT
			opportunity_attachments.attachment_id,
			opportunity_attachments.name
		FROM
			" . DB_SCHEMA_ERP . ".opportunity_attachments
		ORDER BY
			opportunity_attachments.name
	");

	$grab_groups = $db->query("
		SELECT
			COUNT(*) AS count
		FROM
			" . DB_SCHEMA_ERP . ".opportunity_groups
		WHERE
			opportunity_groups.opportunity_id = " . $db->quote($_REQUEST['opportunity_id']) . "
			AND
			opportunity_groups.selected = 1
	");
	$group_count = $grab_groups->fetch();

	if(!$group_count['count']) {
		?>
		At least one Lineitem Group must be checked in order to send a quote.
		<?php
		$html = ob_get_contents(); // Load buffer into accessible var.
		ob_end_clean(); // Clear the buffer.

		print json_encode([
			'success' => True,
			'html' => $html
		]);
		exit;
	}

	$quote = new Quote($opportunity['opportunity_id']);

	$filename = $quote->generatePDF(
		$opportunity['quotetemplate_id'],
		$_POST['email'], // to_email
		$_POST['name'], // to_name,
		$session->login['initials'] // salesman_initials
	);
} else if($view == 'existing') {
	$filename = $_REQUEST['filename'];
}

?>

<style type="text/css">
	#quote-preview-form {
		padding:0;
		margin:0;
		width:100%;
		height:100%;
		position:relative;
	}
	#quote-preview-form .iframe-container {
		position:absolute;
		top:0;
		left:0;
		right:0;
		bottom:54px;
	}
	#quote-preview-form iframe {
		width:100%;
		height:100%;
		margin:0;
		padding:0;
	}
	#quote-preview-form .floating {
		position:absolute;
		bottom:0px;
		left:0;
		right:0;
		height:38px;
		padding-left:12px;
	}
</style>

<form id="quote-preview-form" method="post" action="<?php print BASE_URI;?>/dashboard/opportunities/quote/customize">
	<?php
	if($view == 'preview') {
		?>
		<input type="hidden" name="opportunity_id" value="<?php print htmlentities($_POST['opportunity_id'], ENT_QUOTES);?>" />
		<input type="hidden" name="email" value="<?php print htmlentities($_POST['email'], ENT_QUOTES);?>" />
		<input type="hidden" name="name" value="<?php print htmlentities($_POST['name'], ENT_QUOTES);?>" />
		<input type="hidden" name="filename" value="<?php print htmlentities($filename, ENT_QUOTES);?>" />
		<?php
	}
	?>

	<div class="iframe-container">
		<iframe src="http://dev.maven.local/dashboard/opportunities/quote/preview-pdf?filename=<?php print htmlentities($filename, ENT_QUOTES);?>" />
	</div>

	<div class="floating">
		<?php
		if($view == 'preview') {
			?>
			<button type="submit" class="btn btn-primary">Approve</button>
			<button type="button" class="btn btn-warning reject">Reject</button>
			<?php
		} else if($view == 'existing') {
			?>
			<button type="button" class="btn reject btn-primary">Close</button>
			<?php
		}
		?>
	</div>
</form>

<script type="text/javascript">
	// Initialize WYSIWYG Editor.
	$('#sendquote-message').trumbowyg();

	$(document).off('submit', '#quote-preview-form');
	$(document).on('submit', '#quote-preview-form', function(event) {
		var form = this;
		var $form = $(form);

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': $form.attr('action'),
			'data': new FormData(form),
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				// Remove loading overlay.
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
				alert('Quote successfully sent');
				
				var $overlayz = $form.closest('.overlayz');
				$overlayz.fadeOut('fast', function() {
					$overlayz.remove();
				});
			}
		});
		return false;
	});

	/**
	 * Bind to clicks on "Reject" button.
	 */
	$(document).off('click', '#quote-preview-form button.reject');
	$(document).on('click', '#quote-preview-form button.reject', function(event) {
		var $overlay = $(this).closest('.overlayz');
		$overlay.fadeOut('fast', function() {
			$overlay.remove();
		});
	});

	/*
	 * Bind to "Approve" form submissions.
	 */
	$(document).off('submit', '#quote-preview-form');
	$(document).on('submit', '#quote-preview-form', function(event) {
		var $overlay = $(this).closest('.overlayz');

		var form = this;
		var $form = $(form);
		var data = new FormData(form);
		$.ajax({
			'url': $form.attr('action'),
			'method': $form.attr('method'),
			'data': data,
			'processData': false,
			'contentType': false,
			'dataType': 'json',
			'enctype': 'multipart/form-data',
			'success': function(response) {
				$overlay.find('.overlayz-body').html(response.html);
			}
		});
		return false;
	});
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
