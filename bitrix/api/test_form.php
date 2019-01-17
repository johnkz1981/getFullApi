<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// if (CModule::IncludeModule("sale") && CModule::IncludeModule("iblock")) {

$productArr = [];

$dbBasketItems22 = CSaleBasket::GetList(
  array("NAME" => "ASC"),
  array("ORDER_ID" => 268),
  false,
  false,
  array("PRODUCT_ID", "ID", "NAME", "QUANTITY", "PRICE", "CURRENCY")
);
while ($arProps = $dbBasketItems22->Fetch()) {

  $product = new stdClass();
  $product->name = $arProps['NAME'];
  $product->id = $arProps['ID'];
  $product->quantity = $arProps['QUANTITY'];
  $product->price = round($arProps['PRICE']);
  $product->sum = $arProps['QUANTITY'] * $arProps['PRICE'];

  $db_res = CSaleBasket::GetPropsList(
    array(
      "SORT" => "ASC",
      "NAME" => "ASC"
    ),
    array("BASKET_ID" => $arProps['ID'], "NAME" => 'STATUS')
  );
  $product->status = $db_res->Fetch()['VALUE'];

  $productArr[] = $product;


}
// var_dump($productArr);
//}
use Bitrix\Sale\Internals\BasketTable;
$dbBasketItems = BasketTable::GetList(
  [
    'filter' => ["ORDER_ID" => 268],
    'select' => ["PRODUCT_ID", "ID", "NAME", "QUANTITY", "PRICE", "CURRENCY"]
  ]
);

var_dump($dbBasketItems->fetch());

