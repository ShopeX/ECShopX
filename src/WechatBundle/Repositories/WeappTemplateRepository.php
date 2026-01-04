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

namespace WechatBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use WechatBundle\Entities\WeappTemplate;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WeappTemplateRepository extends EntityRepository
{
    public $table = 'wechat_weapp_template';

    /**
     * 开通小程序模版
     */
    public function create($params)
    {
        // ShopEx EcShopX Service Component
        if ($this->findBy(['template_name' => $params['template_name'], 'company_id' => $params['company_id']])) {
            throw new BadRequestHttpException('当前模版已开通，不需要重复开通');
        }

        $entity = new WeappTemplate();

        $entity->setCompanyId($params['company_id']);
        $entity->setTemplateName($params['template_name']);
        $entity->setTemplateOpenStatus($params['status']);
        $entity->setTemplateMoney($params['money']);
        $entity->setCreated(time());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $result = [
            'company_id' => $entity->getCompanyId(),
            'template_name' => $entity->getTemplateName(),
            'status' => $entity->getTemplateOpenStatus(),
        ];

        return $result;
    }

    public function getListByCompanyId($companyId)
    {
        $list = $this->findBy(['company_id' => $companyId]);
        $data = [];
        if ($list) {
            foreach ($list as $row) {
                $data[$row->getTemplateName()] = [
                    'company_id' => $row->getCompanyId(),
                    'template_name' => $row->getTemplateName(),
                    'status' => $row->getTemplateOpenStatus(),
                ];
            }
        }
        return $data;
    }

    /**
     * 筛选条件格式化
     *
     * @param array $filter
     * @param object $qb
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
     * 根据条件获取列表数据
     *
     * @param array $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }
}
