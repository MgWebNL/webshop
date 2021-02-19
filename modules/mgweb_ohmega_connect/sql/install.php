<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mgweb_ohmega_connect` (
    `id_mgweb_ohmega_connect` int(11) NOT NULL AUTO_INCREMENT,
    `db_host` varchar(255) NOT NULL,
    `db_name` varchar(255) NOT NULL,
    `db_user` varchar(255) NOT NULL,
    `db_pass` varchar(255) NOT NULL,
    PRIMARY KEY  (`id_mgweb_ohmega_connect`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'mgweb_ohmega_connect` (`id_mgweb_ohmega_connect`) VALUES (1);';

$sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'mgweb_ohmega_connect_mapping`( 
	`id_mgweb_ohmega_connect_mapping` Int( 11 ) AUTO_INCREMENT NOT NULL,
	`type` VarChar( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`ohmega_id` VarChar( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`prestashop_id` Int( 11 ) NOT NULL,
	PRIMARY KEY ( `id_mgweb_ohmega_connect_mapping` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 1;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
