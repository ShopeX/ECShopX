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

namespace PopularizeBundle\Neo4jLabels;

use PopularizeBundle\Neo4jDatabase\Client;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;

// 推广员结构存储表
// 使用 neo4j 存储
class Promoter
{
    public $whereStr = ' where 1=1 ';
    public $client;

    public function __construct()
    {
        $client = new Client();
        $this->client = $client->connection('default');
    }

    /**
     * 新增推关员
     *
     * @param array $data 推广员的存储数据
     * @param int $parentId 当前推广员的上级id
     */
    public function create($data, $parentId = null)
    {
        $tx = $this->client->transaction();

        if ($data['user_id'] && $this->hasUserId($data['user_id'])) {
            return true;
        }

        $result = $tx->run('CREATE (n:promoter) SET n += {data} RETURN id(n)', ['data' => $data]);
        $relResult = true;
        if ($result && $parentId !== null) {
            $id = $result->getRecord()->value("id(n)");
            $relResult = $this->relationshipById($id, $parentId, $tx);
        }

        if (!$result || !$relResult) {
            throw new \Exception('保存失败');
        }

        $insertId = $result->firstRecord()->value('id(n)');

        // 更新到数据库
        $data['id'] = $insertId;
        $results = $tx->commit();

        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterRepository->create($data);

        return $insertId;
    }

    public function lists($filter = array(), $offset = 0, $limit = 20)
    {
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($col, $operator) = $list;
                $this->where($col, $operator, $value);
            } else {
                $this->where($field, '=', $value);
            }
        }

        $countQuery = 'START info=node(*) '. $this->whereStr .' with count(*) as count return count';
        $countRes = $this->client->run($countQuery);
        if ($countRes) {
            $count = $countRes->firstRecord()->value('count');
        } else {
            $count = 0;
        }

        if ($count <= 0) {
            $return['total_count'] = 0;
            $return['list'] = [];
        } else {
            $query = 'START info=node(*) '. $this->whereStr . ' return info ORDER BY info.created desc SKIP '.$offset . ' limit '. $limit;
            $result = $this->client->run($query);
            $return = $this->formatRecords($result);
            $return['total_count'] = $count;
        }

        return $return;
    }

    /**
     * 根据会员ID，更新推广员
     */
    public function updateByUserId($userId, $data)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterRepository->updateOneBy(['user_id' => $userId], $data);

        if (isset($data['brief'])) {
            unset($data['brief']);
        }
        if (isset($data['shop_pic'])) {
            unset($data['shop_pic']);
        }

        $result = $this->client->run('MERGE (n:promoter {user_id: '.$userId.'}) SET n += {data} RETURN n', ['data' => $data]);
        $result = $this->formatRecords($result);
        if (!$result) {
            throw new \Exception('更新失败');
        }

        return $result;
    }

    /**
     * 新增关联关系
     */
    public function relationshipById($id, $parentId, $client = null)
    {
        // 如果已经绑定则直接返回成功
        if ($this->hasRelation($id, $client)) {
            return true;
        }

        if (!$this->hasId($parentId)) {
            throw new \Exception('上级不存在');
        }

        $client = $client ?: $this->client;
        $result = $client->run('start parent=node({pid}),children=node({id}) create (parent)-[n:children]->(children) return n', ['id' => $id, 'pid' => $parentId]);
        return $result ? true : false;
    }

    /**
     * 将指定的推广员id，移动到新的推广员ID上
     */
    public function relationshipRemove($id, $oldPid = null, $newPid = null)
    {
        $tx = $this->client->transaction();

        // 如果有老的上级则需要删除
        if ($oldPid !== null) {
            $query = 'start parent=node('.$oldPid.'), children=node('.$id.') MATCH (parent)-[r:children]->(children) delete r';
            $tx->run($query);
        }


        if ($newPid !== null) {
            $this->relationshipById($id, $newPid, $tx);
        }

        return $tx->commit();
    }

    /**
     * 根据ID获取详情
     */
    public function getInfoById($id)
    {
        $query = 'start n=node('.$id.') return n';
        $result = $this->client->run($query);
        return $this->_getFirstRecordData($result);
    }

    /**
     * 根据ID获取详情
     */
    public function getInfoByUserId($userId)
    {
        $query = 'match(n:promoter) where n.user_id='. intval($userId).' return n';
        $result = $this->client->run($query);
        return $this->_getFirstRecordData($result);
    }

    /**
     * 获取单个推广员详情 function
     *
     * @return mixed
     */
    protected function _getFirstRecordData($result)
    {
        $this->clearWhereStr();

        if (!$result || empty($result->records())) {
            return null;
        }

        $keys = $result->firstRecord()->keys();
        $data = [];
        foreach ($keys as $key) {
            $record = $result->firstRecord()->get($key);
            $item = $record->values();
            $data = array_merge($data, $item);
        }
        $data['promoter_id'] = $record->identity();
        return $data;
    }

    /**
     * undocumented function
     *
     * @return void
     */
    protected function clearWhereStr()
    {
        return $this->whereStr = ' where 1=1 ';
    }


    /**
     * 通过ID获取关联的上级
     *
     * $strat=1 $end=1  获取直属上级
     * $strat=2 $end=2  获取上上级
     * $strat=1 $end=2  获取直属上级和上上级
     *
     * @param int $id 当前推广员id
     */
    public function getRelationParentById($id, $strat = 1, $end = null)
    {
        $query = 'start c=node('.$id.') Match (c)<-[rel:children*'.$strat.'..'.$end.']-(info:promoter) return rel,info';
        $result = $this->client->run($query);
        return $this->formatRecords($result);
    }

    /**
     * 通过ID获取关联的下级
     *
     * $strat=1 $end=1  获取直属下级
     * $strat=2 $end=2  获取下下级
     * $strat=1 $end=2  获取直属下级和下下级
     *
     * @param int $id 当前推广员id
     */
    public function getRelationChildrenById($id, $strat = 1, $end = null, $offset = 0, $limit = null)
    {
        $query = 'start c=node('.$id.') Match (c)-[rel:children*'.$strat.'..'.$end.']->(info:promoter) ';

        $query .= $this->whereStr;

        $countQuery = $query.' with count(*) as count return count';
        $countRes = $this->client->run($countQuery);
        if ($countRes) {
            $count = $countRes->firstRecord()->value('count');
        } else {
            $count = 0;
        }

        if ($count) {
            if ($limit) {
                $query .= ' return rel,info ORDER BY info.created desc SKIP '.$offset . ' limit '. $limit;
            } else {
                $query .= ' return rel,info ORDER BY info.created desc ';
            }

            $result = $this->client->run($query);
            $data = $this->formatRecords($result);
        } else {
            $data['list'] = [];
        }
        $data['total_count'] = $count;
        $this->clearWhereStr();
        return $data;
    }

    /**
     * 根据条件查询下级
     */
    public function getRelationChildrenBy($filter, $strat = 1, $end = null, $offset = 0, $limit = null)
    {
        if (isset($filter['promoter_id'])) {
            $id = $filter['promoter_id'];
            unset($filter['promoter_id']);
            $method = 'getRelationChildrenById';
        } else {
            $id = $filter['user_id'];
            $method = 'getRelationChildrenByUserId';
            unset($filter['user_id']);
        }

        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($col, $operator) = $list;
                $this->where($col, $operator, $value);
            } else {
                $this->where($field, '=', $value);
            }
        }

        return $this->$method($id, $strat, $end, $offset, $limit);
    }

    /**
     * 通过ID获取关联的下级
     *
     * $strat=1 $end=1  获取直属下级
     * $strat=2 $end=2  获取下下级
     * $strat=1 $end=2  获取直属下级和下下级
     *
     * @param int $id 当前推广员id
     */
    public function getRelationChildrenByUserId($userId, $strat = 1, $end = null, $offset = 0, $limit = null)
    {
        $query = 'Match (start:promoter{user_id:'.$userId.'})-[rel:children*'.$strat.'..'.$end.']->(info:promoter) ';

        $query .= $this->whereStr;

        $countQuery = $query.' with count(*) as count return count';
        $countRes = $this->client->run($countQuery);
        if ($countRes) {
            $count = $countRes->firstRecord()->value('count');
        } else {
            $count = 0;
        }
        if ($count) {
            if ($limit) {
                $query .= ' return rel,info ORDER BY info.created desc SKIP '.$offset . ' limit '. $limit;
            } else {
                $query .= ' return rel,info ORDER BY info.created desc';
            }
            $result = $this->client->run($query);
            $data = $this->formatRecords($result);
        } else {
            $data['list'] = [];
        }

        $data['total_count'] = $count;

        $this->clearWhereStr();

        return $data;
    }

    /**
     * 设置查询条件 function
     *
     * @return void
     */
    public function where($key, $operator = '=', $value)
    {
        $this->whereStr .= ' and info.'.$key.' '. $operator. ' '. $value;
        return $this;
    }

    /**
     * 更加条件查询上级
     *
     * $strat=1 $end=1  获取直属上级
     * $strat=2 $end=2  获取上上级
     * $strat=1 $end=2  获取直属上级和上上级
     *
     * @param $userId 会员id
     */
    public function getRelationParentByUserId($userId, $strat = 1, $end = null, $offset = 0, $limit = null)
    {
        $query = 'Match (start:promoter{user_id:'. intval($userId).'})<-[rel:children*'. intval($strat).'..'.$end.']-(info:promoter) ';

        $query .= $this->whereStr;

        $countQuery = $query.' with count(*) as count return count';
        $count = $this->client->run($countQuery)->firstRecord()->value('count');
        if ($count) {
            if ($limit) {
                $query .= ' return rel,info SKIP '.$offset . ' limit '. $limit;
            } else {
                $query .= ' return rel,info';
            }
            $result = $this->client->run($query);
            $data = $this->formatRecords($result);
        } else {
            $data['list'] = [];
        }

        $data['total_count'] = $count;

        $this->clearWhereStr();

        return $data;
    }

    /**
     * 关联的下级数量统计
     */
    public function relationChildrenCountByUserId($userId, $strat = 1, $end = null, $filter = array())
    {
        $query = 'Match (start:promoter{user_id:'. intval($userId).'})-[:children*'. intval($strat).'..'. $end.']->(info:promoter) ';

        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode("|", $field);
                if (count($list) > 1) {
                    list($col, $operator) = $list;
                    $this->where($col, $operator, $value);
                } else {
                    $this->where($field, '=', $value);
                }
            }
        }

        $query .= $this->whereStr.' WITH count(*) as count return count';

        $result = $this->client->run($query);

        $this->clearWhereStr();

        return $result->firstRecord()->value('count');
    }

    /**
     * 格式化返回 function
     *
     * @return mixed
     */
    protected function formatRecords($result)
    {
        $this->clearWhereStr();

        $return = [
            'total_count' => 0,
            'list' => [],
        ];
        if (!$result || empty($result->records())) {
            return $return;
        }

        $list = [];
        foreach ($result->records() as $record) {
            $recordKeys = $record->keys();
            $data = [];
            foreach ($recordKeys as $key) {
                $item = $record->get($key);
                // 如果为数组，目前只表示Relationship类型数据
                if (is_array($item)) {
                    $depth = count($item);
                    $data['relationship_depth'] = $depth;
                } else {
                    $data = array_merge($data, $this->formatRecordCol($item));
                }
            }
            $list[] = $data;
        }

        $return['total_count'] = count($result->records());
        $return['list'] = $list;
        return $return;
    }

    private function formatRecordCol($data)
    {
        $result['promoter_id'] = $data->identity();
        $keys = $data->keys();
        foreach ($keys as $key) {
            $result[$key] = $data->value($key);
        }
        return $result;
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
     * 判断当前会员id是否有上级
     */
    public function hasRelation($id, $client = null)
    {
        $query = 'start c=node('.$id.') Match (c)<-[:children]-(end:promoter) return end';
        $client = $client ?: $this->client;
        $result = $client->run($query);
        return $result && $result->records() ? true : false;
    }
}
