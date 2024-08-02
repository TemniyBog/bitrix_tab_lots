<?php

require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require __DIR__ . '/properties.php';
require __DIR__ . '/check_permissions.php';

use Bitrix\Crm\Service;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

Loader::includeModule('crm');
Loader::includeModule('ui');
Loader::includeModule('main');
Loader::includeModule('socialnetwork');
CUtil::InitJSCore(array('ajax', 'popup'));

$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($documentRoot, '', __DIR__ . '/custom_script.js');
Asset::getInstance()->addJs($relativePath);

?>

<!DOCTYPE html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://api.bitrix24.com/api/v1/"></script>

    <style>
        @media print {
            body.crm-iframe-popup.crm-detail-page.template-bitrix24.crm-iframe-popup-no-scroll.task-iframe-popup.grid-mode.no-paddings.no-background.top-menu-mode {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        select,
        input[type="text"],
        input[type="number"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            max-width: 200px;
            margin-bottom: 15px;
        }

        select {
            cursor: pointer;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            box-sizing: border-box;
            font-size: 14px;
        }

        #add_button {
            box-sizing: border-box;
            margin: 0;
            outline: 0;
            height: var(--ui-btn-height);
            border: var(--ui-btn-border);
            border-color: var(--ui-btn-border-color);
            background-color: var(--ui-btn-background);
            box-shadow: var(--ui-btn-box-shadow);
            text-shadow: var(--ui-btn-text-shadow);
            cursor: pointer;
            transition: 160ms linear background-color, 160ms linear color, 160ms linear opacity, 160ms linear box-shadow, 160ms linear border-color;
            position: relative;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: var(--ui-btn-padding);
            color: var(--ui-btn-color);
            vertical-align: middle;
            text-align: center;
            -webkit-text-decoration: var(--ui-text-transform-none);
            text-decoration: var(--ui-text-transform-none);
            text-transform: var(--ui-text-transform-uppercase);
            white-space: nowrap;
            font: var(--ui-btn-font-size) / calc(var(--ui-btn-height) - 2px) var(--ui-font-family-secondary, var(--ui-font-family-open-sans));
            font-weight: var(--ui-font-weight-bold);
            -webkit-user-select: none;
            user-select: none;
            line-height: calc(var(--ui-btn-height) - 2px);
            vertical-align: middle;
            --ui-btn-background: #3bc8f5;
            --ui-btn-background-hover: #3eddff;
            --ui-btn-background-active: #12b1e3;
            --ui-btn-border-color: #3bc8f5;
            --ui-btn-border-color-hover: #3eddff;
            --ui-btn-border-color-active: #12b1e3;
            --ui-btn-color: var(--ui-color-on-primary);
            --ui-btn-color-hover: var(--ui-color-on-primary);
            --ui-btn-color-active: var(--ui-color-on-primary);
            color: #fff;
            height: 33px;
            border-radius: 5px; /* Закругленные края */
        }

        #add_button:hover {
            background-color: var(--ui-btn-background-hover);
            border-color: var(--ui-btn-border-color-hover);
            color: #fff; /* Сохраняем белый цвет текста */
        }

        #add_button:active {
            background-color: var(--ui-btn-background-active);
            border-color: var(--ui-btn-border-color-active);
            color: #fff; /* Сохраняем белый цвет текста */
        }
    </style>

    <?php $APPLICATION->ShowHead() ?>

</head>

    <?php

    $user_role = check_permissions();
    $deal_id = $_POST['PARAMS']['DEAL_ID'];
    $constants = parse_ini_file(__DIR__ . '/.settings.ini');

    // toolbar
    $APPLICATION->IncludeComponent(
        "bitrix:main.interface.toolbar",
        "",
        array(
            "BUTTONS" => array(
                array(
                    "TEXT" => "Добавить лот",
                    "ICON" => '',
                    "LINK" => "#",
                ),
            ),
        ),
        false
    );

    $arr_properties = get_properties();

    // вводные
    $id_and_name_property = $arr_properties['name'];
    $id_and_type_property = $arr_properties['type'];
    $id_and_values_list = $arr_properties['value'];
    $ib_elems = get_elems($deal_id);


    // дополнительный массив для записи элементов типа Список
    $additional_arr_lists = [];
    foreach ($id_and_type_property as $type_id => $type) {
        if ($type == 'list') {
            $additional_arr_lists[] = $type_id;
            $id_and_value_of_list = $id_and_values_list[$type_id];

            if ($id_and_value_of_list) {
                $arr = [];
                foreach ($id_and_value_of_list as $list_elem_id => $list_value) {
                    $arr[$list_elem_id] = $list_value;
                }
                // устанавливаем в список (не установлено)
                $arr[] = '(не установлено)';
                $columns[] = [
                    'id' => strval($type_id),
                    'name' => $id_and_name_property[$type_id],
                    "sort" => "ID",
                    'MULTIPLE' => 'N',
                    'default' => true,
                    'type' => 'list',
                    "editable" => ["items" => $arr],
                ];
            }
        } else {
            $columns[] = [
                'id' => strval($type_id),
                'name' => $id_and_name_property[$type_id],
                'PLACEHOLDER' => $type,
                'sort' => 'ID',
                'default' => true,
                'type' => 'text',
                'editable' => array("size" => 20, "maxlength" => 255),
            ];
        }
    }



    /**
     * Toolbar buttons
     **/
    foreach ($ib_elems as $elem_id => $elem_properties) {

        $columns_values = [];

        foreach ($elem_properties as $property_id => $property_value) {
            if (in_array($property_id, $additional_arr_lists)) {
                $property_value = $id_and_values_list[$property_id][$property_value];
                if ($property_value == null) {
                    $property_value = '(не установлено)';
                }
            }
            $columns_values[$property_id] = $property_value;
        }

        $rows[] = [
            'id' => $columns_values['IBLOCK_ELEMENT_ID'],
            'editable' => 'Y',
            'data' => ['deal_id' => $deal_id],
            'columns' => $columns_values,
        ];
    }


    $controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
    $snippet = new Bitrix\Main\Grid\Panel\Snippet();
    $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();

    // кнопка редактировать
    $controlPanel['GROUPS'][0]['ITEMS'][] = [
        'TYPE' => 'BUTTON',
        'ID' => 'custom_grid_edit_button',
        'NAME' => '',
        'CLASS' => 'icon edit',
        'TEXT' => 'Редактировать',
        'TITLE' => 'Редактировать отмеченные элементы',
        'ONCHANGE' => [
            [
                'ACTION' => 'CALLBACK',
                'DATA' => [
                    ['JS' => "BX.GridCustom.editCustom()"],
                ],
            ],
            [
                'ACTION' => 'HIDE_ALL_EXPECT',
                'DATA' => [
                    ['ID' => 'custom_grid_save_button'],
                    ['ID' => 'custom_grid_cancel_button'],
                ],
            ],
        ],
    ];

    // Кнопка удалить
    $controlPanel['GROUPS'][0]['ITEMS'][] = [
        'TYPE' => 'BUTTON',
        'ID' => 'custom_grid_remove_button',
        'NAME' => '',
        'CLASS' => 'icon remove',
        'TEXT' => 'Удалить',
        'TITLE' => 'Удалить отмеченные элементы',
        'ONCHANGE' => [
            [
                'ACTION' => 'CALLBACK',
                'DATA' => [
                    ['JS' => "BX.GridCustom.removeCustom()"],
                ],
            ],
        ],
    ];

    // подключаем компонент
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        [
            'GRID_ID' => 'LOTS_GRID',
            'COLUMNS' => $columns,
            'ROWS' => $rows,
            'AJAX_MODE' => 'Y',
            'SHOW_ROW_CHECKBOXES' => true,
            'NAV_OBJECT' => null,
            'AJAX_OPTION_JUMP' => 'N',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_OPTION_STYLE' => 'Y',
            'SHOW_ROW_ACTIONS_MENU' => true,
            'SHOW_GRID_SETTINGS_MENU' => false,
            'SHOW_NAVIGATION_PANEL' => true,
            'ACTION_PANEL' => $controlPanel,
            'SHOW_SELECT_ALL_RECORDS_CHECKBOX' => true,
            'ALLOW_SORT' => false,
            'SHOW_CHECK_ALL_CHECKBOXES' => true,
            'SHOW_PAGINATION' => true,
            'SHOW_SELECTED_COUNTER' => false,
            'SHOW_TOTAL_COUNTER' => false,
            'SHOW_PAGESIZE' => false,
            'ALLOW_PIN_HEADER' => true,
            'ALLOW_COLUMNS_RESIZE' => true,
        ]
    );

    ?>

    <script>
        // добавляем айди к столбцу чекбокс
        document.getElementsByClassName('main-grid-cell-head main-grid-cell-static main-grid-cell-checkbox')[0].dataset.name = 0;

        const user_role = <?php echo json_encode($user_role); ?>;
        const status_field_id = <?php echo json_encode($constants['STATUS_FIELD_ID']); ?>;

        console.log('user_role');
        console.log(user_role);

        if (user_role == 'user') {
            // делаем инпут поля Статус - disabled
            window.disabled_field = [status_field_id];
            hide_control_panel();
        } else if (user_role == 'editor') {
            // делаем инпут поля Статус - disabled
            window.disabled_field = [status_field_id];
        }

        // изменение дизайна кнопки
        let add_lot_button = document.getElementsByClassName('bx-context-button')[0];
        add_lot_button.classList.add('ui-btn-custom');
        add_lot_button.id = 'add_button';
        add_lot_button.classList.remove('bx-context-button');

        window.onload = function () {
            let save_button = document.querySelector("button.ui-btn-success");
            // проверяем, новая ли сделка
            if (BX.Crm.Deal.DealComponent.getDealDetailManager()._entityId == '0') {
                // сначала удаляем событие, потом вызываем событие, чтобы в итоге было только одно событие
                save_button.removeEventListener('click', button_push);

                save_button.addEventListener('click', button_push);
            }

            // button - Добавить лот
            document.getElementById('add_button').addEventListener('click', function () {
                event.preventDefault();
                add_empty_row();
            });
        };

        // выводим общее количество лотов. Всего:
        display_total_quantity();

        function button_push() {
            console.log('Button push!');
            // сначала удаляем событие, потом вызываем событие, чтобы в итоге было только одно событие
            BX.removeCustomEvent('onCrmEntityCreate', deal_create);

            BX.addCustomEvent('onCrmEntityCreate', deal_create);
        }

        function deal_create(event) {
            BX.GridCustom.saveCustom(event.entityId, false);
        }

    </script>


<?php
require_once ($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_after.php");
?>