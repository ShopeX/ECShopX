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

namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
// use GuzzleHttp\Client as Client;

use BsPayBundle\Entities\UserEnt;
use BsPayBundle\Entities\EntryApply;
use BsPayBundle\Entities\UserCard;
use BsPayBundle\Entities\UserUpdateLog;
use BsPayBundle\Services\UserService;
use BsPayBundle\Services\V2\User\BasicdataEntModify;

use PaymentBundle\Services\Payments\BsPayService;

/**
 * 企业用户
 */
class UserEntService
{
    public $userEntRepository;
    public $entryApplyRepository;
    public $userCardRepository;
    public $userUpdateLogRepository;
    public $user_type = 'ent';

    public function __construct($companyId = 0)
    {
        $this->userEntRepository = app('registry')->getManager('default')->getRepository(UserEnt::class);
        $this->entryApplyRepository = app('registry')->getManager('default')->getRepository(EntryApply::class);
        $this->userCardRepository = app('registry')->getManager('default')->getRepository(UserCard::class);
        $this->userUpdateLogRepository = app('registry')->getManager('default')->getRepository(UserUpdateLog::class);
    }

    //创建企业用户
    public function createUser($data = [])
    {
        // $data['req_seq_id'] = date("YmdHis") . rand(100000, 999999);
        $service = new BsPayService();
        $setting = $service->getPaymentSetting($data['company_id']);
        if (!$setting) {
            throw new ResourceException('请先配置支付信息');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data['sys_id'] = $setting['sys_id'];
            
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('data====>'.var_export($data,1));
            $userRes = $this->create($data);//保存到企业用户表
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('userRes====>'.var_export($userRes,1));
            if (!$userRes) {
                throw new ResourceException('企业用户信息保存失败');
            }
            // $bankCodeService = new BankCodeService();
            //创建结算账户
            $cardInfo = [
                'sys_id' => $data['sys_id'],
                'user_id' => $userRes['id'],
                'company_id' => $data['company_id'],
                'user_type' => $this->user_type,
                'card_type' => $data['card_type'] ?? 0,
                'card_name' => $data['legal_name'] ?? '',
                'card_no' => $data['card_no'] ?? '',
                'prov_id' => $data['prov_id'] ?? '',
                'area_id' => $data['area_id'] ?? '',
                'bank_code' => $data['bank_code'] ?? '',
                'branch_name' => $data['branch_name'] ?? '',
                'cert_no' => $data['legal_cert_no'] ?? '',
                'cert_validity_type' => $data['legal_cert_validity_type'] ?? '',
                'cert_begin_date' => $data['legal_cert_begin_date'] ?? '',
                'cert_end_date' => $data['legal_cert_end_date'] ?? '',
                'mp' => $data['mp'] ?? '',
            ];
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('cardInfo====>'.var_export($cardInfo,1));
            // $settleAccountService = new SettleAccountService();
            $cardRes = $this->userCardRepository->create($cardInfo);
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('cardRes====>'.var_export($cardRes,1));
            if (!$cardRes) {
                throw new ResourceException('结算卡创建失败');
            }

            // $address = $data['reg_prov'] ?? '' . "-" . $data['reg_area'] ?? '';
            $address = '';
            //同时创建一条申请记录
            $userService = new UserService();
            $operator = $userService->getOperator();
            $apply = [
                'user_type' => $this->user_type,
                'user_name' => $data['reg_name'] ?? '',
                'company_id' => $data['company_id'],
                'user_id' => $userRes['id'],
                'operator_id' => $operator['operator_id'],
                'operator_type' => $operator['operator_type'],
                'address' => $address,
                'status' => 'WAIT_APPROVE'
            ];
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('apply====>'.var_export($apply,1));
            $applyRes = $this->entryApplyRepository->create($apply);
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('applyRes====>'.var_export($applyRes,1));
            if (!$applyRes) {
                throw new ResourceException("开户申请创建失败");
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $userRes;
    }

    public function modifyUser($data = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //查询所有关联的企业用户
            $userInfo = $this->getInfo(['id' => $data['id']]);
            if (!$userInfo) {
                throw new ResourceException("开户信息不存在");
            }
            // 申请成功后修改
            $userService = new UserService();
            $isSuccessUpdate = $userInfo['audit_state'] == $userService::AUDIT_CARD_FAIL || $userInfo['audit_state'] == $userService::AUDIT_SUCCESS;
            if ($isSuccessUpdate) {
                unset($data['legal_cert_no']);
            }
            //更新企业用户表
            $filter = [
                'id' => $data['id'],
                'company_id' => $data['company_id'],
            ];
            // $_data = [
            //     'reg_name' => $data['reg_name'],
            //     'license_code' => $data['license_code'],
            //     'license_code' => $data['license_code'],
            // ];
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('filter====>'.var_export($filter,1));
            app('log')->info('data====>'.var_export($data,1));
            $userRes = $this->updateOneBy($filter, $data);
            if (!$userRes) {
                throw new ResourceException('企业用户信息更新失败');
            }

            //更新结算账户
            $filter = [
                'user_id' => $data['id'],
                'company_id' => $data['company_id'],
            ];
            $cardInfo = [
                'card_type' => $data['card_type'] ?? 0,
                'card_name' => $data['card_name'] ?? '',
                'card_no' => $data['card_no'] ?? '',
                'prov_id' => $data['prov_id'] ?? '',
                'area_id' => $data['area_id'] ?? '',
                'bank_code' => $data['bank_code'] ?? '',
                'branch_name' => $data['branch_name'] ?? '',
                'cert_no' => $data['cert_no'] ?? '',
                'cert_validity_type' => $data['cert_validity_type'] ?? '',
                'cert_begin_date' => $data['cert_begin_date'] ?? '',
                'cert_end_date' => $data['cert_end_date'] ?? '',
                'mp' => $data['mp'] ?? '',
            ];

            if ($isSuccessUpdate) {
                unset($cardInfo['card_name']);
            }
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('cardInfo====>'.var_export($cardInfo,1));
            $cardRes = $this->userCardRepository->updateOneBy($filter, $cardInfo);
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('cardRes====>'.var_export($cardRes,1));
            if (!$cardRes) {
                throw new ResourceException('结算卡更新失败');
            }
            // $regionService = new RegionService();
            // $prov = $regionService->getAreaName($data['prov_code']);
            // $area = $regionService->getAreaName($data['area_code']);
            $address = '';
            // $address = $data['reg_prov'] ?? '' . "-" . $data['reg_area'] ?? '';
            //同时创建一条申请记录
            $operator = $userService->getOperator();
            $apply = [
                'user_type' => $this->user_type,
                'user_name' => $data['reg_name'] ?? '',
                'company_id' => $data['company_id'],
                'user_id' => $userRes['id'],
                'operator_id' => $operator['operator_id'],
                'operator_type' => $operator['operator_type'],
                'address' => $address,
                'status' => 'WAIT_APPROVE'
            ];
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('apply====>'.var_export($apply,1));
            $rs = $this->entryApplyRepository->create($apply);
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('rs====>'.var_export($rs,1));
            if (!$rs) {
                throw new ResourceException("开户申请创建失败");
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $userRes;
    }

    /**
     * 更新企业用户(已开户)
     * @param  array  $data 
     * @return
     */
    public function updateUser($data = [])
    {
        $userInfo = $this->getInfo(['id' => $data['id']]);
        if (!$userInfo) {
            throw new ResourceException("开户信息不存在");
        }
        $data['req_seq_id'] = date("YmdHis").mt_rand();
        $this->updateOneBy(['id' => $member_id], ['is_update' => 1, 'req_seq_id' => $data['req_seq_id']]);

        $basicdataEnt = new BasicdataEntModify($data['company_id']);
        $apiRes = $basicdataEnt->handle($userInfo);

        $data = [
            'company_id' => $data['company_id'],
            'sys_id' => $userInfo['sys_id'],
            'huifu_id' => $userInfo['huifu_id'],
            'user_type' => $userInfo['user_type'],
            'user_id' => $data['id'],
            'data' => json_encode($data),
        ];
        app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
        app('log')->info('data====>'.var_export($data,1));
        $userService = new UserService();
        # 成功/失败应答的处理
        if (!$apiRes || $apiRes->isError()) {
            $result = $apiRes->getErrorInfo();
            $data['audit_desc'] = $result['msg'];
            $data['audit_state'] = $userService::AUDIT_FAIL;
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('data====>'.var_export($data,1));
            $this->userUpdateLogRepository->create($data);
            throw new ResourceException('数据更新失败: '.$audit_desc);
        } else {
            $result = $apiRes->getRspDatas();
            $data['audit_state'] = $userService::AUDIT_WAIT;
            $data['huifu_id'] = $result['data']['huifu_id'];
            app('log')->info('file:'.__FILE__.',line:'.__LINE__."\n");
            app('log')->info('data====>'.var_export($data,1));
            $this->userUpdateLogRepository->create($data);
        }

        return ['status' => true];
    }

    /**
     * 检查参数
     * @param  array   $params   
     * @param  boolean $isCreate
     */
    public function checkParams($params = [], $isCreate = false)
    {
        if (!empty($params['license_regions_id']) && !is_array($params['license_regions_id'])) {
           $params['license_regions_id'] = explode(',', $params['license_regions_id']);
        }
        // $params['license_regions_id'] = json_decode($params['license_regions_id'], 1);
        if (!is_array($params['license_regions_id']) or count($params['license_regions_id']) != 3) {
            throw new ResourceException('住所省市区数据格式错误');
        } else {
            $params['reg_prov_id'] = $params['license_regions_id'][0];
            $params['reg_area_id'] = $params['license_regions_id'][1];
            $params['reg_district_id'] = $params['license_regions_id'][2];
            unset($params['license_regions_id']);
        }
        // $params['license_regions'] = json_decode($params['license_regions'], 1);
        // if (is_array($params['license_regions']) && count($params['license_regions']) == 3) {
        //     $params['reg_prov'] = $params['license_regions'][0];
        //     $params['reg_area'] = $params['license_regions'][1];
        //     $params['reg_district'] = $params['license_regions'][2];
        //     unset($params['license_regions']);
        // }

        if ($params['reg_detail']) {
            $params['reg_detail'] = mb_substr($params['reg_detail'], 0, 60);
        }

        if ($params['legal_name']) {
            $params['legal_name'] = mb_substr($params['legal_name'], 0, 20);
        }
        $params['contact_name'] = $params['legal_name'];// 联系人姓名，使用法人姓名
        // 结算卡
        if ($params['card_type'] == 1) {
             // 对私
             $params['card_name'] = $params['legal_name'];// 持卡人姓名，使用法人姓名
        } else {
            // 对公
            $params['card_name'] = $params['reg_name'];// 户名，使用企业名称
        }

        if ($params['legal_cert_no']) {
            $preg_card = '/^[1-9]\d{5}(19|20)\d{2}[01]\d[0123]\d\d{3}[X\d]$/';
            if (!preg_match($preg_card, $params['legal_cert_no'])) {
                throw new ResourceException('法人身份证号码格式错误');
            }
        }
        if (!empty($params['card_regions_id']) && !is_array($params['card_regions_id'])) {
           $params['card_regions_id'] = explode(',', $params['card_regions_id']);
        }
        // $params['card_regions_id'] = json_decode($params['card_regions_id'], 1);
        if (!is_array($params['card_regions_id']) or count($params['card_regions_id']) != 2) {
            throw new ResourceException('银行卡开户地区数据格式错误');
        } else {
            $params['prov_id'] = $params['card_regions_id'][0];
            $params['area_id'] = $params['card_regions_id'][1];
            unset($params['card_regions_id']);
        }
        return $params;
    }

    /**
     * 处理前端的日期格式
     * @param string $str Y-m-d
     * @return string YYYYMMDD, 例如：20190909
     */
    private function _formatDate($str = '')
    {
        $str = str_replace('-', '', $str);
        return $str;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->userEntRepository->$method(...$parameters);
    }
}
