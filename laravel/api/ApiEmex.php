<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiEmex extends BaseApi
{
  private $source = null;
  private $login = '';
  private $password = '';

  public function __construct($source)
  {
    $this->login = '1217282';
    $this->password = "125asdf";

    parent::__construct();

    $this->source = (object)array_merge((array)$this->target, (array)$source);
  }

  public function getResult()
  {
    try {
      $client = new \SoapClient($this->source->url);
      $params = [
        "login" => $this->login,
        "password" => $this->password,
        "detailNum" => $this->source->searchCode,
        "makeLogo" => $this->source->makeLogo,
        "substLevel" => $this->source->substLevel,
        "substFilter" => "None",
        "deliveryRegionType" => "PRI",
        "maxOneDetailOffersCount" => null,
      ];
      $result = $client->FindDetailAdv4($params);
      $resulJson = json_encode($result->FindDetailAdv4Result->Details);
      
      if ($resulJson === '{}' || $result->FindDetailAdv4Result->Details->SoapDetailItem instanceof \stdClass) {
        return $this->arResult = json_encode($result->FindDetailAdv4Result->Details);
      } else {
        return $this->arResult = json_encode($result->FindDetailAdv4Result->Details->SoapDetailItem);
      }
    } catch (SoapFault $exception) {

    }
  }
}
