<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class History {
	public static function Record($login_id, $action, $result, $details = Null) {

		if($result === True || $result === 1) {
			$result = '1';
		} else {
			$result = '0';
		}

		$db_conn = DB::get();
		$db_conn->exec("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".login_history
			(
				login_id,
				action,
				result,
				ip_address,
				details
			) VALUES (
				" . $db_conn->quote($login_id) . ",
				" . $db_conn->quote($action) . ",
				" . $db_conn->quote($result) . ",
				" . $db_conn->quote($_SERVER['REMOTE_ADDR']) . ",
				" . $db_conn->quote($details) . "
			)
		");

		return True;
	}
}