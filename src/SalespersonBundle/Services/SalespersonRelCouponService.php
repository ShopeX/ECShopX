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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonRelCoupon;

class SalespersonRelCouponService
{
    /**
     * SalespersonRelCoupon Repositories实例化
     */
    public $salespersonRelCouponRepository;


    public function __construct()
    {
        $this->salespersonRelCouponRepository = app('registry')->getManager('default')->getRepository(SalespersonRelCoupon::class);
    }

    /**
     * 获取导购分类
     *
     * @param array $filter
     * @param integer $page
     * @param integer $pageSize
     * @param array $orderBy
     * @return void
     */
    public function getSalespersonCouponList($filter, $page = 1, $pageSize = 10, $orderBy = ['coupon_id' => 'desc'])
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('kaquan_discount_cards', 'kdc')
            ->join('kdc', 'salesperson_rel_coupon', 'src', 'kdc.card_id = src.coupon_id');

        $row = 'kdc.*,src.id,src.send_num';

        $criteria = $this->getFilter($filter, $criteria);

        $result['total_count'] = $criteria->execute()->fetchColumn();
        $result['list'] = [];
        if ($result['total_count'] > 0) {
            if ($page > 0) {
                $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
            }

            foreach ($orderBy as $key => $value) {
                $criteria->addOrderBy($key, $value);
            }
            $lists = $criteria->select($row)->execute()->fetchAll();
            $result['list'] = $lists;
        }
        return $result;
    }

    /**
     * 添加导购发放优惠券
     *
     * @param int $companyId
     * @param array $params
     * @return void
     */
    public function createCoupon($companyId, array $params)
    {
        $result = [];
        foreach ($params as $v) {
            $this->salespersonRelCouponRepository->deleteBy(['company_id' => $companyId, 'coupon_id' => $v['coupon_id']]);
            $data = [
                'company_id' => $companyId,
                'coupon_id' => $v['coupon_id'],
                'send_num' => $v['send_num'] ?? 1,
            ];
            $result[] = $this->salespersonRelCouponRepository->create($data);
        }

        return $result;
    }


    /**
     * 删除导购发放优惠券
     *
     * @param int $id
     * @param int $companyId
     * @return void
     */
    public function deleteCouponById($id, $companyId)
    {
        $result = $this->salespersonRelCouponRepository->deleteBy(['id' => $id, 'company_id' => $companyId]);
        return $result;
    }

    private function getFilter($filter, $criteria)
    {
        $coupon = ['company_id'];

        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if ($filterValue) {
                    if (is_array($filterValue)) {
                        array_walk($filterValue, function (&$value) use ($criteria) {
                            $value = $criteria->expr()->literal($value);
                        });
                    } else {
                        $filterValue = $criteria->expr()->literal($filterValue);
                    }
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $v = in_array($v, $coupon) ? 'kdc.'.$v : $v;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->$k($v, $filterValue)
                        ));
                        continue;
                    } elseif (is_array($filterValue)) {
                        $key = in_array($key, $coupon) ? 'kdc.'.$key : $key;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->in($key, $filterValue)
                        ));
                        continue;
                    } else {
                        $key = in_array($key, $coupon) ? 'kdc.'.$key : $key;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->eq($key, $filterValue)
                        ));
                    }
                }
            }
        }
        return $criteria;
    }

    /**
     * Dynamically call the SalespersonNoticeService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonRelCouponRepository->$method(...$parameters);
    }
}
