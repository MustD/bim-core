<?php

namespace Bim\Db\Lib;

use Bim\Db\Lib\CodeGenerator;
use Bim\Util\BitrixUserGroupsHelper;

/**
 * Class IblockGen
 * @package Bim\Db\Lib
 */
class IblockGen extends CodeGenerator
{

    public function __construct()
    {
        \CModule::IncludeModule('iblock');
    }


    /**
     * generateAddCode
     * @param array $IblockCode
     * @return bool|string
     */
    public function generateAddCode($IblockCode)
    {
        $iblock = new \CIBlock();
        $return = array();
        $iblockObject = $iblock->GetList(array(), array('CODE' => $IblockCode, 'CHECK_PERMISSIONS'=>'N'));
        if ($item = $iblockObject->Fetch()) {
            $item['GROUP_ID'] = \CIBlock::GetGroupPermissions($item['ID']);

            // Перегоняем id-шники групп в имена групп (если заданы в базе)
            $arGroups = BitrixUserGroupsHelper::getUserGroups();
            foreach($item['GROUP_ID'] as $groupId => $right){
                $groupCode = BitrixUserGroupsHelper::getUserGroupCode($groupId, $arGroups);
                if($groupCode != null && strlen($groupCode) > 0) {
                    $item['GROUP_ID'][$groupCode] = $item['GROUP_ID'][$groupId];
                    unset($item['GROUP_ID'][$groupId]);
                }
            }
            
            $item['FIELDS'] = \CIBlock::GetFields($item['ID']);
            unset($item['ID']);
            if ($return[] = $this->getMethodContent('Bim\Db\Iblock\IblockIntegrate', 'Add', array($item))) {
                $IblockProperty = new \CIBlockProperty();
                $dbIblockProperty = $IblockProperty->GetList(array(), array('IBLOCK_CODE' => $item['CODE']));
                while ($arIblockProperty = $dbIblockProperty->Fetch()) {
                    unset($arIblockProperty['ID']);
                    //unset($arIblockProperty['IBLOCK_ID']);
                    $arIblockProperty['IBLOCK_CODE'] = $item['CODE'];
                    $dbPropertyValues = \CIBlockPropertyEnum::GetList(
                        array(),
                        array("IBLOCK_ID" => $arIblockProperty['IBLOCK_ID'], "CODE" => $arIblockProperty['CODE']));
                    while($arPropertyValues = $dbPropertyValues->Fetch()) {
                        unset($arPropertyValues['ID']);
                        unset($arPropertyValues['PROPERTY_ID']);
                        $arIblockProperty['VALUES'][] = $arPropertyValues;
                    }
                    if(!is_null($arIblockProperty['LINK_IBLOCK_ID'])) {
                        $arLinkedIBlock = $iblock->GetList(array(), array('ID' => $arIblockProperty['LINK_IBLOCK_ID'], 'CHECK_PERMISSIONS'=>'N'))->Fetch();
                        $arIblockProperty['LINK_IBLOCK_CODE'] = $arLinkedIBlock['CODE'];
                    }
                    $return[] = $this->getMethodContent('Bim\Db\Iblock\IblockPropertyIntegrate', 'Add',
                                                        array($arIblockProperty));
                }
                return implode(PHP_EOL, $return);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * метод для генерации кода обновления инфоблока
     * @param $params array
     * @return mixed
     */
    public function generateUpdateCode($params)
    {
        $this->checkParams($params);
        $code = false;
        foreach ($this->ownerItemDbData as $iblockData) {
            $updateFields = $iblockData;
            unset($updateFields['ID']);
            $updateFields['FIELDS'] = \CIBlock::GetFields($iblockData['ID']);
            $updateFields['GROUP_ID'] = \CIBlock::GetGroupPermissions($iblockData['ID']);
            $code = $code . $this->getMethodContent('Bim\Db\Iblock\IblockIntegrate', 'Update',
                    array($updateFields['CODE'], $updateFields)) . PHP_EOL . PHP_EOL;
        }
        return $code;
    }


    /**
     * метод для генерации кода удаления  инфоблока
     * @param array $iblockCode
     * @return mixed
     * @internal param array $params
     */
    public function generateDeleteCode($iblockCode)
    {
        return $this->getMethodContent('Bim\Db\Iblock\IblockIntegrate', 'Delete', array($iblockCode));
    }


    /**
     * абстрактный метод проверки передаваемых параметров
     * @param $params array
     * @return mixed
     */
    public function checkParams($params)
    {
        // TODO: Implement checkParams() method.
    }

}