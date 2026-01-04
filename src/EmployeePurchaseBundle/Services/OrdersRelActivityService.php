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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;

use EmployeePurchaseBundle\Entities\OrdersRelActivity;

class OrdersRelActivityService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OrdersRelActivity::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    /**
     * 获取内购订单的一些信息，员工企业、员工信息
     */
    public function getDetail($filter)
    {
        $relData = $this->entityRepository->getInfo($filter);
        if (empty($relData)) {
            return null;
        }
        $sql = 'SELECT `op`.`order_id`,`op`.`user_id`,`op`.`enterprise_id`,`op`.`activity_id`,`ep`.`name` AS `enterprise_name`,
            CASE 
                WHEN `epe`.`id` IS NOT NULL THEN "employee"
                WHEN `epr`.`id` IS NOT NULL THEN "relative"
                ELSE ""
            END AS `type`,
            CASE 
                WHEN `epe`.`id` IS NOT NULL THEN `epe`.`name`
                WHEN `epr`.`id` IS NOT NULL THEN `epe2`.`name`
                ELSE NULL
            END AS `employee_name`
            FROM `employee_purchase_orders_rel_activity` AS `op`
            LEFT JOIN `employee_purchase_employees` AS `epe`
            ON `epe`.`enterprise_id` = `op`.`enterprise_id` AND `epe`.`user_id` = `op`.`user_id` 
            LEFT JOIN `employee_purchase_relatives` AS `epr`
            ON `epr`.`enterprise_id` =`op`.`enterprise_id` AND `epr`.`user_id` = `op`.`user_id` AND `epr`.`activity_id` = `op`.`activity_id`
            LEFT JOIN `employee_purchase_employees` AS `epe2`
            ON `epe2`.`id` = `epr`.`employee_id`
            LEFT JOIN `employee_purchase_enterprises` AS `ep`
            ON `ep`.`id` = `op`.`enterprise_id`
            WHERE `op`.`order_id` = '.intval($filter['order_id']);
        app('log')->info('=======>sql=====>'.var_export($sql, true));
        $conn = app('registry')->getConnection('default');
        $result = $conn->fetchAssoc($sql);
        app('log')->info('=======>result=====>'.var_export($result, true));
        return $result;
        $result = [];
        $employeesService = new EmployeesService();
        $employeeInfo = $employeesService->getInfo([
            'company_id' => $filter['company_id'],
            'enterprise_id' => $relData['enterprise_id'],
            'user_id' => $relData['user_id'],
        ]);
        if (empty($employeeInfo)) {
            $result['type'] = 'relative';// 亲友
            $relativesService = new RelativesService();
            $relativesInfo = $relativesService->getInfo([
                'company_id' => $filter['company_id'],
                'enterprise_id' => $relData['enterprise_id'],
                'user_id' => $relData['user_id'],
            ]);
            if (!empty($relativesInfo)) {
                $relativesEmployeeInfo = $employeesService->getInfoById($relativesInfo['employee_id']);
                $result['employee_name'] = $relativesEmployeeInfo['name'];
            }
        } else {
            $result['type'] = 'employee';// 员工
            $result['employee_name'] = $employeeInfo['name'];
        }
        // 查询企业信息
        $enterprisesService = new EnterprisesService();
        $enterprisesInfo = $enterprisesService->getInfoById($relData['enterprise_id']);
        if (!empty($enterprisesInfo)) {
            $result['enterprise_id'] = $enterprisesInfo['id'];
            $result['enterprise_name'] = $enterprisesInfo['name'];
        }
        return $result;
    }

    public function getOrdersRelList($filter)
    {
        $relDataLists = $this->entityRepository->getLists($filter);
        if (empty($relDataLists)) {
            return null;
        }
        $sql = 'SELECT `op`.`order_id`,`op`.`user_id`,`op`.`enterprise_id`,`op`.`activity_id`,
            `ep`.`name` AS `enterprise_name`,
            CASE 
                WHEN `epe`.`id` IS NOT NULL THEN "employee"
                WHEN `epr`.`id` IS NOT NULL THEN "relative"
                ELSE ""
            END AS `type`,
            CASE 
                WHEN `epe`.`id` IS NOT NULL THEN `epe`.`name`
                WHEN `epr`.`id` IS NOT NULL THEN `epe2`.`name`
                ELSE NULL
            END AS `employee_name`
            FROM `employee_purchase_orders_rel_activity` AS `op`
            LEFT JOIN `employee_purchase_employees` AS `epe`
            ON `epe`.`enterprise_id` = `op`.`enterprise_id` AND `epe`.`user_id` = `op`.`user_id` 
            LEFT JOIN `employee_purchase_relatives` AS `epr`
            ON `epr`.`enterprise_id` = `op`.`enterprise_id` AND `epr`.`user_id` = `op`.`user_id` AND `epr`.`activity_id` = `op`.`activity_id`
            LEFT JOIN `employee_purchase_employees` AS `epe2` ON `epe2`.`id` = `epr`.`employee_id`
            LEFT JOIN `employee_purchase_enterprises` AS `ep`
            ON `ep`.`id` = `op`.`enterprise_id`
            WHERE `op`.`order_id` IN ('.implode(',', $filter['order_id']).')';
        $conn = app('registry')->getConnection('default');
        $result = $conn->fetchAll($sql);
        return $result;
    }
}
