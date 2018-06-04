<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

Template::Render('header', '403 - Forbidden', 'public');?>

<h1>403</h1>

<h2>Access Denied</h2>

You have been denied access to this page, probably because you lack the necessary credentials.

<?php Template::Render('footer', False, 'public');
