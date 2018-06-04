<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Warehouse: Inventory',
	'breadcrumbs' => array(
		'Warehouse: Inventory' => BASE_URI . '/dashboard/inventory'
	)
);

Template::Render('header', $args, 'account');

// Grab the orders to display on this page.
// We use prepare / execute so we can actually return the rowCount().
if(isset($_GET['submit'])) {
	$where = [
		'1 = 1'
	];

	/**
	 * Com Code Constraints
	 */
	$comcode_sql = array();
	if(!empty($_GET['comcode-select'])) { // Select (Amazon)
		$comcode_sql[] = "icitem.comcode = 'S'";
	}
	if(!empty($_GET['comcode-overstock'])) { // Overstock
		$comcode_sql[] = "icitem.comcode = 'OS'";
	}
	if(!empty($_GET['comcode-closeout'])) { // Closeout
		$comcode_sql[] = "icitem.comcode = 'CL'";
	}
	if(!empty($comcode_sql)) {
		$comcode_sql = '((' . implode(') OR (', $comcode_sql) . '))';
	} else {
		$comcode_sql = '';
	}
	if(!empty($comcode_sql)) {
		$where[] = $comcode_sql;
	}

	/**
	 * Stock Constraints
	 */ 
	if(empty($_GET['stock-in']) && empty($_GET['stock-out'])) { // Default
		$stock_sql = "iciqty.qonhand > 0";
	} else if(!empty($_GET['stock-in']) && !empty($_GET['stock-out'])) { // In Stock & Out Of Stock
		$stock_sql = '1=1';
	} else if(!empty($_GET['stock-in'])) { // In Stock
		$stock_sql = "iciqty.qonhand > 0";
	} else { // Out Of Stock
		$stock_sql = "iciqty.qonhand <= 0";
	}
	if(!empty($stock_sql)) {
		$where[] = $stock_sql;
	}

	/**
	 * Last Vendor
	 */
	$lastvendor_sql = '';
	if(!empty($_REQUEST['lastvendors'])) {
		$lastvendor_arr = array_map(array($db, 'quote'), $_REQUEST['lastvendors']);
		$lastvendor_str = implode(',', $lastvendor_arr);
		$lastvendor_sql = "iciloc.lsupplr IN (" . $lastvendor_str . ")";
		$where[] = $lastvendor_sql;
	}

	/**
	 * Location
	 */
	$location_sql = '';
	if(!empty($_REQUEST['locations'])) {
		$location_arr = array_map(array($db, 'quote'), $_REQUEST['locations']);
		$location_str = implode(',', $location_arr);
		$location_sql = "iciloc.loctid IN (" . $location_str . ")";
		$where[] = $location_sql;
		$where[] = 'iciqty.qonhand > 0';
	}

	/**
	 * Product Line
	 */
	$productline_sql = '';
	if(!empty($_REQUEST['productlines'])) {
		$productline_arr = array_map(array($db, 'quote'), $_REQUEST['productlines']);
		$productline_str = implode(',', $productline_arr);
		$productline_sql = "icitem.plinid IN (" . $productline_str . ")";
		$where[] = $productline_sql;
	}

	/**
	 * Class
	 */
	$class_sql = '';
	if(!empty($_REQUEST['classes'])) {
		$class_arr = array_map(array($db, 'quote'), $_REQUEST['classes']);
		$class_str = implode(',', $class_arr);
		$class_sql = "icitem.itmclss IN (" . $class_str . ")";
		$where[] = $class_sql;
	}

	$grab_orders_query = "
		SELECT DISTINCT
			icitem.item AS item,
			icitem.itmdesc AS part_number,
			iciloc.loctid AS location,
			iciqty.qonhand AS local_stock,
			icitem.ionhand AS total_stock,
			icitem.makeitr AS bom,
			icitem.lstcost AS last_cost,
			icitem.itmdes2 AS description,
			CONVERT(varchar(10), icitem.ilrecv, 120) AS last_received,
			CONVERT(varchar(10), icitem.ilsale, 120) AS last_sale,
			iciloc.lsupplr AS lastvendor,
			icitem.plinid AS productline,
			icitem.itmclss AS class
		FROM
			" . DB_SCHEMA_ERP . ".icitem
		INNER JOIN
			" . DB_SCHEMA_ERP . ".iciloc
			ON
			iciloc.item = icitem.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".iciqty
			ON
			iciloc.item = iciqty.item
			AND
			iciloc.loctid = iciqty.loctid
		WHERE
			" . implode(' AND ', $where) . "
		ORDER BY
			item
	";
	$grab_orders = $db->prepare($grab_orders_query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed equired for retrieving rowCount.
	$grab_orders->execute();
}

$grab_lastvendors = $db->query("
	SELECT DISTINCT
		LTRIM(RTRIM(iciloc.lsupplr)) AS vendor
	FROM
		" . DB_SCHEMA_ERP . ".iciloc
	ORDER BY
		vendor
");

$grab_locations = $db->query("
	SELECT DISTINCT
		LTRIM(RTRIM(iciloc.loctid)) AS location
	FROM
		" . DB_SCHEMA_ERP . ".iciloc
	ORDER BY
		location
");

$grab_productlines = $db->query("
	SELECT DISTINCT
		LTRIM(RTRIM(icitem.plinid)) AS productline
	FROM
		" . DB_SCHEMA_ERP . ".icitem
	ORDER BY
		productline
");

$grab_classes = $db->query("
	SELECT DISTINCT
		LTRIM(RTRIM(icitem.itmclss)) AS class
	FROM
		" . DB_SCHEMA_ERP . ".icitem
	ORDER BY
		class
");

?>

<div class="padded">
	<fieldset>
		<legend>Customize Inventory View</legend>
		<form id="dates-container" method="get" class="form-horizontal">
			<input type="hidden" name="submit" value="1" />
			<table>
				<thead>
					<tr>
						<th style="text-align:center;">Com Code</th>
						<th style="text-align:center;">Stock</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="vertical-align:top;">
							<div class="control-group">
								<label class="control-label" for="comcode-select">Select Only</label>
								<div class="controls">
									<input type="checkbox" name="comcode-select" id="comcode-select" value="1" <?php !empty($_GET['comcode-select']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="comcode-overstock">Overstock Only</label>
								<div class="controls">
									<input type="checkbox" name="comcode-overstock" id="comcode-overstock" value="1" <?php !empty($_GET['comcode-overstock']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="comcode-closeout">Closeout Only</label>
								<div class="controls">
									<input type="checkbox" name="comcode-closeout" id="comcode-closeout" value="1" <?php !empty($_GET['comcode-closeout']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
						</td>
						<td style="vertical-align:top;">
							<div class="control-group">
								<label class="control-label" for="stock-in">In Stock</label>
								<div class="controls">
									<input type="checkbox" name="stock-in" id="stock-in" value="1" <?php !empty($_GET['stock-in']) || !isset($_GET['submit']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="stock-out">Out Of Stock</label>
								<div class="controls">
									<input type="checkbox" name="stock-out" id="stock-out" value="1" <?php !empty($_GET['stock-out']) ? print 'checked="checked"' : Null;?> />
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="search-fields">
								<div class="search-field search-field-thin">
									<div class="search-field-title">Last Vendor</div>
									<div class="search-field-add">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-plus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="search-field-remove">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-minus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="control-group">
										<label class="control-label" />Available</label>
										<div class="controls">
											<div class="search-field-available" input-name="lastvendors[]">
												<?php
												foreach($grab_lastvendors as $lastvendor) {
													if(empty($_REQUEST['lastvendors']) || !in_array($lastvendor['vendor'], $_REQUEST['lastvendors'])) {
														?><div class="search-field-value" input-value="<?php print htmlentities($lastvendor['vendor'], ENT_QUOTES);?>"><?php print htmlentities($lastvendor['vendor']);?></div><?php
													}
												}
												?>
											</div>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" />Filter By</label>
										<div class="controls">
											<div class="search-field-filterby">
												<?php
												if(!empty($_REQUEST['lastvendors'])) {
													foreach($_REQUEST['lastvendors'] as $lastvendor) {
														?>
														<div class="search-field-value" input-value="<?php print htmlentities($lastvendor, ENT_QUOTES);?>">
															<?php print htmlentities($lastvendor);?>
															<input type="hidden" name="lastvendors[]" value="<?php print htmlentities($lastvendor, ENT_QUOTES);?>" />
														</div>
														<?php
													}
												}
												?>
											</div>
										</div>
									</div>
								</div>

								<div class="search-field search-field-thin">
									<div class="search-field-title">Location</div>
									<div class="search-field-add">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-plus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="search-field-remove">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-minus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="control-group">
										<label class="control-label" />Available</label>
										<div class="controls">
											<div class="search-field-available" input-name="locations[]">
												<?php
												foreach($grab_locations as $location) {
													if(empty($_REQUEST['locations']) || !in_array($location['location'], $_REQUEST['locations'])) {
														?><div class="search-field-value" input-value="<?php print htmlentities($location['location'], ENT_QUOTES);?>"><?php print htmlentities($location['location']);?></div><?php
													}
												}
												?>
											</div>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" />Filter By</label>
										<div class="controls">
											<div class="search-field-filterby">
												<?php
												if(!empty($_REQUEST['locations'])) {
													foreach($_REQUEST['locations'] as $location) {
														?>
														<div class="search-field-value" input-value="<?php print htmlentities($location, ENT_QUOTES);?>">
															<?php print htmlentities($location);?>
															<input type="hidden" name="locations[]" value="<?php print htmlentities($location, ENT_QUOTES);?>" />
														</div>
														<?php
													}
												}
												?>
											</div>
										</div>
									</div>
								</div>

								<div class="search-field search-field-thin">
									<div class="search-field-title">Product Line</div>
									<div class="search-field-add">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-plus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="search-field-remove">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-minus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="control-group">
										<label class="control-label" />Available</label>
										<div class="controls">
											<div class="search-field-available" input-name="productlines[]">
												<?php
												foreach($grab_productlines as $productline) {
													if(empty($_REQUEST['productlines']) || !in_array($productline['productline'], $_REQUEST['productlines'])) {
														?><div class="search-field-value" input-value="<?php print htmlentities($productline['productline'], ENT_QUOTES);?>"><?php print htmlentities($productline['productline']);?></div><?php
													}
												}
												?>
											</div>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" />Filter By</label>
										<div class="controls">
											<div class="search-field-filterby">
												<?php
												if(!empty($_REQUEST['productlines'])) {
													foreach($_REQUEST['productlines'] as $productline) {
														?>
														<div class="search-field-value" input-value="<?php print htmlentities($productline, ENT_QUOTES);?>">
															<?php print htmlentities($productline);?>
															<input type="hidden" name="productlines[]" value="<?php print htmlentities($productline, ENT_QUOTES);?>" />
														</div>
														<?php
													}
												}
												?>
											</div>
										</div>
									</div>
								</div>

								<div class="search-field search-field-thin">
									<div class="search-field-title">Class</div>
									<div class="search-field-add">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-plus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="search-field-remove">
										<span class="fa-stack fa-lg">
											<i class="fa fa-circle fa-stack-2x"></i>
											<i class="fa fa-minus fa-stack-1x fa-inverse"></i>
										</span>
									</div>
									<div class="control-group">
										<label class="control-label" />Available</label>
										<div class="controls">
											<div class="search-field-available" input-name="classes[]">
												<?php
												foreach($grab_classes as $class) {
													if(empty($_REQUEST['classes']) || !in_array($class['class'], $_REQUEST['classes'])) {
														?><div class="search-field-value" input-value="<?php print htmlentities($class['class'], ENT_QUOTES);?>"><?php print htmlentities($class['class']);?></div><?php
													}
												}
												?>
											</div>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" />Filter By</label>
										<div class="controls">
											<div class="search-field-filterby">
												<?php
												if(!empty($_REQUEST['classes'])) {
													foreach($_REQUEST['classes'] as $class) {
														?>
														<div class="search-field-value" input-value="<?php print htmlentities($class, ENT_QUOTES);?>">
															<?php print htmlentities($class);?>
															<input type="hidden" name="classes[]" value="<?php print htmlentities($class, ENT_QUOTES);?>" />
														</div>
														<?php
													}
												}
												?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="control-group">
								<div class="controls">
									<button class="btn btn-primary" type="submit">
										<i class="fa fa-calendar fa-fw"></i>
										Retrieve
									</button>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</fieldset>
</div>

<?php
if(isset($_GET['submit'])) {
	?>
	<fieldset>
		<legend>
			<div class="padded-x">
				Inventory Matching Criteria:
				<span id="order-count"><?php print number_format($grab_orders->rowCount());?></span>
			</div>
		</legend>
		<table id="inventory-container" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
			<thead>
				<tr>
					<th class="filterable sortable">Item</th>
					<th class="filterable sortable">Part #</th>
					<th class="filterable sortable">Location</th>
					<th class="filterable sortable">Local Stock</th>
					<th class="filterable sortable">Total Stock</th>
					<th class="filterable sortable">BOM</th>
					<th class="filterable sortable">Last Cost</th>
					<th class="filterable sortable">Description</th>
					<th class="filterable sortable">Last Received</th>
					<th class="filterable sortable">Last Sale</th>
					<th class="filterable sortable">Last Vendor</th>
					<th class="filterable sortable">Product Line</th>
					<th class="filterable sortable">Class</th>
				</tr>
			<tbody>
				<?php
				while($order = $grab_orders->fetch()) {
					$overlayz_url = BASE_URI . '/dashboard/inventory/item-details';
					$overlayz_data = htmlentities(
						json_encode(
							array(
								'item-number' => $order['item']
							)
						),
						ENT_QUOTES
					);
					?>
					<tr class="stripe" part-number="<?php print htmlentities($order['part_number'], ENT_QUOTES);?>">
						<td class="content content-item overlayz-link" overlayz-url="<?php print $overlayz_url;?>" overlayz-data="<?php print $overlayz_data;?>"><?php print htmlentities($order['item']);?></td>
						<td class="content content-part-number"><?php print htmlentities($order['part_number']);?></td>
						<td class="content content-location"><?php print htmlentities($order['location']);?></td>
						<td class="content content-stock"><?php
							print number_format($order['local_stock']);
						?></td>
						<td class="content content-stock"><?php
							print number_format($order['total_stock']);
						?></td>
						<td class="content content-bom"><?php
							if($order['bom'] == 1) {
								print 'BOM';
							}
						?></td>
						<td class="content content-last-cost">$<?php
							print number_format($order['last_cost'], 2);
						?></td>
						<td class="content content-description"><?php print htmlentities($order['description']);?></td>
						<td class="content content-last-received"><?php print htmlentities($order['last_received']);?></td>
						<td class="content content-last-sale"><?php print htmlentities($order['last_sale']);?></td>
						<td class="content content-lastvendor"><?php print htmlentities($order['lastvendor']);?></td>
						<td class="content content-productline"><?php print htmlentities($order['productline']);?></td>
						<td class="content content-class"><?php print htmlentities($order['class']);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>
	<?php
}

Template::Render('footer', 'account');
