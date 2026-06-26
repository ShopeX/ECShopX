<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 数云线下权益：发送报告 V2、明细 V2、核销结果 V2 出站调用。
 *
 * @see .tasks/plans/shuyun-offline-benefit-coupon.md §3.1（JSON number）、§7 T2
 */
class ShuyunOfflineBenefitReportService
{
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
     * {@see ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_REPORT_PUSH_V2}
     */
    public function pushSendReportV2(
        int $companyId,
        string $platformHeader,
        string $benefitId,
        string $requestId,
        int $total,
        int $success,
        int $failure
    ): bool {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        $platformNorm = strtolower(trim($platformHeader));
        if ($platformNorm === '') {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun offline benefit send.report.push.v2 skipped: empty platform header.', [
                'company_id' => $companyId,
            ]);

            return false;
        }

        $body = [
            'benefitId' => $benefitId,
            'requestId' => $requestId,
            'total' => $total,
            'success' => $success,
            'failure' => $failure,
        ];

        return $this->postJsonOrFalse(
            $companyId,
            $config,
            ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_REPORT_PUSH_V2,
            $body,
            $platformNorm
        );
    }

    /**
     * {@see ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_RESULT_DETAIL_PUSH_V2}
     *
     * @param  list<array<string, mixed>>  $rows  根级 JSON 数组（与数云文档一致）
     */
    public function pushSendResultDetailV2(int $companyId, string $platformHeader, array $rows): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        $platformNorm = strtolower(trim($platformHeader));
        if ($platformNorm === '') {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun offline benefit send.result.detail.push.v2 skipped: empty platform header.', [
                'company_id' => $companyId,
            ]);

            return false;
        }

        if ($rows === []) {
            return true;
        }

        return $this->postJsonOrFalse(
            $companyId,
            $config,
            ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_RESULT_DETAIL_PUSH_V2,
            $rows,
            $platformNorm
        );
    }

    /**
     * {@see ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_RESULT_PUSH_V2}
     *
     * @param  list<array<string, mixed>>  $rows  根级 JSON 数组
     */
    public function pushResultV2(int $companyId, string $platformHeader, array $rows): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return false;
        }

        $platformNorm = strtolower(trim($platformHeader));
        if ($platformNorm === '') {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun offline benefit result.push.v2 skipped: empty platform header.', [
                'company_id' => $companyId,
            ]);

            return false;
        }

        if ($rows === []) {
            return true;
        }

        return $this->postJsonOrFalse(
            $companyId,
            $config,
            ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_RESULT_PUSH_V2,
            $rows,
            $platformNorm
        );
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $body
     */
    private function postJsonOrFalse(
        int $companyId,
        CompanyShuyunOpenPlatformConfig $config,
        string $actionMethod,
        array $body,
        string $platformNorm
    ): bool {
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

        try {
            $client->postJson($actionMethod, $body, $tokenStr, $platformNorm);
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
        Log::channel(self::LOG_CHANNEL)->error('Shuyun offline benefit gateway push failed.', [
            'company_id' => $companyId,
            'exception' => $kind,
            'code' => $code,
            'message' => $message,
        ]);
    }
}
