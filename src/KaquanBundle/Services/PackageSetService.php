<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace KaquanBundle\Services;

use Exception;
use KaquanBundle\Entities\CardPackageTrigger;

class PackageSetService
{
    public const TRIGGER_TYPE = ['vip_grade', 'grade'];

    private $cardPackageTrigger;

    public function __construct()
    {
        $this->cardPackageTrigger = app('registry')->getManager('default')->getRepository(CardPackageTrigger::class);
    }

    /**
     * 卡券包触发设置
     *
     * associationId: 关联的会员等级数据ID
     * triggerType: vip_grade 付费会员,grade 会员等级
     * @param int $companyId
     * @param array $packageIdSet
     * @param int $associationId
     * @param string $triggerType
     * @return bool
     * @throws Exception
     */
    public function setTriggerByPackageIdSet(int $companyId, array $packageIdSet, int $associationId, string $triggerType): bool
    {
        if (empty($packageIdSet)) {
            return true;
        }
        if (!in_array($triggerType, self::TRIGGER_TYPE)) {
            return false;
        }

        $current = current($packageIdSet);
        if (!is_numeric($current) && array_has($current, 'package_id')) {
            $packageIdSet = array_column($packageIdSet, 'package_id');
        }

        $packageIdSet = array_filter(array_unique($packageIdSet));

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $where = [
                'company_id' => $companyId,
                'trigger_type' => $triggerType,
                'association_id' => $associationId
            ];
            $this->cardPackageTrigger->deleteBy($where);

            foreach ($packageIdSet as $item) {
                $insertRow = [
                    'package_id' => $item,
                    'company_id' => $companyId,
                    'trigger_type' => $triggerType,
                    'association_id' => $associationId
                ];
                $this->cardPackageTrigger->create($insertRow);
            }
            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return true;
    }

    /**
     * 通过关联ID删除
     *
     * @param int $companyId
     * @param $associationId
     * @param string $triggerType
     * @return bool
     */
    public function deleteTriggerByAssociationId(int $companyId, $associationId, string $triggerType): bool
    {
        // ShopEx EcShopX Core Module
        if (!in_array($triggerType, self::TRIGGER_TYPE)) {
            return false;
        }

        $where = [
            'company_id' => $companyId,
            'association_id' => $associationId,
            'trigger_type' => $triggerType
        ];

        $this->cardPackageTrigger->deleteBy($where);
        return true;
    }

    /**
     * 得到对应绑定的包名ID列表
     *
     * @param int $companyId
     * @param int $gradeId
     * @param string $triggerType
     * @return array
     */
    public function getBindPackage(int $companyId, int $gradeId, string $triggerType): array
    {
        $where = [
            'company_id' => $companyId,
            'association_id' => $gradeId,
            'trigger_type' => $triggerType
        ];
        $fields = 'package_id';
        $packageList = $this->cardPackageTrigger->getLists($where, $fields);
        return array_column($packageList, 'package_id');
    }

    /**
     * 得到对应绑定的包名列表
     *
     * @param int $companyId
     * @param array $gradeIdList
     * @param string $triggerType
     * @return array
     */
    public function getBindPackageList(int $companyId, array $gradeIdList, string $triggerType): array
    {
        // ShopEx EcShopX Core Module
        if (empty($gradeIdList)) {
            return [];
        }

        $where = [
            'company_id' => $companyId,
            'association_id' => $gradeIdList,
            'trigger_type' => $triggerType
        ];
        $fields = 'package_id,association_id';
        $bindPackageList = $this->cardPackageTrigger->getLists($where, $fields);

        $packageIdList = array_column($bindPackageList, 'package_id');

        $packageList = (new PackageQueryService())->getListByIdList($companyId, $packageIdList);
        $packageIndex = array_column($packageList, null, 'package_id');


        $bindPackageIndex = [];
        foreach ($bindPackageList as $item) {
            if (isset($bindPackageIndex[$item['association_id']]) && isset($packageIndex[$item['package_id']])) {
                $bindPackageIndex[$item['association_id']][] = $packageIndex[$item['package_id']];
            } elseif (isset($packageIndex[$item['package_id']])) {
                $bindPackageIndex[$item['association_id']] = [$packageIndex[$item['package_id']]];
            }
        }
        return $bindPackageIndex;
    }

    /**
     * 触发券包发放
     *
     * @param int $companyId
     * @param int $userId
     * @param int $gradeId
     * @param string $triggerType
     * 是否重复发放
     * @param string $isReissue
     * @return bool
     */
    public function triggerPackage(int $companyId, int $userId, int $gradeId, string $triggerType, string $isReissue): bool
    {
        $packageReceivesService = new PackageReceivesService();

        if (!$isReissue) {
            $record = $packageReceivesService->getReceivesRecord($companyId, $userId, $gradeId, $triggerType);
            if (!empty($record)) {
                return true;
            }
        }

        $packageIdList = $this->getBindPackage($companyId, $gradeId, $triggerType);

        if (!empty($packageIdList)) {
            foreach ($packageIdList as $packageId) {
                try {
                    $packageReceivesService->receivesPackage($companyId, $packageId, $userId, $triggerType);
                } catch (Exception $exception) {
                    app('log')->debug('脚本未能成功发放卡券包：' . $exception->getMessage() . 'file:' . $exception->getFile() . 'line:' . $exception->getLine());
                }
            }
            // 记录还用户已发过此券
            $packageReceivesService->addReceivesRecord($companyId, $userId, $gradeId, $triggerType);
        }


        return true;
    }
}
