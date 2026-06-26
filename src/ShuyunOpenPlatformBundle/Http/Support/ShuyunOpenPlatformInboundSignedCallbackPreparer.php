<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Auth\ShuyunCallbackSignatureVerifier;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 数云 **入站** 需验签回调的共用逻辑：JSON 解析、租户（appId / platCode / limitShops / 线下权益 benefitId 影子表）、身份注册密匙、{@see ShuyunCallbackSignatureVerifier}。
 * body 仅 {@code platCode=OFFLINE} 时：按 DB {@code plat_code=OFFLINE} 解析租户（须已开启同步或手工订正配置行）；不再使用 env 默认平台码回退。
 *
 * 出站调用数云网关的签名不在此处理。
 */
final class ShuyunOpenPlatformInboundSignedCallbackPreparer
{
    public function __construct(
        private CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private ShuyunOpenPlatformShopSyncService $shopSyncService,
        private ShuyunOfflineBenefitRepository $offlineBenefitRepository,
    ) {
    }

    /**
     * @return JsonResponse|array{companyId: int, body: array<string, mixed>}
     */
    public function prepare(Request $request, ShuyunInboundSignedPrepareMode $mode): JsonResponse|array
    {
        $raw = $request->getContent();
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->invalidJsonResponse($mode);
        }

        if (!\is_array($decoded)) {
            return $this->invalidJsonResponse($mode);
        }

        /** @var array<string, mixed> $decoded */
        $appIdFromQuery = $this->trimStringish($request->query->get('appId'));
        $appIdFromBody = $this->trimStringish($decoded['appId'] ?? null);
        $platCodeFromBody = $this->resolvePlatCodeForTenant($decoded);

        [$config, $earlyResponse] = $this->resolveTenantConfig(
            $appIdFromQuery,
            $appIdFromBody,
            $platCodeFromBody,
            $mode,
        );
        if ($earlyResponse instanceof JsonResponse) {
            return $earlyResponse;
        }

        if ($config === null && $mode === ShuyunInboundSignedPrepareMode::OfflineBenefit) {
            [$config, $earlyResponse] = $this->resolveOfflineBenefitTenantByShadowBenefitId($decoded, $mode);
            if ($earlyResponse instanceof JsonResponse) {
                return $earlyResponse;
            }
        }

        if ($config === null) {
            $benefitIdOnly = $this->trimStringish($decoded['benefitId'] ?? null);
            if ($mode === ShuyunInboundSignedPrepareMode::OfflineBenefit && $benefitIdOnly !== '') {
                return $this->offlineBenefitUnknownShadowResponse();
            }
            if ($appIdFromQuery === '' && $appIdFromBody === '' && $platCodeFromBody === '') {
                return $this->appIdRequiredResponse($mode);
            }

            return $this->unknownAppResponse($mode);
        }

        if ($mode === ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange) {
            if ($config->getIsEnabled() !== 1) {
                return $this->loyaltyJsonResponse('40303', 'LOYALTY_GRADE_CALLBACK_DISABLED');
            }
        }

        $callbackSecret = ShuyunOpenPlatformInboundCallbackSecret::getTrimmedFromConfig();
        if ($callbackSecret === '') {
            return $this->secretNotConfiguredResponse($mode);
        }

        $sign = $this->resolveCallbackSign($request);
        $verifier = new ShuyunCallbackSignatureVerifier();
        if (!$verifier->verifyHttpCallback($callbackSecret, $request, $sign)) {
            return $this->invalidSignResponse($mode);
        }

        if ($mode === ShuyunInboundSignedPrepareMode::OfflineBenefit) {
            if (!$this->shopSyncService->isEligible($config)) {
                return response()->json([
                    'code' => 403,
                    'msg' => 'SHUYUN_OFFLINE_BENEFIT_NOT_ELIGIBLE',
                ], 403);
            }
        }

        return [
            'companyId' => $config->getCompanyId(),
            'body' => $decoded,
        ];
    }

    /**
     * body 顶层 `platCode`，否则 `limitShops` 中首条非空 `platCode`（线下权益创建体常见）。
     *
     * @param  array<string, mixed>  $decoded
     */
    public function resolvePlatCodeForTenant(array $decoded): string
    {
        $pc = $this->trimStringish($decoded['platCode'] ?? null);
        if ($pc !== '') {
            return $pc;
        }

        $shops = $decoded['limitShops'] ?? null;
        if (!\is_array($shops)) {
            return '';
        }

        foreach ($shops as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $p = $this->trimStringish($row['platCode'] ?? null);
            if ($p !== '') {
                return $p;
            }
        }

        return '';
    }

    public function resolveCallbackSign(Request $request): string
    {
        $fromQuery = trim((string) $request->query->get('sign', ''));
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        foreach (['SY-Request-Sign', 'Sy-Request-Sign'] as $name) {
            $h = $request->headers->get($name);
            if ($h !== null && trim($h) !== '') {
                return trim($h);
            }
        }

        return '';
    }

    /**
     * @param  mixed  $value
     */
    public function trimStringish($value): string
    {
        if ($value === null) {
            return '';
        }
        if (\is_string($value) || \is_numeric($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @return array{0: CompanyShuyunOpenPlatformConfig|null, 1: JsonResponse|null}
     */
    private function resolveTenantConfig(
        string $appIdFromQuery,
        string $appIdFromBody,
        string $platCodeFromBody,
        ShuyunInboundSignedPrepareMode $mode,
    ): array {
        if ($appIdFromQuery !== '') {
            return [$this->configRepository->findOneByAppId($appIdFromQuery), null];
        }
        if ($appIdFromBody !== '') {
            return [$this->configRepository->findOneByAppId($appIdFromBody), null];
        }
        if ($platCodeFromBody !== '') {
            $byPlat = $this->configRepository->findAllEnabledByNormalizedPlatCode($platCodeFromBody);
            if (\count($byPlat) > 1) {
                return [null, $this->ambiguousPlatCodeResponse($mode)];
            }
            if (\count($byPlat) > 1) {
                return [null, $this->ambiguousPlatCodeResponse($mode)];
            }

            return [$byPlat[0] ?? null, null];
        }

        return [null, null];
    }

    private function isCanonicalOfflinePlatCode(string $platCodeFromBody): bool
    {
        return strtolower(trim($platCodeFromBody)) === 'offline';
    }

    /**
     * 发放类回调：仅 body.benefitId 时，用 {@see ShuyunOfflineBenefitRepository::findAllByBenefitId} 反查 company_id → 数云配置。
     *
     * @param  array<string, mixed>  $decoded
     *
     * @return array{0: CompanyShuyunOpenPlatformConfig|null, 1: JsonResponse|null}
     */
    private function resolveOfflineBenefitTenantByShadowBenefitId(array $decoded, ShuyunInboundSignedPrepareMode $mode): array
    {
        $bid = $this->trimStringish($decoded['benefitId'] ?? null);
        if ($bid === '') {
            return [null, null];
        }

        $rows = $this->offlineBenefitRepository->findAllByBenefitId($bid);
        if (\count($rows) === 0) {
            return [null, null];
        }
        if (\count($rows) > 1) {
            return [null, $this->ambiguousBenefitIdAcrossCompaniesResponse($mode)];
        }

        $companyId = $rows[0]->getCompanyId();
        $config = $this->configRepository->findOneByCompanyId($companyId);

        return [$config, null];
    }

    private function ambiguousBenefitIdAcrossCompaniesResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40308', 'AMBIGUOUS_BENEFIT_ID'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'AMBIGUOUS_BENEFIT_ID',
            ], 403),
        };
    }

    private function offlineBenefitUnknownShadowResponse(): JsonResponse
    {
        return response()->json([
            'code' => 403,
            'msg' => 'OFFLINE_BENEFIT_NOT_REGISTERED',
        ], 403);
    }

    private function ambiguousPlatCodeResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40307', 'AMBIGUOUS_PLAT_CODE'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'AMBIGUOUS_PLAT_CODE',
            ], 403),
        };
    }

    private function invalidJsonResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40001', 'INVALID_JSON'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 400,
                'msg' => 'INVALID_JSON',
            ], 400),
        };
    }

    private function appIdRequiredResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40301', 'APP_ID_REQUIRED'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'APP_ID_REQUIRED',
            ], 403),
        };
    }

    private function unknownAppResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40302', 'UNKNOWN_APP'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'UNKNOWN_APP',
            ], 403),
        };
    }

    private function secretNotConfiguredResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40304', 'CALLBACK_IDENTITY_SECRET_NOT_CONFIGURED'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'CALLBACK_IDENTITY_SECRET_NOT_CONFIGURED',
            ], 403),
        };
    }

    private function invalidSignResponse(ShuyunInboundSignedPrepareMode $mode): JsonResponse
    {
        return match ($mode) {
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange => $this->loyaltyJsonResponse('40305', 'INVALID_SIGN'),
            ShuyunInboundSignedPrepareMode::OfflineBenefit => response()->json([
                'code' => 403,
                'msg' => 'INVALID_SIGN',
            ], 403),
        };
    }

    private function loyaltyJsonResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'msg' => $message,
            'code' => $code,
            'success' => 'false',
        ], 200);
    }
}
