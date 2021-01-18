<?php

/*

* This file called using a cron to do 'something'

*/

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

/* Check security token */

if (substr(Tools::encrypt('mgweb_ohmega_connect/cron'), 0, 10) != Tools::getValue('token') || !Module::isInstalled('mgweb_ohmega_connect')){
    mail('mike@mgweb.nl','Bad token', 'Cron job bad token issue');
    die();
}

//v1.8 START

if (!defined('_PS_MODE_DEMO_'))

define('_PS_MODE_DEMO_', false);

//END v1.8

include(dirname(__FILE__).'/mgweb_ohmega_connect.php');

$module = new Mgweb_ohmega_connect();

mail('mike@mgweb.nl','Token ok', 'Token is ok');
die();