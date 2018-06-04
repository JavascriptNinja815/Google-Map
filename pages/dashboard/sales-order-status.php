<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Sales');

$args = array(
	'title' => 'Orders: Sales Order Status',
	'breadcrumbs' => array(
		'Orders' => BASE_URI . '/dashboard/sales-order-status',
		'Sales Orders' => BASE_URI . '/dashboard/sales-order-status'
	)
);

Template::Render('header', $args, 'account');

$compid = COMPANY;
if(strlen($compid) === 1) {
	$compid = '0' . $compid;
}
$grab_order_statuses = $db->query("
	SELECT
		sycrlst.id_col AS order_status_id,
		RTRIM(LTRIM(sycrlst.chrvl)) AS order_status
	FROM
		" . DB_SCHEMA_PROSYS . ".sycrlst
	WHERE
		sycrlst.ruleid = 'ORDRSTAT'
		AND
		sycrlst.compid = " . $db->quote($compid) . "
	ORDER BY
		sycrlst.chrvl
");
$order_statuses = array('' => '');
foreach($grab_order_statuses as $order_status) {
	$order_statuses[$order_status['order_status']] = $order_status['order_status'];
}

/**
 * Date constraints
 */
if(isset($_GET['date_from'])) {
	$date_from_input = $_GET['date_from'];
	$date_from_timestamp = strtotime($date_from_input);
} else {
	$date_from_input = date('n/j/Y', time());
	$date_from_timestamp = time();
}
$date_from_sql = date('Y/m/d', $date_from_timestamp);

if(isset($_GET['date_to'])) {
	$date_to_input = $_GET['date_to'];
	$date_to_timestamp = strtotime($date_to_input);
} else {
	$date_to_input = date('n/j/Y', time());
	$date_to_timestamp = time();
}
$date_to_sql = date('Y/m/d', $date_to_timestamp);
if(empty($_GET['filterby-date']) || $_GET['filterby-date'] == 'duedate') {
	$date_column = 'somast.ordate';
} else if($_GET['filterby-date'] == 'adddate') {
	$date_column = 'somast.adddate';
}

/**
 * Status constraints
 */
$status_sql = array();
if(!empty($_GET['status-open'])) {
	$status_sql[] = "(somast.sotype IS NULL OR somast.sotype = '') AND (somast.sostat IS NULL OR somast.sostat = '')";
}
if(!empty($_GET['status-partial'])) {
	$status_sql[] = "somast.sotype = 'O' AND (somast.sostat IS NULL OR somast.sostat != 'C')";
}
if(!empty($_GET['status-closed'])) {
	$status_sql[] = "somast.sostat = 'C'";
}
if(!empty($status_sql)) {
	$status_sql = '((' . implode(') OR (', $status_sql) . ')) AND ';
} else {
	$status_sql = "(somast.sotype IS NULL OR somast.sotype = '') AND (somast.sostat IS NULL OR somast.sostat = '') AND ";
}

/**
 * Order Status Constraints
 */
$orderstat_sql = '';
if(!empty($_REQUEST['orderstat'])) {
	$orderstat_arr = array_map(array($db, 'quote'), $_REQUEST['orderstat']);
	$orderstat_str = implode(',', $orderstat_arr);
	$orderstat_sql = "somast.orderstat IN (" . $orderstat_str . ") AND ";
}

/**
 * Territory Constraints
 */
$territory_sql = '';
if(!empty($_REQUEST['territories'])) {
	$territory_arr = array_map(array($db, 'quote'), $_REQUEST['territories']);
	$territory_str = implode(',', $territory_arr);
	$territory_sql = "somast.terr IN (" . $territory_str . ") AND ";
}

/**
 * Location Constraints
 */
$location_sql = '';
if(!empty($_REQUEST['locations'])) {
	$location_arr = array_map(array($db, 'quote'), $_REQUEST['locations']);
	$location_str = implode(',', $location_arr);
	$location_sql = "somast.defloc IN (" . $location_str . ") AND ";
}

// Grab the orders to display on this page.
// We use prepare / execute so we can actually return the rowCount().
$grab_orders_query = "
	SELECT
		somast.id_col                  AS order_id,           -- Order ID
		LTRIM(RTRIM(somast.orderstat)) AS order_status,       -- Order Status
		somast.terr                    AS territory,          -- Warehouse Territory
		somast.defloc                  AS location,           -- Warehouse Location
		somast.sono                    AS sales_order_number, -- Sales Order Number
		somast.custno                  AS customer_code,      -- Customer Code
		somast.ponum                   AS customer_po,        -- Customer Purchase Order Number
		somast.shipvia                 AS ship_via,           -- Ship Via... Shipping Carrier
		LTRIM(RTRIM(somast.salesmn))   AS sales_person,       -- Sales Person
		somast.adduser                 AS who_entered,        -- Who Entered
		somast.pterms                  AS payment_terms,      -- Payment Terms
		arcust.cstmemo                 AS customer_notes,     -- Customer Notes
		somast.notes                   AS order_notes,        -- Order Notes
		CONVERT(varchar(10), somast.adddate, 120)                 AS input_date,         -- Add Date
		DATEDIFF(dd,somast.ordate,getdate())	AS daysSinceDue,																				-- Days to Date Due
		CONVERT(varchar(10), somast.ordate, 120)                  AS due_date,           -- Due Date
		--somast.addtime AS input_time -- Input Time
		--sotran.loctid,
		somast.sostat                  AS sales_order_status,       -- Sales Order Status
		somast.sotype                  AS sales_order_type          -- Sales Order Type
	FROM
		" . DB_SCHEMA_ERP . ".somast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".sotran
		ON
		sotran.id_col = (
			-- This query ensures multiple rows aren't encountered matching in `sotran`.
			SELECT
				TOP (1) id_col
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			WHERE
				sono = somast.sono
		)
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.id_col = (
			-- This query ensures multiple rows aren't encountered matching in `arcust`.
			SELECT
				TOP (1) id_col
			FROM
				" . DB_SCHEMA_ERP . ".arcust
			WHERE
				custno = somast.custno
		)
	WHERE
		" . $orderstat_sql . "
		" . $territory_sql . "
		" . $location_sql . "
		" . $status_sql . "
		somast.sostat != 'V' -- V = Void
		AND
		somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
		" . (
			$session->hasRole('Administration') ?
				// Administrative roles get acess to everything.
				''
			:
				// Add SQL to limig results based on this login's Sales
				// "view-order" permissions.
				"
					AND
					somast.salesmn IN ('" . implode(
						"','",
						$session->getPermissions('Sales', 'view-orders')
					) . "')
				"
		) . "
		AND
		" . $date_column . "
			BETWEEN
			" . $db->quote($date_from_sql) . "
			AND
			" . $db->quote($date_to_sql) . "
	ORDER BY
		somast.ordate DESC
";
$grab_orders = $db->prepare($grab_orders_query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_orders->execute();

$grab_territories = $db->query("
	SELECT
		settings.value AS territory
	FROM
		" . DB_SCHEMA_INTERNAL . ".settings
	WHERE
		settings.name = 'Territories'
	ORDER BY
		settings.value
");

$grab_locations = $db->query("
	SELECT
		settings.value AS location
	FROM
		" . DB_SCHEMA_INTERNAL . ".settings
	WHERE
		settings.name = 'Locations'
	ORDER BY
		settings.value
");

?>
<div style="display:none;"><?php print htmlentities($grab_orders_query);?></div>

<div class="padded">
	<fieldset>
		<legend>Search Orders</legend>
		<form id="dates-container" method="get" class="form-horizontal">
			<input type="hidden" name="submit" value="date" />

			<div class="search-fields">
				<div class="search-field">
					<div class="search-field-title">SO Status</div>
					<div class="control-group">
						<label class="control-label" for="status-open">Open Orders</label>
						<div class="controls">
							<input type="checkbox" name="status-open" id="status-open" value="1" <?php !empty($_REQUEST['status-open']) || !isset($_REQUEST['submit']) ? print 'checked="checked"' : Null;?> />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="status-partial">Partially Shipped</label>
						<div class="controls">
							<input type="checkbox" name="status-partial" id="status-partial" value="1" <?php !empty($_REQUEST['status-partial']) ? print 'checked="checked"' : Null;?> />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="status-closed">Closed Orders</label>
						<div class="controls">
							<input type="checkbox" name="status-closed" id="status-closed" value="1" <?php !empty($_REQUEST['status-closed']) ? print 'checked="checked"' : Null;?> />
						</div>
					</div>
				</div>
				<div class="search-field">
					<div class="search-field-title">Date</div>
					<div class="control-group">
						<label class="control-label" for="datepicker-from">Search By...</label>
						<div class="controls">
							<select class="span2" name="filterby-date">
								<option value="duedate" <?php empty($_REQUEST['filterby-date']) || $_REQUEST['filterby-date'] == 'duedate' ? print 'selected="selected"' : Null;?>>Due Date</option>
								<option value="adddate" <?php !empty($_REQUEST['filterby-date']) && $_REQUEST['filterby-date'] == 'adddate' ? print 'selected="selected"' : Null;?>>Add Date</option>
							</select>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="datepicker-from">From</label>
						<div class="controls">
							<input class="span2" type="text" name="date_from" id="datepicker-from" value="<?php print htmlentities($date_from_input, ENT_QUOTES);?>" />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="datepicker-to" />To</label>
						<div class="controls">
							<input class="span2" type="text" name="date_to" id="datepicker-to" value="<?php print htmlentities($date_to_input, ENT_QUOTES);?>" />
						</div>
					</div>
				</div>

				<div class="search-field search-field-thin">
					<div class="search-field-title">Status</div>
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
						<label class="control-label">Available</label>
						<div class="controls">
							<div class="search-field-available" input-name="orderstat[]">
								<?php
								foreach($order_statuses as $order_status) {
									if(empty($_REQUEST['orderstat']) || !in_array($order_status, $_REQUEST['orderstat'])) {
										?>
										<div class="search-field-value" input-value="<?php print htmlentities($order_status, ENT_QUOTES);?>"><?php print htmlentities($order_status);?></div>
										<?php
									}
								}
								?>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Filter By</label>
						<div class="controls">
							<div class="search-field-filterby">
								<?php
								if(!empty($_REQUEST['orderstat'])) {
									foreach($_REQUEST['orderstat'] as $order_status) {
										?>
										<div class="search-field-value" input-value="<?php print htmlentities($order_status, ENT_QUOTES);?>">
											<?php print htmlentities($order_status);?>
											<input type="hidden" name="orderstat[]" value="<?php print htmlentities($order_status, ENT_QUOTES);?>" />
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
					<div class="search-field-title">Territory</div>
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
						<label class="control-label">Available</label>
						<div class="controls">
							<div class="search-field-available" input-name="territories[]">
								<?php
								foreach($grab_territories as $territory) {
									if(empty($_REQUEST['territories']) || !in_array($territory['territory'], $_REQUEST['territories'])) {
										?><div class="search-field-value" input-value="<?php print htmlentities($territory['territory'], ENT_QUOTES);?>"><?php print htmlentities($territory['territory']);?></div><?php
									}
								}
								?>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Filter By</label>
						<div class="controls">
							<div class="search-field-filterby">
								<?php
								if(!empty($_REQUEST['territories'])) {
									foreach($_REQUEST['territories'] as $territory) {
										?>
										<div class="search-field-value" input-value="<?php print htmlentities($territory, ENT_QUOTES);?>">
											<?php print htmlentities($territory);?>
											<input type="hidden" name="territories[]" value="<?php print htmlentities($territory, ENT_QUOTES);?>" />
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
						<label class="control-label">Available</label>
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
						<label class="control-label">Filter By</label>
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
			</div>

			<div class="control-group">
				<div class="controls">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-calendar fa-fw"></i>
						Retrieve
					</button>
				</div>
			</div>

		</form>
	</fieldset>
</div>

<fieldset>
	<legend>
		<div class="padded-x">
			Sales Orders Matching Criteria:
			<span id="order-count"><?php print number_format($grab_orders->rowCount());?></span>
		</div>
	</legend>
	<table id="orders-container" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
		<thead>
			<tr>
				<th class="sortable">Order Status</th>
				<th class="sortable" title="Sales Order Status"><i class="fa fa-dropbox fw"></i></th>
				<th class="filterable dragtable-drag-handle" title="Sales Order Number">SO #</th>
				<th class="filterable dragtable-drag-handle">Territory</th>
				<th class="filterable dragtable-drag-handle">Location</th>
				<th class="filterable sortable dragtable-drag-handle">Customer</th>
				<th class="filterable sortable dragable-drag-handle" title="Customer Purchase Order Number">Customer PO</th>
				<th class="filterable sortable">Ship Via</th>
				<th class="filterable sortable">Sales Person</th>
				<th class="filterable sortable">Who Entered</th>
				<th class="filterable sortable">Payment Terms</th>
				<th class="sortable">Customer Notes</th>
				<th class="sortable">Order Notes</th>
				<th class="filterable">Add Date</th>
				<th class="filterable">Due Date</th>
				<th class="sortable">Days</th>
				<th class="filterable">Lead Time</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while($order = $grab_orders->fetch()) {
				$input_date = new DateTime($order['input_date']);
				$due_date = new DateTime($order['due_date']);
				$lead_time = $input_date->diff($due_date);

				if($order['sales_order_status'] == 'C') {
					$shipping_status = 'closed';
				} else if(($order['sales_order_status'] == Null || trim($order['sales_order_status']) == '') && $order['sales_order_type'] == 'O') {
					$shipping_status = 'partial';
				} else {
					$shipping_status = 'open';
				}

				$overlayz_url = BASE_URI . '/dashboard/sales-order-status/so-details';
				$overlayz_data = htmlentities(
					json_encode(array(
						'so-number' => trim($order['sales_order_number'])
					)),
					ENT_QUOTES
				);
				?>
				<tr class="stripe sales-order-<?php print trim($order['sales_order_number']);?>" sales-order-number="<?php print trim($order['sales_order_number']);?>" order-id="<?php print trim($order['order_id']);?>">
					<td class="content content-order-status">
						<?php
						if($session->hasPermission('Sales', 'edit-orders', $order['sales_person'])) {
							?><div class="order-status-edit-container">
								<i class="order-status-edit-icon fa fa-pencil fa-fw"></i>
							</div><?php
						}
						?>
						<div class="order-status-container">
							<?php
							if(!empty($order['order_status'])) {
								print htmlentities($order['order_status']);
							} else {
								?><span class="hidden">z</span><?php
							}
							?>
						</div>
					</td>
					<td class="content content-po-status content-po-status-<?php print $shipping_status;?>">
						<?php
						if($shipping_status == 'closed') {
							// Closed Order
							?><i class="content-po-status-shipped fa fa-archive fw" title="Fully Shipped"></i><?php
							?><span class="hidden">a</span><?php
						} else if($shipping_status == 'partial') {
							// Partially Closed Order
							?><i class="content-po-status-shipped fa fa-dropbox fw" title="Partially Shipped"></i><?php
							?><span class="hidden">b</span><?php
						} else {
							// Open Order
							?><span class="hidden">x</span><?php
						}
						?>
					</td>
					<td class="content content-sales-order-number overlayz-link" overlayz-url="<?php print $overlayz_url;?>" overlayz-data="<?php print $overlayz_data;?>"><?php print htmlentities($order['sales_order_number']);?></td>
					<td class="content content-territory"><?php print htmlentities($order['territory']);?></td>
					<td class="content content-location"><?php print htmlentities($order['location']);?></td>
					<td class="content content-customer-number"><?php print htmlentities($order['customer_code']);?></td>
					<td class="content content-customer-po"><?php print htmlentities($order['customer_po']);?></td>
					<td class="content content-shipping-method"><?php print htmlentities($order['ship_via']);?></td>
					<td class="content content-sales-person"><?php print htmlentities($order['sales_person']);?></td>
					<td class="content content-enter-person"><?php print htmlentities($order['who_entered']);?></td>
					<td class="content content-payment-terms"><?php print htmlentities($order['payment_terms']);?></td>
					<td class="content content-customer-notes"><?php
						$customer_notes = trim($order['customer_notes']);
						if(!empty($customer_notes)) {
							?>
							<i class="fa fa-file-text notes-icon overlayz-link" overlayz-url="<?php print $overlayz_url;?>" overlayz-data="<?php print $overlayz_data;?>"></i>
							<span class="hidden">a</span>
							<?php
						} else {
							?>
							<span class="hidden">z</span>
							<?php
						}
					?></td>
					<td class="content content-order-notes">
						<?php
						$order_notes = trim($order['order_notes']);
						if(!empty($order_notes)) {
							?>
							<i class="fa fa-file-text notes-icon overlayz-link" overlayz-url="<?php print $overlayz_url;?>" overlayz-data="<?php print $overlayz_data;?>"></i>
							<span class="hidden">a</span>
							<?php
						} else {
							?>
							<span class="hidden">z</span>
							<?php
						}
						?>
					</td>
					<td class="content content-input-date"><?php print $order['input_date'];?></td>
					<td class="content content-due-date"><?php print $order['due_date'];?></td>
					<td class="content content-due-days"><?php print $order['daysSinceDue'];?></td>
					<td class="content content-lead-time"><?php print $lead_time->format('%a days');?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</fieldset>

<script type="text/javascript">
	var order_statuses = <?php print json_encode($order_statuses);?>;
</script>

<?php Template::Render('footer', 'account');
