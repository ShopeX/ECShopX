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

namespace AdaPayBundle\Services\CallBack;

use AdaPayBundle\Services\OpenAccountService;

class Entry
{
    /**
     * 进件成功
     *
        "object": "queryEntryUser",
        "status": "succeeded",
        "prod_mode": "true",
        "request_id": "req_mer_20200403115903666666",
        "test_api_key": "api_test_364ac64b-xxxx-xxxx-xxxx-144411199999",
        "live_api_key": "api_live_bf636064-xxxx-xxxx-xxxx-ddd11ffe0000",
        "app_id_list": [{
        "app_id": "app_2fc6b8a4-xxxx-xxxx-xxxx-34eeee33bbbbb",
        "app_name": "test_17612762359",
        "cre_ts": 1585886351000,
        "cre_user": "0075757575757575",
        "id": 49045,
        "mer_cust_id": "0075757575757575",
        "remark": "",
        "stat": "N",
        "upd_ts": 1585886351000,
        "upd_user": "0075757575757575"
        }
        ],
        "sign_view_url": "https://xxx.xxxxxx.com/api/downloadContract?contractId=EieUFRY4eR6&digest=78F056245F32C28F5042781E66668888",
        "login_pwd": "Axxx6666"
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $openAccountService = new OpenAccountService();
        $openAccountService->entryCallback($data);
        return ['success'];
    }

    /**
     * 进件失败
     *
        "object": "userEntry",
        "status": "failed",
        "error_type": "api_error",
        "error_msg": "营业执照认证失败",
        "prod_mode": "true",
        "request_id": "req_mer_20200403166666655555"
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $openAccountService = new OpenAccountService();
        $openAccountService->entryCallback($data);
        return ['success'];
    }
}
