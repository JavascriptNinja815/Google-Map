<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_sabos = $db->query("
	SELECT
		sabo.saboid,
		sabo.type,
		sabo.ponum AS client_ponum,
		sabo.addate,
		sabo.startdate,
		sabo.enddate,
		sabo.adduser,
		sabo.custsig,
		sabo.custsigtit,
		sabo.cdab1,
		sabo.cdab2,
		sabo.ponum
	FROM
		" . DB_SCHEMA_ERP . ".sabo
	WHERE
		RTRIM(LTRIM(sabo.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	ORDER BY
		sabo.enddate ASC
");

?>

<style type="text/css">
	#client-details-container .sabos .sabo.prototype {
		display:none;
	}
	#client-details-container .sabos .saboitems-row .sabos-loading {
		width:120px;
		margin:auto;
		text-align:center;
	}
	#client-details-containe .action-add-sabo {
		display:inline-block;
		margin-top:6px;
	}
</style>

<h2>Stocking Agreements & Blanket Orders</h2>

<table class="sabos">
	<thead>
		<tr>
			<th></th>
			<th>Agreement No</th>
			<th>Type</th>
			<th>Customer PO</th>
			<th>Add Date</th>
			<th>Start Date</th>
			<th>Expires Date</th>
			<th>Add User</th>
			<th>Client Approver</th>
			<th>Approver Title</th>
			<th>CD Approver 1</th>
			<th>CD Approver 2</th>
		</tr>
	</thead>
	<tbody>
		<tr class="sabo prototype">
			<td class="actions">
				<i class="fa fa-toggle-down action action-toggle"></i>
			</td>
			<td class="agreementno"></td>
			<td class="type"></td>
			<td class="ponum"></td>
			<td class="adddate"></td>
			<td class="startdate"></td>
			<td class="enddate"></td>
			<td class="adduser"></td>
			<td class="custsig"></td>
			<td class="custsigtit"></td>
			<td class="approver1"></td>
			<td class="approver2"></td>
		</tr>
		<?php
		foreach($grab_sabos as $sabo) {
			?><tr class="sabo sabo-<?php print htmlentities(strtolower($sabo['type']), ENT_QUOTES);?>" saboid="<?php print htmlentities($sabo['saboid'], ENT_QUOTES);?>"><?php
				?><td class="actions"><?php
					?><i class="fa fa-toggle-down action action-toggle action-toggle-saboitems"></i><?php
				?></td><?php
				?><td class="agreementno"><?php print htmlentities($sabo['saboid']);?></td><?php
				?><td class="type"><?php print htmlentities(strtoupper($sabo['type']));?></td><?php
				?><td class="ponum"><?php print htmlentities($sabo['ponum']);?></td><?php
				?><td class="adddate"><?php print htmlentities($sabo['addate']);?></td><?php
				?><td class="startdate"><?php print htmlentities($sabo['startdate']);?></td><?php
				?><td class="enddate"><?php print htmlentities($sabo['enddate']);?></td><?php
				?><td class="adduser"><?php print htmlentities($sabo['adduser']);?></td><?php
				?><td class="custsig"><?php print htmlentities($sabo['custsig']);?></td><?php
				?><td class="custsigtit"><?php print htmlentities($sabo['custsigtit']);?></td><?php
				?><td class="approver1"><?php print htmlentities($sabo['cdab1']);?></td><?php
				?><td class="approver2"><?php print htmlentities($sabo['cdab2']);?></td><?php
			?></tr><?php
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="16">
				<span class="action action-add-sabo overlayz-link" overlayz-url="/dashboard/clients/details/sa-bo/edit" overlayz-data="<?php print htmlentities(json_encode([
						'custno' => trim($_POST['custno'])
					])
				);?>">
					<i class="fa fa-plus"></i>
					Add SA/BO
				</span>
			</td>
		</tr>
	</tfoot>
</table>

<script type="text/javascript">
	// Bind to clicks on "Toggle SA/BO Item Display" icon.
	$(document).off('click', '#client-details-page .sabos .sabo .action-toggle-saboitems');
	$(document).on('click', '#client-details-page .sabos .sabo .action-toggle-saboitems', function(event) {
		var $icon = $(this);
		var $sabo = $icon.closest('.sabo');
		var $saboitems_row = $sabo.next();
		var saboid = $sabo.attr('saboid');
		if($saboitems_row.hasClass('saboitems-row')) {
			// Currently shown, hide and delete.
			$saboitems_row.children().slideUp(function() {
				$saboitems_row.remove();
			});
		} else { // Not currently shown.
			// Create the SA/BO items row/container.
			var $saboitems_row = $('<tr class="saboitems-row">');
			var $saboitems_container = $('<td class="saboitems-container" colspan="12">').appendTo($saboitems_row);
			// Add a loading animation to the container.
			var $saboitems_loading = $('<div class="sabos-loading">').appendTo($saboitems_container);
			// Insert the SA/BO items row directly after the SA/BO's row.
			$saboitems_row.insertAfter($sabo);

			// Perform the AJAX query to populate the SA/BO items container.
			$.ajax({
				'url': BASE_URI + '/dashboard/clients/details/sa-bo/items',
				'method': 'POST',
				'dataType': 'json',
				'data': {
					'saboid': saboid
				},
				'success': function(response) {
					if(response.success) {
						var $saboitems = $(response.html);
						$saboitems.hide();
						$saboitems.appendTo($saboitems_container);

						// Remove the loading animation gracefully.
						$saboitems_loading.slideUp('fast', function() {
							$saboitems_loading.remove();
						});
						// Animate the SA/BO items into view.
						$saboitems.slideDown('fast');
					} else if(response.message) {
						alert(response.message);
						$saboitems_row.remove(); // Something's not right, remove the row.
					} else {
						alert('Something didn\'t go right');
						$saboitems_row.remove(); // Something's not right, remove the row.
					}
				},
				'error': function(response) {
					alert('Something didn\'t go right');
					$saboitems_row.remove(); // Something's not right, remove the row.
				}
			});
		}
	});
</script>

<?php
