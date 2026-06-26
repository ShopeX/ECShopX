<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use ShuyunOpenPlatformBundle\Entities\ShuyunOpenPlatformTrafficAudit;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformCallbackRequestDebug;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOpenPlatformTrafficAuditRepository;

/**
 * 数云开放网关出站 / 入站回调排障审计写入（失败不阻断业务）。
 */
final class ShuyunOpenPlatformTrafficAuditWriter
{
    public const DIRECTION_OUTBOUND = 'outbound';

    public const DIRECTION_INBOUND = 'inbound';

    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_BUSINESS_ERROR = 'business_error';

    public const OUTCOME_TRANSPORT_ERROR = 'transport_error';

    public const OUTCOME_PARSE_ERROR = 'parse_error';

    private LoggerInterface $logger;

    private ManagerRegistry $managerRegistry;

    private ?CompanyShuyunOpenPlatformConfigRepository $configRepository;

    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry,
        ?CompanyShuyunOpenPlatformConfigRepository $configRepository = null
    ) {
        $this->logger = $logger;
        $this->managerRegistry = $managerRegistry;
        $this->configRepository = $configRepository;
    }

    /**
     * @param array<string, mixed> $requestHeaders
     */
    public function writeOutbound(
        int $companyId,
        string $correlationId,
        string $httpVerb,
        ?string $actionMethod,
        array $requestHeaders,
        ?string $requestBody,
        ?int $httpStatus,
        string $outcome,
        ?string $responseBody,
        ?string $errorMessage = null
    ): void {
        $this->persistSafe(function (ShuyunOpenPlatformTrafficAuditRepository $repository) use (
            $companyId,
            $correlationId,
            $httpVerb,
            $actionMethod,
            $requestHeaders,
            $requestBody,
            $httpStatus,
            $outcome,
            $responseBody,
            $errorMessage
        ): void {
            $row = new ShuyunOpenPlatformTrafficAudit();
            $row->setCompanyId($companyId);
            $row->setDirection(self::DIRECTION_OUTBOUND);
            $row->setCorrelationId($correlationId);
            $row->setHttpVerb(strtoupper($httpVerb));
            $row->setActionMethod($actionMethod);
            $row->setHttpStatus($httpStatus);
            $row->setOutcome($outcome);
            $row->setRequestHeadersJson($this->encodeJson($requestHeaders));
            $row->setRequestBody($requestBody);
            $row->setResponseBody($responseBody);
            $row->setErrorMessage($this->truncateErrorMessage($errorMessage));
            $repository->persistAndFlush($row);
        });
    }

    /**
     * 入站审计：`actionMethod` 写入实体 `action_method`（与出站同一列，配合 `direction=inbound` 区分语义）。
     *
     * @param array<string, mixed> $requestHeaders
     */
    public function writeInbound(
        int $companyId,
        string $actionMethod,
        string $correlationId,
        string $httpVerb,
        array $requestHeaders,
        ?string $requestBody,
        int $httpStatus,
        string $outcome,
        ?string $responseBody,
        ?string $errorMessage = null
    ): void {
        $this->persistSafe(function (ShuyunOpenPlatformTrafficAuditRepository $repository) use (
            $companyId,
            $actionMethod,
            $correlationId,
            $httpVerb,
            $requestHeaders,
            $requestBody,
            $httpStatus,
            $outcome,
            $responseBody,
            $errorMessage
        ): void {
            $row = new ShuyunOpenPlatformTrafficAudit();
            $row->setCompanyId($companyId);
            $row->setDirection(self::DIRECTION_INBOUND);
            $row->setCorrelationId($correlationId);
            $row->setHttpVerb(strtoupper($httpVerb));
            $row->setActionMethod($actionMethod);
            $row->setHttpStatus($httpStatus);
            $row->setOutcome($outcome);
            $row->setRequestHeadersJson($this->encodeJson($requestHeaders));
            $row->setRequestBody($requestBody);
            $row->setResponseBody($responseBody);
            $row->setErrorMessage($this->truncateErrorMessage($errorMessage));
            $repository->persistAndFlush($row);
        });
    }

    /**
     * 从 Token 回调原始体解析 appId 并解析 company_id（失败则 0）。
     */
    public function resolveCompanyIdFromTokenCallbackBody(string $rawBody): int
    {
        if ($this->configRepository === null) {
            return 0;
        }
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return 0;
        }
        if (!is_array($decoded)) {
            return 0;
        }
        $items = $decoded;
        if ($decoded !== [] && !array_is_list($decoded) && isset($decoded['accessToken'], $decoded['authValue'])) {
            $items = [$decoded];
        }
        if (!is_array($items) || $items === []) {
            return 0;
        }
        $appIds = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $authValue = isset($item['authValue']) ? (string) $item['authValue'] : '';
            if ($authValue === '') {
                continue;
            }
            $appId = isset($item['appId']) ? (string) $item['appId'] : '';
            if ($appId !== '') {
                $appIds[$appId] = true;
            }
        }
        $keys = array_keys($appIds);
        if (count($keys) !== 1) {
            return 0;
        }
        $row = $this->configRepository->findOneByAppId((string) $keys[0]);

        return $row !== null ? (int) $row->getCompanyId() : 0;
    }

    /**
     * @return array{headers: array<string, mixed>, body_raw: string}
     */
    public static function captureFromRequest(Request $request): array
    {
        return ShuyunOpenPlatformCallbackRequestDebug::capture($request);
    }

    /**
     * @param  callable(ShuyunOpenPlatformTrafficAuditRepository): void  $fn
     */
    private function persistSafe(callable $fn): void
    {
        try {
            $this->persistWithOpenEntityManager($fn);
        } catch (\Throwable $e) {
            if (!$this->throwableChainContainsEntityManagerClosed($e)) {
                $this->logger->error('shuyun_open_platform_traffic_audit_persist_failed', [
                    'exception' => $e,
                ]);

                return;
            }
            try {
                $this->managerRegistry->resetManager('default');
                $this->persistWithOpenEntityManager($fn);
            } catch (\Throwable $retry) {
                $this->logger->error('shuyun_open_platform_traffic_audit_persist_failed', [
                    'exception' => $retry,
                    'prior' => $e,
                ]);
            }
        }
    }

    /**
     * @param  callable(ShuyunOpenPlatformTrafficAuditRepository): void  $fn
     */
    private function persistWithOpenEntityManager(callable $fn): void
    {
        $em = $this->managerRegistry->getManager('default');
        if ($em instanceof EntityManagerInterface && !$em->isOpen()) {
            $this->managerRegistry->resetManager('default');
            $em = $this->managerRegistry->getManager('default');
        }
        $repo = $em->getRepository(ShuyunOpenPlatformTrafficAudit::class);
        if (!$repo instanceof ShuyunOpenPlatformTrafficAuditRepository) {
            throw new \RuntimeException('Invalid repository for ShuyunOpenPlatformTrafficAudit.');
        }
        $fn($repo);
    }

    private function throwableChainContainsEntityManagerClosed(\Throwable $e): bool
    {
        if ($e instanceof EntityManagerClosed) {
            return true;
        }
        $prev = $e->getPrevious();

        return $prev instanceof \Throwable && $this->throwableChainContainsEntityManagerClosed($prev);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeJson(array $data): string
    {
        try {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return '{}';
        }
    }

    private function truncateErrorMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return null;
        }
        if (strlen($message) <= 1024) {
            return $message;
        }

        return substr($message, 0, 1021).'...';
    }
}
