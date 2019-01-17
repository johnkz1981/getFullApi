<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

trait TBasket
{
  private function _basket()
  {

    if ($this->q->basket === 'yes' && Cache::has($this->q->id)) {

      $id = json_encode(Cache::get($this->q->id));

      echo $id;
      exit;
    }
  }
}