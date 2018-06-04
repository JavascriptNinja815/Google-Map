<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

if($session->logged_in) {
	?>
	<div class="nav-container left">
		<div class="nav">
			<a class="nav-link">Dashboard <i class="fa fa-fw fa-sort-asc"></i></a>

			<div class="nav-container">
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard"><i class="fa fa-fw fa-user"></i> My Dashboard</a>
				</div>
				<?php
				if($session->hasRole('Sales')) {
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/sales"><i class="fa fa-fw fa-usd"></i> Sales Dashboard</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/ar"><i class="fa fa-fw fa-check"></i> AR Dashboard</a>
					</div>
					<?php
					if($session->hasRole('Accounting')) {
						?>
						<div class="nav">
							<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/ap"><i class="fa fa-fw fa-share"></i> AP Dashboard</a>
						</div>
						<?php
					}
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/vics"><i class="fa fa-fw fa-exclamation-triangle"></i> VIC Accounts</a>
					</div>
					<?php
				}
				?>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/feedback"><i class="fa fa-fw fa-thumb-tack"></i> Feedback</a>
				</div>
			</div>
		</div>

		<?php
		if($session->hasRole('Sales')) {
			?>
			<div class="nav">
				<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/clients">Clients</a>
			</div>
			<?php
		}
		?>

		<div class="nav">
			<a class="nav-link">Orders <i class="fa fa-fw fa-sort-asc"></i></a>

			<div class="nav-container">
				<?php
				if($session->hasRole('Administration') || $session->hasRole('Supervisor') || $session->hasRole('Sales')) {
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/creditcard"><i class="fa fa-fw fa-credit-card"></i> Credit Card</a>
					</div>
					<?php
				}
				if($session->hasRole('Sales')) {
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/sales-order-status"><i class="fa fa-fw fa-clock-o"></i> Sales Orders</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/pending-invoice"><i class="fa fa-fw fa-hourglass-half"></i> Pending Invoice</a>
					</div>
					<?php
				}
				?>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/late-orders"><img src="<?php print BASE_URI;?>/interface/images/skullandbones.png" style="width:18px;height:18px;" /> Past Due</a>
				</div>
				<?php
				if($session->hasRole('Sales')) {
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/opportunities"><i class="fa fa-fw fa-lightbulb-o"></i> Opportunities</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/sales-orders/not-printed"><i class="fa fa-fw fa-print"></i> Not Printed</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/sales-orders/hot"><i class="fa fa-fw fa-fire" style="padding:1px;width:16px;height:16px;font-size:18px;"></i> Hot</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/sales-orders/web"><i class="fa fa-fw fa-globe"></i> Web</a>
					</div>
					<?php
				}
				?>
			</div>
		</div>

		<?php
		if($session->hasRole('Sales')) {
			?>
			<div class="nav">
				<a class="nav-link">POs <i class="fa fa-fw fa-sort-asc"></i></a>

				<div class="nav-container">
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/purchaseorders"><i class="fa fa-fw fa-clock-o"></i> Purchase Orders</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/purchaseorders/rejections"><i class="fa fa-fw fa-reply"></i> Rejections</a>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<div class="nav">
			<a class="nav-link">Warehouse <i class="fa fa-fw fa-sort-asc"></i></a>

			<div class="nav-container">
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/inventory"><i class="fa fa-fw fa-list-ol"></i> Inventory</a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/inventory-v2"><i class="fa fa-fw fa-list-ol"></i> Inventory V2</a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/inventory/age"><i class="fa fa-fw fa-calendar"></i> Inventory Age<a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/inventory/reorder-points"><i class="fa fa-fw fa-area-chart"></i> Re-Order Points<a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/inventory/reorder-points-v2"><i class="fa fa-fw fa-area-chart"></i> Re-Order Points V2<a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/inventory/sales"><i class="fa fa-fw fa-usd"></i> Item By Sales<a>
				</div>
				<?php
				if($session->hasRole('Sales')) {
					?>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/labels"><i class="fa fa-fw fa-pencil-square-o"></i> Labels</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/shipping"><i class="fa fa-fw fa-truck"></i> Shipping</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/tracking"><i class="fa fa-fw fa-globe"></i> Tracking</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/amazon"><i class="fa fa-fw fa-amazon"></i> Amazon Labels</a>
					</div>
					<?php
				}
				?>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/open-wo-report"><i class="fa fa-fw fa-file"></i> WO Completion</a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/warehouse/large-orders"><i class="fa fa-fw fa-list-alt"></i> Large Orders</a>
				</div>
			</div>
		</div>

		<?php
		if($session->hasRole('Sales')) {
			?>
			<div class="nav">
				<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/reports">Reports</a>
			</div>
			<?php
		}
		?>
	</div>

	<div class="nav-container right">
		<?php
		if($session->hasRole('Administration')) {
			?>
			<div class="nav">
				<a class="nav-link">Admin <i class="fa fa-fw fa-sort-asc"></i></a>

				<div class="nav-container">
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/administration/logins"><i class="fa fa-fw fa-user"></i> Logins</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/administration/roles"><i class="fa fa-fw fa-users"></i> Roles</a>
					</div>
					<div class="nav">
						<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/administration/feedback"><i class="fa fa-fw fa-thumbs-o-up"></i> Feedback</a>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<div class="nav">
			<a class="nav-link">My Account <i class="fa fa-fw fa-sort-asc"></i></a>

			<div class="nav-container">
				<?php
				$db_conn = DB::get();
				$grab_companies = $db_conn->prepare("
					SELECT
						companies.company_id,
						companies.company
					FROM
						" . DB_SCHEMA_INTERNAL . ".companies
					INNER JOIN
						" . DB_SCHEMA_INTERNAL . ".login_companies
						ON
						companies.company_id = login_companies.company_id
					WHERE
						login_companies.login_id = '" . $session->login['login_id'] . "'
					ORDER BY
						companies.company
				", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
				$grab_companies->execute();
				if($grab_companies->rowCount() > 1) {
					foreach($grab_companies as $company) {
						?>
						<div class="nav">
							<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/company?company=<?php print $company['company_id'];?>"><i class="fa fa-building-o"></i> <?php print htmlentities($company['company']);?></a>
						</div>
						<?php
					}
					?>
					<div style="height:4px;background-color:#000;"></div>
					<?php
				}
				?>
				<div class="nav" style="border-top:1px solid #ccc;">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/terminology"><i class="fa fa-fw fa-book"></i> Terminology</a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/rocks"><i class="fa fa-fw fa-hand-rock-o"></i> Rocks</a>
				</div>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/tasks"><i class="fa fa-fw fa-check-square-o"></i> Tasks</a>
				</div>

				<div style="height:4px;background-color:#000;"></div>

				<div class="nav" style="border-top:1px solid #ccc;">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/profile/profile-directory"><i class="fa fa-fw fa-users"></i> Profile Directory</a>
				</div>
				<div class="nav" style="border-top:1px solid #ccc;">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/profile"><i class="fa fa-fw fa-user"></i> My Profile</a>
				</div>

				<div style="height:4px;background-color:#000;"></div>
				<div class="nav" style="border-top:1px solid #ccc;">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/timeoff"><i class="fa fa-fw fa-clock-o"></i> Time Off</a>
				</div>
				<div class="nav" style="border-top:1px solid #ccc;">
					<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account"><i class="fa fa-fw fa-wrench"></i> Preferences</a>
				</div>
				<?php
					if($session->hasRole("Supervisor")){
						?>
						<div class="nav">
							<a class="nav-link" href="<?php print BASE_URI;?>/dashboard/account/switch-user"><i class="fa fa-fw fa-exchange"></i> Switch User</a>
						</div>
						<?php
					}
				?>
				<div class="nav">
					<a class="nav-link" href="<?php print BASE_URI;?>/logout"><i class="fa fa-fw fa-sign-out"></i> Log Out</a>
				</div>
			</div>
		</div>
	</div>
	<?php
}
