<?php

class Logging {
	public static function Log($subject, $action, $subject_id = Null, $details = Null) {
		$db_conn = DB::get();

		$subject = $db_conn->quote($subject);
		$action = $db_conn->quote($action);

		if($subject_id === Null) {
			$subject = 'NULL';
		} else {
			$subject = $db_conn->quote($subject);
		}

		if($details === Null) {
			$details = 'NULL';
		} else {
			$details = $db_conn->quote($details);
		}

		$db_conn->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".logs
			(
				subject,
				action,
				subject_id,
				details
			) VALUES (
				" . $subject . ",
				" . $action . ",
				" . $subject_id . ",
				" . $details . "
			)
		");
	}

	public static function getLog($subject, $action, $subject_id = Null, $num_results = 100) {
		$db_conn = DB::get();

		$where_arr = array(
			'subject = ' . $db_conn->quote($subject),
			'action = ' . $db_conn->quote($action)
		);

		if($subject_id !== Null) {
			$where_arr[] = 'subject_id = ' . $db_conn->quote($subject_id);
		}

		$where_str = implode(' AND ', $where_arr);

		$grab_logs = $db_conn->query("
			SELECT
				logs.*
			FROM
				" . DB_SCHEMA_INTERNAL . ".logs
			WHERE
				" . $where_str . "
			ORDER BY
				logs.created_on
		");
		return $grab_logs->fetchAll();
	}
}

Logging::Log('', '', 0, '');