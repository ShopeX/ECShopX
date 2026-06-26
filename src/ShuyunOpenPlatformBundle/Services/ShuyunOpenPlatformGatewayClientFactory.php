<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use ShuyunOpenPlatformBundle\Gateway\ShuyunGatewayClient;
use ShuyunOpenPlatformBundle\Gateway\ShuyunSigner;

/**
 * 构造带出站审计的 {@see ShuyunGatewayClient}（与手写 new 参数序一致，附加 companyId）。
 */
final class ShuyunOpenPlatformGatewayClientFactory
{
    private ?ShuyunOpenPlatformTrafficAuditWriter $trafficAuditWriter;

    public function __construct(?ShuyunOpenPlatformTrafficAuditWriter $trafficAuditWriter = null)
    {
        $this->trafficAuditWriter = $trafficAuditWriter;
    }

    public function create(
        string $appId,
        string $appSecret,
        string $baseUri,
        ClientInterface $http,
        int $companyId,
        ?ShuyunSigner $signer = null
    ): ShuyunGatewayClient {
        return new ShuyunGatewayClient(
            $appId,
            $appSecret,
            $baseUri,
            $http,
            $signer,
            $companyId,
            $this->trafficAuditWriter
        );
    }
}
