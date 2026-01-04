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

namespace OpenapiBundle\Services\Distributor;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorItemsService;
use EspierBundle\Services\Cache\RedisCacheService;
use EspierBundle\Services\Upload\UploadService;
use EspierBundle\Services\UploadToken\UploadTokenAbstract;
use EspierBundle\Services\UploadTokenFactoryService;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\BaseService;
use OrdersBundle\Services\LocalDeliveryService;

class DistributorService extends BaseService
{
    public function getEntityClass(): string
    {
        return Distributor::class;
    }

    public const STATUS_DELETED = 0; // 废弃
    public const STATUS_OPEN = 1; // 启用
    public const STATUS_CLOSE = 2; // 禁用
    public const STATUS_MAP = [
        self::STATUS_DELETED => "废弃",
        self::STATUS_OPEN => "启用",
        self::STATUS_CLOSE => "禁用",
    ];

    /**
     * 根据状态获取is_valid值
     * @param int $status
     * @return string
     */
    public static function getIsValidByStatus(int $status): string
    {
        switch ($status) {
            case self::STATUS_DELETED:
                return "delete";
                break;
            case self::STATUS_OPEN:
                return "true";
                break;
            case self::STATUS_CLOSE:
                return "false";
                break;
            default:
                throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "店铺状态参数错误");
        }
    }

    /**
     * 根据is_valid值获取状态
     * @param int $status
     * @return string
     */
    public static function getStatusByIsValid(string $isValid): int
    {
        switch ($isValid) {
            case "delete":
                return self::STATUS_DELETED;
                break;
            case "true":
                return self::STATUS_OPEN;
                break;
            case "false":
                return self::STATUS_CLOSE;
                break;
            default:
                return -1;
        }
    }

    /**
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @param bool $noHaving true表示不需要对数据做聚合处理，false表示需要对数据做聚合处理。 聚合筛选附近门店
     * @return array
     */
    public function list(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true, bool $noHaving = false): array
    {
        $result = $this->getRepository()->lists($filter, $orderBy, $cols, $pageSize, $needCountSql, $cols, $noHaving);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 根据店铺id或店铺code来查询店铺
     * @param array $filter
     * @return array
     */
    public function findByIdOrCode(array $filter): array
    {
        // 获取店铺
        $distributorFilter = [
            "company_id" => $filter["company_id"]
        ];
        if (isset($filter["distributor_id"])) {
            $distributorFilter["distributor_id"] = $filter["distributor_id"];
        } elseif (isset($filter["shop_code"])) {
            $distributorFilter["shop_code"] = $filter["shop_code"];
        } elseif (isset($filter["distributor_code"])) {
            $distributorFilter["shop_code"] = $filter["distributor_code"];
        } else {
            return [];
        }
        return parent::find($distributorFilter);
    }

    /**
     * 检查店铺号是否重复
     * @param int $companyId 企业id
     * @param string $shopCode 店铺号
     * @param int $distributorId 店铺id
     * @return $this
     */
    protected function checkShopCode(int $companyId, string $shopCode, int $distributorId = 0): self
    {
        $info = $this->find(['company_id' => $companyId, 'shop_code' => $shopCode]);
        if (!empty($info) && $info["distributor_id"] != $distributorId) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_EXIST, "店铺号已存在");
        }
        return $this;
    }

    /**
     * 检查店铺名称是否重复
     * @param int $companyId 企业id
     * @param string $name 店铺名称
     * @param int $distributorId 店铺id
     * @return $this
     */
    protected function checkName(int $companyId, string $name, int $distributorId = 0): self
    {
        $info = $this->find(['company_id' => $companyId, 'name' => $name]);
        if (!empty($info) && $info["distributor_id"] != $distributorId) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_EXIST, "店铺名称已存在");
        }
        return $this;
    }

    /**
     * 插件店铺的联系号是否重复
     * @param int $companyId 企业id
     * @param string $mobile 店铺手机号
     * @param int $distributorId 店铺id
     * @return $this
     */
    protected function checkMobile(int $companyId, string $mobile, int $distributorId = 0): self
    {
        $info = $this->find(['company_id' => $companyId, 'mobile' => $mobile]);
        if (!empty($info) && $info["distributor_id"] != $distributorId) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_EXIST, "店铺手机号已存在");
        }
        return $this;
    }

    /**
     * 检查同城配送
     * @param int $companyId 企业id
     * @param int $isData 是否是同城配送
     * @param array $params 请求参数
     * @return $this
     */
    protected function checkLocalDelivery(int $companyId, int $isCreate, array &$params): self
    {
        if ($isCreate) {
            $localDeliveryConfig = $localDeliveryService->getConfigService()->getInfo(['company_id' => $companyId]);
            if (empty($localDeliveryConfig['is_open'])) {
                throw new ErrorException(ErrorCode::ORDER_ERROR, "该商户未开启达达同城配");
            }
            $dadaResult = $localDeliveryService->getShopService()->createShop($companyId, [$params]);
            $originShopId = $dadaResult['successList'][0]['originShopId'];
            $params['shop_code'] = $originShopId;
            $dirver = $localDeliveryService->getDirver();
            $params[$dirver.'_shop_create'] = 1;
        }
        return $this;
    }

    /**
     * 创建店铺
     * @param array $createData
     * @return array
     */
    public function create(array $createData): array
    {
        $params = [
            "shop_code" => (string)($createData["shop_code"] ?? ""), // 店铺号
            "is_distributor" => true, // 默认不是主店铺
            "company_id" => (int)$createData["company_id"], // 企业id
            "name" => (string)($createData["distributor_name"] ?? ""), // 店铺名称
            "auto_sync_goods" => (bool)($createData["is_auto_sync_goods"] ?? false), // 自动同步总部商品, 默认是false
            "logo" => (string)($createData["logo"] ?? ""), // 店铺logo
            "contact" => (string)($createData["contact_username"] ?? ""), // 联系人名称
            "mobile" => (int)($createData["contact_mobile"] ?? ""), // 店铺手机号
            "contract_phone" => "0", // 其他联系方式
            "banner" => "", // 店铺banner
            "is_valid" => "false", // 店铺是否有效，默认是禁用
            "lng" => "", // 腾讯地图纬度
            "lat" => "", // 腾讯地图经度
            "province" => "", // 省
            "city" => "", // 市
            "area" => "", // 区
            "address" => "", // 店铺地址
            "hour" => (string)($createData["hour"] ?? ""), // 营业时间
            "regions_id" => null, //
            "regions" => null, //
            "is_default" => (bool)($createData["is_default"] ?? false), // 是否默认店铺, 默认是false
            "is_ziti" => (bool)($createData["is_ziti"] ?? false), // 是否支持自提, 默认是false
            "is_delivery" => (bool)($createData["is_delivery"] ?? true), // 是否支持快递, 默认是true
            "review_status" => true, // 入驻审核状态，0未审核，1已审核, 默认已审核
            "source_from" => 3, // 店铺来源
            "is_dada" => (bool)($createData["is_dada"] ?? false), // 是否开启同城配, 默认是false
            "dada_shop_create" => 0, // 该门店在达达是否已创建
            "shansong_shop_create" => 0, // 该门店在闪送是否已创建
        ];

        $this->checkShopCode($params["company_id"], $params["shop_code"])
            ->checkMobile($params["company_id"], $params["mobile"])
            ->checkName($params["company_id"], $params["name"])
            ->checkLocalDelivery($params["company_id"], $params["is_dada"], $params);

        $result = parent::create($params);
        // 分发事件
        (new \DistributionBundle\Services\DistributorService())->dispatchEventsWhenCreate($result);
        // 返回结果
        return $result;
    }

    /**
     * 更新店铺
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        // 查询店铺信息
        $distributorInfo = $this->findByIdOrCode($filter);
        if (empty($distributorInfo)) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
        }
        // 获取企业id与店铺id
        $companyId = (int)$distributorInfo["company_id"];
        $distributorId = (int)$distributorInfo["distributor_id"];
        $params = [];
        // 店铺号
        if (isset($updateData["shop_code"])) {
            $params["shop_code"] = (string)$updateData["shop_code"];
            $this->checkShopCode($companyId, $params["shop_code"], $distributorId);
        }
        // 店铺名称
        if (isset($updateData["distributor_name"])) {
            $params["name"] = (string)$updateData["distributor_name"];
            $this->checkName($companyId, $params["name"], $distributorId);
        }
        // 联系号码
        if (isset($updateData["contact_mobile"])) {
            $params["mobile"] = (string)$updateData["contact_mobile"];
            $this->checkMobile($companyId, $params["mobile"], $distributorId);
        }
        // 普通字段的处理
        foreach ([
                     "hour" => null,      // 营业时间
                     "contact_username" => "contact", // 联系人
                     "logo" => null,      // 店铺logo
                     "province" => null,      // 省
                     "city" => null,      // 市
                     "area" => null,      // 区
                     "address" => null,      // 详细地址
                     "lng" => null,      // 经度
                     "lat" => null,      // 纬度
                 ] as $updateDataKeyName => $dbColumn) {
            if (isset($updateData[$updateDataKeyName])) {
                $params[is_null($dbColumn) ? $updateDataKeyName : $dbColumn] = (string)$updateData[$updateDataKeyName];
            }
        }
        // 枚举字段的处理
        foreach ([
                     "is_ziti" => null,              // 是否支持自提
                     "is_delivery" => null,              // 是否支持快递
                     "is_auto_sync_goods" => "auto_sync_goods", // 是否自动同步总部商品
                     "is_dada" => null,              // 是否开启达达同城配
                     "is_default" => null,              // 是否默认店铺
                 ] as $updateDataKeyName => $dbColumn) {
            if (isset($updateData[$updateDataKeyName])) {
                $params[is_null($dbColumn) ? $updateDataKeyName : $dbColumn] = (bool)$updateData[$updateDataKeyName];
                switch ($updateDataKeyName) {
                    // dada同城配送的验证处理
                    case "is_dada":
                        $this->checkLocalDelivery($companyId, (int)$params["is_data"], $params);
                        break;
                }
            }
        }
        // 获取店铺状态
        if (isset($updateData["status"])) {
            $params["is_valid"] = self::getIsValidByStatus((int)$updateData["status"]);
        }
        if (empty($params)) {
            return $distributorInfo;
        } else {
            try {
                $result = parent::updateDetail($filter, $params);
            } catch (ResourceException $exception) {
                throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
            }
            (new \DistributionBundle\Services\DistributorService())->dispatchEventsWhenUpdate($result);
            return $result;
        }
    }

    /**
     * 获取二维码的图片url
     * @param int $companyId 企业id
     * @param string $wxaAppid 微信小程序的appid
     * @param int $distributorId 店铺id
     * @return array
     */
    public function getQRCodeUrl(int $companyId, string $wxaAppid, int $distributorId): array
    {
        $cacheService = new RedisCacheService($companyId, "distributor_qr_code");
        // 获取上文件的配置信息
        $tokenService = UploadTokenFactoryService::create("image");
        $tokenInfo = $tokenService->getToken($companyId);

        // 获取缓存内容
        $result = $cacheService->hashGet([$distributorId]);
        // 如果hash缓存里不存在
        if (!isset($result[$distributorId])) {
            $qrCodeContent = (new \DistributionBundle\Services\DistributorService())->getWxaDistributorCodeStream($wxaAppid, $distributorId, 0, "index");
            $uploadService = new UploadService($companyId, $tokenService);
            $uri = $uploadService->upload($qrCodeContent, UploadTokenAbstract::GROUP_DISTRIBUTOR_QR_CODE) ? $uploadService->getUri() : "";
            // 设置hash缓存
            $cacheService->hashSet([$distributorId => $uri]);
        } else {
            $uri = (string)$result[$distributorId];
        }
        // 获取完整的url路径
        $url = sprintf("%s/%s", trim($tokenInfo["token"]["domain"] ?? "", "/"), trim($uri, "/"));
        return [
            "url" => $url
        ];
    }
}
