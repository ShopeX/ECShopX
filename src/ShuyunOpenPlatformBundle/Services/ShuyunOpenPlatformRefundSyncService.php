<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 退款逆向同步 {@see ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_REFUND_SYNC}；body 为退款对象 JSON 数组；单次最多 50 条。
 */
class ShuyunOpenPlatformRefundSyncService
{
    public const BATCH_SIZE = 50;

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->httpClient = $httpClient;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @param  list<array<string, mixed>>  $refunds  已通过业务组装的 refund 对象（与整理表字段一致）
     */
    public function syncValidatedRefunds(int $companyId, string $platformHeader, array $refunds): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        if ($refunds === []) {
            return true;
        }

        $platformNorm = strtolower(trim($platformHeader));
        if ($platformNorm === '') {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync skipped: empty platform header.', [
                'company_id' => $companyId,
            ]);

            return false;
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

        $chunks = array_chunk($refunds, self::BATCH_SIZE);

        try {
            foreach ($chunks as $chunk) {
                $client->postJson(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_REFUND_SYNC, $chunk, $tokenStr, $platformNorm);
            }
        } catch (ShuyunGatewayBusinessException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayBusinessException', (string) $e->getBusinessCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayHttpException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayHttpException', (string) $e->getStatusCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayJsonException $e) {
            $this->logFailure($companyId, 'ShuyunGatewayJsonException', '', $e->getMessage());

            return false;
        } catch (\Throwable $e) {
            $this->logFailure($companyId, get_class($e), '', $e->getMessage());

            return false;
        }

        return true;
    }

    private function logFailure(int $companyId, string $kind, string $code, string $message): void
    {
        Log::channel(self::LOG_CHANNEL)->error('Shuyun refund.sync failed.', [
            'company_id' => $companyId,
            'exception' => $kind,
            'code' => $code,
            'message' => $message,
        ]);
    }
}
