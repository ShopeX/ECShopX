<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeSyncService;

/**
 * 后管 API 响应体与 {@see OpenPlatformConfigController::putConfig} 一致：
 * 成功为 `$this->response->array([...])` 且含顶层 **`ok`**；网关/环境类错误抛 {@see ResourceException}。
 * 等级行级校验失败仍返回 **`ok: false`** + `failures`（与计划「同步报表」一致，区别于创建平台整段失败走异常）。
 */
class LoyaltyGradeSyncController extends Controller
{
    public function postManualSync()
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        try {
            $report = app(ShuyunOpenPlatformLoyaltyGradeSyncService::class)->syncByCompanyIdWithReport($companyId);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new ResourceException($e->getMessage());
        } catch (ShuyunGatewayHttpException $e) {
            throw new ResourceException($e->getMessage());
        } catch (ShuyunGatewayJsonException $e) {
            throw new ResourceException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw new ResourceException($e->getMessage());
        }

        if (($report['ok'] ?? false) === true) {
            return $this->response->array([
                'ok' => true,
                'synced_count' => (int) ($report['synced_count'] ?? 0),
            ]);
        }

        return $this->response->array($report);
    }
}
