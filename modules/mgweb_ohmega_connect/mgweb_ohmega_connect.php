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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mgweb_ohmega_connect extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'mgweb_ohmega_connect';
        $this->tab = 'quick_bulk_update';
        $this->version = '1.0.0';
        $this->author = 'MgWeb.nl';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('OhmegaConnect™');
        $this->description = $this->l('Connect Prestashop to Ohmega Toolbox to sync all your data');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? All data will be deleted!');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('MGWEB_OHMEGA_CONNECT_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install();
    }

    public function uninstall()
    {
        Configuration::deleteByName('MGWEB_OHMEGA_CONNECT_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $host = strval(Tools::getValue('db_host'));
            $user = strval(Tools::getValue('db_user'));
            $pass = strval(Tools::getValue('db_pass'));
            $name = strval(Tools::getValue('db_name'));

            if (
                !$host || empty($host) ||
                !$user || empty($user) ||
                !$name || empty($name)
            ) {
                $output .= $this->displayError($this->l('Invalid value(s)'));
            } else {
                // GET CURRENT VALUES
                $currVal = $this->checkConfig();

                // SET VALUES TO UPDATE
                $array = [
                    'db_host' => $host,
                    'db_user' => $user,
                    'db_name' => $name,
                    'db_pass' => $currVal['db_pass']
                ];

                // CHECK PASSWORD
                if($pass !== ""){
                    $array["db_pass"] = $pass;
                }

                // CHECK IF CREDENTIALS ARE CORRECT && DATATABLE IS AVAILABLE
                $externalDbError = (bool)Db::checkConnection($array['db_host'],$array['db_user'],$array['db_pass'],$array['db_name']);

                if(!$externalDbError){
                    // SET CONFIG
                    $result = Db::getInstance()->update('mgweb_ohmega_connect', array(
                        'db_host' => ($host),
                        'db_user' => ($user),
                        'db_name' => ($name),
//                    'db_pass' => ($pass),
                    ), 'id_mgweb_ohmega_connect = 1');

                    if(!$result){
                        $output .= $this->displayError($this->l('Something went wrong during saving'));
                    }else{
                        $output .= $this->displayConfirmation($this->l('Settings updated'));
                    }
                }

                else{
                    $output .= $this->displayError($this->l('Cannot connect to external database'));
                }

            }
        }

        // Add FORM
        $output .= $this->displayForm();

        // EXEC TEST-QUERY

        $output .= $this->connectToOhmega();

        return $output;
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Get current data from DB
        $data = $this->checkConfig();
        if($data === false){
            return $this->displayError($this->l('Configuration record not found. Please re-install the module'));
        }


        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Database settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Database host'),
                    'name' => 'db_host',
                    'size' => 255,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Database name'),
                    'name' => 'db_name',
                    'size' => 255,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Database user'),
                    'name' => 'db_user',
                    'size' => 255,
                    'required' => true
                ],
                [
                    'type' => 'password',
                    'label' => $this->l('Database password'),
                    'name' => 'db_pass',
                    'hint' => $this->l('Keep empty to leave unchanged'),
                    'size' => 255,
                    'required' => false
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['db_host'] = $data["db_host"];
        $helper->fields_value['db_name'] = $data["db_name"];
        $helper->fields_value['db_user'] = $data["db_user"];

        return $helper->generateForm($fieldsForm);
    }


    protected function checkConfig(){
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mgweb_ohmega_connect` WHERE id_mgweb_ohmega_connect = 1;';
        $data = Db::getInstance()->getRow($sql);
        return $data;
    }

    protected function connectToOhmega(){
        $config = $this->checkConfig();
        $dsn = 'mysql://'.$config['db_user'].':'.$config['db_pass'].'@'.$config['db_host'].':3306/'.$config['db_name'].'';
        $db = new DbMySQLi($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        $data = $db->executeS("SELECT * FROM FRwebMIDDELWARE WHERE WBSMWVERWERKT <> 1 AND WBSMWNRINT <> ''");

        $this->fields_list = array(
            'WBSMWFILE' => array(
                'title' => $this->l('Id'),
                'width' => 140,
                'type' => 'text'
            ),
            'WBSMWKEY' => array(
                'title' => $this->l('Size'),
                'width' => 140,
                'type' => 'text'
            ),
            'WBSMWVALUE' => array(
                'title' => $this->l('Description'),
                'width' => 'auto',
                'type' => 'text'
            )
        );

        $helper= new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->listTotal = count($data);
        $helper->actions = array('edit', 'delete', 'view','test');
        $helper->module = $this;
        $helper->identifier = 'bcard_id';
        $helper->show_toolbar = false;
        $helper->title = 'List of printable models';
        $helper->table = 'bcard';
        $helper->token = Tools::getAdminTokenLite('AdminBcardprint');
        $helper->currentIndex = AdminController::$currentIndex;
        return $helper->generateList($data,$this->fields_list);
    }

    protected function buildOhmegaSelectQuery($data){
        $table  = $data['WBSMWFILE'];
        $key    = $data['WBSMWKEY'];
        $value  = $data["WBSMWVALUE"];
        return "SELECT * FROM ".$table." WHERE ".$key." = '".$value."';";
    }

    protected function getOhmegaQueryType($data){
        $table  = $data['WBSMWFILE'];
        switch ($table){
            case "FRbkhARTIKELGROEP":
                return "category";
            case "FRbkhARTIKEL":
            case "FRordARTIKEL":
                return "product";
            case "FRbkhPRIJSGROEP":
                return "prices";
            default:
                return false;
        }
    }
}
