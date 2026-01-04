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

namespace SelfserviceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use SelfserviceBundle\Entities\FormTemplate;

use Dingo\Api\Exception\ResourceException;
use Exception;


class FormTemplateRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = 'selfservice_form_template';

    public $prk = 'id';
    private $multiLangField = [
        'tem_name',
        'content',
        'header_link_title',
        'header_bg_pic',
        'header_title'
    ];
    public function getLangService()
    {
        // Built with ShopEx Framework
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new FormTemplate();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result =  $this->getColumnNamesData($entity);
        $data['content'] = is_array($data['content']) ? json_encode($data['content']) : $data['content'];
        $this->getLangService()->addMultiLangByParams($result[$this->prk],$data,$this->table);

        return $result;
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
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        if(isset($filter[$this->prk])){
            $data['content'] = is_array($data['content']) ? json_encode($data['content']) : $data['content'];
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
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $tmp = $this->getColumnNamesData($entityProp);
            if(isset($tmp[$this->prk])){
                $this->getLangService()->updateLangData($data,$this->table,$tmp[$this->prk]);
            }
            $result[] = $tmp;
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
            throw new Exception(trans("SelfserviceBundle.delete_data_not_exist"));
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
            throw new Exception(trans("SelfserviceBundle.delete_data_not_exist"));
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
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

        $result = $this->getColumnNamesData($entity);
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

        $result = $this->getColumnNamesData($entity);
        if ($result && $result['content']) {
            $content = $result['content'];
            unset($result['content']);
            $newArr = array();
            foreach ($content as $key => &$val) {
                $newArr[$key]['sort'] = $val['sort'];

                if ($val['formdata'] ?? null) {
                    $formdata = $val['formdata'];
                    unset($val['formdata']);
                    $na = [];
                    foreach ($formdata as $k => $v) {
                        $na[$k]['sort'] = $v['sort'] ?? 1;
                    }
                    array_multisort($na, SORT_ASC, $formdata);
                    $val['formdata'] = $formdata;
                }
            }
            array_multisort($newArr, SORT_ASC, $content);
            $result['content'] = $content;
        }

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
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
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
        $res["list"] = $this->getLangService()->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData(FormTemplate $entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["tem_name"]) && $data["tem_name"]) {
            $entity->setTemName($data["tem_name"]);
        }
        if (isset($data["header_height"])) {
            $entity->setHeaderHeight($data["header_height"]);
        }
        if (isset($data["header_bg_pic"])) {
            $entity->setHeaderBgPic($data["header_bg_pic"]);
        }
        if (isset($data["content"]) && $data["content"]) {
            $entity->setContent(json_encode($data["content"]));
        }
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }
        if (isset($data["tem_type"]) && $data["tem_type"]) {
            $entity->setTemType($data["tem_type"]);
        }
        if (isset($data["key_index"]) && $data["key_index"]) {
            $entity->setKeyIndex(json_encode($data["key_index"]));
        }
        if (isset($data["form_style"]) && $data["form_style"]) {
            $entity->setFormStyle($data["form_style"]);
        }
        if (isset($data["header_link_title"])) {
            $entity->setHeaderLinkTitle($data["header_link_title"]);
        }
        if (isset($data["header_title"])) {
            $entity->setHeaderTitle($data["header_title"]);
        }
        if (isset($data["bottom_title"])) {
            $entity->setBottomTitle($data["bottom_title"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData(FormTemplate $entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'header_bg_pic' => $entity->getHeaderBgPic(),
            'header_height' => $entity->getHeaderHeight(),
            'tem_name' => $entity->getTemName(),
            'content' => (array)json_decode($entity->getContent(), true),
            'status' => $entity->getStatus(),
            'form_style' => $entity->getFormStyle(),
            'header_link_title' => $entity->getHeaderLinkTitle(),
            'header_title' => $entity->getHeaderTitle(),
            'bottom_title' => $entity->getBottomTitle(),
            'key_index' => (array)json_decode($entity->getKeyIndex(), true),
            'tem_type' => $entity->getTemType(),
        ];
    }

    public function discard($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.data_not_exist"));
        }
        $data['status'] = 2;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
    }

    public function restore($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.data_not_exist"));
        }
        $data['status'] = 1;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
    }
}
