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

namespace HfPayBundle\Services;

abstract class HfpayStatisticsBaseService
{
    protected function _where($filter, $criteria)
    {
        $orderStatus = $filter['order_status'] ?? [];
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }

        $order = [ 'company_id', 'create_time', 'distributor_id', 'pay_type', 'order_id'];

        if ($filter) {
            foreach ($filter as $key => $filterValue) {
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
                    $v = in_array($v, $order) ? 'a.'.$v : $v;
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->$k($v, $filterValue)
                    ));
                    continue;
                } elseif (is_array($filterValue)) {
                    $key = in_array($key, $order) ? 'a.'.$key : $key;
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->in($key, $filterValue)
                    ));
                    continue;
                } else {
                    $key = in_array($key, $order) ? 'a.'.$key : $key;
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        $criteria->andWhere($criteria->expr()->andX(
            $criteria->expr()->notIn('order_status', ["'NOTPAY'", "'PART_PAYMENT'", "'WAIT_GROUPS_SUCCESS'"]),
            $criteria->expr()->neq('pay_status', $criteria->expr()->literal('NOTPAY'))
        ));

        if ($orderStatus) {
            switch ($orderStatus) {
                case 'refunding':   //退款中
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('CANCEL_WAIT_PROCESS')),
                                $criteria->expr()->eq('cancel_status', $criteria->expr()->literal('WAIT_PROCESS'))
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('CANCEL_WAIT_PROCESS')),
                                $criteria->expr()->eq('cancel_status', $criteria->expr()->literal('NO_APPLY_CANCEL'))
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('PAYED')),
                                $criteria->expr()->eq('cancel_status', $criteria->expr()->literal('WAIT_PROCESS'))
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('DONE'))
                            )
                        )
                    );
                    break;
                case 'pay': //支付成功 = 待发货 + 待自提 + 待收货 + 已完成 + 待审核 + 已完成未开票 + 已完成已开票
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('PAYED')),
                                $criteria->expr()->in('cancel_status', "'NO_APPLY_CANCEL', 'FAILS'")
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('PAYED')),
                                $criteria->expr()->eq('receipt_type', $criteria->expr()->literal('ziti')),
                                $criteria->expr()->eq('ziti_status', $criteria->expr()->literal('PENDING'))
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('WAIT_BUYER_CONFIRM')),
                                $criteria->expr()->eq('delivery_status', $criteria->expr()->literal('DONE')),
                                $criteria->expr()->eq('receipt_type', $criteria->expr()->literal('logistics'))
                            ),
                            $criteria->expr()->andX(
                                $criteria->expr()->in('order_status', "'DONE', 'REVIEW_PASS'")
                            )
                        )
                    );
                    break;
                case 'refundsuccess': //退款成功
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('CANCEL')),
                                $criteria->expr()->eq('cancel_status', $criteria->expr()->literal('SUCCESS'))
                            )
                        )
                    );
                    break;
                case 'refundfail': //退款失败
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->andX(
                                $criteria->expr()->eq('order_status', $criteria->expr()->literal('DONE')),
                                $criteria->expr()->eq('cancel_status', $criteria->expr()->literal('FAILS'))
                            )
                        )
                    );
                    break;
            }
        }
        return $criteria;
    }
}
