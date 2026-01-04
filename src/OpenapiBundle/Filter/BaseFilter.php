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

namespace OpenapiBundle\Filter;

use Carbon\Carbon;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\Member\MemberService;

abstract class BaseFilter implements \ArrayAccess
{
    public function offsetExists($offset)
    {
        return isset($this->filter[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->filter[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->filter[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->filter[$offset]);
    }

    protected $filter = [];

    /**
     * 获取过滤内容
     * @return array
     */
    final public function get(): array
    {
        return $this->filter;
    }

    /**
     * 设置过滤内容
     * @param string $key key名
     * @param mixed $value value值
     * @return $this
     */
    final public function set(string $key, $value): self
    {
        $this->filter[$key] = $value;
        return $this;
    }

    /**
     * 请求数据
     * @var array
     */
    protected $requestData;

    protected $companyId;

    public function __construct(?array $requestInputData = null)
    {
        $this->requestData = is_null($requestInputData) ? app("request")->input() : $requestInputData;
        // 设置企业id
        if (!isset($this->requestData["company_id"])) {
            // 获取企业id
            $auth = (array)app("request")->attributes->get("auth");
            // 企业id
            $this->requestData["company_id"] = (int)$auth["company_id"];
        }
        $this->set("company_id", $this->requestData["company_id"]);
        // 初始化
        $this->init();
        // 过滤空值
        $this->unsetEmptyValue();
    }

    /**
     * 初始化过滤参数
     * @return void
     */
    abstract protected function init();

    /**
     * 根据手机号设置用户id
     */
    protected function setUserIdByMobile()
    {
        if (isset($this->requestData["mobile"])) {
            $member = (new MemberService())->find(["company_id" => $this->filter["company_id"], "mobile" => $this->requestData["mobile"]]);
            $this->filter["user_id"] = (int)($member["user_id"] ?? 0);
        }
    }

    /**
     * 根据时间范围来做过滤
     * @param string $startDateFilterKey 开始时间的字段名
     * @param string $endDateFilterKey 结束时间的字段名
     * @param int $defaultStartTimestamp 开始时间的默认值，如果是0则不过滤
     * @param int $defaultEndTimestamp 结束时间的默认值，如果是0则不过滤
     */
    protected function setTimeRange(string $startDateFilterKey, string $endDateFilterKey, int $defaultStartTimestamp = 0, int $defaultEndTimestamp = 0)
    {
        // 判断开始时间
        if (isset($this->requestData["start_date"]) && !empty($this->requestData["start_date"])) {
            $this->filter[$startDateFilterKey] = $this->transformTimeToCarbon((string)$this->requestData["start_date"])->getTimestamp();
        } elseif ($defaultStartTimestamp > 0) {
            $this->filter[$startDateFilterKey] = $defaultStartTimestamp;
        }
        // 判断结束时间
        if (isset($this->requestData["end_date"]) && !empty($this->requestData["end_date"])) {
            $this->filter[$endDateFilterKey] = $this->transformTimeToCarbon((string)$this->requestData["end_date"])->getTimestamp();
        } elseif ($defaultEndTimestamp > 0) {
            $this->filter[$endDateFilterKey] = $defaultEndTimestamp;
        }
    }

    /**
     * 将时间转成Carbon格式
     * @param string $time
     * @return Carbon
     */
    protected function transformTimeToCarbon(string $time): Carbon
    {
        if (is_numeric($time)) {
            throw new ErrorException(ErrorCode::VALIDATION_TIMESTAMP_ERROR, "时间格式有误");
        }
        try {
            return Carbon::parse($time);
        } catch (\Exception $exception) {
            throw new ErrorException(ErrorCode::VALIDATION_TIMESTAMP_ERROR, "时间格式有误");
        }
    }

    /**
     * 需要在unsetEmptyValue方法中忽略的key值
     * @var string[]
     */
    protected $ignoreEmptyKeys = [];

    /**
     * 对空值的处理，目前的逻辑是直接不过滤
     * 例：query: ?distributor_id=&shop_code=1  此时distributor_id的值为空，在isset()中会被通过，所以要手动从filter中过滤
     */
    final protected function unsetEmptyValue()
    {
        foreach ($this->filter as $key => $value) {
            // 如果值为空则直接删除这个key
            if ($value === "" && !in_array($key, $this->ignoreEmptyKeys)) {
                unset($this->filter[$key]);
            }
        }
    }
}
