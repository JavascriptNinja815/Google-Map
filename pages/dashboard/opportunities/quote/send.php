<?php

// Create the generator which will be used for determining the quote's filename.
function generateAlphabeticalList($lower, $upper) {
    ++$upper;
    for($i = $lower; $i !== $upper; ++$i) {
        yield $i;
    }
}

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		logins.login_id AS salesman_loginid,
		logins.first_name AS salesman_firstname,
		logins.last_name AS salesman_lastname,
		logins.login AS salesman_email
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		opportunities.login_id = logins.login_id
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();
$opportunity_id = $opportunity['opportunity_id'];

$message = $_POST['message'];
if(empty($message)) {
	$message = 'Thank you for the opportunity - ' . COMPANY_NAME;
}

$cc = [];
if($opportunity['salesman_loginid'] != $session->login['login_id'] || !empty($_POST['ccself'])) {
	$cc[] = $opportunity['salesman_firstname'] . ' ' . $opportunity['salesman_lastname'] . ' <' . $opportunity['salesman_email'] . '>';
}
if(!empty($_POST['contacts'])) {
	foreach($_POST['contacts'] as $contact) {
		$cc[] = $contact;
	}
}
if(!empty($_POST['offices'])) {
	foreach($_POST['offices'] as $office) {
		$cc[] = $office;
	}
}

$bcc = [
	'emailtosalesforce@2kymidmehrsroti9ettyudffl.in.salesforce.com'
];

$attachments = [
	Quote::$base_dir . '\\' . $_POST['filename']
];
if(!empty($_POST['attachments']) && is_array($_POST['attachments'])) {
	foreach($_POST['attachments'] as $attachment_id) {
		$grab_attachment = $db->query("
			SELECT
				opportunity_attachments.full_path
			FROM
				" . DB_SCHEMA_ERP . ".opportunity_attachments
			WHERE
				opportunity_attachments.attachment_id = " . $db->quote($attachment_id) . "
		");
		$attachment = $grab_attachment->fetch();
		if($attachment) {
			$attachments[] = $attachment['full_path'];
		}
	}
}

$quote = new Quote($opportunity_id);
$send_result = $quote->send(
	'CasterDepot Quote #' . $opportunity_id, // subject
	$message,
	$session->login['first_name'] . ' ' . $session->login['last_name'] . ' <' . $session->login['login'] . '>', // from
	[ $_POST['name'] . ' <' . $_POST['email'] . '>' ], // to
	$cc,
	$bcc,
	$attachments,
	$_POST['api']
);

if(!$send_result['success']) {
	print json_encode($send_result);
	exit;
}

print json_encode([
	'success' => True
]);
