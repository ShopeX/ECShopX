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

namespace ThemeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use ThemeBundle\Entities\ThemePcTemplate;
use ThemeBundle\Entities\ThemePcTemplateContent;
use GoodsBundle\Services\ItemsService;

class ThemePcTemplateContentServices
{
    private $themePcTemplateRepository;
    private $themePcTemplateContentRepository;

    public function __construct()
    {
        // $this->themePcTemplateRepository = app('registry')->getManager('default')->getRepository(ThemePcTemplate::class);
        // $this->themePcTemplateContentRepository = app('registry')->getManager('default')->getRepository(ThemePcTemplateContent::class);
        $this->themePcTemplateRepository = getRepositoryLangue(ThemePcTemplate::class);
        $this->themePcTemplateContentRepository = getRepositoryLangue(ThemePcTemplateContent::class);
    }

    /**
     * 头尾部保存
     */
    public function save($params)
    {
        $company_id = $params['company_id'];
        $page_name = $params['page_name'];
        $config = $params['config'];
        //查询头部、尾部是否存在
        $filter = [
            'company_id' => $company_id,
            'name' => $page_name,
        ];
        $info = $this->themePcTemplateContentRepository->getInfo($filter);
        if (empty($info)) {
            $data = [
                'company_id' => $company_id,
                'theme_pc_template_id' => 0,
                'name' => $page_name,
                'params' => $config
            ];
            $result = $this->themePcTemplateContentRepository->create($data);
        } else {
            $data = [
                'params' => $config
            ];
            $result = $this->themePcTemplateContentRepository->updateOneBy($filter, $data);
        }

        return $result;
    }

    /**
     * @param $params
     * @return mixed
     *
     * 模板内容详情-头部尾部数据查询
     */
    public function detail($params)
    {
        $company_id = $params['company_id'];
        $page_name = $params['page_name'];
        $filter = [
            'company_id' => $company_id,
            'name' => $page_name,
        ];
        $result = $this->themePcTemplateContentRepository->getInfo($filter);

        return $result;
    }

    /**
     * 获取模版内容
     */
    public function templateContent($params)
    {
        $company_id = $params['company_id'];
        $theme_pc_template_id = $params['theme_pc_template_id'];
        $filter = [
            'company_id' => $company_id,
        ];
        if (!empty($theme_pc_template_id)) {
            $filter['theme_pc_template_id'] = $theme_pc_template_id;
        } else {
            $filter['page_type'] = $params['page_type'];
            $filter['status'] = 1;
        }

        $data = [];
        $theme_pc_template_info = $this->themePcTemplateRepository->getInfo($filter);
        if (empty($theme_pc_template_info)) {
            return $data;
        }

        $_filter = [
            'theme_pc_template_id' => $theme_pc_template_info['theme_pc_template_id']
        ];
        $list = $this->themePcTemplateContentRepository->getLists($_filter, '*', 1, -1, ['theme_pc_template_content_id' => 'ASC']);
        if (empty($list)) {
            return $data;
        }

        foreach ($list as $value) {
            $config = json_decode($value['params'], true);
            $config = $this->setItemPrice($params['company_id'], $params['user_id'] ?? 0, $config);
            $config = $this->setItemCategory($config);
            $data[] = [
                'id' => $value['theme_pc_template_content_id'],
                'name' => $value['name'],
                'config' => json_encode($config),
            ];
        }

        return $data;
    }

    private function setItemCategory($config)
    {
        if ($config['type'] == 'W0007') {
            if (isset($config['categoryData']) && is_array($config['categoryData'])) {
                foreach ($config['categoryData'] as $key1 => $lv1) {
                    if (isset($lv1['children'])) {
                        foreach ($lv1['children'] as $key2 => $lv2) {
                            if (isset($lv2['children'])) {
                                foreach ($lv2['children'] as $key3 => $lv3) {
                                    $lv2['children'][$key3] = [
                                        'category_id' => $lv3['category_id'],
                                        'category_name' => $lv3['category_name'],
                                    ];
                                }
                                $lv1['children'][$key2] = [
                                    'category_id' => $lv2['category_id'],
                                    'category_name' => $lv2['category_name'],
                                    'children' => $lv2['children'],
                                ];
                            } else {
                                $lv1['children'][$key2] = [
                                    'category_id' => $lv2['category_id'],
                                    'category_name' => $lv2['category_name'],
                                ];
                            }
                        }

                        $config['categoryData'][$key1] = [
                            'category_id' => $lv1['category_id'],
                            'category_name' => $lv1['category_name'],
                            'image_url' => $lv1['image_url'],
                            'children' => $lv1['children'],
                        ];
                    } else {
                        $config['categoryData'][$key1] = [
                            'category_id' => $lv1['category_id'],
                            'category_name' => $lv1['category_name'],
                            'image_url' => $lv1['image_url'],
                        ];
                    }
                }
            }
        }

        if ($config['type'] == 'W0006') {
            foreach ($config['childWidgets'] as $key => $val) {
                $config['childWidgets'][$key] = $this->setItemCategory($val);
            }
        }

        return $config;
    }

    private function setItemPrice($companyId, $userId, $config)
    {
        if ($config['type'] == 'W0002') {
            $itemIds = array_column($config['data'], 'goods_id');
            $priceArr = $this->getItemPrice($companyId, $userId, $itemIds);
            foreach ($config['data'] as $key => $val) {
                $config['data'][$key]['price'] = $priceArr[$val['goods_id']] ?? $val['price'];
            }
        }

        if ($config['type'] == 'W0005') {
            foreach ($config['data'] as $key => $val) {
                $itemIds = array_column($val['data'], 'goods_id');
                $priceArr = $this->getItemPrice($companyId, $userId, $itemIds);
                foreach ($val['data'] as $k => $v) {
                    $config['data'][$key]['data'][$k]['price'] = $priceArr[$v['goods_id']] ?? $v['price'];
                }
            }
        }

        if ($config['type'] == 'W0012') {
            foreach ($config['data'] as $key => $val) {
                foreach ($val as $k => $v) {
                    if (($v['type'] ?? '') == 'goods') {
                        $itemIds = array_column($v['data'], 'goods_id');
                        $priceArr = $this->getItemPrice($companyId, $userId, $itemIds);
                        foreach ($v['data'] as $m => $n) {
                            $config['data'][$key][$k]['data'][$m]['price'] = $priceArr[$n['goods_id']] ?? $n['price'];
                        }
                    }
                }
            }
        }

        if ($config['type'] == 'W0015') {
            foreach ($config['data'] as $key => $val) {
                $itemIds = array_column($val['data'], 'goods_id');
                $priceArr = $this->getItemPrice($companyId, $userId, $itemIds);
                foreach ($val['data'] as $k => $v) {
                    $config['data'][$key]['data'][$k]['price'] = $priceArr[$v['goods_id']] ?? $v['price'];
                }
            }
        }

        if ($config['type'] == 'W0018') {
            foreach ($config['data'] as $key => $val) {
                $itemIds = array_column($val['data'], 'goods_id');
                $priceArr = $this->getItemPrice($companyId, $userId, $itemIds);
                foreach ($val['data'] as $k => $v) {
                    $config['data'][$key]['data'][$k]['price'] = $priceArr[$v['goods_id']] ?? $v['price'];
                }
            }
        }

        if ($config['type'] == 'W0006') {
            foreach ($config['childWidgets'] as $key => $val) {
                $config['childWidgets'][$key] = $this->setItemPrice($companyId, $userId, $val);
            }
        }

        return $config;
    }

    private function getItemPrice($companyId, $userId, $itemIds)
    {
        $itemfilter['company_id'] = $companyId;
        $itemfilter['item_id'] = $itemIds;
        $itemsService = new ItemsService();
        $list = $itemsService->getItemsList($itemfilter);

        $list = $itemsService->getItemsListMemberPrice($list, $userId, $companyId);
        //营销标签-PC端暂时没有团购、秒杀等活动
        $list = $itemsService->getItemsListActityTag($list, $companyId);
        //计算税费、税率-PC端暂时没有跨境商品
        /*$ItemTaxRateService = new ItemTaxRateService($companyId);
        foreach ($list['list'] as $key => $value) {
            // 判断是否跨境，如果是，获取税费税率
            if ($value['type'] == '1') {
                $tax_calculation = 'price';                       // 计税
                $tax_calculation_price = $value['price'];         // 计税价格

                // 是否有会员价格，如果有覆盖计税价格
                if ($value['member_price'] ?? 0) {
                    $tax_calculation = 'member_price';                   // 计税
                    $tax_calculation_price = $value['member_price'];
                }
                // 是否有活动价格，如果有覆盖计税价格
                if ($value['activity_price'] ?? 0) {
                    $tax_calculation = 'activity_price';                   // 计税
                    $tax_calculation_price = $value['activity_price'];
                }

                $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($value['item_id'], $tax_calculation_price);      // 税率信息
                $cross_border_tax = bcdiv(bcdiv(bcmul($tax_calculation_price, bcmul($ItemTaxRate['tax_rate'], 100)), 100), 100);  // 税费计算
                $list['list'][$key]['cross_border_tax'] = $cross_border_tax;  // 税费
                $list['list'][$key]['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];  // 税率
                $list['list'][$key][$tax_calculation] = bcadd($value[$tax_calculation], $cross_border_tax); // 含税价格(列表显示的价格)
                if ($tax_calculation == 'activity_price') {
                    $list['list'][$key]['promotion_activity'][count($list['list'][$key]['promotion_activity'])-1]['activity_price'] = $list['list'][$key][$tax_calculation];
                }
            } else {
                $list['list'][$key]['cross_border_tax'] = 0;  // 税费
                $list['list'][$key]['cross_border_tax_rate'] = 0; // 税率
            }
        }*/

        $result = [];
        foreach ($list['list'] as $value) {
            $result[$value['item_id']] = $value['price'];

            if ($value['member_price'] ?? 0) {
                $result[$value['item_id']] = $value['member_price'];
            }

            if ($value['activity_price'] ?? 0) {
                $result[$value['item_id']] = $value['activity_price'];
            }
        }

        return $result;
    }

    /**
     * @param $params
     * @return mixed
     *
     * 添加模板内容
     */
    public function addTemplateContent($params)
    {
        $company_id = $params['company_id'];
        $theme_pc_template_id = $params['theme_pc_template_id'];
        $config = json_decode($params['config'], true);
        if (!is_array($config)) {
            throw new ResourceException('页面装修内容不合法');
        }

        //判断页面模板是否存在
        $theme_pc_template_info = $this->themePcTemplateRepository->getInfoById($theme_pc_template_id);
        if (empty($theme_pc_template_info)) {
            throw new ResourceException('页面不存在');
        }

        //删除页面模板内容
        $filter = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id
        ];
        // $this->themePcTemplateContentRepository->deleteBy($filter);
        $ids = []; 
        $themePcTemplateContentRepositoryOld = app('registry')->getManager('default')->getRepository(ThemePcTemplateContent::class);
        $lists = $themePcTemplateContentRepositoryOld->lists($filter);
        if (!empty($lists['list'])) {
            $ids = array_column($lists['list'], 'theme_pc_template_content_id');
        }
        $editId = [];
        foreach ($config as $key => $row) {
            $id = $row['id'] ?? 0;
            if ($id > 0) {
                $config_params = json_encode($row, JSON_UNESCAPED_UNICODE);
                $data = [
                    'company_id' => $company_id,
                    'theme_pc_template_id' => $theme_pc_template_id,
                    'name' => '',
                    'params' => $config_params,
                    'sort_by' => $key + 1
                ];
                $this->themePcTemplateContentRepository->updateOneBy(['theme_pc_template_content_id' => $id], $data);
            $editId[] = $id;
            }else {
                $config_params = json_encode($row, JSON_UNESCAPED_UNICODE);
                $data = [
                    'company_id' => $company_id,
                    'theme_pc_template_id' => $theme_pc_template_id,
                    'name' => '',
                    'params' => $config_params,
                    'sort_by' => $key + 1
                ];
                $result = $this->themePcTemplateContentRepository->create($data);
                $editId[] = $result['theme_pc_template_content_id'];
                // 重新把id写回去
                $newRow = $row;
                $newRow['id'] = $result['theme_pc_template_content_id'];
                $config_params = json_encode($newRow, JSON_UNESCAPED_UNICODE);
                $data = [
                    'params' => $config_params
                ];
                $this->themePcTemplateContentRepository->updateOneBy(['theme_pc_template_content_id' => $newRow['id']], $data);
            }
        }
        $diffIds = array_diff($ids, $editId);
        if (!empty($diffIds)) {
            $filter['theme_pc_template_content_id'] = $diffIds;
            $this->themePcTemplateContentRepository->deleteBy($filter);
        }

        return true;
    }
}
