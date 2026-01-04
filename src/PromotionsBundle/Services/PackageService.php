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

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use OrdersBundle\Services\CartService;
use PromotionsBundle\Entities\PackageItemPromotions;
use PromotionsBundle\Entities\PackageMainItemPromotions;
use PromotionsBundle\Entities\PackagePromotions;
use PromotionsBundle\Traits\CheckPromotionsRules;
use SalespersonBundle\Jobs\SalespersonItemsShelvesJob;

class PackageService
{
    use CheckPromotionsRules;

    /**
     * packagePromotions Repository类
     */
    private $packageRepository;
    /**
     * packageMainItemPromotions Repository类
     */
    private $packageMainItemRepository;
    /**
     * packageItemPromotions Repository类
     */
    private $packageItemRepository;

    public function __construct()
    {
        $this->packageRepository = app('registry')->getManager('default')->getRepository(PackagePromotions::class);
        $this->packageMainItemRepository = app('registry')->getManager('default')->getRepository(PackageMainItemPromotions::class);
        $this->packageItemRepository = app('registry')->getManager('default')->getRepository(PackageItemPromotions::class);
    }

    /**
     * 获取组合商品信息
     * @param int $companyId 公司id
     * @param int $packageId 组合商品id
     * @return mixed
     */
    public function getPackageInfo($companyId, $packageId)
    {
        $packageParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $info = $this->packageRepository->getInfo($packageParams);
        if (!$info) {
            return [];
        }
        $info['main_item_price'] = (int)$info['main_item_price'];
        $packageItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $relLists = $this->packageItemRepository->lists($packageItemParams, '*', 1, 1000, ["created" => "DESC"]);
        $itemIds = array_column($relLists['list'], 'item_id');
        $info['package_items'] = $itemIds;
        $itemIds[] = $info['main_item_id'];
        foreach ($relLists['list'] as $v) {
            $info['new_price'][$v['item_id']] = (int)$v['package_price'];
        }
        //获取组合活动包含的商品的所有明细
        $itemService = new ItemsService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($filter);
        foreach ($itemsList['list'] as $key => &$items) {
            if (isset($info['new_price'][$items['item_id']])) {
                $items['new_price'] = $info['new_price'][$items['item_id']];
            }
        }
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        $relItems = [];
        foreach ($relLists['list'] as $value) {
            if (!($itemdata[$value['item_id']] ?? [])) {
                $value['status'] = 'invalid';
            } else {
                $value['status'] = 'valid';
                $relItems[] = $itemdata[$value['item_id']];
            }
        }
        $itemdata[$info['main_item_id']]['price'] = (int)$info['main_item_price'];
        $info['items'] = $itemsList['list'];
        $info['mainItem'][] = $itemdata[$info['main_item_id']];
        $info['itemTreeLists'] = $relItems ?? [];
        if ($info['itemTreeLists']) {
            $info['itemTreeLists'] = $this->formatItemsList($info['itemTreeLists']);
        }
        // 查询主商品sku列表
        $packageMainItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $main_item_list = $this->packageMainItemRepository->lists($packageMainItemParams, '*', 1, 1000);
        $main_item_data = array_column($main_item_list['list'], null, 'main_item_id');
        $main_item_ids = array_column($main_item_list['list'], 'main_item_id');
        $main_filter = ['company_id' => $companyId, 'item_id' => $main_item_ids];
        $mainItemsList = $itemService->getSkuItemsList($main_filter);
        $main_items = [];
        foreach ($mainItemsList['list'] as $key => $_main_items) {
            $main_items[] = [
                'item_id' => $_main_items['item_id'],
                'item_title' => $_main_items['item_name'],
                'item_price' => (int)$main_item_data[$_main_items['item_id']]['main_item_price'],
                'item_type' => $_main_items['item_type'],
                'item_spec_desc' => $_main_items['item_spec_desc'] ?? '',
            ];
        }
        $info['main_items'] = $main_items;
        return $info;
    }

    /**
     * 商品sku列表，格式化为商品列表，商品包含sku格式
     */
    public function formatItemsList($list)
    {
        if (!$list) {
            return [];
        }

        $result = [];
        foreach ($list as $row) {
            $itemId = $row['default_item_id'] ?: $row['item_id'];

            if (!isset($result[$itemId])) {
                // $row['item_id'] = $itemId;
                $result[$itemId] = $row;
            }

            // 如果为多规格
            if ($row['nospec'] === false || $row['nospec'] === 'false' || $row['nospec'] === 0 || $row['nospec'] === '0') {
                $result[$itemId]['spec_items'][] = $row;
            }
        }

        $res = [];
        foreach ($result as $value) {
            $res[] = $value;
        }
        return $res;
    }

    public function getPackageInfoFront($companyId, $packageId, $authorizerAppId = null)
    {
        $packageParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $info = $this->packageRepository->getInfo($packageParams);

        if (!$info) {
            throw new ResourceException(trans('PromotionsBundle.package_product_not_found'));
        }
        $itemService = new ItemsService();
        // 查询主商品的skuList start
        $packageMainItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $main_item_list = $this->packageMainItemRepository->lists($packageMainItemParams, '*', 1, 1000);
        $main_item_ids = array_column($main_item_list['list'], 'main_item_id');
        $main_filter = ['company_id' => $companyId, 'item_id' => $main_item_ids];
        $mainItemsList = $itemService->getSkuItemsList($main_filter);

        $mainItemdata = array_column($mainItemsList['list'], null, 'item_id');
        $mainRelItems = [];

        foreach ($main_item_list['list'] as $value) {
            if (!($mainItemdata[$value['main_item_id']] ?? [])) {
                $value['status'] = 'invalid';
            } else {
                $value['status'] = 'valid';
                $mainRelItems[] = $mainItemdata[$value['main_item_id']];
            }
            $mainPackagePrice[$value['main_item_id']]['market_price'] = (int)$mainItemdata[$value['main_item_id']]['price'];// 组合的原价
            $mainPackagePrice[$value['main_item_id']]['price'] = (int)$value['main_item_price'];// 组合的销售价
        }
        $mainItemTreeLists = $mainRelItems ?? [];
        $mainItemInfo = [];
        if ($mainItemTreeLists) {
            $mainItemTreeLists = $this->formatPackageItemsList($mainItemTreeLists);
            $mainItem = $mainItemTreeLists[0];
            if (isset($mainItem['spec_items'])) {
                $limitItemIds = array_column($mainItem['spec_items'], 'item_id');
                unset($mainItem['spec_items']);
                $mainItemInfo = $itemService->getItemsDetail($mainItem['itemId'], $authorizerAppId, $limitItemIds, $companyId);
            } else {
                $mainItemInfo = $itemService->getItemsDetail($mainItem['itemId'], $authorizerAppId);
            }
        }
        // 最低的原价
        $_mainPackageMarketPrice = array_column($mainPackagePrice, 'market_price');
        $mainItemInfo['price'] = min($_mainPackageMarketPrice);
        // 最低销售价
        $_mainPackagePrice = array_column($mainPackagePrice, 'price');
        $mainItemInfo['package_price'] = min($_mainPackagePrice);
        $info['mainItem'] = $mainItemInfo;
        $info['main_package_price'] = $mainPackagePrice;
        // 查询主商品的skuList end

        $packageItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $relLists = $this->packageItemRepository->lists($packageItemParams, '*', 1, 1000, ["created" => "DESC"]);
        $itemIds = array_column($relLists['list'], 'item_id');
        //获取组合活动包含的子商品的所有明细
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($filter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');
        $relItems = [];
        $package_markte_price = $package_price = [];
        foreach ($relLists['list'] as $value) {
            if (!($itemdata[$value['item_id']] ?? [])) {
                $value['status'] = 'invalid';
            } else {
                $value['status'] = 'valid';
                $relItems[] = $itemdata[$value['item_id']];
            }
            // 原价
            $info['package_price'][$value['item_id']]['market_price'] = (int)$value['price'];

            $default_item_id = $itemdata[$value['item_id']]['default_item_id'];
            $package_markte_price[$default_item_id][] = (int)$value['price'];
            $info['package_price'][$value['item_id']]['price'] = (int)$value['package_price'];
            $package_price[$default_item_id][] = (int)$value['package_price'];
        }
        $info['itemLists'] = [];
        $itemTreeLists = $relItems ?? [];
        if ($itemTreeLists) {
            $itemTreeLists = $this->formatPackageItemsList($itemTreeLists);
            foreach ($itemTreeLists as $v) {
                if (isset($v['spec_items'])) {
                    $limitItemIds = array_column($v['spec_items'], 'item_id');
                    unset($v['spec_items']);
                    $itemInfo = $itemService->getItemsDetail($v['itemId'], $authorizerAppId, $limitItemIds, $companyId);
                } else {
                    $itemInfo = $itemService->getItemsDetail($v['itemId'], $authorizerAppId);
                }
                $itemInfo['price'] = min($package_markte_price[$itemInfo['default_item_id']]);
                $itemInfo['package_price'] = min($package_price[$itemInfo['default_item_id']]);
                $info['itemLists'][] = $itemInfo;
            }
        }

        return $info;
    }

    /**
     * 获取组合商品的所有商品价格
     * @param int $companyId 公司id
     * @param int $packageId 组合商品id
     * @return mixed
     */
    public function getPackageInfoPrice($companyId, $packageId)
    {
        $packageParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $info = $this->packageRepository->getInfo($packageParams);
        // 查询主商品sku价格
        $mainItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $mainRelList = $this->packageMainItemRepository->lists($mainItemParams, '*', 1, 1000);
        foreach ($mainRelList['list'] as $v) {
            $info['new_price'][$v['main_item_id']] = (int)$v['main_item_price'];
        }

        $packageItemParams = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        $relLists = $this->packageItemRepository->lists($packageItemParams, '*', 1, 1000, ["created" => "DESC"]);
        $itemIds = array_column($relLists['list'], 'item_id');
        $itemIds[] = $info['main_item_id'];
        foreach ($relLists['list'] as $v) {
            $info['new_price'][$v['item_id']] = (int)$v['package_price'];
        }
        return $info;
    }

     /**
     * 获取某一个商品所有的组合商品活动列表带其他筛选条件
     * @param int $companyId 公司id
     * @param int $itemId 商品id
     * @param int $page 分页页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序字段
     * @return mixed
     */
    public function getPackageListByItemsId($companyId, $itemId, $page = 1, $pageSize = 6, $orderBy = ["start_time" => "DESC"], $otherFilter = [])
    {
        if (!$itemId) {
            return ['list' => [], 'total_count' => 0];
        }
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getItemsDetail($itemId);
        if (!$itemInfo) {
            return ['list' => [], 'total_count' => 0];
        }
        // 合并其他筛选条件，并加入店铺筛选
        if (isset($otherFilter['distributor_id']) && $otherFilter['distributor_id'] > 0) {
            $filter['source_id'] = $otherFilter['distributor_id'];
            $filter['source_type'] = 'distributor';
        } 
        $filter['company_id'] = $companyId;
        $filter['goods_id'] = $itemInfo['goods_id'];
        $filter['package_status'] = 'AGREE';
        $filter['start_time|lt'] = time();
        $filter['end_time|gt'] = time();
        $result = $this->packageRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!empty($resultp['list'])) {
            foreach ($result['list'] as &$v) {
                if ($v['start_time'] > time()) {
                    $v['status'] = 'waiting';
                } elseif ($v['end_time'] < time()) {
                    $v['status'] = 'end';
                } else {
                    $v['status'] = 'ongoing';
                }
            }
        }
        return $result;
    }

    /**
     * 获取所有的组合商品活动列表
     * @param int $companyId 公司id
     * @param string $status 活动状态 waiting 等待开启| ongoing 进行中| end 已结束| all 全部活动
     * @param int $page 分页页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序字段
     * @return mixed
     */
    public function getPackageList($companyId, $status, $sourceType, $sourceId, $page, $pageSize, $orderBy = ["created" => "DESC"])
    {
        $filter['company_id'] = $companyId;
        $filter['package_status'] = 'AGREE';
        switch ($status) {
            case 'waiting':
                $filter['start_time|gt'] = time();
                break;
            case 'ongoing':
                $filter['start_time|lt'] = time();
                $filter['end_time|gt'] = time();
                break;
            case 'end':
                $filter['end_time|lt'] = time();
                break;
            default:
                break;

        }

        if ($sourceId > 0) {
            switch ($sourceType) {
                case 'distributor'://按店铺ID筛选
                    $filter['source_id'] = $sourceId;
                    $filter['source_type'] = $sourceType;
                    break;
            }
        }

        $result = $this->packageRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$v) {
            if ($v['start_time'] > time()) {
                $v['status'] = 'waiting';
            } elseif ($v['end_time'] < time()) {
                $v['status'] = 'end';
            } else {
                $v['status'] = 'ongoing';
            }
        }
        return $result;
    }

    /**
     * 获取所有的组合商品活动列表
     * @param int $companyId 公司id
     * @param string $status 活动状态 waiting 等待开启| ongoing 进行中| end 已结束| all 全部活动
     * @param int $page 分页页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序字段
     * @return mixed
     */
    public function getPackageFilterList($filter, $page, $pageSize, $orderBy = ["created" => "DESC"])
    {
        $result = $this->packageRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        return $result;
    }

    /**
     * 创建组合商品
     * @param array $params 商品参数
     * @return mixed
     * @throws \Exception
     */
    public function createPackagePromotions(array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params = $this->formatPackageParams($params);
            $this->checkActivity($params);

            $this->checkActivityValidByPackage($params);

            $itemService = new ItemsService();
            $mainItemIds = array_column($params['main_items'], 'item_id');
            $filter = [
                'company_id' => $params['company_id'],
                'item_id' => $mainItemIds,
            ];
            $itemsInfo = $itemService->getSkuItemsList($filter);
            $main_goods_id = $itemsInfo['list'][0]['goods_id'];

            $main_item_id = $params['main_items'][0]['item_id'];
            $main_item_price = $params['main_items'][0]['item_price'];
            $data = [
                'company_id' => $params['company_id'],
                'package_name' => $params['package_name'],
                'goods_id' => $main_goods_id,
                'main_item_id' => $main_item_id,
                'main_item_price' => bcmul($main_item_price, 100, 2),
                'valid_grade' => implode(',', $params['valid_grade']),
                'used_platform' => $params['used_platform'],
                'free_postage' => 1,
                'package_total_price' => 0, // 先默认为0,关联商品插入完成再计算
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'package_status' => 'AGREE',
                'reason' => '',
                'source_id' => $params['source_id'],
                'source_type' => $params['source_type'],
            ];
            $result = $this->packageRepository->create($data);
            // 创建主商品数据
            $result['main_items'] = $result ? $this->createPackageMainItemRel($result['package_id'], $params, $itemsInfo) : [];

            $result['items'] = $result ? $this->createPackagePromotionsItemRel($result, $params, $packageTotalPrice) : [];
            $data['package_total_price'] = $packageTotalPrice;
            $result = $this->packageRepository->updateOneBy(['package_id' => $result['package_id']], ['package_total_price' => $packageTotalPrice]);

            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['package_id'], 'package'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 修改组合商品
     * @param int $packageId 组合商品id
     * @param array $params 商品参数
     * @return mixed
     * @throws \Exception
     */
    public function updatePackagePromotions($packageId, array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params = $this->formatPackageParams($params);

            $this->checkActivity($params);

            $this->checkActivityValidByPackage($params, $packageId);

            $itemService = new ItemsService();
            $mainItemIds = array_column($params['main_items'], 'item_id');
            $filter = [
                'company_id' => $params['company_id'],
                'item_id' => $mainItemIds,
            ];
            $itemsInfo = $itemService->getSkuItemsList($filter);
            $main_goods_id = $itemsInfo['list'][0]['goods_id'];

            $main_item_id = $params['main_items'][0]['item_id'];
            $main_item_price = $params['main_items'][0]['item_price'];

            $data = [
                'company_id' => $params['company_id'],
                'package_name' => $params['package_name'],
                'goods_id' => $main_goods_id,
                'main_item_id' => $main_item_id,
                'main_item_price' => bcmul($main_item_price, 100, 2),
                'valid_grade' => implode(',', $params['valid_grade']),
                'used_platform' => $params['used_platform'],
                'free_postage' => 1,
                'package_total_price' => 0, // 先默认为0,关联商品插入完成再计算
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'package_status' => 'AGREE',
                'reason' => '',
            ];
            $result = $this->packageRepository->updateOneBy(['package_id' => $packageId], $data);
            // 创建主商品数据
            $result['main_items'] = $result ? $this->updatePackageMainItemRel($result['package_id'], $params, $itemsInfo) : [];

            $result['items'] = $result ? $this->updatePackagePromotionsItemRel($result, $params, $packageTotalPrice) : [];
            $data['package_total_price'] = $packageTotalPrice;
            $result = $this->packageRepository->updateOneBy(['package_id' => $result['package_id']], ['package_total_price' => $packageTotalPrice]);

            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['package_id'], 'package'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 取消组合商品活动
     * @param int $packageId 组合商品id
     * @return mixed
     * @throws \Exception
     */
    public function cancelPackagePromotions($packageId, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $companyId,
                'package_id' => $packageId,
            ];
            $params = [
                'end_time' => time() - 1
            ];
            $result = $this->packageRepository->updateOneBy($filter, $params);
            $this->packageItemRepository->updateBy($filter, $params);
            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($companyId, $packageId, 'package'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException('取消失败');
        }
    }

    /**
     * 创建组合商品关联商品
     * @param array $package 组合商品主表信息
     * @param array $params 组合商品参数
     * @param int $packageTotalPrice 组合商品总价格
     * @return array
     */
    private function createPackagePromotionsItemRel(array $package, array $params, &$packageTotalPrice)
    {
        $itemService = new ItemsService();
        $itemIds = array_column($params['items'], 'item_id');
        $itemPrice = array_bind_key($params['items'], 'item_id');
        $filter = [
            'company_id' => $params['company_id'],
            'item_id' => $itemIds,
        ];
        $itemsInfo = $itemService->getSkuItemsList($filter);
        $goodsId = array_column($itemsInfo['list'], 'goods_id');
        $items = array_column($itemsInfo['list'], null, 'item_id');
        $num = count(array_unique($goodsId));
        if ($num > 10) {
            throw new ResourceException(trans('PromotionsBundle.package_max_10_products'));
        }
        $result = [];
        foreach ($params['items'] as $data) {
            $itemId = $data['item_id'];
            if ($items[$itemId]['special_type'] == 'drug') {
                throw new ResourceException(trans('PromotionsBundle.prescription_drug_not_allowed'));
            }
            if (isset($items[$itemId])) {
                if (isset($isShowIds[$items[$itemId]['default_item_id']])) {
                    $isShow = false;
                } else {
                    $packageTotalPrice += $itemPrice[$itemId]['new_price'];
                    $isShowIds[$items[$itemId]['default_item_id']] = true;
                    $isShow = true;
                }
            }
            $data['package_id'] = $package['package_id'];
            $data['item_id'] = $itemId;
            $data['is_show'] = $isShow;
            $data['default_item_id'] = $items[$itemId]['default_item_id'] ?? '';
            $data['company_id'] = $params['company_id'];
            $data['item_spec_desc'] = $items[$itemId]['item_spec_desc'] ?? '';
            $data['title'] = $items[$itemId]['itemName'] ?? '';
            $data['image_default_id'] = isset($items[$itemId]['pics']) && !empty($items[$itemId]['pics']) ? $items[$itemId]['pics'][0] : '';
            $data['package_price'] = bcmul($itemPrice[$itemId]['new_price'], 100, 2);
            $data['price'] = $items[$itemId]['price'] ?? '';
            $data['status'] = 1;
            $data['start_time'] = $package['start_time'];
            $data['end_time'] = $package['end_time'];
            $result[] = $this->packageItemRepository->create($data);
        }
        return $result;
    }

    /**
     * 创建组合商品主商品关联数据
     * @param array $package 组合商品主表信息
     * @param array $params 组合商品参数
     * @param array $itemsInfo 主商品的sku详情
     * @return array
     */
    private function createPackageMainItemRel($package_id, array $params, array $itemsInfo)
    {
        $items_id = array_column($itemsInfo['list'], 'item_id');
        $itemPrice = array_bind_key($params['main_items'], 'item_id');

        $result = [];
        foreach ($itemsInfo['list'] as $data) {
            $_data = [];
            $_data['package_id'] = $package_id;
            $_data['company_id'] = $params['company_id'];
            $_data['goods_id'] = $data['goods_id'];
            $_data['main_item_id'] = $data['item_id'];
            $_data['main_item_price'] = bcmul($itemPrice[$data['item_id']]['item_price'], 100, 0);
            $result[] = $this->packageMainItemRepository->create($_data);
        }
        return $result;
    }

    /**
     * 修改组合商品主商品关联数据
     * @param array $package 组合商品主表信息
     * @param array $params 组合商品参数
     * @param array $itemsInfo 主商品的sku详情
     * @return array
     */
    private function updatePackageMainItemRel($package_id, array $params, array $itemsInfo)
    {
        $filter = [
            'package_id' => $package_id,
            'company_id' => $params['company_id'],
        ];
        $this->packageMainItemRepository->deleteBy($filter);

        return $this->createPackageMainItemRel($package_id, $params, $itemsInfo);
    }

    /**
     * 修改组合商品关联商品
     * @param array $package 组合商品主表信息
     * @param array $params 组合商品参数
     * @param int $packageTotalPrice 组合商品总价格
     * @return array
     */
    private function updatePackagePromotionsItemRel($package, $params, &$packageTotalPrice)
    {
        $this->deletePackagePromotionsItemRel($params['company_id'], $package['package_id']);
        $result = $this->createPackagePromotionsItemRel($package, $params, $packageTotalPrice);
        return $result;
    }


    /**
     * 删除组合商品关联商品
     * @param int $companyId 公司id
     * @param int $packageId 组合商品活动id
     * @return array
     */
    public function deletePackagePromotionsItemRel($companyId, $packageId)
    {
        $filter = [
            'package_id' => $packageId,
            'company_id' => $companyId,
        ];
        return $this->packageItemRepository->deleteBy($filter);
    }

    /**
     * 校验组合活动参数
     * @param $params
     * @return bool
     */
    private function checkActivity($params)
    {
        if (!$params['start_time'] || !$params['end_time']) {
            throw new ResourceException('活动时间必填');
        }

        if ($params['start_time'] > $params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_end_time'));
        }

        if (!isset($params['items']) || !is_array($params['items'])) {
            throw new ResourceException(trans('PromotionsBundle.no_activity_products_please_add'));
        }

//        if (count($params['items']) > 10) {
//            throw new ResourceException('适用商品最大选择10个');
//        }
        if (!isset($params['main_items'])) {
            throw new ResourceException(trans('PromotionsBundle.please_select_main_product'));
        } else {
            $main_item_ids = array_column($params['main_items'], 'item_id');
            if (!$main_item_ids) {
                throw new ResourceException(trans('PromotionsBundle.please_select_main_product'));
            }
        }

        foreach ($params['items'] as $v) {
            if (!isset($v['item_id']) || !$v['item_id']) {
                throw new ResourceException(trans('PromotionsBundle.applicable_products_selection_error'));
            }
            if (!isset($v['new_price']) || !$v['new_price']) {
                throw new ResourceException(trans('PromotionsBundle.applicable_products_price_required'));
            }
            if ($v['item_id'] == $params['main_item']) {
                throw new ResourceException(trans('PromotionsBundle.package_cannot_contain_main_product'));
            }
        }

        if (!isset($params['valid_grade']) || !$params['valid_grade']) {
            throw new ResourceException(trans('PromotionsBundle.please_select_applicable_members'));
        }

        if (!isset($params['used_platform']) && !in_array($params['used_platform'], [0])) {
            throw new ResourceException(trans('PromotionsBundle.applicable_platform_required'));
        }

        // 普通商品和跨境商品不能作为组合商品售卖
        $packageItemIds = array_column($params['main_items'], 'item_id');
        $packageItemIds = array_merge($packageItemIds, array_column($params['items'], 'item_id'));
        $itemService = new ItemsService();
        $items = $itemService->getItemsLists(['item_id' => $packageItemIds], 'item_id,type');
        $type = -1;
        foreach ($items as $item) {
            if ($type == -1) {
                $type = $item['type'];
            } else {
                if ($type != $item['type']) {
                    throw new ResourceException(trans('PromotionsBundle.regular_crossborder_cannot_package'));
                }
            }
        }

        return true;
    }

    /**
     * 商品sku列表，格式化为商品列表，商品包含sku格式
     * 单独为组合商品使用
     */
    public function formatPackageItemsList($list)
    {
        if (!$list) {
            return [];
        }

        $result = [];
        foreach ($list as $row) {
            $itemId = $row['default_item_id'] ?: $row['item_id'];

            if (!isset($result[$itemId])) {
                // $row['item_id'] = $itemId;
                $result[$itemId] = $row;
            }

            // 如果为多规格
            if ($row['nospec'] === false || $row['nospec'] === 'false' || $row['nospec'] === 0 || $row['nospec'] === '0') {
                $result[$itemId]['spec_items'][] = $row;
            }
        }
        $res = [];
        foreach ($result as $value) {
            $res[] = $value;
        }
        return $res;
    }

    public function formatPackageParams($params)
    {
        foreach ($params['main_items'] as $key => $value) {
            $params['main_items'][$key]['item_price'] = sprintf('%.2f', floatval($value['item_price']));
        }
        foreach ($params['items'] as $key => $value) {
            $params['items'][$key]['new_price'] = sprintf('%.2f', floatval($value['new_price']));
        }
        return $params;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->packageRepository->$method(...$parameters);
    }
}
