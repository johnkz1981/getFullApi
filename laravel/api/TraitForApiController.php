<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

trait TraitForApiController
{
  private function _getSkipped($contractorItem)
  {
    return !is_object($contractorItem) ||
      $contractorItem->price === 0 ||
      $contractorItem->quantity === 0 ||
      ($contractorItem->isContractor === 1 && $this->q->bitrix === 'yes') ||
      ($contractorItem->isContractor === 0 && !($this->q->bitrix === 'yes')) ||
      ($this->q->brandAndCode !== '' && $contractorItem->brandAndCode !== $this->q->brandAndCode) ||
      ($this->q->priceGroupName && !($this->q->priceGroupName === $contractorItem->PriceGroup));
  }

  private function _getSeparationTotal($contractorItem, $totalItem)
  {
    if ($contractorItem->isContractor === 1) {
      $totalItem->minDays = $this->_getMin($totalItem->minDays, $contractorItem->deliveryTime);
      $totalItem->minPriceContractor = $this->_getMin($totalItem->minPriceContractor, $contractorItem->price);
      $totalItem->countApi++;
    } else {
      $totalItem->minPriceOur = $this->_getMin($totalItem->minPriceOur, $contractorItem->price);
      $totalItem->countBitix++;
    }
    return $totalItem;
  }

  private function _getMin(int $minimal, int $compare): int
  {
    $minimal = $minimal === 0 ? $compare : $minimal;
    return $minimal = ($minimal <=> $compare) === 1 ? $compare : $minimal;
  }

  /**
   * Пропускает не уникальные значения
   * @param $contractorItem {object}
   * @param $arrUnique {Array}
   * @return  {bool}
   */
  private function _isSkippedNotUnique($contractorItem, &$arrUnique): bool
  {
    if ($this->q->bitrix === 'yes' || !($this->q->group === 'yes')) {
      return false;
    }
    if (count($arrUnique) === 0) {
      $arrUnique[] = $contractorItem;
      return false;
    }
    foreach ($arrUnique as &$item) {
      if ($item->brandAndCode === $contractorItem->brandAndCode) {
        $item->price = $this->_getMin($contractorItem->price, $item->price);

        if (strlen($contractorItem->MakeLogo) > 0) {
          $item->MakeLogo = $contractorItem->MakeLogo;
        }
        return true;
      }
    }
    $arrUnique[] = $contractorItem;
    return false;
  }

  /**
   * @param $contractorItem {object}
   * @param $arrUnique {Array}
   * @return  {bool}
   */
  private function _isSkippedPriceGroup($contractorItem, &$arrUnique): bool
  {
    if ($this->q->bitrix === 'yes' || !($this->q->priceGroup === 'yes')) {
      return false;
    }
    if (count($arrUnique) === 0) {
      $arrUnique[] = $contractorItem;
      return false;
    }
    foreach ($arrUnique as &$item) {
      if ($item->PriceGroup === $contractorItem->PriceGroup) {
        return true;
      }
    }
    $arrUnique[] = $contractorItem;
    return false;
  }

  private function _sortBy(&$contractors)
  {
    if (isset($this->q->sortField) && $this->q->sortField[0]) {
      $field = $this->q->sortField[0];
      $direction = $this->q->sortField[1];
      usort($contractors, function ($param1, $param2) use ($field, $direction) {
        if ($direction) {
          return $param2->$field <=> $param1->$field;
        }
        return $param1->$field <=> $param2->$field;
      });
    }
  }

  private function _limitRows($contractors, $keyLimit)
  {
    if ($keyLimit === -1) {
      return $contractors;
    }
    return array_filter($contractors, function ($f1) use ($keyLimit) {
      return $f1 < $keyLimit + $this->q->skipLimit && $f1 >= $this->q->skipLimit;
    }, ARRAY_FILTER_USE_KEY);
  }
}
