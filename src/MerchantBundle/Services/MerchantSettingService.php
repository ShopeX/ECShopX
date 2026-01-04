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

namespace MerchantBundle\Services;

use Dingo\Api\Exception\ResourceException;

use MerchantBundle\Entities\MerchantType;

use CompanysBundle\Services\CompanysService;

class MerchantSettingService
{
    /**
     * @var \MerchantBundle\Repositories\MerchantTypeRepository
     */
    private $merchantTypeRepository;


    public function __construct()
    {
        $this->merchantTypeRepository = app('registry')->getManager('default')->getRepository(MerchantType::class);
    }

    /**
     * 获取商户基础设置
     * @param $companyId  企业ID
     * @return array|mixed
     */
    public function getBaseSetting($companyId)
    {
        $inputData = $this->getCompanyBaseSetting($companyId);
        $companysService = new CompanysService();
        $domainInfo = $companysService->getDomainInfo(['company_id' => $companyId]);
        $h5urlDomain = $domainInfo['h5_domain'] != "" ? $domainInfo['h5_domain'] : $domainInfo['h5_default_domain'];
        $inputData['h5url'] = 'https://'.$h5urlDomain . '/subpages/merchant/login';
        return $inputData;
    }

    public function getCompanyBaseSetting($companyId)
    {
        $default = [
            'status'       => 'false',// true:开启 false:关闭
            'display_on_pc' => 'false',
            'settled_type' => [],// array 多选 enterprise:企业 soletrader:个体户
            'content'      => '',// 入驻协议内容
        ];
        $key = 'settlementAgreement:' . $companyId;
        $result = app('redis')->connection('companys')->get($key);
        $result = $result ? json_decode($result, true) : $default;
        $result = array_merge($default, $result);
        $result['status'] = !($result['status'] == 'false');
        return $result;
    }

    /**
     * 设置商户基础设置
     * @param $companyId  企业ID
     * @param $inputdata  保存数据
     * @return bool
     */
    public function setBaseSetting($companyId, $inputdata)
    {
        $key = 'settlementAgreement:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($inputdata));
        return true;
    }

    /**
     * 获取所有商品类型，并递归
     */
    public function getTypeList($filter, $isShowChildren = true, $page = 1, $pageSize = 1000, $orderBy = ["sort" => "ASC", "created" => "ASC"])
    {
        $typeList = $this->merchantTypeRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        return $this->getTree($typeList['list'], 0, 0, $isShowChildren);
    }

    /**
     * 根据类型名称，查询分类，保留层级关系
     * 如果是一级，则查询一级下的所有二级
     * 如果是二级，查询它的上级
     */
    public function getTypeListByName($filter, $isShowChildren = true, $page = 1, $pageSize = 1000, $orderBy = ["sort" => "ASC", "created" => "ASC"])
    {
        $typeList = $this->merchantTypeRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$typeList['list']) {
            return [];
        }
        $typeIds = array_column($typeList['list'], 'id');
        foreach ($typeList['list'] as $key => $value) {
            $typeFilter = [
                'company_id' => $filter['company_id'],
            ];
            // 如果是一级，则查询当前类型的所有二级
            if ($value['parent_id'] == 0) {
                $typeFilter['parent_id'] = $value['id'];
            } else {
                // 如果是二级，则查询当前类型的一级
                $typeFilter['id'] = $value['parent_id'];
            }
            if (isset($filter['is_show'])) {
                $typeFilter['is_show'] = $filter['is_show'];
            }
            $list = $this->merchantTypeRepository->lists($typeFilter, '*', 1, -1, $orderBy);
            $typeIds = array_merge($typeIds, array_column($list['list'], 'id'));
        }
        $tmpFilter = [
            'company_id' => $filter['company_id'],
            'id' => $typeIds,
        ];
        $tmpList = $this->merchantTypeRepository->lists($tmpFilter, '*', $page, $pageSize, $orderBy);

        return $this->getTree($tmpList['list'], 0, 0, $isShowChildren);
    }



    /**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public function getTree($array, $pid = 0, $level = 0, $isShowChildren = true)
    {
        $list = [];
        foreach ($array as $k => $v) {
            $v['children'] = [];
            if ($v['parent_id'] == $pid) {
                $v['cur_level'] = $level;
                $v['children'] = $this->getTree($array, $v['id'], $level + 1, $isShowChildren);
                if ($v['level'] == 2) {
                    unset($v['children']);
                }
                if (!$isShowChildren && isset($v['children']) && empty($v['children'])) {
                    unset($v['children']);
                }
                $v['sort'] = intval($v['sort']);
                $list[] = $v;
            }
        }
        return $list;
    }

    /**
     * 添加商品类型
     * @param  string  $companyId 企业ID
     * @param  array  $params    类型数据
     * @param  integer $level     等级
     * @param  string  $path      路径
     * @return array
     */
    public function createMerchantType($companyId, $params, $level = 1, $path = "")
    {
        $params['level'] = $level;
        $params['company_id'] = $companyId;
        $params['path'] = $path;
        $params['is_show'] = $params['is_show'] == '1' ? true : false;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $uniqueName = $this->merchantTypeRepository->getInfo(['name' => $params['name'], 'parent_id' => $params['parent_id'], 'company_id' => $companyId]);
            if ($uniqueName) {
                throw new ResourceException(trans('MerchantBundle.name_already_exists'));
            }
            if ($params['parent_id'] == 0) {
                $res = $this->merchantTypeRepository->create($params);
                $updPath = $this->merchantTypeRepository->updateOneBy(['id' => $res['id']], ['path' => $res['id']]);
                if ($res && $updPath) {
                    $result = ['status' => true];
                }
            } else {
                $parentInfo = $this->merchantTypeRepository->getInfo(['id' => $params['parent_id']]);
                if (!$parentInfo) {
                    throw new ResourceException(trans('MerchantBundle.parent_data_error_check_and_resubmit'));
                }
                if ($parentInfo['level'] >= 2) {
                    throw new ResourceException(trans('MerchantBundle.can_only_add_to_second_level'));
                }
                if ($parentInfo['parent_id'] == 0) {
                    $params['level'] = $level + 1;
                    $path = $parentInfo['path'];
                } else {
                    $params['level'] = $parentInfo['level'] + 1;
                    $path = $parentInfo['path'];
                }
                $res = $this->merchantTypeRepository->create($params);
                $updPath = $this->merchantTypeRepository->updateOneBy(['id' => $res['id']], ['path' => $path.','.$res['id']]);
                if ($res && $updPath) {
                    $result = ['status' => true];
                }
            }

            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 修改单条商户类型信息
     * @param  array $filter 修改条件
     * @param  array $params 要修改的数据
     */
    public function updateMerchantType($filter, $params)
    {
        if (isset($params['parent_id'])) {
            unset($params['parent_id']);
        }
        $typeInfo = $this->getInfoById($filter['id']);
        if (!$typeInfo) {
            throw new ResourceException(trans('MerchantBundle.data_not_exist'));
        }
        $params['is_show'] = $params['is_show'] == '1' ? true : false;
        $tmpFilter = [
            'id|neq' => $filter['id'],
            'company_id' => $filter['company_id'],
            'name' => $params['name'],
            'parent_id' => $typeInfo['parent_id'],
        ];
        $tmpList = $this->lists($tmpFilter, '*', 1, -1);
        if ($tmpList['list']) {
            throw new ResourceException(trans('MerchantBundle.name_already_exists'));
        }
        return $this->updateOneBy($filter, $params);
    }

    /**
     * 删除商户类型
     *
     * @param array filter
     * @return bool
     */
    public function deleteMerchantType($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 检查类型下是否有入驻申请或商户数据
            $ids = $this->getMerchantApplyIds($filter['company_id'], $filter['id']);
            if ($ids) {
                throw new ResourceException(trans('MerchantBundle.category_has_merchants_check_and_retry'));
            }
            // 删除当前商品类型
            $result = $this->deleteBy(['id' => $filter['id'], 'company_id' => $filter['company_id']]);
            $resultChild = true;
            // 查询是否有下级类型
            $resultChildList = $this->lists(['parent_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            // 删除下级类型
            if ($resultChildList['total_count'] > 0) {
                $resultChild = $this->deleteBy(['parent_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            }
            if ($result && $resultChild) {
                $conn->commit();
                return true;
            } else {
                throw new ResourceException(trans('MerchantBundle.delete_failed'));
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 根据商户类型ID，查询在使用的商户入驻或商户数据
     * @param  string $companyId 企业ID
     * @param  string $typeId    商户类型ID
     */
    public function getMerchantApplyIds($companyId, $typeId)
    {
        $typeIds = $this->getTypeIds($companyId, $typeId);
        if ($typeIds) {
            // 查询商户入驻申请
            $settlementApplyService = new MerchantSettlementApplyService();
            $filter['company_id'] = $companyId;
            $filter['merchant_type_id'] = $typeIds;
            $data = $settlementApplyService->lists($filter);
            if ($data['list']) {
                $settlementApplyIds = array_column($data['list'], 'id');
                return $settlementApplyIds;
            }
            // 检查是否有商户数据
            $merchantService = new MerchantService();
            $merchantList = $merchantService->lists($filter);
            if ($merchantList['list']) {
                $merchantIds = array_column($merchantList['list'], 'id');
                return $merchantIds;
            }
        }
        return [];
    }

    /**
     * 根据商品类型ID,查询所有下级，返回当前类型和所有的下级类型
     * @param  string $companyId 企业ID
     * @param  string $typeId    类型ID
     * @return array            类型ID
     */
    public function getTypeIds($companyId, $typeId)
    {
        if (is_array($typeId)) {
            $ids = $typeId;
        } else {
            $ids[] = $typeId;
        }
        $parentTypeList = $this->lists(['parent_id' => $typeId, 'company_id' => $companyId]);
        if ($parentTypeList['total_count'] > 0) {
            $tmpIds = array_column($parentTypeList['list'], 'id');
            $ids = array_merge($ids, $tmpIds);
        }

        return $ids;
    }

    /**
     * 根据商户类型id，查询当前类型名称和上级类型数据
     * 如果没有上级类型，上级id和名称返回空
     * @param  string $companyId 企业ID
     * @param  string $typeId    商户类型ID(一级ID或二级ID)
     * @return [type]            [description]
     */
    public function getTypeNameById($companyId, $typeId)
    {
        $typeInfo = $this->getInfo(['company_id' => $companyId, 'id' => $typeId]);
        if (!$typeInfo) {
            throw new ResourceException(trans('MerchantBundle.business_scope_data_query_failed'));
        }
        $result = [
            'merchant_type_parent_id' => '',
            'merchant_type_parent_name' => '',
        ];
        $result['merchant_type_name'] = $typeInfo['name'];

        if ($typeInfo['level'] == '1') {
            return $result;
        }
        $parentTypeInfo = $this->getInfo(['company_id' => $companyId, 'id' => $typeInfo['parent_id']]);
        if (!$parentTypeInfo) {
            throw new ResourceException(trans('MerchantBundle.merchant_type_data_query_failed'));
        }
        $result['merchant_type_parent_id'] = intval($typeInfo['parent_id']);
        $result['merchant_type_parent_name'] = $parentTypeInfo['name'];
        return $result;
    }

    /**
     * 返回可见的商户类型列表
     * 如果是一级，只返回有二级的
     * @param  [type]  $filter   [description]
     * @param  string  $cols     [description]
     * @param  integer $page     [description]
     * @param  integer $pageSize [description]
     * @param  array   $orderBy  [description]
     * @return [type]            [description]
     */
    public function getVisibleTypeList($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result = $this->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (intval($filter['parent_id']) > 0 || $result['total_count'] <= 0) {
            return $result;
        }
        // 获取所有的一级，去检查一级下是否有可见的二级
        $firstList = $this->lists($filter);
        $parentIds = array_column($firstList['list'], 'id');
        $childrenFilter = [
            'company_id' => $filter['company_id'],
            'parent_id' => $parentIds,
            'is_show' => true,
        ];
        $childrenList = $this->lists($childrenFilter);
        if ($childrenList['total_count'] <= 0) {
            return ['result' => [], 'total_count' => 0];
        }
        $filter['id'] = array_column($childrenList['list'], 'parent_id');
        $result = $this->lists($filter, $cols, $page, $pageSize, $orderBy);
        return $result;
    }


    /**
     * 检查商户类型是否正确
     * 需要检查是否为叶子节点
     * @param  string $companyId 企业ID
     * @param  string $typeId    商户类型ID(一级或二级ID)
     * @return [type]            [description]
     */
    public function __checkMerchantType($companyId, $typeId)
    {
        $filter = [
            'company_id' => $companyId,
            'id' => $typeId,
            'is_show' => true,
        ];
        $merchantTypeInfo = $this->getInfo($filter);
        if (!$merchantTypeInfo) {
            throw new ResourceException(trans('MerchantBundle.business_scope_error_confirm_and_resubmit'));
        }
        // 检查一级商户类型是否有二级
        if ($merchantTypeInfo['level'] == '1') {
            $filter = [
                'company_id' => $companyId,
                'parent_id' => $merchantTypeInfo['id'],
                'is_show' => true,
            ];
            $firstMerchantTypeCount = $this->count($filter);
            if ($firstMerchantTypeCount > 0) {
                throw new ResourceException(trans('MerchantBundle.business_scope_error_confirm_and_resubmit'));
            }
        }
        return true;
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
        return $this->merchantTypeRepository->$method(...$parameters);
    }
}
