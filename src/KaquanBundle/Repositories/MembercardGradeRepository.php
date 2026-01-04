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

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use KaquanBundle\Entities\MemberCardGrade;

class MembercardGradeRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = 'membercard_grade';

    private $prk = 'grade_id';

    public function setDefaultGrade($gradeInfo)
    {
        $gradeEntity = new MemberCardGrade();
        $em = $this->getEntityManager();
        $grade = $this->setGradeData($gradeEntity, $gradeInfo);
        $em->persist($grade);
        $em->flush();

        $result = [
            'company_id' => $grade->getCompanyId(),
            'grade_id' => $grade->getGradeId(),
            'grade_name' => $grade->getGradeName(),
            'default_grade' => $grade->getDefaultGrade(),
        ];
        $service = new MultiLangService();
        $service->addMultiLangByParams($result[$this->prk],$gradeInfo,$this->table);
        return $result;
    }

    public function update($companyId, $newGrades, $deleteIds, array &$newGradesList = [])
    {
        $service = new MultiLangService();
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if ($deleteIds) {
                foreach ($deleteIds as $gradeId) {
                    $delgrade = $this->findOneBy(['company_id' => $companyId, 'grade_id' => $gradeId]);
                    $em->remove($delgrade);
                    $em->flush();
                }
            }
            if ($newGrades) {
                foreach ($newGrades as $gradeInfo) {
                    $filter = [
                        'grade_id' => $gradeInfo['grade_id'],
                        'company_id' => $gradeInfo['company_id']
                    ];
                    $grade = $this->findOneBy($filter);
                    if (!$grade) {
                        $grade = new MemberCardGrade();
                    }
                    $grade = $this->setGradeData($grade, $gradeInfo);
                    $em->persist($grade);
                    $em->flush();
                    $tempItem = $this->getGradeData($grade);

                    $service->addMultiLangByParams($tempItem[$this->prk],$tempItem,$this->table);
                    $tempItem['voucher_package'] = empty($gradeInfo['voucher_package']) ? [] : $gradeInfo['voucher_package'];
                    $newGradesList[] = $tempItem;
                }
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }

    public function setGradeData($grade, $postdata)
    {
        if (isset($postdata['company_id'])) {
            $grade->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['grade_name'])) {
            $grade->setGradeName($postdata['grade_name']);
        }
        if (isset($postdata['default_grade'])) {
            $grade->setDefaultGrade($postdata['default_grade']);
        }
        if (isset($postdata['background_pic_url'])) {
            $grade->setBackgroundPicUrl($postdata['background_pic_url']);
        }
        if (isset($postdata['grade_background'])) {
            $grade->setGradeBackground($postdata['grade_background']);
        }
        if (isset($postdata['promotion_condition'])) {
            $grade->setPromotionCondition($postdata['promotion_condition']);
        }
        if (isset($postdata['privileges'])) {
            $grade->setPrivileges($postdata['privileges']);
        }
        if (isset($postdata['third_data'])) {
            $grade->setThirdData($postdata['third_data']);
        }
        if (isset($postdata["external_id"])) {
            $grade->setExternalId($postdata["external_id"]);
        } else {
            $grade->setExternalId((string)$grade->getExternalId());
        }
        if (isset($postdata["description"])) {
            $grade->setDescription($postdata["description"]);
        }
        if (isset($postdata["dm_grade_code"])) {
            $grade->setDmGradeCode($postdata["dm_grade_code"]);
        }
        return $grade;
    }

    public function getGradeData(MemberCardGrade $memberCardGrade)
    {
        return [
            "company_id" => $memberCardGrade->getCompanyId(),
            "grade_id" => $memberCardGrade->getGradeId(),
            "grade_name" => $memberCardGrade->getGradeName(),
            "default_grade" => $memberCardGrade->getDefaultGrade(),
            "background_pic_url" => $memberCardGrade->getBackgroundPicUrl(),
            "grade_background" => $memberCardGrade->getGradeBackground(),
            "promotion_condition" => $memberCardGrade->getPromotionCondition(),
            "privileges" => $memberCardGrade->getPrivileges(),
            "created" => $memberCardGrade->getCreated(),
            "updated" => $memberCardGrade->getUpdated(),
            "third_data" => $memberCardGrade->getThirdData(),
            "external_id" => $memberCardGrade->getExternalId(),
            "description" => $memberCardGrade->getDescription(),
            "dm_grade_code" => $memberCardGrade->getDmGradeCode(),
        ];
    }

    public function getListByCompanyId($companyId, $fields = '*')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($fields)
            ->from($this->table)
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('company_id', $qb->expr()->literal($companyId))
            ));
        $dataList =  $qb->execute()->fetchAll();
        $service = new MultiLangService();
        $dataList = $service->getListAddLang($dataList,[],$this->table,$this->getLang(),$this->prk);
        return $dataList;
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $OrderBy = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select($cols)
            ->from($this->table);

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }
        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } else {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                } elseif (is_array($filterValue)) {
                    $qb->andWhere($qb->expr()->in($key, $filterValue));
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        $lists = $qb->execute()->fetchAll();
        $service = new MultiLangService();
        $lists = $service->getListAddLang($lists,[],$this->table,$this->getLang(),$this->prk);
        return $lists;
    }

    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } else {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        return $qb->execute()->fetchColumn();
    }

    public function getInfo($filter)
    {
        $info = $this->findOneBy($filter);
        if (is_null($info)) {
            return [];
        }
        return $this->getGradeData($info);
    }
}
