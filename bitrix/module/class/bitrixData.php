<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Arrilot\BitrixModels\Models\ElementModel;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class CBitrixController
{

    private $IBLOCK_ID;
    private $hlblock_id;
    private $art;
    private $USER;

    function __construct($IBLOCK_ID, $hlblock_id, $art = false, $USER = null)
    {

        $this->art = $art;
        $this->IBLOCK_ID = $IBLOCK_ID;
        $this->hlblock_id = $hlblock_id;
        $this->USER = $USER;
    }

    public function getAnalogiSearch()
    {
        CModule::IncludeModule('search');
        $module_id = "iblock";
        $obSearch = new CSearch;
        $obSearch->Search(array(
            "QUERY" => $this->art,
            "=MODULE_ID" => $module_id,
            "PARAM1" => "1c_catalog",
            "PARAM2" => $this->IBLOCK_ID,
        ));

        while ($arResult = $obSearch->Fetch()) {
            $ITEM_ID[] = $arResult["ITEM_ID"];
        }

        $arSelect = Array("ID", "XML_ID");
        $arFilter = Array("IBLOCK_ID" => $this->IBLOCK_ID, "ID" => $ITEM_ID);
        $res_xml = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while ($arXml = $res_xml->Fetch()) {
            $analogi[] = $arXml["XML_ID"];
            $db_props = CIBlockElement::GetProperty($this->IBLOCK_ID, $arXml["ID"], array(), Array("CODE" => "ANALOGI3"));
            while ($ar_props = $db_props->Fetch()) {
                $analogi[] = $ar_props["VALUE"];
            }
        }
        return $analogi;

    }

    public function getAnalogi()
    {
        $arSelect = Array("ID", "XML_ID", "PROPERTY_ANALOGI3", "PROPERTY_CML2_ARTICLE", "PROPERTY_ARTIKULDLYAPOISKA_ATTR_S2");
        $arFilter = Array("IBLOCK_ID" => $this->IBLOCK_ID, array("LOGIC" => "OR", ["PROPERTY_CML2_ARTICLE" => $this->art], ["PROPERTY_ARTIKULDLYAPOISKA_ATTR_S2" => $this->art]));
        $cache = new CPHPCache();
        if ($cache->InitCache(60, 'analogi' . $this->art)) {
            $res = $cache->GetVars();
            $res_xml = $res['arResult'];
        } elseif ($cache->StartDataCache()) {
            $res_xml = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $cache->EndDataCache(array("arResult" => $res_xml));
        }
        $res_xml = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while ($arXml = $res_xml->Fetch()) {
            $analogi[] = $arXml["XML_ID"];
            foreach ($arXml["PROPERTY_ANALOGI3_VALUE"] as $item) {
                $analogi[] = $item;
            }
        }
        return $analogi;
    }

    public function getData($post = false)
    {
        if (!$this->art) {
            $data["show"] = false;
            return json_encode($data);
        }
        $analogi = $this->getAnalogi();
        if (!$analogi) {
            $data["show"] = false;
            return json_encode($data);
        }
        $arSelect = Array("ID", "XML_ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_CML2_ARTICLE", "PROPERTY_PROIZVODITEL3",
            "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_SROK_POSTAVKI", "PROPERTY_CML2_MANUFACTURER");
        $arFilter = Array("IBLOCK_ID" => $this->IBLOCK_ID, "XML_ID" => $analogi, "ACTIVE" => "Y"/*, "PROPERTY_SROK_POSTAVKI" => $post ? "%" : false/*Выбор между поставщиком и своим товаром*/);
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        //Подключает модуль highloadblock
        CModule::IncludeModule("highloadblock");
        $hlblock_id = $this->hlblock_id;
        $hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        //$res->NavStart();

        //dd($res->NavRecordCount); //Подсчитывает количество записей

        while ($arBrand = $res->Fetch()) {
            //Фильтрует нужного производителя с highloadblock
            $filterAll = array('UF_XML_ID' => $arBrand['PROPERTY_PROIZVODITEL3_VALUE']);

            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("UF_NAME" => "ASC"),
                "filter" => $filterAll
            ));
            while ($arData = $rsData->Fetch()) {
                $UF_NAME = $arData["UF_NAME"];

            }
            $userId = $this->USER == null? 7366 : $this->USER->GetID();
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

            //var_dump($db_res);

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
                array("IBLOCK_ID" => $this->IBLOCK_ID, "ACTIVE" => "Y", "ID" => $arBrand['IBLOCK_SECTION_ID']),
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

        if ($arResult == null) $data["show"] = false;
        else
            $data["show"] = true;
        $data["result"] = $arResult;

        return json_encode($data);
    }
}

?>