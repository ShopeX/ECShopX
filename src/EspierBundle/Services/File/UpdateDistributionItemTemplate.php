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

namespace EspierBundle\Services\File;

use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemStoreService;
use OpenapiBundle\Services\Distributor\DistributorItemService;

/**
 * 更新店铺商品的模板
 * Class UpdateDistributionItemTemplate
 * @package EspierBundle\Services\File
 */
class UpdateDistributionItemTemplate extends AbstractTemplate
{
    protected $header = [
        "店铺ID" => "distribution_id",
        "店铺号" => "shop_code",
        "商品货号" => "item_bn",
        "是否上架" => "is_onsale",
        "是否总部发货" => "is_total_store",
        "商品库存" => "item_store",
        "商品价格" => "item_price",
    ];

    protected $headerInfo = [
        "店铺ID" => ["size" => 32, "remarks" => "店铺ID和店铺号需选一项进行填写", "is_need" => false],
        "店铺号" => ["size" => 32, "remarks" => "店铺ID和店铺号需选一项进行填写", "is_need" => false],
        "商品货号" => ["size" => 32, "remarks" => "", "is_need" => true],
        "是否上架" => ["size" => 32, "remarks" => "是否上架: 0否, 1是", "is_need" => false],
        "是否总部发货" => ["size" => 32, "remarks" => "是否总部发货: 0否, 1是 （当前只支持更新spu级商品）", "is_need" => false],
        "商品库存" => ["size" => 32, "remarks" => "库存为0-999999的整数 （若总部发货, 商品库存将不会更新）", "is_need" => false],
        "商品价格" => ["size" => 32, "remarks" => "价格需大于0元 （若总部发货, 商品库存将不会更新）", "is_need" => false],
    ];

    protected $isNeedCols = [
        "商品货号" => "item_bn",
    ];

    public const STORE_MIN = 0;//库存最小值
    public const STORE_MAX = 999999;//库存最大值

    public const IS_TRUE = DistributorItemService::IS_TRUE;
    public const IS_TRUE_DESC = DistributorItemService::IS_TRUE_DESC;

    public const IS_FALSE = DistributorItemService::IS_FALSE;
    public const IS_FALSE_DESC = DistributorItemService::IS_FALSE_DESC;

    public const IS_MAP = [
        self::IS_TRUE => self::IS_TRUE_DESC,
        self::IS_FALSE => self::IS_FALSE_DESC,
    ];

    public function handleRow(int $companyId, array $row): void
    {
        // 更新的参数
        $updateData = [
            "is_can_sale" => null,
            "is_total_store" => null,
            "store" => null,
            "price" => null,
        ];

        // 行数据的整理
        $rowData = [
            "distribution_id" => $row["distribution_id"] ?? "",
            "shop_code" => $row["shop_code"] ?? "",
            "item_bn" => $row["item_bn"] ?? "",
            "is_onsale" => $row["is_onsale"] ?? "",
            "is_total_store" => $row["is_total_store"] ?? "",
            "item_store" => $row["item_store"] ?? "",
            "item_price" => $row["item_price"] ?? "",
        ];
        // 去空格
        array_walk($rowData, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });
        // 验证门店参数
        if ($rowData["distribution_id"] <= 0 && empty($rowData["shop_code"])) {
            throw new \Exception("店铺ID与店铺号必填一项");
        }
        // 获取商品编号
        if (empty($rowData["item_bn"])) {
            throw new \Exception("未填写商品货号");
        }
        // 是否上架
        if ($rowData["is_onsale"] !== "" && !is_null($rowData["is_onsale"])) {
            if (!isset(self::IS_MAP[$rowData["is_onsale"]])) {
                throw new \Exception("更新是否上架数据失败,请查看填写说明重新上传或联系客服处理");
            }
            $updateData["is_can_sale"] = (int)$rowData["is_onsale"];
        } else {
            unset($updateData["is_can_sale"]);
        }
        // 是否总部发货
        if ($rowData["is_total_store"] !== "" && !is_null($rowData["is_total_store"])) {
            if (!isset(self::IS_MAP[$rowData["is_total_store"]])) {
                throw new \Exception("更新是否总部发货数据失败,请查看填写说明重新上传或联系客服处理");
            }
            $updateData["is_total_store"] = (int)$rowData["is_total_store"];
        } else {
            unset($updateData["is_total_store"]);
        }
        // 库存验证
        if (is_numeric($rowData["item_store"])) {
            $rowData["item_store"] = (int)$rowData["item_store"];
            if ($rowData["item_store"] < self::STORE_MIN) {
                throw new \Exception("库存数量不能小于" . self::STORE_MIN);
            }
            if ($rowData["item_store"] > self::STORE_MAX) {
                throw new \Exception("库存数量不能大于" . self::STORE_MAX);
            }
            $updateData["store"] = $rowData["item_store"];
        } else {
            unset($updateData["store"]);
        }
        // 价格验证
        if ($rowData["item_price"] != "") {
            if (!is_numeric($rowData["item_price"]) || $rowData["item_price"] <= 0) {
                throw new \Exception("更新价格数据失败,请查看填写说明重新上传或联系客服处理");
            }
            // 将价格转为分
            $updateData["price"] = (int)bcmul((string)$rowData["item_price"], "100", 0);
        } else {
            unset($updateData["price"]);
        }

        // 获取商品列表
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(["company_id" => $companyId, "item_bn" => $rowData["item_bn"]]);
        // 获取商品主键id
        $itemId = (int)($itemInfo["item_id"] ?? 0);
        // 如果商品不存在
        if ($itemId <= 0) {
            throw new \Exception("未查询到对应商品");
        }
        // 是否是默认商品
        $isDefault = (int)($itemInfo["is_default"] ?? false);
        // 获取商品的spu的item_id，这里没有用到goods_id来查询，是因为goods_id是不会变的，如果默认商品删除了，goods_id则会变为脏数据，而default_item_id实时更新, goods_id只能作为判断当前的商品是否是同一个spu下面的sku
        $goodsId = (int)($itemInfo["goods_id"] ?? 0);
        $defaultItemId = (int)($itemInfo["default_item_id"] ?? 0);
        // 如果存在spu_id，且当前的商品并不是spu，此时又存在 是否总部发货的字段，则不进行更新
        if ($defaultItemId > 0 && $itemId != $defaultItemId && isset($updateData["is_total_store"])) {
            throw new \Exception("是否总部发货, 当前只支持更新spu级商品");
        }

        // 获取店铺信息
        $distributor = $this->getDistributor($companyId, (int)$rowData["distribution_id"], (string)$rowData["shop_code"]);
        $distributorId = (int)($distributor["distributor_id"] ?? 0);
        if (empty($distributor) || $distributorId <= 0) {
            throw new \Exception("未查询到对应店铺");
        }

        // 获取门店商品服务
        $distributorItemsService = new DistributorItemsService();
        $filter = [
            "distributor_id" => $distributorId,
            "company_id" => $companyId,
            "item_id" => $itemId
        ];
        // 如果商品查不到则直接返回，不做新增操作
        $distributorItemInfo = $distributorItemsService->getInfo($filter);
        // 是否存在数据，如果为true则做更新操作，false则做新增操作
        $issetData = true;
        if (empty($distributorItemInfo)) {
            $issetData = false;
            if (!isset($updateData["is_can_sale"])) {
                $updateData["is_can_sale"] = self::IS_FALSE;
            }
            // 获取spu的店铺商品信息
            $defaultDistributorItemInfo = $distributorItemsService->getInfo([
                "distributor_id" => $distributorId,
                "company_id" => $companyId,
                "item_id" => $defaultItemId
            ]);
            // 如果存在spu的店铺商品，则获取spu店铺商品的 是否总店发货 的状态，否则去获取店铺的 是否总店发货 的状态
            if (empty($defaultDistributorItemInfo)) {
                if (!isset($updateData["is_total_store"])) {
                    $updateData["is_total_store"] = self::IS_FALSE;
                }
            } else {
                $updateData["is_total_store"] = (bool)($defaultDistributorItemInfo["is_total_store"] ?? false);
            }
            // 值拷贝
            $isTotalStore = $updateData["is_total_store"];
            // 如果是总部发货，则无法更新商品的价格和库存
            if ($isTotalStore == self::IS_TRUE) {
                if (isset($updateData["store"]) || isset($updateData["price"])) {
                    throw new \Exception("商品总部发货无法更新库存和价格");
                }
            }
            // 如果没有库存，就默认获取商品自己的库存
            if (!isset($updateData["store"])) {
                $updateData["store"] = (int)($itemInfo["store"] ?? 0);
            }
            // 如果没有价格，就默认获取商品自己的价格
            if (!isset($updateData["price"])) {
                $updateData["price"] = (int)($itemInfo["price"] ?? 0);
            }
        } else {
            // 获取 是否总部发货 的参数
            if (isset($updateData["is_total_store"])) {
                $isTotalStore = $updateData["is_total_store"];
            } else {
                $isTotalStore = (int)($distributorItemInfo["is_total_store"] ?? false);
            }
            // 如果是总部发货，则无法更新商品的价格和库存
            if ($isTotalStore == self::IS_TRUE && (isset($updateData["store"]) || isset($updateData["price"]))) {
                throw new \Exception("商品总部发货无法更新库存和价格");
            }
        }
        // 如果扔存在商品价格，则检查价格
        if (isset($updateData['price'])) {
            // 检查商品价格是否大于活动的价格
            $distributorItemsService->checkItemPrice($companyId, [$defaultItemId], [$itemId => $updateData['price']]);
        }
        if (empty($updateData)) {
            return;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!$issetData) {
                // 新增店铺商品信息
                $distributorItemsService->create(array_merge([
                    'distributor_id' => $distributorId,
                    'company_id' => $companyId,
                    'shop_id' => 0, // 店铺不再关联门店
                    'item_id' => $itemId,
                    'goods_id' => $goodsId,
                    'default_item_id' => $defaultItemId,
                    'is_show' => $isDefault,
                    'is_self_delivery' => false,   //默认关闭自提配送
                    'is_express_delivery' => false, //默认关闭快递配送
                ], $updateData));
            } else {
                // 更新店铺商品信息
                $distributorItemsService->updateOneBy($filter, $updateData);
                // 如果存在库存和是否总部发货的配置项 且 设置的参数为 不是总部发货，则提示更新库存信息
                if (isset($updateData['store'], $updateData['is_total_store']) && $isTotalStore == self::IS_FALSE) {
                    (new ItemStoreService())->saveItemStore($itemId, $updateData['store'], $distributorId);
                }
            }

            // 如果存在总部发货的参数，则统一更新所有的sku的总部发货
            // 这里定义是spu才能更新，因为上文做了判断，is_total_store的参数，商品必须是spu
            if (isset($updateData['is_total_store'])) {
                $distributorItemsService->updateBy(['distributor_id' => $distributorId, 'goods_id' => $defaultItemId], ['is_total_store' => $isTotalStore]);
            }

            // 更新商品是否可售的逻辑
            // $filter = [
            //     'company_id' => $companyId,
            //     'default_item_id' => $defaultItemId,
            //     'item_type' => 'normal',
            // ];
            // $list = $itemsService->getItemsList($filter, 1, 1000, ["item_id" => "desc"]);
            // $list['list'] = $distributorItemsService->getDistributorSkuReplace($companyId, $distributorId, $list['list'], false);
            // $goodsCanSale = false;
            // foreach ($list['list'] as $value) {
            //     if ($value['is_can_sale']) {
            //         $goodsCanSale = true;
            //         break;
            //     }
            // }
            // 更新商品的是否上下架的参数
            // $distributorItemsService->updateBy(['distributor_id' => $distributorId, 'goods_id' => $defaultItemId], ['goods_can_sale' => $goodsCanSale]);
            $conn->commit();
        } catch (\Exception $exception) {
            $conn->rollback();
            // 记录日志
            app('log')->info(sprintf(
                "UpdateDistributionItemTemplate_handleRow_error: %s, file: %s, line: %d, company_id: %d, rowData: %s",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $companyId,
                json_encode($rowData, JSON_UNESCAPED_UNICODE)
            ));
            throw new \Exception("未知原因,请重新上传或联系客服处理");
        }
    }

    /**
     * 获取店铺信息
     * @param int $companyId 企业id
     * @param int $distributionId 门店id
     * @param string $shopCode 门店code
     * @return array 店铺信息
     */
    private function getDistributor(int $companyId, int $distributionId, string $shopCode): array
    {
        // 返回的结果集
        $result = [];
        // 获取门店服务
        $distributorService = new DistributorService();
        // 设置过滤条件
        $filter = [
            "company_id" => $companyId
        ];
        // 如果存在门店id就通过门店id去查询
        if ($distributionId > 0) {
            $result = $distributorService->getInfoSimple(array_merge($filter, ["distributor_id" => $distributionId]));
        }
        // 如果通过门店id查询不到，则通过门店code去查询
        if (empty($result) && !empty($shopCode)) {
            $result = $distributorService->getInfoSimple(array_merge($filter, ["shop_code" => $shopCode]));
        }
        return $result;
    }
}
