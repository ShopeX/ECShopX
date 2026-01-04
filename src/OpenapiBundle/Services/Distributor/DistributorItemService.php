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

namespace OpenapiBundle\Services\Distributor;

use DistributionBundle\Entities\DistributorItems;
use DistributionBundle\Services\DistributorItemsService;
use EspierBundle\Services\Cache\RedisCacheService;
use EspierBundle\Services\Upload\UploadService;
use EspierBundle\Services\UploadToken\UploadTokenAbstract;
use EspierBundle\Services\UploadTokenFactoryService;
use GoodsBundle\Entities\Items;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemStoreService;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Exceptions\ServiceErrorException;
use OpenapiBundle\Services\BaseService;

class DistributorItemService extends BaseService
{
    public const IS_TRUE = 1;
    public const IS_TRUE_DESC = "是";

    public const IS_FALSE = 0;
    public const IS_FALSE_DESC = "否";

    public const IS_MAP = [
        self::IS_TRUE => self::IS_TRUE_DESC,
        self::IS_FALSE => self::IS_FALSE_DESC,
    ];

    public function getEntityClass(): string
    {
        return DistributorItems::class;
    }

    /**
     * 获取店铺商品的信息
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @return array
     */
    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = [
            "list" => $this->getRepository()->getList($filter, $cols, $page, $pageSize, $orderBy),
            "total_count" => $needCountSql ? $this->getRepository()->count($filter) : 0,
        ];
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 获取商品spu列表
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @param array $distributorInfo 店铺信息，如果传入，则可以把方法内店铺数据传递出去，防止外部多次重复查询店铺信息
     * @return array
     */
    public function itemSpuList(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true, array &$distributorInfo = []): array
    {
        // 获取店铺
        $distributorInfo = (new DistributorService())->findByIdOrCode($filter);
        unset($filter["shop_code"]);
        if (empty($distributorInfo)) {
            $result = [];
            $this->handlerListReturnFormat($result, $page, $pageSize);
            return $result;
        }

        $column = "item_id,item_bn,item_name,store,price,approve_status,item_type,default_item_id,type";

        if (empty($orderBy)) {
            $orderBy['item_id'] = 'DESC';
        }

        $filter["is_default"] = true;
        $filter["item_type"] = "normal";
        $filter["distributor_id"] = $distributorInfo['distributor_id'];
        $filter['is_can_sale'] = $filter['goods_can_sale'] ?? 1;
        if (isset($filter['goods_can_sale'])) {
            unset($filter['goods_can_sale']);
        }

        if (isset($filter['item_name|like'])) {
            $filter['item_name'] = $filter['item_name|like'];
            $filter['item_name|contains'] = $filter['item_name|like'];
            unset($filter['item_name|like']);
        }


        $result = (new DistributorItemsService())->getDistributorRelItemList($filter, $pageSize, $page, $orderBy, true, $column);

        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 批量更新
     * @param array $filter
     * @param array $updateData
     * @return int
     */
    public function updateBatch(array $filter, array $updateData): int
    {
        $result = $this->getRepository()->updateBy($filter, $updateData);
        return count($result);
    }

    /**
     * 更新店铺商品
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function save(array $filter, array $updateData, bool $isCoverStore = true): array
    {
        // 参数获取
        $params = [];
        // 是否上下架
        if (isset($updateData["is_can_sale"])) {
            $params["is_can_sale"] = $updateData["is_can_sale"];
        }
        // 是否总部发货
        if (isset($updateData["is_total_store"])) {
            $params["is_total_store"] = $updateData["is_total_store"];
        }
        // 库存
        if (isset($updateData["store"]) && is_numeric($updateData["store"])) {
            $params["store"] = (int)$updateData["store"];
        }
        // 价格，最终保存的单位是分
        if (isset($updateData["price"]) && is_numeric($updateData["price"])) {
            $params["price"] = (int)bcmul($updateData["price"], 100, 0);
        }
        // 如果为空则不更新
        if (empty($params)) {
            return [];
        }

        // 店铺信息
        $distributorInfo = (new DistributorService())->findByIdOrCode($filter);
        if (empty($distributorInfo)) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
        }
        // 商品信息
        $itemInfo = $this->getRepository(Items::class)->getInfo([
            "company_id" => $filter["company_id"],
            "item_bn" => $filter["item_bn"]
        ]);
        if (empty($itemInfo)) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }
        $defaultItemId = $itemInfo["default_item_id"]; // 获取该商品spu的item_id，如果与当前商品的item_id相同，则是spu，否则该商品是sku
        $itemId = $itemInfo["item_id"]; // 获取商品id
        // 如果存在spu_id，且当前的商品并不是spu，此时又存在 是否总部发货的字段，则不进行更新
        if (isset($params["is_total_store"]) && $defaultItemId > 0 && $itemId != $defaultItemId) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_ITEM_ERROR, "是否总部发货, 当前只支持更新spu级商品");
        }
        // 店铺商品信息
        $distributorItemInfo = $this->find([
            "company_id" => $filter["company_id"],
            "distributor_id" => $distributorInfo["distributor_id"],
            "item_id" => $itemInfo["item_id"]
        ]);

        // 设置是否总部发货
        $this->setIsTotalStore($params, $distributorInfo, $itemInfo, $distributorItemInfo);
        // 设置是否上下架
        $this->setIsCanSale($params, $distributorInfo, $itemInfo, $distributorItemInfo);
        // 设置价格和库存
        $this->setPriceAndStore($params, $distributorInfo, $itemInfo, $distributorItemInfo);
        // 根据店铺商品是否存在，来获取 是否总部发货的参数（考虑到更新的时候有可能没有传递这个值，则需要从店铺商品中获取）
        if (!isset($params["is_total_store"])) {
            $isTotalStore = (int)($distributorItemInfo["is_total_store"] ?? self::IS_FALSE);
        } else {
            $isTotalStore = (int)$params["is_total_store"];
        }

        $this->transaction(function () use ($filter, $distributorItemInfo, $distributorInfo, $itemInfo, $params, $isTotalStore, $isCoverStore) {
            if ($distributorItemInfo) {
                // 存在库存操作时，则需要判断库存是覆盖还是增减操作
                if (isset($params["store"])) {
                    if (!$isCoverStore) {
                        $this->updateStore($filter, (int)$params["store"]);
                        $store = (int)bcadd($distributorItemInfo["store"], $params["store"]);
                        unset($params["store"]);
                    } else {
                        $store = (int)$params["store"];
                    }
                } else {
                    $store = null;
                }
                // 更新店铺商品
                $this->updateDetail([
                    "company_id" => $filter["company_id"],
                    "distributor_id" => $distributorInfo["distributor_id"],
                    "item_id" => $itemInfo["item_id"]
                ], $params);
                if (!is_null($store)) {
                    (new ItemStoreService())->saveItemStore($itemInfo["item_id"], $store, $distributorInfo["distributor_id"]);
                }
            } else {
                // 存在库存操作时，则需要判断库存是覆盖还是增减操作
                if (isset($params["store"])) {
                    $store = (int)$params["store"];
                } else {
                    $store = null;
                }
                // 新增店铺商品信息
                $this->create(array_merge([
                    'distributor_id' => $distributorInfo["distributor_id"],
                    'company_id' => $distributorInfo["company_id"],
                    'shop_id' => 0, // 店铺不再关联门店
                    'item_id' => $itemInfo["item_id"],
                    'goods_id' => $itemInfo["goods_id"],
                    'default_item_id' => $itemInfo["default_item_id"],
                    'is_show' => (int)($itemInfo["is_default"] ?? false),
                    'goods_can_sale' => true,
                    'is_self_delivery' => false,   //默认关闭自提配送
                    'is_express_delivery' => false, //默认关闭快递配送
                ], $params));
                if (!is_null($store)) {
                    (new ItemStoreService())->saveItemStore($itemInfo["item_id"], $store, $distributorInfo["distributor_id"]);
                }
            }

            /**
             * 如果需要更新 是否总部发货，则上文已经判断了该更新的商品一定是spu
             * 则将该spu下面所有的sku的 是否总部发货 的值更新
             */
            if (isset($params['is_total_store'])) {
                $this->updateBatch(['distributor_id' => $distributorInfo["distributor_id"], 'goods_id' => $itemInfo["default_item_id"]], ['is_total_store' => $params['is_total_store']]);
            }

            /**
             * 如果需要更新 是否上下架的字段
             * 当前spu下面如果有一个sku是已上架，则需要将该spu下面的所有sku的goods_can_sale都变为已上架
             * 当前spu下面如果所有的sku是已下架，则需要将该spu下面的所有sku的goods_can_sale都变为已下架
             */
            if (isset($params["is_can_sale"])) {
                $canSaleResult = $this->list([
                    "company_id" => $filter["company_id"],
                    "distributor_id" => $distributorInfo["distributor_id"],
                    "default_item_id" => $itemInfo["default_item_id"]
                ]);
                $isCanSaleFlag = false;
                foreach ($canSaleResult["list"] as $canSaleDistributorItemInfo) {
                    if (!$isCanSaleFlag && $canSaleDistributorItemInfo["is_can_sale"] == self::IS_TRUE) {
                        $isCanSaleFlag = true;
                    }
                }
                // 更新商品的是否上下架的参数
                $this->updateBatch([
                    "company_id" => $filter["company_id"],
                    "distributor_id" => $distributorInfo["distributor_id"],
                    "default_item_id" => $itemInfo["default_item_id"]
                ], [
                    'goods_can_sale' => $isCanSaleFlag ? self::IS_TRUE : self::IS_FALSE,
                ]);
            }
        }, function (\Exception $exception) {
            if ($exception instanceof ErrorException) {
                throw $exception;
            } else {
                throw new ServiceErrorException($exception);
            }
        });
        return [];
    }

    /**
     * 设置 是否总部发货
     * @param array $params 更新的字段
     * @param array $distributorInfo 店铺信息
     * @param array $itemInfo 总部商品的信息，即items表中的信息
     * @param array $distributorItemInfo 店铺商品的信息
     * @throws \Exception
     */
    protected function setIsTotalStore(array &$params, array $distributorInfo, array $itemInfo, array $distributorItemInfo)
    {
        // 获取该商品spu的item_id，如果与当前商品的item_id相同，则是spu，否则该商品是sku
        $itemInfo["default_item_id"] = (int)$itemInfo["default_item_id"];
        // 获取商品id
        $itemInfo["item_id"] = (int)$itemInfo["item_id"];
        // 如果存在spu_id，且当前的商品并不是spu，此时又存在 是否总部发货的字段，则不进行更新
        if (isset($params["is_total_store"]) && $itemInfo["default_item_id"] > 0 && $itemInfo["item_id"] != $itemInfo["default_item_id"]) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_ITEM_ERROR, "是否总部发货, 当前只支持更新spu级商品");
        }
        if (empty($distributorItemInfo)) {
            // 获取spu的店铺商品信息
            $defaultDistributorItemInfo = $this->find([
                "distributor_id" => $distributorInfo["distributor_id"],
                "company_id" => $itemInfo["company_id"],
                "item_id" => $itemInfo["default_item_id"]
            ]);
            // 如果存在spu的店铺商品，则获取spu下的是否总部发货的值
            if ($defaultDistributorItemInfo) {
                $params["is_total_store"] = (bool)($defaultDistributorItemInfo["is_total_store"] ?? self::IS_FALSE);
            } elseif (!isset($params["is_total_store"])) {
                // 如果不存在spu信息 且 不存在 是否总部发货 的字段，则根据店铺信息做默认配置
                $params["is_total_store"] = self::IS_FALSE;
            }
        } else {
            if (!isset($params["is_total_store"])) {
                $params["is_total_store"] = (int)$distributorItemInfo["is_total_store"];
            }
        }
    }

    /**
     * 设置 是否上下架
     * @param array $params 更新的字段
     * @param array $distributorInfo 店铺信息
     * @param array $itemInfo 总部商品的信息，即items表中的信息
     * @param array $distributorItemInfo 店铺商品的信息
     * @throws \Exception
     */
    protected function setIsCanSale(array &$params, array $distributorInfo, array $itemInfo, array $distributorItemInfo)
    {
        // 如果不存在店铺商品，且 不存在是否上下架的字段，则需要提供一个默认值
        if (empty($distributorItemInfo) && !isset($params["is_can_sale"])) {
            $params["is_can_sale"] = self::IS_FALSE;
        }
    }

    /**
     * 设置 价格和库存
     * @param array $params 更新的字段
     * @param array $distributorInfo 店铺信息
     * @param array $itemInfo 总部商品的信息，即items表中的信息
     * @param array $distributorItemInfo 店铺商品的信息
     * @throws \Exception
     */
    protected function setPriceAndStore(array &$params, array $distributorInfo, array $itemInfo, array $distributorItemInfo)
    {
        // 如果商品是总部发货，则无法更新库存和价格
        // 如果店铺商品不存在，则需要给库存和价格默认值，默认值从商品信息中获取
        if (empty($distributorItemInfo)) {
            if ($params["is_total_store"] == self::IS_TRUE && (isset($params["store"]) || isset($params["price"]))) {
                throw new ErrorException(ErrorCode::DISTRIBUTOR_ITEM_ERROR, "商品总部发货无法更新库存和价格");
            }
            // 如果没有库存，就默认获取商品自己的库存
            if (!isset($params["store"])) {
                $params["store"] = (int)($itemInfo["store"] ?? 0);
            }
            // 如果没有价格，就默认获取商品自己的价格
            if (!isset($params["price"])) {
                $params["price"] = (int)($itemInfo["price"] ?? 0);
            }
        } else {
            if ($params["is_total_store"] == self::IS_TRUE && (isset($params["store"]) || isset($params["price"]))) {
                throw new ErrorException(ErrorCode::DISTRIBUTOR_ITEM_ERROR, "商品总部发货无法更新库存和价格");
            }
        }
        // 如果扔存在商品价格，则检查价格
        if (isset($params['price'])) {
            // 检查商品价格是否大于活动的价格
            (new DistributorItemsService())->checkItemPrice($itemInfo["company_id"], [(int)$itemInfo["default_item_id"]], [(int)$itemInfo["item_id"] => $params['price']]);
        }
    }

    /**
     * 更新库存信息(只做增减，不覆盖)
     * @param array $filter 过滤条件
     * @param int $store 库存值
     * @return int 受影响的行数
     */
    public function updateStore(array $filter, int $store): int
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->getRepository()->table);

        if ($store > 0) {
            $qb = $qb->set("store", sprintf("store + %d", $store));
        } elseif ($store < 0) {
            // 取绝对值
            $store = abs($store);
            $filter["store|gte"] = $store;
            $qb = $qb->set("store", sprintf("store - %d", $store));
        } else {
            return 0;
        }

        $this->filter($filter, $qb);

        $row = $qb->execute();
        if ($row == 0) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_ITEM_ERROR, "店铺商品的库存操作有误");
        }
        return $row;
    }

    /**
     * 获取二维码的图片url
     * @param int $companyId 企业id
     * @param string $wxaAppid 微信小程序的appid
     * @param int $distributorId 店铺id
     * @param int $itemId 商品id
     * @return array
     */
    public function getQRCodeUrl(int $companyId, string $wxaAppid, int $distributorId, int $itemId): array
    {
        $cacheService = new RedisCacheService($companyId, sprintf("distributor_%d_item_qr_code", $distributorId));
        // 获取上文件的配置信息
        $tokenService = UploadTokenFactoryService::create("image");
        $tokenInfo = $tokenService->getToken($companyId);

        // 获取缓存内容
        $result = $cacheService->hashGet([$itemId]);
        // 如果hash缓存里不存在
        if (!isset($result[$itemId])) {
            $qrCodeContent = (new ItemsService())->getDistributionGoodsWxaCode($wxaAppid, $itemId, $distributorId, 0);
            $uploadService = new UploadService($companyId, $tokenService);
            $uri = $uploadService->upload($qrCodeContent, UploadTokenAbstract::GROUP_DISTRIBUTOR_ITEM_QR_CODE) ? $uploadService->getUri() : "";
            // 设置hash缓存
            $cacheService->hashSet([$distributorId => $uri]);
        } else {
            $uri = (string)$result[$itemId];
        }
        // 获取完整的url路径
        $url = sprintf("%s/%s", trim($tokenInfo["token"]["domain"] ?? "", "/"), trim($uri, "/"));
        return [
            "url" => $url
        ];
    }
}
