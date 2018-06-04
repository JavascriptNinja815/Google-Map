<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class Misc {

	public static function RandomString($length = 32, $constrain = False) {
		if($constrain == 'lowercase') {
			$chars = "abcdefghijklmnopqrstuvwxyz";
		} else if($constrain == 'uppercase') {
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		} else if($constrain == 'numeric') {
			$chars = "0123456789";
		} else {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		}

		$str = '';
		$size = strlen( $chars );
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}

		return $str;
	}

	public static function pgHstoreToPhp($hstore) {
		if($hstore == '') {
			return array();
		} 
		$hstore_rows = explode('", "', $hstore);
		$num_rows = count($hstore_rows);
		$curr_row = 0;
		$hstore_data = array();
		$first = True;
		foreach($hstore_rows as $hstore_row) {
			$curr_row++;
			$hstore_keyval = explode('"=>"', $hstore_row);
			
			$key = $hstore_keyval[0];
			$key = str_replace('\\"', '"', $key);
			if($first === True) { // Remove leading " from first entry
				$key = substr($key, 1);
				$first = false;
			}
			
			$value = $hstore_keyval[1];
			$value = str_replace('\\"', '"', $value);
			
			if($curr_row === $num_rows) { // Remove trailing " from last entry
				$value = substr($value, 0, -1);
			}

			$hstore_data[$key] = $value;
		}

		return $hstore_data;

		/*
		if(empty($hstore)) { // Take care of empty hstores and null values
			return array();
		}
		$hstore_escaped = str_replace("'", "''", $hstore);
		$hstore_escaped = str_replace('\\"', '\\\\"', $hstore);
		$query = "
			SELECT
				(
					EACH('" . $hstore_escaped . "'::hstore)
				).*
		";
		$grab_keys_values = pg_query($query);
		if(pg_num_rows($grab_keys_values) == 0) {
			throw New Exception('Malformed HStore: ' . $hstore);
			return False;
		}
		$return = array();
		while($entry = pg_fetch_assoc($grab_keys_values)) {
			$return[$entry['key']] = $entry['value'];
		}
		return $return;*/
	}

	/*
	public static function pgHstoreFromPhp($array) {
		$hstore = '';
		foreach($array as $key => $value) {
			$key = str_replace('"', '\\\\"', pg_escape_string($key));
			$value = str_replace('"', '\\\\"', pg_escape_string($value));

			if(!empty($hstore)) {
				$hstore .= ', ';
			}
			$hstore .= '"' . $key . '"=>"' . $value . '"';
		}
		return $hstore;
	}
	 */

	public static function pgHstoreFromPhp($data) {
		if (!is_array($data)) {
			throw new Exception(sprintf("HStore::toPg takes an associative array as parameter ('%s' given).", gettype($data)));
		}

		$insert_values = array();

		foreach($data as $key => $value) {
			$key = pg_escape_string(str_replace('"', '\"', $key));
			$value = pg_escape_string(str_replace('"', '\"', $value));
			//if(is_null($value) || strlen($value) == 0) {
			//    continue;
			//if (is_null($value)) {
			    //$insert_values[] = sprintf('"%s" => NULL', $key);
			//} else {
			    $insert_values[] = sprintf('"%s" => "%s"', $key, $value);
			//}
		}

		return sprintf("'%s'::hstore", join(', ', $insert_values));
	}

	public static function pgArrayToPhp($text, $datatype = 'string') { // $datatype may be "string" or "integer"
		if(is_null($text)) {
			return array();
		} else if(is_string($text) && $text != '{}') {
			$text = substr($text, 1, -1);// Removes starting "{" and ending "}"

			if(substr($text, 0, 1) == '"') {
				$text = substr($text, 1);
			}
			if(substr($text, -1, 1) == '"') {
				$text = substr($text, 0, -1);
			}

			if($datatype == 'string') {
				$values = explode('","', $text);
			} else if($datatype == 'integer') {
				$values = explode(',', $text);
			}

			$fixed_values = array();
			foreach($values as $value) {
				$value = str_replace('\\"', '"', $value);
				$fixed_values[] = $value;
			}
			return $fixed_values;
		} else {
			return array();
		}
	}

	public static function pgArrayFromPhp($array, $data_type = 'character varying') {
		$array = (array) $array;

		$result = array();
		foreach($array as $t) {
			if(is_array($t)) {
				$result[] = to_pg_array($t);
			} else {
				// probably dont need the following line since it's running through escape_string.
				$t = str_replace('"', '\\"', $t); // escape double quote
				$t = pg_escape_string($t);
				$result[] = '"' . $t . '"';
			}
		}

		return '\'{' . implode(',', $result) . '}\'::' . $data_type . '[]'; // format
	}

	public static function pgDateToPhp($date_string, $format = False) {
		$timestamp = self::pgDateToPhpTimestamp($date_string);
		if($format === False) {
			$format = 'n/j/Y';
		}
		return date($format, $timestamp);
	}

	public static function pgDateToPhpTimestamp($date_string) {
		return strtotime($date_string);
	}

	public static function pgDateFromPhpString($datetime) {
		return date('Y-m-d H:i O', strtotime($datetime));
	}

	public static function pgDateFromPhpEpoch($timestamp) {
		return date('Y-m-d H:i O', $timestamp);
	}
}
