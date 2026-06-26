<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 数云 POST JSONArray Token 推送：按 authValue 更新行（platCode 可为空）。当前不对回调验签，须由网络层（白名单 / 专线等）保障来源可信。
 */
final class ShuyunOpenPlatformTokenCallbackService
{
    private CompanyShuyunOpenPlatformConfigRepository $repository;

    public function __construct(CompanyShuyunOpenPlatformConfigRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array{code:int,msg:string,data:string}
     */
    public function handle(Request $request): array
    {
        $rawBody = (string) $request->getContent();

        $items = $this->parsePayload($rawBody);
        if ($items === null) {
            return $this->resp(400, 'INVALID_BODY');
        }
        if ($items === []) {
            return $this->resp(200, 'SUCCESS');
        }

        $appIdResult = $this->extractUniqueAppId($items);
        if ($appIdResult['error'] !== null) {
            return $this->resp($appIdResult['code'], $appIdResult['error']);
        }
        if ($appIdResult['appId'] === null) {
            return $this->resp(200, 'SUCCESS');
        }
        $appId = $appIdResult['appId'];

        $credentialRow = $this->repository->findOneByAppId($appId);
        if ($credentialRow === null) {
            return $this->resp(403, 'NO_APP_CONFIG');
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $authValue = isset($item['authValue']) ? (string) $item['authValue'] : '';
            if ($authValue === '') {
                continue;
            }
            $accessToken = isset($item['accessToken']) ? (string) $item['accessToken'] : '';
            $isOverDue = isset($item['isOverDue']) ? (string) $item['isOverDue'] : null;

            $target = $this->repository->findOneByAuthValue($authValue);
            if ($target === null) {
                if (($credentialRow->getAuthValue() ?? '') === $authValue) {
                    $target = $credentialRow;
                } else {
                    return $this->resp(400, 'UNKNOWN_AUTH_VALUE');
                }
            }
            if ($target->getCompanyId() !== $credentialRow->getCompanyId()) {
                return $this->resp(403, 'COMPANY_MISMATCH');
            }

            $target->setAccessToken($accessToken !== '' ? $accessToken : null);
            if ($isOverDue !== null) {
                $target->setIsOverDue($isOverDue);
            }
            if ($target->getAppId() === null || $target->getAppId() === '') {
                $target->setAppId($appId);
            }
            $this->repository->saveTokenCallbackRowWithRetry($target);
        }

        return $this->resp(200, 'SUCCESS');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{error: ?string, appId: ?string, code: int}
     */
    private function extractUniqueAppId(array $items): array
    {
        $seen = [];
        $hasPayload = false;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $authValue = isset($item['authValue']) ? (string) $item['authValue'] : '';
            if ($authValue === '') {
                continue;
            }
            $hasPayload = true;
            $appId = isset($item['appId']) ? (string) $item['appId'] : '';
            if ($appId === '') {
                return ['error' => 'MISSING_APP_ID', 'appId' => null, 'code' => 400];
            }
            $seen[$appId] = true;
        }
        if (!$hasPayload) {
            return ['error' => null, 'appId' => null, 'code' => 200];
        }
        $keys = array_keys($seen);
        if (count($keys) > 1) {
            return ['error' => 'INCONSISTENT_APP_ID', 'appId' => null, 'code' => 400];
        }

        return ['error' => null, 'appId' => (string) $keys[0], 'code' => 200];
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function parsePayload(string $rawBody): ?array
    {
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
        if (!is_array($decoded)) {
            return null;
        }
        if ($decoded === []) {
            return [];
        }
        if (array_is_list($decoded)) {
            return $decoded;
        }
        if (isset($decoded['accessToken'], $decoded['authValue'])) {
            return [$decoded];
        }

        return null;
    }

    /**
     * @return array{code:int,msg:string,data:string}
     */
    private function resp(int $code, string $msg): array
    {
        return ['code' => $code, 'msg' => $msg, 'data' => ''];
    }
}
