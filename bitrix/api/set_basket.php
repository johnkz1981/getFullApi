<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// CModule::IncludeModule("sale");

$id = (array)json_decode($id);
$props = [];

foreach ($id as $key => $item) {
  $props[] = ["NAME" => $key,
    "VALUE" => $item,];
}

$arFields = array(
  "PRODUCT_ID" => $id['id'],
  "PRODUCT_PRICE_ID" => 0,
  "BASE_PRICE" => 0,
  "PRICE" => $id['price'],
  "DISCOUNT_PRICE" => 0,
  "DISCOUNT_VALUE" => 0,
  "DISCOUNT_NAME" => "Скидка по акции",
  "CURRENCY" => "RUB",
  "QUANTITY" => $count,
  "LID" => "s1",
  "DELAY" => "N",
  "CAN_BUY" => "Y",
  "CUSTOM_PRICE" => "Y",
  "NAME" => $id["DetailNameRus"],
  "MODULE" => "catalog",
  "PROPS" => $props,
  "NOTES" => "",
);

echo CSaleBasket::Add($arFields);

