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

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayBankCodes;
use AdaPayBundle\Entities\AdapayMerchantEntry;
use AdaPayBundle\Entities\AdapayMerchantResident;
use AdaPayBundle\Entities\AdapayRegionsThird;
use AdaPayBundle\Entities\AdapaySmsLog;
use AdaPayBundle\Entities\AdapaySubmitLicense;
use AdaPayBundle\Entities\AdapayUploadLicense;
use AdaPayBundle\Entities\AdapayWxBusinessCategory;
use AdaPayBundle\Services\Request\Request;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Services\SmsManagerService;

class OpenAccountService
{
    private $Key = 'adapay_private_key';

    public $adapayMerchantEntryRepository;
    public $adapayAdapayBankCodesRepository;
    public $adapayMerchantResidentRepository;
    public $adapayWxBusinessCategoryRepository;
    public $adapayUploadLicenseRepository;
    public $adapaySubmitLicenseRepository;
    public $adapayRegionsThirdRepository;
    public $adapaySmsLog;

    public $feeType = [
        [
            'code' => '01',
            'name' => '标准费率线上',
        ],
        [
            'code' => '02',
            'name' => '标准费率线下',
        ]
    ];

    public $modelType = [
        [
            'code' => '1',
            'name' => '服务商模式',
        ]
    ];

    public $merType = [
        [
            'code' => '1',
            'name' => '政府机构',
        ],
        [
            'code' => '2',
            'name' => '国营企业',
        ],
        [
            'code' => '3',
            'name' => '私营企业',
        ],
        [
            'code' => '4',
            'name' => '外资企业',
        ],
        [
            'code' => '5',
            'name' => '个体工商户',
        ],
        [
            'code' => '7',
            'name' => '事业单位',
        ],
        [
            'code' => '8',
            'name' => '小微',
        ],
    ];

    public function __construct()
    {
        $this->adapayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
        $this->adapayAdapayBankCodesRepository = app('registry')->getManager('default')->getRepository(AdapayBankCodes::class);
        $this->adapayMerchantResidentRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantResident::class);
        $this->adapayWxBusinessCategoryRepository = app('registry')->getManager('default')->getRepository(AdapayWxBusinessCategory::class);
        $this->adapayUploadLicenseRepository = app('registry')->getManager('default')->getRepository(AdapayUploadLicense::class);
        $this->adapaySubmitLicenseRepository = app('registry')->getManager('default')->getRepository(AdapaySubmitLicense::class);
        $this->adapayRegionsThirdRepository = app('registry')->getManager('default')->getRepository(AdapayRegionsThird::class);
        $this->adapaySmsLog = app('registry')->getManager('default')->getRepository(AdapaySmsLog::class);
    }

    public function merchantEntryCreateService($companyId, $params)
    {
        $params['request_id'] = 'merchant_entry_'.$companyId.'_'.time();
        $params['company_id'] = $companyId;
        $params['legal_type'] = '0';//法人/负责人证件类型，0-身份证

//        $RSA = $this->generateKeyService();
//        $params['rsa_public_key'] = $RSA['public_key'];

        $params['notify_url'] = config('adapay.notify_url');
        $params['api_method'] = 'MerchantUser.create';

        //打接口到代理商后台  返回状态存入
        $request = new Request();
        $resData = $request->call($params);

//        if ($resData['data']['status'] == 'failed') {
//            throw new ResourceException($resData['data']['error_msg']);
//        }
        //返回结果存入本地
        unset($params['notify_url']);
        unset($params['api_method']);
        $params['status'] = 'pending';
        $info = $this->adapayMerchantEntryRepository->getInfo(['company_id' => $companyId]);
        if ($info) {
            $this->adapayMerchantEntryRepository->updateBy(['company_id' => $companyId], $params);
        } else {
            $this->adapayMerchantEntryRepository->create($params);

            $logParams = [
                'company_id' => $companyId,
            ];
            $merchantId = app('auth')->user()->get('operator_id');
            (new AdapayLogService())->logRecord($logParams, $merchantId, 'merchant_entry/create', 'merchant');
        }

//        $this->setPrivateKey($companyId, $RSA['private_key']);

        return ['status' => true];
    }

    public function merchantEntryInfoService($companyId)
    {
        $info = $this->adapayMerchantEntryRepository->getInfo(['company_id' => $companyId]);
        if ($info) {
            $bankInfo = $this->adapayAdapayBankCodesRepository->getInfo(['bank_code' => $info['bank_code']]);
            $info['bank_name'] = $bankInfo['bank_name'];

            $regionService = new RegionService();
            $regInfo = $regionService->getInfo(['area_code' => $info['prov_code']]);
            $info['prov_name'] = $regInfo['area_name'];

            $regInfo = $regionService->getInfo(['area_code' => $info['area_code']]);
            $info['area_name'] = $regInfo['area_name'];
        }


        return $info;
    }

    public function merchantResidentCreateService($companyId, $params)
    {
        $wxCategoryName = $params['wx_category'];
        $WxBusinessInfo = $this->adapayWxBusinessCategoryRepository->getInfo(['fee_type' => $params['fee_type'], 'merchant_type_name' => $wxCategoryName]);
        $params['wx_category'] = $WxBusinessInfo['business_category_id'];
        $merchantEntryInfo = $this->merchantEntryInfoService($companyId);
        $appIdList = json_decode($merchantEntryInfo['app_id_list'], true);
        $params['sub_api_key'] = $merchantEntryInfo['live_api_key'];
        $params['app_id'] = $appIdList[0]['app_id'];
        $params['request_id'] = 'merchant_resident_'.$companyId.'_'.time();
        $params['company_id'] = $companyId;
        $params['notify_url'] = config('adapay.notify_url');
        $params['api_method'] = 'MerchantConf.create';
        if (is_array($params['add_value_list'])) {
            $params['add_value_list'] = json_encode($params['add_value_list']);
        }
        //打接口到代理商后台
        $request = new Request();
        $resData = $request->call($params);
        if ($resData['data']['status'] == 'failed') {
            throw new ResourceException($resData['data']['error_msg']);
        }

        $params['status'] = 'pending';
        unset($params['notify_url']);

        $merchantResidentinfo = $this->merchantResidentInfoservice($companyId);
        if ($merchantResidentinfo) {
            $this->adapayMerchantResidentRepository->updateBy(['company_id' => $companyId], $params);
        } else {
            $this->adapayMerchantResidentRepository->create($params);
            $logParams = [
                'company_id' => $companyId,
            ];
            $merchantId = app('auth')->user()->get('operator_id');
            (new AdapayLogService())->logRecord($logParams, $merchantId, 'merchant_resident/create', 'merchant');
        }

        return ['status' => true];
    }

    public function merchantResidentInfoservice($companyId)
    {
        $info = $this->adapayMerchantResidentRepository->getInfo(['company_id' => $companyId]);

        if ($info) {
            if ($info['add_value_list']) {
                $addValueList = json_decode($info['add_value_list'], true);
                $info['authorizer_appid'] = $addValueList['wx_lite']['appid'];
            }
            $regThirdInfo = $this->adapayRegionsThirdRepository->getInfo(['area_code' => $info['province_code']]);
            $info['province_name'] = $regThirdInfo['area_name'];

            $regThirdInfo = $this->adapayRegionsThirdRepository->getInfo(['area_code' => $info['city_code']]);
            $info['city_name'] = $regThirdInfo['area_name'];

            $regThirdInfo = $this->adapayRegionsThirdRepository->getInfo(['area_code' => $info['district_code']]);
            $info['district_name'] = $regThirdInfo['area_name'];

            if ($info['wx_category']) {
                $wxBusinessService = new WxBusinessService();
                $wxBusinessInfo = $wxBusinessService->getInfo(['business_category_id' => $info['wx_category']]);
                $info['wx_category_name'] = $wxBusinessInfo['merchant_type_name'];
            }
        }

        return $info;
    }

    public function getBanksListsService($params, $page, $pageSize)
    {
        $filter = [];
        if ($params['bank_name']) {
            $filter['bank_name|contains'] = $params['bank_name'];
        }
        return $this->adapayAdapayBankCodesRepository->lists($filter, '*', $page, $pageSize);
    }

    public function setPrivateKey($companyId, $privateKey)
    {
        $redisKey = $this->Key. '_'.sha1($companyId);
        app('redis')->set($redisKey, $privateKey);
    }

    public function getPrivateKey($companyId)
    {
        $redisKey = $this->Key. '_'.sha1($companyId);
        $privateKey = app('redis')->get($redisKey);

        return $privateKey;
    }

    public function generateKeyService()
    {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 1024,           //字节数  512 1024 2048  4096 等 ,不能加引号，此处长度与加密的字符串长度有关系
            "private_key_type" => OPENSSL_KEYTYPE_RSA,   //加密类型
        );
        $res = openssl_pkey_new($config);

        //提取私钥
        openssl_pkey_export($res, $private_key);

        //生成公钥
        $public_key = openssl_pkey_get_details($res);

        $public_key = $public_key["key"];

        $private_key = preg_replace('/-----BEGIN PRIVATE KEY-----/', '', $private_key);
        $private_key = preg_replace('/-----END PRIVATE KEY-----/', '', $private_key);
        $private_key = preg_replace('/\n/', '', $private_key);

        $public_key = preg_replace('/-----BEGIN PUBLIC KEY-----/', '', $public_key);
        $public_key = preg_replace('/-----END PUBLIC KEY-----/', '', $public_key);
        $public_key = preg_replace('/\n/', '', $public_key);

        $result = [
            'private_key' => $private_key,
            'public_key' => $public_key,
        ];

        return $result;
    }


    public function getWxBusinessCatListService($params)
    {
        $filter = [];
        if ($params['fee_type']) {
            $filter['fee_type'] = $params['fee_type'];
        }

        if ($params['merchant_type_name']) {
            $filter['merchant_type_name|contains'] = $params['merchant_type_name'];
        }

        $wxBusinessList = $this->adapayWxBusinessCategoryRepository->lists($filter);

        $categorySet = [];

        foreach ($wxBusinessList['list'] as $value) {
            $categorySet[$value['merchant_type_name'] . '_' . $value['business_category_id']] = $value;
        }
        $wxBusinessList['list'] = array_values($categorySet);

        return $wxBusinessList;
    }

    public function uploadLicenseService($companyId, $params)
    {
        $merchantEntryInfo = $this->merchantEntryInfoService($companyId);
        $appIdList = json_decode($merchantEntryInfo['app_id_list'], true);
        $data = [
            'company_id' => $companyId,
            'subApiKey' => $merchantEntryInfo['live_api_key'],
            'file' => $params['file_url'],
            'fileType' => $params['file_type'],
            'api_method' => 'MerchantProfile.merProfilePicture'
        ];
        $params['company_id'] = $companyId;

        //调用代理商接口 返回pic_id存入
        $request = new Request();
        $resData = $request->call($data);
        if ($resData['data']['status'] == 'failed') {
            throw new ResourceException($resData['data']['error_msg']);
        }
        $temUrl = $params['file_url'];
        $params['sub_api_key'] = $merchantEntryInfo['live_api_key'];
        $params['pic_id'] = $resData['data']['pic_id'];
        $params['file_url'] = $params['file_dir'];
        unset($params['file_dir']);
        $rs = $this->adapayUploadLicenseRepository->create($params);
        $rs['tem_url'] = $temUrl;
        return $rs;
    }

    public function submitLicenseService($companyId, $params)
    {
        $merchantEntryInfo = $this->merchantEntryInfoService($companyId);
        if ($merchantEntryInfo['entry_mer_type'] == '1' && !($params['social_credit_code_url'] ?? [])) {
            throw new ResourceException('商户类型为企业商户，三证合一码必传');
        }
        $merchantResidentInfo = $this->merchantResidentInfoservice($companyId);
        if ($merchantResidentInfo['fee_type'] == '01' && !($params['business_add'] ?? [])) {
            throw new ResourceException('入驻的费率类型为线上，请传入商户的业务网址或者商城地址');
        }

        if ($merchantResidentInfo['fee_type'] == '02' && !($params['store_url'] ?? [])) {
            throw new ResourceException('入驻的费率类型为线下，门店必传');
        }

        if (($params['cert_back_image_url'] ?? '') || ($params['cert_front_image_url'] ?? '') || ($params['cert_id'] ?? '') || ($params['cert_name'] ?? '')) {
            if (!($params['cert_back_image_url'] ?? '') || !($params['cert_front_image_url'] ?? '') || !($params['cert_id'] ?? '') || !($params['cert_name'] ?? '')) {
                throw new ResourceException('股东信息必填');
            }
        }

        $licenseList = $this->adapayUploadLicenseRepository->lists(['company_id' => $companyId]);

        foreach ($licenseList['list'] as $value) {
            switch ($value['file_url']) {
                case $params['social_credit_code_url'] ?? '':
                    $socialCreditCodeId = $value['pic_id'];
                    break;
                case $params['legal_certId_front_url']:
                    $legalCertIdFrontId = $value['pic_id'];
                    break;
                case $params['legal_cert_id_back_url']:
                    $legalCertIdBackId = $value['pic_id'];
                    break;
                case $params['account_opening_permit_url']:
                    $accountOpeningPermitId = $value['pic_id'];
                    break;
                case $params['store_url'] ?? '':
                    $storeId = $value['pic_id'];
                    break;
                case $params['transaction_test_record_url'] ?? '':
                    $transactionTestRecordId = $value['pic_id'];
                    break;
                case $params['web_pic_url'] ?? '':
                    $webPicId = $value['pic_id'];
                    break;
                case $params['lease_contract_url'] ?? '':
                    $leaseContractId = $value['pic_id'];
                    break;
                case $params['settle_account_certificate_url'] ?? '':
                    $settleAccountCertificateId = $value['pic_id'];
                    break;
                case $params['buss_support_materials_url'] ?? '':
                    $bussSupportMaterialsId = $value['id'];
                    break;
                case $params['icp_registration_license_url'] ?? '':
                    $icpRegistrationLicenseId = $value['id'];
                    break;
                case $params['industry_qualify_doc_license_url'] ?? '':
                    $industryQualifyDocLicenseId = $value['pic_id'];
                    break;
                case $params['cert_back_image_url'] ?? '':
                    $certBackImageId = $value['pic_id'];
                    break;
                case $params['cert_front_image_url'] ?? '':
                    $certFrontImageId = $value['pic_id'];
                    break;
//                default :
//                    throw new ResourceException('没有该图片类型');
            }
        }
        $data = [
            'subApiKey' => $merchantEntryInfo['live_api_key'],
            'socialCreditCodeId' => $socialCreditCodeId ?? '',
            'legalCertIdFrontId' => $legalCertIdFrontId,
            'legalCertIdBackId' => $legalCertIdBackId,
            'accountOpeningPermitId' => $accountOpeningPermitId,
            'businessAdd' => $params['business_add'] ?? '',
            'storeId' => $storeId ?? '',
            'transactionTestRecordId' => $transactionTestRecordId ?? '',
            'webPicId' => $webPicId ?? '',
            'leaseContractId' => $leaseContractId ?? '',
            'settleAccountCertificateId' => $settleAccountCertificateId ?? '',
            'bussSupportMaterialsId' => $bussSupportMaterialsId ?? '',
            'icpRegistrationLicenseId' => $icpRegistrationLicenseId ?? '',
            'industryQualifyDocType' => $params['industry_qualify_doc_type'] ?? '',
            'industryQualifyDocLicenseId' => $industryQualifyDocLicenseId ?? '',
//            'shareholderInfoList' => [
//                [
//                  'certBackImageId' => $certBackImageId ?? '',
//                  'certFrontImageId' => $certFrontImageId ?? '',
//                  'certId' => $params['cert_id'] ?? '',
//                  'certName' => $params['cert_name'] ?? '',
//                ]
//            ]
        ];
        if ($params['cert_id'] ?? '') {
            $data['shareholderInfoList'] = [
                [
                    'certBackImageId' => $certBackImageId ?? '',
                    'certFrontImageId' => $certFrontImageId ?? '',
                    'certId' => $params['cert_id'] ?? '',
                    'certName' => $params['cert_name'] ?? '',
                ]
            ];
        }
        $data = array_filter($data);
        $data['company_id'] = $companyId;
        $data['api_method'] = 'MerchantProfile.merProfileForAudit';

        //提交到代理商
        $request = new Request();
        $resData = $request->call($data);
        if ($resData['data']['status'] == 'failed') {
            throw new ResourceException($resData['data']['error_msg']);
        }

        $addParams = [
            'company_id' => $companyId,
            'sub_api_key' => $merchantEntryInfo['live_api_key'],
            'social_credit_code_id' => $socialCreditCodeId ?? '',
            'legal_certId_front_id' => $legalCertIdFrontId,
            'legal_cert_id_back_id' => $legalCertIdBackId,
            'account_opening_permit_id' => $accountOpeningPermitId,
            'business_add' => $params['business_add'] ?? '',
            'store_id' => $storeId ?? '',
            'transaction_test_record_id' => $transactionTestRecordId ?? '',
            'web_pic_id' => $webPicId ?? '',
            'lease_contract_id' => $leaseContractId ?? '',
            'settle_account_certificate_id' => $settleAccountCertificateId ?? '',
            'buss_support_materials_id' => $bussSupportMaterialsId ?? '',
            'icp_registration_license_id' => $icpRegistrationLicenseId ?? '',
            'industry_qualify_doc_type' => $params['industry_qualify_doc_type'] ?? '',
            'industry_qualify_doc_license_id' => $industryQualifyDocLicenseId ?? '',
//            'shareholder_info_list' => json_encode([
//                [
//                    'certBackImageId' => $certBackImageId ?? '',
//                    'certFrontImageId' => $certFrontImageId ?? '',
//                    'certId' => $params['cert_id'] ?? '',
//                    'certName' => $params['cert_name'] ?? '',
//                ]
//            ]),
            'audit_status' => 'I',
            'is_sms' => $params['is_sms']
        ];
        if ($params['cert_id'] ?? '') {
            $addParams['shareholder_info_list'] = json_encode([
                [
                    'certBackImageId' => $certBackImageId ?? '',
                    'certFrontImageId' => $certFrontImageId ?? '',
                    'certId' => $params['cert_id'] ?? '',
                    'certName' => $params['cert_name'] ?? '',
                ]
            ]);
        }
        $adapaySubmitLicenseInfo = $this->submitLicenseInfoService($companyId);
        if ($adapaySubmitLicenseInfo) {
            $this->adapaySubmitLicenseRepository->updateBy(['company_id' => $companyId], $addParams);
        } else {
            $this->adapaySubmitLicenseRepository->create($addParams);
            $logParams = [
                'company_id' => $companyId,
            ];
            $merchantId = app('auth')->user()->get('operator_id');
            (new AdapayLogService())->logRecord($logParams, $merchantId, 'license_submit/create', 'merchant');
        }

        return ['status' => true];
    }

    /**
     * @param $companyId
     * @param bool $isSimple 只返回简单数据。不处理证照图片信息
     * @return array
     */
    public function openAccountStepService($companyId, $isSimple=false)
    {
        $step = 1;
        $adapayMerchantEntryInfo = $this->merchantEntryInfoService($companyId);
        $adapayMerchantResidentInfo = $this->merchantResidentInfoservice($companyId);
        if ($isSimple) {
            $adapaySubmitLicenseInfo = $this->getSubmitLicenseInfo($companyId);
        } else {
            $adapaySubmitLicenseInfo = $this->submitLicenseInfoService($companyId);
        }

        $rs = [
            'MerchantEntry' => $adapayMerchantEntryInfo,
            'MerchantResident' => $adapayMerchantResidentInfo,
            'SubmitLicense' => $adapaySubmitLicenseInfo,
        ];
        if ($adapayMerchantEntryInfo && $adapayMerchantEntryInfo['status'] == 'succeeded') {
            $step++;
            if ($adapayMerchantResidentInfo && $adapayMerchantResidentInfo['status'] == 'succeeded') {
                $step++;
                if ($adapaySubmitLicenseInfo && $adapaySubmitLicenseInfo['audit_status'] == 'P') {
                    $step++;
                }
            }
        }

        if ($step == 3) {
            $expireTime = $adapayMerchantResidentInfo['update_time'] + 60 * 60 * 24 * 5;
            if ($expireTime < time() && ($adapaySubmitLicenseInfo['audit_status'] ?? 'W') != 'I') {//W:待补充状态  说明还没提交证照信息
                $step = 1;
                $this->adapayMerchantEntryRepository->updateBy(['company_id' => $companyId], ['status' => 'failed', 'error_msg' => '入驻成功后5个工作日内没有提交证照']);
                $this->adapayMerchantResidentRepository->updateBy(['company_id' => $companyId], ['status' => 'failed']);
            }
        }

        return ['step' => $step, 'info' => $rs];
    }

    //获取商户开户提交的证照信息
    public function getSubmitLicenseInfo($companyId)
    {
        $submitInfo = $this->adapaySubmitLicenseRepository->getInfo(['company_id' => $companyId]);
        return $submitInfo;
    }

    public function submitLicenseInfoService($companyId)
    {
        $submitInfo = $this->adapaySubmitLicenseRepository->getInfo(['company_id' => $companyId]);
        $filesystem = app('filesystem')->disk('import-file');
        $fileDir = [];
        foreach ($submitInfo as $key => $value) {
            if ($key == 'id' || $key == 'company_id' || $key == 'sub_api_key' || $key == 'business_add' || $key == 'industry_qualify_doc_type' || $key == 'audit_status' || $key == 'audit_desc' || $key == 'create_time' || $key == 'update_time') {
                continue;
            }

            if ($key == 'shareholder_info_list') {
                $shareholderArr = json_decode($value, true);

                if ($shareholderArr) {
                    $submitInfo['cert_id'] = $shareholderArr[0]['certId'];
                    $submitInfo['cert_name'] = $shareholderArr[0]['certName'];
                    if ($shareholderArr[0]['certBackImageId']) {
                        $certBack = $this->adapayUploadLicenseRepository->getInfo(['company_id' => $companyId, 'pic_id' => $shareholderArr[0]['certBackImageId']]);
                        $submitInfo['cert_back_image_url'] = $filesystem->privateDownloadUrl($certBack['file_url']);
                        $fileDir['cert_back_image_url'] = $certBack['file_url'];
                    }
                    if ($shareholderArr[0]['certBackImageId']) {
                        $certFront = $this->adapayUploadLicenseRepository->getInfo(['company_id' => $companyId, 'pic_id' => $shareholderArr[0]['certBackImageId']]);
                        $submitInfo['cert_front_image_url'] = $filesystem->privateDownloadUrl($certFront['file_url']);
                        $fileDir['cert_front_image_url'] = $certFront['file_url'];
                    }
                }
                continue;
            }

            if ($value) {
                $uploadInfo = $this->adapayUploadLicenseRepository->getInfo(['company_id' => $companyId, 'pic_id' => $value]);
                if ($uploadInfo) {
                    switch ($uploadInfo['file_type']) {
                        case '01':
                            $submitInfo['social_credit_code_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['social_credit_code_url'] = $uploadInfo['file_url'];
                            break;
                        case '02':
                            $submitInfo['legal_certId_front_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['legal_certId_front_url'] = $uploadInfo['file_url'];
                            break;
                        case '03':
                            $submitInfo['legal_cert_id_back_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['legal_cert_id_back_url'] = $uploadInfo['file_url'];
                            break;
                        case '04':
                            $submitInfo['store_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['store_url'] = $uploadInfo['file_url'];
                            break;
                        case '05':
                            $submitInfo['account_opening_permit_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['account_opening_permit_url'] = $uploadInfo['file_url'];
                            break;
                        case '08':
                            $submitInfo['settle_account_certificate_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['settle_account_certificate_url'] = $uploadInfo['file_url'];
                            break;
                        case '09':
                            $submitInfo['web_pic_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['web_pic_url'] = $uploadInfo['file_url'];
                            break;
                        case '10':
                            $submitInfo['industry_qualify_doc_license_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['industry_qualify_doc_license_url'] = $uploadInfo['file_url'];
                            break;
                        case '11':
                            $submitInfo['icp_registration_license_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['icp_registration_license_url'] = $uploadInfo['file_url'];
                            break;
                        case '12':
                            $submitInfo['lease_contract_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['lease_contract_url'] = $uploadInfo['file_url'];
                            break;
                        case '13':
                            $submitInfo['transaction_test_record_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['transaction_test_record_url'] = $uploadInfo['file_url'];
                            break;
                        case '14':
                            $submitInfo['buss_support_materials_url'] = $filesystem->privateDownloadUrl($uploadInfo['file_url']);
                            $fileDir['buss_support_materials_url'] = $uploadInfo['file_url'];
                            break;
                    }
                }
            }
        }
        if ($fileDir) {
            $submitInfo['file_dir'] = $fileDir;
        }
        return $submitInfo;
    }


    public function entryCallback($data)
    {
        if ($data['status'] == 'succeeded') {
            $params = [
                'status' => $data['status'],
                'test_api_key' => $data['test_api_key'],
                'live_api_key' => $data['live_api_key'],
                'app_id_list' => json_encode($data['app_id_list']),
                'sign_view_url' => $data['sign_view_url'],
                'login_pwd' => $data['login_pwd'],
                'error_msg' => '',
            ];
        } else {
            $params = [
                'status' => $data['status'],
                'error_msg' => $data['error_msg'],
            ];
        }

        $rs = $this->adapayMerchantEntryRepository->updateOneBy(['request_id' => $data['request_id']], $params);
        if (isset($rs['is_sms']) && $rs['is_sms']) {
            try {
                $data = ['mer_name' => $rs['mer_name'], 'step' => '(第一步)'];
                $smsManagerService = new SmsManagerService($rs['company_id']);
                $smsManagerService->send($rs['usr_phone'], $rs['company_id'], 'admin_account_approved', $data);
            } catch (\Exception $e) {}
        }
    }

    public function residentCallback($data)
    {
        if ($data['status'] == 'succeeded') {
            $params = [
                'status' => $data['status'],
                'wx_stat' => $data['wx_stat']['stat'],
                'alipay_stat' => $data['alipay_stat']['stat'] ?? '',
                'wx_stat_msg' => '',
                'alipay_stat_msg' => '',
            ];
        } else {
            $params = [
                'status' => $data['status'],
                'wx_stat' => $data['wx_stat']['stat'],
                'alipay_stat' => $data['alipay_stat']['stat'] ?? '',
                'wx_stat_msg' => $data['wx_stat']['message'],
                'alipay_stat_msg' => $data['alipay_stat']['message'] ?? '',
            ];
        }

        $this->adapayMerchantResidentRepository->updateOneBy(['request_id' => $data['request_id']], $params);
    }

    public function getSubmitLicenseStatus()
    {
        $lists = $this->adapaySubmitLicenseRepository->getLists(['audit_status' => 'I']);
        if (!$lists) {
            return;
        }
        $request = new Request();
        foreach ($lists as $val) {
            $params = [
                'company_id' => $val['company_id'],
                'subApiKey' => $val['sub_api_key'],
                'api_method' => 'MerchantProfile.merProfileAuditStatus',
            ];

            $resData = $request->call($params);
            $data = [
                'audit_status' => $resData['data']['audit_status'],
                'audit_desc' => $resData['data']['audit_desc'] ?? '',
            ];
            $rs = $this->adapaySubmitLicenseRepository->updateOneBy(['company_id' => $val['company_id']], $data);
            if ($resData['data']['audit_status'] != 'I' && isset($rs['is_sms']) && $rs['is_sms']) {
                $entryInfo = $this->adapayMerchantEntryRepository->getInfo(['company_id' => $rs['company_id']]);
                try {
                    $data = ['mer_name' => $entryInfo['mer_name'], 'step' => '(第三步)'];
                    $smsManagerService = new SmsManagerService($rs['company_id']);
                    $smsManagerService->send($entryInfo['usr_phone'], $rs['company_id'], 'admin_account_approved', $data);
                } catch (\Exception $e) {}
            }
        }
    }
    public function otherCatService($merchantTypeName)
    {
        $res = [
            'fee_type' => $this->feeType,
            'model_type' => $this->modelType,
//            'mer_type' => $this->merType,
        ];
        $res['mer_type'] = [];
        foreach ($this->merType as $value) {
            if ($merchantTypeName == '企业') {
                if ($value['code'] == '2' || $value['code'] == '3') {
                    array_push($res['mer_type'], $value);
                }
            }

            if ($merchantTypeName == '个体户') {
                if ($value['code'] == '5') {
                    array_push($res['mer_type'], $value);
                }
            }

            if ($merchantTypeName == '政府事业单位') {
                if ($value['code'] == '1' || $value['code'] == '7') {
                    array_push($res['mer_type'], $value);
                }
            }

            if ($merchantTypeName == '小微商户') {
                if ($value['code'] == '8') {
                    array_push($res['mer_type'], $value);
                }
            }

            if ($merchantTypeName == '其他组织') {
                if ($value['code'] == '4') {
                    array_push($res['mer_type'], $value);
                }
            }
        }



        return $res;
    }
    //判断当前是否已经开户
    public function isOpenAccount($companyId)
    {
        $auth = app('auth')->user()->get();
        $memberService = new MemberService();
        $operator = $memberService->getOperator();
        $step = $this->openAccountStepService($companyId, true);
        if ($operator['operator_type'] == 'admin') {
            if ($step['step'] == 4) {
                return 'SUCCESS';
            }
        } elseif ($operator['operator_type'] == 'dealer' || $operator['operator_type'] == 'distributor') {
            if ($step['step'] == 4) {
                $filter = [
                    'company_id' => $companyId,
                    'operator_type' => $operator['operator_type'],
                    'operator_id' => $operator['operator_id'],
                    'audit_state' => 'E',
                ];
                $member = (new MemberService())->getInfo($filter, ['id' => 'DESC']);
                if ($member) {
                    return 'SUCCESS';
                }
            } else {
                return 'ADMIN_NO_ACCOUNT';
            }
        }
        return 'FAIL';
    }
    public function getAppIdByCompanyId($companyId)
    {
        $entryInfo = $this->merchantEntryInfoService($companyId);
        $appIdList = json_decode($entryInfo['app_id_list'], true);

        $appId = $appIdList[0]['app_id'];

        return $appId;
    }

    /**
     * 开户信息-数据脱敏
     * @param  [type] $result        [description]
     * @param  [type] $datapassBlock [description]
     * @return [type]                [description]
     */
    public function openAccountStepDataMasking($result, $datapassBlock)
    {
        if (!$datapassBlock) {
            return $result;
        }

        if (!$result['info']['MerchantEntry']) {
            return $result;
        }

        $result['info']['MerchantEntry']['cont_name'] = data_masking('truename', (string) $result['info']['MerchantEntry']['cont_name']);
        $result['info']['MerchantEntry']['cust_tel'] = data_masking('mobile', (string) $result['info']['MerchantEntry']['cust_tel']);

        $result['info']['MerchantEntry']['legal_name'] = data_masking('truename', (string) $result['info']['MerchantEntry']['legal_name']);
        $result['info']['MerchantEntry']['legal_mp'] = data_masking('mobile', (string) $result['info']['MerchantEntry']['legal_mp']);
        $result['info']['MerchantEntry']['cont_phone'] = data_masking('mobile', (string) $result['info']['MerchantEntry']['cont_phone']);
        $result['info']['MerchantEntry']['legal_idno'] = data_masking('idcard', (string) $result['info']['MerchantEntry']['legal_idno']);
        $result['info']['MerchantEntry']['usr_phone'] = data_masking('mobile', (string) $result['info']['MerchantEntry']['usr_phone']);

        $result['info']['MerchantEntry']['card_id_mask'] = data_masking('bankcard', (string) $result['info']['MerchantEntry']['card_id_mask']);
        $result['info']['MerchantEntry']['card_name'] = data_masking('truename', (string) $result['info']['MerchantEntry']['card_name']);
        if (isset($result['info']['MerchantEntry']['cert_name'])) {
            $result['info']['MerchantEntry']['cert_name'] = data_masking('truename', (string) $result['info']['MerchantEntry']['cert_name']);
        }
        if (isset($result['info']['MerchantEntry']['cert_id'])) {
            $result['info']['MerchantEntry']['cert_id'] = data_masking('idcard', (string) $result['info']['MerchantEntry']['cert_id']);
        }
        if (isset($result['info']['SubmitLicense']['legal_certId_front_url'])) {
            $result['info']['SubmitLicense']['legal_certId_front_url'] = data_masking('image', (string) $result['info']['SubmitLicense']['legal_certId_front_url']);
        }
        if (isset($result['info']['SubmitLicense']['legal_cert_id_back_url'])) {
            $result['info']['SubmitLicense']['legal_cert_id_back_url'] = data_masking('image', (string) $result['info']['SubmitLicense']['legal_cert_id_back_url']);
        }
        if (isset($result['info']['SubmitLicense']['cert_front_image_url'])) {
            $result['info']['SubmitLicense']['cert_front_image_url'] = data_masking('image', (string) $result['info']['SubmitLicense']['cert_front_image_url']);
        }
        if (isset($result['info']['SubmitLicense']['cert_back_image_url'])) {
            $result['info']['SubmitLicense']['cert_back_image_url'] = data_masking('image', (string) $result['info']['SubmitLicense']['cert_back_image_url']);
        }
        if (isset($result['info']['SubmitLicense']['account_opening_permit_url'])) {
            $result['info']['SubmitLicense']['account_opening_permit_url'] = data_masking('image', (string) $result['info']['SubmitLicense']['account_opening_permit_url']);
        }
        return $result;
    }
}
