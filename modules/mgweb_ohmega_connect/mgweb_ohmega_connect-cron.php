<?php

/*

* This file called using a cron to do 'something'

*/

const OHMEGA_TAAL = 'CHQF6SB2';

ini_set('max_execution_time', '300'); //300 seconds = 5 minutes

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include(dirname(__FILE__).'/mgweb_ohmega_connect.php');

$module = new Mgweb_ohmega_connect();

$data = $module->getDataToSync();
foreach($data as $sql){
    $query = $module->buildOhmegaSelectQuery($sql);
    $type = $module->getOhmegaQueryType($sql);

    if(executeQuery($type, $query) === true){
        // UPDATE RECORD MIDDLEWARE OHMEGA
        updateMiddleware($sql["WBSMWNRINT"]);
    }
}
die('Done');


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

function updateMiddleware($p_sNrint){
    $a["WBSMWVERWERKT"] = 1;
    $db = db();
    $result = $db->executeS('UPDATE FRwebMIDDELWARE SET WBSMWVERWERKT = 1 WHERE WBSMWNRINT = "'.$p_sNrint.'"');
}

function updateCategory($query){
    // INIT Ohmega DB && SELECT RECORD
    $db = db();
    $data = $db->getRow($query);

    // CHECK FOR SUBCAT
    $subId = Configuration::get('PS_HOME_CATEGORY');
    if(!empty($data["BKHAG_BKHAGNRINT"])){
        // CHECK FOR EXISTING MAPPING
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect_mapping` WHERE `type` = "FRbkhARTIKELGROEP" AND ohmega_id = "'.$data["BKHAG_BKHAGNRINT"].'";';
        $mappingSub = Db::getInstance()->getRow($sql);
        if($mappingSub){
            $subId = $mappingSub["prestashop_id"];
        }
    }

    // CHECK FOR EXISTING MAPPING
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect_mapping` WHERE `type` = "FRbkhARTIKELGROEP" AND ohmega_id = "'.$data["BKHAGNRINT"].'";';
    $mapping = Db::getInstance()->getRow($sql);

    // NO MAPPING = NEW ID + CREATE MAPPING
    if(!$mapping){
        // CREATE CAT
        $category = new \PrestaShop\PrestaShop\Adapter\Entity\Category();
        $category->active = 1;
        $category->id_parent = $subId;
        $category->name = [];
        $category->link_rewrite = [];
        foreach (Language::getLanguages(false) as $lang){
            $category->name[$lang['id_lang']] = seo_friendly($data["BKHAGOMS"]);
            $category->link_rewrite[$lang['id_lang']] = Mgweb_ohmega_connect::slugify($data["BKHAGOMS"]);
        }
        $category->add();

        // CREATE MAPPING
        $id = $category->getFields()["id_category"];
        $mappingData = [
            "id_mgweb_ohmega_connect_mapping" => 0,
            "type" => "FRbkhARTIKELGROEP",
            "ohmega_id" => $data["BKHAGNRINT"],
            "prestashop_id" => $id
        ];
        Db::getInstance()->insert("mgweb_ohmega_connect_mapping", $mappingData);
    }

    // OK MAPPING = UPDATE ID
    else{
        // UPDATE CAT
        $category = new \PrestaShop\PrestaShop\Adapter\Entity\Category($mapping["prestashop_id"]);
        $category->active = $data["BKHAG_ONLINE"];
        $category->id_parent = $subId;
        $category->name = [];
        $category->link_rewrite = [];
        foreach (Language::getLanguages(false) as $lang){
            $category->name[$lang['id_lang']] = seo_friendly($data["BKHAGOMS"]);
            $category->link_rewrite[$lang['id_lang']] = Mgweb_ohmega_connect::slugify($data["BKHAGOMS"]);
        }
        $category->save();
    }

    return true;

}

function updateProduct($query){
    // INIT Ohmega DB && SELECT RECORD
    $db = db();
    $data = $db->getRow($query);

    // FIND MAPPING CAT
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect_mapping` WHERE `type` = "FRbkhARTIKELGROEP" AND ohmega_id = "'.$data["BKHAR_BKHAGNRINT"].'";';
    $mappingCat = Db::getInstance()->getRow($sql);
    if(!$mappingCat){
        return false;
    }
    $categoryId = $mappingCat["prestashop_id"];

    // CHECK FOR EXISTING MAPPING
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect_mapping` WHERE `type` = "FRbkhARTIKEL" AND ohmega_id = "'.$data["BKHARNRINT"].'";';
    $mapping = Db::getInstance()->getRow($sql);

    // NO MAPPING = NEW ID + CREATE MAPPING
    if(!$mapping){
        // GET FRordARTIKEL WEBSHOP
        $bkhArtikelTaal = $db->getRow('SELECT * FROM FRbkhARTIKELTALEN WHERE BKHAT_BKHARNRINT = "'.$data["BKHARNRINT"].'" AND BKHAT_BKHTANRINT = "'.OHMEGA_TAAL.'"');

        // CREATE CAT
        $product = new \PrestaShop\PrestaShop\Adapter\Entity\Product();
//        $product->active = 0;
        $product->active = 1;
        $product->id_category_default = $categoryId;
        $product->reference = $data["BKHARCODE"];
        $product->description_short = [];
        $product->description = [];
        $product->quantity = $data["BKHARAANTAL"];
        $product->name = [];
        $product->link_rewrite = [];
        foreach (Language::getLanguages(false) as $lang){
            $product->name[$lang['id_lang']] = seo_friendly($data["BKHAROMS"]);
            $product->link_rewrite[$lang['id_lang']] = Mgweb_ohmega_connect::slugify($data["BKHAROMS"]);
            $product->description_short[$lang['id_lang']] = $bkhArtikelTaal["BKHATOMS"];
            $product->description[$lang['id_lang']] = $bkhArtikelTaal["BKHATOMS_EXTRA"];
        }
        $product->price = $data["BKHARPRIJS"];
        $product->width = $data["BKHAR_BREEDTE"];
        $product->height = $data["BKHAR_HOOGTE"];
        $product->depth = $data["BKHAR_LENGTE"];
        $product->weight = $data["BKHARGEWICHT_GRAM"];
        $product->ean13 = $data["BKHAREANCODE"];

        $product->add();
        $product->addToCategories([$categoryId]);

        // CREATE MAPPING
        $id = $product->getFields()["id_product"];
        $mappingData = [
            "id_mgweb_ohmega_connect_mapping" => 0,
            "type" => "FRbkhARTIKEL",
            "ohmega_id" => $data["BKHARNRINT"],
            "prestashop_id" => $id
        ];
        Db::getInstance()->insert("mgweb_ohmega_connect_mapping", $mappingData);
        
    }

    // OK MAPPING = UPDATE ID
    else{
        // GET FRordARTIKEL WEBSHOP
        $bkhArtikelTaal = $db->getRow('SELECT * FROM FRbkhARTIKELTALEN WHERE BKHAT_BKHARNRINT = "'.$data["BKHARNRINT"].'" AND BKHAT_BKHTANRINT = "'.OHMEGA_TAAL.'"');

        // GET FRordARTIKEL WEBSHOP
        $ordWebshop = $db->getValue('SELECT ORDARWEBSHOP FROM FRordARTIKEL WHERE ORDARNRINT = "'.$data["BKHARNRINT"].'"');

        // UPDATE CAT
        $product = new \PrestaShop\PrestaShop\Adapter\Entity\Product($mapping["prestashop_id"]);
//        $product->active = $ordWebshop;
        $product->active = 1;
        $product->id_category_default = $categoryId;
        $product->reference = $data["BKHARCODE"];
        $product->quantity = $data["BKHARAANTAL"];
        $product->description_short = [];
        $product->description = [];
        $product->quantity = $data["BKHARAANTAL"];
        $product->name = [];
        $product->link_rewrite = [];
        foreach (Language::getLanguages(false) as $lang){
            $product->name[$lang['id_lang']] = seo_friendly($data["BKHAROMS"]);
            $product->link_rewrite[$lang['id_lang']] = Mgweb_ohmega_connect::slugify($data["BKHAROMS"]);
            $product->description_short[$lang['id_lang']] = $bkhArtikelTaal["BKHATOMS"];
            $product->description[$lang['id_lang']] = $bkhArtikelTaal["BKHATOMS_EXTRA"];
        }
        $product->price = $data["BKHARPRIJS"];
        $product->width = $data["BKHAR_BREEDTE"];
        $product->height = $data["BKHAR_HOOGTE"];
        $product->depth = $data["BKHAR_LENGTE"];
        $product->weight = $data["BKHARGEWICHT_GRAM"];
        $product->ean13 = $data["BKHAREANCODE"];
        $product->save();
    }

    return true;
}

function seo_friendly($string){
    $string = str_replace(array('[\', \']'), '', $string);
    $string = preg_replace('/\[.*\]/U', '', $string);
    $string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $string);
    $string = htmlentities($string, ENT_COMPAT, 'utf-8');
    $string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string );
    $string = preg_replace(array('/[^a-z0-9 ]/i', '/[-]+/') , '-', $string);
    dump($string);
    return substr(trim(htmlentities($string), '-'), 0, 50);
}

function executeQuery($type, $query){
    switch($type){
        case "category":
            return updateCategory($query);
        case "product":
            return updateProduct($query);

    }
}