<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');
CModule::IncludeModule('lists');

$constants = parse_ini_file(__DIR__ . '/.settings.ini');

function get_properties()
{
    global $constants;

    // поля, которые мы не выводим
    $fields_not_for_display_ids = [$constants['PROPERTY_AKTIVNOST_ID'], $constants['PROPERTY_PRIVYAZKA_K_SDELKE_ID'], $constants['PROPERTY_SUMMA_ID']];

    $id_and_type_property = [];
    $id_and_name_property = [];
    $id_and_code_property = [];
    $property_values = []; // определяем массив значений свойств
    $properties = CIBlockProperty::GetList(
        arFilter: [
            'IBLOCK_ID' => $constants['LOTS_IBLOCK_ID'],
        ]
    );
    while ($property = $properties->Fetch()) {
        // не выводим поля
        if (!in_array($property['ID'], $fields_not_for_display_ids)) {
            $id_and_code_property[$property['ID']] = $property['CODE'];
            $id_and_name_property[$property['ID']] = $property['NAME'];
            $id_and_type_property[$property['ID']] = $property['USER_TYPE'] ?: $property['PROPERTY_TYPE'];

            // Заполняем значения свойств
            if ($property['PROPERTY_TYPE'] == 'L') {
                $list = CIBlockPropertyEnum::GetList(array("SORT" => "ASC"), array("IBLOCK_ID" => $constants['LOTS_IBLOCK_ID'], "CODE" => $property['CODE']));
                while ($option = $list->Fetch()) {
                    $property_values[$property['ID']][$option['ID']] = $option['VALUE'];
                }
            }
        }
    }

    foreach ($id_and_type_property as $key => $value) {
        if ($value == 'S') {
            $id_and_type_property[$key] = 'string';
        } else if ($value == 'N') {
            $id_and_type_property[$key] = 'integer';
        } else if ($value == 'Money') {
            $id_and_type_property[$key] = 'money';
        } else if ($value == 'L') {
            $id_and_type_property[$key] = 'list';
        } else if ($value == 'ECrm') {
            $id_and_type_property[$key] = 'ecrm';
        }
    }
    $result_arr = [
        'name' => $id_and_name_property,
        'type' => $id_and_type_property,
        'code' => $id_and_code_property,
        'value' => $property_values,
    ];

    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'function get_properties(), ' . '$result_arr:', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', var_export($result_arr, true), FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);

    return $result_arr;
}


// массив: ключ - айди элемента, значение - массив: ключ - айди свойства, значение - значение свойства
// берём элементы ИБ (списка)
function get_elems($deal_id)
{
    global $constants;
    $value_elem = [];
    // берём элементы инфоблока
    $elements = CIBlockElement::GetList(
        arFilter: [
            "IBLOCK_ID" => $constants['LOTS_IBLOCK_ID'],
        ],
        arSelectFields: [
            'ID',
            'IBLOCK_ID',
        ]
    );
    while ($element = $elements->Fetch()) {

        // берём значения свойств элементов инфоблока
        $element_properties = CIBlockElement::GetPropertyValues(
            $constants['LOTS_IBLOCK_ID'],
            arElementFilter: [
                'ID' => $element['ID'],
                // привязка к сделке
                'PROPERTY_' . $constants['PROPERTY_PRIVYAZKA_K_SDELKE_ID'] => $deal_id,
                // активность - да
                'PROPERTY_' . $constants['PROPERTY_AKTIVNOST_ID'] => $constants['PROPERTY_ACTIVE_YES'],
            ]
        );
        while ($property_value = $element_properties->Fetch()) {
            $value_elem[$element['ID']] = [];

            foreach ($property_value as $key => $value) {
                if (str_contains($value, '|KZT')) {
                    $property_value[$key] = substr($value, 0, -4);
                }
                // 1371 - Количество. округляем
                if ($key == $constants['PROPERTY_KOLICHESTVO_ID']) {
                    $property_value[$key] = round($value, 0);
                }
            }
            $value_elem[$element['ID']] = $property_value;
        }
    }

    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'function get_elems(), ' . '$value_elem:', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', var_export($value_elem, true), FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    return $value_elem;
}
