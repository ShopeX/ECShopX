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

class ThemePcTemplateServices
{
    private $themePcTemplateRepository;
    private $themePcTemplateContentRepository;

    public function __construct()
    {
        //$this->themePcTemplateRepository = app('registry')->getManager('default')->getRepository(ThemePcTemplate::class);
        // $this->themePcTemplateContentRepository = app('registry')->getManager('default')->getRepository(ThemePcTemplateContent::class);
        $this->themePcTemplateRepository = getRepositoryLangue(ThemePcTemplate::class);
        $this->themePcTemplateContentRepository = getRepositoryLangue(ThemePcTemplateContent::class);
    }

    /**
     * pc模板列表
     */
    public function lists($params)
    {
        $company_id = $params['company_id'];
        $page_type = $params['page_type'];
        $page_no = $params['page_no'];
        $page_size = $params['page_size'];
        $status = $params['status'];

        $filter = [
            'company_id' => $company_id
        ];
        if (!empty($page_type)) {
            $filter['page_type'] = $page_type;
        }
        if (!empty($status)) {
            $filter['status'] = $status;
        }
        $result = $this->themePcTemplateRepository->lists($filter, '*', $page_no, $page_size, ['created' => 'DESC']);

        return $result;
    }

    
    /**
     * 检查首页模板启用状态：只允许有一个首页是启用状态
     * 
     * @param int $company_id 公司ID
     * @param string $page_type 页面类型
     * @param int $status 启用状态
     * @param int|null $exclude_template_id 排除的模板ID（编辑时使用）
     * @throws ResourceException
     */
    private function checkIndexTemplateStatus($company_id, $page_type, $status, $exclude_template_id = null)
    {
        // 只检查首页且启用状态的情况
        if ($page_type == 'index' && $status == 1) {
            $filter = [
                'company_id' => $company_id,
                'page_type' => 'index',
                'status' => 1,
            ];
            $checkList = $this->themePcTemplateRepository->getLists($filter, '*', 1, -1);
            
            // 排除当前编辑的模板
            if (!empty($checkList)) {
                foreach ($checkList as $item) {
                    if ($exclude_template_id === null || $item['theme_pc_template_id'] != $exclude_template_id) {
                        throw new ResourceException('已有启用的模版');
                    }
                }
            }
        }
    }

    /**
     * 创建pc模板
     */
    public function add($params)
    {
        // 检查首页模板启用状态
        $this->checkIndexTemplateStatus(
            $params['company_id'],
            $params['page_type'] ?? '',
            $params['status'] ?? 2
        );

        $result = $this->themePcTemplateRepository->create($params);

        return $result;
    }

    /**
     * 编辑模板
     */
    public function edit($params)
    {
        app('log')->info('edit::params====>'.json_encode($params));
        // Debug: 1e2364
        $company_id = $params['company_id'];
        $theme_pc_template_id = $params['theme_pc_template_id'];
        $filter = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id
        ];

        $pc_template_info = $this->themePcTemplateRepository->getInfo($filter);
        if (empty($pc_template_info)) {
            throw new ResourceException('模板页面不存在');
        }

        // 确定 page_type：优先使用传入的值，否则使用数据库中的值
        $page_type = $params['page_type'] ?? $pc_template_info['page_type'];
        $status = $params['status'] ?? null;

        // 检查首页模板启用状态
        if (!empty($status)) {
            $this->checkIndexTemplateStatus(
                $company_id,
                $page_type,
                $status,
                $theme_pc_template_id
            );
        }

        $data = [];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!empty($params['template_title'])) {
                $data['template_title'] = $params['template_title'];
            }

            if (!empty($params['template_description'])) {
                $data['template_description'] = $params['template_description'];
            }

            if (!empty($params['status'])) {
                $data['status'] = $params['status'];
            }

            if (!empty($params['page_type'])) {
                $data['page_type'] = $params['page_type'];
            }

            $result = $this->themePcTemplateRepository->updateOneBy($filter, $data);
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

    /**
     * 删除模板
     */
    public function delete($params)
    {
        // Debug: 1e2364
        $company_id = $params['company_id'];
        $theme_pc_template_id = $params['theme_pc_template_id'];
        $filter = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id
        ];
        $info = $this->themePcTemplateRepository->getInfo($filter);
        if (empty($info)) {
            throw new ResourceException('pc模板不存在');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->themePcTemplateRepository->deleteById($theme_pc_template_id);
            $this->themePcTemplateContentRepository->deleteBy(['theme_pc_template_id' => $theme_pc_template_id]);

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
}
