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

namespace DistributionBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DistributionBundle\Entities\Distributor;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Traits\Repository\FilterRepositoryTrait;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use MerchantBundle\Entities\Merchant;

class DistributorRepository extends EntityRepository
{
    use FilterRepositoryTrait,MagicLangTrait;

    public $table = "distribution_distributor";

    private $multiLangField = [
        'name',
        'contact',
        'logo',
        'province',
        'city',
        'area',
        'address',
        'introduce'
    ];

    private $prk = 'distributor_id';

    public $cols = [
        'distributor_id',
        'shop_id',
        'is_distributor',
        'company_id',
        'mobile',
        'address',
        'house_number',
        'name',
        'auto_sync_goods',
        'logo',
        'contract_phone',
        'banner',
        'contact',
        'is_valid',
        'lng',
        'lat',
        'child_count',
        'is_default',
        'is_audit_goods',
        'is_ziti',
        'regions_id',
        'regions',
        'is_domestic',
        'is_direct_store',
        'province',
        'is_delivery',
        'city',
        'area',
        'hour',
        'created',
        'updated',
        'shop_code',
        'wechat_work_department_id',
        'distributor_self',
        'regionauth_id',
        'is_open',
        'rate',
        'is_dada',
        'business',
        'dada_shop_create',
        'shansong_shop_create',
        'shansong_store_id',
        'review_status',
        'dealer_id',
        'split_ledger_info',
        'bspay_split_ledger_info',
        'introduce',
        'merchant_id',
        'distribution_type',
        'is_require_subdistrict',
        'is_require_building',
        'delivery_distance',
        'offline_aftersales',
        'offline_aftersales_self',
        'offline_aftersales_distributor_id',
        'offline_aftersales_other',
        'offline_aftersales_other',
        'is_self_delivery',
        'freight_time',
        'is_open_salesman',
        'is_refund_freight',
        'wdt_shop_no',
        'wdt_shop_id',
        'jst_shop_id',
        'kuaizhen_store_id',
        'open_divided',//开启店铺隔离
        'payment_subject', // 收款主体
    ];

    public function getLangService()
    {
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }


        $entity = new Distributor();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);

        $this->getLangService()->addMultiLangByParams($dataRet[$this->prk],$data,$this->table);

        return $dataRet;
    }

    public function fake()
    {
        return $this->getColumnNamesData(new Distributor());
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }

        if (isset($data['auto_sync_goods'])) {
            // 兼容多种输入格式：字符串 'true'/'false'，整数 1/0，布尔值 true/false
            if (is_string($data['auto_sync_goods'])) {
                $data['auto_sync_goods'] = ($data['auto_sync_goods'] == 'true' || $data['auto_sync_goods'] === '1') ? 1 : 0;
            } elseif (is_bool($data['auto_sync_goods'])) {
                $data['auto_sync_goods'] = $data['auto_sync_goods'] ? 1 : 0;
            } else {
                // 整数类型，直接转换为 0 或 1
                $data['auto_sync_goods'] = (int)$data['auto_sync_goods'] ? 1 : 0;
            }
        }

        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans('DistributionBundle/Repositories.no_update_data_found'));
        }
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        if(isset($filter[$this->prk])){
            $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
        }
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
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }

        if (isset($data['auto_sync_goods'])) {
            // 兼容多种输入格式：字符串 'true'/'false'，整数 1/0，布尔值 true/false
            if (is_string($data['auto_sync_goods'])) {
                $data['auto_sync_goods'] = ($data['auto_sync_goods'] == 'true' || $data['auto_sync_goods'] === '1') ? 1 : 0;
            } elseif (is_bool($data['auto_sync_goods'])) {
                $data['auto_sync_goods'] = $data['auto_sync_goods'] ? 1 : 0;
            } else {
                // 整数类型，直接转换为 0 或 1
                $data['auto_sync_goods'] = (int)$data['auto_sync_goods'] ? 1 : 0;
            }
        }

        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);
        if(isset($filter[$this->prk])){
            $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
        }

        return $qb->execute();
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
            return true;
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
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
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
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
        }

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $isTotalCount = true, $column = "*", $noHaving = false)
    {
        // 将字段拆成数组
        $column = $this->table.'.*';
        $select = explode(",", $column);

        // 获取经度
        $lng = $filter['lng'] ?? null;
        // 获取纬度
        $lat = $filter['lat'] ?? null;
        // 将经纬度从筛选条件中移除
        unset($filter['lng']);
        unset($filter['lat']);

        $conn = app('registry')->getConnection('default');

        $qb = $conn->createQueryBuilder();
        $merchantTable = app('registry')->getManager('default')->getRepository(Merchant::class)->table;
        if (empty($filter['merchant_id'])) {
            $qb->leftJoin(
                $this->table,
                $merchantTable,
                $merchantTable,
                sprintf("%s.merchant_id=%s.id", $this->table, $merchantTable)
            );
            $select[] = 'merchant_name';
        }


        if ($lng && $lat) {
            $select[] = '( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat ) ) ) ) AS distance';
            $having = true;
        } else {
            $having = false;
        }

        // 是否需要聚合操作, 如果只需要获取距离，则不需要过滤条离用户比较远的店铺数据
        if ($noHaving) {
            $having = false;
        }

        $qb->select(implode(",", $select))->from($this->table);
        $qb = $this->_filter($filter, $qb);

        // 聚合操作
        if ($having) {
            // $distributorService = new DistributorService();
            // $distance = $distributorService->getDistanceRedis($filter['company_id'] ?? 0) ?? config('common.distributor_distance');
            // if ($distance > 0) {
            //     $qb->having($qb->expr()->lte('distance', $distance));
            // }
            $qb->having(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->gt('delivery_distance', 0),
                        $qb->expr()->lte('distance', 'delivery_distance')
                    ),
                    $qb->expr()->eq('delivery_distance', 0)
                )
            );
        }

        $res["total_count"] = 0;
        if ($isTotalCount) {
            $totalCountQb = $conn->createQueryBuilder();
            $res["total_count"] = (int)$totalCountQb->select('count(*) as _count')->from("(".$qb->getSql().")", 'tmp')->execute()->fetchColumn();
        }

        // 分页设置
        if ($pageSize > 0) {
            $qb->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        }

        // 设置排序方式
        if (is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy($key, $value);
            }
        }
//        if ($lng && $lat) {
//            $qb->orderBy('distance', $orderBy["distance"] ?? "ASC");
//        } elseif ($orderBy) {
//            foreach ($orderBy as $key => $value) {
//                $qb->addOrderBy($key, $value);
//            }
//        }

        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
            // 解密
            isset($v['mobile']) and $v['mobile'] = fixeddecrypt($v['mobile']);
            isset($v['contact']) and $v['contact'] = fixeddecrypt($v['contact']);
        }
        $res["list"] = $lists;

        $res["list"] = $this->getLangService()->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $res;
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

        $result =  $this->getColumnNamesData($entity);
        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }

        return $result;
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

        $result =  $this->getColumnNamesData($entity);

        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }

        return $result;
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(distributor_id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 获取指定位置最近的店铺
     */
    public function getNearDistributorList($filter, $lat = 0, $lng = 0)
    {
        $conn = app('registry')->getConnection('default');

        $select = '*, ' . '( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat ) ) ) ) AS distance';

        $qb = $conn->createQueryBuilder()->select($select)->from($this->table);
        $qb = $this->_filter($filter, $qb);

        $qb->orderBy('distance', 'asc');

        $qb->setFirstResult(0);
        $qb->setMaxResults(1);

        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
            if ($v['mobile'] ?? '') {
                $v['mobile'] = fixeddecrypt($v['mobile']);
            }
            if ($v['contact'] ?? '') {
                $v['contact'] = fixeddecrypt($v['contact']);
            }
            if ($v['phone'] ?? '') {
                $v['phone'] = fixeddecrypt($v['phone']);
            }
        }

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }

    public function changeChildCount($companyId, $distributorId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->update($this->table)
            ->set('child_count', 'child_count + 1')
            ->where('is_distributor=1 and company_id = :company_id and distributor_id = :distributor_id')
            ->setParameters([
                ':company_id' => $companyId,
                ':distributor_id' => $distributorId,
            ]);
        $result = $qb->execute();
        return $result;
    }

    public function setDefaultDistributor($companyId, $distributorId, $isDistributor = true)
    {
        $distributorEntity = $this->find($distributorId);
        if (!$distributorEntity) {
            throw new UpdateResourceFailedException(trans('DistributionBundle/Repositories.distributor_not_exist_with_id', ['id' => $distributorId]));
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->getConnection('default')->update('distribution_distributor', ['is_default' => 0], ['company_id' => $companyId, 'is_distributor' => $isDistributor]);
            $isDefault = true;
            $distributorEntity->setIsDefault($isDefault);
            $em->persist($distributorEntity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }

    public function openOrClose($distributor_id, $status = 1)
    {
        if ($status === 'false' || $status === false) {
            $status = false;
        } elseif ($status === 'true' || $status === true) {
            $status = true;
        }
        $shopsEntity = $this->find($distributor_id);
        if (!$shopsEntity) {
            throw new DeleteResourceFailedException(trans('DistributionBundle/Repositories.store_not_exist_with_id', ['id' => $distributor_id]));
        }

        $shopsEntity->setIsValid($status);

        $em = $this->getEntityManager();
        $em->persist($shopsEntity);
        $em->flush();
        return true;
    }

    /**
     * 为key添加表的别名前缀
     * @param string $key
     * @return string
     */
    protected function appendPrefixTableAlias(string $key): string
    {
        if (strpos($key, ".") === false) {
            return sprintf("%s.%s", $this->table, $key);
        }
        return $key;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $v = $this->appendPrefixTableAlias($v);
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $value)
                    ));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                if ($field == "or") {
//                    [
//                        "or" => [
//                            "distributor_id" => [1,2,34],
//                            "name" => "asdasd"
//                        ]
//                    ];
//                    AND ( (distributor_id in (1,2,34) OR (name = "asdasd") )
                    $groupOr = [];
                    // or下的数组用or符号连接
                    foreach ($value as $itemColumn => $itemValue) {
                        $itemColumnArray = explode('|', $itemColumn);
                        // 获取列名
                        $itemColumnName = (string)array_shift($itemColumnArray);
                        $itemColumnName = $this->appendPrefixTableAlias($itemColumnName);
                        // 获取表达式的符号
                        $itemColumnSymbol = array_shift($itemColumnArray);
                        $groupOr[] = $this->getFilterExpression($qb, $itemColumnName, $itemColumnSymbol, $itemValue);
                    }
                    $qb = $qb->andWhere($this->getOrExpression($qb, ...$groupOr));
                } else {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in($field, $value));
                }
            } else {
                $field = $this->appendPrefixTableAlias($field);
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }


    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set" . str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get" . str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            $values[$col] = $entity->$fun();
        }
        $values['regions_id'] = json_decode($values['regions_id'], true);
        $values['regions'] = json_decode($values['regions'], true);
        $values['offline_aftersales_distributor_id'] = json_decode($values['offline_aftersales_distributor_id'], true);

        return $values;
    }

    public function getDistributorIdByRegionAuthId($company_id, $regionauth_id)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $result = $criteria
            ->select('distributor_id')
            ->from($this->table)
            ->andWhere($criteria->expr()->eq('company_id', $criteria->expr()->literal($company_id)))
            ->andWhere($criteria->expr()->eq('regionauth_id', $criteria->expr()->literal($regionauth_id)))
            ->execute()
            ->fetchAll();
        if (is_array($result)) {
            return array_column($result, 'distributor_id');
        }
        return [];
    }
}
