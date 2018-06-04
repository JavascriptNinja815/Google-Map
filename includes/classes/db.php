<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class OldDBException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

// DEPRECATED, SHOULD BE CALLING SQL() instead.
class DB {
	public static function get($alias = 'internal') {
		if(!isset($GLOBALS['db'][$alias])) {
			throw new OldDBException('DB alias specified does not exist');
		}
		return $GLOBALS['db'][$alias];
	}
}
