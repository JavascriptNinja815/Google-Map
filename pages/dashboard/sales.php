<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */ 

$session->ensureLogin();
$session->ensureRole('Sales');

$args = array(
	'title' => 'Sales Dashboard',
	'breadcrumbs' => array(),
	'body-class' => 'padded'
);

$current_date = date('Y-m-d', time());

$grab_sales_goal = $db->prepare("
	SELECT
		goals.title,
		goals.goal
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goals.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		type = 'Sales Goal'
		AND
		'" . $current_date . "' > goals.start_date
		AND
		'" . $current_date . "' < goals.end_date
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_sales_goal->execute();

$grab_margin_goal = $db->prepare("
	SELECT
		goals.title,
		goals.goal
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goals.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		type = 'Margin Goal'
		AND
		'" . $current_date . "' > goals.start_date
		AND
		'" . $current_date . "' < goals.end_date
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_margin_goal->execute();

$grab_clientcount_goal = $db->prepare("
	SELECT
		goals.title,
		goals.goal
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goals.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		type = 'Client Count Goal'
		AND
		'" . $current_date . "' > goals.start_date
		AND
		'" . $current_date . "' < goals.end_date
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_clientcount_goal->execute();

$grab_newclientcount_goal = $db->prepare("
	SELECT
		goals.title,
		goals.goal
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goals.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		type = 'New Client Count Goal'
		AND
		'" . $current_date . "' > goals.start_date
		AND
		'" . $current_date . "' < goals.end_date
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_newclientcount_goal->execute();

$grab_newclientsales_goal = $db->prepare("
	SELECT
		goals.title,
		goals.goal
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		goals.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		type = 'New Client Sales Goal'
		AND
		'" . $current_date . "' > goals.start_date
		AND
		'" . $current_date . "' < goals.end_date
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_newclientsales_goal->execute();

Template::Render('header', $args, 'account');

$current_month = date('Y-m-1', time());
$last_month = date('Y-m-1', strtotime('Last Month'));

?>

<style type="text/css">
	#dashboard .sales-container {
		display:none;
	}
	#dashboard h3 {
		cursor:pointer;
		border-top:1px solid #eee;
	}
	#dashboard h3:hover {
		background-color:#eee;
	}
	#dashboard h3 .action-toggle {
		font-size:1em;
		min-width:30px;
	}
</style>

<div class="dashboard" id="dashboard">
	<!-- <div class="so-blocks">
		<div class="so-block-container so-block-pastdue">
			<div class="so-block-title">Past Due</div>
			<div class="so-block overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/sales/orders-due" overlayz-data="<?php print htmlentities(json_encode(['block' => 'Past Due']), ENT_QUOTES);?>">
				<div class="so-block-count">...</div>
			</div>
		</div>
		<div class="so-block-container so-block-today">
			<div class="so-block-title">Today</div>
			<div class="so-block overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/sales/orders-due" overlayz-data="<?php print htmlentities(json_encode(['block' => 'Today']), ENT_QUOTES);?>">
				<div class="so-block-count">...</div>
			</div>
		</div>
		<div class="so-block-container so-block-atrisk">
			<div class="so-block-title">At Risk</div>
			<div class="so-block">
				<div class="so-block-count">...</div>
			</div>
		</div>
		<div class="so-block-container so-block-mtdclients">
			<div class="so-block-title">MTD Clients</div>
			<div class="so-block">
				<div class="so-block-count">...</div>
			</div>
		</div>
	</div> -->
	<br />

	<?php
	if($grab_sales_goal->rowCount() || $grab_margin_goal->rowCount() || $grab_clientcount_goal->rowCount() || $grab_newclientcount_goal->rowCount() || $grab_newclientsales_goal->rowCount()) {
		?>
		<h3><i class="fa fa-plus action action-toggle"></i>Personal Goals</h3>
		<div class="goal-blocks">
			<?php
			if($grab_sales_goal->rowCount()) {
				$goal = $grab_sales_goal->fetch();
				?>
				<div class="goal-block-container">
					<div class="title"><?php print htmlentities($goal['title']);?></div>
					<div class="goal-block">
						<div class="percentage">00%</div>
						<div class="current">000000</div>
						<div class="of">of</div>
						<div class="goal"><?php print number_format($goal['goal'], 0);?></div>
					</div>
				</div>
				<?php
			}
			if($grab_margin_goal->rowCount()) {
				$goal = $grab_margin_goal->fetch();
				?>
				<div class="goal-block-container">
					<div class="title"><?php print htmlentities($goal['title']);?></div>
					<div class="goal-block">
						<div class="percentage">%</div>
						<div class="current"></div>
						<div class="of">of</div>
						<div class="goal"><?php print number_format($goal['goal'], 0);?></div>
					</div>
				</div>
				<?php
			}
			if($grab_clientcount_goal->rowCount()) {
				$goal = $grab_clientcount_goal->fetch();
				?>
				<div class="goal-block-container">
					<div class="title"><?php print htmlentities($goal['title']);?></div>
					<div class="goal-block">
						<div class="percentage">%</div>
						<div class="current"></div>
						<div class="of">of</div>
						<div class="goal"><?php print number_format($goal['goal'], 0);?></div>
					</div>
				</div>
				<?php
			}
			if($grab_newclientcount_goal->rowCount()) {
				$goal = $grab_newclientcount_goal->fetch();
				?>
				<div class="goal-block-container">
					<div class="title"><?php print htmlentities($goal['title']);?></div>
					<div class="goal-block">
						<div class="percentage">%</div>
						<div class="current"></div>
						<div class="of">of</div>
						<div class="goal"><?php print number_format($goal['goal'], 0);?></div>
					</div>
				</div>
				<?php
			}
			if($grab_newclientsales_goal->rowCount()) {
				$goal = $grab_newclientsales_goal->fetch();
				?>
				<div class="goal-block-container">
					<div class="title"><?php print htmlentities($goal['title']);?></div>
					<div class="goal-block">
						<div class="percentage">%</div>
						<div class="current"></div>
						<div class="of">of</div>
						<div class="goal"><?php print number_format($goal['goal'], 0);?></div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Opportunities By Individual
		<span class="datetime" id="personalopportunities-datetime"></span>
	</h3>
	<div id="personalopportunities-container" class="sales-container">
		<table id="personalopportunities-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Today</th>
					<th class="right">Week Of The <?php print date('jS', strtotime('Last Sunday'));?></th>
					<th class="right"><?php print date('F', time());?></th>
					<th class="right"><?php print date('Y', time());?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Sales By Individual
		<span class="datetime" id="personalsales-datetime"></span>
	</h3>
	<div id="personalsales-container" class="sales-container">
		<table id="personalsales-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Today</th>
					<th class="right">Week Of The <?php print date('jS', strtotime('Last Sunday'));?></th>
					<th class="right"><?php print date('F', time());?></th>
					<th class="right"><?php print date('Y', time());?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Sales By Territory
		<span class="datetime" id="salesbyterritory-datetime"></span>
	</h3>

	<div id="salesbyterritory-container" class="sales-container">
		<table id="salesbyterritory-table">
			<thead>
				<tr>
					<th>Territory</th>
					<th class="right">Today</th>
					<th class="right">Week Of The <?php print date('jS', strtotime('Last Sunday'));?></th>
					<th class="right"><?php print date('F', time());?></th>
					<th class="right"><?php print date('Y', time());?></th>
				</tr>
			</thead>
			<tbody></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Billing By Individual
		<span class="datetime" id="billbyindividual-datetime"></span>
	</h3>
	<div id="billbyindividual-container" class="sales-container" current-month="<?php print $current_month;?>" last-month="<?php print $last_month;?>">
		<table id="billbyindividual-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Prior Month</th>
					<th class="right">Month to Date</th>
					<th class="right">Year To Date</th>
					<th class="right">Prev. Year To Date</th>
					<th class="right">% Change</th>
					<th class="right">$ Change</th>
				</tr>
			</thead>
			<tbody></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Billing By Territory
		<span class="datetime" id="billingbyterritory-datetime"></span>
	</h3>
	<div id="billbyterritory-container" class="sales-container" current-month="<?php print $current_month;?>" last-month="<?php print $last_month;?>">
		<table id="billbyterritory-table">
			<thead>
				<tr>
					<th>Territory</th>
					<th class="right">Prior Month</th>
					<th class="right">Month to Date</th>
					<th class="right">Year To Date</th>
					<th class="right">Prev. Year To Date</th>
					<th class="right">% Change</th>
					<th class="right">$ Change</th>
				</tr>
			</thead>
			<tbody></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Client Count By Individual
		<span class="datetime" id="clientcountbyindividual-datetime"></span>
	</h3>
	<div id="clientcountbyindividual-container" class="sales-container">
		<table id="clientcountbyindividual-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Month to Date</th>
					<th class="right">Year To Date</th>
					<th class="right">Prev. Year To Date</th>
					<th class="right">% Change</th>
					<th class="right"># Change</th>
				</tr>
			</thead>
			<tbody></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Client Count By Territory
		<span class="datetime" id="clientcountbyterritory-datetime"></span>
	</h3>
	<div id="clientcountbyterritory-container" class="sales-container">
		<table id="clientcountbyterritory-table">
			<thead>
				<tr>
					<th>Territory</th>
					<th class="right">Month to Date</th>
					<th class="right">Year To Date</th>
					<th class="right">Prev. Year To Date</th>
					<th class="right">% Change</th>
					<th class="right"># Change</th>
				</tr>
			</thead>
			<tbody></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		New Clients By Individual
		<span class="datetime" id="newclientsbyindividual-datetime"></span>
	</h3>
	<div id="newclientsbyindividual-container" class="sales-container">
		<table id="newclientsbyindividual-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Month To Date</th>
					<th class="right">Year To Date</th>
					<th class="right">Open Orders</th>
					<th class="right">YTD Billing</th>
				</tr>
			</thead>
			<tbody><tr><td colspan="6">IN PROGRESS</td></tr></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Back Log by Individual
		<span class="datetime" id="backlogbyindividual-datetime"></span>
	</h3>
	<div id="backlogbyindividual-container" class="sales-container">
		<table id="backlogbyindividual-table">
			<thead>
				<tr>
					<th>Sales Person</th>
					<th class="right">Prior To Today</th>
					<th class="right">Today And Next 6 Days</th>
					<th class="right">Rest Of Current Month</th>
					<th class="right">Next Month</th>
					<th class="right">Total</th>
				</tr>
			</thead>
			<tbody><tr><td colspan="6">IN PROGRESS</td></tr></tbody>
			<tfoot></tfoot>
		</table>
	</div>

	<h3><i class="fa fa-plus action action-toggle"></i>
		Back Log by Territory
		<span class="datetime" id="backlogbyterritory-datetime"></span>
	</h3>
	<div id="backlogbyterritory-container" class="sales-container">
		<table id="backlogbyterritory-table">
			<thead>
				<tr>
					<th>Territory</th>
					<th class="right">Prior To Today</th>
					<th class="right">Today And Next 6 Days</th>
					<th class="right">Rest Of Current Month</th>
					<th class="right">Next Month</th>
					<th class="right">Total</th>
				</tr>
			</thead>
			<tbody><tr><td colspan="6">IN PROGRESS</td></tr></tbody>
			<tfoot></tfoot>
		</table>
	</div>
</div>
<br />
<br />

<script type="text/javascript">
	$(document).off('click', '#dashboard h3');
	$(document).on('click', '#dashboard h3', function(event) {
		var $h3 = $(this);
		var $icon = $h3.find('.fa');
		var $sales_container = $h3.next('.sales-container');
		if($sales_container.is(':visible')) {
			$sales_container.slideUp('fast');
			$icon.removeClass('fa-minus').addClass('fa-plus');
		} else {
			$sales_container.slideDown('fast');
			$icon.removeClass('fa-plus').addClass('fa-minus');
		}
	});
</script>

<?php Template::Render('footer', 'account');
