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

use CompanysBundle\Services\EmailService;
use DistributionBundle\Services\DistributorService;

use EmployeePurchaseBundle\Entities\Enterprises;
use EmployeePurchaseBundle\Entities\EnterpriseEmailBox;


class EnterprisesService
{
    /** @var \EmployeePurchaseBundle\Repositories\EnterprisesRepository */
    public $enterprisesRepository;
    public $enterpriseEmailBoxRepository;

    public function __construct()
    {
        $this->enterprisesRepository = app('registry')->getManager('default')->getRepository(Enterprises::class);
        $this->enterpriseEmailBoxRepository = app('registry')->getManager('default')->getRepository(EnterpriseEmailBox::class);
    }

    /**
     *
     * 创建
     * @param $data
     * @return mixed
     */
    public function create($params)
    {
        $this->__checkExist($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->enterprisesRepository->create($params);
            if ($params['auth_type'] == 'email') {
                $params['enterprise_id'] = $result['id'];
                $relData = $this->saveRelEmailBox($params);
                $result['relay_host'] = $relData['relay_host'] ?? '';
                $result['smtp_port'] = $relData['smtp_port'] ?? '';
                $result['email_user'] = $relData['user'] ?? '';
                $result['email_password'] = $relData['password'] ?? '';
                $result['email_suffix'] = $relData['suffix'] ?? '';
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 修改
     * @param $filter array 条件
     * @param $params array 修改信息
     */
    public function updateEnterprise($filter, $params) 
    {
        $params['company_id'] = $filter['company_id'];
        $params['enterprise_id'] = $filter['id'];
        $this->__checkExist($params);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->enterprisesRepository->updateOneBy($filter, $params);
            if ($params['auth_type'] == 'email') {
                $relData = $this->saveRelEmailBox($params);
                $result['relay_host'] = $relData['relay_host'] ?? '';
                $result['smtp_port'] = $relData['smtp_port'] ?? '';
                $result['email_user'] = $relData['user'] ?? '';
                $result['email_password'] = $relData['password'] ?? '';
                $result['email_suffix'] = $relData['suffix'] ?? '';
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }
    
    public function __checkExist($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'name' => $params['name'],
        ];
        $data = $this->enterprisesRepository->getInfo($filter);
        if ($data && $data['id'] != ($params['enterprise_id'] ?? 0)) {

            throw new ResourceException('企业名称不能重复');
        }

        $filter = [
            'company_id' => $params['company_id'],
            'enterprise_sn' => $params['enterprise_sn'],
        ];
        $data = $this->enterprisesRepository->getInfo($filter);
        if ($data && $data['id'] != ($params['enterprise_id'] ?? 0)) {
            throw new ResourceException('企业编码不能重复');
        }

        if ($params['auth_type'] == 'email') {
            $filter = [
                'company_id' => $params['company_id'],
                'suffix' => $params['email_suffix'],
            ];
            $relData = $this->enterpriseEmailBoxRepository->getInfo($filter);
            if ($relData && $relData['enterprise_id'] != ($params['enterprise_id'] ?? 0)) {
                throw new ResourceException('企业收件邮箱后缀不能重复');
            }
        }

        return true;
    }

    /**
     *
     * 获取列表
     * @param $filter
     * @param array $orderBy
     * @param int $pageSize
     * @param int $page
     * @return mixed
     */
    public function getEnterprisesList($filter, $page = 1, $pageSize = 20, $orderBy = ['created' => 'DESC'])
    {
        $result = $this->enterprisesRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$result['total_count'] || !$result['list']) {
            return $result; 
        }
        $enterpriseIds = array_column($result['list'], 'id');
        $relFilter = [
            'company_id' => $filter['company_id'],
            'enterprise_id' => $enterpriseIds,
        ];
        $relList = $this->enterpriseEmailBoxRepository->getLists($relFilter);
        $relList = array_column($relList, null, 'enterprise_id');
        $distributorService = new DistributorService();
        $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });
        $storeData = [];
        if ($storeIds) {
            $storeList = $distributorService->getDistributorOriginalList([
                'company_id' => $filter['company_id'],
                'distributor_id' => $storeIds,
            ], 1, $pageSize);
            $storeData = array_column($storeList['list'], null, 'distributor_id');
            // 附加总店信息
            $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
        }
        foreach ($result['list'] as $key => $row) {
            if ($row['auth_type'] == 'email') {
                $relData = $relList[$row['id']] ?? [];
                $result['list'][$key]['relay_host'] = $relData['relay_host'] ?? '';
                $result['list'][$key]['smtp_port'] = $relData['smtp_port'] ?? '';
                $result['list'][$key]['email_user'] = $relData['user'] ?? '';
                $result['list'][$key]['email_password'] = $relData['password'] ?? '';
                $result['list'][$key]['email_suffix'] = $relData['suffix'] ?? '';
            }
            $result['list'][$key]['distributor_name'] = isset($row['distributor_id']) ? ($storeData[$row['distributor_id']]['name'] ?? '') : '';
            $result['list'][$key]['is_employee_check_enabled'] = $row['is_employee_check_enabled'] == true ? 'true' : 'false';

        }
        return $result;
    }

    public function getEnterpriseInfo($filter)
    {
        $data = $this->enterprisesRepository->getInfo($filter);
        if (!$data) {
            return [];
        }

        if ($data['auth_type'] == 'email') {
            $relFilter = [
                'company_id' => $data['company_id'],
                'enterprise_id' => $data['id'],
            ];
            $relData = $this->enterpriseEmailBoxRepository->getInfo($relFilter);
            if ($relData) {
                $data['relay_host'] = $relData['relay_host'];
                $data['smtp_port'] = $relData['smtp_port'];
                $data['email_user'] = $relData['user'];
                $data['email_password'] = $relData['password'];
                $data['email_suffix'] = $relData['suffix'];
            }
        }
        $data['is_employee_check_enabled'] = $data['is_employee_check_enabled'] == true ? 'true' : 'false';
        return $data;
    }
    
    public function delete($filter)
    {
        $data = $this->enterprisesRepository->getInfo($filter);
        if (!$data) {
            return true;
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->enterprisesRepository->deleteBy($filter);
            $relFilter = [
                'company_id' => $data['company_id'],
                'enterprise_id' => $data['id'],
            ];
            $this->enterpriseEmailBoxRepository->deleteBy($relFilter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 更新状态
     * @param   $filter 
     * @param   $params 
     * @return  boole true 
     */
    public function updateStatus($filter, $disabled)
    {
        $data = $this->enterprisesRepository->getInfo($filter);
        if (!$data) {
            throw new ResourceException('记录已经删除！');
        }

        return $this->enterprisesRepository->updateOneBy($filter, ['disabled' => $disabled]);
    }

    /**
     * 设置排序
     */
    public function setSort($filter, $sort)
    {
        $data = $this->enterprisesRepository->getInfo($filter);
        if (!$data) {
            throw new ResourceException('记录已经删除！');
        }

        return $this->enterprisesRepository->updateOneBy($filter, ['sort' => $sort]);
    }


    public function saveRelEmailBox($params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'enterprise_id' => $params['enterprise_id'],
            'smtp_port' => $params['smtp_port'],
            'relay_host' => $params['relay_host'],
            'user' => $params['email_user'],
            'password' => $params['email_password'],
            'suffix' => $params['email_suffix'],
        ];

        $filter = [
            'company_id' => $params['company_id'],
            'enterprise_id' => $params['enterprise_id'],
        ];
        $exist = $this->enterpriseEmailBoxRepository->count($filter);
        if ($exist) {
            return $this->enterpriseEmailBoxRepository->updateOneBy($filter, $data);
        } else {
            return $this->enterpriseEmailBoxRepository->create($data);
        }
    }

    public function getEnterpriseByEmailSuffix($filter)
    {
        if (isset($filter['company_id'])) {
            $filter['e.company_id'] = $filter['company_id'];
            unset($filter['company_id']);
        }
        if (isset($filter['suffix'])) {
            $filter['eb.suffix'] = $filter['suffix'];
            unset($filter['suffix']);
        }
        
        $relData = $this->enterprisesRepository->getListsRelSuffix($filter);
        if (empty($relData)) {
            return [];
        }
        $data = $relData[0];
        $data['relay_host'] = $data['relay_host'];
        $data['smtp_port'] = $data['smtp_port'];
        $data['email_user'] = $data['user'];
        $data['email_password'] = $data['password'];
        $data['email_suffix'] = $data['suffix'];
        return $data;
    }

    public function sendTestEmail($params)
    {
        // 查询企业设置的发件邮箱配置
        $info = $this->enterpriseEmailBoxRepository->getInfo(['company_id' => $params['company_id'], 'enterprise_id' => $params['enterprise_id']]);
        if (!$info) {
            throw new ResourceException('企业未配置发件邮箱');
        }
        $from = [
            'email_smtp_port' => $info['smtp_port'],
            'email_relay_host' => $info['relay_host'],
            'email_user' => $info['user'],
            'email_password' => $info['password'],
        ];
        $emailService = new EmailService($from);
        $to = $params['email'];
        // 标题
        $subject = '发件测试';
        //邮件内容
        $body = <<<EOF
<p>邮箱配置成功。</p>
EOF;
        return $emailService->sendmail($to, $subject, $body);
    }

    /**
     * 根据员工企业ID，获取店铺数据
     * @param  array $filter
     */
    public function getEnterpriseDistributorInfo($filter)
    {
        $enterpriseInfo = $this->getInfo($filter);
        if (!$enterpriseInfo) {
            throw new ResourceException('获取企业失败');
        }
        $result = [
            'distributor_id' => 0,
            'distributor_name' => '',
        ];
        if ($enterpriseInfo['distributor_id'] == 0) {
            return $result;
        }
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo(['distributor_id' => $enterpriseInfo['distributor_id']]);
        $result = [
            'distributor_id' => $distributorInfo['distributor_id'],
            'distributor_name' => $distributorInfo['name'],
        ];
        return $result;
    }
    

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->enterprisesRepository->$method(...$parameters);
    }
}
