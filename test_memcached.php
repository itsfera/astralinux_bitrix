<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$cache = new CPHPCache();
if ($cache->InitCache(3600, '12356356gt' , '/' )) {
    echo "cache";
    $res = $cache->GetVars();
    $arResult = $res['arResult'];
} elseif ($cache->StartDataCache()) {
    echo "no cache, reload the page - you should see cached";
    $arResult = array(1,2,3,4,5);
    $cache->EndDataCache(array("arResult" => $arResult));
}
