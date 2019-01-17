<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
?><?

use Bitrix\Highloadblock as HL;

class CSearchCode
{
  private $source = null;

  public function __construct($source)
  {
    $target = new stdClass();
    $target->q = '';
    $target->iBlockId = 150;
    $target->hlBlockId = 5;
    $target->markup = false;
    $target->userId = 0;
    $target->isAdmin = false;
    
    $this->source = (object)array_merge((array)$target, (array)$source);

    if($this->source->markup){
      echo $this->_getMarkup();
      return;
    }
    if($this->source->isAdmin){
      echo $this->_isAdmin();
      return;
    }
    CModule::IncludeModule("search");

    $arrSearchId = $this->_getSearchId();

    if (empty($arrSearchId)) {
      echo 'Артикул не найден';
      return;
    }

    $getArrXmlId = $this->_getArrXmlId($arrSearchId);
    if (empty($getArrXmlId)) {
      echo 'Оригинальный артикул не найден';
      die();
    }
    echo json_encode($this->render($getArrXmlId));
  }

  private function _getSearchId()
  {
    $arrSearchId = [];
    $module_id = "iblock";
    $obSearch = new CSearch;

    $obSearch->Search(array(
      "QUERY" => $this->source->q,
      "SITE_ID" => LANG,
      "MODULE_ID" => $module_id,
      "PARAM1" => "1c_catalog",
      "PARAM2" => $this->source->iBlockId,
    ));

    while ($arResult = $obSearch->Fetch()) {
      $arrSearchId[] = $arResult["ITEM_ID"];
    }
    return $arrSearchId;
  }

  private function _clearString($string)
  {
    return str_replace(['!', '#', '$', '%', '&', "'", '*', '+', '-', '=', '?', '^', '_', '`', '{', '|', '}', '~',
      '@', '.', '[', ']'], '', filter_var($string, FILTER_SANITIZE_EMAIL));
  }

  private function _getProps($id, $code)
  {
    $CDBResProps = CIBlockElement::GetProperty($this->source->iBlockId, $id, array(), Array("CODE" => $code));

    while ($resItemProp = $CDBResProps->Fetch()) {
      $arrProps[] = $resItemProp["VALUE"];
    }
    return $arrProps;
  }

  private function _getArrXmlId($arrItemId)
  {
    $analogsAndOriginal = [];
    $arSelect = Array("ID", "XML_ID");
    $arFilter = Array("IBLOCK_ID" => $this->source->iblockId, "ID" => $arrItemId);
    $CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

    while ($resItem = $CDBRes->Fetch()) {
      // Сравниваем Артикул модуля поиска с оригинальным артикулам

      $vendorCode = $this->_getProps($resItem["ID"], "CML2_ARTICLE")[0];
      $vendorCodeSearch = $this->_getProps($resItem["ID"], "ARTIKULDLYAPOISKA_ATTR_S2")[0];

      if ($vendorCode === mb_strtoupper($this->source->q) || $vendorCodeSearch === $this->_clearString(mb_strtoupper($this->source->q))) {
        array_push($analogsAndOriginal, $resItem["XML_ID"]);

        foreach ($this->_getProps($resItem["ID"], "ANALOGI3") as $item) {
          if ($item === null) continue;
          array_push($analogsAndOriginal, $item);
        }
      }
    }
    return $analogsAndOriginal;
  }

  private function _getPrice($ID)
  {
    $arGroupAvalaible = array(10, 11, 12); // массив групп, которые в которых нужно проверить доступность пользователя
    $arGroups = CUser::GetUserGroup($this->source->userId); // массив групп, в которых состоит пользователь
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

  private function _getManufacturer($manufacturer)
  {
    //CModule::IncludeModule("highloadblock");
    $UF_NAME = [];
    $hlBlock = HL\HighloadBlockTable::getById($this->source->hlBlockId)->fetch();
    $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
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

  function _isSectionActive($sectionId)
  {
    $rsSections = CIBlockSection::GetList(
      array(),
      array("IBLOCK_ID" => $this->source->iblockId, "ACTIVE" => "Y", "ID" => $sectionId),
      false
    );
    while ($arSections = $rsSections->fetch()) {
      return $arSections['CODE'];
    }
  }

  function _getQuantityAndMeasure($id)
  {
    //Получаем количество и ID еденицы измерения
    $db_res = CCatalogProduct::GetList(
      array("QUANTITY" => "DESC"),
      array("QUANTITY_TRACE" => "Y", "ID" => $id,),
      false,
      false
    );

    while ($ar_res = $db_res->Fetch()) {
      $quantity = $ar_res["QUANTITY"];
      $measureId = $ar_res["MEASURE"];
    }

    $db_resm = CCatalogMeasure::GetList(array(), array("ID" => $measureId));
    while ($ar_resm = $db_resm->Fetch()) {
      $measure = $ar_resm["SYMBOL_RUS"];
    }
    return ["QUANTITY" => $quantity, "MEASURE" => $measure];
  }

  function _getMarkup()
  {
    /**
     * кат. 1 [10] id = 2
     * кат. 0 [11] id = 3
     * Кат -1 [12] id =
     * Розница [13] id = 1
     */
    $arGroups = CUser::GetUserGroup($this->source->userId);
    $result_intersect = array_intersect([10, 11, 12], $arGroups);
    $resultPop = array_pop($result_intersect);
    $arrMarkup = [
      10 => 2,
      11 => 3,
      13 => 1,
    ];
    $id = $resultPop === null ? 13 : $resultPop;
    $idMarkup = isset($arrMarkup[$id]) ? $arrMarkup[$id] : 1;
    $CDBResult = CExtra::GetList(
      [], ['ID' => $idMarkup], false, false, ['PERCENTAGE']
    );

    while ($result = $CDBResult->fetch()) {
      return $result['PERCENTAGE'];
    }
  }

  function _isAdmin()
  {
    /**
     * id = 1
     */
    $arGroups = CUser::GetUserGroup($this->source->userId);
    $result_intersect = array_intersect([1], $arGroups);
    return array_pop($result_intersect);
  }

  function render($arrXmlId)
  {
    $arr = [];

    $arSelect = Array("ID", "XML_ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL");
    $arFilter = Array("IBLOCK_ID" => $this->source->iBlockId, "XML_ID" => $arrXmlId);
    $CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

    while ($resItem = $CDBRes->GetNext()) {
      $obArticle = new stdClass();
      $isSectionActive = $this->_isSectionActive($resItem['IBLOCK_SECTION_ID']);
      $quantityAndMeasure = $this->_getQuantityAndMeasure($resItem["ID"]);

      if ($quantityAndMeasure["QUANTITY"] == 0 || !$isSectionActive) {
        continue;
      }

      $obArticle->id = $resItem["ID"];
      $obArticle->xmlId = $resItem["XML_ID"];
      $obArticle->name = $resItem["~NAME"];
      $obArticle->code = $resItem["CODE"];
      $obArticle->iblockSectionId = $resItem["IBLOCK_SECTION_ID"];
      $obArticle->detailPageUrl = $resItem["DETAIL_PAGE_URL"];
      $obArticle->price = $this->_getPrice($resItem["ID"]);
      $obArticle->vendorCode = $this->_getProps($resItem["ID"], "CML2_ARTICLE")[0];
      $obArticle->deliveryTime = $this->_getProps($resItem["ID"], "SROK_POSTAVKI")[0];
      $obArticle->contractor = $this->_getProps($resItem["ID"], "POSTAVCHIK")[0];
      $obArticle->manufacturer = strlen($resItem["XML_ID"]) === 36 ?
        $this->_getManufacturer($this->_getProps($resItem["ID"], "PROIZVODITEL3")[0]) :
        $this->_getProps($resItem["ID"], "PROIZVODITEL_PRAIS")[0];
      $obArticle->quantity = $quantityAndMeasure["QUANTITY"];
      $obArticle->measure = $quantityAndMeasure["MEASURE"];
      $arr[] = $obArticle;
    }
    return $arr;
  }
}

$source = new stdClass();
$source->q = $q;
$source->iBlockId = 150;
$source->hlBlockId = 5;
$source->markup = $markup;
$source->userId = $userId;
$source->isAdmin = $isAdmin;

$obSearchCode = new CSearchCode( $source);