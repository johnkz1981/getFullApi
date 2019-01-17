<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
?><?

CModule::IncludeModule("search");
$q = "op595";
$IBLOCK_ID = 150;
$module_id = "iblock";
$obSearch = new CSearch;
$obSearch->Search(array(
  "QUERY" => $q,
  "SITE_ID" => LANG,
  "MODULE_ID" => $module_id,
  "PARAM1" => "1c_catalog",
  "PARAM2" => $IBLOCK_ID,
));
$obSearch->NavStart();

if ($obSearch->NavRecordCount === 0) {
  echo 'Записей не найдено!';
  die();
}

while ($arResult = $obSearch->Fetch()) {
  $arrItemId[] = $arResult["ITEM_ID"];
}

$analogsAndOriginal = [];
$contractorId = [];
$manufacturer = [];

function clearString($string)
{
  return str_replace(['!', '#', '$', '%', '&', "'", '*', '+', '-', '=', '?', '^', '_', '`', '{', '|', '}', '~',
    '@', '.', '[', ']'], '', filter_var($string, FILTER_SANITIZE_EMAIL));
}

$arSelect = Array("ID", "XML_ID");
$arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "ID" => $arrItemId);
$CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
while ($resItem = $CDBRes->Fetch()) {
  // Сравниваем Артикул модуля поиска с оригинальным артикулам

  $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "CML2_ARTICLE"));
  while ($resItemProp = $CDBResProps->Fetch()) {

    if ($resItemProp["VALUE"] === mb_strtoupper($q)) {
      // Отделаяем 1с от excel
      if ($resItem["XML_ID"] !== $resItem["ID"]) {
        $analogsAndOriginal[] = $resItem["XML_ID"];
      } else {
        $contractorId[] = $resItem["ID"];
        continue;
      }

      // Создаем массив XML_ID аналоги + искомый артикул
      $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "ANALOGI3"));
      while ($resItemProp = $CDBResProps->Fetch()) {
        if (empty($resItemProp["VALUE"])) {
          continue;
        } else {
          $analogsAndOriginal[] = $resItemProp["VALUE"];
        }
      }
    }
  }
}

if (!$analogsAndOriginal && !$contractorId) {
  echo 'Не найдено Артикула по точному соответсвию!';
  die();
}

echo '<pre>';
/*print_r($analogsAndOriginal);
print_r($contractorId);
print_r($manufacturer);*/

function getIdOnXmlId($IBLOCK_ID, $analogsAndOriginal, $contractorId)
{
  $result = [];
  $arSelect = Array("ID", "XML_ID");
  $arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "XML_ID" => $analogsAndOriginal);
  $CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
  while ($resItem = $CDBRes->GetNext()) {
    array_push($contractorId, $resItem["ID"]);
  }
  return $contractorId;
}

if ($analogsAndOriginal) {
  $contractorId = getIdOnXmlId($IBLOCK_ID, $analogsAndOriginal, $contractorId);
}

function getPrise($USER, $ID)
{
  $userId = $USER == null ? 7366 : $USER->GetID();
  //Поиск Цены из справочника Цен пропускает цикл если нет цены
  $arGroupAvalaible = array(10, 11, 12); // массив групп, которые в которых нужно проверить доступность пользователя
  $arGroups = CUser::GetUserGroup($userId); // массив групп, в которых состоит пользователь
  $result_intersect = array_intersect($arGroupAvalaible, $arGroups);// далее проверяем, если пользователь вошёл хотя бы в одну из групп

  $resultPop = array_pop($result_intersect);
  $CATALOG_GROUPS = [10 => 7, 11 => 8, 12 => 34];

  $db_res = CPrice::GetList(
    array(),
    array(
      "PRODUCT_ID" => $ID,
      "CATALOG_GROUP_ID" => empty($resultPop) ? 2 : $CATALOG_GROUPS[$resultPop]
    )
  );

  if ($ar_res = $db_res->Fetch()) {
    return $ar_res["PRICE"];
  }
}

CModule::IncludeModule("highloadblock");

use Bitrix\Highloadblock as HL;

function getManufacturer($manufacturer)
{
  $UF_NAME = [];
  $hlblock_id = 5;
  $hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
  $entity = HL\HighloadBlockTable::compileEntity($hlblock);
  $entity_data_class = $entity->getDataClass();

  $filterAll = array('UF_XML_ID' => $manufacturer);

  $rsData = $entity_data_class::getList(array(
    "select" => array("*"),
    "order" => array("UF_NAME" => "ASC"),
    "filter" => $filterAll
  ));

  while ($arData = $rsData->Fetch()) {
    $UF_NAME = $arData["UF_NAME"];
  }
  return $UF_NAME;
}

$arr = [];
$arSelect = Array("ID", "XML_ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL");
$arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "ID" => $contractorId);
$CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

while ($resItem = $CDBRes->GetNext()) {
  $obArticle = new stdClass();

  $obArticle->id = $resItem["ID"];
  $obArticle->xmlId = $resItem["XML_ID"];
  $obArticle->name = $resItem["NAME"];
  $obArticle->code = $resItem["CODE"];
  $obArticle->iblockSectionId = $resItem["IBLOCK_SECTION_ID"];
  $obArticle->detailPageUrl = $resItem["DETAIL_PAGE_URL"];
  $obArticle->prise = getPrise($USER, $resItem["ID"]);

  if (strlen($resItem["XML_ID"]) === 36) {
    $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "PROIZVODITEL3"));
    while ($resItemProp = $CDBResProps->Fetch()) {
      $obArticle->manufacturer = getManufacturer($resItemProp["VALUE"]);
    }
  } else {
    $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "PROIZVODITEL_PRAIS"));
    while ($resItemProp = $CDBResProps->Fetch()) {
      $obArticle->manufacturer = $resItemProp["VALUE"];
    }
  }

  $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "CML2_ARTICLE"));
  while ($resItemProp = $CDBResProps->Fetch()) {
    $obArticle->vendorСode = $resItemProp["VALUE"];
    $obArticle->vendorСodeClearString = clearString($resItemProp["VALUE"]);
  }

  $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "ARTIKULDLYAPOISKA_ATTR_S2"));
  while ($resItemProp = $CDBResProps->Fetch()) {
    $obArticle->vendorСodeSearch = $resItemProp["VALUE"];
  }

  $CDBResProps = CIBlockElement::GetProperty($IBLOCK_ID, $resItem["ID"], array(), Array("CODE" => "SROK_POSTAVKI"));
  while ($resItemProp = $CDBResProps->Fetch()) {
    $obArticle->deliveryTime = $resItemProp["VALUE"];
  }

  //Получаем количество и ID еденицы измерения
  $db_res = CCatalogProduct::GetList(
    array("QUANTITY" => "DESC"),
    array("QUANTITY_TRACE" => "Y", "ID" => $resItem["ID"],),
    false,
    false
  );

  $measureId = 0;
  while ($ar_res = $db_res->Fetch()) {
    $obArticle->quantity = $ar_res["QUANTITY"];
    $measureId = $ar_res["MEASURE"];
  }

  $db_resm = CCatalogMeasure::GetList(array(), array("ID" => $measureId));
  while ($ar_resm = $db_resm->Fetch()) {
    $obArticle->measure = $ar_resm["SYMBOL_RUS"];
  }

  $rsSections = CIBlockSection::GetList(
    array(),
    array("IBLOCK_ID" => $IBLOCK_ID, "ACTIVE" => "Y", "ID" => $resItem['IBLOCK_SECTION_ID']),
    false
  );
  while ($arSections = $rsSections->fetch()) {
    $obArticle->sectionActive = $arSections['CODE'];
  }
  array_push($arr, $obArticle);
}

print_r($arr);
//----------------------------------------

die();
$arSelect = Array("ID", "XML_ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_CML2_ARTICLE", "PROPERTY_PROIZVODITEL3",
  "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_SROK_POSTAVKI");
$arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "XML_ID" => $analogs, "ACTIVE" => "Y");
$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

while ($arBrand = $res->Fetch()) {
  //Фильтрует нужного производителя с highloadblock

  $userId = $USER == null ? 7366 : $USER->GetID();
  //Поиск Цены из справочника Цен пропускает цикл если нет цены
  $arGroupAvalaible = array(10, 11, 12); // массив групп, которые в которых нужно проверить доступность пользователя
  $arGroups = CUser::GetUserGroup($userId); // массив групп, в которых состоит пользователь
  $result_intersect = array_intersect($arGroupAvalaible, $arGroups);// далее проверяем, если пользователь вошёл хотя бы в одну из групп

  $resultPop = array_pop($result_intersect);
  $CATALOG_GROUPS = [];
  $CATALOG_GROUPS[10] = 7;
  $CATALOG_GROUPS[11] = 8;
  $CATALOG_GROUPS[12] = 34;

  $db_res = CPrice::GetList(
    array(),
    array(
      "PRODUCT_ID" => $arBrand['ID'],
      "CATALOG_GROUP_ID" => empty($resultPop) ? 2 : $CATALOG_GROUPS[$resultPop]
    )
  );

  if ($ar_res = $db_res->Fetch()) {
    $PRICE = $ar_res["PRICE"];

  } else {
    continue;
  }
  //Получаем количество и  ID еденицы измерения
  $db_res = CCatalogProduct::GetList(
    array("QUANTITY" => "DESC"),
    array("QUANTITY_TRACE" => "Y", "ID" => $arBrand['ID'],),
    false,
    false
  );

  while ($ar_res = $db_res->Fetch()) {
    $QUANTITY = $ar_res["QUANTITY"];
    $MEASURE = $ar_res["MEASURE"];
  }

  if ($QUANTITY < 1) continue;//Пропустить цикл если товара меньше 1-го
  //Фильтрует неактивные разделы
  $rsSections = CIBlockSection::GetList(
    array(),
    array("IBLOCK_ID" => $IBLOCK_ID, "ACTIVE" => "Y", "ID" => $arBrand['IBLOCK_SECTION_ID']),
    false
  );
  while ($arSections = $rsSections->fetch()) {
    $getIdcat = $arSections['CODE'];
  }
  //Получаем еденицы измерения зная ID
  $db_resm = CCatalogMeasure::GetList(
    array(),
    array("ID" => $MEASURE)
  );
  while ($ar_resm = $db_resm->Fetch()) {
    $SYMBOL_RUS = $ar_resm["SYMBOL_RUS"];
  }

  $PATH = '/catalog/' . $getIdcat . '/' . $arBrand['CODE'] . '/';
  $arResult[] = [
    "UF_NAME" => $UF_NAME,
    'ARTICLE' => $arBrand['PROPERTY_CML2_ARTICLE_VALUE'],
    'PROIZVODITEL' => $arBrand['PROPERTY_PROIZVODITEL3_VALUE'],
    'NAME' => $arBrand['NAME'],
    'CODE' => $arBrand['CODE'],
    "PRICE" => $PRICE,
    "QUANTITY" => $QUANTITY,
    "PATH" => $PATH,
    "SYMBOL_RUS" => $SYMBOL_RUS,
    "ID" => $arBrand['ID'],
    "SROK_POSTAVKI" => $arBrand['PROPERTY_SROK_POSTAVKI_VALUE'],
    "PROIZVODITEL2" => $arBrand['PROPERTY_CML2_MANUFACTURER_VALUE'],
    "user" => "$resultPop",
    "CATALOG_GROUP" => empty($resultPop) ? 2 : $CATALOG_GROUPS[$resultPop]
  ];
}

