<?php

namespace App\Http\Controllers;

abstract class BaseApi
{
  //private $target = null;
  public function __construct()
  {
    $this->target = new \stdClass();
    $this->target->url = '';
    $this->target->q = '';
  }
}
