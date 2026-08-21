<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace AliyunsmsBundle\Services;

use InvalidArgumentException;

class SmsSignMapper
{
    public function mapAuditStatus(string $auditStatus): int
    {
        $map = [
            'AUDIT_STATE_INIT' => 0,
            'AUDIT_STATE_PASS' => 1,
            'AUDIT_STATE_NOT_PASS' => 2,
            'AUDIT_STATE_CANCEL' => 2,
        ];

        if (array_key_exists($auditStatus, $map)) {
            return $map[$auditStatus];
        }

        throw new InvalidArgumentException('Unknown AuditStatus: ' . $auditStatus);
    }

    public function mapSignStatus(int $signStatus): int
    {
        $map = [
            0 => 0,
            1 => 1,
            2 => 2,
            10 => 2,
        ];

        if (array_key_exists($signStatus, $map)) {
            return $map[$signStatus];
        }

        throw new InvalidArgumentException('Unknown SignStatus: ' . $signStatus);
    }

    /**
     * @param mixed $value
     */
    public function normalizeThirdParty($value): int
    {
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return 1;
        }

        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return 0;
        }

        throw new InvalidArgumentException('Invalid third_party value: ' . var_export($value, true));
    }

    /**
     * @param mixed $value
     */
    public function normalizeThirdPartyBool($value): bool
    {
        return $this->normalizeThirdParty($value) === 1;
    }

    /**
     * @param array|string|null $reasonOrAuditInfo
     */
    public function extractRejectReason($reasonOrAuditInfo): string
    {
        if (is_string($reasonOrAuditInfo)) {
            return $reasonOrAuditInfo;
        }

        if (!is_array($reasonOrAuditInfo)) {
            return '';
        }

        if (isset($reasonOrAuditInfo['RejectInfo']) && is_string($reasonOrAuditInfo['RejectInfo'])) {
            return $reasonOrAuditInfo['RejectInfo'];
        }

        if (isset($reasonOrAuditInfo['Reason']['RejectInfo']) && is_string($reasonOrAuditInfo['Reason']['RejectInfo'])) {
            return $reasonOrAuditInfo['Reason']['RejectInfo'];
        }

        if (isset($reasonOrAuditInfo['AuditInfo']['RejectInfo']) && is_string($reasonOrAuditInfo['AuditInfo']['RejectInfo'])) {
            return $reasonOrAuditInfo['AuditInfo']['RejectInfo'];
        }

        return '';
    }

    public function buildUpsertPayload(array $cloudDetail, ?array $localExisting = null): array
    {
        $payload = [];

        if (isset($cloudDetail['SignName'])) {
            $payload['sign_name'] = mb_substr((string) $cloudDetail['SignName'], 0, 20);
        }

        if (isset($cloudDetail['SignStatus'])) {
            $payload['status'] = $this->mapSignStatus((int) $cloudDetail['SignStatus']);
        } elseif (isset($cloudDetail['AuditStatus'])) {
            $payload['status'] = $this->mapAuditStatus((string) $cloudDetail['AuditStatus']);
        }

        $payload['reason'] = mb_substr($this->extractRejectReason($cloudDetail), 0, 255);

        if (array_key_exists('Remark', $cloudDetail)) {
            $payload['remark'] = mb_substr((string) ($cloudDetail['Remark'] ?? ''), 0, 255);
        } elseif ($localExisting === null) {
            // 新建时 remark 非空列，云端偶发缺失则兜底
            $payload['remark'] = '';
        }

        if (array_key_exists('QualificationId', $cloudDetail)) {
            $payload['qualification_id'] = (string) ($cloudDetail['QualificationId'] ?? '');
        }

        if (array_key_exists('ThirdParty', $cloudDetail)) {
            $payload['third_party'] = $this->normalizeThirdParty($cloudDetail['ThirdParty']);
        } elseif ($localExisting === null) {
            $payload['third_party'] = 0;
        }

        if (isset($cloudDetail['SignSource']) && $cloudDetail['SignSource'] !== '' && $cloudDetail['SignSource'] !== null) {
            $payload['sign_source'] = (string) $cloudDetail['SignSource'];
        } elseif ($localExisting !== null && isset($localExisting['sign_source']) && $localExisting['sign_source'] !== '') {
            $payload['sign_source'] = $localExisting['sign_source'];
        } elseif ($localExisting === null) {
            // GetSmsSign 通常不返回 SignSource；本地新建必填，默认企事业单位(0)
            $payload['sign_source'] = '0';
        }

        return $payload;
    }
}
