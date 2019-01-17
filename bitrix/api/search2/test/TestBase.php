<?php
$_SERVER['DOCUMENT_ROOT'] ='/home/bitrix/www';
require_once('/home/bitrix/www/bitrix/modules/main/include/prolog_before.php');
define ('NOT_CHECK_PERMISSIONS', true);
define ('NO_AGENT_CHECK', true);
$GLOBALS['DBType'] = 'mysql';
$_SESSION['SESS_AUTH']['USER_ID'] = 9999;

use PHPUnit\Framework\TestCase;


class TestBase extends TestCase
{
    protected $USER;

    public function setUp()
    {
        GLOBAL $USER;
        $this->USER=$USER;
        $_GET["detailNum"]="EH-00131";
        $_GET["methods"]='PrimaryTable';
        $_GET["substLevel"]='OriginalOnly';
        CModule::IncludeModule("kod.emex");
        
    }
}

?>