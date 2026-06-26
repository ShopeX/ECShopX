<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use DistributionBundle\Repositories\DistributorRepository;
use DistributionBundle\Repositories\DistributorItemsRepository;
use GoodsBundle\Repositories\ItemRelAttributesRepository;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use GoodsBundle\Repositories\ItemsRelCatsRepository;
use GoodsBundle\Repositories\ItemsRepository;
use GoodsBundle\Services\ItemsAttributesService;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 商品同步 {@see shuyun.base.product.sync}；单次请求最多 50 条商品对象。
 * 全部店铺（含虚拟店）：仅 `platform: offline` 一次 POST；`shop_id`、`category_id` 为原值，无后缀。
 *
 * A-PROD-05：必填缺失的条目在入网关前剔除并打日志，不阻塞其余条目。
 *
 * skus[].sku_detail：多规格时为「规格名:规格值」逗号拼接（如 尺码:L,颜色:红色）；单规格（无有效规格或仅一维）时传「单规格」。
 * skus[].stock：当前不同步库存，字段不输出（需要时恢复 {@see buildSkuRow} 内注释）。
 * skus[].status：Integer。**虚拟店**（distributor_self）按 items.approve_status，仅 onsale 为 1；**非虚拟店**仍按 distribution_distributor_items.is_can_sale。
 */
class ShuyunOpenPlatformProductSyncService
{
    public const GATEWAY_ACTION_PRODUCT_SYNC = 'shuyun.base.product.sync';

    public const BATCH_SIZE = 50;

    /** 单 SKU / 单规格维度时同步至数云的 sku_detail 固定文案 */
    public const SKU_DETAIL_SINGLE_SPEC = '单规格';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ItemsRepository $itemsRepository;

    private DistributorRepository $distributorRepository;

    private DistributorItemsRepository $distributorItemsRepository;

    private ItemsRelCatsRepository $itemsRelCatsRepository;

    private ItemsCategoryRepository $itemsCategoryRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver;

    private ItemRelAttributesRepository $itemRelAttributesRepository;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ItemsRepository $itemsRepository,
        DistributorRepository $distributorRepository,
        DistributorItemsRepository $distributorItemsRepository,
        ItemsRelCatsRepository $itemsRelCatsRepository,
        ItemsCategoryRepository $itemsCategoryRepository,
        ItemRelAttributesRepository $itemRelAttributesRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->itemsRepository = $itemsRepository;
        $this->distributorRepository = $distributorRepository;
        $this->distributorItemsRepository = $distributorItemsRepository;
        $this->itemsRelCatsRepository = $itemsRelCatsRepository;
        $this->itemsCategoryRepository = $itemsCategoryRepository;
        $this->itemRelAttributesRepository = $itemRelAttributesRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->httpClient = $httpClient;
        $this->gatewayShopIdResolver = $gatewayShopIdResolver;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * 按 SPU（default_item_id）组装当前店铺可见 SKU 并同步（店铺维度+distributor_id）。
     *
     * @return bool false 合格租户下网关失败；true 成功或无待发数据
     */
    public function syncProductByDefaultItem(int $companyId, int $distributorId, int $defaultItemId): bool
    {
        if ($companyId < 1 || $distributorId < 1 || $defaultItemId < 1) {
            return true;
        }

        $variants = $this->itemsRepository->list(
            ['company_id' => $companyId, 'default_item_id' => $defaultItemId],
            ['item_id' => 'ASC'],
            -1,
        );
        if (($variants['list'] ?? []) === []) {
            return true;
        }

        $variantIds = array_column($variants['list'], 'item_id');
        $diList = $this->distributorItemsRepository->lists(
            [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'item_id' => $variantIds,
            ],
            ['item_id' => 'ASC'],
            -1,
            1,
        );
        /** @var array<int, array<string, mixed>> $diByItem */
        $diByItem = [];
        foreach (($diList['list'] ?? []) as $row) {
            $diByItem[(int) ($row['item_id'] ?? 0)] = $row;
        }

        $dist = $this->distributorRepository->getInfo([
            'distributor_id' => $distributorId,
            'company_id' => $companyId,
        ]);
        if ($dist === [] || $dist === null) {
            return true;
        }

        $product = $this->buildProductBodyFromVariants(
            $companyId,
            $distributorId,
            $defaultItemId,
            $variants['list'],
            $diByItem,
            $dist,
        );
        if ($product === null) {
            return true;
        }

        return $this->syncValidatedProductPayloads($companyId, [$product], $dist, [
            'distributor_id' => $distributorId,
            'default_item_id' => $defaultItemId,
        ]);
    }

    /**
     * 已结构化的商品对象列表（每个元素为单商品 JSON 对象）。会自动校验、按 {@see BATCH_SIZE} 切片并对 offline platform POST。
     *
     * @param  list<array<string, mixed>>  $products
     * @param  array<string, mixed>|null  $distributorRowForPlatformPolicy 分销商行（虚拟店策略等）
     * @param  array<string, mixed>  $logContext  失败日志附加字段（如 distributor_id、default_item_id）
     */
    public function syncValidatedProductPayloads(int $companyId, array $products, ?array $distributorRowForPlatformPolicy = null, array $logContext = []): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        $valid = [];
        foreach ($products as $idx => $row) {
            if (!is_array($row)) {
                $this->logSkipInvalid($companyId, 'row_not_array', $idx, null);

                continue;
            }
            $err = $this->validateProductRow($row);
            if ($err !== null) {
                $this->logSkipInvalid($companyId, $err, $idx, $row);

                continue;
            }
            $valid[] = $row;
        }

        if ($valid === []) {
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

        $platforms = $this->resolvePlatformHeaders($config, $distributorRowForPlatformPolicy);
        $chunks = array_chunk($valid, self::BATCH_SIZE);

        try {
            foreach ($platforms as $platformHeader) {
                foreach ($chunks as $chunk) {
                    $payload = [];
                    foreach ($chunk as $unit) {
                        $payload[] = $this->adaptProductPayloadForGatewayPlatform($unit, $platformHeader);
                    }
                    $client->postJson(self::GATEWAY_ACTION_PRODUCT_SYNC, $payload, $tokenStr, $platformHeader);
                }
            }
        } catch (ShuyunGatewayBusinessException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayBusinessException', $e->getBusinessCode(), $e->getMessage(), $logContext);

            return false;
        } catch (ShuyunGatewayHttpException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayHttpException', $e->getStatusCode(), $e->getMessage(), $logContext);

            return false;
        } catch (ShuyunGatewayJsonException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayJsonException', null, $e->getMessage(), $logContext);

            return false;
        } catch (\Throwable $e) {
            $this->logFailure($companyId, get_class($e), null, $e->getMessage(), $logContext);

            return false;
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $variantRows  items 行
     * @param  array<int, array<string, mixed>>  $diByItem  distribution_distributor_items 按 item_id
     * @param  array<string, mixed>  $distributorRow
     * @return array<string, mixed>|null
     */
    private function buildProductBodyFromVariants(
        int $companyId,
        int $distributorId,
        int $defaultItemId,
        array $variantRows,
        array $diByItem,
        array $distributorRow
    ): ?array {
        $main = null;
        foreach ($variantRows as $row) {
            if ((int) ($row['item_id'] ?? 0) === $defaultItemId) {
                $main = $row;

                break;
            }
        }
        if ($main === null) {
            $main = $variantRows[0];
        }

        $categoryId = $this->resolvePrimaryCategoryId($companyId, $defaultItemId, $main);
        if ($categoryId === '' || $categoryId === '0') {
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning('Shuyun open platform product sync: skip unit, missing category_id.', [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'default_item_id' => $defaultItemId,
                'reason' => 'A-PROD-05_missing_category',
            ]);

            return null;
        }

        try {
            $shopId = $this->gatewayShopIdResolver->resolve($distributorRow);
        } catch (\InvalidArgumentException $e) {
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning('Shuyun open platform product sync: cannot resolve shop_id from distributor.', [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
        $variantItemIds = array_column($variantRows, 'item_id');
        $skuDetailByItemId = [];
        if ($variantItemIds !== []) {
            $attrList = $this->itemRelAttributesRepository->lists(
                ['item_id' => $variantItemIds, 'attribute_type' => 'item_spec'],
                1,
                -1,
                ['attribute_sort' => 'ASC'],
            );
            $skuDetailByItemId = $this->buildShuyunSkuDetailByItemId($attrList['list'] ?? []);
        }

        $virtualShop = $this->isVirtualDistributorRow($distributorRow);
        $skus = [];
        foreach ($variantRows as $row) {
            $iid = (int) ($row['item_id'] ?? 0);
            if (!isset($diByItem[$iid])) {
                continue;
            }
            $di = $diByItem[$iid];
            $skus[] = $this->buildSkuRow($row, $di, $skuDetailByItemId[$iid] ?? self::SKU_DETAIL_SINGLE_SPEC, $virtualShop);
        }
        if ($skus === []) {
            return null;
        }

        $isGift = $main['is_gift'] ?? false;
        $type = ($isGift === true || $isGift === 'true' || $isGift === 1 || $isGift === '1') ? 'SY_GIFT' : 'SY_NORMAL';

        $body = [
            'shop_id' => $shopId,
            'product_id' => ShuyunOpenPlatformItemProductIdResolver::resolveFromItemRow($main, $defaultItemId),
            'product_name' => (string) ($main['item_name'] ?? ''),
            'category_id' => $categoryId,
            'type' => $type,
            'modified' => $this->formatDateTime($main['updated'] ?? null),
            'status' => $this->mapApproveStatusToShuyun((string) ($main['approve_status'] ?? '')),
            'price' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber((int) ($main['price'] ?? 0)),
            'skus' => $skus,
        ];
        $bn = trim((string) ($main['item_bn'] ?? ''));
        if ($bn !== '') {
            $body['outer_product_id'] = $bn;
        }

        return $body;
    }

    /**
     * @param  list<array<string, mixed>>  $attrListRows  items_rel_attributes 行（item_spec）
     * @return array<int, string> item_id => sku_detail
     */
    private function buildShuyunSkuDetailByItemId(array $attrListRows): array
    {
        if ($attrListRows === []) {
            return [];
        }
        $svc = new ItemsAttributesService();
        $parsed = $svc->getItemsRelAttrValuesList($attrListRows);
        $byItem = $parsed['item_spec'] ?? [];
        $out = [];
        foreach ($byItem as $itemId => $specsByAttr) {
            if (!is_array($specsByAttr)) {
                continue;
            }
            $parts = [];
            foreach ($specsByAttr as $spec) {
                if (!is_array($spec)) {
                    continue;
                }
                $name = trim((string) ($spec['spec_name'] ?? ''));
                $val = trim((string) ($spec['spec_value_name'] ?? ''));
                if ($name === '' && $val === '') {
                    continue;
                }
                $parts[] = $name.':'.$val;
            }
            $iid = (int) $itemId;
            // 多规格维度：逗号拼接；单规格或无有效规格：固定「单规格」
            $out[$iid] = count($parts) > 1 ? implode(',', $parts) : self::SKU_DETAIL_SINGLE_SPEC;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $itemRow
     * @param  array<string, mixed>  $diRow
     * @param  bool  $virtualDistributor 虚拟门店：sku status 跟 items.approve_status；否则跟 di.is_can_sale
     * @return array<string, mixed>
     */
    private function buildSkuRow(array $itemRow, array $diRow, string $skuDetail, bool $virtualDistributor): array
    {
        $priceFen = (int) ($diRow['price'] ?? 0) > 0 ? (int) $diRow['price'] : (int) ($itemRow['price'] ?? 0);

        if ($virtualDistributor) {
            $skuStatus = $this->mapItemApproveStatusToSkuOnlineInt((string) ($itemRow['approve_status'] ?? ''));
        } else {
            $canSale = $diRow['is_can_sale'] ?? true;
            $skuStatus = ($canSale === true || $canSale === 'true' || $canSale === 1 || $canSale === '1') ? 1 : 0;
        }

        return [
            'sku_id' => (string) (int) ($itemRow['item_id'] ?? 0),
            'sku_detail' => $skuDetail,
            'price' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($priceFen),
            // 'stock' => (int) ($diRow['store'] ?? 0),
            'status' => $skuStatus,
        ];
    }

    private function resolvePrimaryCategoryId(int $companyId, int $defaultItemId, array $mainItemRow): string
    {
        $rel = $this->itemsRelCatsRepository->lists(
            ['company_id' => $companyId, 'item_id' => $defaultItemId],
            ['category_id' => 'ASC'],
            10000,
            1,
        );

        foreach (($rel['list'] ?? []) as $relRow) {
            $categoryId = (int) ($relRow['category_id'] ?? 0);
            if ($categoryId < 1) {
                continue;
            }

            $categoryRow = $this->itemsCategoryRepository->getInfo([
                'category_id' => $categoryId,
                'company_id' => $companyId,
            ]);
            if ((int) ($categoryRow['category_level'] ?? 0) === 3) {
                return (string) $categoryId;
            }
        }

        $raw = trim((string) ($mainItemRow['item_category'] ?? ''));
        if ($raw !== '' && is_numeric($raw)) {
            return (string) (int) $raw;
        }

        return '';
    }

    private function mapApproveStatusToShuyun(string $approveStatus): string
    {
        return match ($approveStatus) {
            'onsale', 'only_show' => 'SY_ONLINE',
            default => 'SY_OFFLINE',
        };
    }

    /**
     * 数云 skus[].status（仅虚拟店使用）：0 无效 / 1 有效；与 items.approve_status 对齐，仅 onsale 为 1。
     */
    private function mapItemApproveStatusToSkuOnlineInt(string $approveStatus): int
    {
        return trim($approveStatus) === 'onsale' ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateProductRow(array $row): ?string
    {
        if (trim((string) ($row['shop_id'] ?? '')) === '') {
            return 'missing_shop_id';
        }
        if (trim((string) ($row['product_id'] ?? '')) === '') {
            return 'missing_product_id';
        }
        if (trim((string) ($row['product_name'] ?? '')) === '') {
            return 'missing_product_name';
        }
        if (trim((string) ($row['category_id'] ?? '')) === '' || (string) ($row['category_id'] ?? '') === '0') {
            return 'missing_category_id';
        }
        if (trim((string) ($row['modified'] ?? '')) === '') {
            return 'missing_modified';
        }
        $st = (string) ($row['status'] ?? '');
        if ($st === '' || !str_starts_with($st, 'SY_')) {
            return 'missing_or_invalid_status';
        }
        if (!is_numeric($row['price'] ?? null)) {
            return 'missing_or_invalid_price';
        }
        $skus = $row['skus'] ?? null;
        if (!is_array($skus) || $skus === []) {
            return 'missing_skus';
        }
        foreach ($skus as $sku) {
            if (!is_array($sku)) {
                return 'invalid_sku_row';
            }
            if (trim((string) ($sku['sku_id'] ?? '')) === '') {
                return 'missing_sku_id';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function adaptProductPayloadForGatewayPlatform(array $product, string $platformHeader): array
    {
        return $product;
    }

    /**
     * @return list<string>
     */
    private function resolvePlatformHeaders(CompanyShuyunOpenPlatformConfig $config, ?array $distributorRow = null): array
    {
        return ['offline'];
    }

    /**
     * @param  array<string, mixed>|null  $row
     */
    private function isVirtualDistributorRow(?array $row): bool
    {
        if ($row === null) {
            return false;
        }
        $v = $row['distributor_self'] ?? null;
        if ($v === true || $v === 1) {
            return true;
        }

        return trim((string) $v) === '1';
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

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailure(int $companyId, string $exceptionClass, mixed $code, string $message, array $context = []): void
    {
        Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning('Shuyun open platform product sync failed.', array_merge([
            'company_id' => $companyId,
            'gateway_action' => self::GATEWAY_ACTION_PRODUCT_SYNC,
            'exception_class' => $exceptionClass,
            'code' => $code,
            'message' => mb_substr($message, 0, 500),
        ], $context));
    }

    /**
     * @param  array<string, mixed>|null  $row
     */
    private function logSkipInvalid(int $companyId, string $reason, int $index, ?array $row): void
    {
        Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->notice('Shuyun open platform product sync: skipped invalid payload row (A-PROD-05).', [
            'company_id' => $companyId,
            'index' => $index,
            'reason' => $reason,
            'product_id' => is_array($row) ? ($row['product_id'] ?? null) : null,
        ]);
    }
}
