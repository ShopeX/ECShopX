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

namespace WechatBundle\Services\Wxapp;

use WechatBundle\Entities\Weapp;
use WechatBundle\Entities\WechatAuth;
use GoodsBundle\Services\ItemsService;
use WechatBundle\Entities\WeappSetting;
use WechatBundle\Services\WeappService;
use WechatBundle\Entities\WeappTemplate;
use WechatBundle\Services\OpenUserPlatform;
use CompanysBundle\Services\CommonLangModService;

use MerchantBundle\Services\MerchantSettingService;
use SuperAdminBundle\Services\WxappTemplateService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TemplateService
{
    public $templateCheckList = [
        'yykmembership' => 'WechatBundle\Services\Wxapp\TemplateCheck\Membership',
        'yykmendian' => 'WechatBundle\Services\Wxapp\TemplateCheck\YiPuMendian',
        'yykcutdown' => 'WechatBundle\Services\Wxapp\TemplateCheck\Cutdown',
    ];

    //获取模版列表
    public function getTemplateList($authorizerAppId)
    {
        //获取提供的模版列表
        $wxappTemplateService = new WxappTemplateService();
        $templateList = $wxappTemplateService->getDataList();
        if (!$templateList) {
            $templateList = config('wxa');
        }

        $list = [];
        foreach ($templateList as $templateName => $row) {
            //判断当前公众号是否有权限
            $checkService = isset($this->templateCheckList[$templateName]) ? $this->templateCheckList[$templateName] : false;
            if ($checkService) {
                $templateCheckService = new $checkService();
                if (!$templateCheckService->checkPermission($authorizerAppId)) {
                    continue;
                }
            }

            $info = $row;
            $info['template_name'] = $templateName;
            $info['is_open'] = true;
            $list[] = $info;
        }

        return $list;
    }

    /**
     * 获取模版列表
     *
     * @param int $companyId
     * @return array
     */
    public function getTemplateWeappList(int $companyId): array
    {
        // 获取现有模版数据
        $templateList = (new WxappTemplateService())->getDataList();
        if (!$templateList) {
            $templateList = config('wxa');
        }

        $authorizerList = (new WeappService())->getWxaList($companyId);

        $indexAuthorizer = [];
        foreach ($authorizerList as $value) {
            $keyTemplateName = $value['weappTemplate']['key_name'] ?? '';
            $indexAuthorizer[$keyTemplateName] = $value;
        }

        $result = [];
        foreach ($templateList as $templateName => $value) {
            $result[] = [
                'template_id' => $value['template_id'],
                'domain' => $value['domain'],
                'name' => $value['name'],
                'key_name' => $value['key_name'],
                'authorizer' => $indexAuthorizer[$templateName] ?? (object)[]
            ];
        }

        return $result;
    }

    /**
     * 获取模版详情
     *
     * @param $companyId
     * @param $templateId
     * @return mixed
     */
    public function getTemplateWeappDetail($companyId, $templateId)
    {
        $filter = [
            'is_disabled' => false,
            'template_id' => $templateId,
        ];
        $templateDetail = (new WxappTemplateService())->getInfo($filter);

        $wxaAppData = app('registry')->getManager('default')->getRepository(Weapp::class)->getWeappInfoByTemplateName($companyId, $templateDetail['key_name']);
        if (!$wxaAppData) {
            $templateDetail['authorizer'] = (object)[];
            return $templateDetail;
        }

        $detail = app('registry')->getManager('default')->getRepository(WechatAuth::class)->checkWxaAppId($companyId, $wxaAppData['authorizer_appid']);
        $templateDetail['authorizer'] = [
            'authorizer_appid' => $detail->getAuthorizerAppid(),
            'authorizer_appsecret' => $detail->getAuthorizerAppSecret(),
            'auto_publish' => $detail->getAutoPublish(),
            'nick_name' => $detail->getNickName(),
            'head_img' => $detail->getHeadImg(),
            'service_type_info' => $detail->getServiceTypeInfo(),
            'verify_type_info' => $detail->getVerifyTypeInfo(),
            'signature' => $detail->getSignature(),
            'principal_name' => $detail->getPrincipalName(),
            'business_info' => $detail->getBusinessInfo(),
            'qrcode_url' => $detail->getQrcodeUrl(),
            'operator_id' => $detail->getOperatorId(),
            'bind_status' => $detail->getBindStatus(),
            'company_id' => $detail->getCompanyId(),
            'is_direct' => $detail->getIsDirect(),
            'weapp' => $wxaAppData,
        ];

        return $templateDetail;
    }

    /**
     * 上传小程序模版检查
     */
    public function submitAuditCheck($companyId, $authorizerAppId, $wxaAppId, $templateName, $wxaName)
    {
        //获取已使用的模版
        $weappInfo = app('registry')->getManager('default')->getRepository(Weapp::class)->getWeappInfo($companyId, $wxaAppId);
        if ($weappInfo && $weappInfo['template_name'] != $templateName) {
            throw new BadRequestHttpException('授权小程序已绑定其他模版，请换一个重试');
        }

        if (config('wechat.open_third')) {
            $openUserPlatform = new OpenUserPlatform();
            if (!$openUserPlatform->checkWxaBind($authorizerAppId, $wxaAppId)) {
                try {
                    $openUserPlatform->userAuthOpen($authorizerAppId, $companyId);
                } catch (\Exception $e) {
                    throw new BadRequestHttpException('当前小程序未绑定开放平台，请到微信->开放平台开通');
                }
            }
        }

        $checkService = isset($this->templateCheckList[$templateName]) ? $this->templateCheckList[$templateName] : false;
        if ($checkService) {
            $templateCheckService = new $checkService();
            $templateCheckService->check($authorizerAppId, $wxaAppId, $templateName, $wxaName);
        }

        return true;
    }

    /**
     * 设置小程序模版配置
     *
     * @param int $companyId 企业ID
     * @param string $templateName 模版名称
     * @param string $pageName 模版对应的页面名称 默认为首页 index
     * @param string $configName 对应的配置名称
     * @param array $params 配置的参数
     */
    public function setTemplateConf($companyId, $templateName, $pageName = 'index', $configName, $params, $version = 'v1.0.0', $prk = 0, $sortBy = 0)
    {
        $this->isValidWxappTemplate($templateName);
        // entityRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class)
        $entityRepository = getRepositoryLangue(WeappSetting::class);
        return $entityRepository->setParams($companyId, $templateName, $pageName, $configName, $params, $version, 0, $prk, $sortBy);
    }

    /**
     * 判断是否为有效的小程序模版
     */
    private function isValidWxappTemplate($templateName)
    {
        if (!in_array($templateName, ['pc', 'h5', 'app'])) {
            $wxappTemplateService = new WxappTemplateService();
            $templateData = $wxappTemplateService->getInfo(['key_name' => $templateName]);
            if (!$templateData) {
                $templateData = config('wxa.'.$templateName);
            }
            if (!$templateData) {
                throw new BadRequestHttpException('小程序模板不存在');
            }
        }

        return true;
    }

    public function savePageAllParams($companyId, $templateName, $pageName, $config, $version = 'v1.0.1')
    {
        $this->isValidWxappTemplate($templateName);
        $entityRepositoryOld = app('registry')->getManager('default')->getRepository(WeappSetting::class);
        $entityRepository = getRepositoryLangue(WeappSetting::class);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter['company_id'] = $companyId;
            $filter['template_name'] = $templateName;
            $filter['page_name'] = $pageName;
            $filter['version'] = $version;
            $lists = $entityRepositoryOld->lists($filter);
            $ids = []; 
            if (!empty($lists['list'])) {
                $ids = array_column($lists['list'], 'id');
            }
            $editId = [];
            foreach ($config as $key => $row) {
                $configName = $row['name'];
                $configParams = $row;
                $id = $this->setTemplateConf($companyId, $templateName, $pageName, $configName, $configParams, $version, $row['id'] ?? 0, ($key+1));
                // 重置主键id,用于修改多语言数据
                $row['id'] = $id;
                $editId[] = $row['id'];
            }
            $diffIds = array_diff($ids, $editId);
            if (!empty($diffIds)) {
                $filter['id'] = $diffIds;
                $entityRepository->deleteBy($filter);
            }

            $conn->commit();

            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取小程序模版配置
     * 如果指定模版对应的配置，则获取对应的参数
     * 如果未指定则获取所有模版的配置
     *
     * @param int $companyId 企业ID
     * @param string $templateName 模版名称
     * @param string $pageName 模版对应的页面名称
     * @param string $configName 对应的配置名称
     */
    public function getTemplateConf($companyId, $templateName, $pageName = null, $configName = null, $version = 'v1.0.0', $userId = null, $distributorId = 0)
    {

        $companySetting = (new MerchantSettingService())->getCompanyBaseSetting($companyId);

        $this->isValidWxappTemplate($templateName);
        $data = app('registry')->getManager('default')->getRepository(WeappSetting::class)->getParamByTempName($companyId, $templateName, $pageName, $configName, $version);
        $list = [];
        $itemsService = new ItemsService();
        if ($data) {
            foreach ($data as $row) {
                $pageName = $row->getPageName();
                $name = $row->getName();
                $params = unserialize($row->getParams());
                // 强制多语言转换
                $ns = new CommonLangModService();
                $lang = $ns->getLang();
                $config_param_lang = $ns->getFieldByLangue($companyId, $lang, 'params', $row->getId(), 'wechat_weapp_setting', 'wechat_weapp_setting');
                if (!empty($config_param_lang)) {
                    $params = unserialize($config_param_lang);
                }

                if (in_array($name, ['goodsScroll', 'goodsGrid'])) {
                    $itemIds = array_column($params['data'], 'goodsId');
                    if (!$itemIds) {
                        continue;
                    }
                    $itemdatalist = [];
                    if ($name == 'goodsScroll' && ($params['config']['seckillId'] ?? 0)) {
                        $promotionSeckillActivityService = new PromotionSeckillActivityService();
                        $seckillfilter['company_id'] = $companyId;
                        $seckillfilter['seckill_id'] = $params['config']['seckillId'];
                        $seckillfilter['item_id'] = $itemIds;
                        $seckilldata = $promotionSeckillActivityService->getSeckillItemList($seckillfilter, 1, 10, [], false);
                        if (($seckilldata['list'] ?? []) && ($seckilldata['activity'] ?? [])) {
                            $params['config']['lastSeconds'] = $seckilldata['activity']['last_seconds'];
                        }
                        if (!empty($seckilldata['list'])) {
                            $itemdatalist = array_column($seckilldata['list'], null, 'item_id');
                        }
                    }
                    if ($name == 'goodsGrid' || ($name == 'goodsScroll' && $params['config']['type'] == 'goods')) {
                        $itemfilter['company_id'] = $companyId;
                        $itemfilter['item_id'] = $itemIds;
                        $result = $itemsService->getItemsList($itemfilter);
                        $result = $itemsService->getItemsListMemberPrice($result, $userId, $companyId);
                        //营销标签
                        $result = $itemsService->getItemsListActityTag($result, $companyId);
                        //获取品牌名和logo
                        $result['list'] = $itemsService->getItemsListBrandData($result['list'], $companyId);
                        $itemdatalist = array_column($result['list'], null, 'item_id');
                    }

                    foreach ($params['data'] as $key => $value) {
                        if (!is_array($value) || !isset($value['goodsId'])) {
                            continue;
                        }

                        if (!($itemdatalist[$value['goodsId']] ?? [])) {   //unset($params['data'][$key]);
                            continue;
                        }
                        $itemInfo = $itemdatalist[$value['goodsId']];
                        if (intval($itemInfo['distributor_id'] ?? 0) == 0) {
                            $params['data'][$key]['distributor_id'] = $distributorId;
                        }
                        $params['data'][$key]['price'] = $itemInfo['price'] ?? 0;
                        $params['data'][$key]['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                        $params['data'][$key]['title'] = $itemInfo['item_name'];
                        $params['data'][$key]['brand'] = $itemInfo['brand_logo'] ?? '';
                        $params['data'][$key]['nospec'] = $itemInfo['nospec'];
                        $params['data'][$key]['special_type'] = $itemInfo['special_type'] ?? 'normal';
                        $params['data'][$key]['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                        $params['data'][$key]['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                        $params['data'][$key]['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                        $params['data'][$key]['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                        if (!config('common.oem-shuyun')) {
                            $params['data'][$key]['merchant_status'] = $companySetting['status'] ?? false;
                        }
                    }
                    $params['data'] = array_values($params['data']);
                } elseif ($name == 'goodsGridTab') {
                    if (isset($params['list']) && is_array($params['list'])) {
                        foreach ($params['list'] as $key => $value) {
                            $itemIds = array_column($value['goodsList'], 'goodsId');
                            if (!$itemIds) {
                                continue;
                            }
                            $itemfilter['company_id'] = $companyId;
                            $itemfilter['item_id'] = $itemIds;
                            $result = $itemsService->getItemsList($itemfilter);
                            $result = $itemsService->getItemsListMemberPrice($result, $userId, $companyId);
                            //营销标签
                            $result = $itemsService->getItemsListActityTag($result, $companyId);
                            //获取品牌名和logo
                            $result['list'] = $itemsService->getItemsListBrandData($result['list'], $companyId);
                            $itemdatalist = array_column($result['list'], null, 'item_id');
                            foreach ($value['goodsList'] as $goodsKey => $goodsValue) {
                                if (!is_array($goodsValue) || !isset($goodsValue['goodsId'])) {
                                    continue;
                                }

                                if (!($itemdatalist[$goodsValue['goodsId']] ?? [])) {   //unset($params['data'][$key]);
                                    continue;
                                }
                                $itemInfo = $itemdatalist[$goodsValue['goodsId']] ?? [];
                                if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                    $params['list'][$key]['goodsList'][$goodsKey]['distributor_id'] = $distributorId;
                                }
                                $params['list'][$key]['goodsList'][$goodsKey]['price'] = $itemInfo['price'] ?? 0;
                                $params['list'][$key]['goodsList'][$goodsKey]['imgUrl'] = $itemInfo['pics'][0] ?? ($goodsValue['imgUrl'] ?? '');
                                $params['list'][$key]['goodsList'][$goodsKey]['title'] = $itemInfo['item_name'];
                                $params['list'][$key]['goodsList'][$goodsKey]['brand'] = $itemInfo['brand_logo'] ?? '';
                                $params['list'][$key]['goodsList'][$goodsKey]['nospec'] = $itemInfo['nospec'];
                                $params['list'][$key]['goodsList'][$goodsKey]['special_type'] = $itemInfo['special_type'] ?? 'normal';
                                $params['list'][$key]['goodsList'][$goodsKey]['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                                $params['list'][$key]['goodsList'][$goodsKey]['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                                $params['list'][$key]['goodsList'][$goodsKey]['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                                $params['list'][$key]['goodsList'][$goodsKey]['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                                if (!config('common.oem-shuyun')) {
                                    $params['list'][$key]['goodsList'][$goodsKey]['merchant_status'] = $companySetting['status'] ?? false;
                                }
                            }
                        }
                    }
                }
                // if (!config('common.oem-shuyun')) {
                //     if (!isset($params['data']['merchant_status']) && !isset($params['data'][0])) {
                //         $params['data'][0]['merchant_status'] = $companySetting['status'] ?? false;
                //     }
                // }

                $list[] = [
                    'id' => $row->getId(),
                    'template_name' => $row->getTemplateName(),
                    'company_id' => $row->getCompanyId(),
                    'name' => $name,
                    'page_name' => $pageName ? $pageName : 'index',
                    'params' => $params,
                ];
            }
        }
        return $list;
    }

    /**
     * 根据配置ID，修改指定配置参数
     */
    public function updateParamsById($id, $companyId, $params)
    {
        return app('registry')->getManager('default')->getRepository(WeappSetting::class)->updateParamsById($id, $companyId, $params);
    }

    /**
     * 保存购物车提醒设置
     * @param string $companyId 企业ID
     * @param array $params    设置数据
     */
    public function setCartremindSetting($companyId, $params)
    {
        $this->__checkCartremind($params);
        $key = 'wxaCartremind:' . $companyId;
        return app('redis')->hset($key, $companyId, json_encode($params));
    }

    /**
     * 检查购物车提醒设置
     * @param  array $params 设置数据
     * @return bool
     */
    public function __checkCartremind($params)
    {
        if ($params['is_open'] === true) {
            $content_len = strlen($params['remind_content']);
            if ($content_len <= 0) {
                throw new BadRequestHttpException('提醒内容必填');
            }
            if ($content_len > 100) {
                throw new BadRequestHttpException('提醒内容长度最大100字符');
            }
        }
        return true;
    }

    /**
     * 获取购物车提醒设置
     * @param  string $companyId 企业ID
     * @return array            购物车提醒设置数据
     */
    public function getCartremindSetting($companyId)
    {
        $default_data = [
            'is_open' => false,
            'remind_content' => '',
        ];
        $key = 'wxaCartremind:' . $companyId;
        $data = app('redis')->hget($key, $companyId);
        if ($data) {
            $data = json_decode($data, 1);
        } else {
            $data = [];
        }
        $data = array_merge($default_data, $data);
        return $data;
    }
}
