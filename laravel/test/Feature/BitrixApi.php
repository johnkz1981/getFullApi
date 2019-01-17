<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BitrixApi extends TestCase
{
  /**
   * Вывод всех записей
   * @param {string} $q. - Артикул для запроса
   * @param {string} $substLevel. - Вывод только искомого артикула или аналогов и emex {OriginalOnly|All}
   * @param {string} $array. - Вывод данных в {array|json}
   * @return void
   * example FILTRON FT ROLF Ъ0 op595 &makeLogo=$makeLogo&brandAndCode=ROLFOP595
   * &substLevel=All&array=yes&limit=10&priceGroup=no&priceGroupName=ReplacementOriginal CP
   */
  public function offtestGetBitrix()
  {
    $q = new \stdClass();
    $q->searchCode = 'c030';
    $q->bitrix = 'yes';
    $q->array = 'yes';
    $q->limit = 5;
    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function offtestGetGroupApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'c030';
    $q->group = 'yes';
    $q->substLevel = 'OriginalOnly';
    $q->array = 'yes';
    $q->limit = 5;
    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function OfftestGetOriginalDetailApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'c030';
    $q->substLevel = 'OriginalOnly';
    $q->makeLogo = 'CP';
    $q->brandAndCode = 'CHAMPIONC030';
    $q->array = 'yes';
    $q->limit = 5;
    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function OfftestGetPriceGroupApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'op595';
    $q->substLevel = 'All';
    $q->makeLogo = 'FT';
    $q->priceGroup = 'yes';
    $q->array = 'yes';
    $q->limit = 5;
    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function offtestSortOriginalApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'op595';
    $q->substLevel = 'OriginalOnly';
    $q->makeLogo = 'FT';
    $q->brandAndCode = 'FILTRONOP595';
    $q->array = 'yes';
    $q->limit = 5;
    $q->sortField = ['price', 'asc'];
    $q->skipLimit = 144;
    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function offtestSortDetailApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'op525';
    $q->substLevel = 'All';
    $q->makeLogo = 'FT';
    $q->array = 'yes';
    $q->limit = 2;
    $q->sortField = ['price', true];
    $q->priceGroupName = 'ReplacementNonOriginal';

    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  public function offtestAllDetailApi()
  {
    $q = new \stdClass();
    $q->searchCode = 'op525';
    $q->substLevel = 'All';
    $q->makeLogo = 'FT';
    $q->array = 'yes';
    $q->limit = 10;
    $q->skipLimit = 2;

    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }

  /**
   * "searchCode":"op564","substLevel":"All","priceGroup":"ReplacementOriginal","limit":620,"skipLimit":420}
   */

  public function testAllDetailApiLazy()
  {
    $q = new \stdClass();
    $q->searchCode = 'op564';
    $q->substLevel = 'All';
    $q->makeLogo = 'FT';
    $q->array = 'yes';
    $q->limit = 2;
    $q->skipLimit = 40;
    $q->priceGroupName = 'ReplacementOriginal';
    $q->lazy = 'yes';

    $q = json_encode($q);

    $response = $this->get("/get-api?q=$q");
    $response->assertStatus(200);
  }
}