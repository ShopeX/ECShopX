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

namespace OrdersBundle\Services;

use DistributionBundle\Services\DistributorGeofenceService;
use EspierBundle\Services\BaseService;
use OrdersBundle\Constants\OrderReceiptTypeConstant;
use OrdersBundle\Entities\CompanyRelDelivery;

/**
 * 订单的配送模式
 */
class CompanyRelDeliveryService extends BaseService
{
    public function getEntityClass(): string
    {
        // ShopEx EcShopX Business Logic Layer
        return CompanyRelDelivery::class;
    }

    /**
     * 商家自配 > 按整单计算
     */
    public const TYPE_MERCHANT_WHOLE_ORDER = 1;

    /**
     * 商家自配 > 按距离计算
     */
    public const TYPE_MERCHANT_DISTANCE = 2;

    /**
     * 状态 - 启用
     */
    public const STATUS_ENABLE = 1;

    /**
     * 状态 - 禁用
     */
    public const STATUS_DISABLE = 0;

    /**
     * 获取已启用的配送模式
     * @param int $companyId
     * @return array
     */
    public function getEnableDeliveryInfo(int $companyId): array
    {
        return $this->find(["company_id" => $companyId, "status" => self::STATUS_ENABLE]);
    }

    public function find(array $filter): array
    {
        // ShopEx EcShopX Core Module
        return $this->handlerData(parent::find($filter));
    }

    /**
     * @param int $companyId 企业id
     * @param int $type 商家配送类型
     * @param int|null $status 商家配送的状态
     * @param string|null $freightPrice 配送费用
     * @return array
     */
    public function save(int $companyId, int $type, ?int $status, ?string $freightPrice): array
    {
        $filter = ["company_id" => $companyId];

        if ($this->find($filter)) {
            $updateData = [];
            if (!is_null($type)) {
                $updateData["type"] = $type;
            }
            if (!is_null($status)) {
                $updateData["status"] = $status;
            }
            if (!is_null($freightPrice)) {
                $updateData["rules"] = (string)jsonEncode(["freight_price" => $freightPrice]);
            }
            $data = parent::updateDetail($filter, $updateData);
        } else {
            $data = parent::create([
                "company_id" => $companyId,
                "type" => $type,
                "status" => is_null($status) ? self::STATUS_DISABLE : $status,
                "rules" => (string)jsonEncode(["freight_price" => $freightPrice]),
                "other_params" => ""
            ]);
        }

        return $this->handlerData($data);
    }

    /**
     * 处理数据
     * @param array $data
     * @return array
     */
    protected function handlerData(array $data): array
    {
        // ShopEx EcShopX Core Module
        if (isset($data["status"])) {
            $data["status"] = (int)$data["status"];
        }
        $data["rules"] = (array)jsonDecode($data["rules"] ?? null);
        $data["other_params"] = (array)jsonDecode($data["other_params"] ?? null);
        return $data;
    }

    /**
     * 获取商家运费
     * @param int $companyId
     * @param array $distributorId
     * @param array $orderData
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFreightFee(array &$orderData): bool
    {
        return false;
        $companyId = (int)$orderData["company_id"];
        $distributorIds = [(int)$orderData["distributor_id"]];
        // 获取商家自配信息
        $deliveryInfo = $this->getEnableDeliveryInfo($companyId);
        if (empty($deliveryInfo)) {
            return false;
        }
        try {
            // 判断范围
            if ((new DistributorGeofenceService())->inRange($companyId, $distributorIds, $orderData)) {
                $deliveryType = $deliveryInfo["type"] ?? null;
                if ($deliveryType == self::TYPE_MERCHANT_DISTANCE || $deliveryType == self::TYPE_MERCHANT_WHOLE_ORDER) {
                    $orderData["receipt_type"] = OrderReceiptTypeConstant::MERCHANT;
                }
                // 运费信息，单位为元
                $freightFee = $deliveryInfo["rules"]["freight_price"] ?? "0";
                if (is_numeric($freightFee)) {
                    $orderData['freight_fee'] = $freightFee;
                    $orderData['total_fee'] = $orderData['total_fee'] > 0 ? $orderData['total_fee'] + $orderData['freight_fee'] : 0; // 订单总金额
                }
            }
            return true;
        } catch (\Exception $exception) {
            app("log")->info(sprintf("distributor_geofence_error:%s,%s,%s", $exception->getMessage(), $exception->getFile(), $exception->getLine()));
            return false;
        }
    }
}
