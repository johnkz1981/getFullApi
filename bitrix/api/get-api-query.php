<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

$url = 'bitrix-api.test/get-api';
$q = $_GET["q"];
$userId = $USER->GetID() ?? 0;
$path = "$url?q=$q&userId=$userId";

$ch = curl_init($path);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);

if(json_decode($q)->basket === 'yes'){
  $count = json_decode($q)->count;
  $id = $output;
  include 'set_basket.php';
}else{
  echo $output;
}



