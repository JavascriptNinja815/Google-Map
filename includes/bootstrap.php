<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

// Ensure all errors are being displayed and reported.
ini_set('display_errors', True); // Now that we've implemented custom error handlers, no reason to show them.
error_reporting(E_ALL | E_STRICT);

// Determine what OS we're running.
$os = strtoupper(PHP_OS);
if(strpos($os, 'LINUX') !== False) {
	define('OS', 'Linux');
} else if(strpos($os, 'WIN') !== False) {
	define('OS', 'Windows');
} else {
	// Fallback to Linux.
	define('OS', 'Linux');
}

// Construct BASE_PATH, an absolute file system path.
if(OS === 'Linux') {
	$base_path = str_replace('/includes/bootstrap.php', '', __FILE__);
} else if(OS === 'Windows') {
	$base_path = str_replace('\includes\bootstrap.php', '', __FILE__);
}
define('BASE_PATH', $base_path);

/* DEPRECATED.
// Detect Base Path
$base_path = str_replace('\\', '/', __FILE__); // Windows compatibility
$base_path = str_replace('/includes/bootstrap.php', '', $base_path);
define('BASE_PATH', $base_path);
unset($base_path);
*/

$http = 'http://';

// Detect Base URL (includes root directory.)
$base_uri = substr($_SERVER['SCRIPT_NAME'], -1, 1) == '/' ? dirname($_SERVER['SCRIPT_NAME'] . '.') : dirname($_SERVER['SCRIPT_NAME']);
$base_uri = str_replace('\\', '/', $base_uri); // Windows compatibility
if($base_uri == '/') {
	$base_uri = '';
}
define('BASE_URI', $base_uri);

if(!empty($_SERVER['SERVER_NAME'])) {
	$port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
	define('BASE_URL', $http . $_SERVER['SERVER_NAME'] . $port . $base_uri);
} else {
	define('BASE_URL', '');
}
unset($base_uri);

/************************** AUTOLOADER *************************/

// Register our class autoloading function.
spl_autoload_register(function($class) {
	$class = str_replace('\\', '/', $class);
	$class_namespaced = explode('/', $class);
	array_pop($class_namespaced); // Remove last element from the array.
	$class_namespaced = implode('/', $class_namespaced);
	$possible_filenames = [
		// Autoload class w/ directory heirarchy.
		BASE_PATH . '/includes/classes/' . str_replace('_', '/', $class) . '.php',
		BASE_PATH . '/includes/classes/' . strtolower(str_replace('_', '/', $class)) . '.php',

		// Autoload class w/ flat heirarchy.
		BASE_PATH . '/includes/classes/' . str_replace('_', '.', $class) . '.php',
		BASE_PATH . '/includes/classes/' . strtolower(str_replace('_', '.', $class)) . '.php',

		// Autoload class from Namespace.
		BASE_PATH . '/includes/classes/' . strtolower(str_replace('\\', '/', $class_namespaced)) . '.php',
	];
	foreach($possible_filenames as $possible_filename) {
		if(OS === 'Windows') {
			$possible_filename = str_replace('/', '\\', $possible_filename);
		}
		if(file_exists($possible_filename)) {
			//print('AUTOLOAD:' . $possible_filename);
			return include $possible_filename;
		}
	}
});

include BASE_PATH . '/includes/config.php';

/* DEPRECATED CLASS AUTOLOADER.
function __autoload($class_name) {
	$class_name = strtolower(str_replace('_', '.', $class_name));
	$full_class_path = BASE_PATH . '/includes/classes/' . $class_name . '.php';
	if(file_exists($full_class_path)) {
		return include $full_class_path;
	} else {
		return False;
	}
}
include BASE_PATH . '/includes/config.php';
*/

/************************** DATE/TIME *************************/

date_default_timezone_set(TIMEZONE);

/************************** DATABASE *************************/

$GLOBALS['db'] = array();
if(DB_ENGINE == 'odbc') {
	$GLOBALS['db']['internal'] = new PDO(DB_ENGINE . ':' . DB_HOST, DB_USER, DB_PASS);
} else if(DB_ENGINE == 'sqlsrv') {
	$GLOBALS['db']['internal'] = new PDO(DB_ENGINE . ':Server=' . DB_HOST . ';', DB_USER, DB_PASS);
	$GLOBALS['db']['internal']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

/************************** HEADERS *************************/

// Remove PHP language-type hint from response Headers
header_remove('X-Powered-By');

/************************** ERROR HANDLING *************************/

// Define a custom function for handling uncaught exceptions.
function exceptionHandler($exception) {
	$exception_type = get_class($exception);
	$exception_linenumber = $exception->getLine();
	$exception_filename = $exception->getFile();
	$exception_message = $exception->getMessage();
	$exception_stacktrace = $exception->getTraceAsString();

	print '<h2>Exception Thrown</h2>';
	print '<b>' . htmlentities($exception_type) . '</b> on line <b>' . htmlentities($exception_linenumber) . '</b> in <u>' . htmlentities($exception_filename) . '</u>';
	print '<br />';
	print '<pre>' . htmlentities($exception_message) . '</pre>';
	print '<br />';
	print '<b>Stack Trace</b>';
	print '<pre>' . htmlentities($exception_stacktrace) . '</pre>';
}
set_exception_handler('exceptionHandler');

// Define a custom function for handling errors.
function errorHandler($error_type, $error_message, $error_filename, $error_linenumber) {
	$error_typename = '';
	if($error_type == E_ERROR) {
		$error_typename = 'FATAL ERROR';
	} else if($error_type == E_WARNING) {
		$error_typename = 'WARNING';
	} else if($error_type == E_PARSE) {
		$error_typename = 'PARSE ERROR';
	} else if($error_type == E_NOTICE) {
		$error_typename = 'NOTICE';
	} else if($error_type == E_CORE_ERROR) {
		$error_typename = 'CORE ERROR';
	} else if($error_type == E_CORE_WARNING) {
		$error_typename = 'CORE WARNING';
	} else if($error_type == E_COMPILE_ERROR) {
		$error_typename = 'COMPILE ERROR';
	} else if($error_type == E_USER_ERROR) {
		$error_typename = 'USER ERROR';
	} else if($error_type == E_USER_WARNING) {
		$error_typename = 'USER WARNING';
	} else if($error_type == E_USER_NOTICE) {
		$error_typename = 'USER NOTICE';
	} else if($error_type == E_STRICT) {
		$error_typename = 'STRICT NOTICE';
	} else if($error_type == E_RECOVERABLE_ERROR) {
		$error_typename = 'RECOVERABLE ERROR';
	} else {
		$error_typename = 'UNKNOWN ERROR (error code: ' . htmlentities($error_type) . ')';
	}

	print '<h2>Error Thrown</h2>';
	print '<b>' . $error_typename . '</b> on line <b>' . htmlentities($error_linenumber) . '</b> in <u>' . htmlentities($error_filename) . '</u>';
	print '<br />';
	print '<pre>' . htmlentities($error_message) . '</pre>';
	return True; // Prevent normal error handler from firing.
}
set_error_handler('errorHandler');
// Work-around enabling error handling for Fatal Errors.
function detectFatalError() {
    $last_error = error_get_last();
    if($last_error['type'] == E_ERROR) {
		// Pass the fatal error to our error handler.
		errorHandler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		return False;
	}
}
register_shutdown_function('detectFatalError');

// Instantiate the Session handling object.
global $session;
$session = new Session();

// Not logged in, retrieve company id from domain.
$db_conn = DB::get(); // Get connection to DB.

// Determine which company is being viewed. First check for session. When logged
// in, the session defines the company. Otherwise, use domain to determine.
if($session->logged_in) {
	// Logged in, retrieve company id from session.
	define('COMPANY', $session->login['company_id']);
	define('ACCOUNT_ID', COMPANY); // Cross compatibility with various modules.

	$grab_company = $db_conn->query("
		SELECT
			companies.company_id,
			companies.company
		FROM
			" . DB_SCHEMA_INTERNAL . ".companies
		WHERE
			companies.company_id = " . $db_conn->quote($session->login['company_id']) . "
	");
	$company = $grab_company->fetch();
	define('COMPANY_NAME', $company['company']);
	unset($company);

	// Based on the active company, determine which database to use for retrieving ERP data.
	define('DB_SCHEMA_ERP', $session->login['company_db']);
} else {
	// Query DB for the domain we're requesting.
	$grab_company = $db_conn->prepare("
		SELECT
			companies.company_id,
			companies.company,
			companies.dbname
		FROM
			" . DB_SCHEMA_INTERNAL . ".company_domains
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".companies
			ON
			companies.company_id = company_domains.company_id
		WHERE
			company_domains.domain = '" . $_SERVER['HTTP_HOST'] . "'
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$grab_company->execute();

	if($grab_company->rowCount()) {
		// Domain matches a record, retrieve corresponding company id.
		$company = $grab_company->fetch();
		define('COMPANY', $company['company_id']);
		define('COMPANY_NAME', $company['company']);
		// Based on the active company, determine which database to use for retrieving ERP data.
		define('DB_SCHEMA_ERP', $company['dbname']);
		unset($company);
	} else {
		// Domain didn't match any records, default to company id 1.
		define('COMPANY', 1);
		define('COMPANY_NAME', 'CasterDepot');
		define('DB_SCHEMA_ERP', 'PRO01.dbo');
	}
	unset($grab_company);

}

unset($db_conn);

