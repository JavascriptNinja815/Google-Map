<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_groups = $db->query("
	SELECT
		opportunity_groups.opportunity_group_id,
		opportunity_groups.name,
		opportunity_groups.memo,
		opportunity_groups.selected,
		opportunity_groups.position
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_groups
	WHERE
		opportunity_groups.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
	ORDER BY
		opportunity_groups.position
");

$grab_fobs = $db->query("
	SELECT
		opportunity_fobs.fob_id,
		opportunity_fobs.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_fobs
	ORDER BY
		opportunity_fobs.name
");
$fobs = [];
foreach($grab_fobs as $fob) {
	$fobs[] = [
		'fob_id' => $fob['fob_id'],
		'name' => $fob['name']
	];
}

$grab_leadtimes = $db->query("
	SELECT
		opportunity_leadtimes.leadtime_id,
		opportunity_leadtimes.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_leadtimes
	ORDER BY
		opportunity_leadtimes.name
");
$leadtimes = [];
foreach($grab_leadtimes as $leadtime) {
	$leadtimes[] = [
		'leadtime_id' => $leadtime['leadtime_id'],
		'name' => $leadtime['name']
	];
}

?>

<style type="text/css">
	#opportunity-lineitems-body .action-rename,
	#opportunity-lineitems-body .action-delete {
		padding-left:4px;
		padding-right:4px;
		font-size:20px;
		color:#00f;
		cursor:pointer;
	}
	#opportunity-lineitems-body input[name="select[]"] {
		width:24px;
		height:24px;
	}
	#opportunity-lineitems-body .action-up,
	#opportunity-lineitems-body .action-down {
		font-size:16px;
		padding:2px;
	}
	#opportunity-lineitems-body legend {
		position:relative;
		overflow:hidden;
	}
	#opportunity-lineitems-body .actions-container {
		display:inline-block;
		position:absolute;
		right:0px;
	}
	#opportunity-lineitems-body .memo-container {
		display:inline-block;
		float:right;
		width:50%;
		margin-right:100px;
	}
	#opportunity-lineitems-body .memo-container textarea {
		width:100%;
	}
	#opportunity-lineitems-body .memo-container textarea,
	#opportunity-lineitems-body textarea {
		font-size:11px;
		line-height:17px;
		height:17px;
		padding:2px 4px;
		resize: none;
	}
	#opportunity-lineitems-body fieldset {
		padding-bottom:12px;
		margin-bottom:12px;
		border-bottom:1px solid #e5e5e5;
	}
	#opportunity-lineitems-body .prototype {
		display:none;
	}
	#opportunity-lineitems-body .add-on {
		font-size:11px;
		padding:1px 3px 0px 3px;
	}
	#opportunity-lineitems-body td {
		padding:2px;
	}
	#opportunity-lineitems-body input[name="last_cost"],
	#opportunity-lineitems-body input[name="available"],
	#opportunity-lineitems-body input[name="priceea"],
	#opportunity-lineitems-body input[name="quantity"],
	#opportunity-lineitems-body input[name="margin"] {
		width:80px;
	}
	#opportunity-lineitems-body .pricing-action-container {
		width:32px;
		display:inline-block;
	}
	#opportunity-lineitems-body .pricing-action-container i {
		padding-top:0px;
		padding-bottom:8px;
	}
	#opportunity-lineitems-body .pricing-fob-container,
	#opportunity-lineitems-body .pricing-leadtime-container,
	#opportunity-lineitems-body .pricing-qtytoorder-container,
	#opportunity-lineitems-body .pricing-priceea-container,
	#opportunity-lineitems-body .pricing-margin-container {
		display:inline-block;
	}
	
	#opportunity-lineitems-body .pricing-fob-container,
	#opportunity-lineitems-body .pricing-leadtime-container {
		width:62px;
	}
	#opportunity-lineitems-body .pricing-qtytoorder-container {
		width:100px;
	}
	#opportunity-lineitems-body .pricing-priceea-container,
	#opportunity-lineitems-body .pricing-margin-container {
		width:120px;
	}
	#opportunity-lineitems-body .lineitem-table td {
		vertical-align:top;
	}
	#opportunity-lineitems-body .table-small select {
		padding:0;
	}
	#opportunity-lineitems-body .lineitem-partnumber input[name="itmdesc"] {
		margin-bottom:0;
	}
	#opportunity-lineitems-body .lineitem-partnumber label {
		font-size:0.8em;
	}
</style>

<div id="opportunity-lineitems-body">
	<button type="button" id="add-group" class="btn btn-primary action-add-group"><i class="fa fa-plus"></i> Add Option Group</button>
	<br /><br />

	<div class="lineitem-groups">
		<fieldset opportunity_group_id="" class="lineitem-group prototype">
			<legend>
				<input type="checkbox" name="select[]" title="Selected" value="" />
				<span class="opportunity-group-name"></span>
				<i class="fa fa-pencil action action-rename" title="Rename"></i>
				<div class="memo-container">
					<textarea class="autofit" name="memo" placeholder="Memo"></textarea>
				</div>
				<div class="actions-container">
					<i class="fa fa-arrow-up action action-up" title="Move Up"></i>
					<i class="fa fa-arrow-down action action-down" title="Move Down"></i>
					<i class="fa fa-trash action action-delete" title="Delete"></i>
				</div>
			</legend>

			<table class="lineitem-table table-small table-striped">
				<thead>
					<tr>
						<th></th>
						<th>Line #</th>
						<th>Descriptive Part #</th>
						<th>Item Code</th>
						<th>Description</th>
						<th>Last Cost</th>
						<th>Available</th>
						<th>
							<div class="pricing-action-container"></div>
							<div class="pricing-fob-container">FOB</div>
							<div class="pricing-leadtime-container">Lead Time</div>
							<div class="pricing-qtytoorder-container">Qty To Order</div>
							<div class="pricing-priceea-container">Price/Ea</div>
							<div class="pricing-margin-container">Margin</div>
						</th>
					</tr>
				</thead>
				<tbody class="lineitem-tbody"></tbody>
			</table>

			<button type="button" class="btn btn-primary action-add-lineitem"><i class="fa fa-plus"></i> Add Line Item</button>
		</fieldset>

		<?php
		foreach($grab_groups as $group) {
			$grab_lineitems = $db->query("
				SELECT
					opportunity_lineitems.opportunity_lineitem_id,
					opportunity_lineitems.icitem_id_col,
					opportunity_lineitems.position,
					opportunity_lineitems.item_code,
					opportunity_lineitems.itmdesc, -- Part Number
					opportunity_lineitems.itmdesc_hide,
					opportunity_lineitems.itmdesc2, -- Description
					COALESCE(icitem.lstcost, opportunity_lineitems.last_cost) AS last_cost,
					COALESCE(icitem.ionhand, opportunity_lineitems.available) AS available,
					opportunity_lineitems.quantity,
					opportunity_lineitems.price_ea
				FROM
					" . DB_SCHEMA_ERP . ".opportunity_lineitems
				LEFT JOIN
					" . DB_SCHEMA_ERP . ".icitem
					ON
					icitem.id_col = opportunity_lineitems.icitem_id_col
				WHERE
					opportunity_lineitems.opportunity_group_id = " . $db->quote($group['opportunity_group_id']) . "
				ORDER BY
					opportunity_lineitems.position
			");
			?>
			<fieldset opportunity_group_id="<?php print htmlentities($group['opportunity_group_id'], ENT_QUOTES);?>" class="lineitem-group">
				<legend>
					<input type="checkbox" name="select[]" title="Selected" value="<?php print htmlentities($group['opportunity_group_id'], ENT_QUOTES);?>" <?php print $group['selected'] ? 'checked' : Null;?> />
					<span class="opportunity-group-name"><?php print htmlentities($group['name']);?></span>
					<i class="fa fa-pencil action action-rename" title="Rename"></i>
					<div class="memo-container">
						<textarea class="autofit" name="memo" placeholder="Memo"><?php print htmlentities($group['memo']);?></textarea>
					</div>
					<div class="actions-container">
						<i class="fa fa-arrow-up action action-up" title="Move Up"></i>
						<i class="fa fa-arrow-down action action-down" title="Move Down"></i>
						<i class="fa fa-trash action action-delete" title="Delete"></i>
					</div>
				</legend>

				<table class="lineitem-table table-small table-striped">
					<tr>
						<th></th>
						<th>Line #</th>
						<th>Part #</th>
						<th>Item Code</th>
						<th>Description</th>
						<th>Last Cost</th>
						<th>Available</th>
						<th>
							<div class="pricing-action-container"></div>
							<div class="pricing-fob-container"></div>
							<div class="pricing-leadtime-container"></div>
							<div class="pricing-qtytoorder-container">Qty To Order</div>
							<div class="pricing-priceea-container">Price/Ea</div>
							<div class="pricing-margin-container">Margin</div>
						</th>
					</tr>
					<tbody class="lineitem-tbody">
						<?php
						foreach($grab_lineitems as $lineitem) {
							?>
							<tr opportunity_lineitem_id="<?php print htmlentities($lineitem['opportunity_lineitem_id'], ENT_QUOTES);?>" class="lineitem">
								<td class="lineitem-actions"><i class="fa fa-minus action action-remove"></i></td>
								<td class="lineitem-pos"><?php print htmlentities($lineitem['position']);?></td>
								<td class="lineitem-partnumber">
									<input type="text" name="itmdesc" value="<?php print htmlentities($lineitem['itmdesc'], ENT_QUOTES);?>" class="span2" <?php print $lineitem['icitem_id_col'] ? 'disabled' : Null;?> />
									<label><input type="checkbox" name="itmdesc_hide" <?php print $lineitem['itmdesc_hide'] ? 'checked' : Null;?> /> Hide</label>
								</td>
								<td class="lineitem-itemcode"><input type="text" name="item_code" value="<?php print htmlentities($lineitem['item_code'], ENT_QUOTES);?>" class="span2" <?php print $lineitem['icitem_id_col'] ? 'disabled' : Null;?> /></td>
								<td class="lineitem-descshort"><textarea class="autofit span3" name="itmdesc2"><?php print htmlentities($lineitem['itmdesc2']);?></textarea></td>
								<td class="lineitem-lastcost">
									<div class="input-prepend">
										<span class="add-on">$</span>
										<input name="last_cost" type="number" step="0.01" value="<?php print htmlentities(number_format($lineitem['last_cost'], 2), ENT_QUOTES);?>" min="0.00" class="span2" <?php print $lineitem['icitem_id_col'] ? 'disabled' : Null;?> />
									</div>
								</td>
								<td class="lineitem-available"><input name="available" type="number" step="1" min="0" value="<?php print htmlentities(number_format($lineitem['available'], 0), ENT_QUOTES);?>" <?php print $lineitem['icitem_id_col'] ? 'disabled' : Null;?> class="span2" /></td>
								<td class="lineitem-pricing">
									<?php
									$grab_lineitem_prices = $db->prepare("
										SELECT
											opportunity_lineitem_prices.price_id,
											opportunity_lineitem_prices.fob,
											opportunity_lineitem_prices.leadtime,
											opportunity_lineitem_prices.quantity,
											opportunity_lineitem_prices.priceea
										FROM
											" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
										WHERE
											opportunity_lineitem_prices.lineitem_id = " . $db->quote($lineitem['opportunity_lineitem_id']) . "
										ORDER BY
											opportunity_lineitem_prices.price_id
									", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
									$grab_lineitem_prices->execute();
									foreach($grab_lineitem_prices as $curr => $price) {
										?>
										<div class="lineitem-price" price_id="<?php print htmlentities($price['price_id'], ENT_QUOTES);?>">
											<div class="pricing-action-container">
												<?php
												if($curr == 0) {
													?><i class="fa fa-plus action action-add" title="Add Pricing Option"></i><?php
												} else {
													?><i class="fa fa-minus action action-remove" title="Remove Pricing Option"></i><?php
												}
												?>
											</div>
											<div class="pricing-fob-container">
												<select name="fob" class="span1">
													<option value=""></option>
													<?php
													foreach($fobs as $fob) {
														?><option value="<?php print htmlentities($fob['name'], ENT_QUOTES);?>" <?php print $price['fob'] == $fob['name'] ? 'selected' : Null;?>><?php print htmlentities($fob['name']);?></option><?php
													}
													?>
												</select>
											</div>
											<div class="pricing-leadtime-container">
												<select name="leadtime" class="span1">
													<option value=""></option>
													<?php
													foreach($leadtimes as $leadtime) {
														?><option value="<?php print htmlentities($leadtime['name'], ENT_QUOTES);?>" <?php print $price['leadtime'] == $leadtime['name'] ? 'selected' : Null;?>><?php print htmlentities($leadtime['name']);?></option><?php
													}
													?>
												</select>
											</div>
											<div class="pricing-qtytoorder-container">
												<input type="number" name="quantity" step="1" min="0" value="<?php print htmlentities($price['quantity'], ENT_QUOTES);?>" class="span2" />
											</div>
											<div class="pricing-priceea-container">
												<div class="input-prepend">
													<span class="add-on">$</span>
													<input name="priceea" type="number" step="0.01" min="0.00" value="<?php print htmlentities($price['priceea'], ENT_QUOTES);?>" class="span2" />
												</div>
											</div>
											<div class="pricing-margin-container">
												<div class="input-prepend">
													<span class="add-on">%</span>
													<input type="number" name="margin" step="0.1" min="0.0" class="span2" />
												</div>
											</div>
										</div>
										<?php
									}
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>

				<button type="button" class="btn btn-primary action-add-lineitem"><i class="fa fa-plus"></i> Add Line Item</button>
			</fieldset>
			<?php
		}
		?>
	</div>
</div>

<script type="text/javascript">
	var ajax_loading_styles = {
		'body': {
			'padding-top': '50px',
			'max-width': '200px',
			'max-height': '150px',
			'border-radius': '200px'
		}
	};

	var fobs = <?php print json_encode($fobs);?>;
	var leadtimes = <?php print json_encode($leadtimes);?>;

	/**
	 * Bind to changes on "Selected" checkbox.
	 */
	$(document).off('change', '#opportunity-lineitems-body :input[name="select[]"]');
	$(document).on('change', '#opportunity-lineitems-body :input[name="select[]"]', function(event) {
		var $checkbox = $(this);
		var $lineitem_group = $checkbox.closest('.lineitem-group');
		if($checkbox.is(':checked')) {
			var selected = 1;
		} else {
			var selected = 0;
		}

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/groups/select',
			'data': {
				'opportunity_group_id': $lineitem_group.attr('opportunity_group_id'),
				'selected': selected
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	/**
	 * Bind to clicks on "Rename" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-rename');
	$(document).on('click', '#opportunity-lineitems-body .action-rename', function(event) {
		var name = prompt('Enter a name for this group');
		if(name) {
			var $icon = $(this);
			var $lineitem_group = $icon.closest('.lineitem-group');
			var $name = $lineitem_group.find('.opportunity-group-name');

			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/groups/rename',
				'data': {
					'opportunity_group_id': $lineitem_group.attr('opportunity_group_id'),
					'name': name
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					$name.text(name);
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	 * Bind to clicks on "Delete" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-delete');
	$(document).on('click', '#opportunity-lineitems-body .action-delete', function(event) {
		if(confirm('Are you sure you want to delete this group?\n\nThis action cannot be undone.')) {
			var $icon = $(this);
			var $lineitem_group = $icon.closest('.lineitem-group');
			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/groups/delete',
				'data': {
					'opportunity_id': <?php print json_encode($_POST['opportunity_id']);?>,
					'opportunity_group_id': $lineitem_group.attr('opportunity_group_id')
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					$lineitem_group.slideUp('fast', function() {
						$lineitem_group.remove();
					});
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	 * Bind to clicks on "Up" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-up');
	$(document).on('click', '#opportunity-lineitems-body .action-up', function(event) {
		var $icon = $(this);
		var $lineitem_group = $icon.closest('.lineitem-group');

		var $previous = $lineitem_group.prev(':not(.prototype)');
		if($previous.length) {
			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');
			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/groups/up',
				'data': {
					'opportunity_id': <?php print json_encode($_POST['opportunity_id']);?>,
					'opportunity_group_id': $lineitem_group.attr('opportunity_group_id')
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					$lineitem_group.slideUp('fast', function() {
						$lineitem_group.insertBefore($previous).slideDown('fast');
					});
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	 * Bind to clicks on "Down" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-down');
	$(document).on('click', '#opportunity-lineitems-body .action-down', function(event) {
		var $icon = $(this);
		var $lineitem_group = $icon.closest('.lineitem-group');

		var $next = $lineitem_group.next();
		if($next.length) {
			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');
			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/groups/down',
				'data': {
					'opportunity_id': <?php print json_encode($_POST['opportunity_id']);?>,
					'opportunity_group_id': $lineitem_group.attr('opportunity_group_id')
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					$lineitem_group.slideUp('fast', function() {
						$lineitem_group.insertAfter($next).slideDown('fast');
					});
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	 * Bind to clicks on "Add Option Group" button.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-add-group');
	$(document).on('click', '#opportunity-lineitems-body .action-add-group', function(event) {
		var name = prompt('Enter a name for this group');
		if(name) {
			var $lineitem_groups = $('#opportunity-lineitems-body .lineitem-groups');
			var $prototype = $lineitem_groups.find('.prototype.lineitem-group');
			var $lineitem_group = $prototype.clone().removeClass('prototype').hide();
			$lineitem_group.find('.opportunity-group-name').text(name);

			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/groups/create',
				'data': {
					'name': name,
					'opportunity_id': <?php print json_encode($_POST['opportunity_id']);?>
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					$lineitem_group.attr('opportunity_group_id', response.opportunity_group_id);
					$lineitem_group.appendTo($lineitem_groups).slideDown('fast');
					if(response.selected) {
						$lineitem_group.find(':input[name="select[]"]').prop('checked', true);
					}
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	* Bind to clicks on "Add Line Item" button.
	 */
	$(document).off('click', '#opportunity-lineitems-body .action-add-lineitem');
	$(document).on('click', '#opportunity-lineitems-body .action-add-lineitem', function(event) {
		var $button = $(this);
		var $lineitem_group = $button.closest('.lineitem-group');
		var $lineitem_tbody = $lineitem_group.find('.lineitem-tbody');

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/lineitems/create',
			'data': {
				'name': name,
				'opportunity_group_id': $lineitem_group.attr('opportunity_group_id')
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				var $fob = $('<select name="fob" class="span1">');
				$fob.append('<option>');
				$.each(fobs, function(offset, fob) {
					$fob.append(
						$('<option>').attr('value', fob['name']).text(fob['name'])
					);
				});

				var $leadtime = $('<select name="leadtime" class="span1">');
				$leadtime.append('<option value="">');
				$.each(leadtimes, function(offset, leadtime) {
					$leadtime.append(
						$('<option>').attr('value', leadtime['name']).text(leadtime['name'])
					);
				});

				var $lineitem = $('<tr class="lineitem">').attr('opportunity_lineitem_id', response.opportunity_lineitem_id).append(
					$('<td class="lineitem-actions">').append(
						$('<i class="fa fa-minus action action-remove">')
					),
					$('<td class="lineitem-pos">').text(response.position),
					$('<td class="lineitem-partnumber">').append(
						$('<input type="text" name="itmdesc" class="span2" />'),
						$('<label>').append(
							$('<input type="checkbox" name="itmdesc_hide" />')
						)
					),
					$('<td class="lineitem-itemcode">').append(
						$('<input type="text" name="item_code" class="span2" />')
					),
					$('<td class="lineitem-descshort">').append(
						$('<textarea class="autofit span3" name="itmdesc2">').hide()
					),
					$('<td class="lineitem-lastcost">').append(
						$('<div class="input-prepend">').append(
							$('<span class="add-on">').text('$'),
							$('<input type="number" name="last_cost" min="0.00" step="0.01" class="span2" />').hide()
						).hide()
					),
					$('<td class="lineitem-available">').append(
						$('<input type="text" name="available" class="span2" />').hide()
					),
					$('<td class="lineitem-pricing">').append(
						$('<div class="lineitem-price">').attr('price_id', response.price_id).append(
							$('<div class="pricing-action-container">').append(
								$('<i class="fa fa-plus action action-add" title="Add Pricing Option">')
							),
							$('<div class="pricing-fob-container">').append(
								$fob
							),
							$('<div class="pricing-leadtime-container">').append(
								$leadtime
							),
							$('<div class="pricing-qtytoorder-container">').append(
								$('<input type="number" name="quantity" step="1" min="0" class="span2">')
							),
							$('<div class="pricing-priceea-container">').append(
								$('<div class="input-prepend">').append(
									$('<span class="add-on">').text('$'),
									$('<input name="priceea" type="number" step="0.01" min="0.00" class="span2">')
								)
							),
							$('<div class="pricing-margin-container">').append(
								$('<div class="input-prepend">').append(
									$('<span class="add-on">').text('%'),
									$('<input type="number" name="margin" step="0.1" min="0.0" class="span2">')
								)
							)
						)
					)
				);
				$lineitem.appendTo($lineitem_tbody);
				$lineitem.find(':input[name="itmdesc"]').focus();
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	/**
	* Bind to clicks on "Remove Lineitem" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .lineitem-actions .action-remove');
	$(document).on('click', '#opportunity-lineitems-body .lineitem-actions .action-remove', function(event) {
		var $icon = $(this);
		var $lineitem = $icon.closest('.lineitem');
		var opportunity_lineitem_id = $lineitem.attr('opportunity_lineitem_id');

		var sure = confirm('Are you sure you want to remove this line item?\n\nThis action cannot be undone.');
		if(sure) {
			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/lineitems/delete',
				'data': {
					'opportunity_lineitem_id': opportunity_lineitem_id
				},
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					if(response.success) {
						$lineitem.remove();
					}
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
	});

	/**
	* Bind to changes on Item Code & Part # inputs.
	 */
	$(document).off('change', [
		'#opportunity-lineitems-body .lineitem :input[name="item_code"]',
		'#opportunity-lineitems-body .lineitem :input[name="itmdesc"]'
	].join(', '));
	$(document).on('change', [
		'#opportunity-lineitems-body .lineitem :input[name="item_code"]',
		'#opportunity-lineitems-body .lineitem :input[name="itmdesc"]'
	].join(', '), function(event) {
		var $input = $(this);
		var $lineitem = $input.closest('.lineitem');

		var data = {
			'opportunity_lineitem_id': $lineitem.attr('opportunity_lineitem_id'),
		};
		if($input.attr('name') === 'item_code') {
			var val = $input.val();
			if(!val) {
				return true;
			}
			data['item_code'] = val;
		} else if($input.attr('name') === 'itmdesc') {
			var val = $input.val();
			if(!val) {
				return true;
			}
			data['partnumber'] = val;
		}

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/lineitems/set/identifier',
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				$lineitem.find(':input[name="itmdesc2"]').show();
				$lineitem.find(':input[name="itmdesc"]').show();
				$lineitem.find(':input[name="last_cost"]').show();
				$lineitem.find(':input[name="available"]').show();
				$lineitem.find(':input[name="fob"]').show();
				$lineitem.find(':input[name="leadtime"]').show();
				$lineitem.find(':input[name="quantity"]').show();
				$lineitem.find(':input[name="priceea"]').show();
				$lineitem.find(':input[name="margin"]').show();
				$lineitem.find('.input-prepend').show();
				if(response.success) {
					$lineitem.find(':input[name="item_code"]').prop('disabled', true).val(response.item_code);
					$lineitem.find(':input[name="itmdesc"]').prop('disabled', true).val(response.itmdesc);
					$lineitem.find(':input[name="last_cost"]').prop('disabled', true).val(response.last_cost);
					$lineitem.find(':input[name="available"]').prop('disabled', true).val(response.available);
					$lineitem.find(':input[name="itmdesc2"]').val(response.itmdesc2).trigger('change').trigger('keydown');
				}
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	/**
	* Bind to change events on Lineitem inputs.
	 */
	$(document).off('change', [
		'#opportunity-lineitems-body .lineitem :input[name="available"]',
		'#opportunity-lineitems-body .lineitem :input[name="itmdesc2"]',
		'#opportunity-lineitems-body .lineitem :input[name="last_cost"]'
	].join(', '));
	$(document).on('change', [
		'#opportunity-lineitems-body .lineitem :input[name="available"]',
		'#opportunity-lineitems-body .lineitem :input[name="itmdesc2"]',
		'#opportunity-lineitems-body .lineitem :input[name="last_cost"]'
	].join(', '), function(event) {
		var $input = $(this);
		var $lineitem = $input.closest('.lineitem');
		var page = $input.attr('name');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/lineitems/set/' + page,
			'data': {
				'opportunity_lineitem_id': $lineitem.attr('opportunity_lineitem_id'),
				'value': $input.val()
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {},
			'complete': function() {}
		});
	});
	
	/*
	* Bind to change events on Lineitem Price inputs.
	*/
	function update_pricing_field($input) {
		var $price_container = $input.closest('.lineitem-price');
		var page = $input.attr('name');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/prices/set/' + page,
			'data': {
				'price_id': $price_container.attr('price_id'),
				'value': $input.val()
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {},
			'complete': function() {}
		});
	}
	$(document).off('change', [
		'#opportunity-lineitems-body .lineitem :input[name="fob"]',
		'#opportunity-lineitems-body .lineitem :input[name="leadtime"]',
		'#opportunity-lineitems-body .lineitem :input[name="priceea"]',
		'#opportunity-lineitems-body .lineitem :input[name="quantity"]'
	].join(', '));
	$(document).on('change', [
		'#opportunity-lineitems-body .lineitem :input[name="fob"]',
		'#opportunity-lineitems-body .lineitem :input[name="leadtime"]',
		'#opportunity-lineitems-body .lineitem :input[name="priceea"]',
		'#opportunity-lineitems-body .lineitem :input[name="quantity"]'
	].join(', '), function(event) {
		var $input = $(this);
		update_pricing_field($input);
	});

	var restrict_change_loop = false;

	/**
	* Bind to change events on "Price/Ea" input, to calculate Margin.
	 */
	$(document).off('change', '#opportunity-lineitems-body .lineitem :input[name="priceea"]');
	$(document).on('change', '#opportunity-lineitems-body .lineitem :input[name="priceea"]', function(event) {
		if(restrict_change_loop) {
			restrict_change_loop = false;
			return;
		}

		var $priceea = $(this);

		var $price_container = $priceea.closest('.lineitem-price');
		var $margin = $price_container.find(':input[name="margin"]');

		var $lineitem = $priceea.closest('.lineitem');
		var $lastcost = $lineitem.find(':input[name="last_cost"]');

		var priceea = parseFloat($priceea.val());
		var lastcost = parseFloat($lastcost.val());

		if(priceea > 0.0 && lastcost > 0.0) {
			var margin = ((priceea - lastcost) / priceea) * 100;
			restrict_change_loop = true;
			$margin.val(margin.toFixed(1)).trigger('change');
		}
	});

	/**
	* Bind to change events on Margin input, to calculate Price/Ea.
	 */
	$(document).off('change', '#opportunity-lineitems-body .lineitem :input[name="margin"]');
	$(document).on('change', '#opportunity-lineitems-body .lineitem :input[name="margin"]', function(event) {
		if(restrict_change_loop) {
			restrict_change_loop = false;
			return;
		}
		var $margin = $(this);

		var $price_container = $margin.closest('.lineitem-price');
		var $priceea = $price_container.find(':input[name="priceea"]');

		var $lineitem = $margin.closest('.lineitem');
		var $lastcost = $lineitem.find(':input[name="last_cost"]');

		var margin = parseFloat($margin.val());
		var lastcost = parseFloat($lastcost.val());

		if(margin > 0.0 && lastcost > 0.0) {
			var priceea = lastcost * (1 / (1 - (margin / 100)));
					//(lastcost * (margin + 100.0)) / 100.0;
			restrict_change_loop = true;
			$priceea.val(priceea.toFixed(2)).trigger('change');
		}
	});

	/**
	* Bind to changes on "Opportunity Group's Memo" field.
	 */
	$(document).off('change', '#opportunity-lineitems-body .lineitem-group :input[name="memo"]');
	$(document).on('change', '#opportunity-lineitems-body .lineitem-group :input[name="memo"]', function(event) {
		var $input = $(this);
		var $lineitem_group = $input.closest('.lineitem-group');
		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/groups/set/memo',
			'data': {
				'opportunity_group_id': $lineitem_group.attr('opportunity_group_id'),
				'memo': $input.val()
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				// Nothing to do.
			}
		});
	});

	/**
	* Bind to clicks on "New Pricing Option" icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .lineitem-pricing .pricing-action-container .action-add');
	$(document).on('click', '#opportunity-lineitems-body .lineitem-pricing .pricing-action-container .action-add', function(event) {
		var $icon = $(this);
		var $price_prototype = $icon.closest('.lineitem-price');
		var $prices_container = $icon.closest('.lineitem-pricing');
		var $lineitem = $icon.closest('.lineitem');

		var $price = $price_prototype.clone();
		$price.find('.action-add').removeClass('action-add').removeClass('fa-plus').addClass('action-remove').addClass('fa-minus');
		$price.find(':input[name="fob"]').val('');
		$price.find(':input[name="leadtime"]').val('');
		$price.find(':input[name="priceea"]').val('');
		$price.find(':input[name="quantity"]').val('');
		$price.find(':input[name="margin"]').val('');

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/prices/create',
			'data': {
				'opportunity_lineitem_id': $lineitem.attr('opportunity_lineitem_id')
			},
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				if(response.success) {
					$price.attr('price_id', response.price_id);
					$price.hide().appendTo($prices_container).slideDown('fast');
				}
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	/**
	* Bind to clicks on Remove Pricing Option icon.
	 */
	$(document).off('click', '#opportunity-lineitems-body .lineitem-pricing .pricing-action-container .action-remove');
	$(document).on('click', '#opportunity-lineitems-body .lineitem-pricing .pricing-action-container .action-remove', function(event) {
		var $icon = $(this);
		var $price = $icon.closest('.lineitem-price');
		var price_id = $price.attr('price_id');

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/prices/delete',
			'method': 'POST',
			'data': {
				'price_id': price_id
			},
			'dataType': 'json',
			'success': function(response) {
				if(response.success) {
					$price.slideUp('fast', function(event) {
						$price.remove();
					});
				}
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	/**
	* Bind to changes on "FOB" drop-down.
	 */
	$(document).off('change', '#opportunity-lineitems-body .lineitem-pricing .pricing-fob-container :input[name="fob"]');
	$(document).on('change', '#opportunity-lineitems-body .lineitem-pricing .pricing-fob-container :input[name="fob"]', function(event) {
		var $this_fob_dropdown = $(this);
		var fob = $this_fob_dropdown.val();
		var $lineitem_pricing_container = $this_fob_dropdown.closest('.lineitem-pricing');
		var $fob_dropdowns = $lineitem_pricing_container.find(':input[name="fob"]').not($this_fob_dropdown);
		$.each($fob_dropdowns, function(offset, fob_dropdown) {
			var $fob_dropdown = $(fob_dropdown);
			if(!$fob_dropdown.val()) {
				$fob_dropdown.val(fob);
				update_pricing_field($fob_dropdown);
			}
		});
	});

	/**
	* Bind to changes on "Lead Time" drop-down.
	 */
	$(document).off('change', '#opportunity-lineitems-body .lineitem-pricing .pricing-leadtime-container :input[name="leadtime"]');
	$(document).on('change', '#opportunity-lineitems-body .lineitem-pricing .pricing-leadtime-container :input[name="leadtime"]', function(event) {
		var $this_leadtime_dropdown = $(this);
		var leadtime = $this_leadtime_dropdown.val();
		var $lineitem_pricing_container = $this_leadtime_dropdown.closest('.lineitem-pricing');
		var $leadtime_dropdowns = $lineitem_pricing_container.find(':input[name="leadtime"]').not($this_leadtime_dropdown);
		$.each($leadtime_dropdowns, function(offset, leadtime_dropdown) {
			var $leadtime_dropdown = $(leadtime_dropdown);
			if(!$leadtime_dropdown.val()) {
				$leadtime_dropdown.val(leadtime);
				update_pricing_field($leadtime_dropdown);
			}
		});
	});
	
	/**
	* Bind to changes on "Hide Part #" Checkbox.
	 */
	$(document).off('change', '#opportunity-lineitems-body :input[name="itmdesc_hide"]');
	$(document).on('change', '#opportunity-lineitems-body :input[name="itmdesc_hide"]', function(event) {
		var $input = $(this);
		var $lineitem = $input.closest('.lineitem');

		if($input.is(':checked')) {
			var value = '1';
		} else {
			var value = '0';
		}

		var data = {
			'opportunity_lineitem_id': $lineitem.attr('opportunity_lineitem_id'),
			'value': value
		};
		
		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/lineitems/set/itmdesc_hide',
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {}
		});
	});

	/**
	* Trigger change event on all margin inputs present.
	 */
	$('#opportunity-lineitems-body .lineitem :input[name="priceea"]').trigger('change');

	// Trigger description changes for forced resize.
	$('#opportunity-lineitems-body textarea.autofit').trigger('keydown');

</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
