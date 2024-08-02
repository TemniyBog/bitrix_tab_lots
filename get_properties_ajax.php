<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require 'properties.php';

CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    return;
}

try {
    $deal = $_POST['PARAMS']['DEAL_ID'];

    $arr_properties = get_properties();

    $types = $arr_properties['type'];
    $values = $arr_properties['value'];
    $elems = get_elems($deal);
    $arr = ['types' => $types, 'values' => $values, 'elems' => $elems];

    echo \Bitrix\Main\Web\Json::encode($arr);
} catch(\Throwable $e) {
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'Error:', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', $e->getMessage(), FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
}


