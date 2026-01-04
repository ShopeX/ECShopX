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

namespace ThemeBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use ThemeBundle\Entities\PagesTemplate;

class PagesTemplateRepository extends EntityRepository
{
    public $table = 'pages_template'; // 多语言对应的表名
    public $module = 'pages_template'; // 多语言对应的模块
    public $primaryKey = 'pages_template_id'; // 主键，对应data_id
    public $langField = [
        'template_title','template_name','template_content'
    ]; // 多语言字段
    
    public function getEntity()
    {
        // CONST: 1E236443
        $entity = new PagesTemplate();
        return $entity;
    }
    
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PagesTemplate();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);

        return $dataRet;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $filter['deletedAt'] = null; //已删除的数据不显示
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $val);
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        // CONST: 1E236443
        $filter['deletedAt'] = null; //已删除的数据不显示
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件删除数据
     *
     * @param $filter 更新的条件
     */
    public function delete(array $filter)
    {
        $filter['deletedAt'] = null; //已删除的数据不显示
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $format = 'Y-m-d';
        $date = \DateTime::createFromFormat($format, date('Y-m-d', time()));
        $entity->setDeletedAt($date);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return true;
    }

    /**
     * @param $filter
     * @param int $page_size
     * @param int $page
     * @param array $order_by
     * @return mixed
     * 获取数据列表
     */
    public function lists($filter, $cols = '*', $page = 1, $page_size = 10, $order_by = ['createdAt' => 'DESC'])
    {
        $filter['deletedAt'] = null;
        $pages_template_list = $this->findBy($filter, $order_by, $page_size, $page_size * ($page - 1));
        $list = [];
        foreach ($pages_template_list as $v) {
            $list[] = $this->getColumnNamesData($v);
        }
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $list;

        return $res;
    }

    /**
     * @param array $filter
     * @return int
     * 根据查询条件获取记录条数
     */
    public function count($filter)
    {
        $filter['deletedAt'] = null; //已删除的数据不显示
        $count = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);

        return intval($count);
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }

        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }

        if (isset($data['template_name']) && $data['template_name']) {
            $entity->setTemplateName($data['template_name']);
        }

        if (isset($data['template_title']) && $data['template_title']) {
            $entity->setTemplateTitle($data['template_title']);
        }

        if (isset($data['template_pic']) && $data['template_pic']) {
            $entity->setTemplatePic($data['template_pic']);
        }

        if (isset($data['template_type'])) {
            $entity->setTemplateType($data['template_type']);
        }

        if (isset($data['element_edit_status']) && $data['element_edit_status']) {
            $entity->setElementEditStatus($data['element_edit_status']);
        }

        if (isset($data['status']) && $data['status']) {
            $entity->setStatus($data['status']);
        }

        if (isset($data['timer_status']) && $data['timer_status']) {
            $entity->setTimerStatus($data['timer_status']);
        }

        if (isset($data['timer_time'])) {
            $entity->setTimerTime($data['timer_time']);
        }

        if (isset($data['template_status_modify_time']) && $data['template_status_modify_time']) {
            $entity->setTemplateStatusModifyTime($data['template_status_modify_time']);
        }

        if (isset($data['weapp_pages']) && $data['weapp_pages']) {
            $entity->setWeappPages($data['weapp_pages']);
        }

        if (isset($data['template_content']) && $data['template_content']) {
            $entity->setTemplateContent($data['template_content']);
        }
        if (!empty($data['lang'])) {
            $entity->setLang($data['lang']);
        }

        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'pages_template_id' => $entity->getPagesTemplateId(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'template_name' => $entity->getTemplateName(),
            'template_title' => $entity->getTemplateTitle(),
            'template_pic' => $entity->getTemplatePic(),
            'template_type' => $entity->getTemplateType(),
            'element_edit_status' => $entity->getElementEditStatus(),
            'status' => $entity->getStatus(),
            'timer_status' => $entity->getTimerStatus(),
            'timer_time' => $entity->getTimerTime(),
            'template_status_modify_time' => $entity->getTemplateStatusModifyTime(),
            'weapp_pages' => $entity->getWeappPages(),
            'template_content' => $entity->getTemplateContent(),
            'lang'=>$entity->getLang(),
        ];
    }
}
