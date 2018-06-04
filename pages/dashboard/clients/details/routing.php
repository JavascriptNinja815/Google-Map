<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_arcust = $db->query("
	SELECT
		arcust.custno,
		arcust.routing,
		arcust.packaging
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		arcust.custno = " . $db->quote($_POST['custno']) . "
");
$arcust = $grab_arcust->fetch();

?>

<style type="text/css">
	#routing-tab-container {
		vertical-align:top;
	}
	#routing-tab-container .body {
		border-top:3px solid #bbb;
	}
	#routing-tab-container .body p {
		padding:12px;
		font-size:0.8em;
	}
	#routing-tab-container textarea[name="memo"] {
		width:90%;
		height:180px;
	}
	#routing-tab-container > div {
		width:48%;
		display:inline-block;
		vertical-align:top;
	}
</style>

<div id="routing-tab-container">
	<div id="client-routing-container">
		<h2>Routing</h2>

		<form id="client-memo-form" class="form-horizontal" method="post" action="<?php print BASE_URI;?>/dashboard/clients/details/routing/append">
			<input type="hidden" name="custno" value="<?php print htmlentities($arcust['custno'], ENT_QUOTES);?>" />
			<input type="hidden" name="field" value="routing" />
			<div class="control-group">
				<label class="control-label" for="date">Add To Memo</label>
				<div class="controls">
					<textarea name="memo"></textarea>
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn btn-primary">
						Save
					</button>
				</div>
			</div>
		</form>

		<div class="body"><?php
			if(!empty($arcust['routing'])) {
				$routing = str_replace("\r", '', $arcust['routing']);
				$routing = str_replace("\n^~*~^\n", '</p><p>', $arcust['routing']);
				$routing = str_replace("\n", '<br />', $routing);
				$routing = str_replace("\n", '<br />', $routing);
				print '<p>' . $routing . '</p>';
			}
		?></div>
	</div>

	<div id="client-packaging-container">
		<h2>Packaging</h2>

		<form id="client-memo-form" class="form-horizontal" method="post" action="<?php print BASE_URI;?>/dashboard/clients/details/routing/append">
			<input type="hidden" name="custno" value="<?php print htmlentities($arcust['custno'], ENT_QUOTES);?>" />
			<input type="hidden" name="field" value="packaging" />
			<div class="control-group">
				<label class="control-label" for="date">Add To Memo</label>
				<div class="controls">
					<textarea name="memo"></textarea>
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn btn-primary">
						Save
					</button>
				</div>
			</div>
		</form>

		<div class="body"><?php
			if(!empty($arcust['packaging'])) {
				$packaging = str_replace("\r", '', $arcust['packaging']);
				$packaging = str_replace("\n^~*~^\n", '</p><p>', $arcust['packaging']);
				$packaging = str_replace("\n", '<br />', $packaging);
				$packaging = str_replace("\n", '<br />', $packaging);
				print '<p>' . $packaging . '</p>';
			}
		?></div>
	</div>
</div>

<script type="text/javascript">
	$(document).off('submit', '#client-memo-form');
	$(document).on('submit', '#client-memo-form', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);
		var $memo = $form.find(':input[name="memo"]');

		var field = $form.find(':input[name="field"]').val();
		if(field == 'routing') {
			var $body = $('#client-routing-container .body');
		} else if(field == 'packaging') {
			var $body = $('#client-packaging-container .body');
		}

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': $form.attr('action'),
			'method': $form.attr('method'),
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(response.text) {
					// Append the memo to the page.
					$body.append(
						$('<p>').append(
							response.text.replace(/\r/gi, '').replace(/\n/gi, '<br />')
						)
					);
					// Clear Memo input.
					$memo.val('');
				}

				// Removing the loading overlay.
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});

		return false;
	});
</script>
