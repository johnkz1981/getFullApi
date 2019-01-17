<?php
Bitrix\Main\Loader::registerAutoloadClasses(
    "kod.emex",
    array(
        "kod\\Emex\\Test" => "lib/test.php",
        "CEmexController" => "class/emexData.php",
        "CEmexController2" => "class/emexData2.php",
        "CBitrixController" => "class/bitrixData.php",
        "CArtikulRepo" => "class/CArtikulRepo.php",
        "Tecdoc" => "class/tecdoc.class.php",
    )
);