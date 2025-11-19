<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
     * 创建pc模板
     */
    public function add($params)
    {
        $result = $this->themePcTemplateRepository->create($params);

        return $result;
    }

    /**
     * 编辑模板
     */
    public function edit($params)
    {
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
                //首页只能同时启用一个页面
                if ($pc_template_info['page_type'] == 'index') {
                    $_filter = [
                        'company_id' => $company_id,
                        'page_type' => 'index'
                    ];
                    $_data = [
                        'status' => 2
                    ];
                    $this->themePcTemplateRepository->updateBy($_filter, $_data);
                }
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
