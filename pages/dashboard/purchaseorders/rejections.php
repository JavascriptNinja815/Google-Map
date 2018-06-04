<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

/*
 *
 */

$session->ensureLogin();

$args = array(
	'title' => 'PO Rejections',
	'breadcrumbs' => array(
		'Purchase Orders' => BASE_URI . '/dashboard/purchaseorders',
		'Rejections' => BASE_URI . '/dashboard/purchaseorders/rejections'
	)
);

Template::Render('header', $args, 'account');

if(isset($_POST['save'])) {
	foreach($_POST['lineitems'] as $vpartno => $info) {
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".potran
			SET
				quality_r = " . $db->quote($info['quality_r']) . ",
				quality_n = " . $db->quote($info['quality_n']) . ",
				qualcode = " . $db->quote($info['qualcode']) . "
			WHERE
				RTRIM(LTRIM(potran.purno)) = " . $db->quote($_POST['purno']) . "
				AND
				RTRIM(LTRIM(potran.vpartno)) = " . $db->quote($vpartno) . "
		");
	}
}

if(isset($_POST['purno'])) {
	// Grab the PO entered.
	$grab_po = $db->query("
		SELECT DISTINCT
			LTRIM(RTRIM(potran.purno)) AS purno,
			LTRIM(RTRIM(potran.vendno)) AS vendno,
			LTRIM(RTRIM(potran.purdate)) AS purdate
		FROM
			" . DB_SCHEMA_ERP . ".potran
		WHERE
			RTRIM(LTRIM(potran.purno)) = " . $db->quote($_POST['purno']) . "
	");
	$po = $grab_po->fetch();
}
?>

<style type="text/css">
	#po-rejections-container input[type="checkbox"] {
		zoom:200%;
		margin:0;
	}
	#po-rejections-container textarea[name="body"] {
		width:80%;
		height:260px;
	}
</style>

<div class="padded" id="po-rejections-container">
	<fieldset>
		<legend>Purchase Order Rejections</legend>
		<form class="form-horizontal" method="post">
			<div class="control-group">
				<label class="control-label" for="po-purno">Purchase Order Number</label>
				<div class="controls">
					<input class="span2" type="text" name="purno" id="po-purno" value="<?php print !empty($po) ? htmlentities($po['purno'], ENT_QUOTES) : '';?>" />
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-folder-open fa-fw"></i>
						Retrieve
					</button>
				</div>
			</div>
		</form>
	</fieldset>

	<?php
	if(!empty($po)) {
		// Grab lineitems associated with the PO specified.
		$grab_lineitems = $db->query("
			SELECT
				LTRIM(RTRIM(potran.vpartno)) AS vpartno,
				LTRIM(RTRIM(potran.descrip)) AS descrip,
				LTRIM(RTRIM(potran.quality_r)) AS quality_r,
				LTRIM(RTRIM(potran.quality_n)) AS quality_n,
				LTRIM(RTRIM(potran.qualcode)) AS qualcode
			FROM
				" . DB_SCHEMA_ERP . ".potran
			WHERE
				RTRIM(LTRIM(potran.purno)) = " . $db->quote($_POST['purno']) . "
				AND
				RTRIM(LTRIM(potran.vpartno)) != ''
		");
		?>
		<div id="po-update-container">
			<fieldset>
				<legend class="po-update-info-title"></legend>
				<dl class="dl-horizontal">
					<dt>PO Number</dt>
					<dd class="po-update-ponumber"><?php print htmlentities($po['purno']);?></dd>
				</dl>
				<dl class="dl-horizontal">
					<dt>Vendor</dt>
					<dd class="po-update-vendor"><?php print htmlentities($po['vendno']);?></dd>
				</dl>
				<dl class="dl-horizontal">
					<dt>Purchase Date</dt>
					<dd class="po-update-purchasedate"><?php print date('Y-m-d', strtotime($po['purdate']));?></dd>
				</dl>
			</fieldset>

			<form class="form-horizontal" method="post">
				<input type="hidden" name="save" value="1" />
				<input type="hidden" name="purno" value="<?php print htmlentities($po['purno'], ENT_QUOTES);?>" />
				<fieldset>
					<legend class="po-update-items-title">Order Items</legend>
					<table id="rejections-container" class="table table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable">
						<thead>
							<tr>
								<th></th>
								<th class="filterable sortable">Vendor Part #</th>
								<th>Reason Code</th>
								<th>Quantity</th>
								<th>Notes</th>
							</tr>
						</thead>
						<tbody class="rejections">
							<?php
							$email_lineitems = [];
							foreach($grab_lineitems as $lineitem) {
								if(!isset($_POST['save']) || $lineitem['quality_r']) {
									$email_lineitems[] = $lineitem;
									?>
									<tr>
										<td>
											<?php
											if(isset($_POST['save'])) {
												// Blank.
											} else {
												?><input type="checkbox" name="lineitems[<?php print htmlentities($lineitem['vpartno'], ENT_QUOTES);?>][selected]" value="1" /><?php
											}
											?>
											
										</td>
										<td><?php print htmlentities($lineitem['vpartno']);?></td>
										<td>
											<?php
											if(isset($_POST['save'])) {
												print htmlentities($lineitem['qualcode']);
											} else {
												?>
												<select name="lineitems[<?php print htmlentities($lineitem['vpartno'], ENT_QUOTES);?>][qualcode]">
													<option value=""></option>
													<option value="overship" <?php print $lineitem['qualcode'] == 'overship' ? 'selected' : Null;?>>Overship</option>
													<option value="undership" <?php print $lineitem['qualcode'] == 'undership' ? 'selected' : Null;?>>Undership</option>
													<option value="damaged" <?php print $lineitem['qualcode'] == 'damaged' ? 'selected' : Null;?>>Damaged</option>
													<option value="misship" <?php print $lineitem['qualcode'] == 'misship' ? 'selected' : Null;?>>Mis-Ship</option>
												</select>
												<?php
											}
											?>
										</td>
										<td>
											<?php
											if(isset($_POST['save'])) {
												if($lineitem['quality_r']) {
													print htmlentities($lineitem['quality_r']);
												}
											} else {
												?><input type="number" step="1" min="0" name="lineitems[<?php print htmlentities($lineitem['vpartno'], ENT_QUOTES);?>][quality_r]" value="<?php print htmlentities($lineitem['quality_r'], ENT_QUOTES);?>" /><?php
											}
											?>
										</td>
										<td>
											<?php
											if(isset($_POST['save'])) {
												print htmlentities($lineitem['quality_n']);
											} else {
												?><input type="text" name="lineitems[<?php print htmlentities($lineitem['vpartno'], ENT_QUOTES);?>][quality_n]" value="<?php print htmlentities($lineitem['quality_n'], ENT_QUOTES);?>" /><?php
											}
											?>
										</td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>

					<?php
					if(!isset($_POST['save'])) {
						?>
						<div class="control-group">
							<div class="controls">
								<button class="btn btn-primary" type="submit">
									<i class="fa fa-floppy-o fa-fw"></i>
									Save & Continue
								</button>
							</div>
						</div>
						<?php
					}
					?>
				</fieldset>
			</form>	
		</div>
		<?php
		if(isset($_POST['save'])) {
			?>
			<form method="post" class="form-horizontal" action="<?php print BASE_URI;?>/dashboard/purchaseorders/rejections/email">
				<input type="hidden" name="email" value="1" />
				<div class="control-group">
					<label class="control-label">E-Mail To</label>
					<div class="controls">
						<input type="text" name="to" /> <small>(Separate multiple recipients with a comma)</small>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">Subject</label>
					<div class="controls">
						<input type="text" name="subject" value="RE: Purchase Order <?php print htmlentities($po['purno'], ENT_QUOTES);?>" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">E-Mail Body</label>
					<div class="controls">
						<textarea name="body"><?php
						print "\r\n\r\n\r\n";
						foreach($email_lineitems as $lineitem) {
							print $lineitem['vpartno'] . ' [' . $lineitem['qualcode'] . ']' . "\r\n";
							if(!empty($lineitem['quality_n'])) {
								print '[NOTE]: ' . $lineitem['quality_n'] . "\r\n";
							}
							print 'Quantity: ' . $lineitem['quality_r'] . "\r\n\r\n";
						}
						?></textarea>
					</div>
				</div>
				<div class="control-group">
					<div class="controls">
						<button type="submit">Send E-Mail</button>
					</div>
				</div>
			</form>
			<?php
		}
	}
?>
</div>

<?php Template::Render('footer', 'account');
