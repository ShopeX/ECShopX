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

use Dingo\Api\Exception\ResourceException;
use Exception;
use KaquanBundle\Entities\CardPackage;
use KaquanBundle\Entities\CardPackageItems;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Traits\CardPackageCheckTrait;

class PackageEditService
{
    use CardPackageCheckTrait;

    // 优惠券
    private $discountCards;
    // 卡券包
    private $cardPackage;
    // 卡券包关联
    private $cardPackageItems;

    public function __construct()
    {
        $this->cardPackage = app('registry')->getManager('default')->getRepository(CardPackage::class);
        $this->discountCards = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->cardPackageItems = app('registry')->getManager('default')->getRepository(CardPackageItems::class);
    }

    /**
     * 递增券包发放数
     *
     * @param int $companyId
     * @param int $packageId
     * @return mixed
     */
    public function incrCardPackageGetNum(int $companyId, int $packageId)
    {
        return $this->cardPackage->incrGetNum($companyId, $packageId);
    }

    /**
     * 创建卡券包
     *
     * @param int $companyId
     * @param array $inputData
     * @return bool
     * @throws Exception
     */
    public function createPackage(int $companyId, array $inputData): bool
    {
        $limitCount = (int)$inputData['limit_count'] ?: 1;
        $nowTime = time();
        // 卡包信息插入
        $createData = [
            'company_id' => $companyId,
            'title' => (string)$inputData['title'],
            'package_describe' => (string)$inputData['package_describe'] ?? '',
            'limit_count' => $limitCount,
            'get_num' => 0,
            'row_status' => 1,
            'created' => $nowTime,
            'updated' => $nowTime,
        ];
        $packageVoucher = $this->checkCreatePackage($companyId, $inputData['package_content']);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $packageId = $this->cardPackage->insertGetId($createData);
            foreach ($packageVoucher as $item) {
                $item['package_id'] = $packageId;
                $this->cardPackageItems->create($item);
            }

            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return true;
    }

    /**
     * 更新卡券包基础内容
     *
     * @param int $companyId
     * @param array $inputData
     * @return bool
     */
    public function editPackage(int $companyId, array $inputData): bool
    {
        $where = [
            'company_id' => $companyId,
            'package_id' => (int)$inputData['package_id'],
            'row_status' => 1
        ];

        $cardPackageInfo = $this->cardPackage->getInfo($where);
        if (empty($cardPackageInfo)) {
            throw new ResourceException(trans('KaquanBundle.package_not_found'));
        }

        $packageBaseInfo = [
            'title' => (string)$inputData['title'],
            'package_describe' => $inputData['package_describe'] ?? '',
        ];


        $this->cardPackage->updateBy($where, $packageBaseInfo);
        return true;
    }

    /**
     * 删除卡券包
     *
     * @param int $companyId
     * @param int $packageId
     * @return bool
     * @throws Exception
     */
    public function deletePackage(int $companyId, int $packageId): bool
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $where = [
                'package_id' => $packageId,
                'company_id' => $companyId
            ];
            $this->cardPackage->updateBy($where, ['row_status' => 0]);
            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return true;
    }
}
