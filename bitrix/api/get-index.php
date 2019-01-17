<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
?><?

use Bitrix\Highloadblock as HL;

class CSearchCode
{
  private $q = '';
  private $iBlockId = 0;
  private $user = null;
  private $hlBlockId = 0;

  public function __construct($Id, $iBlockId, $user, $hlBlockId)
  {
    CModule::IncludeModule("search");

    $this->iBlockId = $iBlockId;
    $this->hlBlockId = $hlBlockId;
    $this->user = $user;
    $id = $_GET["id"];
    if(empty($id) || $id === null || $id === ''){
      echo 'Введите индекс';
    }else{
      var_dump($this->render($id));
    }
  }

  private function _getSearchId()
  {
    $arrSearchId = [];
    $module_id = "iblock";
    $obSearch = new CSearch;

    $obSearch->Search(array(
      "QUERY" => $this->q,
      "SITE_ID" => LANG,
      "MODULE_ID" => $module_id,
      "PARAM1" => "1c_catalog",
      "PARAM2" => $this->iBlockId,
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
    $CDBResProps = CIBlockElement::GetProperty($this->iBlockId, $id, array(), Array("CODE" => $code));

    while ($resItemProp = $CDBResProps->Fetch()) {
      $arrProps[] = $resItemProp["VALUE"];
    }
    return $arrProps;
  }

  private function _getArrXmlId($arrItemId)
  {
    $analogsAndOriginal = [];
    $arSelect = Array("ID", "XML_ID");
    $arFilter = Array("IBLOCK_ID" => $this->iblockId, "ID" => $arrItemId);
    $CDBRes = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

    while ($resItem = $CDBRes->Fetch()) {
      // Сравниваем Артикул модуля поиска с оригинальным артикулам

      $vendorCode = $this->_getProps($resItem["ID"], "CML2_ARTICLE")[0];
      $vendorCodeSearch = $this->_getProps($resItem["ID"], "ARTIKULDLYAPOISKA_ATTR_S2")[0];

      if ($vendorCode === mb_strtoupper($this->q) || $vendorCodeSearch === $this->_clearString(mb_strtoupper($this->q))) {
        array_push($analogsAndOriginal, $resItem["XML_ID"]);

        foreach ($this->_getProps($resItem["ID"], "ANALOGI3") as $item) {
          if ($item === null) continue;
          array_push($analogsAndOriginal, $item);
        }
      }
    }
    return $analogsAndOriginal;
  }

  private function _getPrise($USER, $ID)
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

  private function _getManufacturer($manufacturer)
  {
    //CModule::IncludeModule("highloadblock");
    $UF_NAME = [];
    $hlBlock = HL\HighloadBlockTable::getById($this->hlBlockId)->fetch();
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
      array("IBLOCK_ID" => $this->iblockId, "ACTIVE" => "Y", "ID" => $sectionId),
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

  function render($Id)
  {
    $sectionActive = '';
    $arr = [];

    $arSelect = Array("ID", "XML_ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL");
    $arFilter = Array("IBLOCK_ID" => $this->iBlockId, "ID" => $Id);
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
      $obArticle->prise = $this->_getPrise($this->user, $resItem["ID"]);
      $obArticle->vendorСode = $this->_getProps($resItem["ID"], "CML2_ARTICLE")[0];
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

$obSearchCode = new CSearchCode(555,150, $USER, 5);