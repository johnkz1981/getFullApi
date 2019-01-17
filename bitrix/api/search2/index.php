<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Артикул");
?><?$APPLICATION->IncludeComponent(
	"api:emex",
	"",
	Array(
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO",
		"HLBlok" => "5",
		"IBlok" => "150",
		"Login" => "1217282",
		"Password" => "125asdf",
		"deliveryRegionType" => "PRI",
		"substFilter" => "None",
		"substLevel" => "All"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>