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

use CompanysBundle\Services\CommonLangModService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorTagsService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemTaxRateService;
use KaquanBundle\Services\PackageQueryService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use ThemeBundle\Entities\PagesTemplate;
use ThemeBundle\Entities\PagesTemplateSet;
use WechatBundle\Entities\WeappSetting;
use EmployeePurchaseBundle\Services\ActivityItemsService;
use EmployeePurchaseBundle\Services\ActivitiesService;

class PagesTemplateServices
{
    private $pagesTemplateRepository;
    private $distributorRepository;
    private $pagesTemplateSetRepository;

    public function __construct()
    {
        // $this->pagesTemplateRepository = app('registry')->getManager('default')->getRepository(PagesTemplate::class);
        $this->pagesTemplateRepository = getRepositoryLangue(PagesTemplate::class);
        $this->pagesTemplateSetRepository = app('registry')->getManager('default')->getRepository(PagesTemplateSet::class);
    }

    /**
     * 新建模版 template_type 0 总部创建模板 1总部同步到门店模板 2店铺自主创建模板
     */
    public function create($params)
    {
        //如果$params['distributor_id'] 为0则为总部模板创建, 不为0为店铺自主创建
        if ($params['distributor_id'] == 0) {
            $params['template_type'] = 0;
        } else {
            $params['template_type'] = 2;
        }
        $company_id = $params['company_id'];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //如果模板设置信息为空，则初始化模板信息
            $_filter = [
                'company_id' => $company_id
            ];
            $pages_template_set_info = $this->pagesTemplateSetRepository->getInfo($_filter);
            if (empty($pages_template_set_info)) {
                $_set_params['index_type'] = 1;
                $_set_params['company_id'] = $company_id;
                $result = $this->pagesTemplateSetRepository->create($_set_params);
            }

            //第一个模板默认为启用状态
            $_filter = [
                'company_id' => $company_id
            ];
            $count = $this->pagesTemplateRepository->count($_filter);
            if ($count === 0) {
                $params['status'] = 1;
                $params['template_status_modify_time'] = time();
            }
            
            //保存模板
            $result = $this->pagesTemplateRepository->create($params);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }


    public function updateInfo($company_id, $pages_template_id, $params)
    {
        //保存模板数据
        $template_filter = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id
        ];
        $template_params = [
            'template_title' => $params['template_title'],
            'template_pic' => $params['template_pic'],
        ];
        $this->pagesTemplateRepository->updateOneBy($template_filter, $template_params);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function edit($params)
    {
        $company_id = $params['company_id'];
        $pages_template_id = $params['pages_template_id'];
        $template_content = json_decode($params['template_content'], true);
        $element_edit_status = $params['element_edit_status'];

        $template_name = $params['template_name'];//展示客户端名称 如小程序商城 yykweishop
        $version = 'v1.0.2';    //改版后模板值
        $page_name = 'index';     //旧模板首页参数值

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //生成模板内容数据
            if (!empty($template_content)) {
                $filter['company_id'] = $company_id;
                $filter['template_name'] = $template_name;
                $filter['page_name'] = $page_name;
                $filter['version'] = $version;
                $filter['pages_template_id'] = $pages_template_id;
                $entityRepositoryOld = app('registry')->getManager('default')->getRepository(WeappSetting::class);
                $entityRepository = getRepositoryLangue(WeappSetting::class);
                // 因为不先删除，所有要比对下保存时候的模板数据是否有变化，
                // 获取所有用户使用的模板数据
                $lists = $entityRepositoryOld->lists($filter);
                $ids = []; 
                if (!empty($lists['list'])) {
                    $ids = array_column($lists['list'], 'id');
                }
                $editId = [];
                foreach ($template_content['content'] as $key => &$row) {
                    if (!$row) continue;
                    $config_name = $row['name'];
                    $config_params = $row;
                    $id = $entityRepository->setParams($company_id, $template_name, $page_name, $config_name, $config_params, $version, $pages_template_id, $row['id'] ?? 0, ($key+1));
                    // 重置主键id,用于修改多语言数据
                    $row['id'] = $id;
                    $editId[] = $row['id'];
                }
                $diffIds = array_diff($ids, $editId);
                if (!empty($diffIds)) {
                    $filter['id'] = $diffIds;
                    $entityRepository->deleteBy($filter);
                }
            }

            //保存模板数据
            $template_filter = [
                'company_id' => $company_id,
                'pages_template_id' => $pages_template_id
            ];

            $template_params = [
                'template_content' => json_encode($template_content),
                'element_edit_status' => $element_edit_status
            ];
            $this->pagesTemplateRepository->updateOneBy($template_filter, $template_params);

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
     * 模板详情
     */
    public function detail($params)
    {
        $company_id = $params['company_id'];
        $pages_template_id = $params['pages_template_id'];
        $version = $params['version'];

        //模板数据
        $filter = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id
        ];
        $result = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($result)) {
            return [];
        }

        //模板内容数据
        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
            'template_name' => $result['template_name'],
            'version' => $version
        ];

        $list = $this->templateContent($params);
        foreach ($list as $key => $item) {
            $list[$key]['params'] = $this->templateFilter($company_id, $item['params']);
        }
        // 处理模板中的部分参数
        $this->templateHandler((int)$company_id, $list);
        $return['list'] = $list;
        $config = [];
        foreach ($list as $row) {
            if (isset($row['params']['name']) && isset($row['params']['base'])) {
                $config[] = $this->templateFilter($company_id, $row['params']);
            }
        }
        $return['config'] = $config;
        //模板内容数据赋值
        $result['template_content'] = $return;

        return $result;
    }

    private function templateFilter($companyId, $row)
    {
        $name = $row['name'];
        switch ($name) {
            case 'coupon':
                $row['data'] = $row['data'] ?? [];
                $row['voucher_package'] = $row['voucher_package'] ?? [];
                if (!empty($row['voucher_package'])) {
                    $row['voucher_package'] = $this->filterCardPackage($companyId, $row['voucher_package']);
                }
                break;
        }
        return $row;
    }

    /**
     * 处理模板中的部分参数
     * @param int $companyId
     * @param array $list 模板的列表数据
     * @return void
     */
    private function templateHandler(int $companyId, array &$list): void
    {
        foreach ($list as &$row) {
            $name = $row["name"] ?? "";
            switch ($name) {
                // 商家标签（店铺标签）
                case "nearbyShop":
                    // 如果存在商品标签，则只显示可供前端显示的标签
                    if (!empty($row["params"]["seletedTags"]) && is_array($row["params"]["seletedTags"])) {
                        $tagIds = array_column($row["params"]["seletedTags"], "tag_id");

                        $distributorTags = (new DistributorTagsService())->getListTags([
                            "company_id" => $companyId,
                            "tag_id" => $tagIds,
                            "front_show" => 1
                        ], 1, 0, [
                            sprintf("FIELD(tag_id, %s)", implode(",", $tagIds)) => "ASC"
                        ], true);
                        $row["params"]["seletedTags"] = (array)($distributorTags["list"] ?? []);
                    }
                    break;
                // 商品标签
                case "store":
                    // 如果存在商品标签，则只显示可供前端显示的标签
                    if (!empty($row["params"]["seletedTags"]) && is_array($row["params"]["seletedTags"])) {
                        $tagIds = array_column($row["params"]["seletedTags"], "tag_id");

                        $goodsTags = (new ItemsTagsService())->getListTags([
                            "company_id" => $companyId,
                            "tag_id" => $tagIds,
                            "front_show" => 1
                        ], 1, 0, [
                            sprintf("FIELD(tag_id, %s)", implode(",", $tagIds)) => "ASC"
                        ], true);
                        $row["params"]["seletedTags"] = (array)($goodsTags["list"] ?? []);
                    }
                    break;
            }
        }
    }

    private function filterCardPackage($companyId, $voucherPackage): array
    {
        $item = current($voucherPackage);
        $idKey = isset($item['package_id']) ? 'package_id' : 'id';
        $idList = array_column($voucherPackage, $idKey);
        $packageList = (new PackageQueryService())->getListByIdList($companyId, $idList);
        $packageIndex = array_column($packageList, null, 'package_id');

        foreach ($voucherPackage as $key => $item) {
            if (!isset($packageIndex[$item['package_id']])) {
                unset($voucherPackage[$key]);
            }
        }
        return array_values($voucherPackage);
    }


    /**
     * 删除模板
     */
    public function delete($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'pages_template_id' => $params['pages_template_id']
        ];
        $result = $this->pagesTemplateRepository->getInfo($filter);
        if ($result['status'] == 1) {
            throw new ResourceException("当前模板为启用状态，无法删除");
        }

        $result = $this->pagesTemplateRepository->delete($filter);

        return $result;
    }

    /**
     * 数据列表
     */
    public function lists($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id']
        ];

        $page_size = $params['page_size'];
        $page = $params['page_no'];

        $result = $this->pagesTemplateRepository->lists($filter, '*', $page, $page_size);

        return $result;
    }

    /**
     * 复制模版
     */
    public function copy($params)
    {
        $company_id = $params['company_id'];
        $pages_template_id = $params['pages_template_id'];

        $filter = [
            'company_id' => $params['company_id'],
            'pages_template_id' => $params['pages_template_id']
        ];
        $result = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($result)) {
            throw new ResourceException("复制失败，无效的模板信息");
        }

        //创建模板
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $creatr_params = [
                'company_id' => $result['company_id'],
                'distributor_id' => $result['distributor_id'],
                'template_name' => $result['template_name'],
                'template_title' => $result['template_title'] . '-' . '复制',
                'template_pic' => $result['template_pic'],
                'template_type' => $result['template_type'],
                'element_edit_status' => $result['element_edit_status'],
                'weapp_pages' => $result['weapp_pages'],
            ];
            $result = $this->pagesTemplateRepository->create($creatr_params);
            $new_pages_template_id = $result['pages_template_id'];//新模板id

            //创建模板内容
            $template_name = $result['template_name'];
            $page_name = 'index';  //旧模板默认值
            $config_name = '';
            $version = 'v1.0.2';     //旧模板默认值
            $entityRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);
            //查询拷贝模板内容数据
            $data = $entityRepository->getParamByTempName($company_id, $template_name, $page_name, $config_name, $version, $pages_template_id);
            if ($data) {
                foreach ($data as $row) {
                    $config_name = $row->getName();
                    $config_params = unserialize($row->getParams());
                    // 强制多语言转换
                    $ns = new CommonLangModService();
                    $lang = $ns->getLang();
                    $config_param_lang = $ns->getFieldByLangue($company_id, $lang, 'params', $row->getId(), 'wechat_weapp_setting', 'wechat_weapp_setting');
                    if (!empty($config_param_lang)) {
                        $config_params = unserialize($config_param_lang);
                    }

                    $entityRepository->setParams($company_id, $template_name, $page_name, $config_name, $config_params, $version, $new_pages_template_id);
                }
            }
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 模版状态变更
     */
    public function modifyStatus($params)
    {
        $company_id = $params['company_id'];
        $pages_template_id = $params['pages_template_id'];
        $status = $params['status'];//模板启用状态 1启用 2未启用
        $timer_status = $params['timer_status'];//定时启用状态 1启用 2未启用
        $timer_time = $params['timer_time'];//定时启用 时间戳

        $filter = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id
        ];
        $info = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($info)) {
            throw new ResourceException("无效的模板");
        }

        $distributor_id = $info['distributor_id'];
        //关闭模板启用状态
        if ($status == 2) {
            $filter_count = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'status' => 1,
            ];

            $count = $this->pagesTemplateRepository->count($filter_count);
            if ($count <= 1) {
                throw new ResourceException("至少开启一套模版");
            }
        }

        //启用定时模板操作
        if ($timer_status == 1) {
            //判断当前模板是否为已启用
            if ($info['status'] == 1) {
                throw new ResourceException("当前模板已是开启状态，无需定时启用操作");
            }

            $filter_count = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'timer_status' => 1
            ];
            $count = $this->pagesTemplateRepository->count($filter_count);
            if ($count >= 1) {
                throw new ResourceException("已有启用的定时模版");
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($status == 1) {
                //模板状态为启用中的修改未 未开启状态
                $_filter = [
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'status' => 1,
                ];

                $_params = [
                    'status' => 2
                ];
                $this->pagesTemplateRepository->updateBy($_filter, $_params);

                //记录模板变更时间
                $params['template_status_modify_time'] = time();
            }

            //更新模板信息
            $this->pagesTemplateRepository->updateOneBy($filter, $params);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 同步模板
     */
    public function sync($params)
    {
        $is_all_distributor = $params['is_all_distributor'];//是否全部门店 1 全部门店
        $company_id = $params['company_id'];
        $pages_template_id = $params['pages_template_id'];
        $distributor_ids = $params['distributor_ids']; //非全部门店店铺id
        $is_enforce_sync = 2; //店铺首页强制同步 1强制同步 2非强制同步

        $filter = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id
        ];
        $info = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($info)) {
            throw new ResourceException("无效的模板");
        }

        //判断是否是总部模板
        if ($info['template_type'] != 0) {
            throw new ResourceException("无效的模板");
        }

        $template_title = $info['template_title'];
        $template_pic = $info['template_pic'];
        $weapp_pages = $info['weapp_pages'];
        $element_edit_status = $info['element_edit_status'];
        $pages_template_id = $info['pages_template_id'];
        $template_name = $info['template_name'];

        //获取模板设置信息
        $_filter = [
            'company_id' => $company_id
        ];
        $pages_template_set_info = $this->pagesTemplateSetRepository->getInfo($_filter);
        if (!empty($pages_template_set_info)) {
            $is_enforce_sync = $pages_template_set_info['is_enforce_sync'];
        }

        //同步全部门店
        if ($is_all_distributor == 1) {
            $distributor_ids = []; //存储店铺id
            $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributor_list = $this->distributorRepository->getLists($_filter, $cols = 'distributor_id', $page = 1, $pageSize = -1);
            foreach ($distributor_list as $key => $value) {
                $distributor_ids[] = $value['distributor_id'];
            }
        } else {
            $json_arr = json_decode($distributor_ids, true);
            $distributor_ids = $json_arr;
        }

        if (empty($distributor_ids)) {
            return false;
        }

        foreach ($distributor_ids as $key => $distributor_id) {
            $enforce_sync_status = $is_enforce_sync;
            //当前门店不存在模板信息，则第一个模板默认为启用状态
            $_filter = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id
            ];
            $count = $this->pagesTemplateRepository->count($_filter);
            if ($count === 0) {
                $enforce_sync_status = 1;
            }

            $this->distributorPagesTemplate($distributor_id, $enforce_sync_status, $company_id, $template_title, $template_pic, $weapp_pages, $element_edit_status, $pages_template_id, $template_name);
        }

        return true;
    }

    /**
     * 定时切换
     */
    public function timer()
    {
        //查询所有设置定时切换时间记录
        $count_filter = [
            'timer_status' => 1,
        ];
        $count = $this->pagesTemplateRepository->count($count_filter);
        if ($count < 1) {
            return true;
        }

        $page_size = 1000;
        $list = $this->pagesTemplateRepository->lists($count_filter, '*', 1, $page_size);
        if (empty($list['list'])) {
            return true;
        }

        foreach ($list['list'] as $key => $val) {
            //判断当前时间是否满足定时切换时间
            if ($val['timer_time'] > time()) {
                continue;
            }

            $company_id = $val['company_id'];
            $distributor_id = $val['distributor_id'];
            $pages_template_id = $val['pages_template_id'];

            //切换模板
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                //模板状态为启用中的修改未 未开启状态
                $_filter = [
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'status' => 1,
                ];

                $_params = [
                    'status' => 2
                ];
                $this->pagesTemplateRepository->updateBy($_filter, $_params);

                //当前模板状态设置为启用中
                $filter = [
                    'company_id' => $company_id,
                    'pages_template_id' => $pages_template_id
                ];
                $params = [
                    'status' => 1,
                    'timer_status' => 2,
                    'timer_time' => '',
                    'template_status_modify_time' => time()
                ];
                $this->pagesTemplateRepository->updateOneBy($filter, $params);
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }

        return true;
    }

    /**
     * 新门店创建是，默认绑定模板
     */
    public function newDistributor($params)
    {
        $company_id = $params['company_id'];
        $distributor_id = $params['distributor_id']; //店铺id
        $filter = [
            'company_id' => $company_id,
            'distributor_id' => 0,
            'template_type' => 0,
            'status' => 1
        ];
        $info = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($info)) {
            throw new ResourceException("总部未设置启用模板");
        }

        $template_title = $info['template_title'];
        $template_pic = $info['template_pic'];
        $weapp_pages = $info['weapp_pages'];
        $element_edit_status = $info['element_edit_status'];
        $is_enforce_sync = 1;
        $pages_template_id = $info['pages_template_id'];
        $template_name = $info['template_name'];

        //创建门店模板
        $this->distributorPagesTemplate($distributor_id, $is_enforce_sync, $company_id, $template_title, $template_pic, $weapp_pages, $element_edit_status, $pages_template_id, $template_name);

        return true;
    }

    /**
     * 小程序端展示模板详情
     */
    public function content($params)
    {
        $company_id = $params['company_id'];
        $user_id = $params['user_id'];
        $distributor_id = $params['distributor_id'];
        $weapp_pages = $params['weapp_pages'];
        $template_name = $params['template_name'];
        $version = $params['version'];
        $pages_template_id = $params['pages_template_id'] ?? 0;

        $data = [];

        //获取模板设置
        $pages_template_set_info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $company_id]);
        if (empty($pages_template_set_info['index_type'])) {
            return $data;
        }

        if ($pages_template_id) {
            $filter = [
                'company_id' => $params['company_id'],
                'pages_template_id' => $pages_template_id,
                'weapp_pages' => $weapp_pages
            ];
        } else {
            //总部模板
            if ($pages_template_set_info['index_type'] == 1) {
                $filter = [
                    'company_id' => $params['company_id'],
                    'distributor_id' => 0,
                    'status' => 1,
                    'weapp_pages' => $weapp_pages
                ];
            } else {
                $filter = [
                    'company_id' => $params['company_id'],
                    'distributor_id' => $distributor_id,
                    'status' => 1,
                    'weapp_pages' => $weapp_pages
                ];
            }
        }

        $result = $this->pagesTemplateRepository->getInfo($filter);
        if (!$result) {
            if ($pages_template_id) {
                throw new ResourceException('模版ID错误');
            } else {
                if ($filter['distributor_id'] == 0) {
                    throw new ResourceException('总部未设置启用模板');
                } else {
                    throw new ResourceException('当前门店未设置启用模板');
                }
            }
        }

        // 模版关联的导航设置
        $pages_template_set_info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $company_id, 'pages_template_id' => $result['pages_template_id']]);
        if ($pages_template_set_info) {
            $data['tab_bar'] = $pages_template_set_info['tab_bar'];
        }

        //模板内容数据
        $params = [
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'pages_template_id' => $result['pages_template_id'],
            'user_id' => $user_id,
            'template_name' => $template_name,
            'version' => $version,
            'page' => $params['page'],
            'page_size' => $params['page_size'],
            'weapp_setting_id' => $params['weapp_setting_id'],
            'goods_grid_tab_id' => $params['goods_grid_tab_id'],
            'e_activity_id' => $params['e_activity_id'],
        ];

        $list = $this->templateContentByPage($params);
        // 处理模板参数
        $this->templateHandler((int)$company_id, $list);
        $data['list'] = $list;
        $config = [];
        foreach ($list as $row) {
            if (isset($row['params']['name']) && isset($row['params']['base'])) {
                $config[] = $this->templateFilter($company_id, $row['params']);
            }
        }
        $data['config'] = $config;

        return $data;
    }

    /**
     * @param $params
     * @return array
     * 小程序门店模板详情
     */
    public function shopContent($params)
    {
        $company_id = $params['company_id'];
        $user_id = $params['user_id'];
        $distributor_id = $params['distributor_id'];
        $weapp_pages = $params['weapp_pages'];
        $template_name = $params['template_name'];
        $version = $params['version'];

        $data = [];
        $filter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $distributor_id,
            'status' => 1,
            'weapp_pages' => $weapp_pages
        ];
        $result = $this->pagesTemplateRepository->getInfo($filter);
        if (empty($result)) {
            $data['config'] = '';
            $data['list'] = '';
            return $data;
        }

        // 模版关联的导航设置
        $pages_template_set_info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $company_id, 'pages_template_id' => $result['pages_template_id']]);
        if ($pages_template_set_info) {
            $data['tab_bar'] = $pages_template_set_info['pages_template_set_info'] ?? ($pages_template_set_info['tab_bar'] ?? '');
        }

        //模板内容数据
        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $result['pages_template_id'],
            'user_id' => $user_id,
            'template_name' => $template_name,
            'version' => $version
        ];

        $list = $this->templateContent($params);
        $data['list'] = $list;
        $config = [];
        foreach ($list as $row) {
            if (isset($row['params']['name']) && isset($row['params']['base'])) {
                $config[] = $row['params'];
            }
        }
        $data['config'] = $config;

        return $data;
    }

    /**
     * 获取商品的信息
     * 获取价格的优先级: 活动价 > 会员价 > 销售价
     * @param int $companyId 企业id
     * @param string $configName 配置名称
     * @param array $itemsId 多个商品的item_id
     * @param array $params 模板内容数据，也可以是自己组装的格式
     * @return array 商品的信息
     */
    public function getItemsInfo(int $companyId, string $configName, array $itemsId, array &$params): array
    {
        if (empty($itemsId)) {
            return [];
        }
        // 获取用户id
        $userId = (int)($params['user_id'] ?? 0);
        // 获取活动id
        $activityId = (int)($params['config']['seckillId'] ?? 0);
        // 配置信息的类型
        $configType = $params['config']['type'] ?? "";
        // 店铺id
        $distributorId = (int)($params['distributor_id'] ?? 0);

        // 获取商品服务
        $itemsService = new ItemsService();

        // 获取秒杀活动下的商品信息
        if ($configName == 'goodsScroll' && ($params['config']['seckillId'] ?? 0) && $itemsId) {
            // 基于秒杀活动id来获取秒杀活动
            $seckilldata = (new PromotionSeckillActivityService())->getSeckillItemList([
                "company_id" => $companyId,
                "seckill_id" => $activityId,
                "item_id" => $itemsId
            ], 1, 10, [], false);
            if (!empty($seckilldata['list'])) {
                if (!empty($seckilldata["activity"])) {
                    $params['config']['status'] = $seckilldata['activity']['status'] ?? null;
                    $params['config']['lastSeconds'] = $seckilldata['activity']['last_seconds'] ?? null;
                }

                $seckilldata = $itemsService->getItemsListMemberPrice($seckilldata, $userId, $companyId);

                return (array)array_column($seckilldata['list'], null, 'item_id');
            }
        }

        // 获取商品税率服务
        $ItemTaxRateService = new ItemTaxRateService($companyId);

        // 确定查询使用的 distributor_id（优先使用活动的 distributor_id）
        $queryDistributorId = $distributorId;  // 默认使用传入的 distributor_id
        
        // 如果有 e_activity_id，查询活动信息，获取活动的 distributor_id
        if (isset($params['e_activity_id']) && $params['e_activity_id'] > 0) {
            $activitiesService = new ActivitiesService();
            $activityInfo = $activitiesService->entityRepository->getInfo([
                'id' => $params['e_activity_id'],
                'company_id' => $companyId,
            ]);
            
            // 如果活动存在，使用活动的 distributor_id
            if (!empty($activityInfo) && isset($activityInfo['distributor_id'])) {
                $queryDistributorId = intval($activityInfo['distributor_id']);
            }
        }

        // 使用活动的 distributor_id 查询商品信息
        $result = $itemsService->getItemListData([
            "company_id" => $companyId,
            "item_id" => $itemsId,
            "distributor_id" => $queryDistributorId,  // 使用活动的 distributor_id
        ]);
        // 内购活动价
        if (isset($params['e_activity_id']) && $params['e_activity_id'] > 0) {
            $activityItemsService = new ActivityItemsService();
            $result = $activityItemsService->getItemsListActityPrice($result, $params['e_activity_id'], $companyId);
        }
        $result = $itemsService->getItemsListMemberPrice($result, $userId, $companyId);
        //营销标签
        $result = $itemsService->getItemsListActityTag($result, $companyId);
        foreach ($result['list'] as $key => $value) {
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
                $result['list'][$key]['cross_border_tax'] = $cross_border_tax;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];  // 税率
                $result['list'][$key][$tax_calculation] = bcadd($value[$tax_calculation], $cross_border_tax); // 含税价格(列表显示的价格)
                if ($tax_calculation == 'activity_price') {
                    $result['list'][$key]['promotion_activity'][count($result['list'][$key]['promotion_activity']) - 1]['activity_price'] = $result['list'][$key][$tax_calculation];
                }
            } else {
                $result['list'][$key]['cross_border_tax'] = 0;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = 0; // 税率
            }
        }
        //获取品牌名和logo
        $result['list'] = $itemsService->getItemsListBrandData($result['list'], $companyId);
        return (array)array_column($result['list'], null, 'item_id');
    }

    /**
     * 模板内容组装，原模板数据结构
     * @param $params
     *      company_id 企业id
     *      pages_template_id 当前页的模板id
     *      user_id 用户id
     *      template_name 模板名称
     *      version 小程序版本
     *      config.seckillId 秒杀活动的活动id
     *      config.type 配置类型
     *      distributor_id 店铺id
     * @return array
     */
    private function templateContent($params): array
    {
        $distributorId = (int)($params['distributor_id'] ?? 0);
        $userId = (int)($params['user_id'] ?? 0);
        $eActivityId = (int)($params['e_activity_id'] ?? 0);
        //$companyId        = $params['company_id'];
        //$pages_template_id = $params['pages_template_id'];
        //$user_id           = isset($params['user_id']) ? $params['user_id'] : '';
        //$template_name     = $params['template_name'];
        //$version           = $params['version'];
        //$page_name      = 'index';
        //$config_name    = '';
        $companyId = $params['company_id'];
        // 获取小程序模板装修表中的指定的那条数据
        $repository = app('registry')->getManager('default')->getRepository(WeappSetting::class);
        $data = $repository->getParamByTempName($companyId, $params['template_name'], "index", "", $params['version'], $params['pages_template_id']);
        $list = [];
        if (!$data || !is_array($data)) {
            return $list;
        }
        foreach ($data as $row) {
            $pageName = $row->getPageName();
            $name = $row->getName();
            $params = unserialize($row->getParams());
            // 强制多语言转换
            $ns = new CommonLangModService();
            $lang = $ns->getLang();
            $param_lang = $ns->getFieldByLangue($companyId, $lang, 'params', $row->getId(), 'wechat_weapp_setting', 'wechat_weapp_setting');
            if (!empty($param_lang)) {
                $params = unserialize($param_lang);
            }
            $params["user_id"] = $userId;
            $params["distributor_id"] = $distributorId;
            $params['e_activity_id'] = $eActivityId;
            switch ($name) {
                case "goodsScroll":
                case "goodsGrid":
                    if (isset($params["data"]) && is_array($params["data"])) {
                        // 获取商品的good_id
                        $itemIds = array_column($params['data'], 'goodsId');
                        $itemDataList = $this->getItemsInfo((int)$companyId, $name, (array)$itemIds, $params);
                        foreach ($params['data'] as $key => &$goodsValue) {
                            $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                            $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                            if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                $goodsValue['distributor_id'] = $distributorId;
                            }
                            if (empty($itemInfo)) {
                                //unset($params['data'][$key]);
                                continue;
                            }
                            $goodsValue['price'] = $itemInfo['price'] ?? 0;
                            $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                            $goodsValue['title'] = $itemInfo['item_name'];
                            $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                            $goodsValue['nospec'] = $itemInfo['nospec'];
                            $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                            $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                            $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                            $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                            $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                            $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                            $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                            $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                        }
                        $params['data'] = array_values($params['data']);
                    }
                    break;
                case "goodsGridTab":
                    if (isset($params["list"]) && is_array($params["list"])) {
                        foreach ($params['list'] as $gridKey => &$gridList) {
                            $itemIds = array_column($gridList['goodsList'], 'goodsId');
                            $itemDataList = $this->getItemsInfo((int)$companyId, $name, (array)$itemIds, $params);
                            foreach ($gridList['goodsList'] as $goodsKey => &$goodsValue) {
                                $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                                $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                                if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                    $goodsValue['distributor_id'] = $distributorId;
                                }
                                if (empty($itemInfo)) {
                                    //unset($params['data'][$key]);
                                    continue;
                                }
                                $goodsValue['price'] = $itemInfo['price'] ?? 0;
                                $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                                $goodsValue['title'] = $itemInfo['item_name'];
                                $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                                $goodsValue['nospec'] = $itemInfo['nospec'];
                                $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                                $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                                $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                                $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                                $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                                $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                                $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                                $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                            }
                        }
                    }
                    break;
                case "store":
                    if (isset($params["data"]) && is_array($params["data"])) {
                        $distributor_map = [];
                        $distributor_ids = array_column($params['data'], 'id');
                        if ($distributor_ids) {
                            // 推荐店铺实时信息
                            $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                            $distributor_list = $this->distributorRepository->getLists(['distributor_id' => $distributor_ids], $cols = 'distributor_id, name, logo', $page = 1, $pageSize = -1);
                            foreach ($distributor_list as $distributor) {
                                $distributor_map[$distributor['distributor_id']] = $distributor;
                            }
                        }
                        foreach ($params["data"] as &$datum) {
                            if (!isset($datum["items"]) || !is_array($datum["items"])) {
                                continue;
                            }
                            if (isset($distributor_map[$datum['id']])) {
                                $datum['logo'] = $distributor_map[$datum['id']]['logo'];
                                $datum['name'] = $distributor_map[$datum['id']]['name'];
                            }
                            $itemIds = (array)array_column($datum["items"], 'goodsId');
                            $itemDataList = $this->getItemsInfo((int)$companyId, $name, $itemIds, $params);
                            foreach ($datum["items"] as &$goodsValue) {
                                $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                                $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                                if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                    $goodsValue['distributor_id'] = $distributorId;
                                }
                                if (empty($itemInfo)) {
                                    //unset($params['data'][$key]);
                                    continue;
                                }
                                $goodsValue['price'] = $itemInfo['price'] ?? 0;
                                $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                                $goodsValue['title'] = $itemInfo['item_name'];
                                $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                                $goodsValue['nospec'] = $itemInfo['nospec'];
                                $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                                $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                                $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                                $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                                $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                                $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                                $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                                $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                            }
                        }
                    }
                    break;
            }

            $list[] = [
                'id' => $row->getId(),
                'template_name' => $row->getTemplateName(),
                'company_id' => $row->getCompanyId(),
                'name' => $name,
                'page_name' => $pageName ? $pageName : 'index',
                'params' => $params,
            ];
        }
        return $list;
    }

    /**
     * 模板内容组装，原模板数据结构
     * @param $params
     *      company_id 企业id
     *      user_id 用户id
     *      distributor_id 店铺id
     *      page 页码
     *      page_size 每页数量
     *      weapp_setting_id id
     *      goods_grid_tab_id tab_id
     *
     * @return array
     */
    private function templateContentByPage($params): array
    {
        $distributorId = (int)($params['distributor_id'] ?? 0);
        $userId = (int)($params['user_id'] ?? 0);
        $eActivityId = (int)($params['e_activity_id'] ?? 0);
        //$companyId        = $params['company_id'];
        //$pages_template_id = $params['pages_template_id'];
        //$user_id           = isset($params['user_id']) ? $params['user_id'] : '';
        //$template_name     = $params['template_name'];
        //$version           = $params['version'];
        //$page_name      = 'index';
        //$config_name    = '';
        $companyId = $params['company_id'];
        $page_number = $params['page'];
        $page_size = $params['page_size'];
        $weapp_setting_id = $params['weapp_setting_id'];
        $goods_grid_tab_id = $params['goods_grid_tab_id'];
        // 获取小程序模板装修表中的指定的那条数据
        $data = app('registry')
            ->getManager('default')
            ->getRepository(WeappSetting::class)
            ->getParamByTempName($companyId, $params['template_name'], "index", "", $params['version'], $params['pages_template_id'], $weapp_setting_id);
        $list = [];
        if (!$data || !is_array($data)) {
            return $list;
        }
        foreach ($data as $row) {
            $pageName = $row->getPageName();
            $name = $row->getName();
            $params = unserialize($row->getParams());
            // 强制多语言转换
            $ns = new CommonLangModService();
            $lang = $ns->getLang();
            $param_lang = $ns->getFieldByLangue($companyId, $lang, 'params', $row->getId(), 'wechat_weapp_setting', 'wechat_weapp_setting');
            if (!empty($param_lang)) {
                $params = unserialize($param_lang);
            }
            $params["user_id"] = $userId;
            $params["distributor_id"] = $distributorId;
            $params['e_activity_id'] = $eActivityId;
            switch ($name) {
                case "goodsScroll":
                    if (isset($params["data"]) && is_array($params["data"])) {
                        $all_count = count($params['data']);
                        $params['data'] = array_slice($params['data'], ($page_number - 1) * $page_size, $page_size);
                        $current_count = ($page_number - 1) * $page_size + count($params['data']);
                        $params['more'] = false;
                        if ($current_count < $all_count) {
                            $params['more'] = true;
                        }
                        // 获取商品的good_id
                        $itemIds = array_column($params['data'], 'goodsId');
                        $itemDataList = $this->getItemsInfo((int)$companyId, $name, (array)$itemIds, $params);
                        foreach ($params['data'] as $key => &$goodsValue) {
                            $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                            $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                            if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                $goodsValue['distributor_id'] = $distributorId;
                            }
                            if (empty($itemInfo)) {
                                continue;
                            }
                            $goodsValue['price'] = $itemInfo['price'] ?? 0;
                            $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                            $goodsValue['title'] = $itemInfo['item_name'];
                            $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                            $goodsValue['nospec'] = $itemInfo['nospec'];
                            $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                            $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                            $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                            $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                            $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                            $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                            $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                            $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                        }
                        unset($goodsValue);
                        $params['data'] = array_values($params['data']);
                    }
                    break;
                case "goodsGrid":
                    if (isset($params["data"]) && is_array($params["data"])) {
                        $all_count = count($params['data']);
                        $params['data'] = array_slice($params['data'], ($page_number - 1) * $page_size, $page_size);
                        $current_count = ($page_number - 1) * $page_size + count($params['data']);
                        $params['more'] = false;
                        if ($current_count < $all_count) {
                            $params['more'] = true;
                        }
                        // 获取商品的good_id
                        $itemIds = array_column($params['data'], 'goodsId');
                        $itemDataList = $this->getItemsInfo((int)$companyId, $name, (array)$itemIds, $params);
                        foreach ($params['data'] as $key => &$goodsValue) {
                            $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                            $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                            if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                $goodsValue['distributor_id'] = $distributorId;
                            }
                            if (empty($itemInfo)) {
                                //unset($params['data'][$key]);
                                continue;
                            }
                            $goodsValue['price'] = $itemInfo['price'] ?? 0;
                            $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                            $goodsValue['title'] = $itemInfo['item_name'];
                            $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                            $goodsValue['nospec'] = $itemInfo['nospec'];
                            $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                            $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                            $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                            $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                            $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                            $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                            $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                            $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                            $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                        }
                        $params['data'] = array_values($params['data']);
                    }
                    break;
                case "goodsGridTab":
                    if (isset($params["list"]) && is_array($params["list"])) {
                        foreach ($params['list'] as $gridKey => &$gridList) {
                            if (!empty($weapp_setting_id) && $gridKey != $goods_grid_tab_id) {
                                unset($params['list'][$gridKey]);
                                continue;
                            }
                            $all_count = count($gridList['goodsList']);
                            $gridList['goodsList'] = array_slice($gridList['goodsList'], ($page_number - 1) * $page_size, $page_size);
                            $current_count = ($page_number - 1) * $page_size + count($gridList['goodsList']);
                            $gridList['more'] = false;
                            if ($current_count < $all_count) {
                                $gridList['more'] = true;
                            }
                            $itemIds = array_column($gridList['goodsList'], 'goodsId');
                            $itemDataList = $this->getItemsInfo((int)$companyId, $name, (array)$itemIds, $params);
                            foreach ($gridList['goodsList'] as $goodsKey => &$goodsValue) {
                                $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                                $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                                if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                    $goodsValue['distributor_id'] = $distributorId;
                                }
                                if (empty($itemInfo)) {
                                    //unset($params['data'][$key]);
                                    continue;
                                }
                                $goodsValue['price'] = $itemInfo['price'] ?? 0;
                                $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                                $goodsValue['title'] = $itemInfo['item_name'];
                                $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                                $goodsValue['nospec'] = $itemInfo['nospec'];
                                $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                                $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                                $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                                $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                                $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                                $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                                $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                                $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                            }
                        }
                    }
                    $params['list'] = array_values($params['list']);
                    break;
                case "store":
                    if (isset($params["data"]) && is_array($params["data"])) {
                        $distributor_map = [];
                        $distributor_ids = array_column($params['data'], 'id');
                        if ($distributor_ids) {
                            // 推荐店铺实时信息
                            $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                            $distributor_list = $this->distributorRepository->getLists(['distributor_id' => $distributor_ids], $cols = 'distributor_id, name, logo, is_self_delivery', $page = 1, $pageSize = -1);
                            foreach ($distributor_list as $distributor) {
                                $distributor_map[$distributor['distributor_id']] = $distributor;
                            }
                        }
                        foreach ($params["data"] as &$datum) {
                            if (!isset($datum["items"]) || !is_array($datum["items"])) {
                                continue;
                            }
                            if (isset($distributor_map[$datum['id']])) {
                                $datum['logo'] = $distributor_map[$datum['id']]['logo'];
                                $datum['name'] = $distributor_map[$datum['id']]['name'];
                                $datum['is_self_delivery'] = $distributor_map[$datum['id']]['is_self_delivery'];
                            }
                            $itemIds = (array)array_column($datum["items"], 'goodsId');
                            $itemDataList = $this->getItemsInfo((int)$companyId, $name, $itemIds, $params);
                            foreach ($datum["items"] as &$goodsValue) {
                                $itemGoodsId = $goodsValue['goodsId'] ?? 0;
                                $itemInfo = $itemDataList[$itemGoodsId] ?? [];
                                if (intval($goodsValue['distributor_id'] ?? 0) == 0) {
                                    $goodsValue['distributor_id'] = $distributorId;
                                }
                                if (empty($itemInfo)) {
                                    //unset($params['data'][$key]);
                                    continue;
                                }
                                $goodsValue['price'] = $itemInfo['price'] ?? 0;
                                $goodsValue['imgUrl'] = $itemInfo['pics'][0] ?? ($value['imgUrl'] ?? '');
                                $goodsValue['title'] = $itemInfo['item_name'];
                                $goodsValue['brand'] = $itemInfo['brand_logo'] ?? '';
                                $goodsValue['nospec'] = $itemInfo['nospec'];
                                $goodsValue['special_type'] = $itemInfo['special_type'] ?? 'normal';
                                $goodsValue['member_price'] = ($itemInfo['member_price'] ?? 0) ?: 0;
                                $goodsValue['market_price'] = ($itemInfo['market_price'] ?? 0) ?: 0;
                                $goodsValue['act_price'] = ($itemInfo['activity_price'] ?? 0) ?: 0;
                                $goodsValue['vip_price'] = ($itemInfo['vip_price'] ?? 0) ?: 0;
                                $goodsValue['svip_price'] = ($itemInfo['svip_price'] ?? 0) ?: 0;
                                $goodsValue['promotion_activity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['promotionActivity'] = $itemInfo['promotion_activity'] ?? [];
                                $goodsValue['cross_border_tax'] = $itemInfo['cross_border_tax'] ?? 0;
                                $goodsValue['cross_border_tax_rate'] = $itemInfo['cross_border_tax_rate'] ?? 0;
                            }
                        }
                    }
                    break;
            }

            $list[] = [
                'id' => $row->getId(),
                'template_name' => $row->getTemplateName(),
                'company_id' => $row->getCompanyId(),
                'name' => $name,
                'page_name' => $pageName ? $pageName : 'index',
                'params' => $params,
            ];
        }
        return $list;
    }

    /**
     * @param $distributor_id      店铺id
     * @param $is_enforce_sync     店铺首页强制同步 1是 2否
     * @param $company_id          company_id
     * @param $template_title      模板名称
     * @param $template_pic        模板图片
     * @param $weapp_pages         小程序页面类型
     * @param $element_edit_status 组件可编辑状态
     * @param $pages_template_id   页面模板id
     * @param $template_name       展示客户端名称 如小程序商城 yykweishop
     * @return bool
     * @throws \Exception
     *
     * 门店模板同步创建
     */
    private function distributorPagesTemplate($distributor_id, $is_enforce_sync, $company_id, $template_title, $template_pic, $weapp_pages, $element_edit_status, $pages_template_id, $template_name)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //当前同步模板为强制启用状态，已存在模板需修改为 未启用状态
            if ($is_enforce_sync == 1) {
                $_filter = [
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'status' => 1,
                ];

                $_params = [
                    'status' => 2,
                    'template_status_modify_time' => time() //记录模板变更时间
                ];
                $this->pagesTemplateRepository->updateBy($_filter, $_params);
            }

            //创建模板
            $params = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'template_title' => $template_title,
                'template_pic' => $template_pic,
                'template_type' => 1,
                'weapp_pages' => $weapp_pages,
                'element_edit_status' => $element_edit_status,
                'status' => $is_enforce_sync == 1 ? 1 : 2,
                'template_status_modify_time' => $is_enforce_sync == 1 ? time() : '',
                'template_name' => $template_name
            ];

            $result = $this->pagesTemplateRepository->create($params);
            $new_pages_template_id = $result['pages_template_id'];//新模板id

            //创建模板内容
            $page_name = 'index';  //旧模板默认值
            $config_name = '';
            $version = 'v1.0.2';
            $entityRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);
            //查询拷贝模板内容数据
            $data = $entityRepository->getParamByTempName($company_id, $template_name, $page_name, $config_name, $version, $pages_template_id);
            if ($data) {
                foreach ($data as $row) {
                    $config_name = $row->getName();
                    $config_params = unserialize($row->getParams());
                    // 强制多语言转换
                    $ns = new CommonLangModService();
                    $lang = $ns->getLang();
                    $param_lang = $ns->getFieldByLangue($company_id, $lang, 'params', $row->getId(), 'wechat_weapp_setting', 'wechat_weapp_setting');
                    if (!empty($param_lang)) {
                        $config_params = unserialize($param_lang);
                    }
                    //组件不可编辑 同步店铺把no_edit设置为true,每个挂件config里面
                    if ($element_edit_status == 2) {
                        $config_params['config']['no_edit'] = true;
                    }

                    $entityRepository->setParams($company_id, $template_name, $page_name, $config_name, $config_params, $version, $new_pages_template_id);
                }
            }
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }
}
