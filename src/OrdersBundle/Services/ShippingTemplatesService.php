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

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\AddressService;
use OrdersBundle\Entities\ShippingTemplates;
use OrdersBundle\Repositories\ShippingTemplatesRepository;
use WechatBundle\Services\WeappService;
use GoodsBundle\Services\ItemsService;

class ShippingTemplatesService
{
    // 包邮
    public const STATUS_FREE = 1;
    // 运费状态
    public const STATUS_FEE = 0;
    // 计价方式按重量
    public const STATUS_VALUATION_WEIGHT = 1;
    // 计价方式按件数
    public const STATUS_VALUATION_NUMBER = 2;
    // 计价方式按金额
    public const STATUS_VALUATION_MONEY = 3;
    // 计价方式按体积
    public const STATUS_VALUATION_VOLUME = 4;
    // 模板开启
    public const STATUS_OPEN = 1;
    // 模板关闭
    public const STATUS_DISABLE = 0;

    public const BC_VOLUME_SCALE = 4;

    /**
     * @var $shippingTemplatesRepository ShippingTemplatesRepository
     */
    private $shippingTemplatesRepository;

    public function __construct()
    {
        // $this->shippingTemplatesRepository = app('registry')->getManager('default')->getRepository(ShippingTemplates::class);
        $this->shippingTemplatesRepository = getRepositoryLangue(ShippingTemplates::class);
    }

    /**
     * 根据模板id获取运费模板信息
     * @param $templateId 运费模板id
     * @param $companyId 商家id
     * @return mixed
     */
    public function getInfo($templateId, $companyId)
    {
        return $this->shippingTemplatesRepository->getInfo(['template_id' => $templateId, 'company_id' => $companyId]);
    }

    /**
     * 根据模板名称获取运费模板信息
     * @param $templateName 运费模板名称
     * @param $companyId 商家id
     * @return mixed
     */
    public function getInfoByName($templateName, $companyId)
    {
        return $this->shippingTemplatesRepository->getInfo(['name' => $templateName, 'company_id' => $companyId]);
    }

    /**
     * 获取运费模板列表
     * @param $filter where条件
     * @param $orderBy 排序条件
     * @param $page 当前页数
     * @param $pageSize 分页条数
     * @return mixed
     */
    public function getList($filter, $orderBy, $page = 1, $pageSize = 100)
    {
        return $this->shippingTemplatesRepository->lists($filter, "*", $page, $pageSize, $orderBy = ["create_time" => "DESC"]);
    }

    /**
     * 创建运费模板
     * @param $data 运费模板数据
     * @return mixed
     */
    public function createShippingTemplates($data)
    {
        $info = $this->formatCityData($data);

        $filter = [
            'name' => $data['name'],
            'company_id' => $data['company_id'],
            'distributor_id' => $data['distributor_id'],
            'supplier_id' => $data['supplier_id'],
        ];
        if ($this->shippingTemplatesRepository->count($filter) > 0) {
            throw new ResourceException(trans('OrdersBundle/Order.shipping_template_exists'));
        }
        return $this->shippingTemplatesRepository->create($info);
    }

    /**
     * 修改运费模板
     * @param $templateId 运费模板id
     * @param $companyId 商家id
     * @param $data 运费模板数据
     * @return mixed
     */
    public function updateShippingTemplates($templateId, $companyId, $data)
    {
        $shippingTemplatesInfo = $this->shippingTemplatesRepository->getInfo(['template_id' => $templateId, 'company_id' => $companyId, 'distributor_id' => $data['distributor_id'], 'supplier_id' => $data['supplier_id']]);
        if (!$shippingTemplatesInfo) {
            throw new ResourceException(trans('OrdersBundle/Order.shipping_template_not_created'));
        }
        $filter = [
            'name' => $data['name'],
            'company_id' => $shippingTemplatesInfo['company_id'],
            'distributor_id' => $shippingTemplatesInfo['distributor_id'],
            'supplier_id' => $shippingTemplatesInfo['supplier_id'],
        ];
        if ($data['name'] != $shippingTemplatesInfo['name'] && $this->shippingTemplatesRepository->getInfo($filter)) {
            throw new ResourceException(trans('OrdersBundle/Order.shipping_template_exists'));
        }
        if ($shippingTemplatesInfo['valuation'] != $data['valuation']) {
            throw new ResourceException(trans('OrdersBundle/Order.shipping_method_cannot_change'));
        }
        unset($shippingTemplatesInfo['valuation']);
        $info = $this->formatCityData($data);
        return $this->shippingTemplatesRepository->updateOneBy(['template_id' => $templateId], $info);
    }

    /**
     * 删除运费模板
     * @param $templateId 运费模板id
     * @param $companyId 商家id
     * @return mixed
     */
    public function deleteShippingTemplates($templateId, $companyId, $distributorId, $supplierId)
    {
        $this->template_rel_items(['templates_id' => $templateId, 'company_id' => $companyId]);
        return $this->shippingTemplatesRepository->deleteBy(['template_id' => $templateId, 'company_id' => $companyId, 'distributor_id' => $distributorId, 'supplier_id' => $supplierId]);
    }

    public function template_rel_items($filter)
    {
        $itemsService = new ItemsService();
        $count = $itemsService->count($filter);
        if ($count > 0) {
            throw new ResourceException(trans('OrdersBundle/Order.has_related_items_cannot_delete'));
        }
    }

    /**
     * 格式化运费模板的信息
     * @param $data
     * @return mixed
     */
    private function formatCityData($params)
    {
        $rules = [
            'is_free' => ['in:0,1', '是否包邮参数存在问题'],
            'name' => ['required', '模板名称不能为空!'],
            'status' => ['in:0,1', '是否启用参数存在问题'],
            'valuation' => ['in:1,2,3,4', '计价方式参数存在问题']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        try {
            if (isset($params['company_id'])) {
                $info['company_id'] = $params['company_id'];
            }

            $info['distributor_id'] = $params['distributor_id'];
            $info['supplier_id'] = $params['supplier_id'];
            $info['is_free'] = $params['is_free'];
            $info['name'] = $params['name'];
            $info['status'] = $params['status'];
            $info['valuation'] = $params['valuation'];
            $info['nopost_conf'] = json_encode(is_array($params['nopost_conf']) ? $params['nopost_conf'] : []);
            if (1 == $info['is_free']) {
                return $info;
            }
            $info['free_conf'] = [];
            $info['fee_conf'] = [];
            if (in_array($params['valuation'], [self::STATUS_VALUATION_WEIGHT, self::STATUS_VALUATION_NUMBER, self::STATUS_VALUATION_VOLUME])) {
                foreach ($params['fee_conf'] as $k => $v) {
                    if ($k == 0) {
                        if ($v['add_fee'] == "" || $v['add_standard'] == "") {
                            throw new ResourceException(trans('OrdersBundle/Order.default_shipping_fee_required'));
                        }
                        if ($v['start_fee'] == "" || $v['start_standard'] == "") {
                            throw new ResourceException(trans('OrdersBundle/Order.additional_shipping_fee_required'));
                        }
                        $info['fee_conf'][] = [
                            'add_fee' => $v['add_fee'],
                            'add_standard' => $v['add_standard'],
                            'start_fee' => $v['start_fee'],
                            'start_standard' => $v['start_standard']
                        ];
                    } else {
                        $info['fee_conf'][] = [
                            'area' => $v['area'],
                            'add_fee' => $v['add_fee'],
                            'add_standard' => $v['add_standard'],
                            'start_fee' => $v['start_fee'],
                            'start_standard' => $v['start_standard']
                        ];
                    }
                }
            }
            if (self::STATUS_VALUATION_WEIGHT == $params['valuation'] && isset($params['free_conf'])) {
                foreach ($params['free_conf'] as $k => $v) {
                    if (!in_array($v['freetype'], [1, 2, 3])) {
                        throw new ResourceException(trans('OrdersBundle/Order.free_shipping_condition_error'));
                    }
                    if ($k == 0) {
                        $info['free_conf'][$k] = [
                            'freetype' => $v['freetype'],
                            'inweight' => $v['inweight'],
                            'upmoney' => $v['upmoney']
                        ];
                    } else {
                        if (!is_array($v['area'])) {
                            throw new ResourceException(trans('OrdersBundle/Order.area_format_error'));
                        }
                        $info['free_conf'][$k] = [
                            'area' => $v['area'],
                            'freetype' => $v['freetype'],
                            'inweight' => $v['inweight'],
                            'upmoney' => $v['upmoney']
                        ];
                    }
                }
            }
            if (self::STATUS_VALUATION_NUMBER == $params['valuation'] && isset($params['free_conf'])) {
                foreach ($params['free_conf'] as $k => $v) {
                    if (!in_array($v['freetype'], [1, 2, 3])) {
                        throw new ResourceException(trans('OrdersBundle/Order.free_shipping_condition_error'));
                    }
                    if ($k == 0) {
                        $info['free_conf'][$k] = [
                            'freetype' => $v['freetype'],
                            'upquantity' => $v['upquantity'],
                            'upmoney' => $v['upmoney']
                        ];
                    } else {
                        if (!is_array($v['area'])) {
                            throw new ResourceException(trans('OrdersBundle/Order.area_format_error'));
                        }
                        $info['free_conf'][$k] = [
                            'area' => $v['area'],
                            'freetype' => $v['freetype'],
                            'upquantity' => $v['upquantity'],
                            'upmoney' => $v['upmoney']
                        ];
                    }
                }
            }
            if (self::STATUS_VALUATION_MONEY == $params['valuation'] && isset($params['fee_conf'])) {
                foreach ($params['fee_conf'] as $k => $v) {
                    if ($k == 0) {
                        $info['fee_conf'][$k] = [
                            'rules' => []
                        ];
                    } else {
                        if (!is_array($v['area'])) {
                            throw new ResourceException(trans('OrdersBundle/Order.area_format_error'));
                        }
                        $info['fee_conf'][$k] = [
                            'area' => $v['area'],
                            'rules' => []
                        ];
                    }
                    foreach ($v['rules'] as $k1 => $v1) {
                        if ($v1['down'] != '' && $v1['down'] <= $v1['up']) {
                            throw new ResourceException(trans('OrdersBundle/Order.shipping_fee_range_invalid'));
                        }
                        $info['fee_conf'][$k]['rules'][] = [
                            'up' => $v1['up'],
                            'down' => $v1['down'],
                            'basefee' => $v1['basefee'] ?: 0,
                        ];
                    }
                }
            }
            if (self::STATUS_VALUATION_VOLUME == $params['valuation'] && isset($params['free_conf'])) {
                foreach ($params['free_conf'] as $k => $v) {
                    if (!in_array($v['freetype'], [1, 2, 3])) {
                        throw new ResourceException(trans('OrdersBundle/Order.free_shipping_condition_error'));
                    }
                    if ($k == 0) {
                        $info['free_conf'][$k] = [
                            'freetype' => $v['freetype'],
                            'upmoney' => $v['upmoney'],
                            'upvolume' => $v['upvolume']
                        ];
                    } else {
                        if (!is_array($v['area'])) {
                            throw new ResourceException(trans('OrdersBundle/Order.area_format_error'));
                        }
                        $info['free_conf'][$k] = [
                            'area' => $v['area'],
                            'freetype' => $v['freetype'],
                            'upmoney' => $v['upmoney'],
                            'upvolume' => $v['upvolume']
                        ];
                    }
                }
            }
            $info['fee_conf'] = json_encode($info['fee_conf']);
            $info['free_conf'] = json_encode($info['free_conf']);

            return $info;
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 计算订单运费规则
     * @param $orderGoodsList 商品信息
     * @param array $area 地区 ['省份标识', '城市或区域标识']
     * @return int|string
     */
    public function countFreightFee($orderGoodsList, $companyId, $area = ['', '', ''], $isCheck = true, &$supplierFreightFee = [])
    {
        $weappService = new WeappService();
//        $templateid = $weappService->getTemplateidByTemplateName($companyId);
//        // todo 兼容老版小程序
//        if (empty($templateid) || $templateid > intval(config('common.address_template_id'))) {
        $this->getLocalRegionV2($area[0], $area[1], $area[2]);
//        } else {
//            $this->getLocalRegion($area[0], $area[1], $area[2]);
//        }
        $shippingCount = [];
        foreach ($orderGoodsList as $v) {
            if ($v['templates_id']) {
                $weight = isset($v['weight']) ? $v['weight'] : 0;
                $shippingCount[$v['templates_id']]['weight'] = isset($shippingCount[$v['templates_id']]['weight']) ? $shippingCount[$v['templates_id']]['weight'] + $weight : 0 + $weight;
                $total_fee = isset($v['total_fee']) ? $v['total_fee'] : 0;
                $shippingCount[$v['templates_id']]['price'] = isset($shippingCount[$v['templates_id']]['price']) ? $shippingCount[$v['templates_id']]['price'] + $total_fee : 0 + $total_fee;
                $num = isset($v['num']) ? $v['num'] : 0;
                $shippingCount[$v['templates_id']]['num'] = isset($shippingCount[$v['templates_id']]['num']) ? $shippingCount[$v['templates_id']]['num'] + $num : 0 + $num;
                $volume = isset($v['volume']) ? $v['volume'] : 0;
                $shippingCount[$v['templates_id']]['volume'] = isset($shippingCount[$v['templates_id']]['volume']) ? bcadd($shippingCount[$v['templates_id']]['volume'], $volume, self::BC_VOLUME_SCALE) : 0 + $volume;
            }
        }

        if (!$shippingCount) {
            return 0;
        }
        if (!$area[0] || !$area[1]) {
            return 0;
        }

        $price = 0;
        foreach ($shippingCount as $k => $v) {
            $temp = $this->getInfo($k, $companyId);
            if (isset($temp['nopost_conf']) && $temp['nopost_conf'] && $isCheck) {
                $nopostConf = json_decode($temp['nopost_conf'], true);
                if ($nopostConf && array_intersect($nopostConf, $area)) {
                    throw new ResourceException(trans('OrdersBundle/Order.area_no_delivery'));
                }
            }
            if (!$temp) {
                $price += 0;
                continue;
            }
            if ($temp['status'] == self::STATUS_DISABLE) {
                continue;
            }
            if ($temp['is_free'] == self::STATUS_FEE) {
                switch ($temp['valuation']) {
                    case self::STATUS_VALUATION_WEIGHT:
                        $freightFee = $this->sumFeeByWeight($temp, $area, $v['weight'], $v['price']);
                        break;
                    case self::STATUS_VALUATION_NUMBER:
                        $freightFee = $this->sumFeeByNumber($temp, $area, $v['num'], $v['price']);
                        break;
                    case self::STATUS_VALUATION_MONEY:
                        $freightFee = $this->sumFeeByPrice($temp, $area, $v['price']);
                        break;
                    case self::STATUS_VALUATION_VOLUME:
                        $freightFee = $this->sumFeeByVolume($temp, $area, $v['volume'], $v['price']);
                        break;
                    default:
                        $freightFee = 0;
                        break;
                }

                $price += $freightFee;

                if ($temp['supplier_id'] > 0) {
                    if (isset($supplierFreightFee[$temp['supplier_id']])) {
                        $supplierFreightFee[$temp['supplier_id']] += bcmul($freightFee, 100);
                    } else {
                        $supplierFreightFee[$temp['supplier_id']] = bcmul($freightFee, 100);
                    }
                }
            }
        }
        // return $price * 100;
        return bcmul($price, 100);
    }

    /**
     * 按重量计算运费
     * @param $templatesInfo 运费模板信息
     * @param $area 地区 ['省份标识', '城市或区域标识']
     * @param $weight 重量
     * @return string
     */
    public function sumFeeByWeight($templatesInfo, $area, $weight, $price)
    {
        // 包邮计算
        $confFree = json_decode($templatesInfo['free_conf'], true);
        $fee = 0;
        foreach ($confFree as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 计算区域内运费
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                switch ($v['freetype']) {
                    case 1:
                        if ($v['inweight'] != '' && bccomp($v['inweight'], $weight, self::BC_VOLUME_SCALE) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if ($v['upmoney'] != '' && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if ($v['inweight'] != '' && $v['upmoney'] != '' && bccomp($v['inweight'], $weight, self::BC_VOLUME_SCALE) > -1 && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }

        // 按全国包邮规则
        if (isset($confFree[0]) && !empty($confFree[0])) {
            if ($confFree[0]['inweight'] > 0 || $confFree[0]['upmoney'] > 0) {
                switch ($confFree[0]['freetype']) {
                    case 1:
                        if (bccomp($confFree[0]['inweight'], $weight, self::BC_VOLUME_SCALE) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if (bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if (bccomp($confFree[0]['inweight'], $weight, self::BC_VOLUME_SCALE) > -1 && bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }
        // 运费计算
        $confFee = json_decode($templatesInfo['fee_conf'], true);
        foreach ($confFee as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 按区域计算运费规则
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                $fee += $v['start_fee'];
                if (bccomp($weight, $v['start_standard'], self::BC_VOLUME_SCALE) == 1 && $v['add_standard'] > 0) {
                    $fee += ceil(bcdiv(bcsub($weight, $v['start_standard'], self::BC_VOLUME_SCALE), $v['add_standard'], self::BC_VOLUME_SCALE)) * $v['add_fee'];
                }
                return number_format($fee, 2, '.', '');
            }
        }
        // 按全国计算运费规则
        $fee += $confFee[0]['start_fee'];
        if (bccomp($weight, $confFee[0]['start_standard'], self::BC_VOLUME_SCALE) == 1 && $confFee[0]['add_standard'] > 0) {
            $fee += ceil(bcdiv(bcsub($weight, $confFee[0]['start_standard'], self::BC_VOLUME_SCALE), $confFee[0]['add_standard'], self::BC_VOLUME_SCALE)) * $confFee[0]['add_fee'];
        }
        return number_format($fee, 2, '.', '');
    }

    /**
     * 按金额计算运费
     * @param $templatesInfo 运费模板信息
     * @param $area 地区 ['省份标识', '城市或区域标识']
     * @param $price 金额
     * @return string
     */
    public function sumFeeByPrice($templatesInfo, $area, $price)
    {
        // 格式化运费信息
        $confFee = json_decode($templatesInfo['fee_conf'], true);
        $fee = 0;
        foreach ($confFee as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 计算区域内运费
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                foreach ($v['rules'] as $v1) {
                    if (bccomp($price, bcmul($v1['up'], 100)) > -1 && ((int)$v1['down'] == 0 || bccomp(bcmul($v1['down'], 100), $price) == 1)) {
                        return number_format($v1['basefee'], 2, '.', '');
                    }
                }
            }
        }

        foreach ($confFee[0]['rules'] as $v) {
            if (bccomp($price, bcmul($v['up'], 100)) > -1 && ((int)$v['down'] == 0 || bccomp(bcmul($v['down'], 100), $price) == 1)) {
                $fee = $v['basefee'] ?: 0;
                break;
            }
        }
        return number_format($fee, 2, '.', '');
    }

    /**
     * 按件数计算运费规则
     * @param $templatesInfo 运费模板信息
     * @param $area 地区 ['省份标识', '城市或区域标识']
     * @param $number 件数
     * @param $price 金额
     * @return string 价格
     */
    public function sumFeeByNumber($templatesInfo, $area, $number, $price)
    {
        // 包邮计算
        $confFree = json_decode($templatesInfo['free_conf'], true);
        $fee = 0;
        foreach ($confFree as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 计算区域内运费
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                switch ($v['freetype']) {
                    case 1:
                        if ($v['upquantity'] != '' && bccomp($number, $v['upquantity']) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if ($v['upmoney'] != '' && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if ($v['upquantity'] != '' && $v['upmoney'] != '' && bccomp($number, $v['upquantity']) > -1 && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }

        // 按全国包邮规则
        if (isset($confFree[0]) && !empty($confFree[0])) {
            if ($confFree[0]['upquantity'] > 0 || $confFree[0]['upmoney'] > 0) {
                switch ($confFree[0]['freetype']) {
                    case 1:
                        if (bccomp($number, $confFree[0]['upquantity']) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if (bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if (bccomp($number, $confFree[0]['upquantity']) > -1 && bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }
        // 运费计算
        $confFee = json_decode($templatesInfo['fee_conf'], true);
        foreach ($confFee as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 按区域计算运费规则
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                $fee += $v['start_fee'];
                if (bccomp($number, $v['start_standard']) == 1 && $v['add_standard'] > 0) {
                    $fee += ceil(bcdiv(bcsub($number, $v['start_standard']), $v['add_standard'], self::BC_VOLUME_SCALE)) * $v['add_fee'];
                }
                return number_format($fee, 2, '.', '');
            }
        }
        // 按全国计算运费规则
        $fee += $confFee[0]['start_fee'];

        if (bccomp($number, $confFee[0]['start_standard']) == 1 && $confFee[0]['add_standard'] > 0) {
            $fee += ceil(bcdiv(bcsub($number, $confFee[0]['start_standard']), $confFee[0]['add_standard'], self::BC_VOLUME_SCALE)) * $confFee[0]['add_fee'];
        }
        return number_format($fee, 2, '.', '');
    }

    public function sumFeeByVolume($templatesInfo, $area, $volume, $price)
    {
        // 包邮计算
        $confFree = json_decode($templatesInfo['free_conf'], true);
        $fee = 0;
        foreach ($confFree as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 计算区域内运费
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                switch ($v['freetype']) {
                    case 1:
                        if ($v['upvolume'] != '' && bccomp($v['upvolume'], $volume, self::BC_VOLUME_SCALE) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if ($v['upmoney'] != '' && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if ($v['upvolume'] != '' && $v['upmoney'] != '' && bccomp($v['upvolume'], $volume, self::BC_VOLUME_SCALE) > -1 && bccomp($price, bcmul($v['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }

        // 按全国包邮规则
        if (isset($confFree[0]) && !empty($confFree[0])) {
            if ($confFree[0]['upvolume'] > 0 || $confFree[0]['upmoney'] > 0) {
                switch ($confFree[0]['freetype']) {
                    case 1:
                        if (bccomp($confFree[0]['upvolume'], $volume, self::BC_VOLUME_SCALE) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    case 2:
                        if (bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                    default:
                        if (bccomp($confFree[0]['upvolume'], $volume, self::BC_VOLUME_SCALE) > -1 && bccomp($price, bcmul($confFree[0]['upmoney'], 100)) > -1) {
                            return number_format(0, 2, '.', '');
                        }
                        break;
                }
            }
        }
        // 运费计算
        $confFee = json_decode($templatesInfo['fee_conf'], true);
        foreach ($confFee as $k => $v) {
            if ($k == 0) {
                continue;
            }
            // 按区域计算运费规则
            if (isset($v['area']) && $v['area'] && array_intersect($area, $v['area'])) {
                $fee += $v['start_fee'];
                if (bccomp($volume, $v['start_standard'], self::BC_VOLUME_SCALE) == 1 && $v['add_standard'] > 0) {
                    $fee += ceil(bcdiv(bcsub($volume, $v['start_standard'], self::BC_VOLUME_SCALE), $v['add_standard'], self::BC_VOLUME_SCALE)) * $v['add_fee'];
                }
                return number_format($fee, 2, '.', '');
            }
        }
        // 按全国计算运费规则
        $fee += $confFee[0]['start_fee'];
        if (bccomp($volume, $confFee[0]['start_standard'], self::BC_VOLUME_SCALE) == 1 && $confFee[0]['add_standard'] > 0) {
            $fee += ceil(bcdiv(bcsub($volume, $confFee[0]['start_standard'], self::BC_VOLUME_SCALE), $confFee[0]['add_standard'], self::BC_VOLUME_SCALE)) * $confFee[0]['add_fee'];
        }
        return number_format($fee, 2, '.', '');
    }

    public function getLocalRegionV2(&$province, &$city, &$region)
    {
        $addressService = new AddressService();
        $areaInfo = $addressService->getInfo(['parent_id' => 0, 'label' => $province]);
        $province = $areaInfo['id'] ?? 1;
        if ($city) {
            $cityInfo = $addressService->getInfo(['parent_id' => $areaInfo['id'], 'label' => $city]);
            if (!$cityInfo) {
                $cityInfo = $addressService->getInfo(['parent_id' => $areaInfo['id'], 'label' => str_replace(['市'], [''], $city)]);
            }
            $city = $cityInfo['id'] ?? 1;
            if ($region) {
                $regionInfo = $addressService->getInfo(['parent_id' => $cityInfo['id'], 'label' => $region]);
                if (!$regionInfo) {
                    $regionInfo = $addressService->getInfo(['parent_id' => $cityInfo['id'], 'label' => str_replace(['区'], [''], $region)]);
                }
                $region = $regionInfo['id'] ?? 1;
            } else {
                $region = 1;
            }
        } else {
            $city = 1;
        }
    }

    public function getLocalRegion(&$city, &$district, $area)
    {
        $districtList = json_decode(file_get_contents(storage_path('static/district.json')), true);
        $districtListTemp = [];
        foreach ($districtList as $v) {
            unset($v['children']);
            $districtListTemp[] = $v;
        }
        $temp = $this->array_search_re($city, $districtListTemp);
        if ($temp) {
            $city = $districtListTemp[$temp[0][1]]['value'];
            $districtTemp = $this->array_search_re(str_replace(['市', '地区', '盟'], ['', '', ''], $district), $districtList[$temp[0][1]]['children']);
            $areaTemp = $this->array_search_re(str_replace(['市', '地区', '区', '盟'], ['', '', ''], $area), $districtList[$temp[0][1]]['children']);
            $district = isset($districtTemp[1]) ? $districtList[$temp[0][1]]['children'][$districtTemp[1][1]]['value'] : (isset($areaTemp[1]) ? $districtList[$temp[0][1]]['children'][$areaTemp[1][1]]['value'] : '000000');
        }
    }

    public function array_search_re($needle, $haystack, $a = 0, $nodes_temp = array())
    {
        global $nodes_found;
        $a++;
        foreach ($haystack as $key1 => $value1) {
            $nodes_temp[$a] = $key1;
            if (is_array($value1)) {
                $this->array_search_re($needle, $value1, $a, $nodes_temp);
            } elseif ($value1 === $needle) {
                $nodes_found[] = $nodes_temp;
            }
        }
        return $nodes_found;
    }
}
