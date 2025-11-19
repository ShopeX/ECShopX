<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DataCubeBundle\Entities\Monitors;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class StatisticsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'datacube_statistics';

    /**
     * 添加
     */
    public function create($params)
    {
        $monitorsEnt = new Monitors();

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
            'view_num' => $monitorsEnt->getViewNum(),
            'entries_num' => $monitorsEnt->getEntriesNum(),
            'created' => $monitorsEnt->getCreated(),
            'updated' => $monitorsEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 更新
     */
    public function update($monitor_id, $params)
    {
        $monitorsEnt = $this->find($monitor_id);

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
            'view_num' => $monitorsEnt->getViewNum(),
            'entries_num' => $monitorsEnt->getEntriesNum(),
            'created' => $monitorsEnt->getCreated(),
            'updated' => $monitorsEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 删除
     */
    public function delete($monitor_id)
    {
        $delMonitorsEntity = $this->find($monitor_id);
        if (!$delMonitorsEntity) {
            throw new DeleteResourceFailedException("monitor_id={$monitor_id}的跟踪链接不存在");
        }
        $this->getEntityManager()->remove($delMonitorsEntity);

        return $this->getEntityManager()->flush($delMonitorsEntity);
    }

    /**
     * 获取详细信息
     */
    public function get($monitor_id)
    {
        $monitorsEnt = $this->find($monitor_id);
        if (!$monitorsEnt) {
            throw new ResourceException("monitor_id={$monitor_id}的跟踪链接不存在");
        }
        $result = [
            'monitor_id' => $monitorsEnt->getMonitorId(),
            'source_id' => $monitorsEnt->getSourceId(),
            'company_id' => $monitorsEnt->getCompanyId(),
            'view_num' => $monitorsEnt->getViewNum(),
            'entries_num' => $monitorsEnt->getEntriesNum(),
            'created' => $monitorsEnt->getCreated(),
            'updated' => $monitorsEnt->getUpdated(),
        ];

        return $result;
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
