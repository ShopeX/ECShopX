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

use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Entities\HfpayBankCard;
use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Events\HfPayDistributorWithdrawEvent;

class HfpayEnterapplyService
{
    /** @var entityRepository */
    public $entityRepository;
    public $bankRepository;
    public $cashRecordRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
        $this->bankRepository = app('registry')->getManager('default')->getRepository(HfpayBankCard::class);
        $this->cashRecordRepository = app('registry')->getManager('default')->getRepository(HfpayCashRecord::class);
    }

    /**
     * 保存入驻信息
     */
    public function save($params)
    {
        $params = $this->check($params);
        //企业入驻需存储股东信息
        if ($params['apply_type'] == '1') {
            //股东信息数据转换
            $controlling_shareholder[] = [
                'custName' => $params['controlling_shareholder_cust_name'],
                'idCardType' => $params['controlling_shareholder_id_card_type'],
                'idCard' => $params['controlling_shareholder_id_card']
            ];
            $params['controlling_shareholder'] = json_encode($controlling_shareholder, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($params['hfpay_enterapply_id'])) {
            $filter = [
                'hfpay_enterapply_id' => $params['hfpay_enterapply_id'],
            ];
            $data = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $data = $this->entityRepository->create($params);
        }

        return $data;
    }

    /**
     * 创建初始化数据
     */
    public function createInitApply($company_id, $distributor_id)
    {
        $initData = [
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'apply_type' => 1, //默认开户类型为企业
            'status' => '1'
        ];
        $data = $this->entityRepository->create($initData);
        return $data;
    }

    public function updateApply($filter, $data)
    {
        $data = $this->entityRepository->updateOneBy($filter, $data);
        return $data;
    }

    /**
     * 获取入驻信息
     */
    public function getEnterapply($filter)
    {
        // 0x456353686f7058
        $result = $this->entityRepository->getInfo($filter);
        if (!empty($result)) {
            //图片数据转换
            $filesystem = app('filesystem')->disk('import-image');
            $result['business_code_img_full_url'] = !empty($result['business_code_img_local']) ? $filesystem->url($result['business_code_img_local']) : '';
            $result['institution_code_img_full_url'] = !empty($result['institution_code_img_local']) ? $filesystem->url($result['institution_code_img_local']) : '';
            $result['tax_code_img_full_url'] = !empty($result['tax_code_img_local']) ? $filesystem->url($result['tax_code_img_local']) : '';
            $result['social_credit_code_img_full_url'] = !empty($result['social_credit_code_img_local']) ? $filesystem->url($result['social_credit_code_img_local']) : '';
            $result['legal_card_imgz_full_url'] = !empty($result['legal_card_imgz_local']) ? $filesystem->url($result['legal_card_imgz_local']) : '';
            $result['legal_card_imgf_full_url'] = !empty($result['legal_card_imgf_local']) ? $filesystem->url($result['legal_card_imgf_local']) : '';
            $result['bank_acct_img_full_url'] = !empty($result['bank_acct_img_local']) ? $filesystem->url($result['bank_acct_img_local']) : '';
            $result['bank_acct_num_imgz_full_url'] = !empty($result['bank_acct_num_imgz_local']) ? $filesystem->url($result['bank_acct_num_imgz_local']) : '';
            $result['bank_acct_num_imgf_full_url'] = !empty($result['bank_acct_num_imgf_local']) ? $filesystem->url($result['bank_acct_num_imgf_local']) : '';
            //股东信息数据转换
            $result['controlling_shareholder_cust_name'] = '';
            $result['controlling_shareholder_id_card_type'] = '';
            $result['controlling_shareholder_id_card'] = '';
            if (!empty($result['controlling_shareholder'])) {
                $controlling_shareholder = json_decode($result['controlling_shareholder'], true);
                $controlling_shareholder = $controlling_shareholder[0];
                $result['controlling_shareholder_cust_name'] = $controlling_shareholder['custName'];
                $result['controlling_shareholder_id_card_type'] = $controlling_shareholder['idCardType'];
                $result['controlling_shareholder_id_card'] = $controlling_shareholder['idCard'];
            }
        }

        return $result;
    }

    /**
     * 检查配置数据
     */
    public function check($params)
    {
        switch ($params['apply_type']) {
            case '1':
                $params = $this->checkCorp($params);
                break;
            case '2':
                $params = $this->checkSolo($params);
                break;
            case '3':
                $params = $this->checkUser($params);
                break;
            default:
                throw new ResourceException("未知的入驻类型");
                break;
        }
        return $params;
    }

    /**
     * 组合判断企业入驻数据
     */
    public function checkCorp($params)
    {
        $rules = [
            'corp_license_type' => ['required|in:1,2', '企业证照类型必填|企业证照类型不正确'],
            'corp_name' => ['required', '企业名称必填'],
            'business_code' => ['required_if:business_type,1', '营业执照注册号必填'],
            'institution_code' => ['required_if:business_type,1', '组织机构代码必填'],
            'tax_code' => ['required_if:business_type,1', '税务登记证号必填'],
            'social_credit_code' => ['required_if:business_type,2', '统一社会信用代码必填'],
            'license_start_date' => ['required', '证照起始日期必填'],
            'license_end_date' => ['required', '证照结束日期必填'],
            'controlling_shareholder_cust_name' => ['required', '控股股东姓名必填'],
            'controlling_shareholder_id_card_type' => ['required', '控股股东证件类型必填'],
            'controlling_shareholder_id_card' => ['required', '控股股东证件号必填'],
            'legal_name' => ['required', '法人姓名必填'],
            'legal_id_card_type' => ['required', '法人证件类型必填'],
            'legal_id_card' => ['required', '法人证件号码必填'],
            'legal_cert_start_date' => ['required', '法人证件起始日期必填'],
            'legal_cert_end_date' => ['required', '法人证件起始日期必填'],
            'legal_mobile' => ['required', '法人手机号码必填'],
            'contact_name' => ['required', '企业联系人姓名必填'],
            'contact_mobile' => ['required', '联系人手机号必填'],
            'contact_email' => ['required', '联系人邮箱必填'],
            'bank_acct_name' => ['required', '开户银行账户名必填'],
            'bank_id' => ['required', '开户银行必填'],
            'bank_acct_num' => ['required', '开户银行账号必填'],
            'bank_prov' => ['required', '开户银行省份必填'],
            'bank_area' => ['required', '开户银行地区必填'],
            'bank_branch' => ['required', '企业开户银行的支行名称'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return $params;
    }

    /**
     * 组合判断个体户入驻数据
     */
    public function checkSolo($params)
    {
        $rules = [
            'solo_name' => ['required', '个体户名称必填'],
            'business_code' => ['required', '营业执照注册号必填'],
            'license_start_date' => ['required', '证照起始日期必填'],
            'license_end_date' => ['required', '证照结束日期必填'],
            'solo_business_address' => ['required', '个体户经营地址必填'],
            'solo_reg_address' => ['required', '个体户注册地址必填'],
            'solo_fixed_telephone' => ['required', '个体户固定电话必填'],
            'business_scope' => ['required', '经营范围必填'],
            'legal_name' => ['required', '法人姓名必填'],
            'legal_id_card_type' => ['required', '法人证件类型必填'],
            'legal_id_card' => ['required', '法人证件号码必填'],
            'legal_cert_start_date' => ['required', '法人证件起始日期必填'],
            'legal_cert_end_date' => ['required', '法人证件起始日期必填'],
            'legal_mobile' => ['required', '法人手机号码必填'],
            'contact_name' => ['required', '企业联系人姓名必填'],
            'contact_mobile' => ['required', '联系人手机号必填'],
            'contact_email' => ['required', '联系人邮箱必填'],
            'occupation' => ['required', '职业必填'],
            'bank_acct_num' => ['required', '开户银行账号必填'],
//            'contact_cert_num'      => ['required', '联系人证件号必填'],
            // 'open_license_no'       => ['required', '开户许可证核准号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return $params;
    }

    /**
     * 组合判断个人入驻数据
     */
    public function checkUser($params)
    {
        $rules = [
            'user_name' => ['required', '用户姓名必填'],
            'id_card_type' => ['required', '证件类型必填'],
            'id_card' => ['required', '身份证号必填'],
            'user_mobile' => ['required', '手机号必填'],
            'bank_acct_num' => ['required', '银行卡号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return $params;
    }

    /**
     * 获取店铺进件信息列表
     */
    public function getApplyList($filter, $page = 1, $pageSize = 10, $orderBy = ['hfpay_enterapply_id' => 'DESC'])
    {
        $result = $this->entityRepository->getJoinLists($filter, $page, $pageSize, $orderBy);
        $data = [];
        if (!empty($result['list'])) {
            foreach ($result['list'] as $k => $v) {
                $status_msg = '';
                switch ($v['status']) {
                    case 1:
                        $status_msg = '未提交开户信息';
                        break;
                    case 2:
                        $status_msg = '审核中';
                        break;
                    case 3:
                        $status_msg = '审核成功';
                        break;
                    case 4:
                        $status_msg = '审核失败';
                        break;
                }
                $v['status_msg'] = $status_msg;
                $data[] = $v;
            }
        }

        $result['list'] = $data;

        return $result;
    }

    /**
     * 每日店铺提现
     */
    public function distributorWithdraw()
    {
        $filter = [
            'status' => 3,
            'apply_type' => ['1', '2', '3']
        ];
        $count = $this->entityRepository->count($filter);
        if ($count > 500) {
            $page_size = 50;
            $page_count = round($count / $page_size);
            for ($page = 1; $page <= $page_count; $page++) {
                $data = $this->entityRepository->lists($filter, '*', $page, $page_size);
                $this->addCash($data['list']);
            }
        } else {
            $data = $this->entityRepository->lists($filter, '*', 1, 500);
            $this->addCash($data['list']);
        }
    }

    /**
     * 生成提现记录
     */
    private function addCash($data)
    {
        if (empty($data)) {
            return true;
        }
        $baseService = new HfBaseService();

        foreach ($data as $key => $val) {
            $company_id = $val['company_id'];
            $distributor_id = $val['distributor_id'];
            $user_cust_id = $val['user_cust_id'];
            //判断是否有绑定银行卡
            $filter = [
                'distributor_id' => $distributor_id,
                'is_cash' => 1
            ];
            $brank = $this->bankRepository->getInfo($filter);
            if (empty($brank)) {
                continue;
            }
            $bind_card_id = $brank['bind_card_id'];
            //查询汇付账户余额
            $params = [
                'user_cust_id' => $val['user_cust_id'],
                'acct_id' => $val['acct_id']
            ];
            $service = new AcouService($company_id);
            $result = $service->qry001($params);
            if ($result['resp_code'] != 'C00000') {
                continue;
            }
            $balance = bcmul($result['balance'], 100); //余额单位元转换为分
            //判断余额是否满足最低提现金额
            $_filter = [
                'company_id' => $company_id
            ];
            $hfpay_withdraw_set_service = new HfpayWithdrawSetService();
            $withdraw_set = $hfpay_withdraw_set_service->getWithdrawSet($_filter);
            if (empty($withdraw_set)) {
                $withdraw_set['distributor_money'] = 0;
            } else {
                if ($withdraw_set['withdraw_method'] == 2) {
                    continue;
                }
            }
            $distributor_withdraw_money = bcmul($withdraw_set['distributor_money'], 100); //元转分
            if ($balance <= 0 || $balance - $distributor_withdraw_money < 0) {
                continue;
            }
            //生成提现记录
            $params = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'order_id' => $baseService->getOrderId(),
                'user_cust_id' => $user_cust_id,
                'trans_amt' => $balance,
                'cash_type' => 'T1',
                'bind_card_id' => $bind_card_id
            ];

            $reslut = $this->cashRecordRepository->create($params);
            //提现处理事件
            $eventData = [
                'hfpay_cash_record_id' => $reslut['hfpay_cash_record_id'],
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'trans_amt' => $balance
            ];
            event(new HfPayDistributorWithdrawEvent($eventData));
        }
    }
}
