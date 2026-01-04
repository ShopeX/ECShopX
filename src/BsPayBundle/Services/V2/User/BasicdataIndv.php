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

/**
 * 个人用户基本信息开户
 *
 */
namespace BsPayBundle\Services\V2\User;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2UserBasicdataIndvRequest;


class BasicdataIndv {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V2UserBasicdataIndvRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_seq_id" => $data['req_seq_id'],
                "req_date" => date("Ymd"),
                "name" => $data['name'],
                "cert_type" => "00",
                "cert_no" => $data['cert_no'],
                "cert_validity_type" => $data['cert_validity_type'],
                "cert_begin_date" => $data['cert_begin_date'],
                "cert_end_date" => $data['cert_end_date'],
                "mobile_no" => $data['mobile_no'],
            ),
        );
        // 设置非必填字段
        $extendInfoMap = $this->getExtendInfos($data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        // $request->setExtendInfo($extendInfoMap);
        # 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        return $result;

        
    }

    /**
     * 非必填字段
     *
     */
    public function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        // 经营简称
        // $extendInfoMap["short_name"]= ;
        // 联系人电子邮箱
        // $extendInfoMap["contact_email"]= "jeff.peng@huifu.com";
        // 管理员账号
        // $extendInfoMap["login_name"]= "Lg2022022201374721361";
        // 操作员
        // $extendInfoMap["operator_id"]= "";
        // 是否发送短信标识
        $extendInfoMap["sms_send_flag"]= "Y";
        // 扩展方字段
        $extendInfoMap["expand_id"]= "";
        // 文件列表
        // $extendInfoMap["file_list"]= $this->getFileList();
        // 公司类型
        // $extendInfoMap["ent_type"]= "";
        return $extendInfoMap;
    }

    public function getFileList() {
        $dto = array();
        // 文件类型
        // $dto["file_type"] = "test";
        // 文件jfileID
        // $dto["file_id"] = "test";
        // 文件名称
        // $dto["file_name"] = "";

        $dtoList = array();
        array_push($dtoList, $dto);
        return json_encode($dtoList,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

}







