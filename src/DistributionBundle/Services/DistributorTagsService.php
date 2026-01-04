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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorRelTags;
use DistributionBundle\Entities\DistributorTags;
use EspierBundle\Services\Cache\RedisCacheService;

class DistributorTagsService
{
    public $distributorTagsRepository;
    public $distributorRelTagsRepository;

    /**
     * DistributorTagsService constructor.
     */
    public function __construct()
    {
        $this->distributorTagsRepository = app('registry')->getManager('default')->getRepository(DistributorTags::class);
        $this->distributorRelTagsRepository = app('registry')->getManager('default')->getRepository(DistributorRelTags::class);
    }

    /**
     * 查询多店铺标签
     * @param array $filter 查询条件
     * @param int $page 页数
     * @param int $pageSize 分页条数
     * @param array $orderBy
     * @param bool $is_front_show
     * @return mixed
     */
    public function getListTags(array $filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'], $is_front_show = false)
    {
        return $this->distributorTagsRepository->lists($filter, ['*'], $page, $pageSize, $orderBy);
    }

    /**
     * 为会员批量打标签
     */
    public function createRelTags($distributorIds, $tagIds, $companyId)
    {
        $savedata['company_id'] = $companyId;
        foreach ($distributorIds as $distributorId) {
            $savedata['distributor_id'] = $distributorId;
            foreach ($tagIds as $tagId) {
                $savedata['tag_id'] = $tagId;
                if (!$this->distributorRelTagsRepository->getInfo($savedata)) {
                    $result = $this->distributorRelTagsRepository->create($savedata);
                }
            }
        }
        return true;
    }

    /**
     * 为指定会员打标签
     */
    public function createRelTagsByDistributorId($distributorId, $tagIds, $companyId)
    {
        // Log: 456353686f7058
        $savedata['distributor_id'] = $distributorId;
        $savedata['company_id'] = $companyId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->distributorRelTagsRepository->getInfo($savedata)) {
                $result = $this->distributorRelTagsRepository->deleteBy($savedata);
            }
            if ($tagIds) {
                foreach ($tagIds as $tagId) {
                    $savedata['tag_id'] = $tagId;
                    $this->distributorRelTagsRepository->create($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 单一标签关联多会员
     */
    public function createRelTagsByTagId($distributorIds, $tagId, $companyId)
    {
        $savedata['tag_id'] = $tagId;
        $savedata['company_id'] = $companyId;

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->distributorRelTagsRepository->getInfo($savedata)) {
                $this->distributorRelTagsRepository->deleteBy($savedata);
            }
            foreach ($distributorIds as $distributorId) {
                $savedata['distributor_id'] = $distributorId;
                $result = $this->distributorRelTagsRepository->create($savedata);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getDistributorIdsByTagids($filter)
    {
        $relTags = $this->distributorRelTagsRepository->lists($filter);
        $itemIds = array_column($relTags['list'], 'distributor_id');
        return $itemIds;
    }

    public function getDistributorRelTagList($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('distributor_rel_tags', 'reltag')
            ->leftJoin('reltag', 'distributor_tags', 'tag', 'reltag.tag_id = tag.tag_id');
        if (isset($filter['company_id']) && $filter['company_id']) {
            $criteria->andWhere($criteria->expr()->eq('tag.company_id', $criteria->expr()->literal($filter['company_id'])));
        }

        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            $itemIds = (array)$filter['distributor_id'];
            $criteria->andWhere($criteria->expr()->in('reltag.distributor_id', $itemIds));
        }
        $criteria->select('reltag.distributor_id,tag.*');
        $list = $criteria->execute()->fetchAll();
        return $list;
    }

    /**
     * 获取前台可展示的所有标签
     * @param int $companyId 企业id
     * @return array
     */
    public function getFrontShowTags(int $companyId): array
    {
        return (new RedisCacheService($companyId, "distributor_tags", 60))->getByPrevention(function () use ($companyId) {
            // 添加标签列表信息
            $tagResult = (new DistributorTagsService())->getListTags([
                "company_id" => $companyId, // 企业id
                "front_show" => 1,
            ], 1, -1, ["tag_id" => "DESC"]);
            return (array)($tagResult["list"] ?? []);
        });
    }

    /**
     * 获取与店铺关联的所有tag_id
     * @param array $distributorIds
     * @return array
     */
    public function getRelTagIdList(int $companyId, array $distributorIds): array
    {
        if (empty($distributorIds)) {
            return [];
        }
        return $this->distributorRelTagsRepository->getLists([
            "company_id" => $companyId,
            "distributor_id" => $distributorIds
        ]);
    }

    /**
     * 删除关联的店铺标签
     * @param int $companyId 公司id
     * @param array $distributorIds 店铺id
     * @param array $tagIds 店铺标签id
     * @return bool|null true表示删除成功，null表示未操作
     * @throws Exception
     */
    public function deleteRelTags(int $companyId, array $distributorIds, array $tagIds): ?bool
    {
        if ($companyId < 1 || empty($distributorIds) || empty($tagIds)) {
            return null;
        }
        // 获取实际的店铺id
        $distributors = (new DistributorService())->getLists([
            "company_id" => $companyId,
            "distributor_id" => $distributorIds
        ], "distributor_id", 1, 0);
        $distributorIds = (array)array_column($distributors, "distributor_id");

        // 获取实际的店铺标签id
        $tags = $this->distributorTagsRepository->getLists([
            "company_id" => $companyId,
            "tag_id" => $tagIds
        ], "tag_id", 1, 0);
        $tagIds = (array)array_column($tags, "tag_id");

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($distributorIds as $distributorId) {
                foreach ($tagIds as $tagId) {
                    // 解绑
                    $this->distributorRelTagsRepository->deleteBy([
                        "distributor_id" => $distributorId,
                        "tag_id" => $tagId
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 魔术方法 直接调用distributorTagsRepository类的方法
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->distributorTagsRepository->$method(...$parameters);
    }
}
