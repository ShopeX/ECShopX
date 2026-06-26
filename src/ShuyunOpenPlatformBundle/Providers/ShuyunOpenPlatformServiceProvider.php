<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Providers;

use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Entities\AftersalesRefund;
use AftersalesBundle\Repositories\AftersalesDetailRepository;
use AftersalesBundle\Repositories\AftersalesRefundRepository;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorItems;
use DistributionBundle\Repositories\DistributorItemsRepository;
use DistributionBundle\Repositories\DistributorRepository;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Entities\ItemsRelCats;
use GoodsBundle\Repositories\ItemRelAttributesRepository;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use GoodsBundle\Repositories\ItemsRelCatsRepository;
use GoodsBundle\Repositories\ItemsRepository;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Repositories\TradeRepository;
use GuzzleHttp\Client;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use OpenapiBundle\Services\Member\MemberService as OpenapiMemberService;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Entities\ShuyunOpenPlatformTrafficAudit;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOpenPlatformTrafficAuditRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformInboundSignedCallbackPreparer;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;
use ShuyunOpenPlatformBundle\Services\DefaultShuyunOpenPlatformShopPlatCodeResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncTargetPlatCodesResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformCategorySyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyCardGradeQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyMemberPointChangeService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformProductSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformManageConfigService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberBindPushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberEnhanceDetailQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberInfoQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberModifyService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberRegisterService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberUnbindService;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\DoctrineHistoricalSyncStatisticsProvider;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncAssessor;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncCheckpointStore;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncPointBalanceAligner;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncRunner;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncWechatBindResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderRefundPayloadAssembler;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderTradePayloadAssembler;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderPlatformResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderTradeSourceResolver;
use KaquanBundle\Entities\DiscountCards;
use MembersBundle\Services\MemberService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitConsumePushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitOrderLinkService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCouponGrantServiceInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitIssuingMemberResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitIssuingMemberResolverInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitItemIssuerInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitKaquanCouponGrantAdapter;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitKaquanIssuer;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitReportService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitSendBatchProcessor;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitStubIssuer;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformRefundSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTradeSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformScheduledTokenRefreshRunner;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopPlatCodeResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenRefreshService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenRefreshServiceInterface;

/**
 * 数云开放网关：容器绑定（入口层禁止 {@see \ShuyunOpenPlatformBundle\Jobs\SyncShopToShuyunOpenPlatformJob} 等直接 new *Service，见 .tasks/plans/code-style-guide.md T1）。
 */
class ShuyunOpenPlatformServiceProvider extends ServiceProvider
{
    /** @see config/shuyun_open_platform.php `base_uri` / `timeout` */
    public const CONTAINER_BINDING_HTTP_OPEN_API = 'shuyun_open_platform.http.open_api';

    /** Token 刷新 GET 为绝对 URL，客户端仅需 timeout */
    public const CONTAINER_BINDING_HTTP_TOKEN_REFRESH = 'shuyun_open_platform.http.token_refresh';

    public function register(): void
    {
        $this->app->singleton(ShuyunOpenPlatformGatewayShopIdResolver::class);

        $this->app->singleton(ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver::class, static function ($app) {
            return new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
            );
        });

        $this->app->singleton(self::CONTAINER_BINDING_HTTP_OPEN_API, static function () {
            $baseUri = (string) config('shuyun_open_platform.base_uri');
            $baseUri = $baseUri !== '' ? rtrim($baseUri, '/').'/' : 'http://open-api.shuyun.com/';

            return new Client([
                'base_uri' => $baseUri,
                'timeout' => (float) config('shuyun_open_platform.timeout'),
            ]);
        });

        $this->app->singleton(self::CONTAINER_BINDING_HTTP_TOKEN_REFRESH, static function () {
            return new Client([
                'timeout' => (float) config('shuyun_open_platform.timeout'),
            ]);
        });

        $this->app->singleton(ShuyunOpenPlatformInboundSignedCallbackPreparer::class);

        $this->app->singleton(CompanyShuyunOpenPlatformConfigRepository::class, function ($app) {
            $repo = $app['registry']->getManager('default')->getRepository(CompanyShuyunOpenPlatformConfig::class);
            if (!$repo instanceof CompanyShuyunOpenPlatformConfigRepository) {
                throw new \RuntimeException('Invalid repository for CompanyShuyunOpenPlatformConfig.');
            }

            return $repo;
        });

        $this->app->singleton(ShuyunOpenPlatformTrafficAuditRepository::class, function ($app) {
            $repo = $app['registry']->getManager('default')->getRepository(ShuyunOpenPlatformTrafficAudit::class);
            if (!$repo instanceof ShuyunOpenPlatformTrafficAuditRepository) {
                throw new \RuntimeException('Invalid repository for ShuyunOpenPlatformTrafficAudit.');
            }

            return $repo;
        });

        $this->app->singleton(ShuyunOpenPlatformTrafficAuditWriter::class, function ($app) {
            return new ShuyunOpenPlatformTrafficAuditWriter(
                $app->make('log')->channel('shuyun_open_platform'),
                $app['registry'],
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
            );
        });

        $this->app->singleton(ShuyunOpenPlatformGatewayClientFactory::class, function ($app) {
            return new ShuyunOpenPlatformGatewayClientFactory(
                $app->make(ShuyunOpenPlatformTrafficAuditWriter::class),
            );
        });

        $this->app->singleton(ShuyunOfflineBenefitRepository::class, function ($app) {
            $repo = $app['registry']->getManager('default')->getRepository(ShuyunOfflineBenefit::class);
            if (!$repo instanceof ShuyunOfflineBenefitRepository) {
                throw new \RuntimeException('Invalid repository for ShuyunOfflineBenefit.');
            }

            return $repo;
        });

        $this->app->singleton(ShuyunOfflineBenefitSendBatchRepository::class, function ($app) {
            $repo = $app['registry']->getManager('default')->getRepository(ShuyunOfflineBenefitSendBatch::class);
            if (!$repo instanceof ShuyunOfflineBenefitSendBatchRepository) {
                throw new \RuntimeException('Invalid repository for ShuyunOfflineBenefitSendBatch.');
            }

            return $repo;
        });

        $this->app->singleton(ShuyunOfflineBenefitSendItemRepository::class, function ($app) {
            $repo = $app['registry']->getManager('default')->getRepository(ShuyunOfflineBenefitSendItem::class);
            if (!$repo instanceof ShuyunOfflineBenefitSendItemRepository) {
                throw new \RuntimeException('Invalid repository for ShuyunOfflineBenefitSendItem.');
            }

            return $repo;
        });

        $this->app->singleton(ShuyunOfflineBenefitIssuingMemberResolverInterface::class, ShuyunOfflineBenefitIssuingMemberResolver::class);

        $this->app->singleton(ShuyunOfflineBenefitCouponGrantServiceInterface::class, ShuyunOfflineBenefitKaquanCouponGrantAdapter::class);

        $this->app->bind(ShuyunOfflineBenefitItemIssuerInterface::class, function ($app) {
            $issuer = (string) config('shuyun_open_platform.offline_benefit_issuer', 'kaquan');
            if ($issuer === 'stub') {
                return new ShuyunOfflineBenefitStubIssuer();
            }

            return new ShuyunOfflineBenefitKaquanIssuer(
                $app->make(ShuyunOfflineBenefitRepository::class),
                $app->make(ShuyunOfflineBenefitCouponGrantServiceInterface::class),
                $app->make(ShuyunOfflineBenefitIssuingMemberResolverInterface::class),
            );
        });

        $this->app->bind(ShuyunOfflineBenefitSendBatchProcessor::class, function ($app) {
            return new ShuyunOfflineBenefitSendBatchProcessor(
                $app->make(ShuyunOfflineBenefitSendBatchRepository::class),
                $app->make(ShuyunOfflineBenefitSendItemRepository::class),
                $app->make(ShuyunOfflineBenefitItemIssuerInterface::class),
                $app->make(ShuyunOfflineBenefitReportService::class),
            );
        });

        $this->app->bind(ShuyunOfflineBenefitCallbackService::class, function ($app) {
            /** @var \KaquanBundle\Repositories\DiscountCardsRepository $discountCardsRepo */
            $discountCardsRepo = $app->make('registry')->getManager('default')->getRepository(DiscountCards::class);

            return new ShuyunOfflineBenefitCallbackService(
                $app->make(ShuyunOfflineBenefitRepository::class),
                $app->make(ShuyunOfflineBenefitSendBatchRepository::class),
                $app->make(ShuyunOfflineBenefitSendItemRepository::class),
                $app->make(Dispatcher::class),
                $app->make(ShuyunOfflineBenefitSendBatchProcessor::class),
                $discountCardsRepo,
            );
        });

        $this->app->bind(ShuyunOfflineBenefitConsumePushService::class, function ($app) {
            $em = $app['registry']->getManager('default');
            $normalOrdersRepo = $em->getRepository(NormalOrders::class);
            $distributorRepo = $em->getRepository(Distributor::class);
            if (!$normalOrdersRepo instanceof NormalOrdersRepository) {
                throw new \RuntimeException('Invalid repository for NormalOrders.');
            }
            if (!$distributorRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }

            return new ShuyunOfflineBenefitConsumePushService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOfflineBenefitReportService::class),
                $app->make(ShuyunOfflineBenefitSendItemRepository::class),
                $normalOrdersRepo,
                $distributorRepo,
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
            );
        });

        $this->app->bind(ShuyunOfflineBenefitOrderLinkService::class, function ($app) {
            return new ShuyunOfflineBenefitOrderLinkService(
                $app->make(ShuyunOfflineBenefitSendItemRepository::class),
            );
        });

        $this->app->singleton(ShuyunOpenPlatformShopPlatCodeResolver::class, DefaultShuyunOpenPlatformShopPlatCodeResolver::class);

        $this->app->singleton(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class);

        $this->app->singleton(ShuyunOpenPlatformMergedJobDispatchService::class, function ($app) {
            return new ShuyunOpenPlatformMergedJobDispatchService(
                $app['cache']->store(),
                (int) config('shuyun_open_platform.merge_dispatch_ttl_seconds', 3),
            );
        });

        $this->app->bind(ShuyunOpenPlatformShopSyncService::class, function ($app) {
            return new ShuyunOpenPlatformShopSyncService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopPlatCodeResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformCategorySyncService::class, function ($app) {
            $itemsRepo = $app['registry']->getManager('default')->getRepository(ItemsCategory::class);
            if (!$itemsRepo instanceof ItemsCategoryRepository) {
                throw new \RuntimeException('Invalid repository for ItemsCategory.');
            }

            return new ShuyunOpenPlatformCategorySyncService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $itemsRepo,
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformProductSyncService::class, function ($app) {
            $itemsRepo = $app['registry']->getManager('default')->getRepository(Items::class);
            if (!$itemsRepo instanceof ItemsRepository) {
                throw new \RuntimeException('Invalid repository for Items.');
            }
            $distributorRepo = $app['registry']->getManager('default')->getRepository(Distributor::class);
            if (!$distributorRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }
            $distributorItemsRepo = $app['registry']->getManager('default')->getRepository(DistributorItems::class);
            if (!$distributorItemsRepo instanceof DistributorItemsRepository) {
                throw new \RuntimeException('Invalid repository for DistributorItems.');
            }
            $relCatsRepo = $app['registry']->getManager('default')->getRepository(ItemsRelCats::class);
            if (!$relCatsRepo instanceof ItemsRelCatsRepository) {
                throw new \RuntimeException('Invalid repository for ItemsRelCats.');
            }
            $itemsCategoryRepo = $app['registry']->getManager('default')->getRepository(ItemsCategory::class);
            if (!$itemsCategoryRepo instanceof ItemsCategoryRepository) {
                throw new \RuntimeException('Invalid repository for ItemsCategory.');
            }
            $itemRelAttrRepo = $app['registry']->getManager('default')->getRepository(ItemRelAttributes::class);
            if (!$itemRelAttrRepo instanceof ItemRelAttributesRepository) {
                throw new \RuntimeException('Invalid repository for ItemRelAttributes.');
            }

            return new ShuyunOpenPlatformProductSyncService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $itemsRepo,
                $distributorRepo,
                $distributorItemsRepo,
                $relCatsRepo,
                $itemsCategoryRepo,
                $itemRelAttrRepo,
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformLoyaltyCardGradeQueryService::class, function ($app) {
            return new ShuyunOpenPlatformLoyaltyCardGradeQueryService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformLoyaltyGradeSyncService::class, function ($app) {
            $querySvc = $app->make(ShuyunOpenPlatformLoyaltyCardGradeQueryService::class);
            $distRepo = $app['registry']->getManager('default')->getRepository(Distributor::class);
            if (!$distRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }

            return new ShuyunOpenPlatformLoyaltyGradeSyncService(
                $distRepo,
                $app->make(MemberCardGradeService::class),
                static fn (int $companyId, array $virtualDistributorRow): ?array => $querySvc->queryGradeCard($companyId, $virtualDistributorRow),
            );
        });

        $this->app->singleton(ShuyunOpenPlatformLoyaltyGradeCallbackService::class, static function ($app) {
            return new ShuyunOpenPlatformLoyaltyGradeCallbackService(
                $app->make(OpenapiMemberService::class),
                $app->make(MemberCardGradeService::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberBindPushService::class, function ($app) {
            return new ShuyunOpenPlatformMemberBindPushService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberRegisterService::class, function ($app) {
            return new ShuyunOpenPlatformMemberRegisterService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberInfoQueryService::class, function ($app) {
            return new ShuyunOpenPlatformMemberInfoQueryService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberEnhanceDetailQueryService::class, function ($app) {
            return new ShuyunOpenPlatformMemberEnhanceDetailQueryService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberModifyService::class, function ($app) {
            return new ShuyunOpenPlatformMemberModifyService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformMemberUnbindService::class, function ($app) {
            return new ShuyunOpenPlatformMemberUnbindService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformLoyaltyPointChangelogSearchService::class, function ($app) {
            $em = $app['registry']->getManager('default');
            $distributorRepo = $em->getRepository(Distributor::class);
            if (!$distributorRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }

            return new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $distributorRepo,
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformLoyaltyMemberPointChangeService::class, function ($app) {
            return new ShuyunOpenPlatformLoyaltyMemberPointChangeService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformManageConfigService::class, function ($app) {
            return new ShuyunOpenPlatformManageConfigService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class)
            );
        });

        $this->app->bind(ShuyunOpenPlatformTokenCallbackService::class, function ($app) {
            return new ShuyunOpenPlatformTokenCallbackService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformTokenRefreshServiceInterface::class, function ($app) {
            return new ShuyunOpenPlatformTokenRefreshService(
                (string) config('shuyun_open_platform.token_refresh_base_uri'),
                (float) config('shuyun_open_platform.timeout'),
                $app->make(self::CONTAINER_BINDING_HTTP_TOKEN_REFRESH),
            );
        });

        $this->app->bind(ShuyunOpenPlatformTokenRefreshService::class, function ($app) {
            return $app->make(ShuyunOpenPlatformTokenRefreshServiceInterface::class);
        });

        $this->app->bind(ShuyunOpenPlatformScheduledTokenRefreshRunner::class, function ($app) {
            return new ShuyunOpenPlatformScheduledTokenRefreshRunner(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformTokenRefreshServiceInterface::class),
            );
        });

        $this->app->singleton(ShuyunOpenPlatformOrderPlatformResolver::class);

        $this->app->singleton(ShuyunOpenPlatformOrderTradeSourceResolver::class, function ($app) {
            return new ShuyunOpenPlatformOrderTradeSourceResolver(
                $app->make('log')->channel('shuyun_open_platform'),
            );
        });

        $this->app->bind(ShuyunOpenPlatformTradeSyncService::class, function ($app) {
            return new ShuyunOpenPlatformTradeSyncService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformRefundSyncService::class, function ($app) {
            return new ShuyunOpenPlatformRefundSyncService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOfflineBenefitReportService::class, function ($app) {
            return new ShuyunOfflineBenefitReportService(
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(self::CONTAINER_BINDING_HTTP_OPEN_API),
                $app->make(ShuyunOpenPlatformGatewayClientFactory::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformNormalOrderTradePayloadAssembler::class, function ($app) {
            $em = $app['registry']->getManager('default');
            $normalOrdersRepo = $em->getRepository(NormalOrders::class);
            $normalOrderItemsRepo = $em->getRepository(NormalOrdersItems::class);
            $tradeRepo = $em->getRepository(Trade::class);
            $itemsRepo = $em->getRepository(Items::class);
            $distributorRepo = $em->getRepository(Distributor::class);
            if (!$normalOrdersRepo instanceof NormalOrdersRepository) {
                throw new \RuntimeException('Invalid repository for NormalOrders.');
            }
            if (!$normalOrderItemsRepo instanceof NormalOrdersItemsRepository) {
                throw new \RuntimeException('Invalid repository for NormalOrdersItems.');
            }
            if (!$tradeRepo instanceof TradeRepository) {
                throw new \RuntimeException('Invalid repository for Trade.');
            }
            if (!$itemsRepo instanceof ItemsRepository) {
                throw new \RuntimeException('Invalid repository for Items.');
            }
            if (!$distributorRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }

            return new ShuyunOpenPlatformNormalOrderTradePayloadAssembler(
                $normalOrdersRepo,
                $normalOrderItemsRepo,
                $tradeRepo,
                $itemsRepo,
                $distributorRepo,
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
            );
        });

        $this->app->bind(ShuyunOpenPlatformNormalOrderRefundPayloadAssembler::class, function ($app) {
            $em = $app['registry']->getManager('default');
            $refundRepo = $em->getRepository(AftersalesRefund::class);
            $detailRepo = $em->getRepository(AftersalesDetail::class);
            $normalOrdersRepo = $em->getRepository(NormalOrders::class);
            $normalOrderItemsRepo = $em->getRepository(NormalOrdersItems::class);
            $itemsRepo = $em->getRepository(Items::class);
            $distributorRepo = $em->getRepository(Distributor::class);
            if (!$refundRepo instanceof AftersalesRefundRepository) {
                throw new \RuntimeException('Invalid repository for AftersalesRefund.');
            }
            if (!$detailRepo instanceof AftersalesDetailRepository) {
                throw new \RuntimeException('Invalid repository for AftersalesDetail.');
            }
            if (!$normalOrdersRepo instanceof NormalOrdersRepository) {
                throw new \RuntimeException('Invalid repository for NormalOrders.');
            }
            if (!$normalOrderItemsRepo instanceof NormalOrdersItemsRepository) {
                throw new \RuntimeException('Invalid repository for NormalOrdersItems.');
            }
            if (!$itemsRepo instanceof ItemsRepository) {
                throw new \RuntimeException('Invalid repository for Items.');
            }
            if (!$distributorRepo instanceof DistributorRepository) {
                throw new \RuntimeException('Invalid repository for Distributor.');
            }

            return new ShuyunOpenPlatformNormalOrderRefundPayloadAssembler(
                $refundRepo,
                $detailRepo,
                $normalOrdersRepo,
                $normalOrderItemsRepo,
                $itemsRepo,
                $distributorRepo,
                $app->make(ShuyunOpenPlatformGatewayShopIdResolver::class),
            );
        });

        $this->app->singleton(HistoricalSyncCheckpointStore::class, static function () {
            return new HistoricalSyncCheckpointStore(storage_path('shuyun_historical_sync'));
        });

        $this->app->singleton(HistoricalSyncStatisticsProviderInterface::class, static function ($app) {
            return new DoctrineHistoricalSyncStatisticsProvider(
                $app['registry']->getConnection('default'),
            );
        });

        $this->app->singleton(HistoricalSyncWechatBindResolver::class, static function ($app) {
            return new HistoricalSyncWechatBindResolver(
                $app['registry']->getConnection('default'),
            );
        });

        $this->app->singleton(HistoricalSyncAssessor::class, static function ($app) {
            return new HistoricalSyncAssessor(
                $app->make(HistoricalSyncStatisticsProviderInterface::class),
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
            );
        });

        $this->app->singleton(HistoricalSyncPointBalanceAligner::class, static function ($app) {
            return new HistoricalSyncPointBalanceAligner(
                $app->make(MemberService::class),
                $app->make(ShuyunOpenPlatformMemberEnhanceDetailQueryService::class),
                $app->make(ShuyunOpenPlatformLoyaltyMemberPointChangeService::class),
            );
        });

        $this->app->singleton(HistoricalSyncRunner::class, static function ($app) {
            return new HistoricalSyncRunner(
                $app['registry']->getConnection('default'),
                $app->make(CompanyShuyunOpenPlatformConfigRepository::class),
                $app->make(ShuyunOpenPlatformShopSyncService::class),
                $app->make(HistoricalSyncCheckpointStore::class),
                $app->make(ShuyunOpenPlatformCategorySyncService::class),
                $app->make(ShuyunOpenPlatformProductSyncService::class),
                $app->make(ShuyunOpenPlatformMemberRegisterService::class),
                $app->make(ShuyunOpenPlatformMemberBindPushService::class),
                $app->make(MemberService::class),
                $app->make(HistoricalSyncPointBalanceAligner::class),
                $app->make(HistoricalSyncWechatBindResolver::class),
                $app->make(Dispatcher::class),
            );
        });
    }
}
