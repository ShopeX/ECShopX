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

namespace CompanysBundle\Services\Shops;

use CompanysBundle\Entities\WxShops;
use CompanysBundle\Entities\Resources;
use CompanysBundle\Entities\ResourcesOpLog;

use CompanysBundle\Interfaces\ShopsInterface;

use CompanysBundle\Services\CommonLangModService;
use CompanysBundle\Services\SettingService;
use WechatBundle\Services\OpenPlatform;

use Dingo\Api\Exception\ResourceException;
use Exception;

class WxShopsService implements ShopsInterface
{
    /** @var wxShopsRepository */
    private $wxShopsRepository;

    /** @var openPlatform */
    private $openPlatform;

    /** @var resourcesRepository */
    private $resourcesRepository;

    /**
     * WxShopsService 构造函数.
     */
    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->wxShopsRepository = app('registry')->getManager('default')->getRepository(WxShops::class);
        $this->resourcesRepository = app('registry')->getManager('default')->getRepository(Resources::class);
    }

    /**
     * 添加微信门店,并推送到微信
     *
     * @param array shopsInfo 提交的门店数据
     * @return array
     */
    public function addShops(array $shopsInfo)
    {
        // 检查是否开通了门店小程序
        //$this->checkMerchantAuditInfo();

        //$wxResult = $this->createShopsToWeiXin($shopsInfo);
        // if($wxResult) {
        //$shopsInfo['poi_id'] = $wxResult['data']['poi_id'];
        //$shopsInfo['audit_id'] = $wxResult['data']['audit_id'];
        //$shopsInfo['status'] = '2';
        $rs = $this->wxShopsRepository->create($shopsInfo);
        // } else {
        //     throw new ResourceException('保存微信门店到本地失败.');
        // }

        return $rs;
    }

    /**
     * 添加门店时推送门店信息到微信
     *
     * @param array filter
     * @return array
     */
    private function createShopsToWeiXin($data)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $app = $this->openPlatform->getAuthorizerApplication($authorizerAppId);
        $pic_list = json_decode($data['pic_list'], 1);
        $wxParams = [
            'map_poi_id' => $data['map_poi_id'],
            'pic_list' => json_encode(['list' => $pic_list]),
            'contract_phone' => $data['contract_phone'],
            'hour' => $data['hour'],
            'credential' => $data['credential'],
            'company_name' => ($data['add_type'] == '2') ? $data['company_name'] : '',
            'qualification_list' => ($data['add_type'] == '2') ? $data['qualification_list'] : '',
            'card_id' => '',
            'poi_id' => '',
        ];
        $wxResult = $app->mendian->addStore($wxParams); //调用微信接口

        if ($wxResult['errcode'] != '0') {
            throw new ResourceException('推送门店到微信失败.' . $wxResult['errmsg']);
        }

        return $wxResult;
    }

    /**
     * 删除微信门店
     *
     * @param integer wx_shop_id
     * @return bool
     */
    public function deleteShops($wx_shop_id)
    {
        $wxShops = $this->wxShopsRepository->get($wx_shop_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $wxShops['company_id']) {
            throw new ResourceException('删除门店信息有误，请确认您的门店再删除.');
        }

        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $app = $this->openPlatform->getAuthorizerApplication($authorizerAppId);
        $wxResult = $app->mendian->delStore($wxShops['poi_id']); //调用微信接口
        if ($wxResult['errcode'] != '0') {
            throw new ResourceException('删除微信门店失败.');
        }
        if ($wxShops['resource_id'] && time() < $wxShops['expired_at']) {
            $res = $this->resourcesRepository->get(['resource_id' => $wxShops['resource_id']]);
            if (!$res) {
                throw new Exception("要更新的资源包不存在");
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $wxShops = $this->wxShopsRepository->get($wx_shop_id);
            if ($wxShops && $wxShops['resource_id']) {
                $res = $this->resourcesRepository->get(['resource_id' => $wxShops['resource_id']]);
                if ($res && time() < $wxShops['expired_at']) {
                    $res = $this->resourcesRepository->get(['resource_id' => $wxShops['resource_id']]);
                    $leftShopNum = $res->getLeftShopNum();
                    $resource = $this->resourcesRepository->update($wxShops['resource_id'], ['left_shop_num' => intval($leftShopNum) + 1]);
                    $resourcesOpLogRepository = app('registry')->getManager('default')->getRepository(ResourcesOpLog::class);
                    $resourcesOpLogData = [
                        'store_name' => $wxShops['store_name'],
                        'shop_id' => $wx_shop_id,
                        'resource_id' => $wxShops['resource_id'],
                        'company_id' => $company_id,
                        'op_type' => 'release',
                        'op_time' => time(),
                        'op_num' => 1,
                    ];
                    $resourcesOpLogRepository->create($resourcesOpLogData);
                }
            }

            $result = $this->wxShopsRepository->delete($wx_shop_id);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    public function getShopInfoByShopId($wxShopId)
    {
        return $this->wxShopsRepository->get($wxShopId);
    }

    /**
     *获取门店信息
     *
     * @param array filter
     * @return array
     */
    public function getShopsDetail($wx_shop_id)
    {
        $wxShops = $this->wxShopsRepository->get($wx_shop_id);
        if ($wxShops && $wxShops['resource_id'] && $wxShops['expired_at'] > time()) {
            $resource = $this->resourcesRepository->get(['resource_id' => $wxShops['resource_id']]);
            if (!$resource) {
                throw new Exception("resource_id={$wxShops['resource_id']}的资源包不存在！");
            }
            $wxShops['resource_name'] = $resource->getResourceName();
        } else {
            $wxShops['resource_id'] = null;
        }

        return $wxShops;
    }

    /**
     *获取门店信息
     *
     * @param array filter
     * @return array
     */
    public function getShopsList($filter, $page = 1, $pageSize = 100, $orderBy = ['wx_shop_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = $pageSize ?: 100;
        $wxShopsList = $this->wxShopsRepository->list($filter, $orderBy, $pageSize, $page);
        if ($wxShopsList) {
            foreach ($wxShopsList['list'] as $key => $value) {
                $wxShopsList['list'][$key]['picList'] = json_decode($value['picList'], 1);
                if ($value['expiredAt'] < time()) {
                    $wxShopsList['list'][$key]['is_valid'] = false;
                } else {
                    $wxShopsList['list'][$key]['is_valid'] = true;
                }
            }
        }

        return $wxShopsList;
    }

    /**
     * 同步微信门店到本地
     *
     * @return bool
     */
    public function syncWxShops()
    {
        // 检查是否开通了门店小程序
        $this->checkMerchantAuditInfo();

        $limit = 10;
        $PageCount = 100;
        for ($pageNo = 0; $pageNo < $PageCount; $pageNo++) {
            $list = $this->getWxShops($pageNo * $limit, $limit);
            $totalPage = ceil($list['total_count'] / $limit);
            $result = $this->saveWxShops($list['business_list']);
            if ($pageNo > $totalPage) {
                break;
            }
            if ($result) {
                continue;
            }
        }
        return true;
    }

    /**
     * 分页获取微信返回的门店列表
     *
     * @param int $offset 起始页值
     * @param int $limit 偏移量
     * @return array
     */
    private function getWxShops($offset = 0, $limit = 10)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $app = $this->openPlatform->getAuthorizerApplication($authorizerAppId);
        return $app->mendian->getStoreList($offset, $limit); //调用微信接口
    }

    /**
     * 分页获取微信返回的门店列表
     *
     * @param int $offset 起始页值
     * @param int $limit 偏移量
     * @return array
     */
    private function checkMerchantAuditInfo()
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $app = $this->openPlatform->getAuthorizerApplication($authorizerAppId);
        $result = $app->mendian->getMerchantAuditInfo(); //调用微信接口
        if ($result['errcode'] != '0') {
            throw new ResourceException('查询门店小程序失败.');
        }
        $status = $result['data']['status'];
        switch ($status) {
            case 0:
                throw new ResourceException('未提交门店小程序申请.');
                break;
            case 1: // 审核成功
                return true;
                break;
            case 2:
                throw new ResourceException('门店小程序正在审核中，暂时不能做门店相关操作，请耐心等待.');
                break;
            case 3:
                throw new ResourceException('门店小程序审核失败，请检查原因，重新申请.' . '(' . $result['data']['reason'] . ')');
                break;
            case 4:
                throw new ResourceException('管理员拒绝，请检查原因，重新申请.' . '(' . $result['data']['reason'] . ')');
                break;
        }
        return false;
    }

    /**
     * 保存微信返回的门店列表
     *
     * @param array wxShopsList 微信返回的门店列表
     * @return bool
     */
    private function saveWxShops($wxShopsList)
    {
        $company_id = app('auth')->user()->get('company_id');
        foreach ($wxShopsList as $v) {
            $info = $v['base_info'];
            $wxShopsInfo = $this->wxShopsRepository->getDetailByPoiId($info['poi_id']);
            $pic_list = array_column($info['photo_list'], 'photo_url');
            $shopsInfo = [
                'store_name' => $info['business_name'],
                'poi_id' => $info['poi_id'],
                'lng' => $info['longitude'],
                'lat' => $info['latitude'],
                'address' => $info['province'] . $info['city'] . $info['district'] . $info['address'],
                'category' => $info['categories'] ? implode(':', $info['categories']) : '',
                'pic_list' => json_encode($pic_list),
                'contract_phone' => $info['telephone'],
                'hour' => $info['open_time'],
                'add_type' => $info['qualification_name'] ? 2 : 1,
                'company_name' => $info['qualification_name'],
                'credential' => $info['qualification_num'],
                'status' => $info['status'],
                'company_id' => $company_id,
            ];
            if (
                isset($wxShopsInfo['poi_id'])
                && $wxShopsInfo['poi_id']
                && isset($wxShopsInfo['wx_shop_id'])
                && $wxShopsInfo['wx_shop_id']
            ) {
                $this->wxShopsRepository->update($wxShopsInfo['wx_shop_id'], $shopsInfo);
            } else {
                $shopsInfo['distributor_id'] = 0;
                $this->wxShopsRepository->create($shopsInfo);
            }
        }
        return true;
    }

    /**
     * 修改微信门店信息,并推送到微信
     *
     * @param array data 提交的门店数据
     * @param array filter 过滤参数
     * @return array
     */
    public function updateShops($data, $filter)
    {
        $wxShops = $this->wxShopsRepository->get($data['wx_shop_id']);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $wxShops['company_id']) {
            throw new ResourceException('请确认您的门店信息后再提交.');
        }
        //$wxResult = $this->updateShopsToWeiXin($wxShops, $data);

        //if(isset($wxResult['data']['has_audit_id']) && $wxResult['data']['has_audit_id']=='1') {
        //    $data['status'] = 2;
        //    $data['audit_id'] = $wxResult['data']['audit_id'];
        //}
        if (isset($data['resource_id']) && (!$wxShops['resource_id'] || time() > $wxShops['expired_at'])) {
            $filter = [
                'company_id' => $company_id,
                'resource_id' => $data['resource_id'],
            ];
            $resourceInfo = $this->resourcesRepository->get($filter);
            $resExpiredAt = $resourceInfo->getExpiredAt();
            $leftShopNum = $resourceInfo->getLeftShopNum();
            if (!$resourceInfo || time() > $resExpiredAt || $leftShopNum <= 0) {
                throw new \Exception("resource_id={$data['resource_id']}的资源包不可用！");
            }
            $data['expired_at'] = $resourceInfo->getExpiredAt();
        }
        $rs = $this->wxShopsRepository->update($wxShops['wx_shop_id'], $data);

        return $rs;
    }

    public function setDefaultWxShops($companyId, $wx_shop_id)
    {
        $wxShops = $this->wxShopsRepository->get($wx_shop_id);
        if (!$wxShops) {
            throw new ResourceException("需要更新的门店不存在");
        }
        return $this->wxShopsRepository->setDefaultWxShops($companyId, $wx_shop_id);
    }

    public function setResource($companyId, $wx_shop_id, $resourceId)
    {
        $wxShops = $this->wxShopsRepository->get($wx_shop_id);
        if (!$wxShops) {
            throw new ResourceException("需要更新的门店不存在");
        }
        $resource = $this->resourcesRepository->get(['company_id' => $companyId, 'resource_id' => $resourceId]);
        if (!$resource) {
            throw new ResourceException("资源包不在");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($resourceId != $wxShops['resource_id']) {
                $resExpiredAt = $resource->getExpiredAt();
                $leftShopNum = $resource->getLeftShopNum();
                $updateData = [
                    'resource_id' => $resourceId,
                    'expired_at' => $resExpiredAt,
                ];
                $result = $this->wxShopsRepository->update($wx_shop_id, $updateData);
                $resource = $this->resourcesRepository->update($resourceId, ['left_shop_num' => $leftShopNum - 1]);
            }

            //店铺资源操作记录
            $resourcesOpLogRepository = app('registry')->getManager('default')->getRepository(ResourcesOpLog::class);
            $resourcesOpLogData = [
                'store_name' => $wxShops['store_name'],
                'shop_id' => $wx_shop_id,
                'resource_id' => $resourceId,
                'company_id' => $companyId,
                'op_type' => 'occupy',
                'op_time' => time(),
                'op_num' => 1,
            ];
            $resourcesOpLogRepository->create($resourcesOpLogData);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $resource;
    }

    public function getDefaultShop($companyId, $isValid = true)
    {
        $filter = [
            'company_id' => $companyId,
            'is_default' => true,
        ];
        if ($isValid) {
            $filter['expired_at|gt'] = time();
        }

        return $this->getShopsList($filter, 1, 1);
    }

    /**
     * 修改门店后推送门店信息到微信
     *
     * @param array wxShops 数据库原有信息
     * @param array data 新数据
     * @return array
     */
    private function updateShopsToWeiXin($wxShops, $data)
    {
        if (!isset($data['map_poi_id']) || !$data['map_poi_id']) {
            throw new ResourceException('如果您是从微信同步门店到本系统，则需要您重新编辑门店并在地图上选择门店并导入后再推送.');
        }

        // 推送到微信
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $app = $this->openPlatform->getAuthorizerApplication($authorizerAppId);
        $pic_list = json_decode($data['pic_list'], 1);
        $wxParams = [
            'map_poi_id' => $data['map_poi_id'],
            'poi_id' => $wxShops['poi_id'],
            'hour' => $data['hour'],
            'contract_phone' => $data['contract_phone'],
            'pic_list' => json_encode(['list' => $pic_list]),
        ];
        $wxResult = $app->mendian->updateStore($wxShops['poi_id'], $wxParams); //调用微信接口
        if ($wxResult['errcode'] != '0') {
            throw new ResourceException('推送门店到微信失败.' . $wxResult['errmsg']);
        }

        return $wxResult;
    }

    /**
     * 添加门店后微信推送审核信息到本系统
     *
     * @param array data 微信推送数据
     * @return boolean
     */
    public function WxShopsAddEvent($data)
    {
        $wxShopsInfo = $this->wxShopsRepository->getDetailByAuditId($data['audit_id']);
        if ($wxShopsInfo) {
            $shopsInfo = [
                'errmsg' => $data['errmsg'],
                'status' => $data['status'],
            ];
            $this->wxShopsRepository->update($wxShopsInfo['wx_shop_id'], $shopsInfo);
        }

        return true;
    }

    /**
     * 更新门店后微信推送审核信息到本系统
     *
     * @param array data 微信推送数据
     * @return boolean
     */
    public function WxShopsUpdateEvent($data)
    {
        $wxShopsInfo = $this->wxShopsRepository->getDetailByAuditId($data['audit_id']);
        if ($wxShopsInfo) {
            $shopsInfo = [
                'errmsg' => $data['errmsg'],
                'status' => $data['status'],
            ];
            $this->wxShopsRepository->update($wxShopsInfo['wx_shop_id'], $shopsInfo);
        }

        return true;
    }

    /**
     * 设置门店配置信息
     */
    public function setWxShopsSetting($companyId, $params)
    {
        if (!$params) {
            throw new ResourceException('请填写配置信息');
        }
        $ns = new CommonLangModService();
        $params = $ns->setLangDataIndexLang($params,['intro','brand_name','name']);

        return app('redis')->connection('companys')->set($this->genReidsId($companyId), json_encode($params));
    }

    public function getWxShopsSetting($companyId)
    {
        $data = app('redis')->connection('companys')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            $ns = new CommonLangModService();
            $data = $ns->getLangDataIndexLang($data);
            
            return $data;
        }else {
            $data = [
                "intro" => '',
                "logo" => '',
                "brand_name" => '',
            ];
        }
        $distributorWhite = (new SettingService())->getOpenDistributorDivided($companyId);
        $data['open_distributor_divided'] = $distributorWhite;

        return [];
    }

    /**
     * 获取redis存储的ID
     */
    public function genReidsId($companyId)
    {
        return 'wxShopsSetting:' . sha1($companyId);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->wxShopsRepository->$method(...$parameters);
    }
}
