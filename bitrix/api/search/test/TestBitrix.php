<?php

require_once 'TestBase.php';

use TestBase;

class TestPost extends TestBase
{

    public function teastAnalogiFilter()
    {
        $bitrixController = new CBitrixController(150, 5, "c030");
        $analogi = $bitrixController->getAnalogi();
        $this->assertNotEmpty($analogi);
    }

    public function testAnalogiSearch()
    {
        $bitrixController = new CBitrixController(150, 5, "op595");
        $analogi = $bitrixController->getAnalogiSearch();
        $this->assertNotEmpty($analogi);
    }

    public function testGetDataPost()
    {
        $bitrixController = new CBitrixController(150, 5, "c030");
        $data = json_decode($bitrixController->getData(true));dd($data);
        $this->assertTrue($data->show);
    }

    public function testGetDataNoPost()
    {

        $bitrixController = new CBitrixController(150, 5, "op595");
        $data = json_decode($bitrixController->getData(false));
        $this->assertTrue($data->show);

    }

    public function testArtNull()
    {

        $bitrixController = new CBitrixController(150, 5);
        $data = json_decode($bitrixController->getData(false));
        $this->assertFalse($data->show);
    }

    public function testArtUnknown()
    {

        $bitrixController = new CBitrixController(150, 5, "0000000000000000000");
        $data = json_decode($bitrixController->getData());
        $this->assertFalse($data->show);
    }

    public function teastIBlock()
    {
        $arSelect = Array("ID", "XML_ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_CML2_ARTICLE", "PROPERTY_PROIZVODITEL2",
            "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_SROK_POSTAVKI");
        $arFilter = Array("IBLOCK_ID" => 150, "PROPERTY_CML2_ARTICLE" => "c030", "ACTIVE" => "Y");
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while ($arBrand = $res->Fetch()) {
            print_r($arBrand);
        }
        die();
        //$this->assertTrue(true);
    }


}