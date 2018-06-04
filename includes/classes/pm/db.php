<?php

namespace PM\DB;

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2017, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class DBException extends \Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

// Forward compatibility, we should be using this class, NOT the DB class.
class SQL {
	public static function connection($alias = 'internal') {
		// TODO: Once everything has been migrated over to namespaces, we should
		// migrate the database connection logic into this object rather than
		// existing within bootstrap.php. Then we can create the connection on
		// the fly, as needed.

		if(!isset($GLOBALS['db'][$alias])) {
			throw new DBException('DB alias specified does not exist');
		}
		return $GLOBALS['db'][$alias];
	}
}
