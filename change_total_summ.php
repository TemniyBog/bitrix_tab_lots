<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');
CModule::IncludeModule('lists');
CModule::IncludeModule('crm');

$constants = parse_ini_file(__DIR__ . '/.settings.ini');

function change_total_summ($deal_id): void
{
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', 'chfgecck23k', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', __DIR__, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    try {
        $total_summ = 0;
        global $constants;

        $elems = CIBlockElement::GetList(
            arFilter: array(
                'IBLOCK_ID' => $constants['LOTS_IBLOCK_ID'],
                array(
                    'LOGIC' => 'OR',
                    ['PROPERTY_' . $constants['PROPERTY_STATUS_ID'] => false], // статус null
                    ['PROPERTY_' . $constants['PROPERTY_STATUS_ID'] => $constants['PROPERTY_STATUS_WIN']] // статус Выиграно
                ), // статус
                'PROPERTY_' . $constants['PROPERTY_AKTIVNOST_ID'] => $constants['PROPERTY_ACTIVE_YES'], // active
                'PROPERTY_' . $constants['PROPERTY_PRIVYAZKA_K_SDELKE_ID'] => $deal_id // привязка к сделке
            ),
            arSelectFields: array(
                'PROPERTY_' . $constants['PROPERTY_SUMMA_ID'], // общая сумма
                'PROPERTY_' . $constants['PROPERTY_STATUS_ID'], // статус
            )
        );

        while ($elem = $elems->Fetch()) {
            // общая сумма
            if ($elem['PROPERTY_' . $constants['PROPERTY_SUMMA_ID'] . '_VALUE']) {
                $total_summ += $elem['PROPERTY_' . $constants['PROPERTY_SUMMA_ID'] . '_VALUE'];
            }
        }
        $total_summ = $total_summ ?: 1;

        $arFields = array(
            'OPPORTUNITY' => $total_summ,
        );
        $deal = new CCrmDeal();
        $deal->Update($deal_id, $arFields);

        if ($deal->LAST_ERROR) {
            throw new Exception($deal->LAST_ERROR);
        }

    } catch(\Throwable $e) {
        echo $e->getMessage();
        file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
        file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'Error:', FILE_APPEND);
        file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
        file_put_contents(__DIR__ . '/logs.txt', $e->getMessage(), FILE_APPEND);
        file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    }
}


