<?php

// THIS SCRIPT WILL DELETE ALL STORES CARDS FROM AUTHORIZE.NET. USE CAREFULLY!!!

// CODE COMMENTED OUT FOR SAFETY.

/*
$grab_cards = $db->query("
	SELECT
		authorize_payment_profiles.payment_profile_id,
		authorize_payment_profiles.authorize_payprofile_id,
		authorize_customer_profiles.authorize_custprofile_id,
		authorize_customer_profiles.custno
	FROM
		" . DB_SCHEMA_ERP . ".authorize_customer_profiles
	INNER JOIN
		" . DB_SCHEMA_ERP . ".authorize_payment_profiles
		ON
		authorize_payment_profiles.customer_profile_id = authorize_customer_profiles.customer_profile_id
	WHERE
		authorize_customer_profiles.live = " . (ISLIVE ? '1' : '0') . "
		AND
		authorize_payment_profiles.live = " . (ISLIVE ? '1' : '0') . "
");

foreach($grab_cards as $card) {
	?>
	<p>
		<div><b>Card ID <?php print htmlentities($card['payment_profile_id']);?></b></div>
		<ul>
			<div>Customer Profile: <b><?php print htmlentities($card['authorize_custprofile_id']);?></b></div>
			<div>Payment Profile: <b><?php print htmlentities($card['authorize_payprofile_id']);?></b></div>
		</ul>
		<?php
		$profile = new AuthorizePaymentProfile($card['custno'], $card['payment_profile_id']);

		try {
			$result = $profile->delete($card['authorize_payprofile_id']);
			print '<b>SUCCESS</b>';
		} catch(Exception $e) {
			print '<b>ERROR:</b><br />' . htmlentities($e->getMessage());
		}
		?>
	</p>
	<?php
}

?>
<br /><br /><br /><br /><br />
<textarea style="width:100%;height:200px;">
deleteCustomerPaymentProfile

<?xml version="1.0" encoding="utf-8"?>
<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>API_LOGIN_ID</name>
    <transactionKey>API_TRANSACTION_KEY</transactionKey>
  </merchantAuthentication>
  <customerProfileId>10000</customerProfileId>
  <customerPaymentProfileId>20000</customerPaymentProfileId>
</deleteCustomerPaymentProfileRequest>
</textarea>
 * 
 */