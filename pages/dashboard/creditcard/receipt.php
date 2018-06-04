<?php

$grab_transaction = $db->query("
	SELECT
		authorize_transactions.action,
		authorize_transactions.amount,
		authorize_transactions.last4,
		authorize_transactions.added_on,
		authorize_transactions.auth_code,
		authorize_transactions.transaction_id,
		authorize_transactions.nameoncard,
		authorize_transactions.auth_code,
		authorize_transactions.memo,
		authorize_transactions.authorize_transaction_id,
		authorize_transactions.nameoncard,
		authorize_transactions.custno
	FROM
		" . DB_SCHEMA_ERP . ".authorize_transactions
	WHERE
		authorize_transactions.transaction_id = " . $db->quote($_REQUEST['transaction_id']) . "
");
$transaction = $grab_transaction->fetch();

?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			body {
				font-family:Helvetica,Arial,"Times New Roman";
			}
			.logo {
				margin:auto;
				width:288px;
			}
			.amount {
				font-size:1.1em;
				font-weight:bold;
			}
			.signature {
				font-weight:bold;
			}
			div {
				padding:12px;
			}
			h1 {
				text-align:center;
			}
		</style>
	</head>
	<body>
		<div class="logo"><img src="<?php print BASE_URI;?>/interface/images/cd-logo-receipts.jpg" /></div>

		<h1><?php
		if($transaction['action'] == 'void') {
			print 'Void Receipt';
		} else if($transaction['action'] == 'refund') {
			print 'Refund Receipt';
		} else if($transaction['action'] == 'charge') {
			print 'Payment Receipt';
		}
		?></h1>

		<div>
			Date of Transaction: <?php print date('m/d/Y', strtotime($transaction['added_on']));?>
		</div>
		<div>
			Authorization Code: <?php print htmlentities($transaction['auth_code']);?>
		</div>
		<div>
			Transaction ID: <?php print htmlentities($transaction['authorize_transaction_id']);?>
		</div>
		<?php
		$transaction['custno'] = trim($transaction['custno']);
		if(!empty($transaction['custno'])) {
			?>
			<div>
				Cust Code: <?php print htmlentities($transaction['custno']);?>
			</div>
			<?php
		}
		?>
		<div>
			Name on Card: <?php print htmlentities($transaction['nameoncard']);?>
		</div>
		<div>
			Payment Method: *<?php print htmlentities($transaction['last4']);?>
		</div>
		<div>
			Reference: <?php print htmlentities($transaction['memo']);?>
		</div>
		<div class="amount">
			Total Amount: $<?php print number_format($transaction['amount'], 2);?>
		</div>
		<div class="signature">
			Authorization Signature ______________________________________
		</div>
	</body>
</html>