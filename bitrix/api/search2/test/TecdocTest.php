<?php

require_once 'TestBase.php';
use TestBase;

class EmexTest extends TestBase
{

    public function testSupplier(){
        $idSupplires=Tecdoc::getSupplierId('filtron');
        $this->assertEquals($idSupplires,256);
        return $idSupplires;
    }

    public function taestGetArticle(){
        $Article=Tecdoc::getArticle('OP5452');
        $this->assertEquals($Article,'OP545/2');
    }

    public function taestErrorArtcul(){
        $errorArtcul='filtron4444';
        $idSupplires=Tecdoc::getSupplierId($errorArtcul);
        $this->assertNull($idSupplires);
        return $errorArtcul;
    }



    /**
     * @depends testSupplier
     */
    public function teastVehicles($idSupplires){
        $articul='op525';
        $data[] = Tecdoc::getArtVehicles( $articul, $idSupplires );
        $this->assertNotEmpty($data[0]);
        return $articul;
    }

    /**
     * @depends testVehicles
     */
    public function teastIsArticles($articul){

        $data=Tecdoc::isArticles('filtron111',$articul);
        $this->assertFalse($data);
        $data=Tecdoc::isArticles('filtron',$articul);
        $this->assertTrue($data);
    }

    /**
     * @depends testSupplier
     * @depends testVehicles
     * @depends testErrorArtcul
     */
    public function teastEmptyVehicles($idSupplires,$articul,$errorArtcul){
        $data[] = Tecdoc::getArtVehicles( $errorArtcul, $idSupplires );
        $this->assertEmpty($data[0]);
        return $articul;
    }

    /**
     * @depends testSupplier
     * @depends testVehicles
     */
    public function teastFiles($idSupplires,$articul){
        $data[] = Tecdoc::getArtFiles( $articul, $idSupplires );
        $this->assertNotEmpty($data[0]);
        return $articul;
    }

    /**
     * @depends testSupplier
     * @depends testVehicles
     * @depends testErrorArtcul
     */
    public function teastEmptyFiles($idSupplires,$articul,$errorArtcul){
        $data[] = Tecdoc::getArtFiles( $errorArtcul, $idSupplires );
        $this->assertEmpty($data[0]);
        return $articul;
    }

    /**
     * @depends testSupplier
     * @depends testVehicles
     */
    public function teastAttributes($idSupplires,$articul){
        $data[] = Tecdoc::getArtAttributes( $articul, $idSupplires );
        $this->assertNotEmpty($data[0]);
        return $articul;
    }

    /**
     * @depends testSupplier
     * @depends testVehicles
     * @depends testErrorArtcul
     */
    public function teastEmptyAttributes($idSupplires,$articul,$errorArtcul){
        $data[] = Tecdoc::getArtAttributes( $errorArtcul, $idSupplires );
        $this->assertEmpty($data[0]);
        return $articul;
    }



}