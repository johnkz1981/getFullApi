<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class ConvertContracts extends Controller
{
  private $item;

  public function __construct($item, $markup)
  {
    $this->item = $item;
    $this->markup = $markup;
  }

  private function _convert()
  {
    if ($this->item instanceof \stdClass) {

      $this->item->name = $this->item->name ?? $this->item->DetailNameRus;
      $this->item->price = $this->_getPrice();
      $this->item->vendorCode = $this->item->vendorCode ?? $this->item->DetailNum;
      $this->item->deliveryTime = $this->_getDeliveryTime();
      $this->item->contractor = $this->_getName();
      $this->item->manufacturer = $this->item->manufacturer ?? $this->item->MakeName;
      $this->item->quantity = $this->item->quantity ?? $this->item->Quantity;
      $this->item->brandAndCode = strtoupper($this->item->manufacturer . $this->item->vendorCode);
      $this->item->MakeLogo = $this->item->MakeLogo ?? '';
      $this->item->PriceGroup = $this->item->PriceGroup ?? 'Original';
      $this->item->color = $this->_getColor();
      $this->item->markup = $this->markup;
      $this->_getId();
    }
  }

  private function _getName()
  {
    if (isset($this->item->contractor)) {
      $this->item->isContractor = 1;
      $this->item->isBitrix = 1;
      return $this->item->contractor;
    }
    if (isset($this->item->xmlId)) {
      $this->item->isContractor = 0;
      $this->item->isBitrix = 1;
      return 'Югавтодеталь';
    } else {
      $this->item->isContractor = 1;
      $this->item->isBitrix = 0;
      $this->item->IDENTIFIKATOR = 4;
      $this->item->POSTAVCHIK = 'ООО "АВЭКС"';
      return 'emex';
    }
  }

  private function _getDeliveryTime()
  {
    if (isset($this->item->ADDays)) {
      return $this->item->ADDays;
    }
    if (isset($this->item->deliveryTime)) {
      return (int)$this->item->deliveryTime;
    }
    return null;
  }

  private function _getPrice()
  {
    if (isset($this->item->price)) {
      return (int)$this->item->price;
    } else {
      $this->item->priceOriginal = (int)$this->item->ResultPrice;
      return round((int)$this->item->ResultPrice * (int)$this->markup / 100 + (int)$this->item->ResultPrice, 2);
    }
  }

  private function _getColor()
  {
    if (isset($this->item->DDPercent)) {

      if ($this->item->DDPercent < 25) {
        return 'error';
      } elseif ($this->item->DDPercent < 75) {
        return 'warning';
      } else {
        return 'success';
      }
    } else {
      return '';
    }
  }

  private function _getId()
  {
    $this->item->id = $this->item->id ?? $this->item->GroupId .
      $this->item->MakeLogo . $this->item->PriceLogo . $this->item->DetailNum;
    Cache::put($this->item->id, $this->item, 30);
    return $this->item->id;
  }

  public function getResult()
  {
    $this->_convert();
    return $this->item;
  }
}
