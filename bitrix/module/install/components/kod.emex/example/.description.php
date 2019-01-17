<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("KOD_EXAMPLE_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("KOD_EXAMPLE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/regions.gif",
	"SORT" => 500,
	"PATH" => array(
		"ID" => "kod_emex_components",
		"SORT" => 500,
		"NAME" => GetMessage("KOD_EXAMPLE_COMPONENTS_FOLDER_NAME"),
	),
);

?>