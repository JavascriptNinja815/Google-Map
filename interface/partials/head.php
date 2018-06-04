<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$request_timestamp = date('Y-m-d-G', time());

?>
<head>
	<title><?php print htmlentities($title);?></title>
	<link rel="shortcut icon" type="image/x-icon" href="<?php print BASE_URI;?>/favicon.ico" />
	<!--
	@author Joshua D. Burns <jdburnz@gmail.com>, +1(616)481-1585, <https://www.linkedin.com/in/joshuadburns>
	@copyright Copyright (c) Joshua D. Burns, 2014-2018. All Rights Reserved.
	-->
	<meta name="author" content="Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>" />
	<meta name="copyright" content="Copyright (c) Joshua D. Burns, 2014-2018. All Rights Reserved." />

	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta http-equiv="Content-Language" content="en-us" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

	<script type="text/javascript">
		var BASE_URI = '<?php print BASE_URI;?>';
		var STATIC_PATH = '<?php print STATIC_PATH;?>';

		// TODO: Move these into their own js file.
		/**
		 * Grab arguments passed to page, making them accessible to Javascript
		 * via the `params` variable.
		 */
		function getSearchParameters() {
			  var prmstr = window.location.search.substr(1);
			  return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
		}
		function transformToAssocArray( prmstr ) {
			var params = {};
			var prmarr = prmstr.split("&");
			for ( var i = 0; i < prmarr.length; i++) {
				var tmparr = prmarr[i].split("=");
				params[tmparr[0]] = tmparr[1];
			}
			return params;
		}
		var params = getSearchParameters();
	</script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-1.11.0.min.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-ui-1.10.4.min.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-dragtable.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery.overlayz.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-datetimepicker.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-lazyForm.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-tablesorter.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-tablesorter-widgets.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-tablesorter-widgets-filter-formatter.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-tablesorter-widgets-filter-formatter-select2.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-disableselection.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-autocomplete.min.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-trumbowyg.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/jquery-autofit.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/site.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/site.overlayz.js?t=<?php print $request_timestamp;?>"></script>
	<!--script src="//cdnjs.cloudflare.com/ajax/libs/d3/3.4.4/d3.min.js?t=<?php print $request_timestamp;?>"></script-->
	<!--script type="text/javascript" src="<?php print STATIC_PATH;?>/js/d3pie.min.js?t=<?php print $request_timestamp;?>"></script-->
	<script type="text/javascript" src="<?php print STATIC_PATH;?>/js/progressbar.js?t=<?php print $request_timestamp;?>"></script>
	<script type="text/javascript">
		$.trumbowyg.svgPath = BASE_URI + '/interface/images/jquery-trumbowyg-icons.svg';
	</script>

	<!-- ***** STYLESHEETS ***** -->
	<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Titillium+Web:400,700" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery.overlayz.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-ui/jquery-ui-1.10.4.min.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-datetimepicker.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-dragtable.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-autocomplete.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-trumbowyg.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/jquery-autofit.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/bootstrap.min.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/font-awesome.min.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.overlayz.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.general.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.tables.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.header.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.navigation.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.breadcrumbs.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.mobile.css?t=<?php print $request_timestamp;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/company.<?php print htmlentities($session->login['company_shortname'], ENT_QUOTES);?>.css?t=<?php print $request_timestamp;?>" />
	<!--[if IE]>
		<link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH;?>/css/site.ie.css" />
	<![endif]-->

	<?php
	/**
	 * Dynamically include page-specific CSS and Javascript
	 */
	$page = explode('?', $_SERVER['REQUEST_URI']);
	$page = $page[0];

	// Check if Javascript file exists and include if present.
	$js_path = '/js/pages' . $page . '.js';
	if(file_exists(BASE_PATH . '/interface' . $js_path)) {
		?><script type="text/javascript" src="<?php print STATIC_PATH . $js_path;?>?t=<?php print $request_timestamp;?>"></script><?php
	}

	// Check if CSS file exists and include if present.
	$css_path = '/css/pages' . $page . '.css';
	if(file_exists(BASE_PATH . '/interface' . $css_path)) {
		?><link rel="stylesheet" type="text/css" href="<?php print STATIC_PATH . $css_path;?>?t=<?php print $request_timestamp;?>" /><?php
	}
	?>
</head>
