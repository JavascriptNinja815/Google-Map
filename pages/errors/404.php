<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

Template::Render('header', '404 - Page Not Found', 'public');?>

<h1>404</h1>

<h2>Page Not Found</h2>

The page you are trying to access has been move, removed or does not exist.

<?php Template::Render('footer', False, 'public');
