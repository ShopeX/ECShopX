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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\Distributor;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use DistributionBundle\Events\DistributionAddEvent;
use DistributionBundle\Events\DistributionEditEvent;
use DistributionBundle\Repositories\DistributorRepository;
use DistributionBundle\Events\DistributorCreateEvent;
use DistributionBundle\Events\DistributorUpdateEvent;
use EspierBundle\Services\Upload\UploadService;
use EspierBundle\Services\UploadToken\UploadTokenAbstract;
use EspierBundle\Services\UploadTokenFactoryService;
use GoodsBundle\Services\ItemsService;

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Services\DiscountCardService;
use OrdersBundle\Services\TradeRateService;
use PromotionsBundle\Services\MarketingActivityService;
use ThemeBundle\Jobs\CreateDistributorJob;
use WechatBundle\Services\OpenPlatform;
use OrdersBundle\Traits\GetOrderServiceTrait;

use MerchantBundle\Services\MerchantService;
use AftersalesBundle\Services\AftersalesRefundService;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\Payments\AlipayService;

class DistributorService
{
    use GetOrderServiceTrait;

    /** @var \DistributionBundle\Repositories\DistributorRepository */
    public $entityRepository;
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
     * 创建店铺
     */
    public function createDistributor($data)
    {
        if (!ismobile($data['mobile']) && !istel($data['mobile'])) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.mobile_phone_format_error'));
        }

        if (($data['source_from'] ?? 0) !== 2) {
            $defaultData = $this->getDefaultDistributor($data['company_id']);
            if (!$defaultData) {
                $data['is_default'] = true;
            }
        }
        ## 店铺编号唯一
        $infoByShopcode = $this->entityRepository->getInfo(['shop_code' => $data['shop_code'], 'company_id' => $data['company_id']]);
        if ($infoByShopcode && $infoByShopcode['is_valid'] != 'delete') {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.shop_code_exists'));
        }
        $infoByMobile = $this->entityRepository->getInfo(['mobile' => $data['mobile'], 'company_id' => $data['company_id']]);
        if ($infoByMobile && $infoByMobile['is_valid'] != 'delete') {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.shop_mobile_exists'));
        }
        if (isset($data['wdt_shop_no']) && $data['wdt_shop_no']) {
            $infoByWdt = $this->entityRepository->getInfo(['wdt_shop_no' => $data['wdt_shop_no'], 'company_id' => $data['company_id']]);
            if ($infoByWdt && $infoByWdt['is_valid'] != 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.wdt_shop_bound'));
            }
        }

        if (isset($data['jst_shop_id']) && $data['jst_shop_id'] > 0) {
            $infoByJst = $this->entityRepository->getInfo(['jst_shop_id' => $data['jst_shop_id'], 'company_id' => $data['company_id']]);
            if ($infoByJst && $infoByJst['is_valid'] != 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.jst_shop_bound'));
            }
        }

        $result = $this->entityRepository->create($data);
        // 分发事件
        $this->dispatchEventsWhenCreate($result);



        return $result;
    }

    /**
     * 店铺被创建时，执行对应的事件
     * @param array $eventData
     */
    public function dispatchEventsWhenCreate(array $eventData)
    {
        //新增门店默认创建智能模板
        $gotoJob = (new CreateDistributorJob($eventData))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        //触发事件
        event(new DistributionAddEvent($eventData));
        event(new DistributorCreateEvent($eventData));
    }

    /**
     * 店铺被更新时，执行对应的事件
     * @param array $eventData
     */
    public function dispatchEventsWhenUpdate(array $eventData)
    {
        event(new DistributionEditEvent($eventData));
        event(new DistributorUpdateEvent($eventData));
    }

    /**
     * 更新店铺
     *
     * @param int $distributorId 店铺ID
     * @param array $data 更新的数据
     */
    public function updateDistributor($distributorId, $data)
    {
        $defaultData = $this->getDefaultDistributor($data['company_id']);
        if (!$defaultData) {
            $data['is_default'] = true;
        }

        $infoById = $this->entityRepository->getInfo(['distributor_id' => $distributorId, 'company_id' => $data['company_id']]);
        if (!$infoById) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.confirm_update_data'));
        }

        if (isset($data['mobile'])) {
            if (!ismobile($data['mobile']) && !istel($data['mobile'])) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.mobile_phone_format_error'));
            }
            // $infoByM = $this->entityRepository->getInfo(['mobile' => $data['mobile'], 'company_id' => $data['company_id']]);
            // if ($infoByM && $infoByM['distributor_id'] != $distributorId && $infoByM['is_valid'] != 'delete') {
            //     throw new ResourceException("修改的店铺手机号已存在");
            // }
        }

        if (isset($data['wdt_shop_no']) && $data['wdt_shop_no']) {
            $infoByWdt = $this->entityRepository->getInfo(['wdt_shop_no' => $data['wdt_shop_no'], 'company_id' => $data['company_id']]);
            if ($infoByWdt && $infoByWdt['distributor_id'] != $distributorId && $infoByWdt['is_valid'] != 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.wdt_shop_bound'));
            }
        }

        if (isset($data['jst_shop_id']) && $data['jst_shop_id'] > 0) {
            $infoByJst = $this->entityRepository->getInfo(['jst_shop_id' => $data['jst_shop_id'], 'company_id' => $data['company_id']]);
            if ($infoByJst && $infoByJst['distributor_id'] != $distributorId && $infoByJst['is_valid'] != 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.jst_shop_bound'));
            }
        }

        // 需要审核商品，改为不需要审核商品
        if ($infoById['is_audit_goods'] && isset($data['is_audit_goods']) && $data['is_audit_goods'] == 'false') {
            $itemsService = new ItemsService();
            $itemsService->updateBy(['distributor_id' => $distributorId], ['audit_status' => 'approved']);
        }

        $result = $this->entityRepository->updateOneBy(['distributor_id' => $distributorId], $data);

        //触发事件
        $this->dispatchEventsWhenUpdate($result);

        return $result;
    }

    /**
     * @param string $wxaappid 微信小程序的appid
     * @param int $distributorId 店铺id
     * @param int $isBase64 是否是否base64加密
     * @param string $codetype 二维码内部参数
     * @return string[]
     */
    public function getWxaDistributorCodeStream($wxaappid, $distributorId, $isBase64 = 0, $codetype = 'index')
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        switch ($codetype) {

        case "scancode":
            $data['page'] = 'pages/qrcode-buy';
            $scene = 'dtid=' . $distributorId . '&qrcode=true';
            break;
        case "index":
            $data['page'] = 'pages/index';
            $scene = 'dtid=' . $distributorId;
            break;
        case "store":
            $data['page'] = 'subpages/store/index';
            $scene = 'dtid=' . $distributorId;
            break;
        }
        $response = $app->app_code->getUnlimit($scene, $data);
        if (is_array($response) && $response['errcode'] !== 0) {
            if ($response['errcode'] == 41030) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorService.miniprogram_not_approved'));
            } else {
                throw new ResourceException($response['errmsg']);
            }
        }
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($response);
            return ['base64Image' => $base64];
        } else {
            return $response;
        }
    }

    /**
     * 获取微信店铺码的url（url会被上传至云存储上，只要扫描url中的二维码即可）
     * @param int $companyId 企业id
     * @param string $wxaAppid 微信小程序的appid
     * @param int $distributorId 店铺id
     * @param string $codeType 二维码内部参数
     * @return array
     * @throws \Exception
     */
    public function getWxaDistributorCodeUrl(int $companyId, string $wxaAppid, int $distributorId, string $codeType = "index"): array
    {
        $qrCodeContent = $this->getWxaDistributorCodeStream($wxaAppid, $distributorId, 0, $codeType);
        // 上传文件
        $uploadService = new UploadService($companyId, UploadTokenFactoryService::create("image"));
        $url = $uploadService->upload($qrCodeContent, UploadTokenAbstract::GROUP_DISTRIBUTOR_QR_CODE) ? $uploadService->getUrl() : "";
        // 返回参数
        return ["url" => $url];
    }

    public function getNearShopData($filter, $lat = 0, $lng = 0,$shopDivided = 0)
    {
        $filter['lat|neq'] = null;
        $filter['lng|neq'] = null;
        $distributorList = $this->entityRepository->getNearDistributorList($filter, $lat, $lng);
        if ($distributorList && $distributorList[0]['distance']) {
            $result = $distributorList[0];
            $result = $this->formatStoreInfo($result);
        } else {
            if($shopDivided){
                unset($filter['open_divided']);
                $tmpList = $this->entityRepository->getNearDistributorList($filter, $lat, $lng);
                $result = $tmpList[0] ?? [];
                $result['white_hidden'] = 1;
            }else{
                $result = $this->getDefaultDistributor($filter['company_id']);
                $result['real_default'] = 1;
            }

        }
        return $result;
    }

    /**
     * 将门店信息不再绑定店铺，店铺模拟门店信息
     */
    private function formatStoreInfo($data)
    {
        if (in_array($data['province'], ['上海市', '北京市', '重庆市', '天津市'])) {
            $data['store_address'] = $data['province'].$data['area'].$data['address'];
        } else {
            $data['store_address'] = $data['province'].$data['city'].$data['area'].$data['address'];
        }

        $data['store_name'] = $data['name'];
        $data['phone'] = $data['mobile'];
        $data['rate'] = $data['rate'] ? bcdiv($data['rate'], 100, 2) : '';
        // $data['hour'] = $this->formatHour($data['hour']);

        if (isset($data['is_ziti']) && is_numeric($data['is_ziti'])) {
            $data['is_ziti'] = $data['is_ziti'] == '1';
        }
        if (isset($data['is_delivery']) && is_numeric($data['is_delivery'])) {
            $data['is_delivery'] = $data['is_delivery'] == '1';
        }
        if (isset($data['is_open_salesman']) && is_numeric($data['is_open_salesman'])) {
            $data['is_open_salesman'] = $data['is_open_salesman'] == '1';
        }
        if (isset($data['is_default']) && is_numeric($data['is_default'])) {
            $data['is_default'] = $data['is_default'] == '1';
        }
        if (isset($data['is_self_delivery']) && is_numeric($data['is_self_delivery'])) {
            $data['is_self_delivery'] = $data['is_self_delivery'] == '1';
        }
        if (isset($data['auto_sync_goods']) && is_numeric($data['auto_sync_goods'])) {
            $data['auto_sync_goods'] = $data['auto_sync_goods'] == '1';
        }
        if (isset($data['is_audit_goods']) && is_numeric($data['is_audit_goods'])) {
            $data['is_audit_goods'] = $data['is_audit_goods'] == '1';
        }
        if (isset($data['is_distributor']) && is_numeric($data['is_distributor'])) {
            $data['is_distributor'] = $data['is_distributor'] == '1';
        }
        if (isset($data['review_status']) && is_numeric($data['review_status'])) {
            $data['review_status'] = $data['review_status'] == '1';
        }
        if (isset($data['is_dada'])) {
            $data['is_dada'] = intval($data['is_dada']);
        }
        if (isset($data['dada_shop_create']) && is_numeric($data['dada_shop_create'])) {
            $data['dada_shop_create'] = $data['dada_shop_create'] == '1';
        }
        if (isset($data['is_require_subdistrict']) && is_numeric($data['is_require_subdistrict'])) {
            $data['is_require_subdistrict'] = $data['is_require_subdistrict'] == '1';
        }
        if (isset($data['is_require_building']) && is_numeric($data['is_require_building'])) {
            $data['is_require_building'] = $data['is_require_building'] == '1';
        }

        $selfDeliveryService = new selfDeliveryService();
        $selfDeliveryRule = $selfDeliveryService->getSelfDeliverySetting($data['company_id'],$data['distributor_id'], $data['distributor_self']);
        $data['selfDeliveryRule'] = $selfDeliveryRule;
        return $data;
    }

    public function getDefaultDistributor($companyId)
    {
        $filter['company_id'] = $companyId;
        $filter['is_default'] = true;
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $result = $this->formatStoreInfo($result);
        }
        return $result;
    }

    // 获取虚拟店铺
    public function getMainDistributor($companyId)
    {
        $filter['company_id'] = $companyId;
        $filter['distributor_self'] = true;
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $result = $this->formatStoreInfo($result);
            // 虚拟店可能distributor_id大于0，所以需要手动设置为0
            $result['distributor_id'] = 0;
        }
        return $result;
    }

    /**
     *  获取默认店铺id
     * @param $companyId
     * @return int
     */
    public function getDefaultDistributorId($companyId)
    {
        $result = $this->getDefaultDistributor($companyId, true);
        return $result['distributor_id'] ?? 0;
    }

    public function getDistributionNameListByDistributorId($companyId, $distributorId, $page = 1, $pageSize = -1)
    {
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $filter = [
            'distributor_id' => $distributorId,
            'company_id'     => $companyId
        ];
        return $distributorRepository->getLists($filter, 'name,shop_code,distributor_id', $page, $pageSize);
    }

    public function getListAddDistributorFields(int $companyId,array $distributorIdSet, array &$distributorLists): array
    {
        $distributorList = $this->getDistributionNameListByDistributorId($companyId, $distributorIdSet);
        $distributorIndex = array_column($distributorList, null, 'distributor_id');
        foreach ($distributorLists as $k => $item) {
            if (isset($distributorIndex[$item['distributor_id']])) {
                $distributorLists[$k]['distributor_name'] = $distributorIndex[$item['distributor_id']]['name'];
                $distributorLists[$k]['shop_code'] = $distributorIndex[$item['distributor_id']]['shop_code'];
            } else {
                $distributorLists[$k]['distributor_name'] = trans('DistributionBundle/Services/DistributorService.platform_self_operated');
                $distributorLists[$k]['shop_code'] = '-';
            }
        }
        return $distributorLists;
    }

    /**
     * 获取自提订单门店信息
     */
    public function getOrderZitiShopInfo($companyId, $distributorId, $shopId = null)
    {
        $distributorInfo = [];
        if ($distributorId) {
            $distributorInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
        }

        $shopsInfo = [];
        if ($shopId) {
            $shopsService = new ShopsService(new WxShopsService());
            $shopsInfo = $shopsService->get($shopId);
        }

        if ($shopsInfo) {
            if ($distributorInfo) {
                $distributorInfo['lat'] = $shopsInfo['lat'];
                $distributorInfo['lng'] = $shopsInfo['lng'];
                $distributorInfo['hour'] = $shopsInfo['hour'];
                $distributorInfo['phone'] = $shopsInfo['contract_phone'];
                $distributorInfo['contract_phone'] = $shopsInfo['contract_phone'];
                $distributorInfo['store_name'] = $shopsInfo['store_name'];
                $distributorInfo['store_address'] = $shopsInfo['address'];
            } else {
                $distributorInfo = $shopsInfo;
            }
        } else {
            if ($distributorInfo) {
                $distributorInfo = $this->formatStoreInfo($distributorInfo);
            }
        }

        return $distributorInfo;
    }

    public function getInfo($filter)
    {
        if ($filter['distributor_id'] ?? 0) {
            $result = $this->entityRepository->getInfo($filter);
            if ($result) {
                $result = $this->formatStoreInfo($result);
                return $result;
            }
        }
        $distributorInfo = $this->getDefaultDistributor($filter['company_id']);
        if (!$distributorInfo || $distributorInfo['is_valid'] === 'false' || $distributorInfo['is_valid'] === false || $distributorInfo['distributor_self'] === 0 || $distributorInfo['distributor_self'] === '0') {
            $distributorInfo = $this->getMainDistributor($filter['company_id']);
        }
        
        return $distributorInfo;
    }

    /**
     * 直接查询门店信息
     * @param array $filter
     * @return array
     */
    public function getInfoSimple(array $filter): array
    {
        return $this->entityRepository->getInfo($filter);
    }

    //查到是否有总店配置

    /**
     * 获取总店信息
     * @param int $companyId 公司id
     * @param bool $getInfo 是否只需要返回店铺ID 【true 以数组的方式返回店铺的所有信息】【false 以int的方式返回店铺id】
     * @return array|int|mixed
     */
    public function getDistributorSelf($companyId, $getInfo = false,array $isOpenDivided = [])
    {
        if(!empty($isOpenDivided)){
            $distributorSelfInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'distributor_self' => 0,'open_divided'=>0,'is_valid' => 'true']);
            if(empty($distributorSelfInfo)){
                $distributorSelfInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'is_default' => 01,'is_valid' => 'true']);
                $distributorSelfInfo['white_hidden'] = 1;
            }
        }else{
            $distributorSelfInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'distributor_self' => 1]);
        }

        if (!$getInfo) {
            return $distributorSelfInfo['distributor_id'] ?? 0;
        } else {
            if ($distributorSelfInfo) {
                $distributorSelfInfo = $this->formatStoreInfo($distributorSelfInfo);
            }
            return $distributorSelfInfo;
        }
    }

    /**
     * 获取自营店铺信息（平台版下 总店与自营店是同一个）
     * @param int $companyId 公司id
     * @return array
     */
    public function getDistributorSelfSimpleInfo($companyId)
    {
        // 返回的结果集
        $result = [
            "distributor_id" => 0,
            "company_id" => $companyId,
            "name" => trans('DistributionBundle/Services/DistributorService.platform_self_operated'),
            "logo" => "",
            "shop_code" => "",
            "hour" => "",
            "mobile" => "",
            "contract_phone" => "",
            "contact" => "",
            "store_name" => trans('DistributionBundle/Services/DistributorService.platform_self_operated'),
            "store_address" => "",
        ];

        // 获取总店信息
        $selfDistributorInfo = $this->getDistributorSelf($companyId, true);
        if ($selfDistributorInfo) {
            $result["is_refund_freight"] = $selfDistributorInfo["is_refund_freight"] ?? 0;
            $result["name"] = $selfDistributorInfo["name"] ?? trans('DistributionBundle/Services/DistributorService.platform_self_operated');
            $result["logo"] = $selfDistributorInfo["logo"] ?? "";
            $result['shop_code'] = $selfDistributorInfo["shop_code"] ?? "";
            $result['hour'] = $selfDistributorInfo["hour"] ?? "";
            $result['mobile'] = $selfDistributorInfo["mobile"] ?? "";
            $result['contract_phone'] = $selfDistributorInfo["contract_phone"] ?? "";
            $result['contact'] = $selfDistributorInfo["contact"] ?? "";
            $result['store_name'] = $selfDistributorInfo["store_name"] ?? trans('DistributionBundle/Services/DistributorService.platform_self_operated');
            $result['store_address'] = $selfDistributorInfo["store_address"] ?? "";
        }

        return $result + $this->entityRepository->fake();
    }

    public function getDistributorListById($companyId, $distributorIdList)
    {
        $distributorWhere = [
            'company_id'     => $companyId,
            'distributor_id' => $distributorIdList
        ];
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $distributorListData = $distributorRepository->lists($distributorWhere, ["created" => "DESC"], -1, -1, true);

        if (!empty($distributorListData['list'])) {
            $indexDistributorList = array_column($distributorListData['list'], null, 'distributor_id');
        } else {
            $indexDistributorList = [];
        }

        $selfInfo = $this->getDistributorSelfSimpleInfo($companyId);
        $selfInfo['is_center'] = true;

        $indexDistributorList[0] = $selfInfo;

        return $indexDistributorList;
    }

    /**
     * 获取店铺初始基础信息列表
     */
    public function getDistributorOriginalList($filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'desc'])
    {
        $distributorList = $this->entityRepository->lists($filter, $orderBy, $pageSize, $page);
        return $distributorList;
    }

    public function lists($filter, $orderBy = ['created' => 'desc'], $pageSize = 100, $page = 1, $noHaving = false, $column = "*")
    {
        $distributorList = $this->entityRepository->lists($filter, $orderBy, $pageSize, $page, true, $column, $noHaving);
        if ($distributorList['list']) {
            foreach ($distributorList['list'] as &$row) {
                $row = $this->formatStoreInfo($row);
                if (isset($row['distance'])) {
                    if ($row['distance'] < 1) {
                        $row['distance_show'] = bcmul($row['distance'], 1000);
                        $row['distance_unit'] = 'm';
                    } else {
                        $row['distance_show'] = $row['distance'];
                        $row['distance_unit'] = 'km';
                    }
                } else {
                    $row['distance_show'] = '';
                    $row['distance_unit'] = '';
                }
                $selfDeliveryService = new selfDeliveryService();
                $row['selfDeliveryRule'] = $selfDeliveryService->getSelfDeliverySetting($row['company_id'],$row['distributor_id'], $row['distributor_self']);
            }
        }
        return $distributorList;
    }

    /**
     * 根据经纬度计算距离
     *
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @return string
     */
    private function distance($lat1, $lng1, $lat2, $lng2)
    {
        if (!is_numeric($lat1) || !is_numeric($lng1)) {
            return 0;
        }
        if (!$lat1 || !$lng1) {
            return 0;
        }
        $dx = $lng1 - $lng2; // 经度差值
        $dy = $lat1 - $lat2; // 纬度差值
        $b = ($lat1 + $lat2) / 2.0; // 平均纬度
        $Lx = deg2rad($dx) * 6367000.0 * cos(deg2rad($b)); // 东西距离
        $Ly = 6367000.0 * deg2rad($dy); // 南北距离
        return sqrt($Lx * $Lx + $Ly * $Ly);  // 用平面的矩形对角距离公式计算总距离
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    //获取指定参数的店铺列表，该方法用于店铺选择组件
    public function getDistributorEasylists($filter, $page = 1, $pageSize = -1, $orderBy = [])
    {
        $distributorList = $this->entityRepository->lists($filter, $orderBy, $pageSize, $page);
        if (!($distributorList['list'] ?? null)) {
            return  $distributorList;
        }
        $newlistdata = [];
        foreach ($distributorList['list'] as $val2) {
            $newlistdata[] = [
                'address' => $val2['address'],
                'name' => $val2['name'],
                'shop_code' => $val2['shop_code'],
                'mobile' => $val2['mobile'],
                'contact' => $val2['contact'],
                'distributor_id' => $val2['distributor_id'],
                'logo' => $val2['logo'] ?? null,
                'hour' => $val2['hour'] ?? '',
                'parent_distributor_id' => $val2['shop_id'],
                'lng' => $val2['lng'],
                'lat' => $val2['lat'],
                'is_distributor' => $val2['is_distributor'] ?? true,
                'is_default' => $val2['is_default'],
            ];
        }
        $distributorList['list'] = $newlistdata;
        return $distributorList;
    }

    /**
    * 格式化营业时间
    * @return array [["周一", "周五", "08:00", "21:00"],["周六", "周日", "08:00", "21:00"]]
    */
    private function formatHour($hour)
    {
        if (!$hour) {
            return [];
        }
        $new_hour = json_decode($hour, 1);
        if (!$new_hour) {
            return $this->_oldFormatHour($hour);
        }
        return $new_hour;
    }
    /**
    * 后台-格式化营业时间，兼容老数据  08:00-21:00
    */
    private function _oldFormatHour($hour)
    {
        $_hour = [];
        $hour_tmp = explode('-', $hour);
        $_hour[] = [
            '周一',
            '周日',
            $hour_tmp[0],
            $hour_tmp[1],
        ];
        return $_hour;
    }

    /**
    * 列表页-格式化营业时间
    * @return array ["周一至周三 08:00-21:00", "周四至周五 01:00-23:30", "周六至周日 00:00-23:30"]
    */
    private function formatListHour($hour)
    {
        if (!$hour) {
            return [];
        }
        $_hour = [];
        foreach ($hour as $key => $value) {
            $_hour[] = $value[0].'至'.$value[1].' '.$value[2].'-'.$value[3];
        }
        return $_hour;
    }

    /**
     * Notes: 同步企业微信部门信息到 店铺信息  【仅新增】
     * Author:Michael-Ma
     * Date:  2020年06月08日 10:27:34
     *
     * @param  int  $companyId
     * @param  array  $departmentData
     *
     * @return array[]
     */
    public function syncDepartmentToDistributor(int $companyId = 1, array $departmentData = [])
    {
        $insert_distributore_result = [];
        foreach ($departmentData as $v) {
            // 企微部门 绑定的ID 条件
            $wechat_work_department_filter_data = [
                'company_id' => $companyId,
                'wechat_work_department_id' => $v['id'],
            ];
            // 查询 企微部门 是否已经 绑定店铺
            $distributoreInfo = $this->entityRepository->getInfo($wechat_work_department_filter_data);
            if (!$distributoreInfo) { // 没有绑定就 新增
                $insert_distributore_result[] = $this->entityRepository->create($wechat_work_department_filter_data + [
                    'name' => $v['name'],// 店铺名称
                    'is_ziti' => 'true', // 默认支持自提
                    'is_valid' => 'true', // 是否有效分销店铺
                    'mobile' => implode('-', [
                        $v['id'],
                        $v['parentid'] ?? 0,
                        $v['order'] ?? 0,
                    ]), // 店铺联系方式 -- 用 部门ID + 父级部门ID + 排序 拼接
                ]);
            }
        }

        return $insert_distributore_result;
    }

    /**
     * Notes: 更新 企业微信部门 和 店铺的绑定关系 【仅更新】
     * Author:Michael-Ma
     * Date:  2020年06月11日 15:26:36
     *
     * @param  integer  $companyId
     * @param  array  $departmentData
     * @param  integer  $distributorId
     *
     * @return array
     */
    public function updateDepartmentToDistributor(int $companyId = 1, array $departmentData = [], int $distributorId = 0)
    {
        $update_distributore_result = [];
        foreach ($departmentData as $v) {

            // 企微部门 绑定的ID 条件
            $wechat_work_department_filter_data = [
                'company_id' => $companyId,
                'wechat_work_department_id' => $v['id'],
            ];

            // 查询 企微部门 是否已经 绑定店铺
            $distributoreInfo = $this->entityRepository->getInfo($wechat_work_department_filter_data);
            if ($distributoreInfo) { // 绑定就清空
                $update_distributore_result[] = $this->entityRepository->updateOneBy($wechat_work_department_filter_data, [
                    'wechat_work_department_id' => 0,
                    'is_default' => false,
                ]);
            }

            // 更新 指定店铺 和 企微部门的绑定关系
            $update_distributore_result[] = $this->updateDistributor($distributorId, $wechat_work_department_filter_data);
        }

        return $update_distributore_result;
    }
    /**
     * 设置店铺范围配置
     */
    public function setDistanceRedis($companyId, $distributorId, $value)
    {
        // $sid = $this->getDistanceRedisKey($companyId);
        // app('redis')->connection('default')->set($sid, $value);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $this->entityRepository->updateBy($filter, ['delivery_distance' => $value]);

        return true;
    }
    /**
     * 获取店铺范围配置
     */
    public function getDistanceRedis($companyId, $distributorId = 0)
    {
        // $sid = $this->getDistanceRedisKey($companyId);
        // $result = app('redis')->connection('default')->get($sid);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $distributor = $this->entityRepository->getInfo($filter);

        return $distributor['delivery_distance'] ?? 0;
    }
    /**
     * 获取店铺范围配置key
     */
    private function getDistanceRedisKey($companyId)
    {
        return 'distributorDistance'.'-'.$companyId;
    }

    // 根据区域id获取区域关联的店铺id
    public function getDistributorIdByRegionAuthId($company_id, $regionauth_id)
    {
        return $this->entityRepository->getDistributorIdByRegionAuthId($company_id, $regionauth_id);
    }

    /**
     * 获取签到二维码
     */
    public function getSignCode($distributor_id)
    {
        $signin_code = 'data:image/jpg;base64,'.app('DNS2D')->getBarcodePNG($distributor_id, "QRCODE", 10, 10);
        return $signin_code;
    }

    /**
     * 无门店，获取购物车中所有商品有库存、支持自提的门店列表
     * @param $filter
     * @param $cart_type: 购物车类型
     * @param $order_type: 订单类型
     * @return array $distributor_ids 门店id
     */
    public function getShopIdsByNostores($filter, $params)
    {
        // 查询店铺列表
        $list = $this->entityRepository->getLists($filter, 'distributor_id');
        if (!$list) {
            return [];
        }

        $orderService = $this->getOrderService($params['order_type']);
        $distributor_ids = [];
        foreach ($list as $key => $value) {
            $params['distributor_id'] = $value['distributor_id'];
            $check = $orderService->getCartItemsByNostores($params);
            if ($check) {
                $distributor_ids[] = $value['distributor_id'];
            }
        }
        // 获取所有有效的店铺
        if (!$distributor_ids) {
            return [];
        }
        return $distributor_ids;
    }

    public function getData($filter)
    {
        $result = $this->entityRepository->getInfo($filter);
        return $result;
    }

    public function getShopIdByShopCode(string $shopCode)
    {
        $filter = [
            'shop_code' => $shopCode
        ];
        $info = $this->entityRepository->getInfo($filter);
        if (empty($info)) {
            return false;
        } else {
            return $info['distributor_id'];
        }
    }

    /**
     * 得到有效店铺信息
     *
     * @param int $companyId
     * @return mixed
     */
    public function getValidDistributor(int $companyId)
    {
        $filter = [
            'is_valid' => 'true',
            'company_id' => $companyId
        ];
        $fields = 'distributor_id';
        return $this->entityRepository->getLists($filter, $fields);
    }

    /**
     * 得到有效、禁用的店铺信息
     */
    public function getValidAndDisabledDistributor(int $companyId)
    {
        $filter = [
            'is_valid' => ['true', 'false'],
            'company_id' => $companyId
        ];
        $fields = 'distributor_id';
        return $this->entityRepository->getLists($filter, $fields);
    }
    
    /**
     * 追加店铺列表数据（适用于多个店铺id）
     * @param int $companyId 公司id
     * @param array $list 列表信息
     * @return void
     */
    public function appendDistributorList(int $companyId, array &$list): void
    {
        if ($companyId < 1 || empty($list)) {
            return;
        }

        // 获取店铺id
        $distributorIds = array_filter(array_unique((array)array_column($list, "distributor_id")), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });

        // 获取店铺信息
        if (!empty($distributorIds)) {
            // 获取店铺信息
            $distributorData = $this->entityRepository->getLists(["company_id" => $companyId, "distributor_id" => $distributorIds], "distributor_id,company_id,logo,name", 1, -1);
            $distributorData = (array)array_column($distributorData, null, "distributor_id");
        } else {
            $distributorData = [];
        }
        // 加入自营店的配置信息
        $distributorData[0] = $this->getDistributorSelfSimpleInfo($companyId);

        // 追加店铺信息
        foreach ($list as &$item) {
            // 默认值是0(自营店)
            $distributorId = $item["distributor_id"] ?? 0;
            $item["distributor_list"] = [
                $distributorData[$distributorId] ?? []
            ];
        }
    }

    /**
     * 追加店铺信息
     * @param int $companyId 公司id
     * @param array $list 列表数据
     * @return void
     */
    public function appendDistributorInfo(int $companyId, array &$list): void
    {
        if ($companyId < 1 || empty($list)) {
            return;
        }

        // 获取店铺id
        $distributorIds = array_filter(array_unique((array)array_column($list, "distributor_id")), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });

        // 获取店铺信息
        if (!empty($distributorIds)) {
            // 获取店铺信息
            $distributorData = $this->entityRepository->getLists(["company_id" => $companyId, "distributor_id" => $distributorIds], "distributor_id,company_id,logo,name", 1, -1);
            $distributorData = (array)array_column($distributorData, null, "distributor_id");
        } else {
            $distributorData = [];
        }
        // 加入自营店的配置信息
        $distributorData[0] = $this->getDistributorSelfSimpleInfo($companyId);

        // 追加店铺信息
        foreach ($list as &$item) {
            // 默认值是0(自营店)
            $distributorId = $item["distributor_id"] ?? 0;
            $item["distributor_info"] = $distributorData[$distributorId] ?? [];
        }
    }

    /**
     * 追加店铺的销售数量
     * @param int $companyId
     * @param array $distributorList
     */
    public function appendSalesCount(int $companyId, array &$distributorList, bool $continue = false): void
    {
        if (empty($distributorList) || $continue) {
            return;
        }

        // 获取店铺id
        $distributorIds = (array)array_column($distributorList, "distributor_id");

        if (!empty($distributorIds)) {
            $distributorSalesCountList = (new DistributorSalesCountService())->getTotalSalesCount($companyId);
        } else {
            $distributorSalesCountList = [];
        }


        // 表店铺下的标签信息添加值店铺列表中
        foreach ($distributorList as &$distributorItem) {
            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];

            // 获取店铺的销售数量
            $distributorItem["sales_count"] = (int)($distributorSalesCountList[$distributorId] ?? 0);
        }
    }

    /**
     * 为店铺列表追加 标签列表
     * @param int $companyId 企业id
     * @param array $distributorList 店铺列表
     * @param array $tagList 店铺标签列表
     */
    public function appendTagList(int $companyId, array &$distributorList, array $tagList): void
    {
        if (empty($distributorList)) {
            return;
        }
        // 将tag_id作为key
        $tagsList = (array)array_column($tagList, null, "tag_id");
        // 获取店铺关联的店铺标签id
        $distributorRelTagIds = (new DistributorTagsService())->getRelTagIdList($companyId, (array)array_column($distributorList, "distributor_id"));

        // 将店铺id下存在的标签id的具体内容做添加
        $relTagIdWithDistributorId = [];
        foreach ($distributorRelTagIds as $distributorRelTagIdItem) {
            // 获取店铺商家的标签id
            $tagId = $distributorRelTagIdItem["tag_id"];
            if (empty($tagsList[$tagId])) {
                continue;
            }
            $relTagIdWithDistributorId[$distributorRelTagIdItem["distributor_id"]][] = [
                "tag_id" => $tagsList[$tagId]["tag_id"] ?? null,
                "tag_name" => $tagsList[$tagId]["tag_name"] ?? null,
                "tag_color" => $tagsList[$tagId]["tag_color"] ?? null,
                "font_color" => $tagsList[$tagId]["font_color"] ?? null,
                "tag_icon" => $tagsList[$tagId]["tag_icon"] ?? null,
            ];
        }

        // 表店铺下的标签信息添加值店铺列表中
        foreach ($distributorList as &$distributorItem) {
            $distributorItem["tagList"] = [];

            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];
            // 获取店铺id下面所有关联的标签id
            $distributorItem["tagList"] = $relTagIdWithDistributorId[$distributorId] ?? [];
        }
    }

    /**
     * 追加优惠券信息
     * @param int $companyId 公司id
     * @param array $distributorList 店铺列表
     */
    public function appendCouponList(int $companyId, array &$distributorList): void
    {
        if (empty($distributorList)) {
            return;
        }

        $discountCardList = (new DiscountCardService())->getOngoingList("*", [
            "company_id" => $companyId,
            "source_type" => DiscountCardService::SOURCE_TYPE_DISTRIBUTOR,
            "source_id" => (array)array_column($distributorList, "distributor_id"),
            "kq_status" => DiscountCardService::KQ_STATUS_NORMAL,
        ], -1, 1, ["created" => "DESC"]);

        $discountCardListByDistributorId = [];
        foreach ($discountCardList as $item) {
            $discountCardListByDistributorId[$item["source_id"]][] = [
                "card_id" => $item["card_id"] ?? null,
                "card_type" => $item["card_type"] ?? null,
                "title" => $item["title"] ?? null,
                "color" => $item["color"] ?? null,
                "date_type" => $item["date_type"] ?? null,
                "begin_date" => $item["begin_date"] ?? null,
                "end_date" => $item["end_date"] ?? null,
                "fixed_term" => $item["fixed_term"] ?? null,
                "quantity" => $item["quantity"] ?? null,
                "discount" => $item["discount"] ?? null,
                "least_cost" => $item["least_cost"] ?? null,
                "most_cost" => $item["most_cost"] ?? null,
                "reduce_cost" => $item["reduce_cost"] ?? null,
                "get_limit" => $item["get_limit"] ?? null,
                "receive" => (int)($item["receive"] ?? false),
            ];
        }

        foreach ($distributorList as &$distributorItem) {
            $distributorItem["discountCardList"] = [];

            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];
            // 获取店铺id下面所有关联的标签id
            $distributorItem["discountCardList"] = $discountCardListByDistributorId[$distributorId] ?? [];
        }
    }

    /**
     * 追加满折满减满赠的信息
     * @param int $companyId 公司id
     * @param array $distributorList 店铺列表
     */
    public function appendPromotionsMarketingActivity(int $companyId, array &$distributorList): void
    {
        if (empty($distributorList)) {
            return;
        }

        $list = (new MarketingActivityService())->getOngoingList("*", [
            "company_id" => $companyId,
            "source_type" => MarketingActivityService::SOURCE_TYPE_DISTRIBUTOR,
            "source_id" => (array)array_column($distributorList, "distributor_id"),
            "check_status" => MarketingActivityService::CHECK_STATUS_AGREE,
            "marketing_type" => [
                MarketingActivityService::MARKETING_TYPE_FULL_DISCOUNT,
                MarketingActivityService::MARKETING_TYPE_FULL_MINUS,
                MarketingActivityService::MARKETING_TYPE_FULL_GIFT,
            ]
        ], -1, 1, ["created" => "DESC"]);

        $listByDistributorId = [];
        foreach ($list as $item) {
            $listByDistributorId[$item["source_id"]][] = [
                "marketing_id" => $item["marketing_id"] ?? null,
                "marketing_type" => $item["marketing_type"] ?? null,
                "marketing_name" => $item["marketing_name"] ?? null,
                "condition_type" => $item["condition_type"] ?? null,
                "condition_value" => (array)jsonDecode($item["condition_value"] ?? null),
                "promotion_tag" => $item["promotion_tag"] ?? null,
                "valid_grade" => (array)jsonDecode($item["valid_grade"] ?? null),
                "join_limit" => $item["join_limit"] ?? null,
                "start_time" => $item["start_time"] ?? null,
                "end_time" => $item["end_time"] ?? null,
                "canjoin_repeat" => (int)($item["canjoin_repeat"] ?? null),
                "use_shop" => (int)($item["use_shop"] ?? null),
                "in_proportion" => (int)($item["in_proportion"] ?? null),
            ];
        }

        foreach ($distributorList as &$distributorItem) {
            $distributorItem["marketingActivityList"] = [];
            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];
            // 获取店铺id下面所有关联的标签id
            $distributorItem["marketingActivityList"] = $listByDistributorId[$distributorId] ?? [];
        }
    }

    /**
     * 追加分数
     * @param int $companyId
     * @param array $distributorList
     */
    public function appendScore(int $companyId, array &$distributorList): void
    {
        if (empty($distributorList)) {
            return;
        }

        $rateAvgList = (new TradeRateService())->rateAvgStar($companyId, (array)array_column($distributorList, "distributor_id"));

        foreach ($distributorList as &$distributorItem) {
            $distributorItem["scoreList"] = [
                "avg_star" => "5.0",
                "default" => 1
            ];
            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];
            // 获取店铺id下面的平均分
            if (isset($rateAvgList[$distributorId]) && is_numeric($rateAvgList[$distributorId])) {
                $distributorItem["scoreList"]["avg_star"] = sprintf("%.1f", $rateAvgList[$distributorId]);
                $distributorItem["scoreList"]["default"] = 0;
            }
        }
    }

    /**
     * 追加店铺的商品信息
     * @param int $companyId
     * @param array $distributorList
     * @param string|null $searchItemName
     */
    public function appendItems(int $companyId, array &$distributorList, ?string $searchItemName = null): void
    {
        if (empty($distributorList)) {
            return;
        }

        $itemFilter = [
            "company_id" => $companyId,
            "item_id|direct" => "default_item_id",
            "distributor_id" => (array)array_column($distributorList, "distributor_id"),
            "approve_status" => ["onsale", "offline_sale", "only_show"],
            "audit_status" => "approved"
        ];
        if (!is_null($searchItemName)) {
            $itemFilter["item_name|like"] = $searchItemName;
        }
        // 获取商品名称
        $itemsList = (new ItemsService())->getLists($itemFilter, "item_id,distributor_id,item_name,price,market_price,store,pics", 1, -1, [
            "created" => "DESC",
            "item_id" => "DESC"
        ]);
        $itemsByDistributorId = [];
        foreach ($itemsList as $item) {
            $distributorId = $item["distributor_id"];
            $itemsByDistributorId[$distributorId][] = [
                "item_id" => (int)($item["item_id"] ?? null),
                "item_name" => $item["item_name"] ?? null,
                "price" => (int)($item["price"] ?? null), // 销售价
                "market_price" => (int)($item["market_price"] ?? null), // 原价
                "store" => (int)($item["store"] ?? null),
                "pics" => (string)array_first((array)jsonDecode($item["pics"] ?? null)),
            ];
        }

        // 填充数据
        foreach ($distributorList as &$distributorItem) {
            $distributorItem["itemList"] = [];
            // 获取店铺id
            $distributorId = $distributorItem["distributor_id"];
            // 获取店铺id下面的店铺商品
            $distributorItem["itemList"] = array_slice($itemsByDistributorId[$distributorId] ?? [], 0, 3);
        }
    }

    /**
     * 追加店铺的商品信息
     * @param int $companyId
     * @param array $distributorList
     * @param string|null $searchItemName
     */
    public function appendItemsByFilter(int $companyId, array &$distributorList,  $searchItemFilter = [])
    {
        if (empty($distributorList)) {
            return;
        }
        // 填充数据
        foreach ($distributorList as &$distributorItem) {
            $distributorItem["itemList"] = [];
            $itemFilter = [
                "company_id" => $companyId,
                "item_id|direct" => "default_item_id",
                "distributor_id" => $distributorItem['distributor_id'],
                "approve_status" => ["onsale", "offline_sale", "only_show"],
                "audit_status" => "approved"
            ];
            if (isset($searchItemFilter['item_name']) && !is_null($searchItemFilter['item_name'])) {
                $itemFilter["item_name|like"] = $searchItemFilter['item_name'];
            }
            if (isset($searchItemFilter['item_id']) ) {
                if($searchItemFilter['item_id']){
                    $itemFilter["item_id"] = $searchItemFilter['item_id'];
                }

            }
            // 获取商品名称
            $itemsList = (new ItemsService())->getLists($itemFilter, "item_id,distributor_id,item_name,price,market_price,store,pics", 1, 10, [
                "created" => "DESC",
                "item_id" => "DESC"
            ]);
            if($itemsList){
                foreach ($itemsList as &$item){
                    $item['pics'] = (string)array_first((array)jsonDecode($item["pics"] ?? null));
                }
                $distributorItem["itemList"] = $itemsList;
            }
        }
    }

    /**
     * 根据店铺id，查询关联商户是否可用
     * @param  string $companyId     企业ID
     * @param  string $distributorId 店铺ID
     * @return [type]                [description]
     */
    public function checkMerchantIsvaild($companyId, $distributorId)
    {
        if ($distributorId == 0) {
            return true;
        }
        // 获取店铺信息
        $info = $this->entityRepository->getInfo(['distributor_id' => $distributorId, 'company_id' => $companyId]);
        if (!$info) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.shop_info_query_failed'));
        }
        if ($info['merchant_id'] == 0) {
            return true;
        }
        $merchantService = new MerchantService();
        $merchantInfo = $merchantService->getInfoById($info['merchant_id']);
        if (!$merchantInfo) {
            return true;
        }
        if ($merchantInfo['disabled'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 设置店铺收款主体
     * 
     * @param int $companyId 公司ID
     * @param int $distributorId 店铺ID
     * @param int $paymentSubject 收款主体，0=平台，1=店铺
     * @return bool
     * @throws ResourceException
     */
    public function setPaymentSubject($companyId, $distributorId, $paymentSubject)
    {
        // 验证店铺是否存在且属于当前公司
        $distributorInfo = $this->entityRepository->getInfo([
            'distributor_id' => $distributorId,
            'company_id' => $companyId
        ]);
        
        if (!$distributorInfo) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.distributor_not_exists_or_no_permission'));
        }
        
        // 检查店铺下是否有未完成的退款单
        // 需要检查的退款状态：READY(未审核)、AUDIT_SUCCESS(审核成功待退款)、PROCESSING(已发起退款等待到账)、CHANGE(退款异常)
        $aftersalesRefundService = new AftersalesRefundService();
        $refundFilter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'refund_status' => ['READY', 'AUDIT_SUCCESS', 'PROCESSING', 'CHANGE'] // 数组形式，Repository会自动使用IN查询
        ];
        // 使用count方法检查是否存在未完成的退款单（性能更好）
        $refundCount = $aftersalesRefundService->aftersalesRefundRepository->count($refundFilter);
        
        if ($refundCount > 0) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.has_unfinished_refund_cannot_switch_payment_subject'));
        }
        
        // 支付配置校验
        if ($paymentSubject == 1) {
            // 切换为店铺时，需要校验店铺的支付配置
            $this->validateDistributorPaymentConfig($companyId, $distributorId);
        } else {
            // 切换为平台时，需要校验平台的支付配置
            $this->validatePlatformPaymentConfig($companyId);
        }
        
        // 更新收款主体
        $updateData = ['payment_subject' => $paymentSubject];
        $this->entityRepository->updateBy(
            ['distributor_id' => $distributorId, 'company_id' => $companyId],
            $updateData
        );
        
        return true;
    }

    /**
     * 校验店铺支付配置
     * 
     * @param int $companyId 公司ID
     * @param int $distributorId 店铺ID
     * @throws ResourceException
     */
    private function validateDistributorPaymentConfig($companyId, $distributorId)
    {
        $paymentsService = new PaymentsService();
        $paymentStatus = $paymentsService->getPaymentOpenStatusList($companyId);
        app('log')->debug('validateDistributorPaymentConfig::paymentStatus====>'.json_encode($paymentStatus, 1));
        $wxpayOpen = $paymentStatus['wxpay']['is_open'] ?? false;
        $alipayOpen = $paymentStatus['alipay']['is_open'] ?? false;
        
        // 检查是否至少有一个支付方式开启
        if (!$wxpayOpen && !$alipayOpen) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorService.switch_to_distributor_payment_subject_need_at_least_one_payment'));
        }
        
        // 检查已开启的支付方式配置是否完整
        if ($wxpayOpen) {
            $this->validateWxpayConfig($companyId, $distributorId);
        }
        
        if ($alipayOpen) {
            $this->validateAlipayConfig($companyId, $distributorId);
        }
    }

    /**
     * 校验平台支付配置
     * 
     * @param int $companyId 公司ID
     * @throws ResourceException
     */
    private function validatePlatformPaymentConfig($companyId)
    {
        $paymentsService = new PaymentsService();
        $paymentStatus = $paymentsService->getPaymentOpenStatusList($companyId);
        app('log')->debug('validatePlatformPaymentConfig::paymentStatus====>'.json_encode($paymentStatus));
        $wxpayOpen = $paymentStatus['wxpay']['is_open'] ?? false;
        $alipayOpen = $paymentStatus['alipay']['is_open'] ?? false;
        
        // 检查已开启的支付方式配置是否完整
        if ($wxpayOpen) {
            $this->validateWxpayConfig($companyId, 0);
        }
        
        if ($alipayOpen) {
            $this->validateAlipayConfig($companyId, 0);
        }
    }

    /**
     * 校验微信支付配置
     * 
     * @param int $companyId 公司ID
     * @param int $distributorId 店铺ID，0表示平台
     * @throws ResourceException
     */
    private function validateWxpayConfig($companyId, $distributorId)
    {
        $wxpayService = new WechatPayService($distributorId, false);
        $wxpayServiceWrapper = new PaymentsService($wxpayService);
        $wxpayConfig = $wxpayServiceWrapper->getPaymentSetting($companyId);
        
        $requiredFields = ['app_id', 'merchant_id', 'key', 'cert', 'cert_key'];
        
        foreach ($requiredFields as $field) {
            $value = $wxpayConfig[$field] ?? '';
            if (empty($value) && $value !== '0') {
                if ($distributorId > 0) {
                    throw new ResourceException(trans('DistributionBundle/Services/DistributorService.distributor_wxpay_config_incomplete'));
                } else {
                    throw new ResourceException(trans('DistributionBundle/Services/DistributorService.platform_wxpay_config_incomplete'));
                }
            }
        }
    }

    /**
     * 校验支付宝配置
     * 
     * @param int $companyId 公司ID
     * @param int $distributorId 店铺ID，0表示平台
     * @throws ResourceException
     */
    private function validateAlipayConfig($companyId, $distributorId)
    {
        $alipayService = new AlipayService($distributorId, false);
        $alipayServiceWrapper = new PaymentsService($alipayService);
        $alipayConfig = $alipayServiceWrapper->getPaymentSetting($companyId);
        
        $requiredFields = ['app_id', 'ali_public_key', 'private_key'];
        
        foreach ($requiredFields as $field) {
            $value = $alipayConfig[$field] ?? '';
            if (empty($value) && $value !== '0') {
                if ($distributorId > 0) {
                    throw new ResourceException(trans('DistributionBundle/Services/DistributorService.distributor_alipay_config_incomplete'));
                } else {
                    throw new ResourceException(trans('DistributionBundle/Services/DistributorService.platform_alipay_config_incomplete'));
                }
            }
        }
    }
}
