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
use SupplierBundle\Entities\SupplierItemsAttrDraft;
use SupplierBundle\Repositories\SupplierItemsDraftRepository;
use SupplierBundle\Support\SupplierItemsDraftFields;

/**
 * 供应商商品草稿（staging）领域服务。
 *
 * 数据模型：
 * - supplier_items_draft：按 goods_id + source_item_id 存 SKU 级 content_json
 * - supplier_items_attr_draft：分类/品牌/规格/参数等待审属性
 *
 * 生命周期：
 * 1. saveDraftSku / saveAttrDraft —— 供应商编辑保存（processing 期间可覆盖）
 * 2. overlayDraftOnMainRows —— 详情读路径展示待审内容
 * 3. mergeDraftToMain —— 平台 approved 后合并到主表并删 draft
 * 4. deleteDraftByGoodsId —— 平台 rejected 后丢弃 draft
 */
class SupplierItemsDraftService
{
    /** @var SupplierItemsDraftRepository SKU 草稿表访问 */
    public $draftRepository;

    /** @var \SupplierBundle\Repositories\SupplierItemsAttrDraftRepository 属性草稿表访问 */
    public $attrDraftRepository;

    public function __construct()
    {
        $this->draftRepository = new SupplierItemsDraftRepository();
        $this->attrDraftRepository = app('registry')->getManager('default')->getRepository(SupplierItemsAttrDraft::class);
    }

    /**
     * 指定 SPU 是否存在待审 draft 行（任意 SKU 有记录即 true）。
     */
    public function hasPendingDraft($goodsId, $companyId = null)
    {
        return $this->draftRepository->existsByGoodsId($goodsId, $companyId);
    }

    /**
     * 供应商 SKU 是否已在平台商品池 items 中建立 supplier_item_id 映射。
     *
     * 有映射表示商品曾同步到 C 端，即使 audit_status 非 approved 也应 staging 保护。
     */
    public function hasPlatformMapping(array $sourceItemIds)
    {
        if (!$sourceItemIds) {
            return false;
        }
        $itemsService = new ItemsService();
        $rows = $itemsService->itemsRepository->getItemsLists(['supplier_item_id' => $sourceItemIds], 'item_id');
        return !empty($rows);
    }

    /**
     * 按 SPU 下全部 SKU 主表行判定是否走 staging 写入。
     *
     * 取首行 audit_status + 全 SKU 的 platform 映射，委托 DraftFields::shouldUseStaging。
     */
    public function shouldUseStagingForGoods(array $mainSkuRows)
    {
        if (!$mainSkuRows) {
            return false;
        }
        $default = $mainSkuRows[0];
        $auditStatus = $default['audit_status'] ?? '';
        $itemIds = array_column($mainSkuRows, 'item_id');
        return SupplierItemsDraftFields::shouldUseStaging($auditStatus, $this->hasPlatformMapping($itemIds));
    }

    /**
     * 获取某 SPU 下全部 SKU draft，content_json 已 decode 合并到行内。
     */
    public function getDraftSkusByGoodsId($goodsId, $companyId = null)
    {
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $rows = $this->draftRepository->getLists($filter, '*', 1, -1, ['source_item_id' => 'ASC']);
        return $this->draftRepository->decodeRows($rows);
    }

    /**
     * 保存或更新单个 SKU 的 draft 行（upsert by source_item_id + goods_id）。
     *
     * @param array $meta    draft 表列：source_item_id, goods_id, company_id 等
     * @param array $content 待审内容字段，会经 splitRow 过滤后写入 content_json
     */
    public function saveDraftSku(array $meta, array $content)
    {
        $split = SupplierItemsDraftFields::splitRow(array_merge($meta, $content));
        $payload = [
            'source_item_id' => $meta['source_item_id'],
            'goods_id' => $meta['goods_id'],
            'company_id' => $meta['company_id'],
            'supplier_id' => $meta['supplier_id'] ?? 0,
            'default_item_id' => $meta['default_item_id'] ?? null,
            'is_default' => $meta['is_default'] ?? 0,
            'content' => $split['content'],
        ];

        $existing = $this->draftRepository->getInfo([
            'source_item_id' => $payload['source_item_id'],
            'goods_id' => $payload['goods_id'],
        ]);
        if ($existing) {
            return $this->draftRepository->updateOneBy(['draft_id' => $existing['draft_id']], $payload);
        }

        return $this->draftRepository->create($payload);
    }

    /**
     * 删除某 SPU 全部 draft（SKU + attr），用于驳回或 merge 完成后清理。
     */
    public function deleteDraftByGoodsId($goodsId, $companyId = null)
    {
        $this->draftRepository->deleteByGoodsId($goodsId, $companyId);
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $this->attrDraftRepository->deleteBy($filter);
    }

    /**
     * 保存属性 draft（category / brand / item_spec / item_params）。
     *
     * 与 SupplierItemsAttrService::saveAttrData 对称，staging 期间写 attr_draft 表。
     */
    public function saveAttrDraft(array $filter, $attrData, $goodsId)
    {
        if (is_array($attrData)) {
            $attrData = json_encode($attrData, JSON_UNESCAPED_UNICODE);
        }
        $filter['goods_id'] = $goodsId;
        $rsAttr = $this->attrDraftRepository->getInfo($filter);
        if ($rsAttr) {
            return $this->attrDraftRepository->updateOneBy(['id' => $rsAttr['id']], ['attr_data' => $attrData, 'is_del' => 0]);
        }
        $filter['attr_data'] = $attrData;
        $filter['is_del'] = 0;
        return $this->attrDraftRepository->create($filter);
    }

    /**
     * 标记属性 draft 待删除（save 前先软删旧关联，与主表 attr 的 setDelData 模式一致）。
     */
    public function setAttrDelData(array $filter, $goodsId)
    {
        $filter['goods_id'] = $goodsId;
        if ($this->attrDraftRepository->getInfo($filter)) {
            $this->attrDraftRepository->updateBy($filter, ['is_del' => 1]);
        }
    }

    /**
     * 物理删除已标记 is_del=1 的属性 draft 行。
     */
    public function execAttrDelData(array $filter, $goodsId)
    {
        $filter['goods_id'] = $goodsId;
        $filter['is_del'] = 1;
        $this->attrDraftRepository->deleteBy($filter);
    }

    /**
     * 平台审核通过：draft SKU + attr 合并到主表，然后删除全部 draft。
     *
     * 关键节点：按 source_item_id 逐 SKU merge，仅覆盖 content 字段，不动 store/audit 等 MAIN_ONLY。
     *
     * @param int|string           $goodsId                   SPU goods_id
     * @param object               $supplierItemsRepository   SupplierItems Repository
     * @param int|string|null      $companyId
     * @return bool false 表示无 draft 可 merge
     */
    public function mergeDraftToMain($goodsId, $supplierItemsRepository, $companyId = null)
    {
        $draftSkus = $this->getDraftSkusByGoodsId($goodsId, $companyId);
        if (!$draftSkus) {
            return false;
        }

        foreach ($draftSkus as $draftSku) {
            $sourceItemId = $draftSku['source_item_id'];
            $mainRow = $supplierItemsRepository->getInfo(['item_id' => $sourceItemId]);
            if (!$mainRow) {
                continue;
            }
            $content = SupplierItemsDraftFields::splitRow($draftSku)['content'];
            $updateData = SupplierItemsDraftFields::mergeContentIntoRow($mainRow, $content);
            unset($updateData['item_id']);
            $supplierItemsRepository->updateOneBy(['item_id' => $sourceItemId], $updateData);
        }

        $this->mergeAttrDraftToMain($goodsId, $companyId);
        $this->deleteDraftByGoodsId($goodsId, $companyId);

        return true;
    }

    /**
     * 将 attr_draft 合并到 supplier_items_attr 主表。
     */
    private function mergeAttrDraftToMain($goodsId, $companyId = null)
    {
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $draftAttrs = $this->attrDraftRepository->getLists($filter);
        if (!$draftAttrs) {
            return;
        }

        $attrService = new SupplierItemsAttrService();
        foreach ($draftAttrs as $draftAttr) {
            $itemId = $draftAttr['item_id'];
            $mainFilter = [
                'company_id' => $draftAttr['company_id'],
                'item_id' => $itemId,
                'attribute_id' => $draftAttr['attribute_id'],
                'attribute_type' => $draftAttr['attribute_type'],
            ];
            $attrData = json_decode($draftAttr['attr_data'], true);
            if (!is_array($attrData)) {
                continue;
            }
            $attrService->saveAttrData($mainFilter, $attrData);
        }
    }

    /**
     * 详情读路径：用 draft 覆盖主表行列表中的内容字段（内存 overlay，不写库）。
     */
    public function overlayDraftOnMainRows(array $mainRows, $goodsId, $companyId = null)
    {
        $draftSkus = $this->getDraftSkusByGoodsId($goodsId, $companyId);
        if (!$draftSkus) {
            return $mainRows;
        }
        $draftBySource = [];
        foreach ($draftSkus as $draftSku) {
            $draftBySource[$draftSku['source_item_id']] = $draftSku;
        }

        return SupplierItemsDraftFields::overlayDraftRows($mainRows, $draftBySource);
    }

    /**
     * 从 attr_draft 读取单个属性类型的数据（如 category），供 getItemsDetail 使用。
     */
    public function getAttrData($itemId, $attributeType, $goodsId, $companyId = null)
    {
        $filter = [
            'item_id' => $itemId,
            'goods_id' => $goodsId,
            'attribute_type' => $attributeType,
        ];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $rsAttr = $this->attrDraftRepository->getInfo($filter);
        if ($rsAttr && $rsAttr['attr_data']) {
            $attrData = json_decode($rsAttr['attr_data'], true);
            return $attrData[$attributeType] ?? [];
        }
        return [];
    }

    /**
     * 从 attr_draft 批量读取属性列表（规格图、item_spec 等），结构与主表 attr 查询对齐。
     */
    public function getAttrDataList($itemIds, $goodsId, $attributeTypes = [], $companyId = null)
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }
        $filter = [
            'item_id' => $itemIds,
            'goods_id' => $goodsId,
        ];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        if ($attributeTypes) {
            $filter['attribute_type'] = $attributeTypes;
        }
        $rsAttr = $this->attrDraftRepository->getLists($filter);
        $res = [];
        if ($rsAttr) {
            foreach ($rsAttr as $v) {
                if ($v['attr_data']) {
                    $attrData = json_decode($v['attr_data'], true);
                    if (is_array($attrData[$v['attribute_type']] ?? null)) {
                        $v = array_merge($v, $attrData[$v['attribute_type']]);
                    }
                }
                $v['image_url'] = $v['image_url'] ?? '';
                $v['custom_attribute_value'] = $v['custom_attribute_value'] ?? '';
                $res[] = $v;
            }
        }
        return $res;
    }
}
