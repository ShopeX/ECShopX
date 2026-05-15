<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\StoreHomePage;
use ThemeBundle\Services\PagesTemplateServices;
use WechatBundle\Entities\WeappSetting;
use WechatBundle\Services\Wxapp\CustomizePageService;

class StoreHomePageService
{
    public const CUSTOMIZE_PAGE_TYPE = 'enterprise_store_home';

    /** 与管理端 shopDecoration 保存自定义页（page_template 模式）一致 */
    public const CUSTOM_DECORATION_SETTING_VERSION = 'v1.0.1';

    /** 商城首页 index 装修行版本（与历史 ToC 详情一致） */
    public const INDEX_DECORATION_SETTING_VERSION = 'v1.0.2';

    /** @var \EmployeePurchaseBundle\Repositories\StoreHomePageRepository */
    private $repository;

    /** @var CustomizePageService */
    private $customizePageService;

    /** @var \Doctrine\ORM\EntityRepository */
    private $weappSettingRepository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(StoreHomePage::class);
        $this->customizePageService = new CustomizePageService();
        $this->weappSettingRepository = getRepositoryLangue(WeappSetting::class);
    }

    public static function internalCustomizePageName(int $companyId, int $distributorId): string
    {
        return 'ep_store_home_'.$companyId.'_'.$distributorId;
    }

    /** 新建自定义页 row 的 page_name，带随机后缀以免同门店多条重名 */
    public static function uniqueInternalCustomizePageName(int $companyId, int $distributorId): string
    {
        return self::internalCustomizePageName($companyId, $distributorId).'_'.bin2hex(random_bytes(4));
    }

    /**
     * wechat_weapp_setting.page_name：内购 enterprise 自定义页装修与 shopDecoration 一致为 custom_{id}。
     */
    public static function weappSettingPageNameForCustomizePage(int $weappCustomizePageId): ?string
    {
        if ($weappCustomizePageId <= 0) {
            return null;
        }

        return 'custom_'.$weappCustomizePageId;
    }

    /**
     * @param array<string,mixed>|null $detail PagesTemplateServices::content 返回值
     */
    public static function pageTemplateDetailHasNonEmptyList(?array $detail): bool
    {
        return is_array($detail)
            && isset($detail['list'])
            && is_array($detail['list'])
            && $detail['list'] !== [];
    }

    /**
     * @return array{list: array<int, array<string,mixed>>, total_count: int}
     */
    public function getList(int $companyId, int $authDistributorId, int $page, int $pageSize, ?int $filterDistributorId = null): array
    {
        $filter = ['company_id' => $companyId];
        if ($authDistributorId > 0) {
            $filter['distributor_id'] = $authDistributorId;
        } elseif ($filterDistributorId !== null && $filterDistributorId > 0) {
            $filter['distributor_id'] = $filterDistributorId;
        }
        $result = $this->repository->lists($filter, '*', $page, $pageSize, ['id' => 'DESC']);

        return [
            'list' => $result['list'],
            'total_count' => $result['total_count'],
        ];
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function createRow(int $companyId, int $authDistributorId, array $params): array
    {
        $distributorId = (int) $authDistributorId;
        $templateName = $params['template_name'] ?? '';
        if ($templateName === '') {
            throw new ResourceException('模版名称不能为空');
        }
        $pageName = $params['page_name'] ?? '';
        $pageDescription = $params['page_description'] ?? '';
        if ($pageName === '' || $pageDescription === '') {
            throw new ResourceException('页面名称与描述不能为空');
        }

        $isOpen = self::normalizeIsOpen($params['is_open'] ?? true);

        $internalName = self::uniqueInternalCustomizePageName($companyId, $distributorId);
        $cpParams = [
            'template_name' => $templateName,
            'page_name' => $internalName,
            'page_description' => $pageDescription,
            'page_share_title' => $params['page_share_title'] ?? '',
            'page_share_desc' => $params['page_share_desc'] ?? '',
            'page_share_imageUrl' => $params['page_share_imageUrl'] ?? '',
            'is_open' => $isOpen,
            'page_type' => self::CUSTOMIZE_PAGE_TYPE,
            'regionauth_id' => 0,
            'company_id' => $companyId,
        ];
        $cp = $this->customizePageService->create($cpParams);
        $customizeId = (int) ($cp['id'] ?? 0);
        if ($customizeId <= 0) {
            throw new ResourceException('创建装修页失败');
        }

        $row = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'template_name' => $templateName,
            'page_name' => $pageName,
            'page_description' => $pageDescription,
            'page_share_title' => $params['page_share_title'] ?? null,
            'page_share_desc' => $params['page_share_desc'] ?? null,
            'page_share_imageUrl' => $params['page_share_imageUrl'] ?? null,
            'is_open' => $isOpen ? 1 : 0,
            'weapp_customize_page_id' => $customizeId,
        ];

        return $this->repository->create($row);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function updateRow(int $companyId, int $authDistributorId, int $id, array $params): array
    {
        $row = $this->repository->getInfo(['id' => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException('未查询到数据');
        }
        StoreHomePageAccess::assertRowMatchesDealer($row, $authDistributorId);

        $data = [];
        foreach (['page_name', 'page_description', 'page_share_title', 'page_share_desc', 'page_share_imageUrl', 'template_name'] as $k) {
            if (array_key_exists($k, $params)) {
                $data[$k] = $params[$k];
            }
        }
        if (array_key_exists('is_open', $params)) {
            $data['is_open'] = self::normalizeIsOpen($params['is_open']) ? 1 : 0;
        }
        if (isset($data['page_name']) && $data['page_name'] === '') {
            throw new ResourceException('页面名称不能为空');
        }
        if (isset($data['page_description']) && $data['page_description'] === '') {
            throw new ResourceException('页面描述不能为空');
        }

        $updated = $this->repository->updateOneBy(['id' => $id, 'company_id' => $companyId], $data);

        $cid = (int) ($row['weapp_customize_page_id'] ?? 0);
        if ($cid > 0) {
            $cpUpdate = [];
            if (isset($data['template_name'])) {
                $cpUpdate['template_name'] = $data['template_name'];
            }
            if (isset($data['page_description'])) {
                $cpUpdate['page_description'] = $data['page_description'];
            }
            if (isset($data['page_share_title'])) {
                $cpUpdate['page_share_title'] = $data['page_share_title'];
            }
            if (isset($data['page_share_desc'])) {
                $cpUpdate['page_share_desc'] = $data['page_share_desc'];
            }
            if (isset($data['page_share_imageUrl'])) {
                $cpUpdate['page_share_imageUrl'] = $data['page_share_imageUrl'];
            }
            if (array_key_exists('is_open', $params)) {
                $cpUpdate['is_open'] = self::normalizeIsOpen($params['is_open']);
            }
            if ($cpUpdate) {
                $this->customizePageService->updateOneBy(
                    ['id' => $cid, 'company_id' => $companyId],
                    $cpUpdate
                );
            }
        }

        return $updated;
    }

    public function getById(int $companyId, int $authDistributorId, int $id): array
    {
        $row = $this->repository->getInfo(['id' => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException('未查询到数据');
        }
        StoreHomePageAccess::assertRowMatchesDealer($row, $authDistributorId);

        return $row;
    }

    /**
     * ToC：按内购模版主键读取详情，并尽力解析装修侧 pages_template_id（与活动表 pages_template_id 字段语义不同）。
     *
     * @return array<string,mixed>
     */
    public function getDetailForFront(int $companyId, int $authDistributorId, int $id, int $userId = 0, int $eActivityId = 0): array
    {
        $row = $this->repository->getInfo(['id' => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException('未查询到数据');
        }
        StoreHomePageAccess::assertRowMatchesDealer($row, $authDistributorId);

        $resolved = $this->resolvePagesTemplateForStoreHomeRow($companyId, $row);

        $base = [
            'store_home_page_id' => $id,
            'company_id' => (int) ($row['company_id'] ?? 0),
            'distributor_id' => (int) ($row['distributor_id'] ?? 0),
            'page_name' => $row['page_name'] ?? '',
            'page_description' => $row['page_description'] ?? '',
            'page_share_title' => $row['page_share_title'] ?? '',
            'page_share_desc' => $row['page_share_desc'] ?? '',
            'page_share_imageUrl' => $row['page_share_imageUrl'] ?? '',
            'template_name' => (string) ($row['template_name'] ?? ''),
            'is_open' => $row['is_open'] ?? 0,
            'weapp_customize_page_id' => isset($row['weapp_customize_page_id']) ? (int) $row['weapp_customize_page_id'] : null,
            'resolved_pages_template_id' => $resolved['resolved_pages_template_id'],
            'template_meta' => $resolved['template_meta'],
            'pages_template_record' => $resolved['pages_template_record'],
            'page_template_detail' => null,
        ];

        $pid = (int) ($resolved['resolved_pages_template_id'] ?? 0);
        if ($pid > 0) {
            $distId = (int) ($row['distributor_id'] ?? 0);
            $customizeId = isset($row['weapp_customize_page_id']) ? (int) $row['weapp_customize_page_id'] : 0;
            $pagesTemplateServices = new PagesTemplateServices();

            $indexParams = [
                'company_id' => $companyId,
                'regionauth_id' => 0,
                'user_id' => $userId,
                'distributor_id' => $distId,
                'weapp_pages' => 'index',
                'template_name' => (string) ($row['template_name'] ?? ''),
                'version' => self::INDEX_DECORATION_SETTING_VERSION,
                'page' => '1',
                'page_size' => '50',
                'weapp_setting_id' => null,
                'goods_grid_tab_id' => null,
                'pages_template_id' => $pid,
                'e_activity_id' => $eActivityId,
            ];

            $customPageName = self::weappSettingPageNameForCustomizePage($customizeId);
            if ($customPageName !== null) {
                $customParams = array_merge($indexParams, [
                    'version' => self::CUSTOM_DECORATION_SETTING_VERSION,
                    'weapp_setting_page_name' => $customPageName,
                ]);
                $detail = $pagesTemplateServices->content($customParams);
                if (!self::pageTemplateDetailHasNonEmptyList($detail)) {
                    $customParams['weapp_setting_pages_template_id'] = 0;
                    $detail = $pagesTemplateServices->content($customParams);
                }
                if (!self::pageTemplateDetailHasNonEmptyList($detail)) {
                    app('log')->warning('[StoreHomePageService] enterprise_store_home 自定义页装修无匹配 wechat_weapp_setting，回退 index', [
                        'company_id' => $companyId,
                        'store_home_page_id' => $id,
                        'weapp_customize_page_id' => $customizeId,
                        'weapp_setting_page_name' => $customPageName,
                    ]);
                    $detail = $pagesTemplateServices->content($indexParams);
                }
                $base['page_template_detail'] = $detail;
            } else {
                $base['page_template_detail'] = $pagesTemplateServices->content($indexParams);
            }
        }

        return $base;
    }

    /**
     * @param array<string,mixed> $storeHomeRow
     *
     * @return array{
     *   resolved_pages_template_id: int|null,
     *   template_meta: array<string,mixed>|null,
     *   pages_template_record: array<string,mixed>|null
     * }
     */
    private function resolvePagesTemplateForStoreHomeRow(int $companyId, array $storeHomeRow): array
    {
        $templateName = (string) ($storeHomeRow['template_name'] ?? '');
        if ($templateName === '') {
            return [
                'resolved_pages_template_id' => null,
                'template_meta' => null,
                'pages_template_record' => null,
            ];
        }

        $distributorId = (int) ($storeHomeRow['distributor_id'] ?? 0);
        $weappPages = $distributorId > 0 ? 'distributor_index' : 'index';

        $pagesTemplateServices = new PagesTemplateServices();
        $listResult = $pagesTemplateServices->lists([
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'weapp_pages' => $weappPages,
            'page_no' => 1,
            'page_size' => 100,
        ]);

        $rows = $listResult['list'] ?? [];
        $picked = self::pickResolvedPagesTemplateRow(is_array($rows) ? $rows : [], $templateName);

        if ($picked === null) {
            return [
                'resolved_pages_template_id' => null,
                'template_meta' => null,
                'pages_template_record' => null,
            ];
        }

        $pid = isset($picked['pages_template_id']) ? (int) $picked['pages_template_id'] : 0;

        return [
            'resolved_pages_template_id' => $pid > 0 ? $pid : null,
            'template_meta' => [
                'template_title' => $picked['template_title'] ?? '',
                'template_pic' => $picked['template_pic'] ?? '',
                'weapp_pages' => $picked['weapp_pages'] ?? '',
                'status' => $picked['status'] ?? null,
            ],
            'pages_template_record' => $picked,
        ];
    }

    /**
     * 从 pages_template 列表中选出与内购模版 template_name 一致且启用的记录；多条时取列表顺序第一条。
     *
     * @param array<int,array<string,mixed>> $pagesTemplateListRows
     *
     * @return array<string,mixed>|null
     */
    public static function pickResolvedPagesTemplateRow(array $pagesTemplateListRows, string $templateName): ?array
    {
        if ($templateName === '') {
            return null;
        }

        $enabledFirst = [];
        $anyMatch = [];

        foreach ($pagesTemplateListRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['template_name'] ?? '') !== $templateName) {
                continue;
            }
            $anyMatch[] = $row;
            if ((int) ($row['status'] ?? 0) === 1) {
                $enabledFirst[] = $row;
            }
        }

        if ($enabledFirst !== []) {
            return $enabledFirst[0];
        }

        if ($anyMatch !== []) {
            return $anyMatch[0];
        }

        return null;
    }

    public function deleteRow(int $companyId, int $authDistributorId, int $id): bool
    {
        $row = $this->repository->getInfo(['id' => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException('未查询到数据');
        }
        StoreHomePageAccess::assertRowMatchesDealer($row, $authDistributorId);

        $cid = (int) ($row['weapp_customize_page_id'] ?? 0);
        if ($cid > 0) {
            $pageInfo = $this->customizePageService->getInfoById($cid);
            if ($pageInfo) {
                $delParams = [
                    'template_name' => $pageInfo['template_name'],
                    'company_id' => $companyId,
                    'page_name' => 'custom_'.$cid,
                ];
                $this->weappSettingRepository->deleteBy($delParams);
                $this->customizePageService->deleteBy(['id' => $cid, 'company_id' => $companyId]);
            }
        }

        return $this->repository->deleteById($id);
    }

    /**
     * @param mixed $raw
     */
    private static function normalizeIsOpen($raw): bool
    {
        if ($raw === 'false' || $raw === false || $raw === 0 || $raw === '0') {
            return false;
        }

        return true;
    }
}
