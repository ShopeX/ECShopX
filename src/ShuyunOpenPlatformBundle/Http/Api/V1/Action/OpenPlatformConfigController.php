<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Exception\ShuyunOpenPlatformManageConfigGateException;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformManageConfigService;

class OpenPlatformConfigController extends Controller
{
    public function getConfig(Request $request)
    {
        $companyId = $this->companyIdFromAuth();
        $data = app(ShuyunOpenPlatformManageConfigService::class)->getAdminView($companyId);

        return $this->response->array($data ?? []);
    }

    public function putConfig(Request $request)
    {
        $companyId = $this->companyIdFromAuth();
        $validator = app('validator')->make(
            $request->all(),
            [
                'app_id' => 'sometimes|nullable|string|max:64',
                'app_secret' => 'sometimes|nullable|string|max:512',
                'is_enabled' => 'sometimes',
            ],
            [
                'app_id.string' => '应用 ID（app_id）须为字符串。',
                'app_id.max' => '应用 ID（app_id）长度不能超过 64 个字符。',
                'app_secret.string' => '应用密钥（app_secret）须为字符串。',
                'app_secret.max' => '应用密钥（app_secret）长度不能超过 512 个字符。',
                'access_token.string' => '访问令牌（access_token）须为字符串。',
            ],
            [
                'app_id' => '应用 ID（app_id）',
                'app_secret' => '应用密钥（app_secret）',
                'is_enabled' => '启用状态（is_enabled）',
            ],
        );
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        try {
            app(ShuyunOpenPlatformManageConfigService::class)->saveFromAdmin($companyId, $request->all());
        } catch (ShuyunOpenPlatformManageConfigGateException $e) {
            throw new ResourceException($e->getMessage());
        }

        return $this->response->array(['ok' => true]);
    }

    private function companyIdFromAuth(): int
    {
        return (int) app('auth')->user()->get('company_id');
    }
}
