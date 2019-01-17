<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Arrilot\BitrixModels\Models\ElementModel;

class CEmexController2
{
    private $fullData;
    private $fullDataFilter;
    private $detailNum;
    private $makeLogo;
    private $substLevel;
    private $percent;
    private $error;
    private $USER;

    function __construct($USER, $test = false, $login = "1217282", $url = "http://ws.emex.ru/EmExService.asmx?wsdl")
    {
        $this->detailNum = $_GET['detailNum'];
        $this->makeLogo = $_GET['makeLogo'];
        $this->substLevel = $_GET['substLevel'];

        $cache = new CPHPCache();
        if ($test == false && $cache->InitCache(3600, 'apiemexTest2' . $this->detailNum . $this->makeLogo . $this->substLevel, '/')) {
            $res = $cache->GetVars();
            $this->fullDataFilter = $res['arResult'];
        } elseif ($cache->StartDataCache()) {
            try {
                $client = new SoapClient($url);
                $params = array();
                $params["login"] = $login;
                $params["password"] = "125asdf";
                $params["detailNum"] = $this->detailNum;
                $params["makeLogo"] = $this->makeLogo;
                $params["substLevel"] = $this->substLevel;
                $params["substFilter"] = "None";
                $params["deliveryRegionType"] = "PRI";
                $params["maxOneDetailOffersCount"] = null;

                $result = $client->FindDetailAdv4($params);
                $arResult = $result->FindDetailAdv4Result->Details->SoapDetailItem;
                //$cache->EndDataCache(array("arResult" => $arResult));

            } catch (SoapFault $exception) {
                $this->error = mb_substr($exception->getMessage(), 0, 49, 'UTF-8');
            }
            if (!is_array($arResult)) $arResult1[0] = $arResult;
            else
                $arResult1 = $arResult;
            $coll = collect($arResult1);
            $coll = $coll->sortBy('MakeName');
            $this->USER = $USER;
            $this->getPercent();
            $this->fullData = $coll;
            $this->fullDataFilter = $coll->where('ResultPrice', '>', 0)->where('Quantity', '>', 0);
            $bitrixController = new CBitrixController(150, 5, $this->detailNum);//$this->detailNum);
            $data = $bitrixController->getData(true);
            $data = json_decode($data);
            if ($data->show) {
                foreach ($data->result as $item) {
                    $obj = new stdClass();
                    $obj->Id = $item->ID;
                    $obj->PriceGroup = "Original";
                    $obj->DetailNum = $item->ARTICLE;
                    $obj->MakeName = $item->PROIZVODITEL2;
                    $obj->DetailNameRus = $item->NAME;
                    $obj->ResultPrice = $item->PRICE;
                    $obj->Quantity = $item->QUANTITY;
                    $obj->ADDays = (int)$item->SROK_POSTAVKI;
                    $obj->DDPercent = 80;
                    $obj->excel = 1;
                    $this->fullDataFilter->push($obj);
                }

            }
            $cache->EndDataCache(array("arResult" => $this->fullDataFilter));
        }
    }


    public function getPrimaryTable()
    {
        $arr = array();
        $collection = collect($arr);
        $Price = '';

        $unique = $this->fullDataFilter->unique(function ($item) {
            return $item->MakeName . $item->detailNum;
        });

        foreach ($unique as $item) {
            $filter = $this->fullDataFilter->where('MakeName', $item->MakeName)->where('DetailNum', $item->DetailNum);
            //$filter=$this->transformPrise($filter->values());
            $collection->push([
                'DetailNum' => $item->DetailNum,//$filter->where('DetailNum', $this->detailNum)->count()==0?$this->detailNum:$item->DetailNum,
                'MakeName' => $item->MakeName,
                'DetailNameRus' => $item->DetailNameRus,
                'MakeLogo' => $item->MakeLogo,
                'ResultPrice' => $item->excel ? $item->ResultPrice : $item->ResultPrice * $this->percent,
                'OriginalPrice' => 'от ' . round($filter->min('ResultPrice') * $this->percent, 2) . ' ₽',
                'ADDays' => 'от ' . $filter->min('ADDays') . ' д.'
                /*'OriginalPrice'=>!is_null($filter->where('PriceGroup','Original')->min('ResultPrice'))? 'от '.$filter->
                    where('PriceGroup','Original')->min('ResultPrice').' ₽':'',
                'AnalogPrice'=>!is_null($filter->where('PriceGroup','<>','Original')->min('ResultPrice'))? 'от '.$filter->
                    where('PriceGroup','<>','Original')->min('ResultPrice').' ₽':'',
                'ADDays'=>'от '.$this->fullDataFilter->where('DetailNameRus', $item->DetailNameRus)->min('ADDays')*/]);
        }

        return $collection->toJson();
    }

    public function getSlaveTable($localSortBy = 'ADDays', $localSortDesc = false)
    {
        $localSortBy = $_GET["localSortBy"] ?? $localSortBy;
        $localSortDesc = $_GET["localSortDesc"] ?? $localSortDesc;
        $filter = $this->fullDataFilter->where('PriceGroup', $_GET['PriceGroup']);
        $result["count"] = $filter->count();
        if ($_GET['take'] == 'false') $filter = $filter->take(2);
        $Prise = $this->transformPrise($filter->values());
        if ($localSortDesc == "true")
            $result["mass"] = $Prise->sortByDesc("$localSortBy")->flatten(1);
        else
            $result["mass"] = $Prise->sortBy("$localSortBy")->flatten(1);
        //echo '<pre>';
        echo json_encode($result);
    }

    public function getSlaveTable2()
    {
        echo json_encode($this->fullDataFilter);
    }

    public function transformPrise($SlaveTableResult)
    {
        $transform = $SlaveTableResult->map(function ($item, $key) {
            if ($item->excel == 1) return $item;
            $item->ResultPrice = round($item->ResultPrice * $this->percent, 2);
            return $item;
        });
        return $transform;
    }

    public function getPriceGroup()
    {
        $coll = $this->fullDataFilter->unique('PriceGroup')->values();
        echo $coll;
    }

    public function getFullData()
    {
        $arResult = array();
        if ($_GET['where'] == null)
            $arResult = $this->fullData->values();
        else
            $arResult = $this->fullData->where('DetailNameRus', $_GET['where'])->values();
        //echo $arResult;
        echo '<!DOCTYPE HTML>
        <html>
         <head>
          <meta charset="utf-8">
          <title>Full Table</title>
         </head>
         <body>
          <table border="1">
           <caption>' . $arResult->count() . '</caption>
           <tr>
            <th>GroupId</th>
            <th>PriceGroup</th>
            <th>MakeLogo</th>
            <th>MakeName</th>
            <th>DetailNum</th>
            <th>DetailNameRus</th>
            <th>PriceLogo</th>
            <th>DestinationLogo</th>
            <th>PriceCountry</th>
            <th>LotQuantity</th>
            <th>Quantity</th>
            <th>DDPercent</th>
            <th>ADDays</th>
            <th>DeliverTimeGuaranteed</th>
            <th>ResultPrice</th>
           </tr>';
        foreach ($arResult as $item):
            echo '<tr><td>' . $item->GroupId . '</td><td>' . $item->PriceGroup . '</td>';
            echo '<td>' . $item->MakeLogo . '</td><td>' . $item->MakeName . '</td>';
            echo '<td>' . $item->DetailNum . '</td><td>' . $item->DetailNameRus . '</td>';
            echo '<td>' . $item->PriceLogo . '</td><td>' . $item->DestinationLogo . '</td>';
            echo '<td>' . $item->PriceCountry . '</td><td>' . $item->LotQuantity . '</td>';
            echo '<td>' . $item->Quantity . '</td><td>' . $item->DDPercent . '</td>';
            echo '<td>' . $item->ADDays . '</td><td>' . $item->DeliverTimeGuaranteed . '</td><td>' . $item->ResultPrice . '</td></tr>';

        endforeach;
        echo '
          </table>
         </body>
        </html>';

    }

    public function getError()
    {
        return $this->error;
    }

    public function getPercent()
    {
        $percent = $this->percent = CArtikulRepo::Percent($this->USER);
        $this->percent = $percent / 100 + 1;
        return $this->percent;
    }

    public function getFullDataTest()
    {
        return $this->fullData;
    }
}

?>