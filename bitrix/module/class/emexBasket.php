<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

class CBasket{
    public function setBasket(){
        foreach($_POST["mass"] as $key=>$item) {
            $props[]=["NAME" => $key,
                "VALUE" => $item,];
        }

        CModule::IncludeModule("sale");

        $ID = $_POST["mass"]["DetailNum"];

        $arFields = array(
            "PRODUCT_ID" => $ID,
            "PRODUCT_PRICE_ID" => 0,
            "BASE_PRICE" => 10000,
            "PRICE" => $_POST["mass"]["ResultPrice"],
            "DISCOUNT_PRICE" => 2500,
            "DISCOUNT_VALUE" => 2500,
            "DISCOUNT_NAME" => "Скидка по акции",
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
}

?>