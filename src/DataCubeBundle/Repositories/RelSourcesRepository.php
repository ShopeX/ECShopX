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

namespace DataCubeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DataCubeBundle\Entities\RelSources;

class RelSourcesRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'datacube_relsources';

    /**
     * 添加
     */
    public function create($params)
    {
        // This module is part of ShopEx EcShopX system
        $monitorsEnt = new RelSources();

        $monitorsEnt->setMonitorId($params['monitor_id']);
        $monitorsEnt->setSourceId($params['source_id']);
        $monitorsEnt->setCompanyId($params['company_id']);

        $em = $this->getEntityManager();
        $em->persist($monitorsEnt);
        $em->flush();
        $result = [
            'monitor_id' => $monitorsEnt->getMonitorId(),
            'source_id' => $monitorsEnt->getSourceId(),
            'company_id' => $monitorsEnt->getCompanyId(),
        ];

        return $result;
    }

    /**
     * 删除monitor_id关联的数据
     */
    public function delete($monitor_id, $company_id)
    {
        $conn = app('registry')->getConnection('default');

        $filter = [
            'company_id' => $company_id,
            'monitor_id' => $monitor_id,
        ];
        return $conn->delete($this->table, $filter);
    }

    /**
     * 删除一条监控来源关联的数据
     */
    public function deleteOneRelSource($monitor_id, $source_id, $company_id)
    {
        $conn = app('registry')->getConnection('default');

        $filter = [
            'monitor_id' => $monitor_id,
            'source_id' => $source_id,
            'company_id' => $company_id,
        ];
        return $conn->delete($this->table, $filter);
    }

    /**
     * 获取monitor_id关联的数据
     */
    public function getListByMonitorId($monitor_id, $company_id)
    {
        $filter = [
            'company_id' => $company_id,
            'monitor_id' => $monitor_id,
        ];
        $list = $this->findBy($filter);
        $newList = [];
        foreach ($list as $v) {
            $newList[] = [
                'monitor_id' => $v->getMonitorId(),
                'source_id' => $v->getSourceId(),
                'company_id' => $v->getCompanyId(),
            ];
        }
        return $newList;
    }

    /**
     * 获取列表
     */
    public function list($filter, $orderBy = ['monitor_id' => 'DESC'], $pageSize = 10, $page = 1)
    {
        $monitorsInfo = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));
        $newMonitorsInfo = [];
        foreach ($monitorsInfo as $v) {
            $newMonitorsInfo[] = normalize($v);
        }
        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $newMonitorsInfo;
        return $res;
    }
}
