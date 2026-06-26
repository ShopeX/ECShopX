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
