<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

// \\glc-dc1\Godex EZPi-1300

$session->ensureLogin();

$label_printer = new LabelPrinter($_GET['printer']);
$location = $label_printer->getLocationData();
$printer = $label_printer->getPrinterData();

$args = array(
	'title' => 'Labels',
	'breadcrumbs' => array(
		'Warehouse: Labels' => BASE_URI . '/dashboard/labels',
		$location['locdesc'] => BASE_URI . '/dashboard/labels'
	)
);

Template::Render('header', $args, 'account');
?>

<div class="padded">
	<fieldset>
		<legend>Labels</legend>
	</fieldset>

	<?php

	if($_GET['print'] == 'bin') {
		$labels_result = $label_printer->getBins($_GET['query-by-input']);
	} else if($_GET['print'] == 'product') {
		$labels_result = $label_printer->getProducts($_GET['query-by'], $_GET['query-by-input']);
	}

	if(!$labels_result->rowCount()) {
		?>
		<h3>ERROR</h3>
		Your input did not yield any matches.
		<?php
	} else if(isset($_POST['submit'])) {
		?>
		<h3>PRINTING</h3>
		Please check your printer.
		<br /><br />
		If nothing printed, please go back and ensure you selected the proper printer.
		<?php

		if($_GET['print'] == 'bin') {
			$curr = 0;
			while($curr < $_REQUEST['quantity']) {
				$curr++;
				$label_printer->printBins($labels_result);
			}
		} else if($_GET['print'] == 'product') {
			$curr = 0;
			while($curr < $_REQUEST['quantity']) {
				$curr++;
				$label_printer->printProducts($labels_result);
			}
		}
	} else {
		?>
		<b><?php
			if($_GET['print'] == 'bin') {
				?>Bin Location<?php
			} else if($_GET['print'] == 'product') {
				?>Product Box<?php
			}
		?></b>: Will Print <b><?php print $labels_result->rowCount();?>
		</b> labels to <b><?php print htmlentities($printer['printer']);?></b>
		<br /><br />

		<table class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky">
			<?php
			$first_row = True;
			foreach($labels_result as $result) {
				if($first_row) {
					$first_row = False;
					?>
					<thead>
						<tr>
							<?php
							foreach($result as $name => $val) {
								if(is_numeric($name)) {
									continue;
								}
								?>
								<th class="filterable sortable"><?php print htmlentities($name);?></th>
								<?php
							}
							?>
						</tr>
					</head>
					<tbody>
					<?php
				}
				?>
				<tr>
					<?php
					foreach($result as $row_key => $row_value) {
						if(is_numeric($row_key)) {
							continue;
						}
						if($row_key == 'recdate') {
							$row_value = explode(' ', $row_value);
							$row_value = $row_value[0];
						}
						?>
						<td><?php print htmlentities($row_value);?></td>
						<?php
					}
					?>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<br />
		<form method="post">
			<input type="hidden" name="submit" value="1" />
			<button type="submit">Confirm and Print</button>
		</form>
		<?php
	}
	?>
</div>
<?php

Template::Render('footer', 'account');
