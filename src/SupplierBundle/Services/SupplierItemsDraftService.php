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

class SupplierItemsDraftService
{
    /** @var SupplierItemsDraftRepository */
    public $draftRepository;

    /** @var \SupplierBundle\Repositories\SupplierItemsAttrDraftRepository */
    public $attrDraftRepository;

    public function __construct()
    {
        $this->draftRepository = new SupplierItemsDraftRepository();
        $this->attrDraftRepository = app('registry')->getManager('default')->getRepository(SupplierItemsAttrDraft::class);
    }

    public function hasPendingDraft($goodsId, $companyId = null)
    {
        return $this->draftRepository->existsByGoodsId($goodsId, $companyId);
    }

    public function hasPlatformMapping(array $sourceItemIds)
    {
        if (!$sourceItemIds) {
            return false;
        }
        $itemsService = new ItemsService();
        $rows = $itemsService->itemsRepository->getItemsLists(['supplier_item_id' => $sourceItemIds], 'item_id');
        return !empty($rows);
    }

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

    public function getDraftSkusByGoodsId($goodsId, $companyId = null)
    {
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $rows = $this->draftRepository->getLists($filter, '*', 1, -1, ['source_item_id' => 'ASC']);
        return $this->draftRepository->decodeRows($rows);
    }

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

    public function deleteDraftByGoodsId($goodsId, $companyId = null)
    {
        $this->draftRepository->deleteByGoodsId($goodsId, $companyId);
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $this->attrDraftRepository->deleteBy($filter);
    }

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

    public function setAttrDelData(array $filter, $goodsId)
    {
        $filter['goods_id'] = $goodsId;
        if ($this->attrDraftRepository->getInfo($filter)) {
            $this->attrDraftRepository->updateBy($filter, ['is_del' => 1]);
        }
    }

    public function execAttrDelData(array $filter, $goodsId)
    {
        $filter['goods_id'] = $goodsId;
        $filter['is_del'] = 1;
        $this->attrDraftRepository->deleteBy($filter);
    }

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
