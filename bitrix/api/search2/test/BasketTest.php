<?php

require_once 'TestBase.php';
use TestBase;

class EmexTest extends TestBase
{
    //Категория0 = 11; Категория1 = 10; иначе розница





    public function teastSetGetBasket(){
        $_POST = Array(
            "mass" => Array
            (
                "GroupId" => -936,
                "PriceGroup" => "Original",
                "MakeLogo" => "AW",
                "MakeName" => "Autolite",
                "DetailNum" => 666,
                "DetailNameRus" => "СВЕЧА ЗАЖИГАНИЯ",
                "PriceLogo" => "SONI",
                "DestinationLogo" => "AFL",
                "PriceCountry" => "Америка",
                "LotQuantity" => 1,
                "Quantity" => 49,
                "DDPercent" => 91.0100,
                "ADDays" => 29,
                "DeliverTimeGuaranteed" => 29,
                "ResultPrice" => 165.39
            ),

            "count" => 1
        );
        $class=new CArtikulRepo();
        $BASE_PRICE=$class->getRoznica($this->USER,$_POST["mass"]["ResultPrice"]);
        if(CModule::IncludeModule("kod.emex")){
            CArtikulRepo::Percent($this->USER);
            $class= new CArtikulRepo();
            $ID=$class->setBasket($this->USER);
        }

        $dbBasketItems = CSaleBasket::GetList(
            array(),
            array(
                "FUSER_ID" => CSaleBasket::GetBasketUserID(),
            ),
            array("ID","PRODUCT_ID","BASE_PRICE","PRICE","DISCOUNT_PRICE","DISCOUNT_VALUE","DISCOUNT_NAME",
                "QUANTITY","NAME","PROPS")
        );
        while ($arItems = $dbBasketItems->Fetch()){
            $result=$arItems;
        }

        $this->assertEquals( $result["ID"],$ID);
        $this->assertEquals( [$result["PRODUCT_ID"],$result["PRICE"],$result["BASE_PRICE"]],[$_POST["mass"]["DetailNum"],$_POST["mass"]["ResultPrice"],$BASE_PRICE]);



    }

    public function teastProperty(){
        $_POST = Array(
            "mass" => Array
            (
                "GroupId" => -936,
                "PriceGroup" => "Original",
                "MakeLogo" => "AW",
                "MakeName" => "Autolite",
                "DetailNum" => 666,
                "DetailNameRus" => "СВЕЧА ЗАЖИГАНИЯ",
                "PriceLogo" => "SONI",
                "DestinationLogo" => "AFL",
                "PriceCountry" => "Америка",
                "LotQuantity" => 1,
                "Quantity" => 49,
                "DDPercent" => 91.0100,
                "ADDays" => 29,
                "DeliverTimeGuaranteed" => 29,
                "ResultPrice" => 165.39,

            ),

            "count" => 1
        );
        $class=new CArtikulRepo();
        $BASE_PRICE=$class->getRoznica($this->USER,$_POST["mass"]["ResultPrice"]);
        if(CModule::IncludeModule("kod.emex")){
            CArtikulRepo::Percent($this->USER);
            $class= new CArtikulRepo();
            $ID=$class->setBasket($this->USER);
        }



        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
            \Bitrix\Sale\Fuser::getId(),
            \Bitrix\Main\Context::getCurrent()->getSite()
        );

        // массив объектов \Bitrix\Sale\BasketItem
        $basketItems = $basket->getBasketItems();

        $basketItem = $basketItems[0]; //current($basketItems);

        // Свойства записи, массив объектов Sale\BasketPropertyItem
        $basketPropertyCollection = $basketItem->getPropertyCollection();
        $basketPropertyCollection = \Bitrix\Sale\BasketPropertiesCollection::load($basketItem);
        $props = $basketPropertyCollection->getPropertyValues();
        dd($props);


    }

    public function teastGetRoznica(){
        $cena=100;
        $class=new CArtikulRepo();
        //Розница
        CUser::SetUserGroup($this->USER->GetID(),[2]);
        $roznica=(CArtikulRepo::Percent($this->USER)/100+1)*$cena;
        $roznica=$class->getRoznica($this->USER,$roznica);
        //Категория 0
        CUser::SetUserGroup($this->USER->GetID(),[11]);
        $cena0=(CArtikulRepo::Percent($this->USER)/100+1)*$cena;
        $this->assertEquals($class->getRoznica($this->USER,$cena0),$roznica);
        //Категория 1
        CUser::SetUserGroup($this->USER->GetID(),[10]);
        $cena1=(CArtikulRepo::Percent($this->USER)/100+1)*$cena;
        $this->assertEquals($class->getRoznica($this->USER,$cena1),$roznica);
        $this->assertFalse($cena1==$roznica);

    }

    public function testMailBasket(){
        if(CModule::IncludeModule("sale") && CModule::IncludeModule("iblock")) {
            //СОСТАВ ЗАКАЗА РАЗБИРАЕМ SALE_ORDER НА ЗАПЧАСТИ
            $strCustomOrderList = "";
            $dbBasketItems = CSaleBasket::GetList(
                array("NAME" => "ASC"),
                array("ORDER_ID" => 246 ),
                false,
                false,
                array("PRODUCT_ID", "ID", "NAME", "QUANTITY", "PRICE", "CURRENCY")
            );
            while ($arProps = $dbBasketItems->Fetch()) {
                //ПЕРЕМНОЖАЕМ КОЛИЧЕСТВО НА ЦЕНУ

                $summ = $arProps['QUANTITY'] * $arProps['PRICE'];
                //СОБИРАЕМ В СТРОКУ ТАБЛИЦЫ
                $strCustomOrderList .= "<tr><td>" . $arProps['NAME'] . "</td><td>" . $arProps['QUANTITY'] . "</td><td>" . $arProps['PRICE'] . "</td><td>" . $arProps['CURRENCY'] . "</td><td>" . $summ . "</td><tr>";
            }
            //ОБЪЯВЛЯЕМ ПЕРЕМЕННУЮ ДЛЯ ПИСЬМА
            $arFields["ORDER_TABLE_ITEMS"] = $strCustomOrderList;

        }
        dd($arFields["ORDER_TABLE_ITEMS"]);
    }



}
