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

namespace SupplierBundle\Services;

use GoodsBundle\Services\ItemsService;
use GoodsBundle\Events\ItemDeleteEvent;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemStoreService;
use SupplierBundle\Entities\SupplierItems;
use CompanysBundle\Services\ArticleService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsCategoryService;
use SupplierBundle\Entities\SupplierItemsAttr;
use GoodsBundle\Services\ItemsAttributesService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use GoodsBundle\Services\ItemRelAttributesService;
use OrdersBundle\Services\ShippingTemplatesService;
use EspierBundle\Services\UploadTokenFactoryService;

class SupplierItemsService
{
    use CheckPromotionsValid;

    /**
     * @var \SupplierBundle\Repositories\SupplierItemsRepository
     */
    public $repository;
    public $supplierItemsAttrRepository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(SupplierItems::class);
        $this->supplierItemsAttrRepository = app('registry')->getManager('default')->getRepository(SupplierItemsAttr::class);
    }

    /**
     * 保存上传的商品数据
     * @param array $params
     * @return
     * @throws \Exception
     */
    public function updateUploadItems($params = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemsService = new ItemsService();
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $itemsAttributesService = new ItemsAttributesService();

            $itemData = $params;
            $goodsId = $params['goods_id'];
            $itemId = $params['item_id'];
            $companyId = $params['company_id'];
            $defaultItemId = $params['default_item_id'];

            // 商品主类目字段名切换
            if (isset($itemData['item_main_cat_id']) && $itemData['item_main_cat_id']) {
                $itemData['item_category'] = $itemData['item_main_cat_id'];
                unset($itemData['item_main_cat_id']);
            }

            unset($itemData['item_id']);

            $itemsResult = $this->repository->updateOneBy(['item_id' => $itemId], $itemData);

            // 更新库存
            if (isset($params['store']) && $params['store'] !== '') {
                $store = intval($params['store']);
                //只更新已经发布的供应商商品的库存
                $rsItem = $itemsService->itemsRepository->getInfo(['supplier_item_id' => $itemId]);
                if ($rsItem) {
                    //更新数据库内存
                    $itemsService->itemsRepository->update($rsItem['item_id'], [
                        'store' => $store,
                        'start_num' => $params['start_num'], // 起订量
                    ]);
                    //更新redis内存
                    $itemStoreService = new ItemStoreService();
                    $itemStoreService->saveItemStore($rsItem['item_id'], $store);
                }
            }

            // 检查商品价格是否大于活动的价格
            // if (isset($params['price']) && $params['price']) {
            //     $itemPrices[$itemId] = $params['price'];
            //     $this->checkItemPrice($companyId, [$goodsId], $itemPrices);
            // }

            // 商品销售分类
            if (isset($params['item_category']) && $params['item_category']) {
                $this->itemsRelCats($params, $defaultItemId);
            }

            // 如果品牌发生变化，要先删除原有的品牌
            if (isset($params['brand_id']) && $params['brand_id']) {
                $this->itemsRelBrand($params, $defaultItemId);
            }

            // 关联参数
            if (isset($params['item_params']) && $params['item_params']) {
                $this->itemsRelParams($params, $defaultItemId);
            }

            // 商品规格，必须和主类目一起导入
            if (isset($params['item_spec']) && $params['item_spec']) {
                $sort = 0;
                $delFilter = [
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'attribute_type' => 'item_spec',
                ];
                $SupplierItemsAttrService->setDelData($delFilter);

                foreach ($params['item_spec'] as $row) {
                    $tmpValue = $itemsAttributesService->getAttrValue(['attribute_value_id' => $row['spec_value_id']]);
                    $tempSort = $tmpValue['attribute_sort'] ?? 0;
                    $paramsData = [
                        'company_id' => $companyId,
                        'item_id' => $itemId,
                        'attribute_id' => $row['spec_id'],
                        'attribute_type' => 'item_spec',
                    ];
                    $attrData = [
                        'item_spec' => [
                            'attribute_sort' => $tempSort + $sort,
                            'image_url' => '',
                            'attribute_value_id' => $row['spec_value_id'],
                            'custom_attribute_value' => '',
                        ]
                    ];
                    $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
                    $sort++;
                }

                $SupplierItemsAttrService->execDelData($delFilter);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new \Exception($e->getMessage());
        }
        return $itemsResult;
    }

    /**
     * 修改商品运费模板
     *
     * @param array params 提交的商品数据
     * @return array
     */
    public function setItemsTemplate($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemsService = new ItemsService();
            foreach ($params['item_id'] as $v) {
                $rsItems = $this->repository->getLists(['default_item_id' => $v], 'company_id, item_id');
                if ($params['company_id'] != $rsItems[0]['company_id']) {
                    throw new ResourceException(trans('SupplierBundle.no_permission_edit_item'));
                }
                $itemsResult = $this->repository->updateBy(['default_item_id' => $v], ['templates_id' => $params['templates_id']]);

                //更新平台商品表
                $_filter = ['supplier_item_id' => array_column($rsItems, 'item_id')];
                if ($itemsService->itemsRepository->count($_filter)) {
                    $itemsService->itemsRepository->updateBy($_filter, ['templates_id' => $params['templates_id']]);
                }
            }
            $conn->commit();
            return $itemsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 处理商品列表显示的库存
     * @param $result
     * @return mixed
     */
    public function dealListStore($result)
    {
        if (!$result['list']) {
            return $result;
        }
        $goods_num = [];
        $goods_ids = array_column($result['list'], 'goods_id');
        $rsGoods = $this->repository->getLists(['company_id' => $result['list'][0]['company_id'], 'goods_id' => $goods_ids], 'goods_id, store');
        foreach ($rsGoods as $goods) {
            $goods_num[$goods['goods_id']][] = $goods['store'];
        }
        foreach ($result['list'] as $key => &$v) {
            $v['store'] = 0;
            if (!isset($goods_num[$v['goods_id']])) {
                continue;
            }
            $v['store'] = array_sum($goods_num[$v['goods_id']]);
        }
        return $result;
    }

    public function getSkuItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $itemsList = $this->repository->lists($filter, '*', $page, $pageSize, $orderBy);
        $itemsList = $this->replaceSkuSpec($itemsList);
        return $itemsList;
    }

    public function replaceSkuSpec($itemsList)
    {
        $itemIds = array_column($itemsList['list'], 'item_id');
        // 规格等数据
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $attrList = $SupplierItemsAttrService->getItemRelAttr($itemIds, ['item_spec']);
        // $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);
        }

        foreach ($itemsList['list'] as &$itemRow) {
            // 规格转成bool
            $itemRow['nospec'] = ($itemRow['nospec'] === 'true' || $itemRow['nospec'] === true || $itemRow['nospec'] === 1 || $itemRow['nospec'] === '1') ? true : false;
            $itemRow['item_type'] = $itemRow['item_type'] ?: 'services';
            $itemRow['type_labels'] = [];
            if (!$itemRow['default_item_id']) {
                $itemRow['default_item_id'] = $itemRow['item_id'];
            }
            if (isset($attrData['item_spec']) && isset($attrData['item_spec'][$itemRow['item_id']])) {
                $itemSpecStr = [];
                foreach ($attrData['item_spec'][$itemRow['item_id']] as $row) {
                    $itemRow['item_spec'][] = $row;
                    $itemSpecStr[] = $row['spec_name'] . ':' . $row['spec_value_name'];
                }
                $itemRow['item_spec_desc'] = implode(',', $itemSpecStr);
            }
        }
        return $itemsList;
    }

    /**
     * 添加(更新)供应商商品
     */
    public function addItems($params, $isCreateRelData = true)
    {
        $params['item_type'] = $params['item_type'] ?? "services";
        $params['recommend_items'] = $params['recommend_items'] ?? [];
        // $this->itemtypeObject = new $this->itemsTypeClass[$params['item_type']]();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemIds = [];
            // 商品通用参数
            $data = $this->commonParams($params);

            $data['supplier_id'] = $params['supplier_id'] ?? 0;
            $data['is_market'] = intval($params['is_market'] ?? 1);//供应商的商品可售状态

            //$updateItemInfo = [];
            $goodsId = 0;
            if (isset($params['goods_id']) && $params['goods_id']) {
                $goodsId = $params['goods_id'];//导入数据的时候，不执行 processUpdateItem
            }

            //删除多规格商品里已经不存在的规格
            if (!$goodsId && isset($params['item_id']) && $params['item_id']) {
                $itemId = $params['item_id'];//更新的商品ID, 如果是多规格则为默认的商品id
                $updateItemInfo = $this->processUpdateItem($itemId, $params['company_id'], $params);
                $goodsId = $updateItemInfo['goods_id'];
            }

            //支持导入的 default_item_id
            $defaultItemId = $params['default_item_id'] ?? null;
            $itemPrices = [];
            // 如果是多规格
            if (isset($params['nospec']) && ($params['nospec'] === false || $params['nospec'] === 'false' || $params['nospec'] === 0 || $params['nospec'] === '0')) {
                $specImages = [];
                if (isset($params['spec_images'])) {
                    $tempSpecImages = json_decode($params['spec_images'], true);
                    $specImages = array_column($tempSpecImages, 'item_image_url', 'spec_value_id');
                }
                $data['spec_images'] = $specImages;

                $specItems = json_decode($params['spec_items'], true);
                // 规格数据不能为空
                if (empty($specItems)) {
                    throw new ResourceException(trans('SupplierBundle.please_fill_correct_spec_data'));
                }
                // 如果有外部的商品ID则表示为更新，否则为强制刷新
                $isForceCreate = (isset($params['item_id']) && $params['item_id']) ? false : true;
                $isMiniPrice = 0;
                foreach ($specItems as $row) {
                    //供应商商品的上下架状态，和供应状态同步
                    if ($params['operator_type'] == 'supplier') {
                        $row['approve_status'] = $data['is_market'] ? 'onsale' : 'instock';
                    }
                    $itemsResult = $this->createItems($data, $row, $isForceCreate);
                    $itemIds[] = $itemsResult['item_id'];
                    if ($isMiniPrice == 0) {
                        $isMiniPrice = $row['price'];
                    } elseif (!$defaultItemId && $isMiniPrice > $row['price']) {
                        $isMiniPrice = $row['price'];
                        $defaultItemId = $itemsResult['item_id'];
                    }

                    if (!$defaultItemId && in_array($row['approve_status'], ['onsale', 'only_show', 'offline_sale'])) {
                        $defaultItemId = $itemsResult['item_id'];
                    }
                    $itemPrices[$itemsResult['item_id']] = bcmul($row['price'], 100);

                    //触发事件
                    // $eventData = [
                    //     'item_id' => $itemsResult['item_id'],
                    //     'company_id' => $itemsResult['company_id']
                    // ];
                    // event(new ItemAddEvent($eventData));
                }
                // 如果没有定义默认商品，则默认为第一个
                if (!$defaultItemId) {
                    $defaultItemId = $itemIds[0];
                }
            }
            else {
                //供应商商品的上下架状态，和供应状态同步
                if ($params['operator_type'] == 'supplier') {
                    $params['approve_status'] = $data['is_market'] ? 'onsale' : 'instock';
                }
                // 起订量
                if (isset($params['start_num'])) {
                    $data['start_num'] = $params['start_num'] > 0 ? $params['start_num']: 0;
                }
                $itemsResult = $this->createItems($data, $params);
                $itemIds[] = $itemsResult['item_id'];
                if (!$defaultItemId) {
                    $defaultItemId = $itemsResult['item_id'];
                }
                $itemPrices[$itemsResult['item_id']] = bcmul($params['price'], 100);
                //触发事件
                // $eventData = [
                //     'item_id' => $itemsResult['item_id'],
                //     'company_id' => $itemsResult['company_id']
                // ];
                // event(new ItemAddEvent($eventData));
            }

            if (!$goodsId) {
                $goodsId = $defaultItemId;
            }
            $itemsResult['goods_id'] = $goodsId;

            // 如果设置为赠品，则检查是否有未完成的活动
            if ($itemIds) {
                $itemsService = new ItemsService();
                $rsItems = $itemsService->itemsRepository->getLists(['supplier_item_id' => $itemIds], 'item_id, goods_id, supplier_item_id');
                if ($rsItems) {
                    $item_ids = array_column($rsItems, 'item_id');
                    $goods_ids = array_column($rsItems, 'goods_id');
                    if ($data['is_gift'] == 'true') {
                        $this->checkNotFinishedActivityValid($params['company_id'], $item_ids, $goods_ids);
                    }

                    // 检查商品价格是否大于活动的价格
                    $item_prices = [];
                    foreach ($rsItems as $v) {
                        $price = $itemPrices[$v['supplier_item_id']] ?? 0;
                        if ($price) {
                            $item_prices[$v['item_id']] = $price;
                        }
                    }
                    if ($item_prices) {
                        $this->checkItemPrice($params['company_id'], $goods_ids, $item_prices);
                    }
                }
            }

            $this->repository->updateBy(['item_id' => $itemIds], ['default_item_id' => $defaultItemId, 'goods_id' => $goodsId]);
            $this->repository->updateBy(['default_item_id' => $defaultItemId], ['is_default' => 0]);
            $this->repository->updateBy(['item_id' => $defaultItemId], ['is_default' => 1]);

            if ($isCreateRelData) {
                // 默认商品关联分类
                $this->itemsRelCats($params, $defaultItemId);
                // 关联品牌
                $this->itemsRelBrand($params, $defaultItemId);
                // 关联参数
                $this->itemsRelParams($params, $defaultItemId);
            }
            // 处理关联商品
            // $itemsRecommendService = new ItemsRecommendService();
            // $itemsRecommendService->checkParams($defaultItemId, $params['recommend_items']);
            // $itemsRecommendService->saveItemsRecommendData($params['company_id'], $defaultItemId, $params['recommend_items']);
            // 处理关联商品 end
            // event(new ItemCreateEvent($itemsResult, $itemIds));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->error($e->getFile() . ':' . $e->getLine() . ' => ' . $e->getMessage());
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->error($e->getFile() . ':' . $e->getLine() . ' => ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        // 同步总部商品到门店
        // $this->syncGoods($itemsResult['company_id'], $defaultItemId);
        return $itemsResult;
    }

    private function itemsRelCats($params, $defaultItemId)
    {
        //保存商品分类
        if (isset($params['company_id']) && isset($params['item_category']) && $params['item_category'] && $defaultItemId) {
            $catIds = is_array($params['item_category']) ? $params['item_category'] : [$params['item_category']];
            $itemId = [$defaultItemId];

            $paramsData = [
                'company_id' => $params['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => 0,
                'attribute_type' => 'category',
            ];
            $attrData = [
                'category' => $catIds,
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
            // $itemsService = new ItemsRelCatsService();
            // $result = $itemsService->setItemsCategory($params['company_id'], $itemId, $catIds);
        }
    }

    /**
     * 商品关联品牌 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelBrand($params, $defaultItemId)
    {
        // 保存品牌
        if (isset($params['brand_id']) && trim($params['brand_id'])) {
            $paramsData = [
                'company_id' => $params['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => trim($params['brand_id']),
                'attribute_type' => 'brand',
            ];
            $attrData = [
                'brand' => trim($params['brand_id'])
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
        }
    }

    /**
     * 商品关联参数 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelParams($params, $defaultItemId)
    {
        // 保存参数
        if (isset($params['item_params']) && $params['item_params']) {
            $delFilter = [
                'company_id' => $params['company_id'],
                'item_id' => $defaultItemId,
                'attribute_type' => 'item_params',
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->setDelData($delFilter);
            foreach ($params['item_params'] as $row) {
                $paramsData = [
                    'company_id' => $params['company_id'],
                    'item_id' => $defaultItemId,
                    'attribute_id' => $row['attribute_id'],
                    'attribute_type' => 'item_params',
                ];
                $attrData = [
                    'item_params' => $row
                ];
                $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
            }
            $SupplierItemsAttrService->execDelData($delFilter);
        }
    }

    private function createItems($data, $spec_params, $isForceCreate = false)
    {
        // 商品规格特有参数
        $data = $this->itemSpecParams($data, $spec_params);
        if (isset($spec_params['item_id']) && $spec_params['item_id'] && !$isForceCreate) {
            // 审核重置为 submiting processing 的话，要重置已经同步到商品池的商品数据
            if (isset($data['audit_status']) && in_array($data['audit_status'], ['submiting', 'processing'])) {
                $data['audit_reason'] = '';
                $data['audit_date'] = '';
            }
            $itemsResult = $this->repository->updateOneBy(['item_id' => $spec_params['item_id']], $data);
            // $barcode_item_id = $params['item_id'];
            // $barcode_default_item_id = $itemsResult['default_item_id'];
        } else {
            $data['rebate_type'] = 'default';
            $data['rebate'] = 0;
            $itemsResult = $this->repository->create($data);
            // $barcode_item_id = $itemsResult['item_id'];
            // $barcode_default_item_id = $itemsResult['default_item_id'];
        }
        // 保存条形码
        // $this->saveBarcode($barcode_item_id, $barcode_default_item_id, $data['company_id'], $data['barcode']);
        // if ($data['store'] && $data['store'] > 0) {
        //只更新已经发布的供应商商品的库存
        $itemsService = new ItemsService();
        $rsItem = $itemsService->itemsRepository->getInfo(['supplier_item_id' => $itemsResult['item_id']]);
        if ($rsItem) {
            // 更新数据
            $upData = [
                'store' => $data['store'],   //更新数据库内存
                'pics' => $data['pics'],  // 更新图片
                'approve_status' => $data['approve_status'], // 更新状态
                'item_name' => $data['item_name'],
            ];
            // 审核重置为 submiting processing 的话，要重置已经同步到商品池的商品数据
            if (isset($data['audit_status']) && in_array($data['audit_status'], ['submiting', 'processing'])) {
                $upData['audit_status'] = $data['audit_status'];
                $upData['audit_reason'] = '';
                $upData['audit_date'] = '';
            }
            // 更新成本价
            if (isset($data['cost_price']) && $data['cost_price'] >= 0) {
                $upData['cost_price'] = $data['cost_price'];
            }
            // 更新起订量
            if (isset($data['start_num']) && $data['start_num'] >= 0) {
                $upData['start_num'] = $data['start_num'];
            }
            $itemsService->itemsRepository->update($rsItem['item_id'], $upData);
            //更新redis内存
            $itemStoreService = new ItemStoreService();
            $itemStoreService->saveItemStore($rsItem['item_id'], $data['store']);
        }
        // }

        // 保存参数
        if (isset($spec_params['item_spec'])) {
            $sort = 0;
            $delFilter = [
                'company_id' => $data['company_id'],
                'item_id' => $itemsResult['item_id'],
                'attribute_type' => 'item_spec',
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->setDelData($delFilter);
            foreach ($spec_params['item_spec'] as $row) {
                $itemImageUrl = $data['spec_images'][$row['spec_value_id']] ?? '';
                $tempSort = $row['attribute_sort'] ?? 0;
                $paramsData = [
                    'company_id' => $data['company_id'],
                    'item_id' => $itemsResult['item_id'],
                    'attribute_id' => $row['spec_id'],
                    'attribute_type' => 'item_spec',
                ];
                $attrData = [
                    'item_spec' => [
                        'attribute_sort' => $tempSort + $sort,
                        'image_url' => $itemImageUrl,
                        'attribute_value_id' => $row['spec_value_id'],
                        'custom_attribute_value' => $row['spec_custom_value_name'] ?? null,
                    ]
                ];
                $sort++;
                $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
            }
            $SupplierItemsAttrService->execDelData($delFilter);
        }

        //新增不同类型商品的特殊参数
        // if (method_exists($this->itemtypeObject, 'createRelItem')) {
        //     $itemsResult = $this->itemtypeObject->createRelItem($itemsResult, $params);
        // }

        // 如果是按商品获取积分，则保存关联数据
        // if ('items' == $data['point_access']) {
        //     $itemRelPointAccessService = new ItemRelPointAccessService();
        //     $relPointData = [
        //         'company_id' => $data['company_id'],
        //         'item_id' => $itemsResult['item_id'],
        //         'point' => intval($params['point_num'] ?? 0),
        //     ];
        //     $itemRelPointAccessService->saveOneData($relPointData);
        // }

        return $itemsResult;
    }

    private function itemSpecParams($data, $spec_params)
    {
        // if (!in_array($params['approve_status'], ['onsale', 'offline_sale', 'instock', 'only_show'])) {
        //     throw new ResourceException('请选择正确的商品状态');
        // }

        //修复更新商品报错：请输入正确的分销佣金
        // if (isset($params['rebate'])) {
        //     $params['rebate'] = floatval($params['rebate']);
        // }

        if (empty($data['goods_bn'])) {
            $data['goods_bn'] = $this->__getBn('PC');;
        }

        if (empty($spec_params['item_bn'])) {
            $spec_params['item_bn'] = $this->__getBn('KC');
        }

        $data['item_bn'] = $spec_params['item_bn'] ?? '';
        $data['item_bn'] = trim($data['item_bn']);
        if ($data['item_bn']) {
            $_filter = [
                'company_id' => $data['company_id'],
                'item_bn' => $data['item_bn'],
            ];
            $item = $this->repository->getInfo($_filter);
            if ($item && isset($spec_params['item_id']) && $spec_params['item_id'] != $item['item_id']) {
                throw new ResourceException(trans('SupplierBundle.item_bn_already_exists', ['item_bn' => $data['item_bn']]));
            }else if ($item && !isset($spec_params['item_id'])) {
                throw new ResourceException(trans('SupplierBundle.item_bn_already_exists', ['item_bn' => $data['item_bn']]));
            }

            $itemsService = new ItemsService();
            $item = $itemsService->itemsRepository->getInfo($_filter);
            if ($item && isset($spec_params['item_id']) &&  $spec_params['item_id'] != $item['supplier_item_id']) {
                throw new ResourceException(trans('SupplierBundle.item_bn_already_exists', ['item_bn' => $data['item_bn']]));
            }else if ($item && !isset($spec_params['item_id'])) {
                throw new ResourceException(trans('SupplierBundle.item_bn_already_exists', ['item_bn' => $data['item_bn']]));
            }
        }
        $data['weight'] = $spec_params['weight'] ?? 0;
        $data['weight'] = floatval($data['weight']);
        if (isset($spec_params['volume']) && $spec_params['volume']) {
            $data['volume'] = floatval($spec_params['volume']);
        }
        $data['barcode'] = $spec_params['barcode'] ?? '';
        // $data['supplier_goods_bn'] = $params['supplier_goods_bn'] ?? '';
        $data['tax_rate'] = $spec_params['tax_rate'] ?? '';
        if ($data['tax_rate'] > 13) {
            throw new ResourceException(trans('SupplierBundle.tax_rate_max_13_percent'));
        }
        $data['barcode'] = trim($data['barcode']);
        $data['price'] = bcmul($spec_params['price'], 100);
        //        if ($data['price'] <= 0 && !$data['is_gift']) {
        //            throw new ResourceException('非赠品商品销售价必须大于0');
        //        }
        $data['cost_price'] = isset($spec_params['cost_price']) ? bcmul($spec_params['cost_price'], 100) : 0;
        $data['market_price'] = $spec_params['market_price'] ? bcmul($spec_params['market_price'], 100) : 0;
        $data['profit_fee'] = isset($spec_params['profit_fee']) ? bcmul($spec_params['profit_fee'], 100) : 0;

        $data['item_unit'] = $data['item_unit'] ?? trans('SupplierBundle.default_unit');
        $data['store'] = isset($spec_params['store']) ? intval($spec_params['store']) : 0;
        $data['approve_status'] = $spec_params['approve_status'];
        $data['is_default'] = isset($spec_params['is_default']) ? $spec_params['is_default'] : true;
        $data['point'] = isset($spec_params['point']) ? intval($spec_params['point']) : 0;// 商品赠送积分
        $data['operator_type'] = 'supplier';
        // 起订量
        if (isset($spec_params['start_num'])) {
            $data['start_num'] = $spec_params['start_num'] > 0 ? $spec_params['start_num'] : 0;
        }

        //不同商品类型的参数
        // if (method_exists($this->itemtypeObject, 'preRelItemParams')) {
        //     $data = $this->itemtypeObject->preRelItemParams($data, $params);
        // }

        $itemsCategoryService = new ItemsCategoryService();
        //检测分类是否存在
        // if ($data['item_category'] ?? []) {
        //     $CategoryId = (array)$data['item_category'];
        //     $list = $itemsCategoryService->itemsCategoryRepository->lists(['category_id' => $CategoryId, 'is_main_category' => false]);
        //     $catlist = $list['list'];
        //     if (!$catlist || count($catlist) != count($CategoryId)) {
        //         throw new ResourceException('选中的分类不存在 或 错误');
        //     }
        // }

        //检测主类目实付存在
        // if ($data['item_main_cat_id'] ?? 0) {
        //     $catInfo = $itemsCategoryService->itemsCategoryRepository->getInfo(['category_id' => $data['item_main_cat_id'], 'is_main_category' => true]);
        //     if (!$catInfo) {
        //         throw new ResourceException('您选中的主类目不存在');
        //     }
        // }

        //检测品牌是否存在
        $itemsAttributesService = new ItemsAttributesService();
        if ($data['brand_id'] ?? 0) {
            $info = $itemsAttributesService->itemsAttributesRepository->getInfo(['attribute_id' => $data['brand_id'], 'attribute_type' => 'brand']);
            if (!$info) {
                throw new ResourceException(trans('SupplierBundle.selected_brand_not_exist'));
            }
        }

        //检测参数是否存在
        if ($data['item_params'] ?? []) {
            foreach ($data['item_params'] as $v) {
                if ($v['attribute_id'] ?? 0) {
                    $ids[$v['attribute_id']] = $v['attribute_id'];
                }
                if ($v['attribute_value_id'] ?? 0) {
                    $vids[$v['attribute_value_id']] = $v['attribute_value_id'];
                }
            }
            if ($ids ?? []) {
                $lists = $itemsAttributesService->lists(['attribute_id' => $ids, 'attribute_type' => 'item_params']);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($ids)) {
                    throw new ResourceException(trans('SupplierBundle.selected_params_not_exist'));
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException(trans('SupplierBundle.selected_param_values_not_exist'));
                }
            }
        }
        //检测规格是否存在
        if ($data['item_spec'] ?? []) {
            foreach ($data['item_spec'] as $v) {
                if ($v['spec_id'] ?? 0) {
                    $ids[$v['spec_id']] = $v['spec_id'];
                }
                if ($v['spec_value_id'] ?? 0) {
                    $vids[$v['spec_value_id']] = $v['spec_value_id'];
                }
            }
            if ($ids ?? []) {
                $lists = $itemsAttributesService->lists(['attribute_id' => $ids, 'attribute_type' => 'item_spec']);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($ids)) {
                    throw new ResourceException(trans('SupplierBundle.selected_spec_not_exist'));
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException(trans('SupplierBundle.selected_spec_values_not_exist'));
                }
            }
        }
        //检测运费模板是否存在
        if ($data['templates_id'] ?? 0) {
            $shippingTemplatesService = new ShippingTemplatesService();
            $info = $shippingTemplatesService->getInfo($data['templates_id'], $data['company_id']);
            if (!$info) {
                throw new ResourceException(trans('SupplierBundle.selected_freight_template_not_exist'));
            }
        }
        return $data;
    }

    public function __getBn($prefix)
    {
        $today = date('ymd');;
        $skuCounterKey = $prefix . "_counter_date:" . $today;
        $skuCounter = (int)app('redis')->incr($skuCounterKey);
        if ($skuCounter == 1) {
            app('redis')->set($skuCounterKey, 1, 'EX', 86401);
        }
        return $prefix . $today . str_pad($skuCounter, 8, '0', STR_PAD_LEFT);
    }

    private function processUpdateItem($itemId, $companyId, $params)
    {
        $updateIitemInfo = $this->repository->getInfoById($itemId);
        if (!$updateIitemInfo) {
            throw new ResourceException(trans('SupplierBundle.update_item_invalid'));
        }

        $supplier_id = $params['supplier_id'] ?? 0;
        if ($supplier_id && $updateIitemInfo['supplier_id'] != $supplier_id) {
            throw new ResourceException(trans('SupplierBundle.no_permission_update_supplier_item'));
        }

        $hasSpecItems = false;//是否多规格商品
        if (!$updateIitemInfo['nospec']) $hasSpecItems = true;
        elseif ($updateIitemInfo['nospec'] === false || $updateIitemInfo['nospec'] === 'false' || $updateIitemInfo['nospec'] === 0 || $updateIitemInfo['nospec'] === '0') $hasSpecItems = true;
        elseif (isset($params['nospec']) && ($params['nospec'] === false || $params['nospec'] === 'false' || $params['nospec'] === 0 || $params['nospec'] === '0')) $hasSpecItems = true;
        $spec_items = $params['spec_items'] ?? '';
        if (!$spec_items) {
            $hasSpecItems = false;
        }

        // 如果是多规格
        if ($hasSpecItems) {
            $defaultItemId = $updateIitemInfo['default_item_id'];
            $rsItems = $this->repository->getLists(['default_item_id' => $defaultItemId, 'company_id' => $companyId], '*', 1, -1);
            $specItems = json_decode($params['spec_items'], true);
            $newItemIds = array_column($specItems, 'item_id');
            $deleteIds = [];

            // $itemStoreService = new ItemStoreService();
            $distributorDeleteIds = [];
            foreach ($rsItems as $row) {
                // 如果数据库中的商品不在新更新的数据中，则表示需要把数据库中的删除
                if (!in_array($row['item_id'], $newItemIds)) {
                    $deleteIds[] = $row['item_id'];
                    // $itemStoreService->deleteItemStore($row['item_id']);
                    // 如果不是店铺商品，那么需要删除关联商品数据
                    // if (!$row['distributor_id']) {
                    //     $distributorDeleteIds[] = $row['item_id'];
                    // }
                }
            }

            // 删除商品
            if ($deleteIds) {
                $deleteFilter = ['item_id' => $deleteIds, 'company_id' => $companyId];
                $this->repository->deleteBy($deleteFilter);

                $SupplierItemsAttrService = new SupplierItemsAttrService();
                $SupplierItemsAttrService->repository->deleteBy($deleteFilter);

                // $itemRelPointAccessService = new ItemRelPointAccessService();
                // $itemRelPointAccessService->deleteBy($deleteFilter);

                //删除条码
                // $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
                // $ItemsBarcode->deleteBy($deleteFilter);
            }

            // if ($distributorDeleteIds) {
            //     $distributorItemsService = new DistributorItemsService();
            //     $distributorItemsService->deleteBy(['item_id' => $deleteIds, 'company_id' => $companyId]);
            // }

            // 删除关联分类
            // $itemsService = new ItemsRelCatsService();
            // $itemsService->deleteBy(['item_id' => $deleteIds, 'company_id' => $companyId]);
        } else {
            $newItemIds = $itemId;
        }

        // 删除品牌，商品参数，商品规格关联数据
        // $this->itemRelAttributesRepository->deleteBy(['item_id' => $newItemIds, 'company_id' => $companyId]);

        // if (method_exists($this->itemtypeObject, 'deleteRelItemById')) {
        //     $this->itemtypeObject->deleteRelItemById($itemId);
        // }

        return $updateIitemInfo;
    }

    private function commonParams($params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'item_type' => $params['item_type'] ?? 'services',
            'consume_type' => $params['consume_type'] ?? "every",
            'item_name' => $params['item_name'],
            'item_unit' => $params['item_unit'] ?? '',
            'buy_limit_area' => $params['buy_limit_area'] ?? '',
            'brief' => $params['brief'] ?? '',
            'sort' => $params['sort'] ?? 1,
            'templates_id' => $params['templates_id'] ?? null,
            'is_show_specimg' => ($params['is_show_specimg'] ?? false) == 'true' ? true : false,
            'pics' => $params['pics'] ?? [],
            'pics_create_qrcode' => $params['pics_create_qrcode'] ?? [],
            'video_type' => $params['video_type'] ?? 'local',
            'videos' => $params['videos'] ?? "",
            'intro' => $params['intro'] ?? '',
            'special_type' => $params['special_type'] ?? 'normal',
            'purchase_agreement' => $params['purchase_agreement'] ?? '',
            'enable_agreement' => ($params['enable_agreement'] ?? false) == 'true' ? true : false,
            'item_category' => $params['item_main_cat_id'] ?? '',
            // 'nospec' => $params['nospec'] ?? 'true',
            'nospec' => (isset($params['nospec']) && ($params['nospec'] === true || $params['nospec'] === 'true' || $params['nospec'] === 1 || $params['nospec'] === '1')) ? true : false,
            'item_address_city' => $params['item_address_city'] ?? '',
            'item_address_province' => $params['item_address_province'] ?? '',
            'date_type' => $params['date_type'] ?? "",
            'begin_date' => $params['begin_date'] ?? "",
            'end_date' => $params['end_date'] ?? "",
            'fixed_term' => $params['fixed_term'] ?? "",
            // 'sales' => $params['fixed_term'] ?? 0,
            'brand_id' => $params['brand_id'] ?? 0,
            'tax_rate' => $params['tax_rate'] ?? 13, //13%默认
            // 跨境参数
            'crossborder_tax_rate' => $params['crossborder_tax_rate'] ?? '',
            'origincountry_id' => $params['origincountry_id'] ?? 0,
            'taxstrategy_id' => $params['taxstrategy_id'] ?? 0,
            'taxation_num' => $params['taxation_num'] ?? 0,
            'type' => $params['type'] ?? '0',
            'goods_bn' => $params['goods_bn'] ?? '',
            // 'supplier_goods_bn' => $params['supplier_goods_bn'] ?? '',
            'tdk_content' => $params['tdk_content'] ?? '',
            'distributor_id' => ($params['distributor_id'] ?? 0) ? $params['distributor_id'] : 0,  //店铺id
            'item_source' => ($params['item_source'] ?? '') ? ($params['item_source'] ?: 'mall') : 'mall',  //商品来源，mall:商城，distributor:店铺自有
            'is_gift' => ($params['is_gift'] ?? false) == 'true' ? true : false,
            'is_profit' => ($params['is_profit'] ?? false) == 'true' ? true : false,
            'profit_type' => $params['profit_type'] ?? 0,
            'profit_fee' => $params['profit_fee'] ?? 0,
            'is_default' => $params['is_default'] ?? false,
            'default_item_id' => $params['default_item_id'] ?? null,
        ];

        ini_set('pcre.backtrack_limit', '-1');
        $imgpreg = '/<img(.*?)src="(.+?)".*?>/';
        preg_match_all($imgpreg, $data['intro'], $intro);
        $count = count($intro) - 1;
        $allImg = $intro[$count];
        $fileType = 'image';
        foreach ($allImg as $key => $value) {
            if (substr($value, 0, 5) == 'data:') {
                preg_match('/^(data:\s*image\/(\w+);base64,)/', $value, $res);
                $decode_value = base64_decode(str_replace($res[1], '', $value));
                $result = UploadTokenFactoryService::create($fileType)->upload($params['company_id'], '', '', $decode_value);
                $url = $result['token']['domain'] . $result['token']['key'];
                $data['intro'] = str_replace($value, $url, $data['intro']);
            }
        }
        // 如果是腾讯视频，videos存储腾讯视频vid
        if ($data['video_type'] == 'tencent') {
            $data['videos'] = $params['tencent_vid'] ?? '';
        }

        //审核状态，默认待提交 submitting
        $data['audit_status'] = $params['audit_status'] ?? 'submitting';

        if (isset($params['regions_id'])) {
            if (is_array($params['regions_id'])) {
                $data['regions_id'] = implode(',', $params['regions_id']);
            } else {
                $data['regions_id'] = $params['regions_id'];
            }
        }
        if (isset($params['regions'])) {
            if (is_array($params['regions'])) {
                $data['regions'] = implode(',', $params['regions']);
            } else {
                $data['regions'] = $params['regions'];
            }
        }

        //            $data['audit_status'] = $params['audit_status'];
        if (isset($params['audit_status']) && $data['audit_status'] == 'rejected' && isset($params['audit_reason'])) {
            $data['audit_reason'] = $params['audit_reason'];
        }

        if (isset($params['audit_status']) && in_array($params['audit_status'], ['rejected', 'approved'])) {
            $data['audit_date'] = time();
        }

        $data['is_show_specimg'] = ($data['is_show_specimg'] == 'true') ? true : false;

        return $data;
    }

    /**
     * 获取商品列表
     *
     * @param array filter
     * @return array
     */
    public function getItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $itemsList = $this->repository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$itemsList['list']) {
            return $itemsList;
        }
        // 加入商品池主商品item_id
        $itemsService = new ItemsService();
        $itemIds = array_column($itemsList['list'], 'item_id');
        $oldItems = $itemsService->itemsRepository->getLists(['supplier_item_id' => $itemIds]);
        $mainItems = array_column($oldItems, null, 'supplier_item_id');

        $brandId = array_column($itemsList['list'], 'brand_id');
        $itemsAttributesService = new ItemsAttributesService();
        $brandId = array_filter($brandId);
        $brandlist = [];
        if ($brandId) {
            $bfilter['attribute_id'] = $brandId;
            $bfilter['attribute_type'] = 'brand';
            $brandlist = $itemsAttributesService->itemsAttributesRepository->getLists($bfilter);
            $brandlist = array_column($brandlist, null, 'attribute_id');
        }

        //获取供应商商品的所有销售分类
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $rsAttrs = $SupplierItemsAttrService->repository->getLists(['item_id' => array_column($itemsList['list'], 'default_item_id'), 'attribute_type' => 'category']);
        $rsAttrs = array_column($rsAttrs, null, 'item_id');

        foreach ($itemsList['list'] as $key => &$v) {
            // 规格转成bool
            $v['nospec'] = ($v['nospec'] === 'true' || $v['nospec'] === true || $v['nospec'] === 1 || $v['nospec'] === '1') ? true : false;
            // 加入商品池主商品item_id
            $v['main_item_id'] = $mainItems[$v['item_id']]['item_id'] ?? 0;

            $v['item_main_cat_id'] = $v['item_category'] ?? '';

            $v['item_cat_id'] = [];
            $attr_data = $rsAttrs[$v['default_item_id']]['attr_data'] ?? '';
            if ($attr_data) {
                $attr_data = json_decode($attr_data, true);
                $v['item_cat_id'] = $attr_data['category'] ?? [];
            }

            $v['item_type'] = $v['item_type'] ?: 'services';
            $v['itemName'] = $v['item_name'];
            if ($brandlist && isset($brandlist[$v['brand_id']])) {
                $v['brand_logo'] = $brandlist[$v['brand_id']]['image_url'] ?? '';
                $v['goods_brand'] = $brandlist[$v['brand_id']]['attribute_name'] ?? '';
            }
        }

        return $itemsList;
    }

    //获取指定条件的所有商品列表，可指定字段
    public function getItemsLists($filter, $cols = 'item_id, default_item_id')
    {
        return $this->repository->getItemsLists($filter, $cols);
    }

    public function getItemsDetail($itemId, $authorizerAppId = null, $limitItemIds = array(), $companyId = null)
    {
        if ($limitItemIds && !in_array($itemId, $limitItemIds)) {
            $itemId = $limitItemIds[0];
        }

        $itemsInfo = $this->repository->getInfoById($itemId);
        if (!$itemsInfo || ($companyId && $itemsInfo['company_id'] != $companyId)) {
            return [];
        }
        
        $itemsInfo['data_source'] = 'supplier_goods';
        $itemsInfo['approve_status'] = '';
        $itemsInfo['item_main_cat_id'] = $itemsInfo['item_category'] ?? '';
        // 规格转成bool
        $itemsInfo['nospec'] = ($itemsInfo['nospec'] === 'true' || $itemsInfo['nospec'] === true || $itemsInfo['nospec'] === 1 || $itemsInfo['nospec'] === '1') ? true : false;

        if ($itemsInfo['regions_id']) {
            $itemsInfo['regions_id'] = explode(',', $itemsInfo['regions_id']);
        }
        if ($itemsInfo['regions']) {
            $itemsInfo['regions'] = explode(',', $itemsInfo['regions']);
        }

        $itemsInfo['pics_create_qrcode'] = [];
        if (!empty($itemsInfo['pics'])) {
            if (is_string($itemsInfo['pics'])) $itemsInfo['pics'] = json_decode($itemsInfo['pics'], true);
            foreach ($itemsInfo['pics'] as $key => $value) {
                $pics_create_qrcode = $itemsInfo['pics_create_qrcode'][$key] ?? 'false';
                $itemsInfo['pics_create_qrcode'][$key] = $pics_create_qrcode == 'false' ? false : true;
            }
            if (is_array($itemsInfo['pics_create_qrcode'])) {
                $itemsInfo['pics_create_qrcode'] = array_values($itemsInfo['pics_create_qrcode']);//防止前端出错
            }
        }

        $itemsInfo['item_type'] = $itemsInfo['item_type'] ?: 'services';

        // 如果是多规格
        if ($itemsInfo['nospec'] === false || $itemsInfo['nospec'] === 'false' || $itemsInfo['nospec'] === 0 || $itemsInfo['nospec'] === '0') {
            $filter['company_id'] = $itemsInfo['company_id'];
            if ($limitItemIds) {
                $filter['item_id'] = $limitItemIds;
            } else {
                $filter['default_item_id'] = $itemsInfo['default_item_id'];
            }
            // 获取多规格的商品id
            $itemsList = $this->repository->lists($filter, '*', 1, -1);
            $itemIds = array_column($itemsList['list'], 'item_id');
        } else {
            $itemIds = $itemId;
            $itemsList = array();
        }

        $itemsService = new ItemsService();
        $itemsInfo = $this->__preGetItemRelAttr($itemsInfo, $itemIds, $itemsList);

        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $itemsInfo['item_category'] = $SupplierItemsAttrService->getAttrData($itemsInfo['item_id'], 'category');

        // 商品主类目
        if ($itemsInfo['item_main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemsInfo['item_category_main'] = $itemsCategoryService->getCategoryPathById($itemsInfo['item_main_cat_id'], $itemsInfo['company_id'], true);
            if (!$itemsInfo['item_category_main']) {
                $itemsInfo['item_main_cat_id'] = '';
            }
        } else {
            $itemsInfo['item_category_main'] = [];
        }

        // 设置第一个商品分类信息
        if ($itemsInfo['item_category'] ?? false) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemsInfo['item_category_info'] = $itemsCategoryService->getCategoryPathById($itemsInfo['item_category'][0], $itemsInfo['company_id']);
        } else {
            $itemsInfo['item_category_info'] = [];
        }

        if (isset($itemsInfo['intro'])) {
            $articleService = new ArticleService();
            $itemsInfo['intro'] = $articleService->proArticleContent($itemsInfo['intro'], $authorizerAppId, 0, 'supplier_goods');
        }

        //置换微信视频
        if (isset($itemsInfo['videos']) && $itemsInfo['videos'] && $authorizerAppId && $itemsInfo['video_type'] != 'tencent') {
            $itemsInfo = $itemsService->getVideoPicUrl($itemsInfo, $authorizerAppId);
        } else {
            $itemsInfo['videos_url'] = '';
        }
        if ($itemsInfo['video_type'] == 'tencent') {
            $itemsInfo['tencent_vid'] = $itemsInfo['videos'];
        }
        $itemsInfo['distributor_sale_status'] = true;
        if (in_array($itemsInfo['approve_status'], ['instock', 'only_show'])) {
            $itemsInfo['distributor_sale_status'] = false;
        }
        $itemsInfo['item_total_store'] = $itemsInfo['item_total_store'] ?? $itemsInfo['store'];
        $itemsInfo['item_total_sales'] = $itemsInfo['item_total_sales'] ?? $itemsInfo['sales'];

        $itemsInfo['distributor_info'] = [];

        // 产地国家, 供应商暂不支持
        // if (empty($itemsInfo['origincountry_id'])) {
        //     $itemsInfo['origincountry_name'] = '';
        //     $itemsInfo['origincountry_img_url'] = '';
        // } else {
        //     $info = app('registry')->getManager('default')->getRepository(OriginCountry::class)->getInfoById($itemsInfo['origincountry_id']);
        //     $itemsInfo['origincountry_name'] = $info['origincountry_name'];
        //     $itemsInfo['origincountry_img_url'] = $info['origincountry_img_url'];
        // }

        // 判断是否是跨境商品, 供应商暂不支持
        $itemsInfo['tax_rate'] = 0;
        $itemsInfo['cross_border_tax'] = 0;
        // if ($itemsInfo['type'] == 1) {
        //     $ItemTaxRateService = new ItemTaxRateService($companyId);
        //     $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($itemsInfo['item_id']);
        //
        //     $itemsInfo['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];
        //     $itemsInfo['cross_border_tax'] = bcdiv(bcmul($itemsInfo['price'], $itemsInfo['cross_border_tax_rate'], 0), 100, 0);
        //
        //     // 税费规则
        //     if (!empty($itemsInfo['taxstrategy_id'])) {
        //         $tax_strategy_filter['id'] = $itemsInfo['taxstrategy_id'];
        //         $tax_strategy_filter['company_id'] = $itemsInfo['company_id'];
        //         $Strategy = new Strategy();
        //         $tax_strategy_data = $Strategy->getInfo($tax_strategy_filter);
        //         $itemsInfo['tax_strategy'] = $tax_strategy_data['taxstrategy_content'];
        //     }
        // }

        // 运费模板名称
        $itemsInfo['templates_name'] = '';
        if (!empty($itemsInfo['templates_id'])) {
            $shippingTemplatesService = new ShippingTemplatesService();
            $info = $shippingTemplatesService->getInfo($itemsInfo['templates_id'], $companyId);
            if ($info) {
                $itemsInfo['templates_name'] = $info['name'];
            }
        }

        // 提供格式化的数据供前端使用
        foreach ($itemsInfo['spec_items'] as $key => &$item) {
            usort($item['item_spec'], function ($a, $b) {
                if ($a['spec_id'] == $b['spec_id']) return 0;
                else return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
            });

            $custom_spec_ids = [];
            $custom_spec_names = [];
            foreach ($item['item_spec'] as $item_spec) {
                $custom_spec_ids[] = $item_spec['spec_value_id'];
                $custom_spec_names[] = $item_spec['spec_value_name'];
            }
            $custom_spec_ids = implode('-', $custom_spec_ids);
            $custom_spec_names = implode('、', $custom_spec_names);
            $itemsInfo['spec_items'][$key]['custom_spec_id'] = $custom_spec_ids;
            $itemsInfo['spec_items'][$key]['custom_spec_name'] = $custom_spec_names;
        }
        //修复前端显示错误
        $itemsInfo['brand_id'] = (string)($itemsInfo['brand_id'] ?? 0);
        return $itemsInfo;
    }

    /**
     * 商品详情，商品关联商品属性处理结构
     */
    private function __preGetItemRelAttr($itemsInfo, $itemIds, $itemsList)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $defaultItemId = $itemsInfo['default_item_id'] ?: $itemsInfo['item_id'];

        //获取品牌，属性参数
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        // $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $defaultItemId], 1, -1, ['attribute_sort' => 'asc']);
        $attrList = $SupplierItemsAttrService->getAttrDataList($defaultItemId);
        //规格等数据
        // $specAttrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        $specAttrList = $SupplierItemsAttrService->getAttrDataList($itemIds, ['item_spec']);
        // 临时
        $itemsInfo['spec_pics'] = [];
        if ($specAttrList) {
            foreach ($specAttrList as $specAttrRow) {
                if ($specAttrRow['item_id'] == $itemsInfo['item_id'] && $specAttrRow['image_url']) {
                    $itemsInfo['spec_pics'] = $specAttrRow['image_url'];
                }
            }
        }

        $itemsInfo['item_params'] = [];
        $itemsInfo['item_spec_desc'] = [];
        $itemsInfo['spec_images'] = [];
        $itemsInfo['spec_items'] = [];

        $attrList = array_merge($attrList, $specAttrList);
        if ($attrList) {
            $itemsService = new ItemsService();

            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);

            $itemsInfo['attribute_ids'] = $attrData['attribute_ids'] ?? [];
            $itemsInfo['attr_values_custom'] = $attrData['attr_values_custom'] ?? [];

            $itemsInfo = $itemsService->__preGetItemParams($itemsInfo, $attrData);

            $itemsInfo = $itemsService->__preGetItemSpec($itemsInfo, $attrData, $itemsList);

            if (isset($attrData['brand'])) {
                $itemsInfo['brand_id'] = $attrData['brand']['brand_id'];
                $itemsInfo['goods_brand'] = $attrData['brand']['goods_brand'];
                $itemsInfo['brand_logo'] = $attrData['brand']['brand_logo'];
            }
        }
        return $itemsInfo;
    }

    //审核供应商商品
    public function reviewGoods($params, $itemId = 0)
    {
        $itemsService = new ItemsService();

        $supplierGoods = $this->repository->getInfoById($itemId);
        $companyId = $supplierGoods['company_id'];
        $params['supplier_id'] = $supplierGoods['supplier_id'];
        $params['goods_id'] = $supplierGoods['goods_id'];
        $params['is_market'] = $supplierGoods['is_market'];//供应商控制商品是否可售
        $supplier_goods_id = $supplierGoods['goods_id'];
        $saveData = [
            'audit_status' => $params['audit_status'],//审核状态 approved or rejected
            'audit_date' => time(),
            'audit_reason' => trim($params['audit_reason']),
        ];
        $this->repository->updateBy(['goods_id' => $supplierGoods['goods_id']], $saveData);

        //总部审核通过
        if ($params['audit_status'] == 'approved') {
            $default_item_id = 0;
            $goods_id = 0;
            $item_ids = [];
            $filter = [
                'goods_id' => $supplierGoods['goods_id'],
            ];
            $supplierGoodsList = $this->repository->getLists($filter);
            // 获取对应商品池的goods_id,这样才能查询到所有的sku商品
            $items_goods_id = $itemsService->itemsRepository->getInfo(['supplier_item_id' => $supplier_goods_id])['goods_id'] ?? 0;
            //查询历史存在的商品id
            $oldItemIds = [];
            if ($items_goods_id) {
                $oldItems = $itemsService->itemsRepository->getLists(['goods_id' => $items_goods_id]);
                $oldItemIds = array_column($oldItems, 'item_id');
            }
            foreach ($supplierGoodsList as $v) {
                $v['supplier_item_id'] = $v['item_id'];
                $v['approve_status'] = $v['is_market'] ? 'onsale' : 'instock';
                $v['consume_type'] = 'every';
                // 特殊处理下图片
                if (!is_array($v['pics'])) {
                    $v['pics'] = json_decode($v['pics'], true);
                }
                // 处理规格
                $v['nospec'] = ( isset($v['nospec']) && ($v['nospec'] === 'true' || $v['nospec'] === true || $v['nospec'] === 1 || $v['nospec'] === '1') ) ? true : false;
                unset($v['item_id']);
                $rsItem = $itemsService->itemsRepository->getInfo(['supplier_item_id' => $v['supplier_item_id']]);
                if ($rsItem) {
                    $goods_id = $rsItem['goods_id'];

                    $itemsService->itemsRepository->update($rsItem['item_id'], $v);
                    //更新redis内存
                    $itemStoreService = new ItemStoreService();
                    $itemStoreService->saveItemStore($rsItem['item_id'], $v['store']);
                } else {
                    $rsItem = $itemsService->itemsRepository->create($v);
                    //更新redis内存
                    $itemStoreService = new ItemStoreService();
                    $itemStoreService->saveItemStore($rsItem['item_id'], $v['store']);
                }

                //处理商品规格
                $this->createItemSpec($rsItem['item_id'], $v['supplier_item_id']);

                if ($rsItem['is_default']) {
                    $default_item_id = $rsItem['item_id'];
                }
                $item_ids[] = $rsItem['item_id'];
            }
            if (!$goods_id) {
                $goods_id = $default_item_id;
            }

            $itemsService->itemsRepository->updateBy(['item_id' => $item_ids], ['default_item_id' => $default_item_id, 'goods_id' => $goods_id]);

            //删掉已经不存在的规格
            if (!empty($oldItemIds)) {
                $removeItemIds = array_diff($oldItemIds, $item_ids);
                if ($removeItemIds) {
                    $itemsService->itemsRepository->deleteBy(['item_id' => $removeItemIds]);
                    //删除redis库存
                    $itemStoreService = new ItemStoreService();
                    foreach ($removeItemIds as $v) {
                        $itemStoreService->deleteItemStore($v);
                    }
                }
            }

            //处理商品属性
            $this->syncRelCats($companyId, $default_item_id, $supplierGoods['default_item_id']);// 默认商品关联分类
            $this->syncRelBrand($companyId, $default_item_id, $supplierGoods['default_item_id']);// 关联品牌
            $this->syncRelParams($companyId, $default_item_id, $supplierGoods['default_item_id']); // 关联参数

            //同步商品到店铺
//            $itemsService->syncGoods($companyId, $default_item_id);
        }
        return $itemId;
    }

    private function syncRelCats($companyId, $defaultItemId, $supplier_default_item_id)
    {
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $new_category_ids = $SupplierItemsAttrService->getAttrData($supplier_default_item_id, 'category');

        $itemsRelCatsService = new ItemsRelCatsService();
        $rs = $itemsRelCatsService->entityRepository->getList(['item_id' => $defaultItemId]);
        $old_category_ids = array_column($rs, 'category_id');

        //新增商品销售分类
        $add_category_ids = array_diff($new_category_ids, $old_category_ids);
        if ($add_category_ids) {
            foreach ($add_category_ids as $category_id) {
                $saveData = [
                    'company_id' => $companyId,
                    'item_id' => $defaultItemId,
                    'category_id' => $category_id,
                ];
                $itemsRelCatsService->entityRepository->create($saveData);
            }
        }

        //删除不存在的商品分类
        $remove_category_ids = array_diff($old_category_ids, $new_category_ids);
        if ($remove_category_ids) {
            $filter = [
                'company_id' => $companyId,
                'item_id' => $defaultItemId,
                'category_id' => $remove_category_ids,
            ];
            $itemsRelCatsService->entityRepository->deleteBy($filter);
        }
    }

    /**
     * 商品关联品牌 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function syncRelBrand($companyId, $defaultItemId, $supplier_default_item_id)
    {
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $brandId = $SupplierItemsAttrService->getAttrData($supplier_default_item_id, 'brand');
        if (!$brandId) {
            return true;
        }

        $saveData = [
            'company_id' => $companyId,
            'item_id' => $defaultItemId,
            'attribute_id' => $brandId,
            'attribute_value_id' => $brandId,
            'attribute_type' => 'brand',
            'image_url' => '',
        ];

        $itemRelAttributesService = new ItemRelAttributesService();
        $rs = $itemRelAttributesService->ItemRelAttributes->getInfo(['item_id' => $defaultItemId, 'attribute_type' => 'brand']);
        // 保存品牌
        if ($rs) {
            $rs = $itemRelAttributesService->ItemRelAttributes->updateOneBy(['id' => $rs['id']], $saveData);
        } else {
            $rs = $itemRelAttributesService->ItemRelAttributes->create($saveData);
        }
        return $rs;
    }

    /**
     * 商品关联参数 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function syncRelParams($companyId, $defaultItemId, $supplier_default_item_id)
    {
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $itemRelAttributesService = new ItemRelAttributesService();

        $rs = $itemRelAttributesService->ItemRelAttributes->getList(['item_id' => $defaultItemId, 'attribute_type' => 'item_params']);
        $old_attr_ids = array_column($rs, 'attribute_id');

        $rs = $SupplierItemsAttrService->getAttrDataList($supplier_default_item_id, 'item_params');
        $new_attr_ids = array_column($rs, 'attribute_id');
        foreach ($rs as $v) {
            $filter = [
                'company_id' => $companyId,
                'item_id' => $defaultItemId,
                'attribute_id' => $v['attribute_id'],
                'attribute_type' => 'item_params',
            ];
            $saveData = [
                'attribute_value_id' => $v['attribute_value_id'],
                'custom_attribute_value' => $v['custom_attribute_value'],
            ];
            $rsAttr = $itemRelAttributesService->ItemRelAttributes->getInfo($filter);
            if ($rsAttr) {
                $rsAttr = $itemRelAttributesService->ItemRelAttributes->updateOneBy(['id' => $rsAttr['id']], $saveData);
            } else {
                $saveData = array_merge($saveData, $filter);
                $rsAttr = $itemRelAttributesService->ItemRelAttributes->create($saveData);
            }
        }

        $remove_attr_ids = array_diff($old_attr_ids, $new_attr_ids);
        if ($remove_attr_ids) {
            $filter = [
                'company_id' => $companyId,
                'item_id' => $defaultItemId,
                'attribute_id' => $remove_attr_ids,
                'attribute_type' => 'item_params',
            ];
            $itemRelAttributesService->ItemRelAttributes->deleteBy($filter);
        }
    }

    //供应商商品审核，把商品规格同步到平台商品表
    private function createItemSpec($item_id, $supplier_item_id)
    {
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $rs = $SupplierItemsAttrService->repository->getLists(['item_id' => $supplier_item_id, 'attribute_type' => 'item_spec']);
        if ($rs) {
            $itemRelAttributesService = new ItemRelAttributesService();
            //已经存在的商品规格
            $rsAttr = $itemRelAttributesService->ItemRelAttributes->getList(['item_id' => $item_id, 'attribute_type' => 'item_spec']);
            $oldAttrIds = array_column($rsAttr, 'id');
            $newAttrIds = [];
            foreach ($rs as $v) {
                if (!$v['attr_data']) {
                    continue;
                }
                $attr_data = json_decode($v['attr_data'], true);
                $_filter = [
                    'company_id' => $v['company_id'],
                    'item_id' => $item_id, //$v['item_id'], // 这是供应商item_id, 要换成主商品item_id
                    'attribute_id' => $v['attribute_id'],
                    'attribute_type' => 'item_spec',
                ];
                $item_spec = $attr_data['item_spec'];
                $rsAttr = $itemRelAttributesService->ItemRelAttributes->getInfo($_filter);
                if ($rsAttr) {
                    $rsAttr = $itemRelAttributesService->ItemRelAttributes->updateOneBy(['id' => $rsAttr['id']], $item_spec);
                } else {
                    $item_spec = array_merge($_filter, $item_spec);
                    $rsAttr = $itemRelAttributesService->ItemRelAttributes->create($item_spec);
                }
                $newAttrIds[] = $rsAttr['id'];
            }
            //需要删除已经不存在的商品规格
            if ($oldAttrIds) {
                $removeAttrIds = array_diff($oldAttrIds, $newAttrIds);
                if ($removeAttrIds) {
                    $itemRelAttributesService->ItemRelAttributes->deleteBy(['id' => $removeAttrIds]);
                }
            }
        }
    }

    /**
     * 供应商端目前只支持
     *  1.批量提交审核，2.批量设置停售和开售
     * @param array $filter 更新条件
     * @param array $params 更新数据
     * @return
     */
    public function batchUpdateItems($filter, $params)
    {
        $result = true;
        if (isset($params['is_market'])) {
            //批量设置开售，只更新目前是停售状态的数据，并更新成待提审状态
            if ($params['is_market']) {
                $filter['is_market'] = 0;//设置开售
                $params['approve_status'] = 'onsale';
            } else {
                $filter['is_market'] = 1;//设置停售
                $params['approve_status'] = 'instock';
            }
            $params['audit_status'] = 'processing';
        }
        if ($this->repository->getInfo($filter)) {
            $result = $this->repository->updateBy($filter, $params);
        }
        return $result;
    }

    public function updateItemsStore($companyId, $params)
    {
        $itemStoreService = new ItemStoreService();
        $itemsService = new ItemsService();
        foreach ((array)$params as $data) {
            $filter['company_id'] = $companyId;
            if (isset($data['is_default']) && $data['is_default'] == 'true') {
                //更新所有规格的库存
                $filter['default_item_id'] = $data['item_id'];
            } else {
                //更新单个规格的库存
                $filter['item_id'] = $data['item_id'];
            }

            //更新供应商商品库存
            $this->repository->updateBy($filter, ['store' => $data['store']]);

            //更新平台商品库存
            $rsSupplierItems = $this->repository->getLists($filter);
            $itemlist = $itemsService->itemsRepository->getItemsLists(['supplier_item_id' => array_column($rsSupplierItems, 'item_id')]);
            if ($itemlist) {
                $itemsService->itemsRepository->updateBy(['item_id' => array_column($itemlist, 'item_id')], ['store' => $data['store']]);
                foreach ($itemlist as $value) {
                    $itemStoreService->saveItemStore($value['item_id'], $data['store']);
                }
            }
        }
        return true;
    }

    //设置商品库存预警
    public function setWarningStore($companyId, $store, $supplier_id = 0)
    {
        return app('redis')->set('supplier_warning_store:' . $companyId . ':' . $supplier_id, $store);
    }

    //获取库存预警
    public function getWarningStore($companyId, $supplier_id = 0)
    {
        $store = app('redis')->get('supplier_warning_store:' . $companyId . ':' . $supplier_id);
        return $store ?: 5;
    }

    public function setItemsSort($filter, $sort)
    {
        $itemsInfo = $this->repository->getInfo(['item_id' => $filter['item_id']]);
        if ($filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException(trans('SupplierBundle.please_confirm_item_info'));
        }
        $itemsResult = $this->repository->updateOneBy(['item_id' => $filter['item_id']], ['sort' => $sort]);
        return $itemsResult;
    }
    
    /**
     * 删除商品
     *
     * @param array filter
     * @return bool
     */
    public function deleteItems($filter)
    {
        if (!isset($filter['item_id']) || !$filter['item_id']) {
            throw new ResourceException('商品id不能为空');
        }
        $itemsInfo = $this->repository->getInfo(['item_id' => $filter['item_id']]);
        if (!$itemsInfo || $filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException('删除商品信息有误');
        }
        if ($filter['distributor_id'] != $itemsInfo['distributor_id']) {
            throw new ResourceException('店铺商品信息有误，不可删除');
        }
        if ($itemsInfo['is_market'] == 1) {
            throw new ResourceException('商品可售状态,不能删除');
        }
        // 调整为，校验是未过售后期的订单或者售后单，不能删除
        // if ($itemsInfo['audit_status'] == 'approved') {
        //     throw new ResourceException('商品审核通过,不能删除');
        // }
        // 如果是多规格
        if (!$itemsInfo['nospec'] || ($itemsInfo['nospec'] === false || $itemsInfo['nospec'] === 'false' || $itemsInfo['nospec'] === 0 || $itemsInfo['nospec'] === '0')) {
            $data = $this->repository->lists(['default_item_id' => $itemsInfo['default_item_id'], 'company_id' => $itemsInfo['company_id']]);
            $itemIds = array_column($data['list'], 'item_id');
            // $defaultItemId = $itemsInfo['default_item_id'];
        } else {
            $itemIds = [ $itemsInfo['item_id'] ];
            // $defaultItemId = $itemsInfo['item_id'];
        }
        // 校验是未过售后期的订单或者售后单，不能删除
        $this->checkUnfinishedOrdersAndAftersales($itemIds, $itemsInfo['supplier_id'], $filter['company_id']);
        
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($itemIds as $itemId) {
                $this->repository->deleteBy(['item_id' => $itemId]);
                // 删除品牌，商品参数，商品规格关联数据
                $this->supplierItemsAttrRepository->deleteBy(['item_id' => $itemId, 'company_id' => $filter['company_id']]);
            }
            // 商品池数据删除
            $itemsService = new ItemsService();
            $rsItemsInfo = $itemsService->itemsRepository->getInfo(['supplier_item_id' => $filter['item_id'], 'company_id' => $filter['company_id']]);
            if (!empty($rsItemsInfo)) {
                $filter['item_id'] = $rsItemsInfo['item_id'];
                $result = $itemsService->deleteItems($filter);
            }
            
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

    }

    /**
     * 校验是否存在未完结的订单或未完成的售后单
     *
     * @param array $supplierItemIds 供应商商品ID数组
     * @param int $supplierId 供应商ID
     * @param int $companyId 公司ID
     * @throws ResourceException
     */
    private function checkUnfinishedOrdersAndAftersales($supplierItemIds, $supplierId, $companyId)
    {
        // 1. 获取商品池商品ID列表
        $itemsService = new ItemsService();
        $poolItems = $itemsService->itemsRepository->getLists([
            'supplier_item_id' => $supplierItemIds,
            'company_id' => $companyId
        ], 'item_id');
        $poolItemIds = array_column($poolItems, 'item_id');
        
        if (empty($poolItemIds)) {
            return;
        }
        
        $currentTime = time();
        $conn = app('registry')->getConnection('default');
        
        // 辅助函数：将数组转换为literal值
        $toLiteralArray = function($array, $qb) {
            $result = $array;
            array_walk($result, function (&$value) use ($qb) {
                $value = $qb->expr()->literal($value);
            });
            return $result;
        };
        
        // 2. 查询未完结的订单（未过售后期或未完成）
        $qb = $conn->createQueryBuilder();
        $poolItemIdsLiteral = $toLiteralArray($poolItemIds, $qb);
        $qb->select('COUNT(*)')
            ->from('orders_normal_orders_items', 'oi')
            ->innerJoin('oi', 'orders_normal_orders', 'o', 'oi.order_id = o.order_id')
            ->where($qb->expr()->in('oi.item_id', $poolItemIdsLiteral))
            ->andWhere($qb->expr()->eq('oi.company_id', $companyId))
            ->andWhere($qb->expr()->eq('o.supplier_id', $supplierId))
            ->andWhere($qb->expr()->neq('o.cancel_status', $qb->expr()->literal('SUCCESS')))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->gt('o.order_auto_close_aftersales_time', $currentTime),
                $qb->expr()->orX(
                    $qb->expr()->isNull('o.order_auto_close_aftersales_time'),
                    $qb->expr()->eq('o.order_auto_close_aftersales_time', 0)
                )
            ));
        $orderCount = (int)$qb->execute()->fetchColumn();
        
        // 3. 查询未完成的售后单
        $qb2 = $conn->createQueryBuilder();
        $poolItemIdsLiteral2 = $toLiteralArray($poolItemIds, $qb2);
        $aftersalesStatusLiteral = $toLiteralArray([0, 1], $qb2);
        $qb2->select('COUNT(*)')
            ->from('aftersales_detail', 'ad')
            ->innerJoin('ad', 'aftersales', 'a', 'ad.aftersales_bn = a.aftersales_bn')
            ->where($qb2->expr()->in('ad.item_id', $poolItemIdsLiteral2))
            ->andWhere($qb2->expr()->eq('ad.company_id', $companyId))
            ->andWhere($qb2->expr()->eq('a.supplier_id', $supplierId))
            ->andWhere($qb2->expr()->in('ad.aftersales_status', $aftersalesStatusLiteral));
        $aftersalesCount = (int)$qb2->execute()->fetchColumn();
        
        // 4. 根据情况抛出不同的异常提示
        if ($orderCount > 0 && $aftersalesCount > 0) {
            throw new ResourceException(trans('SupplierBundle.cannot_delete_unfinished_orders_and_aftersales'));
        } elseif ($orderCount > 0) {
            throw new ResourceException(trans('SupplierBundle.cannot_delete_unfinished_orders'));
        } elseif ($aftersalesCount > 0) {
            throw new ResourceException(trans('SupplierBundle.cannot_delete_unfinished_aftersales'));
        }
    }

    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
