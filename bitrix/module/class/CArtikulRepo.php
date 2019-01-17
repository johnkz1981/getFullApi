<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");


class CArtikulRepo{

    static public function Percent($USER){
        $dbPriceType = CExtra::GetList(

        );
        while ($arPriceType = $dbPriceType->Fetch())
        {
            switch ($arPriceType["ID"]) {
                case 1:
                    $roznica=$arPriceType["PERCENTAGE"];
                    break;
                case 2:
                    $kat1=$arPriceType["PERCENTAGE"];
                    break;
                case 3:
                    $kat0=$arPriceType["PERCENTAGE"];
                    break;
            }

        }
        $katUser=$roznica;
        $arGroups = CUser::GetUserGroup($USER->GetID()); // массив групп, в которых состоит пользователь
        if(!empty(array_intersect($arGroups, [10])))$katUser=$kat1;
        if(!empty(array_intersect($arGroups, [11])))$katUser=$kat0;
        return $katUser;
    }

    public function setBasket($USER){
        $Percent=$this->Percent($USER);
        $Percent=$Percent/100+1;
        //$BasePrise=$_POST["mass"]["ResultPrice"]/$Percent*
        foreach($_POST["mass"] as $key=>$item) {
            if($key=="ADDays") $props[]=["NAME" => "SROK_POSTAVKI","VALUE" => $item,];
            $props[]=["NAME" => $key,"VALUE" => $item,];
        }
        $props[]=["NAME" => "POSTAVCHIK","VALUE" => "ООО \"АВЭКС\"",];
        $props[]=["NAME" => "IDENTIFIKATOR","VALUE" => "0004",];

        CModule::IncludeModule("sale");

        $ID = $_POST["mass"]["DetailNum"];
        $this->getRoznica($USER,$_POST["mass"]["ResultPrice"]);
        $arFields = array(
            "PRODUCT_ID" => $ID,
            "PRODUCT_PRICE_ID" => 0,
            "BASE_PRICE" => $this->getRoznica($USER,$_POST["mass"]["ResultPrice"]),
            "PRICE" => $_POST["mass"]["ResultPrice"],
            /*"DISCOUNT_PRICE" => 2500,
            "DISCOUNT_VALUE" => 2500,
            "DISCOUNT_NAME" => "Скидка по акции",*/
            "CURRENCY" => "RUB",
            "QUANTITY" => $_POST["count"],
            "LID" => "s1",
            "DELAY" => "N",
            "CAN_BUY" => "Y",
            "CUSTOM_PRICE" => "Y",
            "NAME" => $_POST["mass"]["DetailNameRus"],
            "MODULE" => "catalog",
            "PROPS" => $props,
            "NOTES" => "",

        );
        return CSaleBasket::Add($arFields);
    }
    public function getRoznica($USER,$cena){
        $dbPriceType = CExtra::GetList(

        );
        while ($arPriceType = $dbPriceType->Fetch())
        {
            if($arPriceType["ID"]==1) {
                    $roznica=$arPriceType["PERCENTAGE"];
            }

        }
        $percentUser=$this->Percent($USER)/100+1;
        $percentRoznica=$roznica/100+1;
        $cena=$cena/$percentUser*$percentRoznica;

        return $cena;
    }
}