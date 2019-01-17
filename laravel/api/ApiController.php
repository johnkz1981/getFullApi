<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
  use TraitForApiController, TBasket;

  private $q = null;
  private $arResult = null;
  private $userId = '';

  function __construct(Request $request)
  {
    $objProvader = new \stdClass();

    $target = new \stdClass();
    $target->makeLogo = '';
    $target->substLevel = '';
    $target->brandAndCode = '';
    $target->bitrix = '';
    $target->priceGroupName = '';
    $target->priceGroup = '';
    $target->group = '';
    $target->array = '';
    $target->skipLimit = 0;
    $target->lazy = '';
    $target->basket = '';

    $this->userId = $request->input('userId');
    $this->q = (object)array_merge((array)$target, (array)json_decode($request->input('q')));
    $this->_basket();
  }

  function index()
  {
    $result = null;
    if ($this->q->substLevel === 'All' && $this->q->lazy !== 'yes') {
      $result = $this->_getAllDetailApi();
    } else {
      $result = $this->_mergerContractor();
    }

    if ($this->q->array === 'yes') {
      print_r($result);
      return;
    }
    echo json_encode($result);
  }

  private function _getAllDetailApi()
  {
    $obj = [];

    $priceGroup = [];
    $this->q->priceGroup = 'yes';
    $itemContractor = $this->_mergerContractor();
    $this->q->priceGroup = 'no';

    foreach ($itemContractor->item as $item) {
      if ($item->PriceGroup === 'Original') {
        continue;
      }
      $priceGroup[] = $item->PriceGroup;
      $this->q->priceGroupName = $item->PriceGroup;
      $obj['priceGroupObj'][$item->PriceGroup] = $this->_mergerContractor();
    }
    $obj['priceGroupTitle'] = $priceGroup;
    return $obj;
  }

  private function _getApiResult($source)
  {
    $target = new \stdClass();
    $target->api = '';
    $target->url = '';
    $target->markup = '';
    $target->isAdmin = false;
    $query = (object)array_merge((array)$target, (array)$source);

    $class = 'App\Http\Controllers\\' . $query->api;
    $key = clone $this->q;
    unset($key->sortField);
    unset($key->group);
    unset($key->limit);
    unset($key->skipLimit);
    unset($key->lazy);
    unset($key->priceGroupName);
    unset($key->brandAndCode);
    unset($key->priceGroup);

    $key = md5(json_encode($key) . json_encode($query));

    if (Cache::has($key)) {
      $this->arResult = Cache::get($key);
    } else {

      // http://yugavtodetal.ru/api/get-api-query.php?q=%7B%22searchCode%22:%228563%22,
      //%22substLevel%22:%22All%22,%22makeLogo%22:%22%23%D0%AB%22,%22priceGroup%22:%22yes%22,%22limit%22:5,%22skipLimit%22:0%7D
      $api = new $class([
        'url' => $query->url,
        'searchCode' => $this->q->searchCode, // TODO Undefined property: stdClass::$searchCode
        'makeLogo' => $this->q->makeLogo,
        'substLevel' => $this->q->substLevel,
        'markup' => $query->markup,
        'userId' => $this->userId,
        'isAdmin' => $query->isAdmin
      ]);
      $this->arResult = $api->getResult();
      Cache::put($key, $this->arResult, 30);
    }
    return $this->arResult;
  }

  private function _getArrContractors($queryContractor = null)
  {
    $source = new \stdClass();
    $source->markup = false;
    $source->isAdmin = false;
    $source = (object)array_merge((array)$source, (array)$queryContractor);
    $source->api = 'ApiBitrix';
    $source->url = '192.168.20.221/api/class-search-code.php';

    if ($source->markup) {
      return $this->_getApiResult($source);
    }
    if ($source->isAdmin) {
      return $this->_getApiResult($source);
    }
    $bitrix = $this->_getApiResult($source);
    $bitrixJson = json_decode($bitrix);

    if ($this->q->bitrix === 'yes') {
      return [$bitrixJson];
    }
    $source->api = 'ApiEmex';
    $source->url = 'http://ws.emex.ru/EmExService.asmx?wsdl';
    $source->makeLogo = $this->q->makeLogo;
    $source->substLevel = $this->q->substLevel;
    $emex = $this->_getApiResult($source);
    $emexJson = json_decode($emex);

    return [$bitrixJson, $emexJson];
  }

  private function _mergerContractor($keyLimit = 20)
  {
    $contractors = [];
    $queryContractor = new \stdClass();
    $queryContractor->isAdmin = true;
    $isAdmin = $this->_getArrContractors($queryContractor);
    $queryContractor->isAdmin = false;
    $queryContractor->markup = true;
    $markup = $this->_getArrContractors($queryContractor);
    $queryContractor->markup = false;
    $arrContracts = $this->_getArrContractors();
    $totalObj = new \stdClass();
    $totalItem = new \stdClass();
    $fullObj = new \stdClass();
    $totalItem->countBitix = 0;
    $totalItem->countApi = 0;
    $totalItem->minDays = 0;
    $totalItem->minPriceContractor = 0;
    $totalItem->minPriceOur = 0;
    $arrUniqueBrandAndCode = [];
    $keyLimit = empty($this->q->limit) ? $keyLimit : $this->q->limit;

    foreach ($arrContracts as $contract) {
      if ($contract === null) {
        continue;
      }

      foreach ($contract as $key => $item) {
        $item = new ConvertContracts($item, $markup);
        $contractorItem = $item->getResult();
        if ($this->_getSkipped($contractorItem)) {
          continue;
        }
        $totalItem = $this->_getSeparationTotal($contractorItem, $totalItem);
        if ($this->_isSkippedNotUnique($contractorItem, $arrUniqueBrandAndCode)) {
          continue;
        }
        if ($this->_isSkippedPriceGroup($contractorItem, $arrUniqueBrandAndCode)) {
          continue;
        }
        $contractors[] = $contractorItem;
      }
    }
    $this->_sortBy($contractors);
    $contractors = $this->_limitRows($contractors, $keyLimit);
    $totalObj->minDays = $totalItem->minDays;
    $totalObj->minPriceOur = $totalItem->minPriceOur;
    $totalObj->minPriceContractor = $totalItem->minPriceContractor;
    $totalObj->countBitix = $totalItem->countBitix;
    $totalObj->countApi = $totalItem->countApi;
    $totalObj->countGroupUnique = count($arrUniqueBrandAndCode);
    $totalObj->isAdmin = $isAdmin;
    $fullObj->item = $contractors;
    $fullObj->total = $totalObj;
    return $fullObj;
  }
}