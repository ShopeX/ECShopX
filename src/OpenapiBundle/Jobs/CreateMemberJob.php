<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Jobs;

use EspierBundle\Jobs\Job;
use OpenapiBundle\Services\Member\MemberService;

class CreateMemberJob extends Job
{
    /**
     * 企业id
     * @var int
     */
    protected $companyId;

    /**
     * 表单数据
     * @var array
     */
    protected $formData;

    public function __construct(int $companyId, array $formData)
    {
        $this->companyId = $companyId;
        $this->formData = $formData;
    }

    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        try {
            $this->result = (new MemberService())->createDetail($this->companyId, $this->formData);
        } catch (\Exception $exception) {
            app("log")->info(sprintf("Openapi_CreateMemberJob_Error. error: %s, file: %s, line: %d, data: %s", $exception->getMessage(), $exception->getFile(), $exception->getLine(), json_encode(["company_id" => $this->companyId, "params" => $this->formData], JSON_UNESCAPED_UNICODE)));
        } catch (\Throwable $throwable) {
            app("log")->info(sprintf("Openapi_CreateMemberJob_Error. error: %s, file: %s, line: %d, data: %s", $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), json_encode(["company_id" => $this->companyId, "params" => $this->formData], JSON_UNESCAPED_UNICODE)));
        }
        return true;
    }

    protected $result = [];

    /**
     * 返回创建的会员结果集
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
