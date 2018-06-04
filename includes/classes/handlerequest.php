<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */


Class HandleRequest {
	public $page;
	public $filename;

	public function __construct($page, $canonical) {
		// Set this request's page.
		$this->page = $page;
		$this->canonical = $canonical;

		// Set this requests's filename.
		//$filename = str_replace('-', '', $page);
		$filename = $page;
		if($filename[0] != '/') {
			$filename = '/' . $filename;
		}
		$this->filename = BASE_PATH . '/pages' . $filename . '.php';
	}

	public function Render() {
		global $session;

		// Ensure the page requested actually exists.
		if(file_exists($this->filename)) { // File exists
			$file = $this->filename; // Requires the appropriate page.
		} else { // File doesn't exist
			header('HTTP/1.1 404 Not Found');
			$file = BASE_PATH . '/pages/errors/404.php'; // Displays a 404 error page.
		}
		
		// Grab DB connection, putting it within the local scope of the template.
		$db = DB::get();

		// Retrieve the template for rendering.
		return require($file);
	}
}
