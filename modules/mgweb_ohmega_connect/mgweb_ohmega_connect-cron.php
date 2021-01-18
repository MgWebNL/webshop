<?php

/*

* This file called using a cron to do 'something'

*/

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include(dirname(__FILE__).'/mgweb_ohmega_connect.php');

$module = new Mgweb_ohmega_connect();

mail('mike@mgweb.nl','Token ok', 'Token is ok');
die();