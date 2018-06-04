<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Re-Order Points',
	'breadcrumbs' => array(
		'Re-Order Points' => BASE_URI . '/dashboard/inventory/reorder-points'
	),
	'body-class' => 'padded'
);

$grab_locations = $db->query("
	SELECT
		LTRIM(RTRIM(icloct.loctid)) AS loctid
	FROM
		" . DB_SCHEMA_ERP . ".icloct
	WHERE
		icloct.loctid IN ('DC', 'DET', 'EGV', 'VA')
	ORDER BY
		icloct.loctid
");

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	#reorderpoints-container .locations .location {
		display:inline-block;
		width:180px;
		padding:12px;
	}
	#reorderpoints-container .locations .location:hover {
		background-color:#eee;
	}
	#reorderpoints-container .item.shortage:hover td,
	#reorderpoints-container .item.shortage td {
		background-color:#fcc;
	}
	#reorderpoints-container .right {
		text-align:right;
	}
</style>

<h2>Re-Order Points</h2>

<div id="reorderpoints-container">
	<div class="locations">
		<?php
		foreach($grab_locations as $location) {
			?>
			<label class="location" loctid="<?php print htmlentities($location['loctid'], ENT_QUOTES);?>">
				<input type="radio" name="loctid" value="<?php print htmlentities($location['loctid'], ENT_QUOTES);?>" <?php print isset($_REQUEST['loctid']) && $_REQUEST['loctid'] == $location['loctid'] ? 'checked' : Null;?> />
				<?php print htmlentities($location['loctid']);?>
			</label>
			<?php
		}
		?>
	</div>

	<?php
	if(isset($_REQUEST['loctid'])) {
		$grab_items = $db->query("
			SELECT
				icitem.item,
				icitem.itmdesc,
				iciloc.lonhand,
				iciloc.orderpt,
				(iciloc.lsoaloc + iciloc.lwoaloc) - (iciloc.lonhand + iciloc.lonordr) AS shortage,
				iciloc.lonordr,
				iciloc.lsoaloc,
				iciloc.lwoaloc,
				(
					SELECT
						ABS(SUM(ictran.tqty))
					FROM
						" . DB_SCHEMA_ERP . ".ictran
					WHERE
						DATEDIFF(day, ictran.tdate, GETDATE()) <= 45
						AND
						ictran.trantyp = ' I'
						AND
						ictran.loctid = " . $db->quote($_REQUEST['loctid']) . "
						AND
						ictran.item = icitem.item
				) AS so_45,
				(
					SELECT
						ABS(SUM(ictran.tqty))
					FROM
						" . DB_SCHEMA_ERP . ".ictran
					WHERE
						DATEDIFF(day, ictran.tdate, GETDATE()) <= 45
						AND
						ictran.trantyp = 'EI'
						AND
						ictran.loctid = " . $db->quote($_REQUEST['loctid']) . "
						AND
						ictran.item = icitem.item
				) AS wo_45,
				(
					SELECT
						ABS(SUM(ictran.tqty))
					FROM
						" . DB_SCHEMA_ERP . ".ictran
					WHERE
						DATEDIFF(day, ictran.tdate, GETDATE()) <= 180
						AND
						ictran.trantyp = ' I'
						AND
						ictran.loctid = " . $db->quote($_REQUEST['loctid']) . "
						AND
						ictran.item = icitem.item
				) AS so_180,
				(
					SELECT
						ABS(SUM(ictran.tqty))
					FROM
						" . DB_SCHEMA_ERP . ".ictran
					WHERE
						DATEDIFF(day, ictran.tdate, GETDATE()) <= 180
						AND
						ictran.trantyp = 'EI'
						AND
						ictran.loctid = " . $db->quote($_REQUEST['loctid']) . "
						AND
						ictran.item = icitem.item
				) AS wo_180
			FROM
				" . DB_SCHEMA_ERP . ".iciloc
			INNER JOIN
				" . DB_SCHEMA_ERP . ".icitem
				ON
				icitem.item = iciloc.item
			WHERE
				iciloc.orderpt > 0
				AND
				iciloc.loctid = " . $db->quote($_REQUEST['loctid']) . "
			ORDER BY
				icitem.item
		");
		?>
		<h3>Inventory With Re-Order Points</h3>
		<table class="items-table table table-small table-striped table-hover columns-sortable columns-filterable">
			<thead>
				<tr>
					<th class="filterable sortable">Item Code</th>
					<th class="filterable sortable">Part #</th>
					<th class="filterable sortable">On Hand</th>
					<th class="filterable sortable">Order Point</th>
					<th class="filterable sortable">Shortage</th>
					<th class="filterable sortable">Open PO</th>
					<th class="filterable sortable">Open SO</th>
					<th class="filterable sortable">Open WO</th>
					<th class="filterable sortable">SO 45 Day Usage</th>
					<th class="filterable sortable">WO 45 Day Usage</th>
					<th class="filterable sortable">SO 180 Day Usage</th>
					<th class="filterable sortable">WO 180 Day Usage</th>
				</tr>
			</thead>
			<tbody class="items-tbody">
				<?php
				foreach($grab_items as $item) {
					$shortage = $item['shortage'] > 0 ? 'shortage' : Null;
					?>
					<tr class="item <?php print $shortage;?>">
						<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities(json_encode(['item-number' => $item['item']]), ENT_QUOTES);?>"><?php print htmlentities($item['item']);?></td>
						<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities(json_encode(['item-number' => $item['item']]), ENT_QUOTES);?>"><?php print htmlentities($item['itmdesc']);?></td>
						<td><?php print number_format($item['lonhand'], 0);?></td>
						<td><?php print number_format($item['orderpt'], 0);?></td>
						<td><?php print $item['shortage'] > 0 ? number_format($item['shortage'], 0) : Null;?></td>
						<td><?php print number_format($item['lonordr'], 0);?></td>
						<td><?php print number_format($item['lsoaloc'], 0);?></td>
						<td><?php print number_format($item['lwoaloc'], 0);?></td>
						<td><?php print number_format($item['so_45'], 0);?></td>
						<td><?php print number_format($item['wo_45'], 0);?></td>
						<td><?php print number_format($item['so_180'], 0);?></td>
						<td><?php print number_format($item['wo_180'], 0);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	?>
</div>

<script type="text/javascript">
	$(document).off('click', '#reorderpoints-container .locations .location');
	$(document).on('click', '#reorderpoints-container .locations .location', function(event) {
		var $location = $(this);
		var loctid = $location.attr('loctid');
		window.location = BASE_URI + '/dashboard/inventory/reorder-points?loctid=' + loctid;
	});
</script>

<?php Template::Render('footer', 'account');
