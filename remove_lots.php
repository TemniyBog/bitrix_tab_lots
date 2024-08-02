<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require 'change_total_summ.php';

CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');


if (!$_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

try {
    $constants = parse_ini_file(__DIR__ . '/.settings.ini');

    $data = $_POST['data']['data'];
    $deal_id = $_POST['data']['deal_id'];

    foreach ($data as $elem_id) {
        $element = CIBlockElement::GetList(
            arFilter: [
                'IBLOCK_ID' => $constants['LOTS_IBLOCK_ID'],
                'ID' => $elem_id
            ],
            arSelectFields: [
                'ID',
                'IBLOCK_ID',
                'PROPERTY_' . $constants['PROPERTY_AKTIVNOST_ID']
            ]
        )->Fetch();
        if ($element) {

            // убираем активность, 3397 - нет
            CIBlockElement::SetPropertyValues(
                $elem_id,
                $constants['LOTS_IBLOCK_ID'],
                $constants['PROPERTY_ACTIVE_NO'],
                $constants['PROPERTY_AKTIVNOST_ID']);
        }
    }
} catch(\Throwable $e) {
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'Error:', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', $e->getMessage(), FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
}

change_total_summ($deal_id);