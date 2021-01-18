<?php

/*

* This file called using a cron to do 'something'

*/

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include(dirname(__FILE__).'/mgweb_ohmega_connect.php');

$module = new Mgweb_ohmega_connect();

$data = $module->getDataToSync();
foreach($data as $sql){
    $query = $module->buildOhmegaSelectQuery($sql);
    $type = $module->getOhmegaQueryType($sql);

    executeQuery($type, $query);

    print_r($type);
}

mail('mike@mgweb.nl','Token ok', 'Token is ok');
die();


function getConfig(){
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect` WHERE id_mgweb_ohmega_connect = 1;';
    return Db::getInstance()->getRow($sql);
}

function db(){
    $config = getConfig();

    $externalDbError = (bool)Db::checkConnection($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']);
    if($externalDbError){
        return null;
    }
    return new DbMySQLi($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
}



function updateCategory($query){
    $db = db();
    $data = $db->executeS($query);
    print_r($data); die();

    $category = new Category;
    $category->id = 0;
    $category->active = 0;
    $category->id_parent = 15;
    $category->name = "category";
    $category->link_rewrite = "one-category";
//this will force ObjectModel to use your ID
    $_GET['forceIDs'] = true;
    $category->add();
}


function executeQuery($type, $query){
    switch($type){
        case "category":
            updateCategory($query);

    }
}