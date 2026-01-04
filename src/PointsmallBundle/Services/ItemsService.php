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

namespace PointsmallBundle\Services;

use CompanysBundle\Services\SettingService;
use CrossBorderBundle\Entities\OriginCountry;
use GoodsBundle\Entities\ItemsMedicine;
use GoodsBundle\Jobs\MedicineItemsSubmitAudit;
use GoodsBundle\Repositories\ItemsMedicineRepository;
use GoodsBundle\Services\ItemsMedicineService;
use PointsmallBundle\Entities\PointsmallItems;
use PointsmallBundle\Entities\PointsmallItemRelAttributes;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\OpenPlatform;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use CompanysBundle\Services\ArticleService;
use WechatBundle\Services\Material as MaterialService;
use WechatBundle\Services\WeappService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsAttributesService;

// use PointsmallBundle\Services\ItemTaxRateService;

class ItemsService
{
    protected $itemsTypeClass = [
        'normal' => \PointsmallBundle\Services\Items\Normal::class, //普通实体商品
    ];

    /**
     * @var itemsRepository
     */
    private $itemsRepository;

    public $itemRelAttributesRepository;

    public $itemtypeObject;

    /** @var $itemsMedicineRepository ItemsMedicineRepository */
    private $itemsMedicineRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(PointsmallItems::class);
        $this->itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(PointsmallItemRelAttributes::class);

        $this->itemsMedicineRepository = app('registry')->getManager('default')->getRepository(ItemsMedicine::class);
    }

    /**
     * 保存商品
     */
    public function addItems($params, $isCreateRelData = true)
    {
        $params['item_type'] = $params['item_type'] ?? "services";
        $params['recommend_items'] = $params['recommend_items'] ?? [];
        $this->itemtypeObject = new $this->itemsTypeClass[$params['item_type']]();
        $itemsMedicineService = new ItemsMedicineService();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 商品通用参数
            $data = $this->commonParams($params);

            // 药品数据
            if (isset($params['is_medicine']) && $params['is_medicine'] == 1) {
                // 检查是否开启医药行业开关
                $medicineSetting = (new SettingService())->getMedicineSetting($params['company_id']);
                if (!$medicineSetting['is_pharma_industry']) {
                    throw new ResourceException('医药行业功能未开启');
                }

                // 药品数据
                $medicineData = (new ItemsMedicineService())->commonMedicineData($params, 'points');
                $data['is_prescription'] = $medicineData['is_prescription'] ?? 0;
            }

            //$updateItemInfo = [];
            $goodsId = 0;

            if (isset($params['goods_id']) && $params['goods_id']) {
                $goodsId = $params['goods_id'];//区分导入的数据
            }

            if (!$goodsId && isset($params['item_id']) && $params['item_id']) {
                $itemId = $params['item_id'];//更新的商品ID, 如果是多规格则为默认的商品id
                $updateItemInfo = $this->processUpdateItem($itemId, $params['company_id'], $params);
                $goodsId = $updateItemInfo['goods_id'];
            }

            // 如果是多规格
            if (isset($params['nospec']) && ($params['nospec'] === false || $params['nospec'] === 'false' || $params['nospec'] === 0 || $params['nospec'] === '0')) {
                $params['nospec'] = $data['nospec'] = 'false';
                $specImages = [];
                if (isset($params['spec_images'])) {
                    $tempSpecImages = json_decode($params['spec_images'], true);
                    $specImages = array_column($tempSpecImages, 'item_image_url', 'spec_value_id');
                }
                $data['spec_images'] = $specImages;

                $defaultItemId = null;
                $specItems = json_decode($params['spec_items'], true);
                // 如果有外部的商品ID则表示为更新，否则为强制刷新
                $isForceCreate = (isset($params['item_id']) && $params['item_id']) ? false : true;
                $isMiniPrice = 0;
                foreach ($specItems as $row) {
                    $itemsResult = $this->createItems($data, $row, $isForceCreate);
                    $itemIds[] = $itemsResult['item_id'];
                    if ($isMiniPrice == 0) {
                        $isMiniPrice = $row['price'];
                    } elseif ($isMiniPrice > $row['price']) {
                        $isMiniPrice = $row['price'];
                        $defaultItemId = $itemsResult['item_id'];
                    }
                    if (!$defaultItemId && in_array($row['approve_status'], ['onsale', 'only_show', 'offline_sale'])) {
                        $defaultItemId = $itemsResult['item_id'];
                    }

                    // 药品数据
                    if (!empty($medicineData)) {
                        $row['medicine_spec'] = $row['spec_name'] ?? '';
                        $medicineData['max_num'] = $row['max_num'] ?? 0;
                        $itemsResult = $itemsMedicineService->updateItemMedicineData($row, $medicineData, $itemsResult);
                    }
                }
                // 如果没有定义默认商品，则默认为第一个
                if (!$defaultItemId) {
                    $defaultItemId = $itemIds[0];
                }
            } else {
                $params['nospec'] = $data['nospec'] = 'true';
                $itemsResult = $this->createItems($data, $params);
                $defaultItemId = $itemsResult['item_id'];
                $itemIds[] = $itemsResult['item_id'];

                // 药品数据
                if (!empty($medicineData)) {
                    $itemsResult = $itemsMedicineService->updateItemMedicineData($params, $medicineData, $itemsResult);
                }
            }
            // 兼容导入多规格数据
            if (!$isCreateRelData && isset($params['default_item_id']) && intval($params['default_item_id']) > 0) {
                $defaultItemId = $params['default_item_id'];
            }

            if (!$goodsId) {
                $goodsId = $defaultItemId;
            }

            $this->itemsRepository->updateBy(['item_id' => $itemIds], ['default_item_id' => $defaultItemId, 'goods_id' => $goodsId]);
            $this->itemsRepository->updateBy(['default_item_id' => $defaultItemId, 'item_id|neq' => $defaultItemId], ['is_default' => 0]);
            $this->itemsRepository->updateBy(['item_id' => $defaultItemId], ['is_default' => 1]);

            if ($isCreateRelData) {
                // 默认商品关联分类
                $this->itemsRelCats($params, $defaultItemId);
                // 关联品牌
                $this->itemsRelBrand($params, $defaultItemId);
                // 关联参数
                $this->itemsRelParams($params, $defaultItemId);
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

    private function processUpdateItem($itemId, $companyId, $params)
    {
        $updateIitemInfo = $this->itemsRepository->getInfo(['item_id' => $itemId, 'company_id' => $companyId]);
        if (!$updateIitemInfo) {
            throw new ResourceException('更新的商品无效');
        }

        // 如果是多规格
        if ($updateIitemInfo['nospec'] === false || $updateIitemInfo['nospec'] === 'false' || $updateIitemInfo['nospec'] === 0 || $updateIitemInfo['nospec'] === '0') {
            $defaultItemId = $updateIitemInfo['default_item_id'];
            $data = $this->itemsRepository->list(['default_item_id' => $defaultItemId, 'company_id' => $companyId]);
            $specItems = json_decode($params['spec_items'], true);
            $newItemIds = array_column($specItems, 'item_id');
            $deleteIds = [];

            $itemStoreService = new ItemStoreService();
            foreach ($data['list'] as $row) {
                // 如果数据库中的商品不在新更新的数据中，则表示需要把数据库中的删除
                if (!in_array($row['item_id'], $newItemIds)) {
                    $deleteIds[] = $row['item_id'];
                    $itemStoreService->deleteItemStore($row['item_id']);
                    // 如果不是店铺商品，那么需要删除关联商品数据
                }
            }

            // 删除商品
            if ($deleteIds) {
                $this->itemsRepository->deleteBy(['item_id' => $deleteIds, 'company_id' => $companyId]);
            }

            // 删除关联分类
            $itemsService = new ItemsRelCatsService();
            $itemsService->deleteBy(['item_id' => $itemId, 'company_id' => $companyId]);
        } else {
            $newItemIds = $itemId;
        }

        // 删除品牌，商品参数，商品规格关联数据
        $this->itemRelAttributesRepository->deleteBy(['item_id' => $newItemIds, 'company_id' => $companyId]);

        if (method_exists($this->itemtypeObject, 'deleteRelItemById')) {
            $this->itemtypeObject->deleteRelItemById($itemId);
        }

        return $updateIitemInfo;
    }

    /**
     * 保存商品关联分类
     */
    private function itemsRelCats($params, $defaultItemId)
    {
        //保存商品分类
        if (isset($params['company_id']) && isset($params['item_category']) && $defaultItemId) {
            $catIds = is_array($params['item_category']) ? $params['item_category'] : [$params['item_category']];
            $itemId = [$defaultItemId];
            $itemsService = new ItemsRelCatsService();
            $result = $itemsService->setItemsCategory($params['company_id'], $itemId, $catIds);
        }
    }

    /**
     * 商品关联品牌 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelBrand($params, $defaultItemId)
    {
        // 保存品牌
        if (isset($params['brand_id']) && trim($params['brand_id'])) {
            // 验证品牌ID是否有效
            $brandData = [
                'company_id' => $params['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => trim($params['brand_id']),
                'attribute_type' => 'brand',
            ];
            $this->itemRelAttributesRepository->create($brandData);
        }
    }

    /**
     * 商品关联参数 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelParams($params, $defaultItemId)
    {
        // 保存参数
        if (isset($params['item_params']) && $params['item_params']) {
            $itemParams = $params['item_params'];
            foreach ($itemParams as $row) {
                $paramsData = [
                    'company_id' => $params['company_id'],
                    'item_id' => $defaultItemId,
                    'attribute_id' => $row['attribute_id'],
                    'attribute_type' => 'item_params',
                    'attribute_value_id' => $row['attribute_value_id'] ?? null,
                    'custom_attribute_value' => $row['attribute_value_name'] ?? null,
                ];
                $this->itemRelAttributesRepository->create($paramsData);
            }
        }
    }

    /**
     * 创建商品
     *
     * @param array $data 已定义的商品参数
     * @param array $params 前台传入的商品参数
     */
    private function createItems($data, $params, $isForceCreate = false)
    {
        // 商品规格特有参数
        $data = $this->itemSpecParams($data, $params);
        if (isset($params['item_id']) && $params['item_id'] && !$isForceCreate) {
            $itemsResult = $this->itemsRepository->update($params['item_id'], $data);
        } else {
            
            $itemsResult = $this->itemsRepository->create($data);
        }
        if ($data['store'] && $data['store'] > 0) {
            $itemStoreService = new ItemStoreService();
            $itemStoreService->saveItemStore($itemsResult['item_id'], $data['store']);
        }
        // 保存参数
        if (isset($params['item_spec'])) {
            $sort = 0;
            foreach ($params['item_spec'] as $row) {
                $itemImageUrl = $data['spec_images'][$row['spec_value_id']] ?? '';
                $tempSort = $row['attribute_sort'] ?? 0;
                $paramsData = [
                    'company_id' => $data['company_id'],
                    'item_id' => $itemsResult['item_id'],
                    'attribute_id' => $row['spec_id'],
                    'attribute_sort' => $tempSort + $sort,
                    'attribute_type' => 'item_spec',
                    'image_url' => $itemImageUrl,
                    'attribute_value_id' => $row['spec_value_id'],
                    'custom_attribute_value' => $row['spec_custom_value_name'] ?? null,
                ];
                $sort++;
                $this->itemRelAttributesRepository->create($paramsData);
            }
        }

        //新增不同类型商品的特殊参数
        if (method_exists($this->itemtypeObject, 'createRelItem')) {
            $itemsResult = $this->itemtypeObject->createRelItem($itemsResult, $params);
        }

        return $itemsResult;
    }

    private function commonParams($params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'item_type' => $params['item_type'] ?? 'services',
            'consume_type' => $params['consume_type'] ?? "every",
            'item_name' => $params['item_name'],
            'item_unit' => $params['item_unit'] ?? '',
            'brief' => $params['brief'] ?? '',
            'sort' => $params['sort'] ?? 1,
            'templates_id' => $params['templates_id'] ?? null,
            'is_show_specimg' => ($params['is_show_specimg'] ?? false) == 'true' ? true : false,
            'pics' => $params['pics'] ?? '',
            'video_type' => $params['video_type'] ?? 'local',
            'videos' => $params['videos'] ?? "",
            'intro' => $params['intro'] ?? '',
            'special_type' => $params['special_type'] ?? 'normal',
            'purchase_agreement' => $params['purchase_agreement'] ?? '',
            'enable_agreement' => ($params['enable_agreement'] ?? false) == 'true' ? true : false,
            'item_category' => $params['item_main_cat_id'] ?? '',
            'nospec' => $params['nospec'] ?? 'true',
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
            'origincountry_id' => $params['origincountry_id'] ?? '0',
            'type' => $params['type'] ?? '0',
            'is_medicine' => $params['is_medicine'] ?? 0,
        ];
        // 如果是腾讯视频，videos存储腾讯视频vid
        if ($data['video_type'] == 'tencent') {
            $data['videos'] = $params['tencent_vid'] ?? '';
        }

        $data['audit_status'] = 'approved';

        if (isset($params['regions_id']) && $params['regions_id']) {
            $data['regions_id'] = implode(',', $params['regions_id']);
        }

        $data['is_show_specimg'] = ($data['is_show_specimg'] == 'true') ? true : false;

        return $data;
    }

    /**
     * 商品规格特有参数
     * 如商品价格等,根据不同规格有不同值
     */
    private function itemSpecParams($data, $params)
    {
        if (!in_array($params['approve_status'], ['onsale', 'offline_sale', 'instock', 'only_show'])) {
            throw new ResourceException('请选择正确的商品状态');
        }
        $data['item_bn'] = $params['item_bn'] ?? '';
        $data['weight'] = $params['weight'] ?? 0;
        $data['weight'] = floatval($data['weight']);
        if (isset($params['volume']) && $params['volume']) {
            $data['volume'] = floatval($params['volume']);
        }
        $data['barcode'] = $params['barcode'] ?? '';
        $data['price'] = bcmul($params['price'], 100);
        // if ($data['price'] <= 0) {
        //     throw new ResourceException('请填写正确的销售价');
        // }

        // 积分兑换价格
        $data['point'] = $params['point'] ?? 0;
        $data['pay_class'] = $params['pay_class'] ?? '';
        if (empty($data['pay_class']) || ! in_array($data['pay_class'], ['point', 'online', 'mix'])) {
            throw new ResourceException('请选择正确的支付方式');
        }
        if ($data['pay_class'] === 'point') {
            $data['price'] = 0;
        } elseif ($data['pay_class'] === 'online') {
            $data['point'] = 0;
        }
        if (in_array($data['pay_class'], ['online', 'mix']) && intval($data['price']) <= 0) {
            throw new ResourceException('请填写正确的销售价格');
        }
        if (in_array($data['pay_class'], ['point', 'mix']) && intval($data['point']) <= 0) {
            throw new ResourceException('请填写正确的积分价格');
        }
        $data['point'] = intval($data['point']);

        $data['cost_price'] = isset($params['cost_price']) ? bcmul($params['cost_price'], 100) : 0;
        $data['market_price'] = $params['market_price'] ? bcmul($params['market_price'], 100) : 0;

        $data['item_unit'] = $data['item_unit'] ?? "个";
        $data['store'] = isset($params['store']) ? intval($params['store']) : 0;
        $data['approve_status'] = $params['approve_status'];
        $data['is_default'] = isset($params['is_default']) ? $params['is_default'] : true;

        //不同商品类型的参数
        if (method_exists($this->itemtypeObject, 'preRelItemParams')) {
            $data = $this->itemtypeObject->preRelItemParams($data, $params);
        }

        $itemsCategoryService = new ItemsCategoryService();
        //检测分类是否存在
        if ($params['item_category'] ?? []) {
            $CategoryId = (array)$params['item_category'];
            $list = $itemsCategoryService->lists(['category_id' => $CategoryId, 'is_main_category' => false]);
            $catlist = $list['list'];
            if (!$catlist || count($catlist) != count($CategoryId)) {
                throw new ResourceException('选中的分类不存在 或 错误');
            }
        }
        //检测主类目实付存在
        if ($params['item_main_cat_id'] ?? 0) {
            $catInfo = $itemsCategoryService->getInfo(['category_id' => $params['item_main_cat_id'], 'is_main_category' => true]);
            if (!$catInfo) {
                throw new ResourceException('您选中的主类目不存在');
            }
        }

        //检测品牌是否存在
        $itemsAttributesService = new ItemsAttributesService();
        if ($params['brand_id'] ?? 0) {
            $info = $itemsAttributesService->getInfo(['attribute_id' => $params['brand_id'], 'attribute_type' => 'brand']);
            if (!$info) {
                throw new ResourceException('您选中的品牌不存在');
            }
        }
        //检测参数是否存在
        if ($params['item_params'] ?? []) {
            foreach ($params['item_params'] as $v) {
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
                    throw new ResourceException('您选中的参数不存在');
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException('您选中的参数值不存在');
                }
            }
        }
        //检测规格是否存在
        if ($params['item_spec'] ?? []) {
            foreach ($params['item_spec'] as $v) {
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
                    throw new ResourceException('您选中的规格不存在');
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException('您选中的规格值不存在');
                }
            }
        }
        //检测运费模板是否存在
        if ($params['templates_id'] ?? 0) {
            $shippingTemplatesService = new ShippingTemplatesService();
            $info = $shippingTemplatesService->getInfo($params['templates_id'], $params['company_id']);
            if (!$info) {
                throw new ResourceException('您选中的运费模板不存在');
            }
        }
        return $data;
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

        $itemsInfo = $this->itemsRepository->get($filter['item_id']);
        if ($filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException('删除商品信息有误');
        }

        // 如果是多规格
        if ($itemsInfo['nospec'] === false || $itemsInfo['nospec'] === 'false' || $itemsInfo['nospec'] === 0 || $itemsInfo['nospec'] === '0') {
            $data = $this->itemsRepository->list(['default_item_id' => $itemsInfo['default_item_id'], 'company_id' => $itemsInfo['company_id']]);
            $itemIds = array_column($data['list'], 'item_id');
            $defaultItemId = $itemsInfo['default_item_id'];
        } else {
            $itemIds = [$itemsInfo['item_id']];
            $defaultItemId = $itemsInfo['item_id'];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemStoreService = new ItemStoreService();
            foreach ($itemIds as $itemId) {
                $this->itemsRepository->delete($itemId);
                $itemStoreService->deleteItemStore($itemId);
                // 删除品牌，商品参数，商品规格关联数据
                $this->itemRelAttributesRepository->deleteBy(['item_id' => $itemId, 'company_id' => $filter['company_id']]);
            }

            $itemsRelCatsService = new ItemsRelCatsService();
            $itemsRelCatsService->deleteBy(['item_id' => $defaultItemId, 'company_id' => $filter['company_id']]);

            $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
            if (method_exists($itemtypeObject, 'deleteRelItemById')) {
                $itemtypeObject->deleteRelItemById($defaultItemId);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取单个商品信息，如果是多规格，也只返回指定ID的信息
     */
    public function getItemsSkuDetail($itemId, $authorizerAppId = null)
    {
        $itemsInfo = $this->itemsRepository->get($itemId);
        // 如果是多规格
        if ($itemsInfo['nospec'] === false || $itemsInfo['nospec'] === 'false' || $itemsInfo['nospec'] === 0 || $itemsInfo['nospec'] === '0') {
            //规格等数据
            $specAttrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemId, 'attribute_type' => 'item_spec'], 1, -1);
            if ($specAttrList['list']) {
                foreach ($specAttrList['list'] as $specAttrRow) {
                    if ($specAttrRow['item_id'] == $itemId && $specAttrRow['image_url']) {
                        $itemsInfo['pics'] = $specAttrRow['image_url'];
                    }
                }
            }
        }

        $itemsInfo['item_type'] = $itemsInfo['item_type'] ?: 'services';
        $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
        $itemsInfo['type_labels'] = [];
        if ($itemsInfo['item_type'] == 'services') {
            $itemsInfo['type_labels'] = $itemtypeObject->listByItemId($itemsInfo['item_id']);
        }

        //置换微信视频
        if (isset($itemsInfo['videos']) && $itemsInfo['videos'] && $authorizerAppId) {
            $itemsInfo = $this->getVideoPicUrl($itemsInfo, $authorizerAppId);
        } else {
            $itemsInfo['videos_url'] = '';
        }
        return $itemsInfo;
    }

    /**
     * 获取商品详情
     *
     * @param inteter item_id 商品id
     * @param inteter limitItemIds 限定的商品ID
     * @return array
     */
    public function getItemsDetail($itemId, $authorizerAppId = null, $limitItemIds = array(), $companyId = null)
    {
        if ($limitItemIds && !in_array($itemId, $limitItemIds)) {
            $itemId = $limitItemIds[0];
        }

        $itemsInfo = $this->itemsRepository->get($itemId);
        if (!$itemsInfo || ($companyId && $itemsInfo['company_id'] != $companyId)) {
            return [];
        }

        if ($itemsInfo['regions_id']) {
            $itemsInfo['regions_id'] = explode(',', $itemsInfo['regions_id']);
        }
        $itemsInfo['item_type'] = $itemsInfo['item_type'] ?: 'services';
        $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
        $itemsInfo['type_labels'] = [];
        if ($itemsInfo['item_type'] == 'services') {
            $itemsInfo['type_labels'] = $itemtypeObject->listByItemId($itemsInfo['item_id']);
        } else {
            // 如果是多规格
            if ($itemsInfo['nospec'] === false || $itemsInfo['nospec'] === 'false' || $itemsInfo['nospec'] === 0 || $itemsInfo['nospec'] === '0') {
                $filter['company_id'] = $itemsInfo['company_id'];
                if ($limitItemIds) {
                    $filter['item_id'] = $limitItemIds;
                } else {
                    $filter['default_item_id'] = $itemsInfo['default_item_id'];
                }
                // 获取多规格的商品id
                $itemsList = $this->itemsRepository->list($filter, null, -1);
                $itemIds = array_column($itemsList['list'], 'item_id');
            } else {
                $itemIds = $itemId;
                $itemsList = array();
            }
            $itemsInfo = $this->__preGetItemRelAttr($itemsInfo, $itemIds, $itemsList);
            $itemsInfo['item_category'] = $this->getCategoryByItemId($itemsInfo['item_id'], $itemsInfo['company_id']);
        }

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

        if (isset($itemsInfo['intro'])) {
            $articleService = new ArticleService();
            $itemsInfo['intro'] = $articleService->proArticleContent($itemsInfo['intro'], $authorizerAppId);
        }

        //置换微信视频
        if (isset($itemsInfo['videos']) && $itemsInfo['videos'] && $authorizerAppId && $itemsInfo['video_type'] != 'tencent') {
            $itemsInfo = $this->getVideoPicUrl($itemsInfo, $authorizerAppId);
        } else {
            $itemsInfo['videos_url'] = '';
        }
        if ($itemsInfo['video_type'] == 'tencent') {
            $itemsInfo['tencent_vid'] = $itemsInfo['videos'];
        }
        $itemsInfo['distributor_sale_status'] = true;
        if (in_array($itemsInfo['approve_status'], ['instock', 'offline_sale', 'only_show'])) {
            $itemsInfo['distributor_sale_status'] = false;
        }
        $itemsInfo['item_total_store'] = $itemsInfo['item_total_store'] ?? $itemsInfo['store'];

        // 产地国家
        if (empty($itemsInfo['origincountry_id'])) {
            $itemsInfo['origincountry_name'] = '';
            $itemsInfo['origincountry_img_url'] = '';
        } else {
            $info = app('registry')->getManager('default')->getRepository(OriginCountry::class)->getInfoById($itemsInfo['origincountry_id']);
            $itemsInfo['origincountry_name'] = $info['origincountry_name'];
            $itemsInfo['origincountry_img_url'] = $info['origincountry_img_url'];
        }

        // 判断是否是跨境商品
        // if ($itemsInfo['type'] == 1) {
        //     $ItemTaxRateService = new ItemTaxRateService($companyId);
        //     $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($itemsInfo['item_id']);

        //     $itemsInfo['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];
        //     $itemsInfo['cross_border_tax'] = bcdiv(bcmul($itemsInfo['price'], $itemsInfo['cross_border_tax_rate'], 0), 100, 0);
        // } else {
        $itemsInfo['tax_rate'] = 0;
        $itemsInfo['cross_border_tax'] = 0;
        // }

        return $itemsInfo;
    }

    /**
     * 更加商品ID获取商品参数属性数据
     */
    public function getItemParamsByItem($itemsInfo)
    {
        //获取品牌，属性参数
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemsInfo['default_item_id'], 'attribute_type' => 'item_params'], 1, -1, ['attribute_sort' => 'asc']);
        $attrList = $attrList['list'];
        if ($attrList) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);
            $itemsInfo = $this->__preGetItemParams($itemsInfo, $attrData);
        }
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
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $defaultItemId], 1, -1, ['attribute_sort' => 'asc']);
        //规格等数据
        $specAttrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        // 临时
        $itemsInfo['spec_pics'] = [];
        if ($specAttrList['list']) {
            foreach ($specAttrList['list'] as $specAttrRow) {
                if ($specAttrRow['item_id'] == $itemsInfo['item_id'] && $specAttrRow['image_url']) {
                    $itemsInfo['spec_pics'] = $specAttrRow['image_url'];
                }
            }
        }

        $itemsInfo['item_params'] = [];
        $itemsInfo['item_spec_desc'] = [];
        $itemsInfo['spec_images'] = [];
        $itemsInfo['spec_items'] = [];

        $attrList = array_merge($attrList['list'], $specAttrList['list']);
        if ($attrList) {
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);

            $itemsInfo['attribute_ids'] = $attrData['attribute_ids'] ?? [];
            $itemsInfo['attr_values_custom'] = $attrData['attr_values_custom'] ?? [];

            $itemsInfo = $this->__preGetItemParams($itemsInfo, $attrData);

            $itemsInfo = $this->__preGetItemSpec($itemsInfo, $attrData, $itemsList);

            if (isset($attrData['brand'])) {
                $itemsInfo['brand_id'] = $attrData['brand']['brand_id'];
                $itemsInfo['goods_brand'] = $attrData['brand']['goods_brand'];
                $itemsInfo['brand_logo'] = $attrData['brand']['brand_logo'];
            }
        }
        return $itemsInfo;
    }

    /**
     * 商品详情，商品规格结构
     */
    private function __preGetItemSpec($itemsInfo, $attrData, $itemsList)
    {
        if (!isset($attrData['item_spec']) || !$attrData['item_spec']) {
            return $itemsInfo;
        }

        $itemsInfo['item_spec_desc'] = $attrData['item_spec_desc'];
        $itemsInfo['spec_images'] = $attrData['spec_images'];
        $totalStore = 0;
        $approveStatus = [];
        foreach ($itemsList['list'] as $itemRow) {
            $itemSpec = $attrData['item_spec'][$itemRow['item_id']];
            $tempItemSpec = [];
            foreach ($itemSpec as $itemSpecRow) {
                $tempItemSpec[] = $itemSpecRow;
            }

            $approveStatus[] = $itemRow['approve_status'];
            $itemsInfo['spec_items'][] = [
                'item_id' => $itemRow['item_id'],
                'price' => $itemRow['price'],
                'store' => $itemRow['store'],
                'cost_price' => $itemRow['cost_price'],
                'item_bn' => $itemRow['item_bn'],
                'barcode' => $itemRow['barcode'],
                'market_price' => $itemRow['market_price'],
                'point' => $itemRow['point'],
                'pay_class' => $itemRow['pay_class'],
                'item_unit' => $itemRow['item_unit'],
                'volume' => $itemRow['volume'],
                'approve_status' => $itemRow['approve_status'],
                'is_default' => $itemRow['is_default'],
                'weight' => $itemRow['weight'],
                'item_spec' => $tempItemSpec,
            ];
            $totalStore += $itemRow['store'];
        }

        if (in_array('onsale', $approveStatus)) {
            $itemsInfo['approve_status'] = 'onsale';
        } elseif (in_array('only_show', $approveStatus)) {
            $itemsInfo['approve_status'] = 'only_show';
        } elseif (in_array('offline_sale', $approveStatus)) {
            $itemsInfo['approve_status'] = 'offline_sale';
        } else {
            $itemsInfo['approve_status'] = 'instock';
        }

        $itemsInfo['item_total_store'] = $totalStore;
        return $itemsInfo;
    }

    /**
     * 商品详情，商品参数结构
     */
    private function __preGetItemParams($itemsInfo, $attrData)
    {
        $itemsInfo['item_params'] = [];
        if (isset($attrData['item_params'])) {
            $itemsInfo['item_params'] = $attrData['item_params'];
        } else {
            if ($itemsInfo['goods_brand'] && !isset($itemsInfo['brand_id'])) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '品牌',
                    'attribute_value_name' => $itemsInfo['goods_brand'],
                ];
            }
            if ($itemsInfo['goods_color']) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '颜色',
                    'attribute_value_name' => $itemsInfo['goods_color'],
                ];
            }
            if ($itemsInfo['goods_function']) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '功能',
                    'attribute_value_name' => $itemsInfo['goods_function'],
                ];
            }
            if ($itemsInfo['goods_series']) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '系列',
                    'attribute_value_name' => $itemsInfo['goods_series'],
                ];
            }
        }
        return $itemsInfo;
    }

    /** 获取商品积分
     * @param $itemId 商品id
     * @param null
     * @return mixed
     */
    public function getItemsPoint($itemId)
    {
        $itemsInfo = $this->itemsRepository->get($itemId);
        return $itemsInfo['is_point'] ? $itemsInfo['point'] : false;
    }

    /**
     * 将商品详情中的视频转为对应的URL地址
     */
    private function getVideoPicUrl($itemsInfo, $authorizerAppId)
    {
        if (preg_match('/(http:\/\/)|(https:\/\/)/i', $itemsInfo['videos'])) {
            $itemsInfo['videos_url'] = $itemsInfo['videos'];
        } else {
            $service = new MaterialService();
            $service = $service->application($authorizerAppId);
            $detail = $service->getMaterial($itemsInfo['videos']);
            if (isset($detail['down_url']) && $detail['down_url']) {
                $itemsInfo['videos_url'] = $detail['down_url'];
            } else {
                $itemsInfo['videos_url'] = '';
                $itemsInfo['videos'] = '';
            }
        }

        return $itemsInfo;
    }

    private function getCategoryByItemId($itemId, $companyId)
    {
        $itemsService = new ItemsRelCatsService();
        $filter['item_id'] = $itemId;
        $filter['company_id'] = $companyId;
        $data = $itemsService->lists($filter);
        if ($data['list']) {
            $catIds = array_column($data['list'], 'category_id');
            return $catIds;
        }
        return [];
    }

    /**
     * 更新销量
     * @param $itemId 商品id
     * @param $sales 商品数量
     * @return mixed
     */
    public function incrSales($itemId, $sales)
    {
        return $this->itemsRepository->updateSales($itemId, $sales);
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
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $itemsList = $this->itemsRepository->list($filter, $orderBy, $pageSize, $page);
        foreach ($itemsList['list'] as $key => &$v) {
            $v['item_cat_id'] = $this->getCategoryByItemId($v['item_id'], $v['company_id']);
            $v['item_type'] = $v['item_type'] ?: 'services';
            $itemtypeObject = new $this->itemsTypeClass[$v['item_type']]();
            if (isset($v['itemId']) && method_exists($itemtypeObject, 'listByItemId')) {
                $v['type_labels'] = $itemtypeObject->listByItemId($v['itemId'], $v);
            } else {
                $v['type_labels'] = [];
            }
        }
        return $itemsList;
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
                $row['item_id'] = $itemId;
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

    /**
     * 实体类商品获取sku
     */
    public function getSkuItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $itemsList = $this->itemsRepository->list($filter, $orderBy, $pageSize, $page);
        if ($itemsList['total_count'] <= 0) {
            return $itemsList;
        }

        $itemsList = $this->replaceSkuSpec($itemsList);
        return $itemsList;
    }

    public function replaceSkuSpec($itemsList)
    {
        $itemIds = array_column($itemsList['list'], 'item_id');
        // 规格等数据
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList['list']);
        }

        foreach ($itemsList['list'] as &$itemRow) {
            $itemRow['is_gift'] = 0;
            $itemRow['item_type'] = $itemRow['item_type'] ?: 'services';
            $itemtypeObject = new $this->itemsTypeClass[$itemRow['item_type']]();
            if (isset($itemRow['itemId']) && method_exists($itemtypeObject, 'listByItemId')) {
                $itemRow['type_labels'] = $itemtypeObject->listByItemId($itemRow['itemId'], $itemRow);
            } else {
                $itemRow['type_labels'] = [];
            }

            if (!$itemRow['default_item_id']) {
                $itemRow['default_item_id'] = $itemRow['item_id'];
            }
            $sum_filter = [
                'default_item_id' => $itemRow['default_item_id'],
                'company_id' => $itemRow['company_id'],
            ];
            $itemRow['goods_store'] = $this->itemsRepository->sum($sum_filter, 'store');
            if (isset($attrData['item_spec']) && isset($attrData['item_spec'][$itemRow['item_id']])) {
                $itemSpecStr = [];
                foreach ($attrData['item_spec'][$itemRow['item_id']] as $row) {
                    if ($row['item_image_url']) {
                        //列表页商品图片被替换成了自定义规格图片，应要求取消掉替换
                        //$itemRow['pics'] = $row['item_image_url'] ?: $itemRow['pics'];
                    }
                    $itemRow['item_spec'][] = $row;
                    $itemSpecStr[] = $row['spec_name'] . ':' . $row['spec_value_name'];
                }
                $itemRow['item_spec_desc'] = implode(',', $itemSpecStr);
            }
        }
        return $itemsList;
    }

    /**
     * 根据商品ID新增权益
     */
    public function addRightsByItemId($itemId, $userId, $companyId, $mobile, $rightsFrom = null, $num = 1)
    {
        $itemsInfo = $this->getItemsSkuDetail($itemId);
        //如果不是服务商品，那么则不能新增权益
        if ($itemsInfo['item_type'] != 'services') {
            return true;
        }

        $rightsObj = new RightsService(new TimesCardService());
        //商品核销类型为团购券
        if ($itemsInfo['consume_type'] == 'all') {
            if ($itemsInfo['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
                $start_time = $itemsInfo['begin_date'];
                $end_time = $itemsInfo['end_date'];
            }
            if ($itemsInfo['date_type'] == 'DATE_TYPE_FIX_TERM') {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $itemsInfo['fixed_term']));
            }
            $labelInfos = [];
            foreach ($itemsInfo['type_labels'] as $v) {
                $labelInfos[] = ['label_id' => $v['labelId'], 'label_name' => $v['labelName']];
            }
            $data = [
                'user_id' => $userId,
                'company_id' => $itemsInfo['company_id'],
                'rights_name' => $itemsInfo['item_name'],
                'rights_subname' => '',
                'total_num' => $num,
                'total_consum_num' => 0,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'order_id' => 0,
                'can_reservation' => false,
                'label_infos' => $labelInfos,
                'rights_from' => $rightsFrom ?: '注册赠送',
                'mobile' => $mobile,
                'is_not_limit_num' => 2,
            ];
            $rightsObj->addRights($companyId, $data);
        } elseif ($itemsInfo['consume_type'] == 'every') {
            foreach ($itemsInfo['type_labels'] as $v) {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $v['limitTime']));
                $data = [
                    'user_id' => $userId,
                    'company_id' => $v['companyId'],
                    'rights_name' => $itemsInfo['item_name'],
                    'rights_subname' => $v['labelName'],
                    'total_num' => $v['num'] * $num,
                    'total_consum_num' => 0,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'order_id' => 0,
                    'can_reservation' => true,
                    'label_infos' => [['label_id' => $v['labelId'], 'label_name' => $v['labelName']]],
                    'rights_from' => $rightsFrom ?: '注册赠送',
                    'mobile' => $mobile,
                    'is_not_limit_num' => $v['isNotLimitNum'],
                ];
                $rightsObj->addRights($companyId, $data);
            }
            return true;
        }
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
            foreach ($params['item_id'] as $v) {
                $itemsInfo = $this->itemsRepository->get($v);

                if ($params['company_id'] != $itemsInfo['company_id']) {
                    throw new ResourceException('请确认您的商品信息后再提交.');
                }

                $itemsResult = $this->itemsRepository->updateBy(['default_item_id' => $v], ['templates_id' => $params['templates_id']]);
            }

            $conn->commit();
            return $itemsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 修改商品商品分类
     *
     * @param array params 提交的商品数据
     * @return array
     */
    public function setItemsCategory($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($params['item_id'] as $v) {
                $itemsInfo = $this->itemsRepository->get($v);

                if ($params['company_id'] != $itemsInfo['company_id']) {
                    throw new ResourceException('请确认您的商品信息后再提交.');
                }

                $itemsResult = $this->itemsRepository->setCategoryId($v, $params['category_id']);
            }
            $conn->commit();
            return $itemsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 商品排序
     * @param $params 查询条件
     * @param $sort 排序编号
     * @return mixed
     */
    public function setItemsSort($filter, $sort)
    {
        $itemsInfo = $this->itemsRepository->get($filter['item_id']);
        if ($filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException('请确认您的商品信息后再提交.');
        }
        $itemsResult = $this->itemsRepository->updateSort($filter['item_id'], $sort);
        return $itemsResult;
    }

    // 获取商品分销码
    public function getDistributionGoodsWxaCode($wxaappid, $itemId, $isBase64 = 0)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        try {
            $data['page'] = 'pages/item/espier-detail';
            $scene = 'id=' . $itemId;
            $wxaCode = $app->app_code->getUnlimit($scene, $data);
        } catch (\Exception $e) {
            $data['page'] = 'pages/goodsdetail';
            $scene = 'id=' . $itemId;
            $wxaCode = $app->app_code->getUnlimit($scene, $data);
        }
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }

    public function getItemListData($filter, $page = 1, $pageSize = 100, $orderBy = ['item_id' => 'DESC'], $isShowItemParams = false)
    {
        $listData['total_count'] = 0;
        $listData['list'] = [];

        $itemIds = [];
        $tagItemIds = [];
        if (isset($filter['item_id']) && $filter['item_id']) {
            $itemIds = $filter['item_id'];
            $tagItemIds = $itemIds;
        }

        // 根据商品分类id，获取到对应的商品ID
        if (isset($filter['category_id']) && $filter['category_id']) {
            $itemIds = $this->getItemIdsByCategoryId($filter, $itemIds);
            if ($itemIds == -1 || !$itemIds) {
                return $listData;
            }
            $tagItemIds = $itemIds;
        }
        // 根据商品参数刷选商品ID
        if (isset($filter['item_params']) && $filter['item_params']) {
            $itemIds = $this->getItemIdsByItemParamsId($filter, $itemIds);
            if ($itemIds == -1) {
                return $listData;
            }
        }

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $itemIds = $this->getItemsIdByTags($filter, $itemIds);
            if (!$itemIds) {
                return $listData;
            }
        }

        if ($itemIds) {
            $filter['item_id'] = $itemIds;
        }

        $newFilter = $this->__filter($filter);
        $listData = $this->itemsRepository->list($newFilter, $orderBy, $pageSize, $page);
        if ($listData['list']) {
            foreach ($listData['list'] as $key => $item) {
                $sum_filter = [
                    'default_item_id' => $item['default_item_id'],
                    'company_id' => $item['company_id'],
                ];
                $item['goods_store'] = $this->itemsRepository->sum($sum_filter, 'store');
                $listData['list'][$key] = $item;
            }
        }
        if ($isShowItemParams) {
            $catFilter = $this->__filter($filter);
            $categorys = $this->itemsRepository->countItemsMainCatIdBy($catFilter);
            if ($categorys) {
                $mainCategoryId = $categorys[0]['item_category'];
                $selectList = $this->getItemSelectList($mainCategoryId, $filter);
                $listData['item_params_list'] = $selectList['item_params_list'] ?? [];
                $listData['select_address_list'] = $selectList['select_address_list'] ?? [];
            } else {
                $listData['item_params_list'] = [];
                $listData['select_address_list'] = [];
            }
        }
        $brandList = $this->getItemBrandList($filter);
        $listData['brand_list'] = $brandList['brand_list'] ?? [];
        return $listData;
    }

    /**
     * 返回参数商品筛选
     *
     * array([
     *  'attribute_name' => '系列',
     *  'attribute_id' => 1,
     *  'values' => [
     *      ['name'=>'美白', 'attribute_value_id'=>2],
     *      ['name'=>'美白2', 'attribute_value_id'=>23],
     *  ],
     * ])
     */
    public function getItemSelectList($mainCategoryId, $filter)
    {
        unset($filter['brand_id']);
        unset($filter['tag_id']);
        $filter = $this->__filter($filter);
        $listData = $this->itemsRepository->list($filter, [], 1000, 1, ['item_id', 'item_type', 'default_item_id', 'item_address_province']);
        if ($listData['total_count'] <= 0) {
            return [];
        }
        // 产品产地
        $itemSelectList = [];
        $itemAddressProvince = [];
        $itemIds = [];
        foreach ($listData['list'] as $row) {
            if ($row['item_address_province']) {
                $itemAddressProvince[$row['item_address_province']] = $row['item_address_province'];
            }
            $itemIds[] = $row['item_id'];
        }
        if ($itemAddressProvince) {
            $itemSelectList['select_address_list'] = array_keys($itemAddressProvince);
        }

        // 获取分类关联的参数
        $itemsCategoryService = new ItemsCategoryService();
        $catInfo = $itemsCategoryService->getInfo(['category_id' => $mainCategoryId, 'is_main_category' => true]);
        if (!$catInfo || !$catInfo['goods_params']) {
            return $itemSelectList;
        }

        $relAttrFilter['item_id'] = $itemIds;
        $relAttrFilter['attribute_id'] = $catInfo['goods_params'];
        $relAttrFilter['attribute_type'] = "item_params";
        $list = $this->itemRelAttributesRepository->getItemRelAttributeBy($relAttrFilter);
        if (!$list) {
            return $itemSelectList;
        }
        foreach ($list as $row) {
            $data[$row['attribute_id']][] = $row['attribute_value_id'];
            $attributeValueIds[] = $row['attribute_value_id'];
            $attributeIds[] = $row['attribute_id'];
        }
        $itemsAttributesService = new ItemsAttributesService();
        $itemSelectList['item_params_list'] = $itemsAttributesService->getAttrValuesList($attributeIds, $attributeValueIds, $data);
        return $itemSelectList;
    }

    /**
     * 获取单个商品信息
     *
     * @param array $filter
     * @return array
     */
    public function getItem($filter = array())
    {
        $itemInfo = $this->itemsRepository->getInfo($filter);
        return $itemInfo;
    }

    /**
     * 获取商品品牌列表
     * @param $filter
     * @return array
     */
    public function getItemBrandList($filter)
    {
        $itemsAttributesService = new ItemsAttributesService();
        return $itemsAttributesService->getBrandList($filter);
    }

    /**
     * 商品条件过滤
     */
    public function __filter($filter)
    {
        $filterCols = ['item_id', 'is_point', 'approve_status', 'company_id', 'approve_status', 'item_type', 'is_default', 'regions_id', 'goods_id', 'brand_id', 'rebate', 'price', 'point', 'item_category', 'audit_status'];

        $_filterCols = [];
        foreach ($filter as $key => $value) {
            $list = explode('|', $key);
            if (count($list) > 1 && in_array($list[0], $filterCols)) {
                $_filterCols[$list[0]][$list[1]] = $value;
            }
        }

        foreach ($filterCols as $col) {
            $list = explode('|', $col);
            if (count($list) > 1 && !isset($filter[$list[0]])) {
                continue;
            }

            if (isset($filter[$col])) {
                $newfilter[$col] = $filter[$col];
            }
            if (isset($_filterCols[$col])) {
                foreach ($_filterCols[$col] as $key => $value) {
                    $newfilter[$col.'|'.$key] = $value;
                }
            }
        }

        if (isset($filter['item_id']) && empty($filter['item_id'])) {
            unset($newfilter['item_id']);
        }

        if (isset($filter['item_name']) && $filter['item_name']) {
            $newfilter['item_name|contains'] = $filter['item_name'];
        }
        return $newfilter;
    }

    /**
     * 根据商品参数刷选商品ID
     */
    public function getItemIdsByItemParamsId($filter, $itemIds = [])
    {
        $attributeValueIds = [];
        foreach ($filter['item_params'] as $row) {
            if ($row['attribute_value_id'] == 'all') {
                continue;
            }
            $attributeIds[$row['attribute_id']] = 1;
            $attributeValueIds[] = $row['attribute_value_id'];
        }

        if ($attributeValueIds) {
            $companyId = $filter['company_id'] ?? null;
            $ids = $this->itemRelAttributesRepository->getItemdsByAttrValIds($attributeValueIds, $attributeIds, $companyId);
            if ($ids) {
                if ($itemIds) {
                    $itemIds = array_intersect($itemIds, $ids);
                } else {
                    $itemIds = $ids;
                }
            } else {
                $itemIds = -1;
            }

            if (!$itemIds) {
                $itemIds = -1;
            }
        }

        return $itemIds;
    }

    /**
     * 根据商品分类获取商品id集合，非主类目
     */
    public function getItemIdsByCategoryId($filter, $itemIds = [])
    {
        $itemsCategoryService = new ItemsCategoryService();
        $tmpItemIds = $itemsCategoryService->getItemIdsByCatId($filter['category_id'], $filter['company_id']);
        if (!$tmpItemIds) {
            return -1;
        }

        if ($itemIds) {
            $itemIds = array_intersect($itemIds, $tmpItemIds);
        } else {
            $itemIds = $tmpItemIds;
        }

        if (!$itemIds) {
            return -1;
        }

        return $itemIds;
    }

    public function getItemCount($filter)
    {
        return $this->itemsRepository->count($filter);
    }

    public function simpleUpdateBy($filter, $params)
    {
        return $this->itemsRepository->simpleUpdateBy($filter, $params);
    }

    public function updateItemsStore($companyId, $params)
    {
        $itemStoreService = new ItemStoreService();
        foreach ((array)$params as $data) {
            if (isset($data['is_default']) && $data['is_default'] == 'true') {
                $filter['company_id'] = $companyId;
                $filter['default_item_id'] = $data['item_id'];
                $this->itemsRepository->updateBy($filter, ['store' => $data['store']]);
                $itemlist = $this->itemsRepository->getItemsLists($filter);
                foreach ($itemlist as $value) {
                    $itemStoreService->saveItemStore($value['item_id'], $data['store']);
                }
            } else {
                $this->itemsRepository->updateStore($data['item_id'], $data['store']);
                $itemStoreService->saveItemStore($data['item_id'], $data['store']);
            }
        }
        return true;
    }

    public function updateItemsStatus($companyId, $items, $status)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        foreach ((array)$items as $data) {
            $filter['company_id'] = $companyId;
            $filter['goods_id'] = $data['goods_id'];
            $this->itemsRepository->updateBy($filter, ['approve_status' => $status]);
        }
        return true;
    }

    public function getWxaItemCodeStream($companyId, $itemId, $isBase64 = 0)
    {
        $weappService = new WeappService();
        $wxaappid = $weappService->getWxappidByTemplateName($companyId);
        if (!$wxaappid) {
            return '';
        }
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        $data['page'] = 'pages/goodsdetail';
        $scene = 'id=' . $itemId;
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }

    public function getItemsListBrandData($itemList, $companyId)
    {
        $brandId = array_column($itemList, 'brand_id');
        $itemsAttributesService = new ItemsAttributesService();
        $brandId = array_filter($brandId);
        if (!$brandId) {
            return $itemList;
        }
        $filter['attribute_id'] = $brandId;
        $filter['company_id'] = $companyId;
        $filter['attribute_type'] = 'brand';
        $brandlist = $itemsAttributesService->getLists($filter);
        if (!$brandlist) {
            return $itemList;
        }
        $brandlist = array_column($brandlist, null, 'attribute_id');
        foreach ($itemList as &$list) {
            $list['brand_logo'] = $brandlist[$list['brand_id']]['image_url'] ?? '';
            $list['brand_name'] = $brandlist[$list['brand_id']]['attribute_name'] ?? '';
        }
        return $itemList;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsRepository->$method(...$parameters);
    }
}
