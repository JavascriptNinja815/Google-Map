<?php

$grab_quote = $db->query("
	SELECT TOP 1
		quotes.quote,
		quotes.author
	FROM
		" . DB_SCHEMA_INTERNAL . ".quotes
	ORDER BY
		NEWID()
");
$quote = $grab_quote->fetch();

print json_encode([
	'success' => True,
	'quote' => $quote['quote'],
	'author' => $quote['author']
]);
