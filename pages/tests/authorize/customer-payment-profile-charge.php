<?php


define('AUTHORIZE_LOGINID', '6Xk88Qu5h'); // SANDBOX
define('AUTHORIZE_KEY', '7979cw3QwjT5LLfm'); // SANDBOX
// define('AUTHORIZE_LOGINID', '8sY9FtB49W2Z'); // LIVE
// define('AUTHORIZE_KEY', '6x285d5q82De2ZPx'); // LIVE

$authorize = new Authorize(
	'sandbox', // Endpoint. Either `production` or `sandbox`.
	AUTHORIZE_LOGINID,
	AUTHORIZE_KEY
);
