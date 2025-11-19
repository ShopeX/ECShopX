<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Interfaces;

interface UploadTokenInterface
{
    /**
     * 获取上传 token
     * @param  string|int $companyId 公司 ID
     * @param  string $group     业务分组，如 item aftersale 等
     * @param  string $fileName  指定上传的的文件名称，不指定将会自动生成
     * @return array|void
     */
    public function getToken($companyId, $group = null, $fileName = null);
    /**
     * 生成上传的文件名
     * @param  string|int $companyId 公司 ID
     * @param  string $group     业务分组，如 item aftersale 等
     * @param  string $fileName  指定上传的的文件名称，不指定将会自动生成
     * @return string
     */
    public function getUploadName($companyId, $group = null, $fileName = null);

    /**
     * 上传数据到存储服务上
     * @param  string|int $companyId 公司 ID
     * @param  string $group     业务分组，如 item aftersale 等
     * @param  string $fileName  指定上传的的文件名称，不指定将会自动生成
     * @param string $fileContent 文件的二进制数据
     * @return array
     */
    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array;
}
