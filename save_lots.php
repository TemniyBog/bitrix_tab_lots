<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require 'check_permissions.php';
require 'change_total_summ.php';
CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');

if (!$_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

try {
    $constants = parse_ini_file(__DIR__ . '/.settings.ini');

    $data = $_POST['data'];

    $deal_id = $data['deal_id'];
    unset($data['deal_id']);

    if ($deal_id == 0) {
        return;
    }

    $return_arr = [];
    $el = new CIBlockElement;

    foreach ($data as $elem_id => $arr_values) {

        // привязка к сделке
        $arr_values[$constants['PROPERTY_PRIVYAZKA_K_SDELKE_ID']] = strval($deal_id);
        // активность = да
        $arr_values[$constants['PROPERTY_AKTIVNOST_ID']] = $constants['PROPERTY_ACTIVE_YES'];

        // общая сумма
        if ($arr_values[
            $constants['PROPERTY_TSENA_ZA_EDINITSU_ID']] &&
            is_numeric($arr_values[$constants['PROPERTY_TSENA_ZA_EDINITSU_ID']]) &&
            $arr_values[$constants['PROPERTY_KOLICHESTVO_ID']] &&
            is_numeric($arr_values[$constants['PROPERTY_KOLICHESTVO_ID']])
        ) {
            $arr_values[$constants['PROPERTY_SUMMA_ID']] = strval($arr_values[$constants['PROPERTY_TSENA_ZA_EDINITSU_ID']] * intval($arr_values[$constants['PROPERTY_KOLICHESTVO_ID']]));
        }


        // если пользователь не тендермэн, удаляем из массива статус
        if (check_permissions() != 'tenderman') {
            unset($arr_values[$constants['PROPERTY_STATUS_ID']]);
        }

        $element = CIBlockElement::GetList(
            arFilter: [
                'IBLOCK_ID' => $constants['LOTS_IBLOCK_ID'],
                'ID' => $elem_id,
            ],
            arSelectFields: [
                'ID',
                'IBLOCK_ID',
            ]
        )->Fetch();
        if ($element) {
            CIBlockElement::SetPropertyValuesEx($elem_id, $constants['LOTS_IBLOCK_ID'], $arr_values);
        } else {
            $arLoadProductArray = array(
                "IBLOCK_ID" => $constants['LOTS_IBLOCK_ID'],
                "PROPERTY_VALUES" => $arr_values,
                "NAME" => 'Element_' . $arr_values[$constants['PROPERTY_NOMER_ID']],
            );
            if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
                $return_arr[strval($elem_id)] = strval($PRODUCT_ID);
            }
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

echo json_encode($return_arr);