<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

global $breadcrumbs, $title;

$breadcrumbs = array();
$title = '';

class Template {
	public static function Render($part, $args = False, $style = 'public') {
		global $request, $session, $breadcrumbs, $title, $db;

		//$title = '';
		//$breadcrumbs = array();
		$body_class = '';

		if(is_array($args)) {
			if(!empty($args['title'])) { // Manage Title
				$title = $args['title'];
			}
			if(!empty($args['breadcrumbs'])) { // Manage Breadcrumbs
				$breadcrumbs = $args['breadcrumbs'];
			}
			if(!empty($args['body-class'])) {
				$body_class = $args['body-class'];
			}
		} else if($args) {
			$title = $args;
		}

		require(BASE_PATH . '/interface/templates/' . strtolower($style) . '/' . strtolower($part) . '.php');
	}

	public static function Partial($name) {
		global $request, $session, $breadcrumbs, $title, $db;
		// Kick the contents of the partial onto a new line. Prevents the
		// first line of the partial from being indented and offset from the
		// rest of the lines within the partial.
		print "\r\n";

		require(BASE_PATH . '/interface/partials/' . strtolower($name) . '.php');
	}
}
