<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

ini_set('max_execution_time', 3000);

$session->ensureLogin();

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

$args = array(
	'title' => 'Item By Sales',
	'breadcrumbs' => array(
		'Warehouse: Item By Sales' => BASE_URI . '/dashboard/inventory/sales'
	)
);

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	#item-sales-container .search-field-double {
		width:350px;
	}
</style>

<div id="item-sales-container">
	<div class="padded">
		<h3>
			Item By Sales
		</h3>
	</div>

	<form method="get" action="<?php print BASE_URI;?>/dashboard/inventory/sales">
		<input type="hidden" name="submit" value="1" />
		<div class="search-fields">
			<div class="search-field search-field-double">
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
				<div style="float:left;">
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
				<div style="float:left;">
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
		</div>
		<button type="submit" class="btn btn-primary">Search</button>
	</form>

	<hr />

	<?php
	if(isset($_REQUEST['submit'])) {
		$from = date('Y-m-d', strtotime('365 days ago'));
		$to = date('Y-m-d', time());
		$territory_arr = array_map([$db, 'quote'], $_REQUEST['territories']);
		$territory_str = implode(',', $territory_arr);
		$territory_sql = "artran.terr IN (" . $territory_str . ")";
		$grab_items = $db->query("
			SELECT
				artran.item,
				icitem.itmdesc,
				SUM(artran.qtyshp) AS sold,
				COUNT(artran.invno) AS invoices,
				COUNT(DISTINCT artran.custno) AS clients,
				(CASE WHEN SUM(artran.extprice) > 0 THEN
					(CASE WHEN SUM(artran.extprice) = 0 THEN
						0
					ELSE
						artran.extprice - ((artran.qtyshp * artran.cost) / artran.extprice)
					END) / SUM(artran.extprice)
				ELSE
					0
				END) AS margin
			FROM
				" . DB_SCHEMA_ERP . ".artran
			INNER JOIN
				" . DB_SCHEMA_ERP . ".icitem
				ON
				icitem.item = artran.item
			WHERE
				artran.invdte >= " . $db->quote($from) . "
				AND
				artran.invdte <= " . $db->quote($to) . "
				AND
				artran.item NOT IN (
					'_CREDT_MEMO_TAX', 'CC_APPROVAL', 'EXPEDITE', 'FREE-SHIP',
					'FRT', 'MISCCHARGE', 'OVERSTOCK', 'RESTOCKING', 'SHIP',
					'SHOP_INSTN'
				)
				AND
				" . $territory_sql . "
			GROUP BY
				artran.item,
				icitem.itmdesc,
				artran.extprice,
				artran.qtyshp,
				artran.cost
		");
		?>
		<table class="table table-small table-striped table-hover columns-sortable columns-filterable">
			<thead>
				<tr>
					<th class="sortable filterable">Item</th>
					<th class="sortable filterable">Part #</th>
					<th class="sortable filterable">Qty Sold</th>
					<th class="sortable filterable"># Invoices</th>
					<th class="sortable filterable"># Clients</th>
					<th class="sortable filterable">Margin</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_items as $item) {
					?>
					<tr class="item" item="<?php print htmlentities($item['item'], ENT_QUOTES);?>">
						<td><?php print htmlentities($item['item']);?></td>
						<td><?php print htmlentities($item['itmdesc']);?></td>
						<td><?php print number_format($item['sold'], 0);?></td>
						<td><?php print number_format($item['invoices'], 0);?></td>
						<td><?php print number_format($item['clients'], 0);?></td>
						<td><?php print number_format($item['margin'], 2);?></td>
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
	/**
	 * Defines the variable used in storing touch events for later comparison.
	 */
	var touch_target;

	/**
	 * Ensure options within "Available" drop-down are never selected.
	 */
	var available_option_click_fn = function(event) {
		var $option = $(this);

		var $available_container = $option.closest('.search-field-available');
		var $filterby_container = $available_container.closest('.search-field').find('.search-field-filterby');

		// Grab the name for the hiddne input to be added to the option.
		var input_name = $available_container.attr('input-name');

		// Grab the value for the hidden input to be added to the option.
		var input_value = $option.attr('input-value');

		$option.appendTo($filterby_container).append(
			$('<input type="hidden">').attr('name', input_name).attr('value', input_value)
		);

		// Sort Filter By options by name.
		var $filterby_options = $filterby_container.find('.search-field-value');
		$filterby_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$filterby_options.detach().appendTo($filterby_container);
	};
	$(document).on('click', '.search-field-available .search-field-value', available_option_click_fn);
	$(document).on('touchstart', '.search-field-available .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '.search-field-available .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});

	/**
	 * Bind to change events on Selected Statuses drop-down.
	 */
	var filterby_option_click_fn = function(event) {
		var $option = $(this);

		var $filterby_container = $option.closest('.search-field-filterby');
		var $available_container = $filterby_container.closest('.search-field').find('.search-field-available');

		$option.appendTo($available_container);
		$option.find('input').remove();

		// Sort Available options by name.
		var $available_options = $available_container.find('.search-field-value');
		$available_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$available_options.detach().appendTo($available_container);
	};
	$(document).on('click', '.search-field-filterby .search-field-value', filterby_option_click_fn);
	$(document).on('touchstart', '.search-field-filterby .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '.search-field-filterby .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});
	
	/**
	 * Bind to clicks on "+" and "-" icons within search fields.
	 */
	$(document).off('click', '.search-field-add'); // Prevents double-binding.
	$(document).on('click', '.search-field-add', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $available_container = $search_field_container.find('.search-field-available');
		$available_container.find('.search-field-value').click();
	});
	$(document).off('click', '.search-field-remove'); // Prevents double-binding.
	$(document).on('click', '.search-field-remove', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $filter_by_container = $search_field_container.find('.search-field-filterby');
		$filter_by_container.find('.search-field-value').click();
	});
</script>

<?php
Template::Render('footer', 'account');
