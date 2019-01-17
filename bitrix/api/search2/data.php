<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// подключаем модуль kod.emex
if (CModule::IncludeModule("kod.emex")) {
    if ($_GET['methods']) {
        $emexController = new CEmexController2($USER);

        switch ($_GET['methods']) {
            case 'PrimaryTable':
                echo $emexController->getPrimaryTable();
                break;
            case 'SlaveTable':
                $emexController->getSlaveTable();
                break;
            case 'SlaveTable2':
                $emexController->getSlaveTable2();
                break;
            case 'PriceGroup':
                $emexController->getPriceGroup();
                break;
            case 'FullData':
                $emexController->getFullData();
                break;

        }

    }
    if ($_GET['art']) {
        $bitrixController = new CBitrixController(150, 5, $_GET['art'], $USER);
        echo $bitrixController->getData(true);

    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $_POST = json_decode(file_get_contents('php://input'), true);
        $class = new CArtikulRepo();
        $class->setBasket($USER);
    }

    if ($_GET['articul'] && $_GET['brend']) {
        $articul = $_GET['articul'];
        $brend = $_GET['brend'];
        $make = $_GET['make'];
        $model = $_GET['model'];
        $idSupplires = Tecdoc::getSupplierId($brend);
        $isArticles = Tecdoc::isArticles($brend, $articul);
        $articul = Tecdoc::getArticle($articul);
        $img = Tecdoc::getArtFiles($articul, $idSupplires);
        $Attributes = Tecdoc::getArtAttributes($articul, $idSupplires);
        $data = Tecdoc::getArtVehicles($articul, $idSupplires);
        $collection = collect($data);
        if ($make) {
            $collection = $collection->flatten()->where('make', $make)->unique('model')->sortBy('model');
            $multiplied = $collection->map(function ($item, $key) {
                return $item->model;
            });
        } elseif ($model) {
            $collection = $collection->flatten()->where('model', $model)->unique('constructioninterval')->sortBy('constructioninterval');
            /*$multiplied = $collection->map(function ($item, $key) {
                return $item->model;
            });*/
            $multiplied = $collection;
        } else {
            $collection = $collection->flatten()->unique('make')->sortBy('make');
            $multiplied = $collection->map(function ($item, $key) {
                return $item->make;
            });
        }
        $dataExport = collect($dataExport);
        $dataExport->push(['vehicle' => $multiplied->flatten()]);
        $dataExport->push(['img' => $img[0]->PictureName]);
        $dataExport->push(['attributes' => $Attributes]);
        $dataExport->push(['isArticles' => $isArticles]);
        echo $dataExport->toJson();

    }
}
?>



