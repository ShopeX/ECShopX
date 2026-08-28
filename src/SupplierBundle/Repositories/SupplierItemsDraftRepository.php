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

namespace SupplierBundle\Repositories;

/**
 * supplier_items_draft 表访问层。
 *
 * 表结构要点：
 * - source_item_id：对应主表 supplier_items.item_id
 * - goods_id：SPU 维度，便于按整组商品查询/删除
 * - content_json：SKU 待审内容（decodeRow 时 merge 到行内供业务层使用）
 */
class SupplierItemsDraftRepository
{
    public $table = 'supplier_items_draft';

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function connection()
    {
        return app('registry')->getConnection('default');
    }

    public function getLists(array $filter, $cols = '*', $page = 1, $pageSize = -1, array $orderBy = [])
    {
        $conn = $this->connection();
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->filter($filter, $qb);
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(max(0, ($page - 1) * $pageSize))->setMaxResults($pageSize);
        }

        return $qb->execute()->fetchAll();
    }

    public function getInfo(array $filter)
    {
        $rows = $this->getLists($filter, '*', 1, 1);
        return $rows[0] ?? null;
    }

    public function hasBarcodeConflict($companyId, array $barcodes, array $excludeSourceItemIds = [])
    {
        if (!$barcodes) {
            return false;
        }

        $rows = $this->decodeRows($this->getLists(['company_id' => $companyId]));
        foreach ($rows as $row) {
            if (in_array($row['source_item_id'], $excludeSourceItemIds)) {
                continue;
            }
            if (in_array(trim((string)($row['barcode'] ?? '')), $barcodes, true)) {
                return true;
            }
        }

        return false;
    }

    public function existsByGoodsId($goodsId, $companyId = null)
    {
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }

        $conn = $this->connection();
        $qb = $conn->createQueryBuilder()
            ->select('COUNT(1) AS cnt')
            ->from($this->table);
        $qb = $this->filter($filter, $qb);

        return (int)$qb->execute()->fetchColumn() > 0;
    }

    /** 新建 draft 行，content 数组自动序列化为 content_json。 */
    public function create(array $data)
    {
        $now = time();
        $data['created'] = $data['created'] ?? $now;
        $data['updated'] = $data['updated'] ?? $now;
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content_json'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
            unset($data['content']);
        }

        $conn = $this->connection();
        $conn->insert($this->table, $this->pickInsertColumns($data));
        $data['draft_id'] = (int)$conn->lastInsertId();

        return $this->decodeRow($data);
    }

    public function updateOneBy(array $filter, array $data)
    {
        $row = $this->getInfo($filter);
        if (!$row) {
            return null;
        }

        $data['updated'] = time();
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content_json'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
            unset($data['content']);
        }

        $conn = $this->connection();
        $conn->update($this->table, $this->pickInsertColumns($data), ['draft_id' => $row['draft_id']]);

        return $this->getInfo(['draft_id' => $row['draft_id']]);
    }

    /** 审核驳回或 merge 完成后，按 goods_id 删除整 SPU 的 SKU draft。 */
    public function deleteByGoodsId($goodsId, $companyId = null)
    {
        $conn = $this->connection();
        $qb = $conn->createQueryBuilder()->delete($this->table);
        $filter = ['goods_id' => $goodsId];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $this->filter($filter, $qb);

        return $qb->execute();
    }

    /** 将 content_json 解码并 merge 到行数组，供 overlay/merge 使用。 */
    public function decodeRow(array $row)
    {
        if (!empty($row['content_json'])) {
            $content = json_decode($row['content_json'], true);
            if (is_array($content)) {
                $row = array_merge($content, $row);
            }
        }

        return $row;
    }

    public function decodeRows(array $rows)
    {
        foreach ($rows as $index => $row) {
            $rows[$index] = $this->decodeRow($row);
        }

        return $rows;
    }

    private function pickInsertColumns(array $data)
    {
        $allowed = ['source_item_id', 'goods_id', 'company_id', 'supplier_id', 'default_item_id', 'is_default', 'content_json', 'created', 'updated'];
        return array_intersect_key($data, array_flip($allowed));
    }

    private function filter(array $filter, $qb)
    {
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                $qb->andWhere($qb->expr()->in($field, array_map([$qb->expr(), 'literal'], $value)));
            } else {
                $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }

        return $qb;
    }
}
