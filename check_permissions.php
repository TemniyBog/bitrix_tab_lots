<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Crm\Service;

CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');

$constants = parse_ini_file(__DIR__ . '/.settings.ini');


function check_permissions()
{
    global $constants;
    $tenderman_group_id = $constants['TENDERMAN_GROUP_ID'];
    $user_role = 'user';
    $entityTypeId = CCrmOwnerType::Deal; // айди сущности - Сделка

    // Получаем айди сделки
    $deal_id = $_POST['PARAMS']['DEAL_ID'] ? null : 0;

    global $USER;
    $user_id = $USER->GetID();

    // Получаем айди категории
    $deal = CCrmDeal::GetByID($deal_id);

    $category_id = $deal['CATEGORY_ID'];  // ID воронки

    if (CSocNetGroup::CanUserReadGroup($user_id, $tenderman_group_id)) {
        $user_role = 'tenderman';
    } else {
        // проверка прав
        $userPermissions = Service\Container::getInstance()->getUserPermissions();
        if ($userPermissions->checkUpdatePermissions($entityTypeId, $deal_id, $category_id)) {
            $user_role = 'editor';
        } else {
            $user_role = 'user';
        }
    }

    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', date(DATE_RFC822) . ', ' . __FILE__ . ', ' . 'function check_permissions(), ' . '$user_role:', FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', var_export($user_role, true), FILE_APPEND);
    file_put_contents(__DIR__ . '/logs.txt', PHP_EOL, FILE_APPEND);

    return $user_role;
}

