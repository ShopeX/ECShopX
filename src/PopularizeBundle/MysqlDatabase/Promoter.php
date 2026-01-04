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

namespace PopularizeBundle\MysqlDatabase;

use PopularizeBundle\Entities\Promoter as EntitiesPromoter;

// 推广员结构存储表
// 使用 mysql 存储
class Promoter
{
    private $depth = 2; //查询child层次 默认最大2层 下级/下下级
    /**
     * @var Filter
     */
    public $filter;
    public $entity;

    public function __construct()
    {
        $this->filter = new Filter();
        $this->entity = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
    }

    /**
     * 新增推关员
     *
     * @param array $data 推广员的存储数据
     * @param int $parentId 当前推广员的上级id
     */
    public function create($data, $parentId = null)
    {
        if ($data['user_id'] && $this->hasUserId($data['user_id'])) {
            return true;
        }
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $insertId = $promoterRepository->create($data);
        return $insertId;
    }

    // private function getId() {
    //     $redis = app('redis')->connection('default');
    //     $key = "promoter_uuid:";
    // }

    /**
     * 根据会员ID，更新推广员
     */
    public function updateByUserId($userId, $data)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $result = $promoterRepository->updateOneBy(['user_id' => $userId], $data);
        $result = $this->formatRecords([$result]);
        if (!$result) {
            throw new \Exception('更新失败');
        }
        return $result;
    }


    /**
     * 根据ID获取详情
     */
    public function getInfoById($id)
    {
        return $this->entity->getInfoById($id);
    }

    /**
     * 根据ID获取详情
     */
    public function getInfoByUserId($userId)
    {
        $filter['user_id'] = $userId;
        return $this->entity->getInfo($filter);
    }

    /**
     * 通过ID获取关联的下级
     * depth 分销层级
     *
     * @param int $id 当前推广员id
     */
    private function getRelationChildrenById($id, $depth, $userId = null)
    {
        $result = [];
        $relationship_depth = 1;
        $pid = [$id];
        while (count($pid) > 0 && $depth > 0) {
            $filter['pid'] = $pid;
            $userId and $filter['user_id'] = $userId;
            $pid = [];
            $children = $this->entity->lists($filter, 1, -1);
            if (!$children) {
                break;
            }
            foreach ($children['list'] as $key => &$value) {
                $value['relationship_depth'] = $relationship_depth;
                $result[] = $value;
                $pid[] = $value['promoter_id'];
            }
            $relationship_depth++;
            $depth--;
        }
        array_multisort(array_column($result, 'created'), SORT_DESC, $result); //按照原来的处理方式, 下级列表按时间倒序
        return $result;
    }

    /**
     * 根据条件查询下级
     */
    public function getRelationChildrenBy($filter, $depth = null, $offset = 0, $limit = null)
    {
        if (!$depth) {
            $depth = $this->depth;
        }
        if (isset($filter['promoter_id'])) {
            $id = $filter['promoter_id'];
            unset($filter['promoter_id']);
        } else {
            $self = $this->getInfoByUserId($filter['user_id']);
            $id = $self['promoter_id'];
            unset($filter['user_id']);
        }
        if (isset($filter['promoter_user_id']) && $filter['promoter_user_id']) {
            $promoter_user_id = $filter['promoter_user_id'];
            unset($filter['promoter_user_id']);
            $result = $this->getRelationChildrenById($id, $depth, $promoter_user_id);
        } else {
            $result = $this->getRelationChildrenById($id, $depth);
        }
        $result = $this->filter->getFilterRecordData($result, $filter, $offset, $limit);
        return $this->formatRecords($result);
    }
    /**
     * 根据条件查询上级
     */
    public function getRelationParentBy($filter, $depth = null)
    {
        $result = [];
        if (!$depth) {
            $depth = 100; //给一个比较大的最深层级(通常也不会有这么多层)
        }
        if (isset($filter['promoter_id'])) {
            $id = $filter['promoter_id'];
            unset($filter['promoter_id']);
        } else {
            $self = $this->getInfoByUserId($filter['user_id']);
            $id = $self['promoter_id'];
            unset($filter['user_id']);
        }
        $result = $this->getRelationParentById($id, $depth);
        $result = $this->filter->getFilterRecordData($result, $filter);
        return $this->formatRecords($result);
    }

    /**
     * 更加条件查询上级
     *
     *
     * @param $userId 会员id
     */
    private function getRelationParentById($id, $depth)
    {
        $self = $this->getInfoById($id);
        $pid = $self['pid'];
        $result = [];
        $relationship_depth = 1;
        while ($pid && $depth > 0) {
            $parent = $this->getInfoById($pid);
            $parent['relationship_depth'] = $relationship_depth++;
            $result[] = $parent;
            $pid = $parent['pid'];
            $depth--;
        }
        return $result;
    }

    /**
     * 关联的下级数量统计
     */
    public function relationChildrenCountByUserId($userId, $depth = null, $filter = array())
    {
        if (!$depth) {
            $depth = $this->depth;
        }
        $filter['user_id' ] = $userId;
        $result = $this->getRelationChildrenBy($filter, $depth);
        return $result['total_count'];
    }

    /**
     * 格式化返回 function
     *
     * @return array
     */
    protected function formatRecords($result)
    {
        //防止重复处理
        if (isset($result['total_count'])) {
            return $result;
        }

        $return = [
            'total_count' => 0,
            'list' => [],
        ];
        $return['total_count'] = count($result);
        $return['list'] = $result;
        return $return;
    }

    /**
     * 判断当前userId是否存在
     */
    public function hasUserId($userId)
    {
        $result = $this->getInfoByUserId($userId);
        return $result ? true : false;
    }

    /**
     * 判断当前id是否存在
     */
    public function hasId($id)
    {
        $result = $this->getInfoById($id);
        return $result ? true : false;
    }

    /**
     * 批量查询直属下级数量
     */
    public function relationChildrenCountByPidList($pidList)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('pid,count(id) as count')
                 ->from('popularize_promoter')
                 ->where($criteria->expr()->in('pid', $pidList))
                 ->groupBy('pid');
        $list = $criteria->execute()->fetchAll();
        return array_column($list, null, 'pid');
    }
}
