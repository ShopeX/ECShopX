<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SuperAdminBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use SuperAdminBundle\Entities\ShopMenu;

use Dingo\Api\Exception\ResourceException;

class ShopMenuRepository extends EntityRepository
{
    public $menusList;
    public $isChildrenMenu;

    public $table = 'shop_menu'; // 多语言对应的表名
    public $module = 'shop_menu'; // 多语言对应的模块
    public $primaryKey = 'shopmenu_id'; // 主键，对应data_id
    public $langField = [
        'alias_name','name'
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new ShopMenu();
        return $entity;
    }

    public function getMenuTree($filter = array(), $isShowParentname = true, $isShowApis = true)
    {
        $repository = getRepositoryLangue(ShopMenu::class);
        $listsData = $repository->lists($filter,"*",1, 1000,['pid' => 'asc','sort' => 'asc'] );
        if ($listsData['total_count'] <= 0) {
            return [
                'tree' => [],
                'list' => [],
            ];
        }

        foreach ($listsData['list'] as $item) {
            if (!$isShowApis) {
                unset($item['apis']);
            }
            $lists[] = $item;
        }
        $menu['tree'] = $this->preMenuTree($lists, 0);
        $menu['list'] = $this->menusList;
        $menuList = array_column($this->menusList, null, 'shopmenu_id');
        foreach ($menu['list'] as &$row) {
            if ($isShowParentname) {
                $row['parent_name'] = isset($menuList[$row['pid']]['name']) ? $menuList[$row['pid']]['name'] : '无';
            }
            $row['isChildrenMenu'] = isset($this->isChildrenMenu[$row['shopmenu_id']]) ? $this->isChildrenMenu[$row['shopmenu_id']] : false;
        }
        return $menu;
    }

    private function preMenuTree($data, $pid = 0, $level = 0)
    {
        $lists = array();
        $isFlag = false;
        foreach ($data as $key => $val) {
            if ($val['pid'] == $pid) {
                if (!$isFlag) {
                    $level++;
                }
                $isFlag = true;

                $val['level'] = $level;
                $this->menusList[] = $val;

                if (!$val['is_show']) {
                    continue;
                }

                $children = $this->preMenuTree($data, $val['shopmenu_id'], $level);
                if ($children) {
                    $val['isChildrenMenu'] = in_array('true', array_column($children, 'is_menu'));
                    $this->isChildrenMenu[$val['shopmenu_id']] = $val['isChildrenMenu'];
                    $val['children'] = $children;
                }
                $lists[] = $val;
            }
        }
        return $lists;
    }



    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ShopMenu();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
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
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->delete('shop_menu');

        $qb = $this->_filter($filter, $qb);
        $qb->execute();
        return true;
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
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param array $filter 更新的条件
     */
    public function lists($filter, $cols="*", $page = 1, $pageSize = 1000, $orderBy = ["created" => "DESC"])
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        $data['is_menu'] = isset($data['is_menu']) && $data['is_menu'] == 'false' ? 0 : 1;
        $data['is_show'] = isset($data['is_show']) && $data['is_show'] == 'false' ? 0 : 1;
        $data['disabled'] = isset($data['disabled']) && $data['disabled'] == 'true' ? 1 : 0;

        if (isset($data["shopmenu_id"])) {
            $entity->setShopmenuId($data["shopmenu_id"]);
        }
        if (isset($data["company_id"])) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["name"]) && $data["name"]) {
            $entity->setName($data["name"]);
        }
        if (isset($data["url"]) && $data["url"]) {
            $entity->setUrl($data["url"]);
        }
        //当前字段非必填
        if (isset($data["sort"])) {
            $entity->setSort($data["sort"]);
        }
        //当前字段非必填
        if (isset($data["is_menu"])) {
            $entity->setIsMenu($data["is_menu"]);
        }
        if (isset($data["pid"])) {
            $entity->setPid($data["pid"]);
        }
        //当前字段非必填
        if (isset($data["apis"])) {
            $entity->setApis($data["apis"]);
        }
        if (isset($data["icon"]) && $data["icon"]) {
            $entity->setIcon($data["icon"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data["alias_name"]) && $data["alias_name"]) {
            $entity->setAliasName($data["alias_name"]);
        }
        if (isset($data["version"]) && $data["version"]) {
            $entity->setVersion($data["version"]);
        }
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
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
            'shopmenu_id' => $entity->getShopmenuId(),
            'company_id' => $entity->getCompanyId(),
            'name' => $entity->getName(),
            'url' => $entity->getUrl(),
            'sort' => $entity->getSort(),
            'is_menu' => $entity->getIsMenu(),
            'pid' => $entity->getPid(),
            'apis' => $entity->getApis(),
            'icon' => $entity->getIcon(),
            'is_show' => $entity->getIsShow(),
            'alias_name' => $entity->getAliasName(),
            'version' => $entity->getVersion(),
            'disabled' => $entity->getDisabled(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
