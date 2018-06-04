<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.')) == 'dev.') {
	define('ISLIVE', False);
} else {
	define('ISLIVE', True);
}

// Used in a few modules where that module is cross-compatible with other implementations by Josh.
define('ERP_SYSTEM', 'PRO'); // Valid values include: PRO and Neuron

/**
 * SQL Server Authentication
 */
define('DB_ENGINE', 'sqlsrv'); //define('DB_ENGINE', 'odbc');
define('DB_HOST', '10.1.247.129'); //define('DB_HOST', 'PhpOdbcInterface');
define('DB_USER', 'sa');
define('DB_PASS', 'bear1ngs');

define('DB_SCHEMA_INTERNAL', 'Neuron.dbo');
define('DB_SCHEMA_PROSYS', 'PROSYS.dbo');
define('DB_SCHEMA_SHIPPING', 'PROUPS.dbo');
define('DB_FOXPRO_SOTRACKING', 'odbc:FoxProUps');

/**
 * SOF UPS (PROUPS) Fox Pro Database
 */
define('PROUPS_FILENAME', 'T:\PROGLC\EXTERNAL\ShipIntegration\sofups.dbf');

/**
 * Algorithm to use in hashing passwords
 */
define('HASHING_ALGO', 'sha512');

/**
 * This server's/the default time zone.
 */
define('TIMEZONE', 'America/Detroit');

/**
 * Authorize.net API Credentials
 */
if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.')) == 'dev.') {
	// DEV -- SANDBOX
	define('AUTHORIZE_ENDPOINT', 'sandbox');
	define('AUTHORIZE_LOGINID', '6Xk88Qu5h');
	define('AUTHORIZE_KEY', '7979cw3QwjT5LLfm');
	define('AUTHORIZE_URL', 'https://apitest.authorize.net/xml/v1/request.api');
} else {
	// LIVE -- PRODUCTION
	define('AUTHORIZE_ENDPOINT', 'production');
	define('AUTHORIZE_LOGINID', '8sY9FtB49W2Z');
	define('AUTHORIZE_KEY', '6x285d5q82De2ZPx');
	define('AUTHORIZE_URL', 'https://api2.authorize.net/xml/v1/request.api');
}

define('STATIC_PATH', BASE_URI . '/interface');
