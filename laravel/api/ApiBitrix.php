<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiBitrix extends BaseApi
{
  private $source = null;
  public function __construct($source)
  {
    parent::__construct();

    $this->target->markup = '';
    $this->target->userId = '';
    $this->target->isAdmin = false;
    $this->source = (object)array_merge((array)$this->target, (array)$source);
  }

  public function getResult()
  {
    $source = $this->source;
    $url = "$source->url?q=$source->searchCode&markup=$source->markup&userId=$source->userId&isAdmin=$source->isAdmin";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    return curl_exec($ch);
  }
}
