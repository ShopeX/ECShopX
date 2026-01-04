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

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Config;

class UrlConfig
{
    // 3.1 校验入参签名以及签名生成前的字符串排序
    public const CHECK_SIGN = '/v1_0/ehospital/openapi/kz/web/diagnosis/checkSign';

    // 4.1 获取问诊状态信息接口
    public const DIAGNOSIS_STATUS = '/v1_0/ehospital/openapi/kz/web/diagnosis/status';

    // 4.2新 新增问诊单接口
    public const PREDEMAND_INITPREDEMAND = '/v1_0/ehospital/openapi/kz/web/predemand/initPreDemand';

    // 4.3 获取西药处方列表接口
    public const PRESCRIPTION_LIST = '/v1_0/ehospital/openapi/kz/prescription/list';

    // 4.4 获取问诊聊天记录列表接口
    public const TEXT_RECORD_LIST = '/v1_0/ehospital/openapi/kz/textRecord/list';

    // 4.5 获取问诊列表接口
    public const DIAGNOSIS_LIST = '/v1_0/ehospital/openapi/kz/diagnosis/list';

    // 4.6 获取问诊详情信息接口
    public const DIAGNOSIS_GET = '/v1_0/ehospital/openapi/kz/diagnosis/get';

    // 4.7 同步药品信息接口
    public const MEDICINE_SYNC = '/v1_0/ehospital/openapi/kz/medicine/sync';

    // 4.8 查询药品审核信息接口
    public const MEDICINE_QUERYAUDITSTATUS = '/v1_0/ehospital/openapi/kz/medicine/queryAuditStatus';

    // 4.9 查询门店信息接口
    public const STORE_QUERY =  '/v1_0/ehospital/openapi/kz/store/query';
}
