<?php

require_once 'TestBase.php';
use TestBase;

class EmexTest extends TestBase
{
    //Категория0 = 11; Категория1 = 10; иначе розница



    public function teastErrorPassEmex(){
        if (CModule::IncludeModule("kod.emex")) {

            $error = new CEmexController($this->USER,true,0000);
            $this->assertEquals($error->getError(), "AccessProvider::GetUser. Пустой логин или пароль.");

        }
    }

    public function teastErrorUrlEmex(){
        if (CModule::IncludeModule("kod.emex")) {

            $error = new CEmexController($this->USER,true,0000,"http://ws.emex.ru33/EmExService.asmx?wsdl");
            $this->assertEquals($error->getError(), "SOAP-ERROR: Parsing WSDL: Couldn't load from 'htt");

        }
    }

    public function teastGetPercent()
    {

            $dbPriceType = CExtra::GetList(

            );
            while ($arPriceType = $dbPriceType->Fetch())
            {
                switch ($arPriceType["ID"]) {
                    case 1:
                        $roznicaCen=$arPriceType["PERCENTAGE"];
                        break;
                    case 2:
                        $kat1Cen=$arPriceType["PERCENTAGE"];
                        break;
                    case 3:
                        $kat0Cen=$arPriceType["PERCENTAGE"];
                        break;
                }

            }

        if (CModule::IncludeModule("kod.emex")) {

            CUser::SetUserGroup($this->USER->GetID(), [2]);
            $roznicaUser=CArtikulRepo::Percent($this->USER);
            $this->assertEquals($roznicaUser, $roznicaCen);

            CUser::SetUserGroup($this->USER->GetID(), [10]);
            $kat1User=CArtikulRepo::Percent($this->USER);
            $this->assertEquals($kat1User,  $kat1Cen);

            CUser::SetUserGroup($this->USER->GetID(),[11]);
            $kat0User=CArtikulRepo::Percent($this->USER);
            $this->assertEquals($kat0User, $kat0Cen);

        }
    }
    public function teastGetKatUser(){
        if (CModule::IncludeModule("kod.emex")) {

            $class = new CEmexController($this->USER,true,0000);
            $this->assertStringMatchesFormat('%f', $class->getPercent());
        }
    }

    public function teastGetFullData(){
        if (CModule::IncludeModule("kod.emex")) {
            $aquailArray=['GroupId', 'PriceGroup', 'MakeLogo','MakeName','DetailNum','DetailNameRus',
                'PriceLogo','DestinationLogo','PriceCountry','LotQuantity','Quantity','DDPercent','ADDays','DeliverTimeGuaranteed','ResultPrice'];
            $class = new CEmexController($this->USER,true);
            $data=$class->getFullDataTest()[0];
            $array=collect($data)->toArray();
            $this->assertEquals( $aquailArray,array_keys($array));

        }
    }

    public function teastTransformPrice(){
        if (CModule::IncludeModule("kod.emex")) {
            $class = new CEmexController($this->USER,false);
            $data=$class->getFullDataTest();
            $ResultPrice=$data[0]->ResultPrice;
            $class->transformPrise($data);
            $this->assertEquals(round($ResultPrice*$class->getPercent(),2),$data[0]->ResultPrice);

        }

    }

    public function teastGetPrimaryTable(){
        if (CModule::IncludeModule("kod.emex")) {
            $class = new CEmexController($this->USER,false);
            $data=$class->getPrimaryTable();var_dump($data);die();
            $this->assertJson($data);
        }

    }

   

}
