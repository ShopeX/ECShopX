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

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Entities\Items;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MultiLangService;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class ItemsRepository extends EntityRepository
{
    use MagicLangTrait;
    /**
     * 当前表名称
     */
    public $table = 'items';

    private $prk = 'item_id';

    private $multiLangField = [
        'item_name','brief','intro'
    ];

    public $cols = [
        'item_id', 'item_type', 'consume_type', 'is_show_specimg','store', 'barcode', 'sales', 'approve_status', 'rebate', 'rebate_conf', 'cost_price','is_point', 'point', 'item_source', 'goods_id', 'brand_id', 'is_market',
        'consume_type', 'item_name', 'item_unit', 'item_bn', 'brief', 'price', 'market_price', 'special_type', 'goods_function', 'goods_series', 'volume','supplier_id','supplier_item_id',
        'goods_color', 'goods_brand', 'item_address_province', 'item_address_city', 'regions_id', 'regions', 'brand_logo', 'sort', 'templates_id', 'is_default', 'nospec', 'default_item_id', 'pics', 'pics_create_qrcode', 'distributor_id',
        'company_id', 'enable_agreement', 'date_type', 'item_category', 'rebate_type', 'weight', 'begin_date', 'end_date', 'fixed_term','tax_rate', 'created', 'updated', 'video_type', 'videos', 'video_pic_url', 'purchase_agreement',
        'intro', 'audit_status', 'audit_reason', 'is_gift', 'is_package', 'profit_type', 'profit_fee', 'is_profit','crossborder_tax_rate','origincountry_id','taxstrategy_id','taxation_num','type','tdk_content','is_epidemic',
        'goods_bn','audit_date','is_medicine','is_prescription','start_num','delivery_time','is_taobao'
    ];

    public function insert($data)
    {
        $data['created'] = time();
        $data['updated'] = time();
        $conn = app('registry')->getConnection('default');
        $conn->insert($this->table, $data);
        return true;
    }

    /**
     * 添加商品
     */
    public function create($params)
    {
//        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($params,$this->multiLangField);
//        $params = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];

        if(isset($params['nospec'])){
            $params['nospec'] = var_export($params['nospec'] , true);
        }

        $itemsEnt = new Items();

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();


        $result = $this->getColumnNamesData($itemsEnt, $this->cols);
        $service = new MultiLangService();
        $service->addMultiLangByParams($result['item_id'],$params,'items');
//        $service->saveLang($result['company_id'],$langBag,$this->table,$result['item_id'],$this->table);
        return $result;
    }

    public function updateSort($itemId, $sort)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSort($sort);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_source' => $itemsEnt->getItemSource() ? $itemsEnt->getItemSource() : 'mall',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];

        return $result;
    }

    public function updateStore($itemId, $store, $is_log = false)
    {
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',line:'.__LINE__);
        }
        /** @var \GoodsBundle\Entities\Items $itemsEnt */
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            if ($is_log) {
                app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',itemsEnt is null,line:'.__LINE__);
            }
            return true;
        }

        $itemsEnt->setStore($store);
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',line:'.__LINE__);
        }
        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_source' => $itemsEnt->getItemSource() ? $itemsEnt->getItemSource() : 'mall',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'supplier_item_id' => $itemsEnt->getSupplierItemId(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.'====end====,line:'.__LINE__);
        }
        return $result;
    }

    /**
     * 更新销量
     * @param $itemId 商品id
     * @param $sales 销量
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSales($itemId, $sales)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSales((int)$sales + (int)$itemsEnt->getSales());

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush($itemsEnt);

        return true;
    }

    /**
     * 更新运费模板
     * @param $itemId 商品id
     * @param $templates_id 运费模板id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setTemplatesId($itemId, $templates_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setTemplatesId($templates_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    /**
     * 更新商品分类
     * @param $itemId 商品id
     * @param $category_id 分类id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setCategoryId($itemId, $category_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setItemCategory($category_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    /**
     * 更新多条数数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
     * @param bool $needLiteral false表示不需要为值做双引号的操作
     * @return mixed
     */
    public function updateBy(array $filter, array $data, bool $needLiteral = true)
    {
        if(isset($params['nospec'])){
            $params['nospec'] = var_export($params['nospec'] , true);
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $needLiteral ? $qb->expr()->literal($val) : $val);
        }

        $qb = $this->_filter($filter, $qb);
        if(isset($filter[$this->prk])){
            if(is_array($filter[$this->prk])){
                foreach ($filter[$this->prk] as $pprk){
                    //更新数据
                    $service = new MultiLangService();
                    $service->updateLangData($data,'items',$pprk);
                }
            }else{
                $service = new MultiLangService();
                $service->updateLangData($data,'items',$filter[$this->prk]);
            }
        }

        return $qb->set('updated', time())->execute();
    }



    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateProfitBy($filter, $profitType, $profitScale)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->update($this->table, 'i');
        $qb->set('i.profit_type', $qb->expr()->literal($profitType))
            ->set('i.profit_fee', 'FLOOR(i.price * ' . $profitScale . ')');

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    private function _filter($filter, $qb, $alias = '')
    {
        if (isset($filter['or']) && $filter['or']) {
            foreach ($filter['or'] as $key => $filterValue) {
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'direct') {
                        $orWhere[] = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$v, $filterValue));
                        continue;
                    }
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
                        $filterValue = '%'.$filterValue.'%';
                    }
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                } else {
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->in(($alias ? $alias.'.' : '').$key, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->eq(($alias ? $alias.'.' : '').$key, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->eq(($alias ? $alias.'.' : '').$key, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                }
            }
            $qb->andWhere(
                $qb->expr()->orX(...$orWhere)
            );
            unset($filter['or']);
        }

        if (isset($filter['is_default'], $filter['approve_status']) && $filter['is_default']) {
            $dqb = app('registry')->getConnection('default')->createQueryBuilder()->select('goods_id')->from('items', 'inner_items');
            if (is_array($filter['approve_status'])) {
                array_walk($filter['approve_status'], function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $dqb->andWhere($qb->expr()->in('inner_items.approve_status', $filter['approve_status']));
            } else {
                $dqb->andWhere($qb->expr()->eq('inner_items.approve_status', $qb->expr()->literal($filter['approve_status'])));
            }
            $qb->andWhere('exists('.$dqb->getSQL().' AND inner_items.goods_id='.($alias ?: $this->table).'.goods_id)');
            unset($filter['approve_status']);
        }

        foreach ($filter as $field => $value) {
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'direct') {
                    $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$v, $value));
                    continue;
                }
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            } else {
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in(($alias ? $alias.'.' : '').$field, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            }
        }
        return $qb;
    }

    public function deleteBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 更新商品信息
     */
    public function update($item_id, $params)
    {
        if(isset($params['nospec'])){
            $params['nospec'] = var_export($params['nospec'] , true);
        }
        $itemsEnt = $this->find($item_id);

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $service = new MultiLangService();
        $service->updateLangData($params,'items',$item_id);
        $result = $this->getColumnNamesData($itemsEnt);
        return $result;
    }

    /**
     * 删除商品
     */
    public function delete($item_id)
    {
        $delItemsEntity = $this->find($item_id);
        if (!$delItemsEntity) {
            return true;
        }
        $this->getEntityManager()->remove($delItemsEntity);

        return $this->getEntityManager()->flush($delItemsEntity);
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
     * 获取会员商品详细信息
     */
    public function get($item_id, $columns = null)
    {
        $itemsEnt = $this->find($item_id);
        if (!$itemsEnt) {
            return [];
        }

        $result = $this->getColumnNamesData($itemsEnt, $columns);
        //替换语言
        $service = new MultiLangService();
        $lang = $this->getLang();
        $result = $service->getOneLangData($result,$this->multiLangField,$this->table,$lang,$result['item_id'],$this->table);
        return $result;
    }

    public function count($filter)
    {
        app('log')->info(__FUNCTION__.':'.__LINE__.':1:filter:'.json_encode($filter));
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':qb:'.$qb->getSQL());
        app('log')->info(__FUNCTION__.':'.__LINE__.':end:filter:'.json_encode($filter));
        $count = $qb->execute()->fetchColumn();
        app('log')->info(__FUNCTION__.':'.__LINE__.':count:'.$count);
        return intval($count);
    }

    /**
     * 指定条件，获取最多的商品所属catid
     */
    public function countItemsMainCatIdBy($filter)
    {
        if (isset($filter['distributor_id'])) {
            unset($filter['distributor_id']);
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('count("item_id") as _count, item_category')->from($this->table);

        $qb = $this->_filter($filter, $qb);

        $qb->orderBy('_count', 'desc');
        $qb->groupBy('item_category');

        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
     * 指定条件，获取所有的品牌id
     */
    public function getBrandIds($filter)
    {
        unset($filter['brand_id']);
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('DISTINCT brand_id')->from($this->table);

        $qb = $this->_filter($filter, $qb);

        $lists = $qb->execute()->fetchAll();

        return $lists;
    }

    /**
     * 获取商品列表
     */
    public function list($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $result['total_count'] = $this->count($filter);
        app('log')->info(__FUNCTION__.':'.__LINE__.':1:result:'.json_encode($result));
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            if (!$columns || $columns == '*') {
                $columns = $this->cols;
            }
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            }
            $qb = $conn->createQueryBuilder()->select(implode(',', $columns))->from($this->table);
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
            app('log')->info(__FUNCTION__.':'.__LINE__.':qb:'.$qb->getSQL());
            $list = $qb->execute()->fetchAll();
            foreach ($list as $key => $row) {
                $values = [];
                foreach ($columns as $col) {
                    if (in_array($col, ['intro', 'purchase_agreement'])) {
                        continue;
                    }
                    if (in_array($col, ['pics', 'pics_create_qrcode', 'rebate_conf'])) {
                        $values[$col] = json_decode($row[$col], true);
                    } else {
                        $values[$col] = $row[$col];
                    }
                }
                $values['itemId'] = $values['item_id'];
                $values['consumeType'] = $values['consume_type'] ?? '';
                $values['itemName'] = $values['item_name'] ?? '';
                $values['itemBn'] = $values['item_bn'] ?? '';
                $values['companyId'] = $values['company_id'] ?? '';
                $values['item_main_cat_id'] = $values['item_category'] ?? '';
                // 规格转成bool
                $values['nospec'] = ( isset($values['nospec']) && ($values['nospec'] === 'true' || $values['nospec'] === true || $values['nospec'] === 1 || $values['nospec'] === '1') ) ? true : false;
                $values['is_medicine'] = $values['is_medicine'] ?? 0;
                $list[$key] = $values;
            }
        }
        $result['list'] = $list ?? [];
        if(empty($result['list'])){
            return $result;
        }
        $service = new MultiLangService();
        $result['list'] = $service->getListAddLang($result['list'],$this->multiLangField,$this->table,$this->getLang(),'item_id');
        foreach ($result['list'] as $i => $vvv){
            $result['list'][$i]['itemName'] = $vvv['item_name'] ?? '';
        }
//        /data1/httpd/ecshopx1/multi-lang/ecshopx-api

        return $result;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter
     * @param string $cols
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return mixed
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
        $lists =  $qb->execute()->fetchAll();
        $service = new MultiLangService();
        $lists = $service->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),'item_id');
        return  $lists;
    }

    /**
     * 获取商品列表
     */
    public function listCopy($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $newItemsList = [];
        if ($res['total_count']) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $list = $this->matching($criteria);
            if (!$columns) {
                $columns = $this->cols;
            }
            foreach ($list as $v) {
                $newItemsList[] = $this->getColumnNamesData($v, $columns);
            }
        }
        $res['list'] = $newItemsList;
        return $res;
    }

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = 'set'. str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
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
            $fun = 'get'. str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
            $values[$col] = $entity->$fun();
        }
        // 历史原因特使处理
        if (isset($values['intro'])) {
            $intro = json_decode($values['intro'], true);
            $values['intro'] = $intro ? $intro : $values['intro'];
        }
        $values['itemId'] = $values['item_id'];
        $values['consumeType'] = $values['consume_type'] ?? '';
        $values['itemName'] = $values['item_name'] ?? '';
        $values['itemBn'] = $values['item_bn'] ?? '';
        $values['companyId'] = $values['company_id'] ?? '';
        $values['item_main_cat_id'] = $values['item_category'] ?? '';
         // 规格转成bool
        $values['nospec'] = ( isset($values['nospec']) && ($values['nospec'] === 'true' || $values['nospec'] === true || $values['nospec'] === 1 || $values['nospec'] === '1') ) ? true : false;

        return $values;
    }

    /**
     * 简单的更新操作，不支持大于 小于等条件更新
     */
    public function simpleUpdateBy($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        return $conn->update($this->table, $data, $filter);
    }

    //获取指定条件的所有商品列表，可指定字段
    public function getItemsLists($filter, $cols = 'item_id, default_item_id')
    {
        // 定义业务逻辑字段列表（非数据库字段）
        $businessLogicFields = ['source', 'operator_type', 'export_type', 'wxaappid', 'merchant_id'];
        
        // 创建过滤后的数组，只包含数据库字段
        $dbFilter = $filter;
        foreach ($businessLogicFields as $field) {
            unset($dbFilter[$field]);
        }
        
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($dbFilter, $qb);
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    public function getSimpleInfo($filter, $cols)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity, $cols);
    }

    public function getItemsFilter($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('i.price,i.market_price,i.item_category,i.distributor_id,ira.attribute_id,ira.attribute_value_id,ia.attribute_name,ia.attribute_type,iav.attribute_value_name')
            ->from('items_rel_attributes', 'ira')
            ->leftJoin('ira', 'items', 'i', 'ira.item_id = i.item_id')
            ->leftJoin('ira', 'items_attributes', 'ia', 'ira.attribute_id = ia.attribute_id')
            ->leftJoin('ira', 'items_attributes_value', 'iav', 'ira.attribute_value_id = iav.attribute_value_id')
            ->where($criteria->expr()->in('ia.attribute_type', [$criteria->expr()->literal('item_params'), $criteria->expr()->literal('item_spec')]))
            ->andWhere($criteria->expr()->eq('ia.is_show', 1));

        unset($filter['is_default']);
        $this->_filter($filter, $criteria, 'i');
        $list = $criteria->execute()->fetchAll();

        //['0,0.3', '0,0.8', '0.1,0.3', '0.3,0.5', '0.5,0.8']
        $discountRate = [];
        $mainCategoryIds = [];
        $distributorIds = [];
        $itemParams = [];
        $itemSpec = [];
        foreach ($list as $item) {
            if ($item['item_category'] > 0 && !in_array($item['item_category'], $mainCategoryIds)) {
                $mainCategoryIds[] = $item['item_category'];
            }
            if ($item['distributor_id'] > 0 && !in_array($item['distributor_id'], $distributorIds)) {
                $distributorIds[] = $item['distributor_id'];
            }

            if ($item['market_price'] > 0) {
                $rate = bcdiv($item['price'], $item['market_price'], 2);
                switch ($rate) {
                    case $rate < 0.1:
                        !in_array('0,0.3', $discountRate) && $discountRate[] = '0,0.3';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                    case $rate >= 0.1 && $rate < 0.3:
                        !in_array('0,0.3', $discountRate) && $discountRate[] = '0,0.3';
                        !in_array('0.1,0.3', $discountRate) && $discountRate[] = '0.1,0.3';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                    case $rate == 0.3:
                        !in_array('0,0.3', $discountRate) && $discountRate[] = '0,0.3';
                        !in_array('0.1,0.3', $discountRate) && $discountRate[] = '0.1,0.3';
                        !in_array('0.3,0.5', $discountRate) && $discountRate[] = '0.3,0.5';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                    case $rate > 0.3 && $rate < 0.5:
                        !in_array('0.3,0.5', $discountRate) && $discountRate[] = '0.3,0.5';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                    case $rate == 0.5:
                        !in_array('0.3,0.5', $discountRate) && $discountRate[] = '0.3,0.5';
                        !in_array('0.5,0.8', $discountRate) && $discountRate[] = '0.5,0.8';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                    case $rate > 0.5 && $rate <= 0.8:
                        !in_array('0.5,0.8', $discountRate) && $discountRate[] = '0.5,0.8';
                        !in_array('0,0.8', $discountRate) && $discountRate[] = '0,0.8';
                        break;
                }
            }
            if ($item['attribute_type'] == 'item_params') {
                if (!isset($itemParams[$item['attribute_id']])) {
                    $itemParams[$item['attribute_id']] = [
                        'attribute_id' => $item['attribute_id'],
                        'attribute_name' => $item['attribute_name'],
                        'values' => [],
                    ];
                    $itemParams[$item['attribute_id']]['values'][$item['attribute_value_id']] = [
                        'attribute_value_id' => $item['attribute_value_id'],
                        'attribute_value_name' => $item['attribute_value_name'],
                    ];
                } else {
                    $itemParams[$item['attribute_id']]['values'][$item['attribute_value_id']] = [
                        'attribute_value_id' => $item['attribute_value_id'],
                        'attribute_value_name' => $item['attribute_value_name'],
                    ];
                }
                foreach ($itemParams as $key => $val) {
                    $itemParams[$key]['values'] = array_values($val['values']);
                }
                $itemParams = array_values($itemParams);
            }

            if ($item['attribute_type'] == 'item_spec') {
                if (!isset($itemSpec[$item['attribute_id']])) {
                    $itemSpec[$item['attribute_id']] = [
                        'attribute_id' => $item['attribute_id'],
                        'attribute_name' => $item['attribute_name'],
                        'values' => [],
                    ];
                    $itemSpec[$item['attribute_id']]['values'][$item['attribute_value_id']] = [
                        'attribute_value_id' => $item['attribute_value_id'],
                        'attribute_value_name' => $item['attribute_value_name'],
                    ];
                } else {
                    $itemSpec[$item['attribute_id']]['values'][$item['attribute_value_id']] = [
                        'attribute_value_id' => $item['attribute_value_id'],
                        'attribute_value_name' => $item['attribute_value_name'],
                    ];
                }
                foreach ($itemSpec as $key => $val) {
                    $itemSpec[$key]['values'] = array_values($val['values']);
                }
                $itemSpec = array_values($itemSpec);
            }
        }

        return [
            'main_category_ids' => $mainCategoryIds,
            'distributor_ids' => $distributorIds,
            'item_params' => $itemParams,
            'item_spec' => $itemSpec,
            'discount_rate' => $discountRate,
        ];
    }

    public function updateStartNum($itemId, $startNum)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setStartNum($startNum);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_source' => $itemsEnt->getItemSource() ? $itemsEnt->getItemSource() : 'mall',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'start_num' => $itemsEnt->getStartNum(),
            'updated' => $itemsEnt->getUpdated(),
        ];

        return $result;
    }

}
