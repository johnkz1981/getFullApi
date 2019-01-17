<?php

require_once 'TestBase.php';
use TestBase;
use Brainkit\D7\Test;
class EmexTest extends TestBase
{
    /*public function testMy(){
        CModule::IncludeModule("brainkit.d7");
        Test::get();
    }*/

    public function teastOne(){
        $_GET['art']="2115280401550";
        if($_GET['art']){
            $bitrixController = new CBitrixController(143,76);
            $bitrixController->getData();
        }
    }

    public function testOne(){
        $idSupplires=Tecdoc::getSupplierId('filtron');
        var_dump($idSupplires);
    }
}