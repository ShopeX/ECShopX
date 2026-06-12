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
     * 自定义页装修 version 候选（按优先级）。
     * - decorate/index scene=1010：门店维度写入 shop_{distributor_id}
     * - 旧 shopDecoration page_template：固定 v1.0.1（与 distributor 无关）
     *
     * @return list<string>
     */
    public static function customDecorationSettingVersionCandidates(int $rowDistributorId, int $authDistributorId): array
    {
        $distributorId = $rowDistributorId > 0 ? $rowDistributorId : $authDistributorId;
        $versions = [];
        if ($distributorId > 0) {
            $versions[] = 'shop_'.$distributorId;
        }
        $versions[] = self::CUSTOM_DECORATION_SETTING_VERSION;

        return array_values(array_unique($versions));
    }

    /**
     * 拉取 wechat_weapp_setting 时尝试的 template_name（decorate/index scene=1010 曾硬编码 yykweishop）。
     *
     * @param array<string,mixed> $storeHomeRow
     *
     * @return list<string>
     */
    public static function decorationTemplateNameCandidates(array $storeHomeRow): array
    {
        $names = [];
        $primary = (string) ($storeHomeRow['template_name'] ?? '');
        if ($primary !== '') {
            $names[] = $primary;
        }
        if ($primary !== 'yykweishop') {
            $names[] = 'yykweishop';
        }

        return array_values(array_unique($names));
    }

    /**
     * 解析 pages_template 列表时的查询计划（按优先级）。
     *
     * @return list<array{distributor_id: int, weapp_pages: string}>
     */
    public static function pagesTemplateListSearchPlans(int $rowDistributorId): array
    {
        if ($rowDistributorId > 0) {
            return [
                ['distributor_id' => $rowDistributorId, 'weapp_pages' => 'distributor_index'],
                ['distributor_id' => $rowDistributorId, 'weapp_pages' => 'index'],
                ['distributor_id' => 0, 'weapp_pages' => 'index'],
            ];
        }

        return [
            ['distributor_id' => 0, 'weapp_pages' => 'index'],
        ];
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

        $templateName = (string) ($row['template_name'] ?? '');
        $pid = (int) ($resolved['resolved_pages_template_id'] ?? 0);
        $distId = (int) ($row['distributor_id'] ?? 0);
        $customizeId = isset($row['weapp_customize_page_id']) ? (int) $row['weapp_customize_page_id'] : 0;
        if ($templateName !== '' && ($pid > 0 || $customizeId > 0)) {
            $pagesTemplateServices = new PagesTemplateServices();

            $indexParams = [
                'company_id' => $companyId,
                'regionauth_id' => 0,
                'user_id' => $userId,
                'distributor_id' => $distId,
                'weapp_pages' => $distId > 0 ? 'distributor_index' : 'index',
                'template_name' => $templateName,
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
                $base['page_template_detail'] = $this->fetchCustomPageDecorationDetail(
                    $companyId,
                    $row,
                    $customPageName,
                    $distId,
                    $authDistributorId
                );
            } elseif ($pid > 0) {
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

        $pagesTemplateServices = new PagesTemplateServices();
        $picked = null;
        foreach (self::decorationTemplateNameCandidates($storeHomeRow) as $tryTemplateName) {
            foreach (self::pagesTemplateListSearchPlans($distributorId) as $plan) {
                $listResult = $pagesTemplateServices->lists([
                    'company_id' => $companyId,
                    'distributor_id' => $plan['distributor_id'],
                    'weapp_pages' => $plan['weapp_pages'],
                    'page_no' => 1,
                    'page_size' => 100,
                ]);
                $rows = $listResult['list'] ?? [];
                $picked = self::pickResolvedPagesTemplateRow(is_array($rows) ? $rows : [], $tryTemplateName);
                if ($picked !== null) {
                    break 2;
                }
            }
        }

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
     * 自定义页装修：与后台 getParamByTempName 一致（custom_{id} + shop_{distributor_id}，pages_template_id=0）。
     *
     * @param array<string,mixed> $storeHomeRow
     *
     * @return array{list: array<int, array<string,mixed>>, config: array<int, array<string,mixed>>}
     */
    private function fetchCustomPageDecorationDetail(
        int $companyId,
        array $storeHomeRow,
        string $customPageName,
        int $distId,
        int $authDistributorId
    ): array {
        foreach (self::decorationTemplateNameCandidates($storeHomeRow) as $tryTemplateName) {
            foreach (self::customDecorationSettingVersionCandidates($distId, $authDistributorId) as $version) {
                $entities = $this->weappSettingRepository->getParamByTempName(
                    $companyId,
                    $tryTemplateName,
                    $customPageName,
                    null,
                    $version,
                    0
                );
                $list = self::buildTemplateConfListFromWeappSettingEntities($entities, $companyId, $tryTemplateName);
                if ($list !== []) {
                    return self::buildPageTemplateDetailFromTemplateConfList($list);
                }
            }
        }

        return ['list' => [], 'config' => []];
    }

    /**
     * @param mixed $raw
     *
     * @return array<string,mixed>
     */
    public static function safeDecodeWeappSettingParams($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $unserialized = @unserialize($raw);
        if (is_array($unserialized)) {
            return $unserialized;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $entities
     *
     * @return array<int,array<string,mixed>>
     */
    public static function buildTemplateConfListFromWeappSettingEntities($entities, int $companyId, string $fallbackTemplateName = ''): array
    {
        if ($entities instanceof \Traversable) {
            $entities = iterator_to_array($entities);
        }
        if (!is_array($entities)) {
            return [];
        }

        $list = [];
        foreach ($entities as $row) {
            if (!is_object($row)) {
                continue;
            }

            $pageName = method_exists($row, 'getPageName') ? (string) $row->getPageName() : '';
            $list[] = [
                'id' => method_exists($row, 'getId') ? $row->getId() : 0,
                'template_name' => method_exists($row, 'getTemplateName') ? $row->getTemplateName() : $fallbackTemplateName,
                'company_id' => method_exists($row, 'getCompanyId') ? $row->getCompanyId() : $companyId,
                'name' => method_exists($row, 'getName') ? (string) $row->getName() : '',
                'page_name' => $pageName !== '' ? $pageName : 'index',
                'params' => self::safeDecodeWeappSettingParams(method_exists($row, 'getParams') ? $row->getParams() : null),
            ];
        }

        return $list;
    }

    /**
     * @param array<int,array<string,mixed>> $list
     *
     * @return array<string,mixed>
     */
    public static function buildPageTemplateDetailFromTemplateConfList(array $list): array
    {
        $config = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $params = $row['params'] ?? null;
            if (is_array($params) && isset($params['name']) && isset($params['base'])) {
                $config[] = $params;
            }
        }

        return [
            'list' => $list,
            'config' => $config,
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
