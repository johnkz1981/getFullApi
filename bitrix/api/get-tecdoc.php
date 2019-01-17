<?php

include_once('tecdoc.class.php');
$q = json_decode($_GET['q']);

$q = array_merge(['vendorCode' => '', 'manufacturer' => ''], (array)($q));
$vendorCode = $q['vendorCode'];
$brand = $q['manufacturer'];
$dataArr = new stdClass();

$dataArr->isArticles = Tecdoc::isArticles($brand, $vendorCode);
if ($q['isArticles']) {
  echo json_encode($dataArr);
  exit;
}
$idSupplier = Tecdoc::getSupplierId($brand);
$dataArr->vendorCode = Tecdoc::getArticle($vendorCode);
$imgArr = Tecdoc::getArtFiles($dataArr->vendorCode, $idSupplier);

$newImgArr = [];

foreach ($imgArr as &$img) {

  if (file_exists("../upload/tmp2/$img->PictureName")) {
    $newImgArr[] = $img;
  }
}

if(count($imgArr) === 0 && count($newImgArr) === 0) {
  $img = new stdClass();
  $img->Description = 'Картинка';
  $img->PictureName = '78_nophoto.jpg';
  $newImgArr[] = $img;
}

$dataArr->img = $newImgArr;
$dataArr->attributes = Tecdoc::getArtAttributes($dataArr->vendorCode, $idSupplier);
$dataArr->idSupplier = $idSupplier;

$makeArr = [];
$makeArrUnique = [];
$modelArr = [];
$modelArrUnique = [];
$vehicles = Tecdoc::getArtVehicles($dataArr->vendorCode, $idSupplier);

foreach ($vehicles['PassengerCar'] as $item) {

  if (!in_array($item[0]->make, $makeArrUnique)) {

    $makeArrUnique[] = $item[0]->make;
  }

  if (!in_array($item[0]->model, $modelArrUnique)) {

    $makeArr[$item[0]->make][] = ['id' => $item[0]->model, 'name' => $item[0]->model];
    $modelArrUnique[] = $item[0]->model;
  }
  $modelArr[$item[0]->model][] = $item[0];
}
$objTree = [];

foreach ($makeArrUnique as $item) {
  $objTree[] = ['id' => $item, 'name' => $item, 'children' => $makeArr[$item]];
}
$dataArr->vehicles['modelArr'] = $modelArr;
$dataArr->vehicles['objTree'] = $objTree;
echo json_encode($dataArr);