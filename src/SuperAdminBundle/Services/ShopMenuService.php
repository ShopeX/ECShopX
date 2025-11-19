<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SuperAdminBundle\Services;

use CompanysBundle\Entities\Companys;
use Dingo\Api\Exception\DeleteResourceFailedException;
use SuperAdminBundle\Entities\ShopMenu;
use Dingo\Api\Exception\StoreResourceFailedException;
use SuperAdminBundle\Entities\ShopMenuRelType;

class ShopMenuService
{

    const PLAT_TYPE = ['b2c', 'platform', 'standard', 'in_purchase'];
    const MENU_TYPE = [
        1 => 'all',
        2 => 'b2c',//品牌官网
        3 => 'platform',//ECX
        4 => 'standard',//云店
        5 => 'in_purchase'//内购
    ];

    /** @var \SuperAdminBundle\Repositories\ShopMenuRepository */
    public $shopMenuRepository;
    public $shopMenuRelTypeRepository;

    public function __construct()
    {
        // $this->shopMenuRepository = app('registry')->getManager('default')->getRepository(ShopMenu::class);
        $this->shopMenuRepository = getRepositoryLangue(ShopMenu::class);
        $this->shopMenuRelTypeRepository = app('registry')->getManager('default')->getRepository(ShopMenuRelType::class);
    }

    /**
     * 菜单类型转换
     *
     * @param array $menuType
     * @return array
     */
    private function helperConMenuType(array $menuType): array
    {
        // ShopEx EcShopX Service Component
        $indexMenuType = array_flip(self::MENU_TYPE);

        if (empty($menuType)) {
            return [$indexMenuType['all']];
        }

        $handlerData = [];

        foreach ($menuType as $value) {
            $handlerData[] = $indexMenuType[$value] ?? (string)$indexMenuType['all'];
        }

        return array_unique($handlerData);
    }

    /**
     * 检查父类菜单
     *
     * @param $parentTypeList
     * @param $sonType
     * @return bool
     */
    public function checkParentMenuType($parentTypeList, $sonType): bool
    {
        $indexMenuType = array_flip(self::MENU_TYPE);
        if (in_array($indexMenuType['all'], $parentTypeList)) {
            return true;
        }

        if (in_array($indexMenuType['all'], $sonType)) {
            throw new StoreResourceFailedException('子类菜单类型范围不能超过父类');
        }

        if (!empty(array_diff($sonType, $parentTypeList))) {
            throw new StoreResourceFailedException('子类菜单类型需在父类范围内');
        }

        return true;
    }

    /**
     * 创建菜单
     *
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function create($params)
    {
        $data = $params;
        $data['company_id'] = $params['company_id'] ?? 0;
        $data['pid'] = $params['pid'] ?? 0;
        $data['sort'] = $params['sort'] ?? 1;
        $data['version'] = $params['version'] ?? 1;
        $menuTypeList = $this->helperConMenuType($params['menu_type'] ?? []);

        if (!isset($params['shopmenu_id'])) {
            $lastInfo = $this->shopMenuRepository->lists(['company_id' => $data['company_id'] ],"*", 1, 1, ['shopmenu_id' => 'DESC']);
            $data['shopmenu_id'] = ($lastInfo['list']['0']['shopmenu_id'] ?? 0) + 1;
        }

        if (isset($data['alias_name']) && $data['alias_name']) {
            $info = $this->shopMenuRepository->getInfo(['alias_name' => $data['alias_name'], 'version' => $data['version'], 'company_id' => $data['company_id']]);
            // 已有菜单
            if (!empty($info)) {
                $where = [
                    'company_id'  => 0,
                    'shopmenu_id' => $info['shopmenu_id']
                ];
                $relList = $this->shopMenuRelTypeRepository->getLists($where);
                $relIdSet = array_column($relList, 'menu_type');

                // 更新菜单
                $indexMenuType = array_flip(self::MENU_TYPE);
                $compareTypeSet = [];
                foreach ($data['menu_type'] as $datum) {
                    $compareTypeSet[] = $indexMenuType[$datum] ?? 1;
                }

                if ($this->checkDuplicateType($relIdSet, $compareTypeSet)) {
                    throw new StoreResourceFailedException('已经相同的菜单唯一标识');
                }
            }
        } else {
            throw new StoreResourceFailedException('菜单唯一标识不能为空');
        }

        if ($data['pid'] != 0) {
            $info = $this->shopMenuRepository->getInfo(['shopmenu_id' => $data['pid'], 'company_id' => $data['company_id']]);
            if (!$info) {
                throw new StoreResourceFailedException('上级菜单不存在');
            }

            if (!$info['is_menu']) {
                throw new StoreResourceFailedException('功能菜单下不能有子菜单');
            }
            $filter = [
                'shopmenu_id' => $data['pid'],
                'company_id'  => $data['company_id']
            ];

            $relList = $this->shopMenuRelTypeRepository->getLists($filter);
            $parentTypeList = array_column($relList, 'menu_type');

            // 检查类型范围
            $this->checkParentMenuType($parentTypeList, $menuTypeList);
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $menuCreate = $this->shopMenuRepository->create($data);

            foreach ($menuTypeList as $value) {
                $createData = [
                    'shopmenu_id' => $menuCreate['shopmenu_id'],
                    'menu_type'   => $value,
                    'company_id'  => $data['company_id']
                ];
                $this->shopMenuRelTypeRepository->create($createData);
            }
            $conn->commit();
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return $menuCreate;
    }

    /**
     * 菜单删除
     *
     * @param $shopmenuId
     * @return array
     * @throws \Exception
     */
    public function deleteMenus($shopmenuId): array
    {
        $info = $this->shopMenuRepository->getInfo(['pid' => $shopmenuId, 'disabled' => 0]);
        if ($info) {
            throw new DeleteResourceFailedException("当前菜单还有子菜单，不可删除");
        }

        $filter = [
            'shopmenu_id' => $shopmenuId
        ];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->shopMenuRelTypeRepository->deleteBy($filter);
            $deleteResult = $this->shopMenuRepository->deleteBy($filter);
            $conn->commit();
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return [
            'status' => $deleteResult
        ];
    }


    /**
     * 检查是否有重复类型
     *
     * @param $typeSet
     * @param $compareTypeSet
     * @return bool
     */
    public function checkDuplicateType($typeSet, $compareTypeSet)
    {
        $indexMenuType = array_flip(self::MENU_TYPE);
        if (in_array($indexMenuType['all'], $typeSet) || in_array($indexMenuType['all'], $compareTypeSet)) {
            return true;
        }
        if (array_intersect($typeSet, $compareTypeSet)) {
            return true;
        }
        return false;
    }

    public function updateMenus($requestData)
    {
        if (!isset($requestData['shopmenu_id']) || !$requestData['shopmenu_id']) {
            throw new DeleteResourceFailedException('未传菜单ID');
        }

        if (isset($requestData['alias_name']) && $requestData['alias_name']) {
            $info = $this->shopMenuRepository->getInfo(['alias_name' => $requestData['alias_name'], 'version' => $requestData['version'], 'company_id' => 0]);
            if ($info && $info['shopmenu_id'] != $requestData['shopmenu_id']) {
                // 已有菜单
                $where = [
                    'company_id'  => 0,
                    'shopmenu_id' => $info['shopmenu_id']
                ];
                $relList = $this->shopMenuRelTypeRepository->getLists($where);
                $relIdSet = array_column($relList, 'menu_type');

                // 更新菜单
                $indexMenuType = array_flip(self::MENU_TYPE);
                $compareTypeSet = [];
                foreach ($requestData['menu_type'] as $datum) {
                    $compareTypeSet[] = $indexMenuType[$datum] ?? 1;
                }

                if ($this->checkDuplicateType($relIdSet, $compareTypeSet)) {
                    throw new StoreResourceFailedException('已经相同的菜单唯一标识');
                }
            }
            // 校验父子菜单是否合理
            $shopMenu = $this->shopMenuRepository->getInfo(['shopmenu_id' => $requestData['shopmenu_id']]);
            if (empty($shopMenu)) {
                throw new DeleteResourceFailedException('无此菜单可更改');
            }

            $menuTypeList = $this->helperConMenuType($requestData['menu_type']);

            if ($shopMenu['pid']) {
                $relList = $this->shopMenuRelTypeRepository->getLists(['shopmenu_id' => $shopMenu['pid']]);
                $parentTypeList = array_column($relList, 'menu_type');
                $this->checkParentMenuType($parentTypeList, $menuTypeList);
            }
            // 检测下面的子节点
            $childrenMenu = $this->shopMenuRepository->lists(['pid' => $requestData['shopmenu_id']]);
            if (!empty($childrenMenu['list'])) {
                $childrenIdList = array_column($childrenMenu['list'], 'shopmenu_id');
                $relList = $this->shopMenuRelTypeRepository->getLists(['shopmenu_id' => $childrenIdList]);
                $sonTypeList = array_column($relList, 'menu_type');
                $this->checkParentMenuType($menuTypeList, $sonTypeList);
            }
        }

        $updateData = [
            'shopmenu_id' => $requestData['shopmenu_id']
        ];

        unset($requestData['shopmenu_id']);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $updateResult = $this->shopMenuRepository->updateOneBy($updateData, $requestData);

            if (isset($requestData['alias_name']) && $requestData['alias_name'] && isset($menuTypeList)) {
                // 删除类型
                $this->shopMenuRelTypeRepository->deleteBy($updateData);
                foreach ($menuTypeList as $item) {
                    $createData = [
                        'shopmenu_id' => $updateData['shopmenu_id'],
                        'menu_type'   => $item,
                        'company_id'  => 0
                    ];
                    $this->shopMenuRelTypeRepository->create($createData);
                }
            }

            $conn->commit();
        }catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        return $updateResult;
    }

    public function getMaxMenuId()
    {
        $rs = $this->shopMenuRepository->lists([],"*", 1, 1, ["shopmenu_id" => "DESC"]);
        if (!$rs["list"]) {
            return 0;
        }
        return $rs["list"][0]['shopmenu_id'];
    }

    public function uploadMenus($data, $company_id = 0)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //删除原来的菜单
            if ($data) {
                $versions = array_column($data, 'version');
                $versions = array_unique($versions);
                $filter = ['version' => $versions, 'company_id' => $company_id];
                $shopMenuList = $this->shopMenuRepository->lists($filter);
                if (!empty($shopMenuList['list'])) {
                    $shopMenuIdList = array_column($shopMenuList['list'], 'shopmenu_id');
                    $this->shopMenuRepository->deleteBy(['shopmenu_id' => $shopMenuIdList]);
                    $this->shopMenuRelTypeRepository->deleteBy(['shopmenu_id' => $shopMenuIdList]);
                }
            }

            $menuIdMapping = [];//菜单新旧ID的映射关系

            foreach ($data as $row) {

                $shopmenu_id = $this->getMaxMenuId() + 1;//新菜单ID
                $menuIdMapping[$row['shopmenu_id']] = $shopmenu_id;//新旧映射关系
                if ($row['pid']) {
                    $row['pid'] = $menuIdMapping[$row['pid']] ?? 0;
                }

                $insert = [
                    'shopmenu_id' => $shopmenu_id,
                    'company_id' => $company_id,
                    'name' => $row['name'],
                    'url' => $row['url'],
                    'sort' => $row['sort'],
                    'pid' => $row['pid'],
                    'apis' => $row['apis'],
                    'icon' => $row['icon'],
                    'alias_name' => $row['alias_name'] ?? null,
                    'is_show' => $row['is_show'] ? 'true' : 'false',
                    'is_menu' => $row['is_menu'] ? 'true' : 'false',
                    'disabled' => $row['disabled'] ? 'true' : 'false',
                    'version' => $row['version'],
                    'created' => time(),
                    'updated' => time(),
                ];

                // 如果导入的数据有alias_name
                if ($insert['alias_name']) {
                    $insert['is_menu'] = $insert['is_menu'] == 'false' ? 0 : 1;
                    $insert['is_show'] = $insert['is_show'] == 'false' ? 0 : 1;
                    $insert['disabled'] = $insert['disabled'] == 'true' ? 1 : 0;

                    if (!$conn->insert('shop_menu', $insert)) {
                        throw new StoreResourceFailedException('导入失败');
                    }

                    $menuTypeList = $this->helperConMenuType($row['menu_type'] ?? []);
                    foreach ($menuTypeList as $value) {
                        $createData = [
                            'shopmenu_id' => $insert['shopmenu_id'],
                            'menu_type'   => $value,
                            'company_id'  => $company_id
                        ];
                        $this->shopMenuRelTypeRepository->create($createData);
                    }
                } else {
                    throw new StoreResourceFailedException($row['name'].'菜单唯一标识不能为空');
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new StoreResourceFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * 获取角色需要的菜单，通过子菜单推导出菜单tree
     */
    public function getRoleShopMenuTree($filter, $shopmenuAliasName)
    {
        $count = $this->count($filter);
        // 如果指定企业没有对应的菜单，那么使用通用的企业菜单
        if ($count === 0 && isset($filter['company_id'])) {
            $filter['company_id'] = 0;
        }

        $listsData = $this->shopMenuRepository->lists($filter, '*', 1, 1000, ['pid' => 'asc','sort' => 'asc']);
        if (!$listsData['total_count']) {
            return [];
        }

        $menuTypeFilter = [
            'company_id' => $filter['company_id'] ?? 0,
        ];
        $menuRelList = $this->shopMenuRelTypeRepository->getLists($menuTypeFilter);

        $menuRelIndex = [];

        foreach ($menuRelList as $item) {
            $menuRelIndex[$item['shopmenu_id']][] = self::MENU_TYPE[$item['menu_type']] ?? 'all';
        }

        foreach ($listsData['list'] as &$item) {
            $item['menu_type'] = $menuRelIndex[$item['shopmenu_id']] ?? ['all'];
        }
        unset($item);

        if ($shopmenuAliasName === null) {
            $shopmenuAliasName = array_column($listsData['list'], 'alias_name');
        }

        foreach ($listsData['list'] as $item) {
            unset($item['apis']);
            $lists[] = $item;
        }
        return $this->preMenuTree($lists, $shopmenuAliasName, 0);
    }

    private function preMenuTree($data, $shopmenuAliasName, $pid = 0, $level = 0)
    {
        $lists = array();
        $isFlag = false;
        foreach ($data as $key => $val) {
            if ($val['pid'] == $pid) {
                if (!$isFlag) {
                    $level++;
                }
                $isFlag = true;

                $val['level'] = $level;

                if (!$val['is_show']) {
                    continue;
                }
                $children = $this->preMenuTree($data, $shopmenuAliasName, $val['shopmenu_id'], $level);
                if ($children) {
                    $val['isChildrenMenu'] = in_array('true', array_column($children, 'is_menu'));

                    // if(!in_array($val['url'], array_column($children, 'url'))) {
                    //     $val['url'] = $children[0]['url'];
                    // }

                    $val['children'] = $children;
                } else {
                    if (!in_array($val['alias_name'], $shopmenuAliasName)) {
                        continue;
                    }
                }
                $lists[] = $val;
            }
        }
        return $lists;
    }

    public function getMenuTypeByCompanyId($companyId): array
    {
        if (!config('common.system_is_saas')) {
            $productModel = config('common.product_model');
            // 系统版本，standard|platform|b2c|in_purchase
            $indexMenuType = array_flip(self::MENU_TYPE);
            $menuType = $indexMenuType[$productModel];
            return [
                'menu_type'     => $menuType,
                'menu_type_str' => self::MENU_TYPE[$menuType]
            ];
        }

        $companyRep = app('registry')->getManager('default')->getRepository(Companys::class);
        $filter = [
            'company_id' => $companyId
        ];

        $companyInfo = $companyRep->getInfo($filter);
        if (empty($companyInfo)) {
            return [];
        }

        $menuType = $companyInfo['menu_type'] ?? 0;

        if (!$menuType) {
            // 如果没有值取env
            $productModel = config('common.product_model');
            // 系统版本，standard|platform|b2c|in_purchase
            $indexMenuType = array_flip(self::MENU_TYPE);
            $menuType = $indexMenuType[$productModel];
        }

        return [
            'menu_type'     => $menuType,
            'menu_type_str' => self::MENU_TYPE[$menuType]
        ];
    }

    public function helperFilterSubMenuType($menuInfo, $menuType)
    {
        if (!$menuType || $menuType == 1) {
            return $menuInfo;
        }
        $menuTypeStr = self::MENU_TYPE[$menuType];

        foreach ($menuInfo as $key => $item) {
            if(!isset($item['menu_type'])){
                continue;
            }
            $temp = array_flip($item['menu_type']);
            if (!array_key_exists($menuTypeStr, $temp) && $menuTypeStr != 'all' && !array_key_exists('all', $temp)) {
                unset($menuInfo[$key]);
                continue;
            }
            if (isset($item['children']) && is_array($item['children'])) {
                $menuInfo[$key]['children'] = $this->helperFilterSubMenuType($item['children'], $menuType);
            }
        }
        return array_values($menuInfo);
    }

    public function getShopMenu($filter = array(), $isShowParentname = true, $isShowApis = true, $menuType = 0)
    {
        $tree = $this->shopMenuRepository->getMenuTree($filter, $isShowParentname, $isShowApis);
        // 渲染上一下菜单类型
        $menuTypeFilter = [
            'company_id' => $filter['company_id'] ?? 0
        ];
        $menuRelList = $this->shopMenuRelTypeRepository->getLists($menuTypeFilter);

        $menuRelIndex = [];

        foreach ($menuRelList as $item) {
            $menuRelIndex[$item['shopmenu_id']][] = self::MENU_TYPE[$item['menu_type']] ?? 'all';
        }

        foreach ($tree['tree'] as &$item) {
            $item['menu_type'] = $menuRelIndex[$item['shopmenu_id']] ?? ['all'];
            if (isset($item['children']) && is_array($item['children'])) {
                foreach ($item['children'] as &$value) {
                    $value['menu_type'] =  $menuRelIndex[$value['shopmenu_id']] ?? ['all'];
                    if (isset($value['children']) && is_array($value['children'])) {
                        foreach ($value['children'] as &$childrenValue) {
                            $childrenValue['menu_type'] =  $menuRelIndex[$childrenValue['shopmenu_id']] ?? ['all'];
                        }
                    }
                }
            }
        }
        unset($item);
        unset($value);
        unset($childrenValue);

        foreach ($tree['list'] as $key => $item) {
            $tree['list'][$key]['menu_type'] = $menuRelIndex[$item['shopmenu_id']] ?? ['all'];
        }

        // 是否需要过滤类型
        if ($menuType) {
            $tree['tree'] = $this->helperFilterSubMenuType($tree['tree'], $menuType);
        }

        return $tree;
    }

    public function getApisByShopmenuAliasName($filter)
    {
        $data = $this->shopMenuRepository->lists($filter, '*', 1, 1000, ['shopmenu_id'=>'ASC']);
        $apis = [];
        if ($data['total_count'] > 0) {
            foreach ($data['list'] as $row) {
                if ($row['apis']) {
                    $apisStr = str_replace(array("\r", "\n", "\r\n"), ' ', $row['apis']);
                    $apisArr = explode(' ', $apisStr);
                    $apis = array_merge($apis, $apisArr);
                }
            }
        }

        return array_unique($apis);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->shopMenuRepository->$method(...$parameters);
    }
}
