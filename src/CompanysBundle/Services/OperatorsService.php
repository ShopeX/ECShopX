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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\Companys;
use CompanysBundle\Entities\DistributorWorkWechatRel;
use CompanysBundle\Entities\OperatorLicense;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Events\CompanyCreateEvent;

use CompanysBundle\Repositories\DistributorWorkWechatRelRepository;
use CompanysBundle\Repositories\OperatorLicenseRepository;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use OrdersBundle\Entities\NormalOrders;
use PromotionsBundle\Services\SystemSmsService;
use SuperAdminBundle\Services\ShopMenuService;

class OperatorsService
{
    /** @var CompanysRepository */
    private $companysRepository;

    /** @var \CompanysBundle\Repositories\OperatorsRepository */
    public $operatorsRepository;

    /**
     * OperatorService 构造函数.
     */
    public function __construct()
    {
        // $this->companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        // $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->companysRepository = getRepositoryLangue(Companys::class);
        $this->operatorsRepository = getRepositoryLangue(Operators::class);
    }

    public function getOperatorByMobile($mobile, $operatorType)
    {
        // This module is part of ShopEx EcShopX system
        $users = $this->operatorsRepository->getOperatorByMobile($mobile, $operatorType);
        return $users;
    }

    public function open($params)
    {
        $operator = $this->operatorsRepository->getInfo(['mobile' => $params['mobile'], 'operator_type' => 'admin']);
        if ($operator) {
            throw new Exception("账号已开通");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params['operator_type'] = 'admin'; // 首次开通账号固定位'admin'
            // 创建商家超级管理员
            $operator = $this->createOperator($params);
            if (isset($params['expiredAt']) && $params['expiredAt']) {
                $expiredAt = strtotime($params['expiredAt']);
            } else {
                $expiredAt = 9999999999;
            }
            $params['company_admin_operator_id'] = $operator['operator_id'];
            $params['expiredAt'] = $expiredAt;
            $params['company_name'] = $params['company_name'] ?? '';
            $params['is_disabled'] = $params['is_disabled'] ?? 0;
            $params['third_params'] = $params['third_params'] ?? [];

            //防止数据库自增id出错，独立部署版本强制为 env 里的值

            if (!config('common.system_is_saas')) {
                $params['company_id'] = config('common.system_companys_id');
            }
            $indexMenuType = array_flip(ShopMenuService::MENU_TYPE);
            $params['menu_type'] = $indexMenuType[$params['menu_type'] ?? config('common.product_model')];

            // $company = $this->companysRepository->create($params);
            $company = $this->companysRepository->add($params);
            $this->operatorsRepository->updateOneBy(['operator_id' => $operator['operator_id']], ['company_id' => $company['company_id']]);
            $demoParams = [
                'eid' => $params['eid'] ?? null,
                'passport_uid' => $params['passport_uid'] ?? null,
                'company_id' => $company['company_id'],
                'expired_at' => $expiredAt,
            ];
            $result = app('authorization')->createDemoCompanyLicense($demoParams);
            // 如果是pms开通，以下信息无效
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            // $companyId = $company['company_id'] ?? 1;
            // $this->companysRepository->resetCompanyId($companyId);//复位自增ID
            throw $e;
        }
        event(new CompanyCreateEvent(['company_id' => $company['company_id']]));
        $result = [
            'operator_id' => $operator['operator_id'],
            'company_id' => $company['company_id'],
            'mobile' => $params['mobile'],
        ];
        return $result;
    }

    // 创建管理员、员工账号
    public function createOperator($data)
    {
        $data['operator_type'] = $data['operator_type'];
        $data['mobile'] = $data['mobile'] ?? '';
        $data['eid'] = $data['eid'] ?? '';
        $data['login_name'] = $data['login_name'] ?? '';
        if (!in_array($data['operator_type'], ['admin', 'staff', 'distributor','dealer', 'merchant', 'supplier', 'agent', 'self_delivery_staff'])) {
            throw new ResourceException("账号类型不存在");
        }
        if (!$data['mobile']) {
            throw new ResourceException("请填写手机号");
        }
        $operator = $this->operatorsRepository->getInfo(['mobile' => $data['mobile'], 'operator_type' => $data['operator_type']]);
        if ($operator) {
            throw new ResourceException("该手机号已被使用");
        }

        if (!$data['eid'] && $data['operator_type'] != 'self_delivery_staff') {
            if (!$data['login_name']) {
                throw new ResourceException("请填写账号名");
            }
            $filter = ['login_name' => $data['login_name'], 'operator_type' => $data['operator_type']];
            $operator = $this->operatorsRepository->getInfo($filter);
            if ($operator) {
                throw new ResourceException("该账号名已被使用");
            }
        }
        if (isset($data['password']) && $data['password']) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['operator_id']);
//        if ($data['operator_type'] == 'dealer' && isset($data['is_dealer_main']) && !$data['is_dealer_main']) {
//            $data['is_dealer_main'] = 1;
//        }
        $res = $this->operatorsRepository->create($data);
        if ($data['operator_type'] == 'dealer' && isset($data['is_dealer_main']) && $data['is_dealer_main']) {
            $this->operatorsRepository->updateOneBy(['operator_id' => $res['operator_id']], ['dealer_parent_id' => $res['operator_id']]);
        }
        return $res;
    }

    // 修改管理员、员工账号信息
    public function updateOperator($operator_id, $data)
    {
        if (!$operator_id) {
            throw new ResourceException("请选择要修改的账号");
        }
        $filter = ['operator_id' => $operator_id];
        $oldOperator = $this->operatorsRepository->getInfo($filter);
        if ($oldOperator['login_name']) {
            unset($data['login_name']);
        }
        // if ($oldOperator['mobile']) {
        //     unset($data['mobile']);
        // }

        if (isset($data['mobile']) && $data['mobile']) {
            $filter = ['mobile' => $data['mobile'], 'operator_type' => $data['operator_type']];
            $operator = $this->operatorsRepository->getInfo($filter);
            if ($operator && ($operator['operator_id'] != $operator_id)) {
                throw new ResourceException("该手机号已被使用");
            }
        }
        if (isset($data['login_name']) && $data['login_name']) {
            $filter = ['login_name' => $data['login_name'], 'operator_type' => $data['operator_type']];
            $operator = $this->operatorsRepository->getInfo($filter);
            if ($operator && ($operator['operator_id'] != $operator_id)) {
                throw new ResourceException("该账号名已被使用");
            }
        }
        if (isset($data['password']) && $data['password']) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['operator_type']); // 不允许修改账号类型
        return $this->operatorsRepository->updateOneBy(['operator_id' => $operator_id], $data);
    }

    // 禁用或者启用管理账号
    public function changeOperatorStatus($authFilter, $isDisable = 0)
    {
        if (!$authFilter['company_id']) {
            throw new ResourceException("公司id必填");
        }

        if (!$authFilter['operator_id']) {
            throw new ResourceException("要修改的账号id必填");
        }
        $filter = [
            'company_id' => $authFilter['company_id'],
            'operator_id' => $authFilter['operator_id'],
        ];

         $operator = $this->operatorsRepository->getInfo($filter);
         if($operator && $operator['operator_type'] == 'self_delivery_staff' && $isDisable){
             $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
             $oFilter = [
                 'self_delivery_status|in'=>['RECEIVEORDER','PACKAGED','DELIVERING'],
                 'company_id'=>$filter['company_id'],
                 'self_delivery_operator_id'=>$authFilter['operator_id']
             ];

             $orderList = $normalOrdersRepository->getList($oFilter,0,-1,null,'order_id');
             if($orderList){
                 throw new ResourceException("该配送员还有未配置完的订单，所有订单配送员后可禁用！");
             }
         }

        $data = ['is_disable' => $isDisable];
        return $this->operatorsRepository->updateOneBy($filter, $data);
    }

    // 修改密码
    public function updatePasswordByMobile($mobile, $password)
    {
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this->operatorsRepository->updateOneBy(['mobile' => $mobile], $data);
    }


    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'companyActivateInfo:'. sha1($companyId);
    }

    //生成验证码的redis key
    private function generateReidsKey($token, $type = "image")
    {
        return "admin-" . $type . ":" . $token;
    }

    //redis存储
    private function redisStore($key, $value, $expire = 300)
    {
        app('log')->info("shop forget redis store :" . json_encode(['key' => $key, 'value' => $value, 'expire' => $expire]));
        app('redis')->connection('companys')->set($key, $value);
        app('redis')->connection('companys')->expire($key, $expire);
        return true;
    }

    //redis读取
    private function redisFetch($key)
    {
        app('log')->info("shop forget redis fetch :" . json_encode(['key' => $key]));
        return app('redis')->connection('companys')->get($key);
    }

    //生成图片验证码
    public function generateImageVcode($type)
    {
        $builder = new CaptchaBuilder(4);
        $builder->build();
        $vcode = $builder->getPhrase();
        $data = $builder->get();
        $data = "data:image/png;base64," . base64_encode($data);
        $token = md5(uniqid(microtime(true), true));
        $key = $this->generateReidsKey($token, $type);
        $this->redisStore($key, $vcode);
        return [$token, $data];
    }


    //验证图片验证码是否正确
    public function checkImageVcode($token, $vcode, $type)
    {
        if (empty($token)) {
            throw new ResourceException('请输入token');
        }
        if (empty($vcode)) {
            throw new ResourceException('请输入vcode');
        }
        $key = $this->generateReidsKey($token, $type);
        $storeVcode = $this->redisFetch($key);
        if (strtoupper($storeVcode) == strtoupper($vcode)) {
            app('redis')->connection('companys')->del($key);
            return true;
        }
        return false;
    }

    //生成短信验证码
    public function generateSmsVcode($phone, $type)
    {
        $key = $this->generateReidsKey($phone, $type);
        $time = intval(app('redis')->connection('companys')->ttl($key));
        if ($time - 240 > 0) {
            $time = $time - 240;
            throw new ResourceException('请' . $time . '秒后重试发送验证码');
        }
        $vcode = (string)rand(100000, 999999);
        app('log')->info("shop forget code :" . json_encode(['phone' => $phone, 'vcode' => $vcode]));
        //保存验证码
        $this->redisStore($key, $vcode);
        ;
        //发送短信
        $this->sendSmsVcode($phone, $vcode);
        return true;
    }

    //验证短信验证码
    public function checkSmsVcode($phone, $vcode, $type)
    {
        if (empty($phone)) {
            throw new ResourceException('请输入手机号');
        }
        $key = $this->generateReidsKey($phone, $type);
        $storeVcode = $this->redisFetch($key);
        if ($storeVcode == $vcode) {
            app('redis')->connection('companys')->del($key);
            return true;
        }
        return false;
    }

    //短信验证码的发送动作
    private function sendSmsVcode($phone, $code)
    {
        $smsContent = '您的验证码是'.$code.'，有效期为30分钟';
        $smsService = new SystemSmsService();
        $smsService->fireWall($phone, $smsContent);
        $smsService->sendSms($phone, $smsContent);
        return true;
    }


    /**
    * 选择版本，留资
    */
    public function createYdleadsData($companyId, $data)
    {
        // 查询官网账号信息
        $operatorInfo = $this->getInfo(['company_id' => $companyId,'operator_type' => 'admin']);
        if (!$operatorInfo) {
            return false;
        }
        $params = [
            'shopexid' => $operatorInfo['passport_uid'],
            'entid' => $operatorInfo['eid'],
            'goods_name' => $data['goods_name'],
            'call_name' => $data['call_name'],
            'sex' => $data['sex'],
            'mobile' => $data['mobile'],
        ];
        $prismIshopexService = new PrismIshopexService();
        $result = $prismIshopexService->opaYdleadsCreate($params);
        return $result;
    }

    public function getLicenseInfo(): OperatorLicense
    {
        /** @var OperatorLicenseRepository $licenseRepo */
        $licenseRepo = app('registry')->getManager('default')->getRepository(OperatorLicense::class);
        return $licenseRepo->getEntityByType(OperatorLicenseRepository::TYPE_APP);
    }

    public function setLicenseInfo($data)
    {
        /** @var OperatorLicenseRepository $licenseRepo */
        $licenseRepo = app('registry')->getManager('default')->getRepository(OperatorLicense::class);
        $license = $licenseRepo->getEntityByType(OperatorLicenseRepository::TYPE_APP);
        $license->setType(OperatorLicenseRepository::TYPE_APP);
        $license->setTitle($data['title']);
        $license->setContent($data['content']);
        return $licenseRepo->persistEntity($license);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->operatorsRepository->$method(...$parameters);
    }

    public function getPassportUid($companyId) {
        $filter = [
            'company_id' => $companyId,
            'operator_type' => 'admin'
        ];
        $result = $this->operatorsRepository->getInfo($filter);
        return $result['passport_uid'];
    }

    public function getInfo($filter, $is_detail = false)
    {
        $result = $this->operatorsRepository->getInfo($filter);
        if (!$is_detail) {
            return $result;
        }
        $result['distributors'] = [];
        $operator_type = app('auth')->user()->get('operator_type');
        $distributor_ids = array_column($result['distributor_ids'] ?: [], 'distributor_id');
        $distributorService = new DistributorService();
        if ($distributor_ids) {
            $distributor_filter = [
                'company_id' => $filter['company_id'],
                'distributor_id' => $distributor_ids,
            ];
            $data = $distributorService->lists($distributor_filter, ["created" => "DESC"]);
            $result['distributors'] = $data['list'] ?? [];
        } elseif ($operator_type == 'admin') {
            $distributor_filter = [
                'company_id' => $filter['company_id'],
            ];
            $data = $distributorService->lists($distributor_filter, ["created" => "DESC"]);
            $result['distributors'] = $data['list'] ?? [];
        }
        foreach ($result['distributors'] as &$d) {
            $d['is_center'] = false;
        }
        if (in_array('0', $distributor_ids) || $operator_type == 'admin') {
            $self_dis = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
            $self_dis['is_center'] = true;
            array_unshift($result['distributors'], $self_dis);
        }
        $workwechat_filter = [
            'company_id' => $filter['company_id'],
            'operator_id' => $filter['operator_id'],
        ];
        /** @var DistributorWorkWechatRelRepository $workWechatRepositories */
        $workwechat_repositories = app('registry')->getManager('default')->getRepository(DistributorWorkWechatRel::class);
        $workwechat_info = $workwechat_repositories->getInfo($workwechat_filter);
        $result['work_userid'] = $workwechat_info['work_userid'] ?? '';

        // 获取用户角色信息
        $employeeService = new EmployeeService();
        $result['role_data'] = $employeeService->getRoleData($filter['company_id'], $filter['operator_id']);
        return $result;
    }
}
