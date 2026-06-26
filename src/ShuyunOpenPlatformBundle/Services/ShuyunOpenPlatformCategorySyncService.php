<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 管理分类同步至数云开放网关 {@see shuyun.base.product.category.sync}（仅 2/3 级；2 级名称拼接 1 级，且 parent_category_id 固定为 0）。
 */
class ShuyunOpenPlatformCategorySyncService
{
    public const GATEWAY_ACTION_CATEGORY_SYNC = 'shuyun.base.product.category.sync';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ItemsCategoryRepository $itemsCategoryRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ItemsCategoryRepository $itemsCategoryRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->itemsCategoryRepository = $itemsCategoryRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->httpClient = $httpClient;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @return bool true 成功、跳过（非 2/3 级）或租户不合格；false 合格租户下网关失败
     */
    public function syncCategory(int $companyId, int $categoryId): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        $row = $this->itemsCategoryRepository->getInfo([
            'category_id' => $categoryId,
            'company_id' => $companyId,
        ]);
        if ($row === null || $row === []) {
            return false;
        }

        $level = (int) ($row['category_level'] ?? 0);
        if (!in_array($level, [2, 3], true)) {
            return true;
        }

        $baseUri = (string) config('shuyun_open_platform.base_uri');
        $baseUri = $baseUri !== '' ? rtrim($baseUri, '/').'/' : 'http://open-api.shuyun.com/';

        $client = $this->gatewayClientFactory->create(
            (string) $config->getAppId(),
            (string) $config->getAppSecret(),
            $baseUri,
            $this->httpClient,
            $companyId,
        );
        $token = $config->getAccessToken();
        $tokenStr = $token !== null && $token !== '' ? $token : null;

        $platforms = $this->resolvePlatformHeaders($config);
        try {
            foreach ($platforms as $platformHeader) {
                $body = $this->buildCategoryBody($companyId, $row, $level, $platformHeader);
                $client->postJson(self::GATEWAY_ACTION_CATEGORY_SYNC, $body, $tokenStr, $platformHeader);
            }
        } catch (ShuyunGatewayBusinessException $e) {
            $this->logFailure($companyId, $categoryId, 'ShuyunGatewayBusinessException', $e->getBusinessCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayHttpException $e) {
            $this->logFailure($companyId, $categoryId, 'ShuyunGatewayHttpException', $e->getStatusCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayJsonException $e) {
            $this->logFailure($companyId, $categoryId, 'ShuyunGatewayJsonException', null, $e->getMessage());

            return false;
        } catch (\Throwable $e) {
            $this->logFailure($companyId, $categoryId, get_class($e), null, $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row  items_category getInfo 行
     * @return array<string, string>
     */
    private function buildCategoryBody(int $companyId, array $row, int $level, string $platformHeader): array
    {
        $name = trim((string) ($row['category_name'] ?? ''));
        if ($level === 2) {
            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0) {
                $parent = $this->itemsCategoryRepository->getInfo([
                    'category_id' => $parentId,
                    'company_id' => $companyId,
                ]);
                $pname = trim((string) ($parent['category_name'] ?? ''));
                if ($pname !== '') {
                    $name = $pname.'/'.$name;
                }
            }
        }

        // 二级类目：数云侧根挂在虚拟父级，parent_category_id 固定 0；名称仍拼接一级/二级（见上）。
        if ($level === 2) {
            $parentIdInt = 0;
            $parentCategoryId = '0';
        } else {
            $parentIdInt = (int) ($row['parent_id'] ?? 0);
            $parentCategoryId = (string) $parentIdInt;
        }
        $categoryIdStr = (string) (int) ($row['category_id'] ?? 0);

        $created = $this->formatDateTime($row['created'] ?? null);
        $modified = $this->formatDateTime($row['updated'] ?? null);

        return [
            'parent_category_id' => $parentCategoryId,
            'category_name' => $name,
            'category_id' => $categoryIdStr,
            'created' => $created,
            'modified' => $modified,
        ];
    }

    /**
     * @return list<string> 小写 platform 头取值（postJson 内会 strtolower，此处显式小写）
     */
    private function resolvePlatformHeaders(CompanyShuyunOpenPlatformConfig $config): array
    {
        return ['offline'];
    }

    private function formatDateTime(mixed $v): string
    {
        if ($v === null || $v === '') {
            return date('Y-m-d H:i:s');
        }
        if (is_numeric($v)) {
            $ts = (int) $v;

            return $ts > 0 ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
        }
        if (is_string($v)) {
            $ts = strtotime($v);

            return $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s');
    }

    private function logFailure(int $companyId, int $categoryId, string $exceptionClass, mixed $code, string $message): void
    {
        Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning('Shuyun open platform category sync failed.', [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'gateway_action' => self::GATEWAY_ACTION_CATEGORY_SYNC,
            'exception_class' => $exceptionClass,
            'code' => $code,
            'message' => mb_substr($message, 0, 500),
        ]);
    }
}
