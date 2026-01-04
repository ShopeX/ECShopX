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

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class WechatFansBindWechatTagRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wechatfans_bind_wechattag';

    /**
     * 插入数据
     */
    public function add($params)
    {
        $insertData = [
            'open_id' => $params['open_id'],
            'tag_id' => $params['tag_id'],
            'authorizer_appid' => $params['authorizer_appid'],
            'company_id' => $params['company_id'],
        ];
        if (!$this->findOneBy($insertData)) {
            $conn = app('registry')->getConnection('default');
            $conn->insert($this->table, $insertData);
        }

        return $insertData;
    }

    public function updateByopenId($openId, $params)
    {
        if (isset($params['tagids']) && $params['tagids']) {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();

            try {
                $delFilter = [
                    'open_id' => $openId,
                    'company_id' => $params['company_id'],
                    'authorizer_appid' => $params['authorizer_appid'],
                ];
                $conn->delete($this->table, $delFilter);
                if (is_array($params['tagids'])) {
                    $tags = $params['tagids'];
                } else {
                    $tags = explode(',', $params['tagids']);
                }
                foreach ($tags as $tag) {
                    $bindData = [
                        'tag_id' => $tag,
                        'open_id' => $openId,
                        'company_id' => $params['company_id'],
                        'authorizer_appid' => $params['authorizer_appid'],
                    ];
                    $conn->insert($this->table, $bindData);
                }
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }

        return true;
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    public function del($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->delete($this->table, $params);
        return $params;
    }

    public function total()
    {
        $conn = app('registry')->getConnection('default');

        $count = $conn->fetchArray("select count(*) from ".$this->table);

        return $count[0];
    }

    public function getList($cols = '*', $filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($cols)
           ->from($this->table);
        if ($filter) {
            foreach ($filter as $k => $v) {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->eq($k, $qb->expr()->literal($v))
                ));
            }
        }
        $result = $qb->execute()->fetchAll();

        return $result;
    }
}
